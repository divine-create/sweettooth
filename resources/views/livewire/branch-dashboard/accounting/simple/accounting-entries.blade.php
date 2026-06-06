<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-gradient-to-br from-white via-white to-zinc-50 p-6 shadow-sm dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900/60">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Entries</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Record any two-line entry: expenses, income, transfers, or adjustments.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <input type="date" wire:model.live="dateFrom" class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900" />
                <input type="date" wire:model.live="dateTo" class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900" />
                <select wire:model.live="entryTypeFilter" class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <option value="">All Types</option>
                    @foreach($entryTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select wire:model.live="debitFilter" class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <option value="">All Debit Accounts</option>
                    @foreach($accounts as $acct)
                        <option value="{{ $acct->id }}">{{ $acct->account_number }} · {{ $acct->account_name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="creditFilter" class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <option value="">All Credit Accounts</option>
                    @foreach($accounts as $acct)
                        <option value="{{ $acct->id }}">{{ $acct->account_number }} · {{ $acct->account_name }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="exportToCsv" class="rounded-full border border-emerald-600 bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-emerald-700">Export CSV</button>
                <button class="rounded-full border border-emerald-600 bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-emerald-700" type="button" wire:click="toggleForm">
                    {{ $showForm ? 'Close Form' : 'New Entry' }}
                </button>
            </div>
        </div>
    </div>

    @if($showForm)
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="text-xs text-zinc-500">Date *</label>
                    <input type="date" wire:model="entryDate" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                    @error('entryDate') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Type *</label>
                    <select wire:model="entryType" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                        @foreach($entryTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('entryType') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    @if($entryTypeHelper)
                        <p class="mt-2 text-xs text-zinc-500">{{ $entryTypeHelper }}</p>
                    @endif
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Amount *</label>
                    <input type="number" step="0.01" wire:model="amount" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                    @error('amount') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Description *</label>
                    <input type="text" wire:model="description" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                    @error('description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Debit Account *</label>
                    <select wire:model="debitGlAccountId" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <option value="">Select Account</option>
                        @foreach($accounts as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->account_number }} · {{ $acct->account_name }}</option>
                        @endforeach
                    </select>
                    @error('debitGlAccountId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Credit Account *</label>
                    <select wire:model="creditGlAccountId" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <option value="">Select Account</option>
                        @foreach($accounts as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->account_number }} · {{ $acct->account_name }}</option>
                        @endforeach
                    </select>
                    @error('creditGlAccountId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Source</label>
                    <input type="text" wire:model="source" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Reference</label>
                    <input type="text" wire:model="reference" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs text-zinc-500">Notes</label>
                    <textarea wire:model="notes" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" rows="2"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="button" wire:click="save" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-emerald-700">Save Entry</button>
                </div>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center justify-between p-4">
            <div class="flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <span class="text-zinc-400">Search</span>
                <input class="w-56 border-none bg-transparent p-0 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none dark:text-white" type="text" placeholder="Description, source" wire:model.debounce.300ms="search" />
            </div>
        </div>
        <x-table :$headers :$rows striped paginate persist :filter="['quantity' => 'quantity', 'search' => 'search']" :quantity="[10,25,50,100]">
            @interact('column_entry_date', $row)
                <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ optional($row->entry_date)->format('Y-m-d') }}</div>
            @endinteract

            @interact('column_entry_type', $row)
                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    {{ strtoupper($row->entry_type) }}
                </span>
            @endinteract

            @interact('column_description', $row)
                <div class="text-zinc-900 dark:text-white">{{ $row->description }}</div>
            @endinteract

            @interact('column_amount', $row)
                <span class="font-semibold text-zinc-900 dark:text-white">{{ number_format($row->amount, 2) }}</span>
            @endinteract

            @interact('column_debit', $row)
                <div class="text-zinc-900 dark:text-white">{{ $row->debitGlAccount?->account_name ?? '-' }}</div>
            @endinteract

            @interact('column_credit', $row)
                <div class="text-zinc-900 dark:text-white">{{ $row->creditGlAccount?->account_name ?? '-' }}</div>
            @endinteract
        </x-table>
        @if($rows->total() === 0)
            <div class="border-t border-zinc-100 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-800">
                No entries found. Create one above, or widen your date filters.
            </div>
        @endif
    </div>
</div>
