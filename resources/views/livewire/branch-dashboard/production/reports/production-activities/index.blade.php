<div class="space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Production Activities</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Real-time view of production output, requests, dispatches, and production value.
            </p>
        </div>
        <div class="flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span>From: {{ $customDateFrom }}</span>
            <span>To: {{ $customDateTo }}</span>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <x-select.native
                    label="Period"
                    wire:model.live="periodFilter"
                    :options="[
                        ['label' => 'Today', 'value' => 'today'],
                        ['label' => 'Yesterday', 'value' => 'yesterday'],
                        ['label' => 'This Week', 'value' => 'week'],
                        ['label' => 'This Month', 'value' => 'month'],
                        ['label' => 'Last Month', 'value' => 'last_month'],
                        ['label' => 'Custom Range', 'value' => 'custom'],
                    ]"
                    select="label:label|value:value"
                />
            </div>
            <div>
                <x-input label="From" type="date" wire:model="customDateFrom" :disabled="$periodFilter !== 'custom'" />
            </div>
            <div>
                <x-input label="To" type="date" wire:model="customDateTo" :disabled="$periodFilter !== 'custom'" />
            </div>
            <div>
                <x-select.native label="Shift" wire:model.live="shiftType">
                    <option value="all">All Shifts</option>
                    <option value="morning">Morning</option>
                    <option value="afternoon">Afternoon</option>
                    <option value="night">Night</option>
                </x-select.native>
            </div>
            <div>
                <x-select.native label="Request Status" wire:model.live="requestStatus">
                    <option value="all">All</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </x-select.native>
            </div>
            @if(count($availableDepartments ?? []) > 0)
                <div>
                    <x-select.native label="Department" wire:model.live="selectedDepartmentId">
                        <option value="">All Departments</option>
                        @foreach($availableDepartments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </x-select.native>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Produced</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($summary['total_produced'] ?? 0, 2) }}</p>
            <p class="mt-1 text-xs text-gray-500">Requested: {{ number_format($summary['total_requested'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Sent Out</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($summary['total_sent_out'] ?? 0, 2) }}</p>
            <p class="mt-1 text-xs text-gray-500">Unfulfilled: {{ number_format($summary['unfulfilled_quantity'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Production Value</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($summary['production_value'] ?? 0, 2) }}</p>
            <p class="mt-1 text-xs text-gray-500">Variance: {{ number_format($summary['total_variance'] ?? 0, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Recent Production Records</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Time</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Product</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Produced</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Sent Out</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($productionRecords as $record)
                            <tr>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                                    {{ optional($record->production_time)->format('Y-m-d H:i') ?? '-' }}
                                </td>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">
                                    {{ $record->recipe?->product_name ?? 'Unknown' }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                    {{ number_format($record->quantity_produced ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                    {{ number_format($record->quantity_sent_out ?? 0, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-center text-gray-500" colspan="4">No production records for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Production Requests</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Request</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Product</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Planned</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($productionRequests as $request)
                            <tr>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                                    {{ $request->id }}
                                </td>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">
                                    {{ $request->recipe?->product_name ?? 'Unknown' }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                    {{ number_format($request->planned_production_quantity ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
                                        {{ $request->fulfillment_status ?? 'unknown' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-center text-gray-500" colspan="4">No production requests for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Daily Produce Summary</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Product</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Requested</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Produced</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Sent Out</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Variance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($dailyProduces as $row)
                        <tr>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                                {{ optional($row->produce_date)->format('Y-m-d') ?? '-' }}
                            </td>
                            <td class="px-4 py-2 text-gray-900 dark:text-white">
                                {{ $row->recipe?->product_name ?? 'Unknown' }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($row->requested_quantity ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($row->produced_quantity ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($row->sent_out_quantity ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($row->variance ?? 0, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-gray-500" colspan="6">No daily produce data for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('livewire.partials.department-select-modal')
</div>
