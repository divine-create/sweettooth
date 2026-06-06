<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-gradient-to-br from-white via-white to-zinc-50 p-6 shadow-sm dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900/60">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Journal Entries</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Record manual entries with balanced debits and credits.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="exportToCsv" class="rounded-full border border-emerald-600 bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-emerald-700">Export CSV</button>
                <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</label>
                <select class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200" wire:model="status">
                    <option value="posted">Posted</option>
                    <option value="draft">Draft</option>
                    <option value="reversed">Reversed</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-900/30 dark:text-rose-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[2.1fr,1fr]">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Create Manual Journal</h2>
                    <p class="mt-1 text-xs text-zinc-500">Use at least two lines and balance debits/credits.</p>
                </div>
                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">Entry</span>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-xs uppercase tracking-wide text-zinc-500">Period</label>
                    <select class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" wire:model="periodId">
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}">{{ $period->name ?? ($period->month . '/' . $period->year) }}</option>
                        @endforeach
                    </select>
                    @error('periodId') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                    @if ($periods->isEmpty())
                        <div class="mt-1 text-xs text-amber-600">No open periods available.</div>
                    @endif
                </div>
                <div>
                    <label class="text-xs uppercase tracking-wide text-zinc-500">Entry Date</label>
                    <input class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="date" wire:model="entryDate" />
                    @error('entryDate') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs uppercase tracking-wide text-zinc-500">Description</label>
                    <input class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="text" wire:model="description" />
                    @error('description') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs uppercase tracking-wide text-zinc-500">Reference (optional)</label>
                    <input class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="text" wire:model="reference" placeholder="Auto-generated if left blank" />
                </div>
                <div class="md:col-span-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900">
                    <input type="checkbox" wire:model="postNow" />
                    <span>Post immediately after saving</span>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-3 py-2">Account</th>
                            <th class="px-3 py-2">Debit</th>
                            <th class="px-3 py-2">Credit</th>
                            <th class="px-3 py-2">Line Description</th>
                            <th class="px-3 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($lines as $index => $line)
                            <tr>
                                <td class="px-3 py-2">
                                    <select class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" wire:model="lines.{{ $index }}.account_id">
                                        <option value="">Select account</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}">
                                                {{ $account->account_number }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("lines.$index.account_id") <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                                </td>
                                <td class="px-3 py-2">
                                    <input class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="number" step="0.01" wire:model="lines.{{ $index }}.debit" />
                                    @error("lines.$index.debit") <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                                </td>
                                <td class="px-3 py-2">
                                    <input class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="number" step="0.01" wire:model="lines.{{ $index }}.credit" />
                                    @error("lines.$index.credit") <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                                </td>
                                <td class="px-3 py-2">
                                    <input class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="text" wire:model="lines.{{ $index }}.description" />
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button class="rounded-full border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-700 hover:border-rose-300 hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-200 dark:hover:bg-rose-900/30" type="button" wire:click="removeLine({{ $index }})">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @error('lines') <div class="mt-2 text-xs text-rose-500">{{ $message }}</div> @enderror
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button class="rounded-full border border-zinc-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:border-zinc-300" type="button" wire:click="addLine">Add Line</button>
                <button class="rounded-full bg-zinc-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800" type="button" wire:click="submit">Save Journal</button>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Quick Expense</h3>
                <p class="mt-2 text-xs text-zinc-500">Record a single expense. This creates a balanced 2-line journal entry.</p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="text-xs uppercase tracking-wide text-zinc-500">Amount</label>
                        <input class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="number" step="0.01" wire:model="quickExpenseAmount" />
                        @error('quickExpenseAmount') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-zinc-500">Expense Account</label>
                        <select class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" wire:model="quickExpenseAccountId">
                            <option value="">Select expense account</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('quickExpenseAccountId') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-zinc-500">Payment Account</label>
                        <select class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" wire:model="quickPaymentAccountId">
                            <option value="">Select cash/bank account</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('quickPaymentAccountId') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-zinc-500">Date</label>
                        <input class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="date" wire:model="quickExpenseDate" />
                        @error('quickExpenseDate') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-zinc-500">Description</label>
                        <input class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="text" wire:model="quickExpenseDescription" />
                        @error('quickExpenseDescription') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                    </div>
                    <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900">
                        <input type="checkbox" wire:model="quickExpensePostNow" />
                        <span>Post immediately</span>
                    </div>
                    <button class="w-full rounded-full bg-zinc-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800" type="button" wire:click="submitQuickExpense">
                        Save Expense
                    </button>
                </div>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Quick Rules</h3>
                <div class="mt-3 space-y-2 text-xs text-zinc-600 dark:text-zinc-300">
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-900/60">At least 2 lines required.</div>
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-900/60">Each line must have debit or credit.</div>
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-900/60">Total debits must equal total credits.</div>
                </div>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Posting</h3>
                <p class="mt-2 text-xs text-zinc-500">If “Post immediately” is checked, entries go straight to posted status; otherwise they stay in draft.</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <x-table
            :$headers
            :$rows
            striped
            paginate
            persist
        >
            @interact('column_entry_date', $row)
                <span class="text-zinc-700 dark:text-zinc-300">{{ optional($row->entry_date)->format('Y-m-d') }}</span>
            @endinteract

            @interact('column_account', $row)
                <div class="font-medium text-zinc-900 dark:text-white">{{ $row->glAccount?->account_name ?? 'Unknown' }}</div>
                <div class="text-xs text-zinc-500">{{ $row->glAccount?->account_number }}</div>
            @endinteract

            @interact('column_debit', $row)
                <span class="font-semibold text-zinc-900 dark:text-white">{{ number_format($row->debit ?? 0, 2) }}</span>
            @endinteract

            @interact('column_credit', $row)
                <span class="font-semibold text-zinc-900 dark:text-white">{{ number_format($row->credit ?? 0, 2) }}</span>
            @endinteract

            @interact('column_reference', $row)
                <span class="font-mono text-xs text-zinc-600 dark:text-zinc-300">{{ $row->reference_number ?? $row->reference ?? '-' }}</span>
            @endinteract

            @interact('column_status', $row)
                @if (($row->status ?? '') === 'posted')
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200">Posted</span>
                @elseif (($row->status ?? '') === 'draft')
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/20 dark:text-amber-200">Draft</span>
                @else
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ ucfirst($row->status ?? 'unknown') }}</span>
                @endif
            @endinteract
        </x-table>
    </div>
</div>
