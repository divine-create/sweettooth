<?php

namespace App\Livewire\BranchDashboard\Payroll;

use App\Livewire\BaseComponent;
use App\Models\BankAccount;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class PayslipCreate extends BaseComponent
{
    use Interactions;

    public ?string $employee_id = null;

    public string $pay_period_start = '';

    public string $pay_period_end = '';

    public ?string $payment_date = null;

    public ?string $bank_account_id = null;

    public float $base_salary = 0;

    public float $overtime_hours = 0;

    public float $overtime_rate = 0;

    public float $allowances = 0;

    public float $tax_deductions = 0;

    public float $other_deductions = 0;

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

    public function mount(): void
    {
        $this->pay_period_start = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->pay_period_end   = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->payment_date     = Carbon::now()->endOfMonth()->addDays(5)->format('Y-m-d');
    }

    public function updatedEmployeeId(): void
    {
        $employee = User::find($this->employee_id);

        if ($employee) {
            $this->base_salary    = (float) ($employee->salary ?? 0);
            $this->overtime_rate  = (float) ($employee->hourly_rate ?? 0);
        }
    }

    public function getPreviewProperty(): array
    {
        $overtime = $this->overtime_hours * $this->overtime_rate;
        $gross    = $this->base_salary + $this->allowances + $overtime;
        $net      = $gross - $this->tax_deductions - $this->other_deductions;

        return compact('overtime', 'gross', 'net');
    }

    public function save(): void
    {
        $this->validate([
            'employee_id'      => 'required|exists:users,id',
            'pay_period_start' => 'required|date',
            'pay_period_end'   => 'required|date|after_or_equal:pay_period_start',
            'payment_date'     => 'nullable|date',
            'bank_account_id'  => 'nullable|exists:bank_accounts,id',
            'base_salary'      => 'required|numeric|min:0',
            'overtime_hours'   => 'nullable|numeric|min:0',
            'overtime_rate'    => 'nullable|numeric|min:0',
            'allowances'       => 'nullable|numeric|min:0',
            'tax_deductions'   => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
        ]);

        $payroll = Payroll::create([
            'branch_id'        => current_branch_id(),
            'employee_id'      => $this->employee_id,
            'pay_period_start' => $this->pay_period_start,
            'pay_period_end'   => $this->pay_period_end,
            'payment_date'     => $this->payment_date,
            'bank_account_id'  => $this->bank_account_id,
            'base_salary'      => $this->base_salary,
            'overtime_hours'   => $this->overtime_hours,
            'overtime_rate'    => $this->overtime_rate,
            'allowances'       => $this->allowances,
            'tax_deductions'   => $this->tax_deductions,
            'other_deductions' => $this->other_deductions,
            'status'           => 'draft',
            'created_by_id'    => auth()->id(),
        ]);

        $this->toast()->success('Payslip created.')->send();
        $this->redirect(branch_route('branch-dashboard.accounting.payroll.payslip.view', ['payroll' => $payroll->id]));
    }

    public function render()
    {
        return view('livewire.branch-dashboard.payroll.payslip-create', [
            'employees'    => User::where('branch_id', current_branch_id())->where('is_active', true)->orderBy('name')->get(['id', 'name', 'employee_number']),
            'bankAccounts' => BankAccount::orderBy('bank_name')->get(['id', 'bank_name', 'account_number']),
        ]);
    }
}
