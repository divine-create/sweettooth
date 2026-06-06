<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Livewire\Concerns\ExportsCsv;
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
    use ExportsCsv;

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

    public function exportToCsv()
    {
        $rows = Payroll::with(['employee', 'bankAccount'])
            ->orderByDesc('id')
            ->get()
            ->map(fn ($p) => [
                $p->employee?->name ?? '',
                $p->pay_period_start ? \Illuminate\Support\Carbon::parse($p->pay_period_start)->format('Y-m-d') : '',
                $p->pay_period_end ? \Illuminate\Support\Carbon::parse($p->pay_period_end)->format('Y-m-d') : '',
                $p->payment_date ? \Illuminate\Support\Carbon::parse($p->payment_date)->format('Y-m-d') : '',
                number_format((float) $p->base_salary, 2, '.', ''),
                number_format((float) $p->allowances, 2, '.', ''),
                number_format((float) $p->tax_deductions, 2, '.', ''),
                number_format((float) $p->other_deductions, 2, '.', ''),
                number_format((float) $p->gross_salary, 2, '.', ''),
                number_format((float) $p->net_salary, 2, '.', ''),
                $p->bankAccount?->bank_name ?? '',
                ucfirst((string) $p->status),
            ]);

        return $this->streamCsv(
            $this->csvFilename('payrolls'),
            ['Employee', 'Period Start', 'Period End', 'Payment Date', 'Base Salary', 'Allowances', 'Tax Deductions', 'Other Deductions', 'Gross Salary', 'Net Salary', 'Bank', 'Status'],
            $rows
        );
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
