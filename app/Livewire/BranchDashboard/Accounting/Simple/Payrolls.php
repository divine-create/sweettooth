<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\Payroll;
use App\Models\User;
use App\Models\BankAccount;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class Payrolls extends Component
{
    use WithPagination;

    public string $pay_period_start = '';
    public string $pay_period_end = '';
    public ?string $payment_date = null;
    public ?string $employee_id = null;
    public ?string $bank_account_id = null;
    public float $base_salary = 0;
    public float $overtime_hours = 0;
    public float $overtime_rate = 0;
    public float $allowances = 0;
    public float $tax_deductions = 0;
    public float $other_deductions = 0;
    public string $status = 'approved';

    public int $perPage = 20;

    public function mount(): void
    {
        $this->pay_period_start = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->pay_period_end = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function save(): void
    {
        $this->validate([
            'employee_id' => 'required|exists:users,id',
            'pay_period_start' => 'required|date',
            'pay_period_end' => 'required|date',
            'payment_date' => 'nullable|date',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'base_salary' => 'required|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'tax_deductions' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,approved,paid,cancelled',
        ]);

        $branchId = current_branch_id();

        Payroll::create([
            'branch_id' => $branchId,
            'employee_id' => $this->employee_id,
            'pay_period_start' => $this->pay_period_start,
            'pay_period_end' => $this->pay_period_end,
            'payment_date' => $this->payment_date,
            'bank_account_id' => $this->bank_account_id,
            'base_salary' => $this->base_salary,
            'overtime_hours' => $this->overtime_hours,
            'overtime_rate' => $this->overtime_rate,
            'allowances' => $this->allowances,
            'tax_deductions' => $this->tax_deductions,
            'other_deductions' => $this->other_deductions,
            'status' => $this->status,
            'created_by_id' => auth()->id(),
        ]);

        $this->reset([
            'employee_id',
            'payment_date',
            'bank_account_id',
            'base_salary',
            'overtime_hours',
            'overtime_rate',
            'allowances',
            'tax_deductions',
            'other_deductions',
            'status',
        ]);

        $this->status = 'approved';
        $this->dispatch('notify', message: 'Payroll created.');
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.simple.payrolls', [
            'employees' => User::orderBy('name')->get(['id', 'name']),
            'bankAccounts' => BankAccount::orderBy('bank_name')->get(['id', 'bank_name', 'account_number']),
            'rows' => Payroll::orderByDesc('id')->paginate($this->perPage),
        ]);
    }
}
