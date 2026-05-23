<?php

namespace App\Livewire\BranchDashboard\Payroll;

use App\Livewire\BaseComponent;
use App\Models\BankAccount;
use App\Models\Payroll;
use App\Services\PayrollService;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class PayslipView extends BaseComponent
{
    use Interactions;

    public Payroll $payroll;

    // Edit mode
    public bool $editing = false;

    public float $edit_base_salary = 0;

    public float $edit_overtime_hours = 0;

    public float $edit_overtime_rate = 0;

    public float $edit_allowances = 0;

    public float $edit_tax_deductions = 0;

    public float $edit_other_deductions = 0;

    public ?string $edit_bank_account_id = null;

    public ?string $edit_payment_date = null;

    protected function getModelClass(): string
    {
        return Payroll::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    protected function getFilteredQuery()
    {
        return Payroll::query();
    }

    public function mount(Payroll $payroll): void
    {
        $this->payroll = $payroll->load(['employee', 'bankAccount', 'payrollRun']);
        $this->loadEditFields();
    }

    private function loadEditFields(): void
    {
        $this->edit_base_salary      = (float) $this->payroll->base_salary;
        $this->edit_overtime_hours   = (float) $this->payroll->overtime_hours;
        $this->edit_overtime_rate    = (float) $this->payroll->overtime_rate;
        $this->edit_allowances       = (float) $this->payroll->allowances;
        $this->edit_tax_deductions   = (float) $this->payroll->tax_deductions;
        $this->edit_other_deductions = (float) $this->payroll->other_deductions;
        $this->edit_bank_account_id  = $this->payroll->bank_account_id ? (string) $this->payroll->bank_account_id : null;
        $this->edit_payment_date     = $this->payroll->payment_date?->format('Y-m-d');
    }

    public function startEditing(): void
    {
        if ($this->payroll->status !== 'draft') {
            $this->toast()->error('Only draft payslips can be edited.')->send();
            return;
        }

        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->loadEditFields();
        $this->editing = false;
    }

    public function saveEdits(): void
    {
        $this->validate([
            'edit_base_salary'      => 'required|numeric|min:0',
            'edit_overtime_hours'   => 'nullable|numeric|min:0',
            'edit_overtime_rate'    => 'nullable|numeric|min:0',
            'edit_allowances'       => 'nullable|numeric|min:0',
            'edit_tax_deductions'   => 'nullable|numeric|min:0',
            'edit_other_deductions' => 'nullable|numeric|min:0',
            'edit_bank_account_id'  => 'nullable|exists:bank_accounts,id',
            'edit_payment_date'     => 'nullable|date',
        ]);

        if ($this->payroll->status !== 'draft') {
            $this->toast()->error('Only draft payslips can be edited.')->send();
            return;
        }

        $this->payroll->update([
            'base_salary'      => $this->edit_base_salary,
            'overtime_hours'   => $this->edit_overtime_hours,
            'overtime_rate'    => $this->edit_overtime_rate,
            'allowances'       => $this->edit_allowances,
            'tax_deductions'   => $this->edit_tax_deductions,
            'other_deductions' => $this->edit_other_deductions,
            'bank_account_id'  => $this->edit_bank_account_id,
            'payment_date'     => $this->edit_payment_date,
        ]);

        $this->payroll->refresh()->load(['employee', 'bankAccount', 'payrollRun']);
        $this->editing = false;
        $this->toast()->success('Payslip updated.')->send();
    }

    public function approve(PayrollService $payrollService): void
    {
        try {
            $payrollService->approvePayslip($this->payroll);
            $this->payroll->refresh()->load(['employee', 'bankAccount', 'payrollRun']);
            $this->toast()->success('Payslip approved and GL accrual posted.')->send();
        } catch (\Exception $e) {
            $this->payroll->refresh()->load(['employee', 'bankAccount', 'payrollRun']);
            $this->toast()->error('Approval failed: ' . $e->getMessage())->send();
        }
    }

    public function markPaid(PayrollService $payrollService): void
    {
        try {
            $payrollService->markPaid($this->payroll);
            $this->payroll->refresh()->load(['employee', 'bankAccount', 'payrollRun']);
            $this->toast()->success('Payslip marked as paid and GL payment posted.')->send();
        } catch (\Exception $e) {
            $this->payroll->refresh()->load(['employee', 'bankAccount', 'payrollRun']);
            $this->toast()->error('Failed: ' . $e->getMessage())->send();
        }
    }

    public function cancel(PayrollService $payrollService): void
    {
        try {
            $payrollService->cancelPayslip($this->payroll);
            $this->payroll->refresh()->load(['employee', 'bankAccount', 'payrollRun']);
            $this->toast()->success('Payslip cancelled.')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Failed: ' . $e->getMessage())->send();
        }
    }

    public function getEditPreviewProperty(): array
    {
        $overtime = $this->edit_overtime_hours * $this->edit_overtime_rate;
        $gross    = $this->edit_base_salary + $this->edit_allowances + $overtime;
        $net      = $gross - $this->edit_tax_deductions - $this->edit_other_deductions;

        return compact('overtime', 'gross', 'net');
    }

    public function render()
    {
        return view('livewire.branch-dashboard.payroll.payslip-view', [
            'bankAccounts' => BankAccount::orderBy('bank_name')->get(['id', 'bank_name', 'account_number']),
        ]);
    }
}
