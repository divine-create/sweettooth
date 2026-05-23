<div class="space-y-6">
    {{-- Header --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center gap-3">
            <a href="{{ branch_route('branch-dashboard.accounting.payroll.index') }}" wire:navigate
               class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Payroll</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">New Individual Payslip</h1>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Form --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500">Employee & Period</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Employee</label>
                        <select wire:model.live="employee_id"
                                class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                            <option value="">Select employee</option>
                            @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} {{ $emp->employee_number ? "({$emp->employee_number})" : '' }}</option>
                            @endforeach
                        </select>
                        @error('employee_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Period Start</label>
                        <input type="date" wire:model="pay_period_start"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                        @error('pay_period_start') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Period End</label>
                        <input type="date" wire:model="pay_period_end"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                        @error('pay_period_end') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Payment Date</label>
                        <input type="date" wire:model="payment_date"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Bank Account</label>
                        <select wire:model="bank_account_id"
                                class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                            <option value="">Select account (optional)</option>
                            @foreach ($bankAccounts as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->bank_name }} – {{ $acct->account_number }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500">Compensation</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Base Salary</label>
                        <input type="number" step="0.01" min="0" wire:model.live="base_salary"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                        @error('base_salary') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Allowances</label>
                        <input type="number" step="0.01" min="0" wire:model.live="allowances"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Overtime Hours</label>
                        <input type="number" step="0.01" min="0" wire:model.live="overtime_hours"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Overtime Rate (per hour)</label>
                        <input type="number" step="0.01" min="0" wire:model.live="overtime_rate"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Tax Deductions</label>
                        <input type="number" step="0.01" min="0" wire:model.live="tax_deductions"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Other Deductions</label>
                        <input type="number" step="0.01" min="0" wire:model.live="other_deductions"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Live Preview --}}
        @php $preview = $this->preview; @endphp
        <div class="space-y-4">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sticky top-4">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500">Preview</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <dt class="text-zinc-500">Base Salary</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($base_salary) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-zinc-500">Allowances</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($allowances) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-zinc-500">Overtime Pay</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($preview['overtime']) }}</dd>
                    </div>
                    <div class="border-t border-zinc-100 pt-3 flex justify-between text-sm dark:border-zinc-800">
                        <dt class="font-semibold text-zinc-700 dark:text-zinc-300">Gross Salary</dt>
                        <dd class="font-bold text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($preview['gross']) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm text-rose-600">
                        <dt>Tax Deductions</dt>
                        <dd>- {{ \App\Helpers\LocalizationHelper::formatCurrency($tax_deductions) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm text-rose-600">
                        <dt>Other Deductions</dt>
                        <dd>- {{ \App\Helpers\LocalizationHelper::formatCurrency($other_deductions) }}</dd>
                    </div>
                    <div class="border-t border-zinc-200 pt-3 flex justify-between dark:border-zinc-700">
                        <dt class="text-base font-bold text-zinc-900 dark:text-white">Net Pay</dt>
                        <dd class="text-xl font-bold text-emerald-600">{{ \App\Helpers\LocalizationHelper::formatCurrency($preview['net']) }}</dd>
                    </div>
                </dl>
                <div class="mt-6 flex flex-col gap-3">
                    <button wire:click="save" wire:loading.attr="disabled"
                            class="w-full rounded-full bg-zinc-900 px-6 py-2.5 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-white dark:text-zinc-900">
                        <span wire:loading.remove wire:target="save">Save Payslip</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                    <a href="{{ branch_route('branch-dashboard.accounting.payroll.index') }}" wire:navigate
                       class="w-full rounded-full border border-zinc-300 px-6 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
