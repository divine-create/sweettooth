<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-gradient-to-br from-white via-white to-zinc-50 p-6 shadow-sm dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900/60">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
            <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Quick Expense</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Record a single expense. This creates a balanced 2-line journal entry.</p>
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

    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
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
            <div>
                <label class="text-xs uppercase tracking-wide text-zinc-500">Amount</label>
                <input class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="number" step="0.01" wire:model="amount" />
                @error('amount') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="text-xs uppercase tracking-wide text-zinc-500">Expense Account</label>
                <select class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" wire:model="expenseAccountId">
                    <option value="">Select expense account</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">
                            {{ $account->account_number }} - {{ $account->account_name }}
                        </option>
                    @endforeach
                </select>
                @error('expenseAccountId') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="text-xs uppercase tracking-wide text-zinc-500">Payment Account</label>
                <select class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" wire:model="paymentAccountId">
                    <option value="">Select cash/bank account</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">
                            {{ $account->account_number }} - {{ $account->account_name }}
                        </option>
                    @endforeach
                </select>
                @error('paymentAccountId') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="text-xs uppercase tracking-wide text-zinc-500">Description</label>
                <input class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="text" wire:model="description" />
                @error('description') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
            </div>
            <div class="md:col-span-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900">
                <input type="checkbox" wire:model="postNow" />
                <span>Post immediately</span>
            </div>
        </div>

        <div class="mt-4">
            <button class="rounded-full bg-zinc-900 px-5 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800" type="button" wire:click="submit">Save Expense</button>
        </div>
    </div>
</div>
