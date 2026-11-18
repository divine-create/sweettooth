<div class="p-4 space-y-4">
    <x-breadcrumb
        title="Closing Stock Report"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'MD Reports', 'url' => '#'],
            ['label' => 'Closing Stock Report']
        ]"
        :compact="false"
        :with-icons="true"/>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4 border border-zinc-200 dark:border-zinc-700 print:hidden">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1">Department</label>
                <select wire:model.live="selectedDepartmentId" class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 p-2 text-sm">
                    <option value="">Select a Department</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">
                            {{ $dept->name }} ({{ $dept->category?->name ?? 'Dept' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1">Date</label>
                <input type="date" wire:model.live="dateFilter" class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 p-2 text-sm" />
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1">Shift</label>
                <select wire:model.live="selectedShiftType" class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 p-2 text-sm">
                    <option value="all">All Shifts</option>
                    <option value="morning">Morning</option>
                    <option value="afternoon">Afternoon</option>
                    <option value="night">Night</option>
                </select>
            </div>
        </div>
    </div>

    @if($selectedDepartmentId && $dateFilter)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden border border-zinc-200 dark:border-zinc-700">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 flex flex-wrap justify-between items-center gap-2">
                <div>
                    <h3 class="font-medium text-lg">
                        Closing Stock for {{ \Carbon\Carbon::parse($dateFilter)->format('D, M j, Y') }} — {{ $selectedDept?->name }}
                    </h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $isProduction ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' }}">
                        {{ $isProduction ? 'Production Department (Tracked Store)' : 'Sales Department (POS Counter)' }}
                    </span>
                </div>

                <div class="text-sm flex flex-wrap items-center gap-4">
                    @forelse($shiftsInfo as $shift)
                        <div>
                            <span class="text-zinc-500 capitalize">{{ $shift->shift_type ?? 'Shift' }}:</span>
                            <span class="font-medium">{{ $shift->employee->name ?? 'Unknown' }}</span>
                            @if($shift->status === 'closed')
                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    Closed
                                </span>
                            @else
                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Active
                                </span>
                            @endif
                        </div>
                    @empty
                        <span class="text-zinc-500 italic">No shift record found</span>
                    @endforelse

                    <button type="button" onclick="window.print()"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-zinc-800 hover:bg-zinc-900 text-white shadow-sm transition flex items-center gap-1.5 print:hidden">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Print Report</span>
                    </button>
                </div>
            </div>

            @if(count($stockRecords) === 0)
                <div class="p-8 text-center text-zinc-500">
                    No closing stock records found for this date and department.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700">
                            <tr>
                                <th class="px-4 py-3">Item / Product</th>
                                <th class="px-4 py-3">Shift</th>
                                <th class="px-4 py-3">Type / Category</th>
                                @if($isProduction)
                                    <th class="px-4 py-3 text-right">Opening</th>
                                    <th class="px-4 py-3 text-right">Added</th>
                                    <th class="px-4 py-3 text-right">Used</th>
                                    <th class="px-4 py-3 text-right">Closing</th>
                                    <th class="px-4 py-3 text-center">Variance</th>
                                @else
                                    <th class="px-4 py-3 text-right">Closing Quantity</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($stockRecords as $record)
                                <tr>
                                    @if($isProduction)
                                        <td class="px-4 py-3 font-medium">
                                            {{ $record->item_name }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize {{ ($record->shift?->shift_type ?? 'morning') === 'afternoon' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                                {{ $record->shift?->shift_type ?? 'morning' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-zinc-500 text-xs">
                                            @if($record->isRawMaterial())
                                                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">Raw Material</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">Finished / WIP</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-xs">
                                            {{ number_format($record->opening_quantity, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-xs text-emerald-600">
                                            +{{ number_format($record->addition_quantity, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-xs text-rose-600">
                                            -{{ number_format($record->quantity_used, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium">
                                            {{ number_format($record->closing_quantity, 2) }}
                                            {{ $record->uom_symbol }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @php $v = (float) $record->variance; @endphp
                                            @if($v == 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    0.00
                                                </span>
                                            @elseif($v > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 font-mono">
                                                    +{{ number_format($v, 2) }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 font-mono">
                                                    {{ number_format($v, 2) }}
                                                </span>
                                            @endif
                                        </td>
                                    @else
                                        <td class="px-4 py-3 font-medium">{{ $record->product->name ?? 'Unknown Product' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize {{ $record->shift_type === 'afternoon' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                                {{ $record->shift_type ?? 'morning' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-zinc-500">{{ $record->product->sales_category ?? 'Sales Good' }}</td>
                                        <td class="px-4 py-3 text-right font-medium">
                                            {{ number_format($record->closing_quantity, 2) }}
                                            {{ $record->product?->unitOfMeasure?->symbol ?? 'units' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Recorded
                                            </span>
                                        </td>
                                    @endif
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
            <p class="text-zinc-500 mt-1">Select from the filters above to view closing stock records.</p>
        </div>
    @endif

    <style>
    @media print {
        header, nav, aside, [role="navigation"],
        flux-sidebar, .flux-header,
        button, input, select, form,
        .print\:hidden, .no-print, .breadcrumb-container {
            display: none !important;
        }

        body, main {
            background: #ffffff !important;
            color: #000000 !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .bg-white, .dark\:bg-zinc-800 {
            box-shadow: none !important;
            border: 1px solid #d1d5db !important;
            background: #ffffff !important;
            color: #000000 !important;
            page-break-inside: avoid;
        }

        table {
            width: 100% !important;
            border-collapse: collapse !important;
        }

        th, td {
            border: 1px solid #d1d5db !important;
            padding: 5px 8px !important;
            color: #000000 !important;
            background: #ffffff !important;
        }

        th {
            background: #f3f4f6 !important;
            font-weight: bold !important;
        }
    }
    </style>
</div>
