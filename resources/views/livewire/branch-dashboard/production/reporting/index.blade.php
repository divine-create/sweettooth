<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Production</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Reports</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Browse and review generated production reports.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Search</label>
            <input type="text" wire:model.debounce.300ms="search" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" placeholder="Report name, type, department" />
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</label>
            <select wire:model="status" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="all">All</option>
                <option value="draft">Draft</option>
                <option value="review">Review</option>
                <option value="approved">Approved</option>
                <option value="published">Published</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Type</label>
            <select wire:model="type" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white">
                <option value="all">All</option>
                @foreach($typeOptions as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">From</label>
            <input type="date" wire:model="dateFrom" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">To</label>
            <input type="date" wire:model="dateTo" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-white" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        @forelse($reports as $report)
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $report->report_name }}</h3>
                        <p class="text-xs uppercase tracking-wide text-zinc-500">{{ $report->report_type }}</p>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $report->department?->name ?? 'Production' }}
                        </p>
                    </div>
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        {{ ucfirst($report->status ?? 'draft') }}
                    </span>
                </div>
                <div class="mt-4 text-xs text-zinc-500">
                    Generated: {{ optional($report->report_date)->format('Y-m-d') ?? '-' }}
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <a class="rounded-full border border-zinc-200 px-3 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800"
                       href="{{ branch_route('branch-dashboard.production.reporting.show', ['id' => $report->id, 'b_id' => $b_id]) }}">
                        View
                    </a>
                    <a class="rounded-full border border-zinc-200 px-3 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800"
                       href="{{ branch_route('branch-dashboard.prints.department-report', ['reportId' => $report->id, 'b_id' => $b_id]) }}" target="_blank">
                        Print
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-300 p-10 text-center text-sm text-zinc-500 dark:border-zinc-700">
                No production reports found.
            </div>
        @endforelse
    </div>

    <div>
        {{ $reports->links() }}
    </div>
</div>
