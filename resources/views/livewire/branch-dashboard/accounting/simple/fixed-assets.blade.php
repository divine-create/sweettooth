<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Fixed Assets</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Log new assets and track depreciation details.</p>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="save" class="grid grid-cols-1 gap-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Asset Name</label>
            <input type="text" wire:model.defer="asset_name" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('asset_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Asset Tag</label>
            <input type="text" wire:model.defer="asset_tag" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('asset_tag') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Cost</label>
            <input type="number" step="0.01" wire:model.defer="asset_cost" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('asset_cost') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Salvage</label>
            <input type="number" step="0.01" wire:model.defer="salvage_value" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('salvage_value') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Life (Months)</label>
            <input type="number" wire:model.defer="useful_life_months" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('useful_life_months') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Method</label>
            <select wire:model.defer="depreciation_method" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="straight_line">Straight Line</option>
                <option value="reducing_balance">Reducing Balance</option>
            </select>
            @error('depreciation_method') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Acquisition Date</label>
            <input type="date" wire:model.defer="acquisition_date" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('acquisition_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Funding Source</label>
            <select wire:model.defer="funding_source" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="cash">Cash</option>
                <option value="ap">Accounts Payable</option>
            </select>
            @error('funding_source') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="lg:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Bank Account (Optional)</label>
            <select wire:model.defer="bank_account_id" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="">Select account</option>
                @foreach($bankAccounts as $account)
                    <option value="{{ $account->id }}">{{ $account->bank_name }} - {{ $account->account_number }}</option>
                @endforeach
            </select>
            @error('bank_account_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="lg:col-span-6 flex justify-end">
            <button type="submit" class="rounded-full bg-zinc-900 px-6 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800">Save Asset</button>
        </div>
    </form>

    <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <x-table
            :headers="['Asset', 'Tag', 'Cost', 'Life', 'Method', 'Status']"
            :rows="$rows"
            striped
            paginate
            persist
            :quantity="[10, 25, 50]"
        >
            @interact('column_cost', $row)
                <span class="font-semibold">{{ \App\Helpers\LocalizationHelper::formatCurrency((float) $row->asset_cost) }}</span>
            @endinteract
            @interact('column_method', $row)
                <span class="text-xs text-zinc-600 dark:text-zinc-300">{{ str_replace('_', ' ', $row->depreciation_method) }}</span>
            @endinteract
        </x-table>
    </div>
</div>
