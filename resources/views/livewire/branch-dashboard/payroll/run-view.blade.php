<div class="space-y-6">
    {{-- Header --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3">
                <a href="{{ branch_route('branch-dashboard.accounting.payroll.index') }}" wire:navigate
                   class="mt-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Payroll Run</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $run->name }}</h1>
                    <p class="mt-1 text-sm text-zinc-500">
                        {{ $run->pay_period_start->format('d M Y') }} – {{ $run->pay_period_end->format('d M Y') }}
                        @if ($run->payment_date)
                        · Payment: {{ $run->payment_date->format('d M Y') }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @include('livewire.branch-dashboard.payroll.partials.status-badge', ['status' => $run->status])

                @if ($run->status !== 'cancelled' && $run->status !== 'paid')
                    @if ($payrolls->where('status', 'draft')->count() > 0)
                    <button wire:click="approveAll" wire:confirm="Approve all draft payslips and post GL accrual entries?"
                            class="rounded-full bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-blue-700">
                        Approve All
                    </button>
                    @endif

                    @if ($run->status === 'approved' || $payrolls->where('status', 'approved')->count() > 0)
                    <button wire:click="markAllPaid" wire:confirm="Mark all approved payslips as paid and post GL payment entries?"
                            class="rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-emerald-700">
                        Mark All Paid
                    </button>
                    @endif

                    <button wire:click="$set('showCancelModal', true)"
                            class="rounded-full border border-rose-300 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-rose-600 hover:bg-rose-50">
                        Cancel Run
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Summary stats --}}
    <div class="grid grid-cols-4 gap-4">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Employees</p>
            <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">{{ $payrolls->count() }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Total Gross</p>
            <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($payrolls->sum('gross_salary')) }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Total Net</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600">{{ \App\Helpers\LocalizationHelper::formatCurrency($payrolls->sum('net_salary')) }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Total Deductions</p>
            <p class="mt-2 text-2xl font-bold text-rose-600">{{ \App\Helpers\LocalizationHelper::formatCurrency($payrolls->sum('tax_deductions') + $payrolls->sum('other_deductions')) }}</p>
        </div>
    </div>

    {{-- Payslips table --}}
    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-100 dark:border-zinc-800">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">Payslips</h2>
            @if ($run->status === 'draft')
            <button wire:click="openAddModal"
                    class="rounded-full border border-zinc-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400">
                + Add Employee
            </button>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Employee</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Base Salary</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">OT Pay</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Allowances</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Gross</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Deductions</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Net</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">GL</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($payrolls as $payslip)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $payslip->employee?->name ?? '—' }}
                            <div class="text-xs text-zinc-400">{{ $payslip->employee?->employee_number ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ \App\Helpers\LocalizationHelper::formatCurrency($payslip->base_salary) }}</td>
                        <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency((float)$payslip->overtime_hours * (float)$payslip->overtime_rate) }}
                        </td>
                        <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ \App\Helpers\LocalizationHelper::formatCurrency($payslip->allowances) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($payslip->gross_salary) }}</td>
                        <td class="px-4 py-3 text-right text-rose-600">{{ \App\Helpers\LocalizationHelper::formatCurrency((float)$payslip->tax_deductions + (float)$payslip->other_deductions) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-600">{{ \App\Helpers\LocalizationHelper::formatCurrency($payslip->net_salary) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($payslip->gl_posting_status === 'posted')
                                <span class="inline-block h-2 w-2 rounded-full bg-emerald-500" title="GL posted"></span>
                            @elseif ($payslip->gl_posting_status === 'failed')
                                <span class="inline-block h-2 w-2 rounded-full bg-rose-500" title="GL failed"></span>
                            @else
                                <span class="inline-block h-2 w-2 rounded-full bg-zinc-300" title="Pending"></span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @include('livewire.branch-dashboard.payroll.partials.status-badge', ['status' => $payslip->status])
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if ($payslip->status === 'draft')
                                    <button wire:click="openEditModal({{ $payslip->id }})"
                                            class="text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">Edit</button>
                                    <button wire:click="approvePayslip({{ $payslip->id }})"
                                            wire:confirm="Approve this payslip and post GL accrual?"
                                            class="text-xs text-blue-600 hover:underline">Approve</button>
                                @elseif ($payslip->status === 'approved')
                                    <button wire:click="markPaid({{ $payslip->id }})"
                                            wire:confirm="Mark this payslip as paid and post GL payment?"
                                            class="text-xs text-emerald-600 hover:underline">Mark Paid</button>
                                @endif
                                <a href="{{ branch_route('branch-dashboard.accounting.payroll.payslip.view', ['payroll' => $payslip->id]) }}"
                                   wire:navigate class="text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">View</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-10 text-center text-sm text-zinc-400">No payslips in this run.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add Employee Modal --}}
    @if ($showAddModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-900">
            <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Add Employee to Run</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Employee</label>
                    <select wire:model.live="add_employee_id"
                            class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                        <option value="">Select employee</option>
                        @foreach ($availableEmployees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }} {{ $emp->employee_number ? "({$emp->employee_number})" : '' }}</option>
                        @endforeach
                    </select>
                    @error('add_employee_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Base Salary</label>
                        <input type="number" step="0.01" wire:model="add_base_salary"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Allowances</label>
                        <input type="number" step="0.01" wire:model="add_allowances"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">OT Hours</label>
                        <input type="number" step="0.01" wire:model="add_overtime_hours"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">OT Rate</label>
                        <input type="number" step="0.01" wire:model="add_overtime_rate"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Tax Deductions</label>
                        <input type="number" step="0.01" wire:model="add_tax_deductions"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Other Deductions</label>
                        <input type="number" step="0.01" wire:model="add_other_deductions"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-3">
                <button wire:click="closeAddModal" class="rounded-full border border-zinc-300 px-5 py-2 text-xs font-semibold text-zinc-600 hover:bg-zinc-50">Cancel</button>
                <button wire:click="addEmployee" class="rounded-full bg-zinc-900 px-5 py-2 text-xs font-semibold text-white hover:bg-zinc-700">Add</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Edit Payslip Modal --}}
    @if ($showEditModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-900">
            <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Edit Payslip</h3>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Base Salary</label>
                    <input type="number" step="0.01" wire:model="edit_base_salary"
                           class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Allowances</label>
                    <input type="number" step="0.01" wire:model="edit_allowances"
                           class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">OT Hours</label>
                    <input type="number" step="0.01" wire:model="edit_overtime_hours"
                           class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">OT Rate</label>
                    <input type="number" step="0.01" wire:model="edit_overtime_rate"
                           class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Tax Deductions</label>
                    <input type="number" step="0.01" wire:model="edit_tax_deductions"
                           class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Other Deductions</label>
                    <input type="number" step="0.01" wire:model="edit_other_deductions"
                           class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-3">
                <button wire:click="closeEditModal" class="rounded-full border border-zinc-300 px-5 py-2 text-xs font-semibold text-zinc-600 hover:bg-zinc-50">Cancel</button>
                <button wire:click="updatePayslip" class="rounded-full bg-zinc-900 px-5 py-2 text-xs font-semibold text-white hover:bg-zinc-700">Save</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Cancel Run Confirmation Modal --}}
    @if ($showCancelModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-900">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Cancel Payroll Run?</h3>
            <p class="mt-2 text-sm text-zinc-500">All draft payslips in this run will be cancelled. This cannot be undone.</p>
            <div class="mt-5 flex justify-end gap-3">
                <button wire:click="$set('showCancelModal', false)" class="rounded-full border border-zinc-300 px-5 py-2 text-xs font-semibold text-zinc-600 hover:bg-zinc-50">Keep</button>
                <button wire:click="cancelRun" class="rounded-full bg-rose-600 px-5 py-2 text-xs font-semibold text-white hover:bg-rose-700">Yes, Cancel Run</button>
            </div>
        </div>
    </div>
    @endif
</div>
