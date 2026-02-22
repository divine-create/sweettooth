<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-gradient-to-br from-white via-white to-zinc-50 p-6 shadow-sm dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900/60">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">POS Remittances</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Track POS collections versus bank remittances.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <input type="date" wire:model.live="dateFrom" class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900" />
                <input type="date" wire:model.live="dateTo" class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900" />
                <select wire:model.live="departmentId" class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <option value="">All Departments</option>
                    @foreach($this->departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-full border border-emerald-600 bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-emerald-700" type="button" wire:click="toggleForm">
                    {{ $showForm ? 'Close Form' : 'Record Remittance' }}
                </button>
                <button class="rounded-full border border-zinc-900 bg-zinc-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800 dark:border-white dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200" type="button" wire:click="exportCSV">
                    Export CSV
                </button>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-wide text-zinc-500">POS Collected</p>
                <p class="mt-2 text-xl font-semibold text-zinc-900 dark:text-white">{{ number_format($this->posCollectedTotal(), 2) }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-wide text-zinc-500">Remitted</p>
                <p class="mt-2 text-xl font-semibold text-zinc-900 dark:text-white">{{ number_format($this->posRemittedTotal(), 2) }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm dark:border-emerald-900 dark:bg-emerald-900/20">
                <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Outstanding</p>
                <p class="mt-2 text-xl font-semibold text-emerald-700 dark:text-emerald-200">{{ number_format($this->posOutstandingTotal(), 2) }}</p>
            </div>
        </div>
    </div>

    @if($showForm)
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Record POS Remittance</h2>
                    <p class="text-xs text-zinc-500">Posts bank receipt and clears POS outstanding.</p>
                </div>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div>
                    <label class="text-xs text-zinc-500">Remitted Amount</label>
                    <input type="number" step="0.01" wire:model="remittanceAmount" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                    @error('remittanceAmount') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Remitted At</label>
                    <input type="datetime-local" wire:model="remittedAt" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                    @error('remittedAt') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Department *</label>
                    <select wire:model="departmentId" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <option value="">Select department</option>
                        @foreach($this->departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('departmentId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Bank Account (Auto)</label>
                    <div class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300">
                        @php
                            $selectedDept = $this->departments->firstWhere('id', $departmentId);
                            $selectedBank = $selectedDept?->bank_account_id ? $this->bankAccounts->firstWhere('id', $selectedDept->bank_account_id) : null;
                        @endphp
                        {{ $selectedBank ? ($selectedBank->bank_name . ' · ' . $selectedBank->account_number) : 'No bank linked' }}
                    </div>
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Reference</label>
                    <input type="text" wire:model="remittanceReference" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs text-zinc-500">Notes</label>
                    <textarea wire:model="remittanceNotes" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" rows="2"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="button" wire:click="saveRemittance" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-emerald-700">
                        Save Remittance
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center justify-between p-4">
            <div class="flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <span class="text-zinc-400">Search</span>
                <input class="w-56 border-none bg-transparent p-0 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none dark:text-white" type="text" placeholder="Reference or note" wire:model.debounce.300ms="search" />
            </div>
        </div>
        <x-table
            :$headers
            :$rows
            striped
            paginate
            persist
            :filter="['quantity' => 'quantity', 'search' => 'search']"
            :quantity="[10, 25, 50, 100]"
        >
            @interact('column_remitted_at', $row)
                <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ optional($row->remitted_at)->format('Y-m-d H:i') }}</div>
            @endinteract

            @interact('column_department', $row)
                <div class="text-zinc-900 dark:text-white">{{ $row->department?->name ?? '-' }}</div>
            @endinteract

            @interact('column_bank', $row)
                <div class="text-zinc-900 dark:text-white">{{ $row->bankAccount?->bank_name ?? 'Bank' }}</div>
                <div class="text-xs text-zinc-500">{{ $row->bankAccount?->account_number ?? '-' }}</div>
            @endinteract

            @interact('column_amount', $row)
                <span class="font-semibold text-zinc-900 dark:text-white">{{ number_format($row->amount, 2) }}</span>
            @endinteract

            @interact('column_reference', $row)
                <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ $row->reference ?? '-' }}</span>
            @endinteract
        </x-table>
    </div>
</div>
