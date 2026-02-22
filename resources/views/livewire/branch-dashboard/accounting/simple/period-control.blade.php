<div class="space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Period Control</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Create, close, lock, and reopen accounting periods.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <div class="flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <span class="text-zinc-400">Search</span>
                <input class="w-52 border-none bg-transparent p-0 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none dark:text-white" type="text" placeholder="Month, year, status" wire:model.debounce.300ms="search" />
            </div>
            <button
                wire:click="toggleCreate"
                class="rounded-full border border-zinc-900 bg-zinc-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800 dark:border-white dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
            >
                {{ $showCreate ? 'Close' : 'New Period' }}
            </button>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($showCreate)
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Create Accounting Period</h2>
                    <p class="mt-1 text-xs text-zinc-500">Choose year and month to auto-fill dates.</p>
                </div>
                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">New</span>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="text-xs uppercase tracking-wide text-zinc-500">Year</label>
                    <input wire:model.defer="year" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="number" min="2000" max="2100" />
                    @error('year') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="text-xs uppercase tracking-wide text-zinc-500">Month</label>
                    <input wire:model.defer="month" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="number" min="1" max="12" />
                    @error('month') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="text-xs uppercase tracking-wide text-zinc-500">Period Start</label>
                    <input wire:model.defer="period_start" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="date" />
                    @error('period_start') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="text-xs uppercase tracking-wide text-zinc-500">Period End</label>
                    <input wire:model.defer="period_end" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" type="date" />
                    @error('period_end') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="text-xs uppercase tracking-wide text-zinc-500">Status</label>
                    <select wire:model.defer="status" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                        <option value="locked">Locked</option>
                    </select>
                    @error('status') <div class="mt-1 text-xs text-rose-500">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <button wire:click="createPeriod" class="rounded-full bg-zinc-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800">
                    Create Period
                </button>
                <button wire:click="toggleCreate" class="rounded-full border border-zinc-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:border-zinc-300">
                    Cancel
                </button>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <x-table
            :$headers
            :$rows
            striped
            paginate
            persist
            :filter="['quantity' => 'quantity', 'search' => 'search']"
            :quantity="[10, 12, 25, 50]"
        >
            @interact('column_period', $row)
                <div class="font-semibold text-zinc-900 dark:text-white">
                    {{ $row->name ?? ($row->month . '/' . $row->year) }}
                </div>
                <div class="text-xs text-zinc-500">Year {{ $row->year }}</div>
            @endinteract

            @interact('column_period_start', $row)
                <span class="text-zinc-700 dark:text-zinc-300">
                    {{ optional($row->period_start)->format('Y-m-d') ?? $row->period_start }}
                </span>
            @endinteract

            @interact('column_period_end', $row)
                <span class="text-zinc-700 dark:text-zinc-300">
                    {{ optional($row->period_end)->format('Y-m-d') ?? $row->period_end }}
                </span>
            @endinteract

            @interact('column_status', $row)
                @if ($row->status === 'open')
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200">Open</span>
                @elseif ($row->status === 'closed')
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/20 dark:text-amber-200">Closed</span>
                @else
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Locked</span>
                @endif
            @endinteract

            @interact('column_action', $row)
                <div class="flex items-center justify-end gap-2">
                    @if ($row->status === 'open')
                        <button class="rounded-full border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-700 hover:border-rose-300 hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-200 dark:hover:bg-rose-900/30" type="button" wire:click="closePeriod({{ $row->id }})">
                            Close
                        </button>
                        <button class="rounded-full border border-amber-200 px-3 py-1 text-xs font-semibold text-amber-700 hover:border-amber-300 hover:bg-amber-50 dark:border-amber-900/50 dark:text-amber-200 dark:hover:bg-amber-900/30" type="button" wire:click="lockPeriod({{ $row->id }})">
                            Lock
                        </button>
                    @elseif ($row->status === 'closed')
                        <button class="rounded-full border border-blue-200 px-3 py-1 text-xs font-semibold text-blue-700 hover:border-blue-300 hover:bg-blue-50 dark:border-blue-900/50 dark:text-blue-200 dark:hover:bg-blue-900/30" type="button" wire:click="reopenPeriod({{ $row->id }})">
                            Reopen
                        </button>
                    @else
                        -
                    @endif
                </div>
            @endinteract
        </x-table>
    </div>
</div>
