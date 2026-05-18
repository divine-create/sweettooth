<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Budgets</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Plan spend and track forecasted vs approved budgets.</p>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="save" class="grid grid-cols-1 gap-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">GL Account</label>
            <select wire:model.defer="gl_account_id" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="">Select account</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->account_name }}</option>
                @endforeach
            </select>
            @error('gl_account_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Period</label>
            <select wire:model.defer="accounting_period_id" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="">Select period</option>
                @foreach($periods as $period)
                    <option value="{{ $period->id }}">{{ $period->period_name ?? ($period->period_start.' - '.$period->period_end) }}</option>
                @endforeach
            </select>
            @error('accounting_period_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Planned Amount</label>
            <input type="number" step="0.01" wire:model.defer="planned_amount" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('planned_amount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Forecast Amount</label>
            <input type="number" step="0.01" wire:model.defer="forecast_amount" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('forecast_amount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Category</label>
            <input type="text" wire:model.defer="category" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('category') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="lg:col-span-4">
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Description</label>
            <input type="text" wire:model.defer="description" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
            @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-center gap-2 lg:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Approved</label>
            <input type="checkbox" wire:model.defer="is_approved" class="h-4 w-4 rounded border-zinc-300 text-zinc-900" />
        </div>
        <div class="lg:col-span-6 flex justify-end">
            <button type="submit" class="rounded-full bg-zinc-900 px-6 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800">Save Budget</button>
        </div>
    </form>

    <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <x-table
            :headers="[
                ['index' => 'account', 'label' => 'Account'],
                ['index' => 'period', 'label' => 'Period'],
                ['index' => 'planned', 'label' => 'Planned'],
                ['index' => 'forecast', 'label' => 'Forecast'],
                ['index' => 'approved', 'label' => 'Approved'],
            ]"
            :rows="$rows"
            striped
            paginate
            persist
            :quantity="[10, 25, 50]"
        >
            @interact('column_planned', $row)
                <span class="font-semibold">{{ \App\Helpers\LocalizationHelper::formatCurrency((float) $row->planned_amount) }}</span>
            @endinteract
            @interact('column_forecast', $row)
                <span>{{ \App\Helpers\LocalizationHelper::formatCurrency((float) ($row->forecast_amount ?? 0)) }}</span>
            @endinteract
            @interact('column_approved', $row)
                <span class="text-xs">{{ $row->is_approved ? 'Yes' : 'No' }}</span>
            @endinteract
        </x-table>
    </div>
</div>
