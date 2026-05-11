<div class="p-4 space-y-4">
    <x-breadcrumb title="Production Records" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Production'],
        ['label' => 'Records'],
    ]" :compact="false" :with-icons="true" />

    <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/30 p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                    {{ $department?->name ?? 'Production' }}
                </div>
                <div class="text-xs text-emerald-700 dark:text-emerald-300">
                    Department records and requests
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs text-emerald-600 dark:text-emerald-400">Branch ID</div>
                <div class="text-sm font-mono text-emerald-900 dark:text-emerald-100">{{ $b_id ?? request('b_id') ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-3 items-start lg:items-center justify-between">
        <div class="flex items-center gap-2">
            <button type="button" wire:click="setTab('records')"
                class="px-3 py-1.5 rounded-md text-sm font-medium border {{ $tab === 'records' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 border-zinc-300 dark:border-zinc-700' }}">
                Records
            </button>
            <button type="button" wire:click="setTab('requests')"
                class="px-3 py-1.5 rounded-md text-sm font-medium border {{ $tab === 'requests' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 border-zinc-300 dark:border-zinc-700' }}">
                Requests
            </button>
            <button type="button" wire:click="setTab('analytics')"
                class="px-3 py-1.5 rounded-md text-sm font-medium border {{ $tab === 'analytics' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 border-zinc-300 dark:border-zinc-700' }}">
                Analytics
            </button>
        </div>

        <div class="flex flex-col md:flex-row gap-2 w-full lg:w-auto">
            <input type="text" wire:model.live.debounce.400ms="search"
                placeholder="Search product, recipe, or request number..."
                class="w-full md:w-72 px-3 py-2 rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm text-zinc-900 dark:text-zinc-100">
            <input type="date" wire:model.live="dateFrom"
                class="w-full md:w-40 px-3 py-2 rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm text-zinc-900 dark:text-zinc-100">
            <input type="date" wire:model.live="dateTo"
                class="w-full md:w-40 px-3 py-2 rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm text-zinc-900 dark:text-zinc-100">
        </div>
    </div>

    @if($tab === 'records')
        <div class="flex items-center gap-4 mb-2">
            <button type="button" wire:click="$set('recordType', 'finished')"
                class="text-sm font-semibold {{ $recordType === 'finished' ? 'text-emerald-700 dark:text-emerald-400 border-b-2 border-emerald-600 pb-1' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 pb-1' }}">
                Finished Goods
            </button>
            <button type="button" wire:click="$set('recordType', 'wip')"
                class="text-sm font-semibold {{ $recordType === 'wip' ? 'text-emerald-700 dark:text-emerald-400 border-b-2 border-emerald-600 pb-1' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 pb-1' }}">
                WIP Goods
            </button>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
            <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                {{ $recordType === 'wip' ? 'Produced WIP Goods' : 'Produced Finished Goods' }}
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-zinc-500 dark:text-zinc-400">
                        <tr class="border-b border-zinc-200 dark:border-zinc-800">
                            <th class="py-2 px-4">Date</th>
                            <th class="py-2 px-4">Product</th>
                            <th class="py-2 px-4">Batch</th>
                            <th class="py-2 px-4">Produced</th>
                            <th class="py-2 px-4">Approved</th>
                            <th class="py-2 px-4">Rejected</th>
                            <th class="py-2 px-4">Remaining</th>
                            <th class="py-2 px-4">Quality</th>
                            <th class="py-2 px-4">Dispatch</th>
                        </tr>
                    </thead>
                    <tbody class="text-zinc-800 dark:text-zinc-200">
                        @forelse($records ?? [] as $record)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="py-2 px-4">{{ $record->production_time?->format('Y-m-d H:i') ?? $record->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="py-2 px-4">{{ $record->recipe?->product_name ?? $record->recipe?->product?->name ?? '-' }}</td>
                                <td class="py-2 px-4">{{ $record->batch_number ?? '-' }}</td>
                                <td class="py-2 px-4">{{ number_format((float) $record->quantity_produced, 2) }}</td>
                                <td class="py-2 px-4">{{ number_format((float) $record->quantity_approved, 2) }}</td>
                                <td class="py-2 px-4">{{ number_format((float) $record->quantity_rejected, 2) }}</td>
                                <td class="py-2 px-4">{{ number_format((float) $record->quantity_remaining, 2) }}</td>
                                <td class="py-2 px-4">{{ $record->quality_status ?? '-' }}</td>
                                <td class="py-2 px-4">{{ $record->dispatch_status ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="py-4 px-4 text-center text-zinc-500" colspan="9">No records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($records)
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    @endif

    @if($tab === 'requests')
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
            <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                Production Requests
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-zinc-500 dark:text-zinc-400">
                        <tr class="border-b border-zinc-200 dark:border-zinc-800">
                            <th class="py-2 px-4">Request #</th>
                            <th class="py-2 px-4">Product</th>
                            <th class="py-2 px-4">Requested</th>
                            <th class="py-2 px-4">Planned</th>
                            <th class="py-2 px-4">Status</th>
                            <th class="py-2 px-4">Fulfillment</th>
                            <th class="py-2 px-4">Priority</th>
                            <th class="py-2 px-4">Created</th>
                        </tr>
                    </thead>
                    <tbody class="text-zinc-800 dark:text-zinc-200">
                        @forelse($requests ?? [] as $request)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="py-2 px-4">{{ $request->itemRequest?->request_number ?? '-' }}</td>
                                <td class="py-2 px-4">{{ $request->recipe?->product_name ?? '-' }}</td>
                                <td class="py-2 px-4">{{ number_format((float) $request->requested_units, 2) }}</td>
                                <td class="py-2 px-4">{{ number_format((float) $request->planned_production_quantity, 2) }}</td>
                                <td class="py-2 px-4">{{ $request->status ?? '-' }}</td>
                                <td class="py-2 px-4">{{ $request->fulfillment_status ?? '-' }}</td>
                                <td class="py-2 px-4">{{ $request->priority ?? '-' }}</td>
                                <td class="py-2 px-4">{{ $request->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="py-4 px-4 text-center text-zinc-500" colspan="8">No requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests)
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    @endif

    @if($tab === 'analytics')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">Produced ({{ $analytics['rangeLabel'] ?? '' }})</div>
                <div class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format((float) ($analytics['totalProduced'] ?? 0), 2) }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">Approved ({{ $analytics['rangeLabel'] ?? '' }})</div>
                <div class="text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ number_format((float) ($analytics['totalApproved'] ?? 0), 2) }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">Rejected ({{ $analytics['rangeLabel'] ?? '' }})</div>
                <div class="text-2xl font-semibold text-red-600 dark:text-red-300">{{ number_format((float) ($analytics['totalRejected'] ?? 0), 2) }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">Open Requests</div>
                <div class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format((int) ($analytics['requestsOpen'] ?? 0)) }}</div>
            </div>
        </div>
    @endif
</div>
