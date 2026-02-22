<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
            <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Payroll</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Record payroll periods and payout amounts.</p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="grid grid-cols-1 gap-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Employee</label>
            <select wire:model.defer="employee_id" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="">Select employee</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
            </select>
            @error('employee_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Start</label>
            <input type="date" wire:model.defer="pay_period_start" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('pay_period_start') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">End</label>
            <input type="date" wire:model.defer="pay_period_end" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('pay_period_end') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Payment Date</label>
            <input type="date" wire:model.defer="payment_date" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('payment_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="lg:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Bank Account</label>
            <select wire:model.defer="bank_account_id" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="">Select account</option>
                @foreach($bankAccounts as $account)
                    <option value="{{ $account->id }}">{{ $account->bank_name }} - {{ $account->account_number }}</option>
                @endforeach
            </select>
            @error('bank_account_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Base Salary</label>
            <input type="number" step="0.01" wire:model.defer="base_salary" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('base_salary') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Overtime Hours</label>
            <input type="number" step="0.01" wire:model.defer="overtime_hours" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Overtime Rate</label>
            <input type="number" step="0.01" wire:model.defer="overtime_rate" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Allowances</label>
            <input type="number" step="0.01" wire:model.defer="allowances" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Tax Deductions</label>
            <input type="number" step="0.01" wire:model.defer="tax_deductions" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Other Deductions</label>
            <input type="number" step="0.01" wire:model.defer="other_deductions" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</label>
            <select wire:model.defer="status" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="draft">Draft</option>
                <option value="approved">Approved</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
            </select>
            @error('status') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="lg:col-span-6 flex justify-end">
            <button type="submit" class="rounded-full bg-zinc-900 px-6 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800">Save Payroll</button>
        </div>
    </form>

    <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <x-table
            :headers="['Employee', 'Period', 'Amount', 'Status']"
            :rows="$rows"
            striped
            paginate
            persist
            :quantity="[10, 25, 50]"
        >
            @interact('column_amount', $row)
                @php
                    $gross = (float) ($row->base_salary ?? 0) + (float) ($row->allowances ?? 0);
                    $deductions = (float) ($row->tax_deductions ?? 0) + (float) ($row->other_deductions ?? 0);
                @endphp
                <span class="font-semibold">{{ \App\Helpers\LocalizationHelper::formatCurrency($gross - $deductions) }}</span>
            @endinteract
            @interact('column_status', $row)
                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ ucfirst($row->status) }}</span>
            @endinteract
        </x-table>
    </div>
</div>
