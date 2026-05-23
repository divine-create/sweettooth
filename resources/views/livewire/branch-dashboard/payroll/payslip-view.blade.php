<div class="space-y-6">
    {{-- Header --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3">
                @if ($payroll->payroll_run_id)
                <a href="{{ branch_route('branch-dashboard.accounting.payroll.run.view', ['run' => $payroll->payroll_run_id]) }}" wire:navigate
                   class="mt-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                @else
                <a href="{{ branch_route('branch-dashboard.accounting.payroll.index') }}" wire:navigate
                   class="mt-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                @endif
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Payslip #{{ $payroll->id }}</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $payroll->employee?->name ?? '—' }}</h1>
                    @if ($payroll->payrollRun)
                    <p class="mt-1 text-sm text-zinc-500">Part of <a href="{{ branch_route('branch-dashboard.accounting.payroll.run.view', ['run' => $payroll->payroll_run_id]) }}" wire:navigate class="text-blue-600 hover:underline">{{ $payroll->payrollRun->name }}</a></p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                @include('livewire.branch-dashboard.payroll.partials.status-badge', ['status' => $payroll->status])

                @if ($payroll->status === 'draft' && !$editing)
                    <button wire:click="startEditing"
                            class="rounded-full border border-zinc-300 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400">
                        Edit
                    </button>
                    <button wire:click="approve" wire:confirm="Approve this payslip and post GL accrual entry?"
                            class="rounded-full bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-blue-700">
                        Approve
                    </button>
                    <button wire:click="cancel" wire:confirm="Cancel this payslip?"
                            class="rounded-full border border-rose-300 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-rose-600 hover:bg-rose-50">
                        Cancel
                    </button>
                @elseif ($payroll->status === 'approved')
                    <button wire:click="markPaid" wire:confirm="Mark as paid and post GL payment entry?"
                            class="rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-emerald-700">
                        Mark Paid
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Payslip details / edit form --}}
        <div class="lg:col-span-2 space-y-5">
            @if ($editing)
            {{-- Edit form --}}
            <div class="rounded-2xl border border-blue-200 bg-white p-6 shadow-sm dark:border-blue-700 dark:bg-zinc-900">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500">Edit Payslip</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Base Salary</label>
                        <input type="number" step="0.01" min="0" wire:model.live="edit_base_salary"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Allowances</label>
                        <input type="number" step="0.01" min="0" wire:model.live="edit_allowances"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Overtime Hours</label>
                        <input type="number" step="0.01" min="0" wire:model.live="edit_overtime_hours"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Overtime Rate</label>
                        <input type="number" step="0.01" min="0" wire:model.live="edit_overtime_rate"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Tax Deductions</label>
                        <input type="number" step="0.01" min="0" wire:model.live="edit_tax_deductions"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Other Deductions</label>
                        <input type="number" step="0.01" min="0" wire:model.live="edit_other_deductions"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Payment Date</label>
                        <input type="date" wire:model="edit_payment_date"
                               class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Bank Account</label>
                        <select wire:model="edit_bank_account_id"
                                class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                            <option value="">Select account</option>
                            @foreach ($bankAccounts as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->bank_name }} – {{ $acct->account_number }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex gap-3">
                    <button wire:click="saveEdits"
                            class="rounded-full bg-zinc-900 px-5 py-2 text-xs font-semibold text-white hover:bg-zinc-700">Save Changes</button>
                    <button wire:click="cancelEditing"
                            class="rounded-full border border-zinc-300 px-5 py-2 text-xs font-semibold text-zinc-600 hover:bg-zinc-50">Discard</button>
                </div>
            </div>
            @else
            {{-- View mode --}}
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500">Employee Details</h2>
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Employee Number</dt>
                        <dd class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{{ $payroll->employee?->employee_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Pay Period</dt>
                        <dd class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">
                            {{ $payroll->pay_period_start->format('d M Y') }} – {{ $payroll->pay_period_end->format('d M Y') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Payment Date</dt>
                        <dd class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{{ $payroll->payment_date?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Bank Account</dt>
                        <dd class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">
                            {{ $payroll->bankAccount ? "{$payroll->bankAccount->bank_name} – {$payroll->bankAccount->account_number}" : '—' }}
                        </dd>
                    </div>
                </dl>
            </div>
            @endif

            {{-- GL Posting Status --}}
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500">GL Posting Status</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Accrual Posting</p>
                        <div class="mt-2 flex items-center gap-2">
                            @if ($payroll->gl_posting_status === 'posted')
                                <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                <span class="text-sm text-emerald-600 font-medium">Posted {{ $payroll->gl_posted_at?->format('d M Y H:i') }}</span>
                            @elseif ($payroll->gl_posting_status === 'failed')
                                <span class="inline-block h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                                <span class="text-sm text-rose-600 font-medium">Failed</span>
                            @else
                                <span class="inline-block h-2.5 w-2.5 rounded-full bg-zinc-300"></span>
                                <span class="text-sm text-zinc-500">Pending</span>
                            @endif
                        </div>
                        @if ($payroll->gl_posting_error)
                        <p class="mt-1 text-xs text-rose-600">{{ $payroll->gl_posting_error }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Payment Posting</p>
                        <div class="mt-2 flex items-center gap-2">
                            @if ($payroll->gl_payment_posting_status === 'posted')
                                <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                <span class="text-sm text-emerald-600 font-medium">Posted {{ $payroll->gl_payment_posted_at?->format('d M Y H:i') }}</span>
                            @elseif ($payroll->gl_payment_posting_status === 'failed')
                                <span class="inline-block h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                                <span class="text-sm text-rose-600 font-medium">Failed</span>
                            @else
                                <span class="inline-block h-2.5 w-2.5 rounded-full bg-zinc-300"></span>
                                <span class="text-sm text-zinc-500">Pending</span>
                            @endif
                        </div>
                        @if ($payroll->gl_payment_posting_error)
                        <p class="mt-1 text-xs text-rose-600">{{ $payroll->gl_payment_posting_error }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Payslip summary panel --}}
        @php $preview = $editing ? $this->editPreview : ['overtime' => (float)$payroll->overtime_hours * (float)$payroll->overtime_rate, 'gross' => (float)$payroll->gross_salary, 'net' => (float)$payroll->net_salary]; @endphp
        <div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500">Pay Summary</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <dt class="text-zinc-500">Base Salary</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($editing ? $edit_base_salary : $payroll->base_salary) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-zinc-500">Allowances</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($editing ? $edit_allowances : $payroll->allowances) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-zinc-500">Overtime</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($preview['overtime']) }}</dd>
                    </div>
                    <div class="border-t border-zinc-100 pt-3 flex justify-between text-sm dark:border-zinc-800">
                        <dt class="font-semibold text-zinc-700 dark:text-zinc-300">Gross</dt>
                        <dd class="font-bold text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($preview['gross']) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm text-rose-600">
                        <dt>Tax Deductions</dt>
                        <dd>- {{ \App\Helpers\LocalizationHelper::formatCurrency($editing ? $edit_tax_deductions : $payroll->tax_deductions) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm text-rose-600">
                        <dt>Other Deductions</dt>
                        <dd>- {{ \App\Helpers\LocalizationHelper::formatCurrency($editing ? $edit_other_deductions : $payroll->other_deductions) }}</dd>
                    </div>
                    <div class="border-t border-zinc-200 pt-3 flex justify-between dark:border-zinc-700">
                        <dt class="text-base font-bold text-zinc-900 dark:text-white">Net Pay</dt>
                        <dd class="text-2xl font-bold text-emerald-600">{{ \App\Helpers\LocalizationHelper::formatCurrency($preview['net']) }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
