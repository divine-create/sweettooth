<?php

namespace App\Livewire\BranchDashboard\Payroll;

use App\Livewire\BaseComponent;
use App\Services\PayrollService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class RunCreate extends BaseComponent
{
    use Interactions;

    public string $name = '';

    public string $pay_period_start = '';

    public string $pay_period_end = '';

    public ?string $payment_date = null;

    public string $notes = '';

    // Each row: employee_id, name, employee_number, base_salary, overtime_hours,
    //           overtime_rate, allowances, tax_deductions, other_deductions, selected
    public array $employeeRows = [];

    protected function getModelClass(): string
    {
        return \App\Models\PayrollRun::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    protected function getFilteredQuery()
    {
        return \App\Models\PayrollRun::query();
    }

    public function mount(PayrollService $payrollService): void
    {
        $this->pay_period_start = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->pay_period_end   = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->payment_date     = Carbon::now()->endOfMonth()->addDays(5)->format('Y-m-d');
        $this->name             = Carbon::now()->format('F Y') . ' Payroll';

        $branchId = current_branch_id();
        $this->employeeRows = $payrollService->buildEmployeeRows($branchId)->toArray();
    }

    public function updatedPayPeriodStart(): void
    {
        $this->updateSuggestedName();
    }

    public function updatedPayPeriodEnd(): void
    {
        $this->updateSuggestedName();
    }

    private function updateSuggestedName(): void
    {
        if ($this->pay_period_start) {
            $this->name = Carbon::parse($this->pay_period_start)->format('F Y') . ' Payroll';
        }
    }

    public function toggleAll(bool $value): void
    {
        foreach ($this->employeeRows as &$row) {
            $row['selected'] = $value;
        }
    }

    public function getPreviewTotalsProperty(): array
    {
        $gross = 0;
        $net   = 0;
        $count = 0;

        foreach ($this->employeeRows as $row) {
            if (! ($row['selected'] ?? false)) {
                continue;
            }

            $count++;
            $overtime = (float) ($row['overtime_hours'] ?? 0) * (float) ($row['overtime_rate'] ?? 0);
            $rowGross = (float) ($row['base_salary'] ?? 0) + (float) ($row['allowances'] ?? 0) + $overtime;
            $rowNet   = $rowGross - (float) ($row['tax_deductions'] ?? 0) - (float) ($row['other_deductions'] ?? 0);
            $gross   += $rowGross;
            $net     += $rowNet;
        }

        return compact('gross', 'net', 'count');
    }

    public function save(PayrollService $payrollService): void
    {
        $this->validate([
            'name'             => 'required|string|max:255',
            'pay_period_start' => 'required|date',
            'pay_period_end'   => 'required|date|after_or_equal:pay_period_start',
            'payment_date'     => 'nullable|date',
        ]);

        $selected = array_filter($this->employeeRows, fn ($r) => $r['selected'] ?? false);

        if (empty($selected)) {
            $this->toast()->error('Select at least one employee.')->send();
            return;
        }

        try {
            $run = $payrollService->createRun(
                [
                    'branch_id'        => current_branch_id(),
                    'name'             => $this->name,
                    'pay_period_start' => $this->pay_period_start,
                    'pay_period_end'   => $this->pay_period_end,
                    'payment_date'     => $this->payment_date,
                    'notes'            => $this->notes,
                    'created_by_id'    => auth()->id(),
                ],
                array_values($selected)
            );

            $this->toast()->success("Payroll run \"{$run->name}\" created with " . $run->payrolls()->count() . " payslips.")->send();
            $this->redirect(branch_route('branch-dashboard.accounting.payroll.run.view', ['run' => $run->id]));

        } catch (\Exception $e) {
            $this->toast()->error('Failed to create payroll run: ' . $e->getMessage())->send();
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.payroll.run-create');
    }
}
