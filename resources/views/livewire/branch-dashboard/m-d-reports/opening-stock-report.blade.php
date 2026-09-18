<div class="p-4 space-y-4">
    <x-breadcrumb
        title="Opening Stock Report"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'MD Reports', 'url' => '#'],
            ['label' => 'Opening Stock Report']
        ]"
        :compact="false"
        :with-icons="true"/>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4 border border-zinc-200 dark:border-zinc-700">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1">Sales Department</label>
                <select wire:model.live="selectedDepartmentId" class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 p-2 text-sm">
                    <option value="">Select a Department</option>
                    @foreach($salesDepartments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1">Date</label>
                <input type="date" wire:model.live="dateFilter" class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 p-2 text-sm" />
            </div>
        </div>
    </div>

    @if($selectedDepartmentId && $dateFilter)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden border border-zinc-200 dark:border-zinc-700">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 flex justify-between items-center">
                <h3 class="font-medium text-lg">
                    Opening Stock for {{ \Carbon\Carbon::parse($dateFilter)->format('D, M j, Y') }}
                </h3>
                
                <div class="text-sm">
                    @if($shiftInfo)
                        <span class="text-zinc-500">Recorded By:</span>
                        <span class="font-medium">{{ $shiftInfo->employee->name ?? 'Unknown' }}</span>
                        @if($shiftInfo->stock_verified_at)
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                Verified
                            </span>
                        @endif
                    @else
                        <span class="text-zinc-500 italic">No shift record found</span>
                    @endif
                </div>
            </div>

            @if($stockRecords->isEmpty())
                <div class="p-8 text-center text-zinc-500">
                    No opening stock records found for this date and department.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700">
                            <tr>
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3">Sales Category</th>
                                <th class="px-4 py-3 text-right">Opening Quantity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($stockRecords as $record)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $record->product->name ?? 'Unknown Product' }}</td>
                                    <td class="px-4 py-3 text-zinc-500">{{ $record->product->sales_category ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-right font-medium">
                                        {{ number_format($record->product ? $record->product->convertBaseToSalesQuantity($record->opening_quantity) : $record->opening_quantity, 2) }}
                                        {{ $record->product?->salesUom?->symbol ?? 'units' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @else
        <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-8 text-center border border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-600 dark:text-zinc-400">Please select a department and date</h3>
            <p class="text-zinc-500 mt-1">Select from the filters above to view opening stock records.</p>
        </div>
    @endif
</div>
