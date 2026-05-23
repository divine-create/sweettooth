<?php

namespace App\Livewire\BranchDashboard\Payroll;

use App\Livewire\BaseComponent;
use App\Models\BankAccount;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\PayrollService;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class RunView extends BaseComponent
{
    use Interactions;

    public PayrollRun $run;

    // Add employee modal
    public bool $showAddModal = false;

    public ?string $add_employee_id = null;

    public float $add_base_salary = 0;

    public float $add_overtime_hours = 0;

    public float $add_overtime_rate = 0;

    public float $add_allowances = 0;

    public float $add_tax_deductions = 0;

    public float $add_other_deductions = 0;

    // Edit payslip modal
    public bool $showEditModal = false;

    public ?int $editing_payroll_id = null;

    public float $edit_base_salary = 0;

    public float $edit_overtime_hours = 0;

    public float $edit_overtime_rate = 0;

    public float $edit_allowances = 0;

    public float $edit_tax_deductions = 0;

    public float $edit_other_deductions = 0;

    // Confirm cancel modal
    public bool $showCancelModal = false;

    protected function getModelClass(): string
    {
        return PayrollRun::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    protected function getFilteredQuery()
    {
        return PayrollRun::query();
    }

    public function mount(PayrollRun $run): void
    {
        $this->run = $run;
    }

    public function updatedAddEmployeeId(): void
    {
        $employee = User::find($this->add_employee_id);
        if ($employee) {
            $this->add_base_salary    = (float) ($employee->salary ?? 0);
            $this->add_overtime_rate  = (float) ($employee->hourly_rate ?? 0);
        }
    }

    public function openAddModal(): void
    {
        $this->reset(['add_employee_id', 'add_base_salary', 'add_overtime_hours', 'add_overtime_rate',
            'add_allowances', 'add_tax_deductions', 'add_other_deductions']);
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
    }

    public function addEmployee(PayrollService $payrollService): void
    {
        $this->validate([
            'add_employee_id'  => 'required|exists:users,id',
            'add_base_salary'  => 'required|numeric|min:0',
            'add_overtime_hours' => 'nullable|numeric|min:0',
            'add_overtime_rate'  => 'nullable|numeric|min:0',
            'add_allowances'     => 'nullable|numeric|min:0',
            'add_tax_deductions' => 'nullable|numeric|min:0',
            'add_other_deductions' => 'nullable|numeric|min:0',
        ]);

        try {
            $payrollService->addPayslipToRun($this->run, [
                'employee_id'      => $this->add_employee_id,
                'base_salary'      => $this->add_base_salary,
                'overtime_hours'   => $this->add_overtime_hours,
                'overtime_rate'    => $this->add_overtime_rate,
                'allowances'       => $this->add_allowances,
                'tax_deductions'   => $this->add_tax_deductions,
                'other_deductions' => $this->add_other_deductions,
            ]);

            $this->toast()->success('Payslip added to run.')->send();
            $this->closeAddModal();
            $this->run->refresh();
        } catch (\Exception $e) {
            $this->toast()->error('Error: ' . $e->getMessage())->send();
        }
    }

    public function openEditModal(int $payrollId): void
    {
        $payroll = Payroll::find($payrollId);

        if (! $payroll || $payroll->payroll_run_id !== $this->run->id) {
            $this->toast()->error('Payslip not found.')->send();
            return;
        }

        if ($payroll->status !== 'draft') {
            $this->toast()->error('Only draft payslips can be edited.')->send();
            return;
        }

        $this->editing_payroll_id   = $payrollId;
        $this->edit_base_salary     = (float) $payroll->base_salary;
        $this->edit_overtime_hours  = (float) $payroll->overtime_hours;
        $this->edit_overtime_rate   = (float) $payroll->overtime_rate;
        $this->edit_allowances      = (float) $payroll->allowances;
        $this->edit_tax_deductions  = (float) $payroll->tax_deductions;
        $this->edit_other_deductions = (float) $payroll->other_deductions;
        $this->showEditModal        = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editing_payroll_id = null;
    }

    public function updatePayslip(): void
    {
        $this->validate([
            'edit_base_salary'     => 'required|numeric|min:0',
            'edit_overtime_hours'  => 'nullable|numeric|min:0',
            'edit_overtime_rate'   => 'nullable|numeric|min:0',
            'edit_allowances'      => 'nullable|numeric|min:0',
            'edit_tax_deductions'  => 'nullable|numeric|min:0',
            'edit_other_deductions' => 'nullable|numeric|min:0',
        ]);

        $payroll = Payroll::find($this->editing_payroll_id);

        if (! $payroll || $payroll->status !== 'draft') {
            $this->toast()->error('Payslip not found or not editable.')->send();
            return;
        }

        $payroll->update([
            'base_salary'      => $this->edit_base_salary,
            'overtime_hours'   => $this->edit_overtime_hours,
            'overtime_rate'    => $this->edit_overtime_rate,
            'allowances'       => $this->edit_allowances,
            'tax_deductions'   => $this->edit_tax_deductions,
            'other_deductions' => $this->edit_other_deductions,
        ]);

        $this->toast()->success('Payslip updated.')->send();
        $this->closeEditModal();
        $this->run->refresh();
    }

    public function approvePayslip(int $payrollId, PayrollService $payrollService): void
    {
        $payroll = Payroll::find($payrollId);

        if (! $payroll) {
            $this->toast()->error('Payslip not found.')->send();
            return;
        }

        try {
            $payrollService->approvePayslip($payroll);
            $this->run->refresh();
            $this->toast()->success("Payslip approved for {$payroll->employee?->name}.")->send();
        } catch (\Exception $e) {
            $this->toast()->error('Approval failed: ' . $e->getMessage())->send();
        }
    }

    public function markPaid(int $payrollId, PayrollService $payrollService): void
    {
        $payroll = Payroll::find($payrollId);

        if (! $payroll) {
            $this->toast()->error('Payslip not found.')->send();
            return;
        }

        try {
            $payrollService->markPaid($payroll);
            $this->run->refresh();
            $this->toast()->success("Marked paid for {$payroll->employee?->name}.")->send();
        } catch (\Exception $e) {
            $this->toast()->error('Failed: ' . $e->getMessage())->send();
        }
    }

    public function approveAll(PayrollService $payrollService): void
    {
        try {
            $count = $payrollService->approveRun($this->run);
            $this->run->refresh();
            $this->toast()->success("Approved {$count} payslip(s).")->send();
        } catch (\Exception $e) {
            $this->run->refresh();
            $this->toast()->warning('Some payslips failed: ' . $e->getMessage())->send();
        }
    }

    public function markAllPaid(PayrollService $payrollService): void
    {
        try {
            $count = $payrollService->markRunPaid($this->run);
            $this->run->refresh();
            $this->toast()->success("Marked {$count} payslip(s) as paid.")->send();
        } catch (\Exception $e) {
            $this->run->refresh();
            $this->toast()->warning('Some payslips failed: ' . $e->getMessage())->send();
        }
    }

    public function cancelRun(PayrollService $payrollService): void
    {
        try {
            $payrollService->cancelRun($this->run);
            $this->run->refresh();
            $this->showCancelModal = false;
            $this->toast()->success('Payroll run cancelled.')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Failed: ' . $e->getMessage())->send();
        }
    }

    public function render()
    {
        $payrolls = $this->run->payrolls()->with(['employee', 'bankAccount'])->orderBy('id')->get();

        $availableEmployees = User::where('branch_id', $this->run->branch_id)
            ->where('is_active', true)
            ->whereNotIn('id', $payrolls->pluck('employee_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'employee_number']);

        $bankAccounts = BankAccount::orderBy('bank_name')->get(['id', 'bank_name', 'account_number']);

        return view('livewire.branch-dashboard.payroll.run-view', [
            'payrolls'           => $payrolls,
            'availableEmployees' => $availableEmployees,
            'bankAccounts'       => $bankAccounts,
        ]);
    }
}
