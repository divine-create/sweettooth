<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Build the selectable employee rows for a new payroll run, pre-filled from
     * each active employee's stored salary / hourly rate.
     */
    public function buildEmployeeRows(string $branchId): Collection
    {
        return User::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'employee_number', 'salary', 'hourly_rate'])
            ->map(fn (User $employee) => [
                'employee_id' => $employee->id,
                'name' => $employee->name,
                'employee_number' => $employee->employee_number,
                'base_salary' => (float) ($employee->salary ?? 0),
                'overtime_hours' => 0,
                'overtime_rate' => (float) ($employee->hourly_rate ?? 0),
                'allowances' => 0,
                'tax_deductions' => 0,
                'other_deductions' => 0,
                'selected' => false,
            ])
            ->values();
    }

    /**
     * Create a payroll run and a draft payslip for each selected employee row.
     */
    public function createRun(array $data, array $rows): PayrollRun
    {
        return DB::transaction(function () use ($data, $rows) {
            $run = PayrollRun::create([
                'branch_id' => $data['branch_id'],
                'name' => $data['name'],
                'pay_period_start' => $data['pay_period_start'],
                'pay_period_end' => $data['pay_period_end'],
                'payment_date' => $data['payment_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
                'created_by_id' => $data['created_by_id'] ?? auth()->id(),
            ]);

            foreach ($rows as $row) {
                if (empty($row['employee_id'])) {
                    continue;
                }

                $run->payrolls()->create([
                    'branch_id' => $run->branch_id,
                    'employee_id' => $row['employee_id'],
                    'pay_period_start' => $run->pay_period_start,
                    'pay_period_end' => $run->pay_period_end,
                    'payment_date' => $run->payment_date,
                    'base_salary' => (float) ($row['base_salary'] ?? 0),
                    'overtime_hours' => (float) ($row['overtime_hours'] ?? 0),
                    'overtime_rate' => (float) ($row['overtime_rate'] ?? 0),
                    'allowances' => (float) ($row['allowances'] ?? 0),
                    'tax_deductions' => (float) ($row['tax_deductions'] ?? 0),
                    'other_deductions' => (float) ($row['other_deductions'] ?? 0),
                    'bank_account_id' => $row['bank_account_id'] ?? null,
                    'status' => 'draft',
                    'created_by_id' => $run->created_by_id,
                ]);
            }

            return $run;
        });
    }

    /**
     * Add a single draft payslip to an existing (non-finalised) run.
     */
    public function addPayslipToRun(PayrollRun $run, array $data): Payroll
    {
        if (! in_array($run->status, ['draft', 'approved'], true)) {
            throw new \InvalidArgumentException('Payslips can only be added to a draft or approved run.');
        }

        return $run->payrolls()->create([
            'branch_id' => $run->branch_id,
            'employee_id' => $data['employee_id'],
            'pay_period_start' => $run->pay_period_start,
            'pay_period_end' => $run->pay_period_end,
            'payment_date' => $run->payment_date,
            'base_salary' => (float) ($data['base_salary'] ?? 0),
            'overtime_hours' => (float) ($data['overtime_hours'] ?? 0),
            'overtime_rate' => (float) ($data['overtime_rate'] ?? 0),
            'allowances' => (float) ($data['allowances'] ?? 0),
            'tax_deductions' => (float) ($data['tax_deductions'] ?? 0),
            'other_deductions' => (float) ($data['other_deductions'] ?? 0),
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'status' => 'draft',
            'created_by_id' => auth()->id(),
        ]);
    }

    /**
     * Approve a single draft payslip. The PayrollObserver posts the GL accrual
     * once the status flips to "approved".
     */
    public function approvePayslip(Payroll $payroll): void
    {
        if ($payroll->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft payslips can be approved.');
        }

        $actor = $this->actor();

        $payroll->update([
            'status' => 'approved',
            'approved_by_id' => $actor?->getKey(),
            'approved_by_type' => $actor ? $actor::class : null,
            'approved_at' => now(),
        ]);

        $this->syncRunStatus($payroll->payrollRun);
    }

    /**
     * Mark an approved payslip as paid. The PayrollObserver posts the GL payment
     * (and the accrual first, if it was never posted) on the flip to "paid".
     */
    public function markPaid(Payroll $payroll): void
    {
        if ($payroll->status !== 'approved') {
            throw new \InvalidArgumentException('Only approved payslips can be marked as paid.');
        }

        $payroll->update([
            'status' => 'paid',
            'payment_date' => $payroll->payment_date ?? Carbon::today(),
        ]);

        $this->syncRunStatus($payroll->payrollRun);
    }

    /**
     * Cancel a payslip that has not yet been paid.
     */
    public function cancelPayslip(Payroll $payroll): void
    {
        if ($payroll->status === 'paid') {
            throw new \InvalidArgumentException('Paid payslips cannot be cancelled.');
        }

        $payroll->update(['status' => 'cancelled']);

        $this->syncRunStatus($payroll->payrollRun);
    }

    /**
     * Approve every draft payslip in a run. Returns how many were approved.
     */
    public function approveRun(PayrollRun $run): int
    {
        $count = 0;

        foreach ($run->payrolls()->where('status', 'draft')->get() as $payroll) {
            $this->approvePayslip($payroll);
            $count++;
        }

        $this->syncRunStatus($run);

        return $count;
    }

    /**
     * Mark every approved payslip in a run as paid. Returns how many were paid.
     */
    public function markRunPaid(PayrollRun $run): int
    {
        $count = 0;

        foreach ($run->payrolls()->where('status', 'approved')->get() as $payroll) {
            $this->markPaid($payroll);
            $count++;
        }

        $this->syncRunStatus($run);

        return $count;
    }

    /**
     * Cancel a run and all of its not-yet-paid payslips.
     */
    public function cancelRun(PayrollRun $run): void
    {
        DB::transaction(function () use ($run) {
            $run->payrolls()->whereNotIn('status', ['paid', 'cancelled'])->update(['status' => 'cancelled']);
            $run->update(['status' => 'cancelled']);
        });
    }

    /**
     * Recompute a run's status from the state of its payslips.
     */
    protected function syncRunStatus(?PayrollRun $run): void
    {
        if (! $run) {
            return;
        }

        $statuses = $run->payrolls()->pluck('status');
        $active = $statuses->reject(fn ($s) => $s === 'cancelled');

        if ($active->isEmpty()) {
            // Everything cancelled (or no payslips left) -> only mark cancelled
            // if there actually were payslips and all are cancelled.
            $status = $statuses->isNotEmpty() ? 'cancelled' : 'draft';
        } elseif ($active->every(fn ($s) => $s === 'paid')) {
            $status = 'paid';
        } elseif ($active->every(fn ($s) => in_array($s, ['approved', 'paid'], true))) {
            $status = 'approved';
        } else {
            $status = 'draft';
        }

        if ($run->status !== $status) {
            $run->update(['status' => $status]);
        }
    }

    protected function actor(): ?User
    {
        $actor = function_exists('current_actor') ? current_actor() : auth()->user();

        return $actor instanceof User ? $actor : auth()->user();
    }
}
