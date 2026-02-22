<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
            <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Purchase Payments</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Settle supplier invoices and track outstanding balances.</p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="grid grid-cols-1 gap-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Purchase</label>
            <select wire:model="purchase_id" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="">Select purchase</option>
                @foreach($purchases as $purchase)
                    <option value="{{ $purchase->id }}">{{ $purchase->purchase_number }} - {{ $purchase->supplier_name }}</option>
                @endforeach
            </select>
            @error('purchase_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Payment Date</label>
            <input type="date" wire:model.defer="payment_date" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('payment_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</label>
            <select wire:model.defer="status" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
            </select>
            @error('status') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
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
        <div class="lg:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Reference</label>
            <input type="text" wire:model.defer="reference_number" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('reference_number') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="lg:col-span-6 grid grid-cols-1 gap-3 rounded-lg bg-zinc-50 p-4 text-sm dark:bg-zinc-800/60 md:grid-cols-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500">Purchase Total</p>
                <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($purchase_total) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500">Balance</p>
                <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($purchase_balance) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500">Payment Amount</p>
                <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($amount) }}</p>
            </div>
        </div>
        <div class="lg:col-span-6 flex justify-end">
            <button type="submit" class="rounded-full bg-zinc-900 px-6 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800">Record Payment</button>
        </div>
    </form>

    <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <x-table
            :headers="['Purchase', 'Amount', 'Date', 'Status', 'Reference']"
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
        </x-table>
    </div>
</div>
