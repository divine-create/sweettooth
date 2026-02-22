<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Tax Payments</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Record statutory payments and track settlement status.</p>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="save" class="grid grid-cols-1 gap-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Tax Type</label>
            <input type="text" wire:model.defer="tax_type" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" placeholder="VAT, PAYE, WHT" />
            @error('tax_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Amount</label>
            <input type="number" step="0.01" wire:model.defer="amount" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('amount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Payment Date</label>
            <input type="date" wire:model.defer="payment_date" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('payment_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="lg:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Bank Account (Optional)</label>
            <select wire:model.defer="bank_account_id" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="">Select account</option>
                @foreach($bankAccounts as $account)
                    <option value="{{ $account->id }}">{{ $account->bank_name }} - {{ $account->account_number }}</option>
                @endforeach
            </select>
            @error('bank_account_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="lg:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Reference</label>
            <input type="text" wire:model.defer="reference_number" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" placeholder="Receipt or transfer ref" />
            @error('reference_number') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</label>
            <select wire:model.defer="status" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
            </select>
            @error('status') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="lg:col-span-6 flex justify-end">
            <button type="submit" class="rounded-full bg-zinc-900 px-6 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800">Save Payment</button>
        </div>
    </form>

    <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <x-table
            :headers="['Date', 'Tax Type', 'Amount', 'Status', 'Bank', 'Reference']"
            :rows="$rows"
            striped
            paginate
            persist
            :quantity="[10, 25, 50]"
        >
            @interact('column_amount', $row)
                <span class="font-semibold">{{ \App\Helpers\LocalizationHelper::formatCurrency((float) $row->amount) }}</span>
            @endinteract
            @interact('column_status', $row)
                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ ucfirst($row->status) }}</span>
            @endinteract
            @interact('column_bank', $row)
                <span class="text-xs text-zinc-600 dark:text-zinc-300">{{ $row->bankAccount?->bank_name ?? '-' }}</span>
            @endinteract
        </x-table>
    </div>
</div>
