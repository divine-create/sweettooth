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
                <h1 class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">New Payroll Run</h1>
            </div>
        </div>
    </div>

    {{-- Run details form --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500">Run Details</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Run Name</label>
                <input wire:model.live="name" type="text"
                       class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Period Start</label>
                <input wire:model.live="pay_period_start" type="date"
                       class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                @error('pay_period_start') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Period End</label>
                <input wire:model.live="pay_period_end" type="date"
                       class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                @error('pay_period_end') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Payment Date</label>
                <input wire:model="payment_date" type="date"
                       class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
            </div>
            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Notes (optional)</label>
                <input wire:model="notes" type="text"
                       class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
            </div>
        </div>
    </div>

    {{-- Preview totals --}}
    @php $totals = $this->previewTotals; @endphp
    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Selected Employees</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $totals['count'] }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Total Gross</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($totals['gross']) }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Total Net</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ \App\Helpers\LocalizationHelper::formatCurrency($totals['net']) }}</p>
        </div>
    </div>

    {{-- Employee rows --}}
    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-100 dark:border-zinc-800">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">Employees</h2>
            <div class="flex gap-2">
                <button wire:click="toggleAll(true)" type="button"
                        class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400">
                    Select All
                </button>
                <button wire:click="toggleAll(false)" type="button"
                        class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400">
                    Deselect All
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left w-10"></th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Employee</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Base Salary</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">OT Hours</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">OT Rate</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Allowances</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Tax Ded.</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Other Ded.</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Net</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($employeeRows as $index => $row)
                    <tr class="{{ !($row['selected'] ?? false) ? 'opacity-40' : '' }} hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-opacity">
                        <td class="px-4 py-2">
                            <input type="checkbox"
                                   wire:model.live="employeeRows.{{ $index }}.selected"
                                   class="rounded border-zinc-300 dark:border-zinc-600" />
                        </td>
                        <td class="px-4 py-2">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $row['name'] }}</div>
                            <div class="text-xs text-zinc-400">{{ $row['employee_number'] ?? '' }}</div>
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" step="0.01" min="0"
                                   wire:model.live="employeeRows.{{ $index }}.base_salary"
                                   class="w-28 rounded border border-zinc-200 bg-white px-2 py-1 text-right text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" step="0.01" min="0"
                                   wire:model.live="employeeRows.{{ $index }}.overtime_hours"
                                   class="w-20 rounded border border-zinc-200 bg-white px-2 py-1 text-right text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" step="0.01" min="0"
                                   wire:model.live="employeeRows.{{ $index }}.overtime_rate"
                                   class="w-24 rounded border border-zinc-200 bg-white px-2 py-1 text-right text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" step="0.01" min="0"
                                   wire:model.live="employeeRows.{{ $index }}.allowances"
                                   class="w-24 rounded border border-zinc-200 bg-white px-2 py-1 text-right text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" step="0.01" min="0"
                                   wire:model.live="employeeRows.{{ $index }}.tax_deductions"
                                   class="w-24 rounded border border-zinc-200 bg-white px-2 py-1 text-right text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" step="0.01" min="0"
                                   wire:model.live="employeeRows.{{ $index }}.other_deductions"
                                   class="w-24 rounded border border-zinc-200 bg-white px-2 py-1 text-right text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                        </td>
                        @php
                            $ot = (float)($row['overtime_hours'] ?? 0) * (float)($row['overtime_rate'] ?? 0);
                            $g  = (float)($row['base_salary'] ?? 0) + (float)($row['allowances'] ?? 0) + $ot;
                            $n  = $g - (float)($row['tax_deductions'] ?? 0) - (float)($row['other_deductions'] ?? 0);
                        @endphp
                        <td class="px-4 py-2 text-right font-semibold text-emerald-600">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($n) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-sm text-zinc-400">No active employees found in this branch.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex justify-end gap-3 border-t border-zinc-100 px-6 py-4 dark:border-zinc-800">
            <a href="{{ branch_route('branch-dashboard.accounting.payroll.index') }}" wire:navigate
               class="rounded-full border border-zinc-300 px-6 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300">
                Cancel
            </a>
            <button wire:click="save" wire:loading.attr="disabled"
                    class="rounded-full bg-zinc-900 px-6 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-white dark:text-zinc-900">
                <span wire:loading.remove wire:target="save">Generate Payrun</span>
                <span wire:loading wire:target="save">Generating...</span>
            </button>
        </div>
    </div>
</div>
