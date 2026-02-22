<div class="p-3 space-y-3">

    <style>
        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            @apply bg-zinc-300 dark:bg-zinc-700 rounded-full;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            @apply bg-zinc-400 dark:bg-zinc-600;
        }
        [x-cloak] {
            display: none !important;
        }
    </style>

    <x-breadcrumb
        title="Stock Movements"
        :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Inventory'],
            ['label' => 'Stock Movements']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Filters Section -->
    <div x-data="{ open: false }"
        class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 transition-all duration-300">
        <div class="flex justify-between items-center px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14.293 13H10v5l-4-4v-3.586L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters
            </h2>
            <button @click="open = !open"
                class="flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                    <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span x-text="open ? 'Close' : 'Show Filters'"></span>
            </button>
        </div>

        <div x-show="open" x-collapse class="p-3 space-y-3">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search by item name, SKU, person, or notes..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Branch</label>
                    <select wire:model.live="filterBranch"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Movement Type</label>
                    <select wire:model.live="filterType"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="in">In</option>
                        <option value="out">Out</option>
                        <option value="adjustment">Adjustment</option>
                        <option value="transfer">Transfer</option>
                        <option value="damaged">Damaged</option>
                        <option value="return">Return</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Shift</label>
                    <select wire:model.live="filterShift"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Shifts</option>
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                        <option value="night">Night</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Department</label>
                    <select wire:model.live="filterDepartment"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date From</label>
                    <input type="date" wire:model.live="filterDateFrom"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date To</label>
                    <input type="date" wire:model.live="filterDateTo"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="flex flex-wrap gap-2 justify-end pt-2.5 border-t border-zinc-200 dark:border-zinc-700">
                <button wire:click="resetFilters"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <x-table
        :headers="[
            ['index' => 'date_time', 'label' => 'Date & Time'],
            ['index' => 'branch', 'label' => 'Branch'],
            ['index' => 'item', 'label' => 'Item'],
            ['index' => 'type', 'label' => 'Type'],
            ['index' => 'quantity', 'label' => 'Quantity Change'],
            ['index' => 'stock_levels', 'label' => 'Stock Levels'],
            ['index' => 'shift', 'label' => 'Shift'],
            ['index' => 'department', 'label' => 'Department'],
            ['index' => 'people', 'label' => 'People Involved'],
            ['index' => 'purpose', 'label' => 'Purpose/Notes'],
        ]"
        :rows="$movements"
        striped
        paginate
        persist
        :filter="['quantity' => 'quantity', 'search' => 'search']"
        :quantity="[10, 25, 50, 100]">

        @interact('column_date_time', $row)
            <div class="text-zinc-900 dark:text-zinc-100">
                <div class="font-medium">{{ $row->movement_date->format('Y-m-d') }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row->movement_date->format('H:i') }}</div>
            </div>
        @endinteract

        @interact('column_branch', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->stock->branch->name ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_item', $row)
            <div>
                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $row->stock->item->name ?? 'N/A' }}
                </div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                    SKU: {{ $row->stock->item->sku ?? 'N/A' }}
                </div>
            </div>
        @endinteract

        @interact('column_type', $row)
            @php
                $typeColors = [
                    'in' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    'out' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                    'adjustment' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    'transfer' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                    'damaged' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                    'return' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                ];
                $typeIcons = [
                    'in' => '↓',
                    'out' => '↑',
                    'adjustment' => '⟳',
                    'transfer' => '⇄',
                    'damaged' => '⚠',
                    'return' => '↩',
                ];
            @endphp
            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $typeColors[$row->type] ?? '' }}">
                {{ $typeIcons[$row->type] ?? '' }} {{ ucfirst($row->type) }}
            </span>
        @endinteract

        @interact('column_quantity', $row)
            @php
                $quantityClass = $row->isInbound() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                $sign = $row->isInbound() ? '+' : '-';
            @endphp
            <div class="font-semibold {{ $quantityClass }}">
                {{ $sign }}{{ number_format(abs($row->quantity), 2) }}
            </div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ $row->stock->item->uom ?? '' }}
            </div>
        @endinteract

        @interact('column_stock_levels', $row)
            <div class="text-sm">
                <div class="text-zinc-600 dark:text-zinc-400">
                    Before: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($row->quantity_before, 2) }}</span>
                </div>
                <div class="text-zinc-600 dark:text-zinc-400">
                    After: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($row->quantity_after, 2) }}</span>
                </div>
            </div>
        @endinteract

        @interact('column_shift', $row)
            @php
                $shift = null;
                try {
                    if ($row->reference_type === 'App\Models\ItemRequest' && $row->reference) {
                        $shift = $row->reference->shift ?? null;
                    }
                } catch (\Exception $e) {
                    // Handle invalid reference types
                    $shift = null;
                }
                $shiftColors = [
                    'morning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    'afternoon' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                    'night' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                ];
            @endphp
            @if($shift)
                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $shiftColors[$shift] ?? '' }}">
                    {{ ucfirst($shift) }}
                </span>
            @else
                <span class="text-xs text-zinc-400 dark:text-zinc-500">N/A</span>
            @endif
        @endinteract

        @interact('column_department', $row)
            @php
                $department = null;
                try {
                    if ($row->reference_type === 'App\Models\ItemRequest' && $row->reference) {
                        $department = $row->reference->department ?? null;
                    }
                } catch (\Exception $e) {
                    // Handle invalid reference types
                    $department = null;
                }
            @endphp
            @if($department)
                <div class="text-zinc-900 dark:text-zinc-100">
                    {{ $department->name }}
                </div>
            @else
                <span class="text-xs text-zinc-400 dark:text-zinc-500">N/A</span>
            @endif
        @endinteract

        @interact('column_people', $row)
            <div class="text-sm space-y-1">
                @php
                    $request = null;
                    try {
                        if ($row->reference_type === 'App\Models\ItemRequest' && $row->reference) {
                            $request = $row->reference;
                        }
                    } catch (\Exception $e) {
                        // Handle invalid reference types
                        $request = null;
                    }
                @endphp

                @if($request)
                    @if($request->requester)
                        <div class="text-zinc-600 dark:text-zinc-400">
                            <span class="text-xs">Ordered:</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $request->requester->name }}</span>
                        </div>
                    @endif

                    @if($request->approver)
                        <div class="text-zinc-600 dark:text-zinc-400">
                            <span class="text-xs">Approved:</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $request->approver->name }}</span>
                        </div>
                    @endif
                @endif

                @if($row->mover)
                    <div class="text-zinc-600 dark:text-zinc-400">
                        <span class="text-xs">{{ $row->type === 'out' ? 'Dispatched' : 'Moved' }}:</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->mover->name }}</span>
                    </div>
                @endif

                @if(!$request && !$row->mover)
                    <span class="text-xs text-zinc-400 dark:text-zinc-500">N/A</span>
                @endif
            </div>
        @endinteract

        @interact('column_purpose', $row)
            <div class="text-sm">
                @php
                    $hasReference = false;
                    $referenceDisplay = '';
                    try {
                        if ($row->reference_type && class_exists($row->reference_type) && $row->reference) {
                            $hasReference = true;
                            $referenceDisplay = class_basename($row->reference_type);
                            if ($row->reference instanceof \App\Models\ItemRequest) {
                                $referenceDisplay .= ': ' . ($row->reference->request_number ?? '#' . $row->reference_id);
                            } else {
                                $referenceDisplay .= ': #' . $row->reference_id;
                            }
                        } elseif ($row->reference_type) {
                            // For manual_adjustment or other string types
                            $hasReference = true;
                            $referenceDisplay = ucwords(str_replace('_', ' ', $row->reference_type));
                        }
                    } catch (\Exception $e) {
                        $hasReference = false;
                    }
                @endphp

                @if($hasReference)
                    <div class="text-zinc-600 dark:text-zinc-400 mb-1">
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $referenceDisplay }}
                        </span>
                    </div>
                @endif

                @if($row->notes)
                    <div class="text-zinc-600 dark:text-zinc-400">
                        {{ Str::limit($row->notes, 80) }}
                    </div>
                @else
                    <span class="text-xs text-zinc-400 dark:text-zinc-500">No notes</span>
                @endif
            </div>
        @endinteract
    </x-table>

</div>
