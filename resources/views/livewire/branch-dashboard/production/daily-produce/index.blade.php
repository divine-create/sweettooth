<div class="p-3 space-y-3">

    <div class="flex items-center justify-between">
        <x-breadcrumb
            title="Daily Production Tracking"
            :items="[
                ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
                ['label' => 'Production'],
                ['label' => 'Daily Produce']
            ]"
            :compact="false"
            :with-icons="true"/>

        <button wire:click="toggleHelpModal"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center gap-2"
                title="How to use this page">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Help
        </button>
    </div>

    <!-- Shift Selection -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Select Shift to View/Edit</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    @if($selectedShiftId === 'no-shift')
                        Viewing: No Shift - Today's Requests
                        <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs rounded-full">
                            {{ $noShiftRequestsCount ?? 0 }} production request(s)
                        </span>
                    @elseif($currentShift)
                        Viewing: {{ ucfirst($currentShift->shift_type) }} - {{ $currentShift->shift_date->format('M d, Y') }}
                        @if($productionRequestsCount > 0)
                            <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs rounded-full">
                                {{ $productionRequestsCount }} production request(s)
                            </span>
                        @else
                            <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 text-xs rounded-full">
                                No production requests for this shift
                            </span>
                        @endif
                    @else
                        No active shift for today
                    @endif
                </p>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">
                    💡 You can select past shifts to view or edit previous production activities
                </p>
            </div>
            <div class="flex gap-2 items-center">
                <select wire:model.live="selectedShiftId"
                        class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
                    <option value="">-- Select Shift --</option>
                    @if($selectedShiftId === 'no-shift')
                    <option value="no-shift" selected>
                        🔧 No Shift (Super Admin) - Today's Requests ({{ $noShiftRequestsCount ?? 0 }})
                    </option>
                    @else
                    @if(($noShiftRequestsCount ?? 0) > 0 || count($availableShifts) == 0)
                        <option value="no-shift">
                            🔧 No Shift (Super Admin) - Today's Requests ({{ $noShiftRequestsCount ?? 0 }})
                        </option>
                    @endif
                    @endif
                    @foreach($availableShifts as $shift)
                        <option value="{{ $shift->id }}">
                            {{ $shift->shift_date->format('M d, Y') }} - {{ ucfirst($shift->shift_type) }} Shift
                            @if($shift->id == $currentShift?->id && $shift->shift_date->isToday()) (Today) @endif
                        </option>
                    @endforeach
                </select>
                <button wire:click="saveAllQuantities" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium flex items-center gap-2"
                        :class="{ 'opacity-75': $wire.loading }">
                    <span wire:loading.remove wire:target="saveAllQuantities" class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save All
                    </span>
                    <span wire:loading wire:target="saveAllQuantities" class="flex items-center gap-2">
                        <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>

    @if(empty($dailyProduces))
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-yellow-900 dark:text-yellow-100">No Production Data Available</h3>
                    <p class="text-yellow-700 dark:text-yellow-300 mt-2">
                        @if($productionRequestsCount == 0)
                            No production requests found for the selected shift.
                        @else
                            Daily produce records exist but may not have loaded properly. Try refreshing.
                        @endif
                    </p>
                    <div class="mt-4 space-y-2">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200 font-medium">To start production:</p>
                        <ol class="text-sm text-yellow-700 dark:text-yellow-300 list-decimal list-inside space-y-1 ml-2">
                            <li>Create a <strong>Production Request</strong> for this shift</li>
                            <li>Wait for <strong>Item Request</strong> to be approved</li>
                            <li>Wait for ingredients to be <strong>dispatched</strong> from inventory</li>
                            <li>Then you can track production here</li>
                        </ol>
                    </div>
                    <div class="mt-4 flex gap-2">
                        @if(!$currentShift && auth()->user()->is_super_admin)
                            <span class="text-xs text-yellow-600 dark:text-yellow-400 italic">
                                💡 As a super admin, you can create production requests without selecting a shift.
                            </span>
                        @endif
                        <a href="{{ branch_route('branch-dashboard.production.request.create', ['deptSlug' => $dept_slug]) }}"
                           class="inline-block px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium">
                            Create Production Request
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Daily Produce Table -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Product & Request</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700" x-data="{ expanded: {} }">
    @foreach($dailyProduces as $key => $produce)
    <!-- Main Collapsed Row -->
    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 cursor-pointer"
        @click="expanded['{{ $key }}'] = !expanded['{{ $key }}']">
        <!-- Product & Request Info -->
        <td class="px-4 py-3">
            <div class="flex items-center gap-3">
                <!-- Collapse/Expand Icon -->
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400 transform transition-transform"
                         :class="{ 'rotate-90': expanded['{{ $key }}'] }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        @if(!empty($produce['production_request_number']))
                            <span class="text-xs font-mono px-2 py-1 bg-zinc-100 text-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 rounded">
                                {{ $produce['production_request_number'] }}
                            </span>
                        @endif
                        @if(!empty($produce['item_request_number']))
                            <span class="text-xs font-mono px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded">
                                {{ $produce['item_request_number'] }}
                            </span>
                        @endif
                        @if(!empty($produce['request_source_label']))
                            <span class="text-xs px-2 py-1 rounded {{ $produce['request_source_class'] }}">
                                {{ $produce['request_source_label'] }}
                            </span>
                        @endif
                        @if(!empty($produce['is_sales_request']))
                            @php
                                $resolvedSalesDeptName = $produce['sales_department_name'] ?? null;
                                if (empty($resolvedSalesDeptName) && !empty($produce['batches'])) {
                                    foreach ($produce['batches'] as $batchRow) {
                                        if (!empty($batchRow['allowed_sales_department_id'])) {
                                            $resolvedDept = collect($salesDepartments)->firstWhere('id', $batchRow['allowed_sales_department_id']);
                                            $resolvedSalesDeptName = $resolvedDept['name'] ?? ('Sales Dept #' . $batchRow['allowed_sales_department_id']);
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            @if(!empty($resolvedSalesDeptName))
                                <span class="text-xs px-2 py-1 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
                                    To: {{ $resolvedSalesDeptName }}
                                </span>
                            @endif
                        @endif
                        <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $produce['recipe_name'] }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $produce['uom'] }}</span>
                        @if($produce['production_records_count'] > 0)
                            <span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs rounded-full">
                                {{ $produce['production_records_count'] }} batches
                            </span>
                        @endif
                        <span class="text-xs text-zinc-600 dark:text-zinc-400">
                            Requested (Production): <strong>{{ number_format($produce['requested_quantity'], 2) }}</strong>
                            @if(!empty($produce['uom']))
                                {{ $produce['uom'] }}
                            @endif
                        </span>
                        @if(!empty($produce['requested_units']) && $produce['requested_units'] != $produce['requested_quantity'])
                            <span class="text-xs text-orange-600 dark:text-orange-400">
                                Sales Requested:
                                <strong>{{ number_format($produce['requested_units'], 2) }}</strong>
                                @if(!empty($produce['sales_uom']))
                                    {{ $produce['sales_uom'] }}
                                @endif
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                    ({{ number_format($produce['requested_quantity'], 2) }} {{ $produce['uom'] }})
                                </span>
                            </span>
                            <span class="text-xs text-emerald-600 dark:text-emerald-400">
                                Excess to Stock: <strong>{{ number_format($produce['excess_to_stock'], 2) }}</strong>
                                @if(!empty($produce['uom']))
                                    {{ $produce['uom'] }}
                                @endif
                            </span>
                        @endif
                        <span class="text-xs text-blue-600 dark:text-blue-400">
                            Produced: <strong>{{ number_format($produce['produced_quantity'], 2) }}</strong>
                        </span>
                    </div>
                </div>
            </div>
        </td>

        <!-- Status -->
        <td class="px-4 py-3 text-center" @click.stop>
            <div class="flex flex-col items-center gap-2">
                <span class="inline-block px-2 py-1 rounded-full text-xs font-medium {{ $produce['status_badge_color'] }}">
                    {{ ucfirst(str_replace('_', ' ', $produce['computed_status'])) }}
                </span>

                <!-- Producability Information -->
                @php
                    $prod = $produce['producability'];
                    $canMake = $prod['producable_quantity'];
                    $requested = $prod['requested_quantity'];
                @endphp

                @if($canMake == 0)
                    <span class="text-xs text-red-600 dark:text-red-400 font-semibold">❌ Cannot produce</span>
                @elseif($canMake < $requested)
                    <span class="text-xs text-orange-600 dark:text-orange-400 font-semibold">
                        ⚠ Can make {{ number_format($canMake) }} of {{ number_format($requested) }}
                    </span>
                @else
                    <span class="text-xs text-green-600 dark:text-green-400 font-semibold">
                        ✓ Ready to produce
                    </span>
                @endif

                <!-- Variance indicator -->
                @if($produce['has_variance_issue'])
                    <span class="text-xs text-red-600 dark:text-red-400 font-semibold">
                        ⚠ Variance: {{ number_format($produce['variance_percentage'], 1) }}%
                    </span>
                @endif
            </div>
        </td>

        <!-- Actions -->
        <td class="px-4 py-3 text-center" @click.stop>
            <div class="flex items-center justify-center gap-2">
                @php
                    $canProduce = $produce['producability']['producable_quantity'] > 0;
                @endphp

                @if($canProduce)
                    <button wire:click="openRecordModal({{ $produce['id'] }})"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium"
                            title="Record Production Batch">
                        📝 Record Batch
                    </button>
                @else
                    <button disabled
                            class="px-3 py-1.5 bg-gray-400 text-white rounded text-xs font-medium cursor-not-allowed"
                            title="Cannot produce - {{ $produce['producability']['limiting_ingredient'] }} missing">
                        📝 Record Batch
                    </button>
                @endif

                @if($produce['manual_status'] === 'completed')
                    <button wire:click="markInProgress({{ $produce['id'] }})"
                            class="px-3 py-1.5 bg-yellow-600 hover:bg-yellow-700 text-white rounded text-xs font-medium"
                            title="Reopen for production">
                        🔄 Reopen
                    </button>
                @else
                    <button wire:click="markComplete({{ $produce['id'] }})"
                            class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-medium"
                            title="Mark this product as completed">
                        ✓ Complete
                    </button>
                @endif
            </div>
        </td>
    </tr>

    <!-- Expanded Details Section -->
    <tr x-show="expanded['{{ $key }}']" x-cloak x-collapse class="bg-zinc-50 dark:bg-zinc-900/50">
        <td colspan="3" class="px-6 py-4">
            <div class="space-y-4">
                <!-- Production Details Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Opening & Requested Section -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Opening & Request
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Opening Qty:</span>
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($produce['opening_quantity'], 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Requested (Production):</span>
                                <span class="font-semibold text-blue-600 dark:text-blue-400">
                                    {{ number_format($produce['requested_quantity'], 2) }}
                                    @if(!empty($produce['uom']))
                                        {{ $produce['uom'] }}
                                    @endif
                                </span>
                            </div>
                            @if(!empty($produce['requested_units']) && $produce['requested_units'] != $produce['requested_quantity'])
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Sales Requested:</span>
                                <span class="font-semibold text-orange-600 dark:text-orange-400">
                                    {{ number_format($produce['requested_units'], 2) }}
                                    @if(!empty($produce['sales_uom']))
                                        {{ $produce['sales_uom'] }}
                                    @endif
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                        ({{ number_format($produce['requested_quantity'], 2) }} {{ $produce['uom'] }})
                                    </span>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Excess to Stock:</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ number_format($produce['excess_to_stock'], 2) }}
                                    @if(!empty($produce['uom']))
                                        {{ $produce['uom'] }}
                                    @endif
                                </span>
                            </div>
                            @endif
                            <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                <span class="text-zinc-600 dark:text-zinc-400">Produced:</span>
                                <span class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($produce['produced_quantity'], 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Production & Dispatch Section -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            Dispatch & Orders
                        </h4>
                        <div class="space-y-3 text-sm" @click.stop>
                            <div>
                                <label class="text-xs text-red-600 dark:text-red-400 font-medium">Callback (Damaged):</label>
                                <input type="number" step="0.01" min="0"
                                       wire:model.live="editingQuantities.{{ $produce['id'] }}.callback_quantity"
                                       wire:change="updateQuantity({{ $produce['id'] }}, 'callback_quantity')"
                                       class="w-full mt-1 px-3 py-2 text-sm border border-red-300 dark:border-red-600 rounded bg-red-50 dark:bg-red-900/20 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-red-500">
                            </div>
                            <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                <span class="text-zinc-600 dark:text-zinc-400">Net Available:</span>
                                <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($produce['net_available'], 2) }}</span>
                            </div>
                            <div>
                                <label class="text-xs text-zinc-600 dark:text-zinc-400 font-medium">
                                    Sent Out
                                    @if(!empty($produce['uom']))
                                        ({{ $produce['uom'] }})
                                    @endif
                                    :
                                </label>
                                <input type="number" step="0.01" min="0"
                                       wire:model.live="editingQuantities.{{ $produce['id'] }}.sent_out_quantity"
                                       wire:change="updateQuantity({{ $produce['id'] }}, 'sent_out_quantity')"
                                       class="w-full mt-1 px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="text-xs text-zinc-600 dark:text-zinc-400 font-medium">
                                    For Order
                                    @if(!empty($produce['uom']))
                                        ({{ $produce['uom'] }})
                                    @endif
                                    :
                                </label>
                                <input type="number" step="0.01" min="0"
                                       wire:model.live="editingQuantities.{{ $produce['id'] }}.order_quantity"
                                       wire:change="updateQuantity({{ $produce['id'] }}, 'order_quantity')"
                                       class="w-full mt-1 px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Closing & Variance Section -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Closing & Variance
                        </h4>
                        <div class="space-y-3 text-sm" @click.stop>
                            <div>
                                <label class="text-xs text-zinc-600 dark:text-zinc-400 font-medium">Closing (Physical Count):</label>
                                <input type="number" step="0.01" min="0"
                                       wire:model.live="editingQuantities.{{ $produce['id'] }}.closing_quantity"
                                       wire:change="updateQuantity({{ $produce['id'] }}, 'closing_quantity')"
                                       class="w-full mt-1 px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                <span class="text-zinc-600 dark:text-zinc-400">Expected:</span>
                                <span class="font-semibold text-purple-600 dark:text-purple-400">{{ number_format($produce['expected_closing'], 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Variance:</span>
                                <span class="font-bold {{ $produce['has_variance_issue'] ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    {{ number_format($produce['variance'], 2) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Variance %:</span>
                                <span class="font-bold {{ $produce['has_variance_issue'] ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    {{ number_format($produce['variance_percentage'], 1) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ingredient Analysis Section -->
                @if(!empty($produce['producability']['ingredient_analysis']))
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                    <div class="text-xs">
                        <div class="mb-3 font-medium text-zinc-700 dark:text-zinc-300 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Ingredient Analysis ({{ count($produce['producability']['ingredient_analysis']) }} ingredients)
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead class="bg-zinc-100 dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-300">Ingredient</th>
                                        <th class="px-3 py-2 text-center font-semibold text-zinc-700 dark:text-zinc-300">Per Batch</th>
                                        <th class="px-3 py-2 text-center font-semibold text-zinc-700 dark:text-zinc-300">Total Needed</th>
                                        <th class="px-3 py-2 text-center font-semibold text-zinc-700 dark:text-zinc-300">Dispatched</th>
                                        <th class="px-3 py-2 text-center font-semibold text-zinc-700 dark:text-zinc-300">Used</th>
                                        <th class="px-3 py-2 text-center font-semibold text-zinc-700 dark:text-zinc-300">Remaining</th>
                                        <th class="px-3 py-2 text-center font-semibold text-zinc-700 dark:text-zinc-300">Can Make</th>
                                        <th class="px-3 py-2 text-center font-semibold text-zinc-700 dark:text-zinc-300">Shortage</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach($produce['producability']['ingredient_analysis'] as $ingredient)
                                    <tr class="{{ $ingredient['is_limiting'] ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                                        <td class="px-3 py-2 font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $ingredient['item_name'] }}
                                            @if($ingredient['is_limiting'])
                                                <span class="ml-1 px-1.5 py-0.5 bg-red-500 text-white text-[10px] rounded-full">LIMITING</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center text-zinc-700 dark:text-zinc-300">
                                            {{ number_format($ingredient['quantity_per_batch'], 2) }} {{ $ingredient['uom'] }}
                                        </td>
                                        <td class="px-3 py-2 text-center text-zinc-700 dark:text-zinc-300">
                                            {{ number_format($ingredient['total_needed_for_request'], 2) }} {{ $ingredient['uom'] }}
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="text-green-600 dark:text-green-400 font-semibold">
                                                {{ number_format($ingredient['quantity_dispatched'], 2) }} {{ $ingredient['uom'] }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="text-blue-600 dark:text-blue-400">
                                                {{ number_format($ingredient['quantity_used'], 2) }} {{ $ingredient['uom'] }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="{{ $ingredient['quantity_remaining'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ number_format($ingredient['quantity_remaining'], 2) }} {{ $ingredient['uom'] }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-center font-semibold">
                                            <span class="{{ $ingredient['producable_batches'] == 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                                {{ number_format($ingredient['producable_batches']) }} batches
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            @if($ingredient['shortage'] > 0)
                                                <span class="text-red-600 dark:text-red-400">
                                                    -{{ number_format($ingredient['shortage'], 2) }} {{ $ingredient['uom'] }}
                                                </span>
                                            @else
                                                <span class="text-green-600 dark:text-green-400">✓</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Batch Management Section -->
                @if(!empty($produce['batches']))
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                    <div class="text-xs">
                        <div class="mb-3 font-medium text-blue-700 dark:text-blue-300 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            Batch Management - Allocate Production to Sales/Orders
                        </div>

                        <div class="mb-3 p-3 bg-blue-100 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="text-xs text-blue-800 dark:text-blue-200">
                                <strong>How it works:</strong> For each batch produced, specify how many units to send out to sales and how many to reserve for orders. The system tracks remaining inventory per batch.
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-xs border border-zinc-200 dark:border-zinc-700">
                                <thead class="bg-blue-100 dark:bg-blue-900/50">
                                     <tr>
                                         <th class="px-3 py-2 text-left font-semibold text-blue-900 dark:text-blue-100">Batch #</th>
                                         <th class="px-3 py-2 text-center font-semibold text-blue-900 dark:text-blue-100">
                                             Produced @if(!empty($produce['uom'])) ({{ $produce['uom'] }}) @endif
                                         </th>
                                         <th class="px-3 py-2 text-center font-semibold text-blue-900 dark:text-blue-100">
                                             Approved @if(!empty($produce['uom'])) ({{ $produce['uom'] }}) @endif
                                         </th>
                                         <th class="px-3 py-2 text-center font-semibold text-blue-900 dark:text-blue-100">
                                             Rejected @if(!empty($produce['uom'])) ({{ $produce['uom'] }}) @endif
                                         </th>
                                         <th class="px-3 py-2 text-center font-semibold text-green-900 dark:text-green-100 bg-green-50 dark:bg-green-900/20">
                                             Dispatch Allocations @if(!empty($produce['uom'])) ({{ $produce['uom'] }}) @endif
                                         </th>
                                         <th class="px-3 py-2 text-center font-semibold text-purple-900 dark:text-purple-100 bg-purple-50 dark:bg-purple-900/20">
                                             For Order @if(!empty($produce['uom'])) ({{ $produce['uom'] }}) @endif
                                         </th>
                                         <th class="px-3 py-2 text-center font-semibold text-blue-900 dark:text-blue-100">
                                             Remaining @if(!empty($produce['uom'])) ({{ $produce['uom'] }}) @endif
                                         </th>
                                         <th class="px-3 py-2 text-center font-semibold text-blue-900 dark:text-blue-100">Status</th>
                                         <th class="px-3 py-2 text-left font-semibold text-blue-900 dark:text-blue-100">Details</th>
                                     </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach($produce['batches'] as $batch)
                                    <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                        <!-- Batch Number -->
                                        <td class="px-3 py-2 font-medium text-blue-900 dark:text-blue-100">
                                            {{ $batch['batch_number'] }}
                                        </td>

                                        <!-- Produced -->
                                        <td class="px-3 py-2 text-center text-zinc-700 dark:text-zinc-300">
                                            {{ number_format($batch['quantity_produced'], 2) }}
                                        </td>

                                        <!-- Approved -->
                                        <td class="px-3 py-2 text-center text-green-700 dark:text-green-300 font-semibold">
                                            {{ number_format($batch['quantity_approved'], 2) }}
                                        </td>

                                        <!-- Rejected -->
                                        <td class="px-3 py-2 text-center {{ $batch['quantity_rejected'] > 0 ? 'text-red-700 dark:text-red-300 font-semibold' : 'text-zinc-500' }}">
                                            {{ number_format($batch['quantity_rejected'], 2) }}
                                        </td>

                                         <!-- Dispatch Allocations (MULTIPLE) -->
                                         <td class="px-3 py-2 bg-green-50 dark:bg-green-900/10">
                                             @php
                                                 $forOrderValue = (float) ($batchQuantities[$batch['id']]['quantity_for_order'] ?? $batch['quantity_for_order'] ?? 0);
                                                 $totalAllocated = 0;
                                                 if (isset($batchDispatches[$batch['id']])) {
                                                     foreach ($batchDispatches[$batch['id']] as $dispatchRow) {
                                                         $totalAllocated += (float) ($dispatchRow['quantity'] ?? 0);
                                                     }
                                                 }
                                                 $availableForDispatch = max(0, $batch['quantity_approved'] - $forOrderValue);
                                         @endphp
                                         <div class="space-y-1">
                                             @if(!empty($batch['allowed_sales_department_id']))
                                                 @php
                                                     $lockedDept = collect($salesDepartments)->firstWhere('id', $batch['allowed_sales_department_id']);
                                                     $lockedDeptName = $lockedDept['name']
                                                         ?? ($produce['sales_department_name'] ?? null)
                                                         ?? ('Sales Dept #' . $batch['allowed_sales_department_id']);
                                                 @endphp
                                                 <p class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-300">
                                                     Locked to Sales: {{ $lockedDeptName }}
                                                 </p>
                                             @endif
                                             @if(isset($batchDispatches[$batch['id']]) && count($batchDispatches[$batch['id']]) > 0)
                                                 @php
                                                     $runningAllocated = 0;
                                                 @endphp
                                                     @foreach($batchDispatches[$batch['id']] as $index => $dispatch)
                                                         @php
                                                             $currentQuantity = (float) ($dispatch['quantity'] ?? 0);
                                                             $remainingForThisDispatch = $availableForDispatch - ($runningAllocated - $currentQuantity);
                                                             $runningAllocated += $currentQuantity;
                                                             $dispatchDisabled = $availableForDispatch <= 0 || $remainingForThisDispatch <= 0;
                                                         @endphp
                                                         <div class="flex items-center gap-1 text-xs">
                                                             <select wire:model.live="batchDispatches.{{ $batch['id'] }}.{{ $index }}.sales_department_id"
                                                                     @disabled($availableForDispatch <= 0 || !empty($batch['allowed_sales_department_id']))
                                                                     class="flex-1 px-1 py-0.5 text-xs border border-green-300 dark:border-green-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                                                                 @if(!empty($batch['allowed_sales_department_id']))
                                                                     @php
                                                                         $allowedDept = collect($salesDepartments)->firstWhere('id', $batch['allowed_sales_department_id']);
                                                                         $allowedDeptName = $allowedDept['name']
                                                                             ?? ($produce['sales_department_name'] ?? null)
                                                                             ?? ('Sales Dept #' . $batch['allowed_sales_department_id']);
                                                                     @endphp
                                                                     <option value="{{ $batch['allowed_sales_department_id'] }}">{{ $allowedDeptName }}</option>
                                                                 @else
                                                                     @php
                                                                         $dispatchSalesDepartmentOptions = $batchSalesDepartmentOptions[$batch['id']] ?? [];
                                                                     @endphp
                                                                     @if(count($dispatchSalesDepartmentOptions) > 0)
                                                                         @foreach($dispatchSalesDepartmentOptions as $dept)
                                                                             <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                                                                         @endforeach
                                                                     @else
                                                                         <option value="">No eligible sales department</option>
                                                                     @endif
                                                                 @endif
                                                             </select>
                                                            <input type="number" step="0.01" min="0" max="{{ $remainingForThisDispatch }}"
                                                                   wire:model.live="batchDispatches.{{ $batch['id'] }}.{{ $index }}.quantity"
                                                                   @disabled($dispatchDisabled)
                                                                   class="w-16 px-1 py-0.5 text-center border border-green-300 dark:border-green-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 {{ $dispatchDisabled ? 'opacity-50 cursor-not-allowed' : '' }}">
                                                            @if(!empty($produce['uom']))
                                                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ $produce['uom'] }}</span>
                                                            @endif
                                                            <button type="button" wire:click="removeBatchDispatch({{ $batch['id'] }}, {{ $index }})"
                                                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                                 <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                                 </svg>
                                                             </button>
                                                         </div>
                                                     @endforeach
                                                 @endif
                                                 <button type="button" wire:click="addBatchDispatch({{ $batch['id'] }})"
                                                         @disabled($availableForDispatch <= 0 || (empty($batch['allowed_sales_department_id']) && count($batchSalesDepartmentOptions[$batch['id']] ?? []) === 0))
                                                         class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1 {{ ($availableForDispatch <= 0 || (empty($batch['allowed_sales_department_id']) && count($batchSalesDepartmentOptions[$batch['id']] ?? []) === 0)) ? 'opacity-50 cursor-not-allowed' : '' }}">
                                                     <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                     </svg>
                                                     @if(!empty($batch['allowed_sales_department_id']))
                                                         Add Dispatch to Locked Sales Dept
                                                     @else
                                                         Add Dispatch
                                                     @endif
                                                 </button>
                                                 @if(empty($batch['allowed_sales_department_id']) && count($batchSalesDepartmentOptions[$batch['id']] ?? []) === 0)
                                                     <p class="text-[10px] text-amber-700 dark:text-amber-300">
                                                         No eligible sales department for this product. Assign a sales department on the product first.
                                                     </p>
                                                 @endif
                                             </div>
                                         </td>

                                        <!-- For Order (EDITABLE) -->
                                        <td class="px-3 py-2 bg-purple-50 dark:bg-purple-900/10">
                                            @php
                                                $isSalesLockedBatch = !empty($batch['allowed_sales_department_id']);
                                                $maxForOrder = $isSalesLockedBatch ? 0 : max(0, $batch['quantity_approved'] - $totalAllocated);
                                            @endphp
                                            <input type="number" step="0.01" min="0" max="{{ $maxForOrder }}"
                                                   wire:model.live="batchQuantities.{{ $batch['id'] }}.quantity_for_order"
                                                   wire:change="updateBatchQuantity({{ $batch['id'] }}, 'quantity_for_order')"
                                                   @disabled($batch['quantity_approved'] <= 0 || $isSalesLockedBatch)
                                                   class="w-20 px-2 py-1 text-center border border-purple-300 dark:border-purple-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-purple-500">
                                            @if($isSalesLockedBatch)
                                                <p class="mt-1 text-[10px] text-purple-700 dark:text-purple-300">
                                                    Sales request flow: dispatch via Send Out only.
                                                </p>
                                            @endif
                                        </td>

                                        <!-- Remaining (AUTO) -->
                                        <td class="px-3 py-2 text-center font-bold {{ $batch['quantity_remaining'] > 0 ? 'text-blue-700 dark:text-blue-300' : 'text-zinc-500 dark:text-zinc-500' }}">
                                            {{ number_format($batch['quantity_remaining'], 2) }}
                                        </td>

                                        <!-- Dispatch Status -->
                                        <td class="px-3 py-2 text-center">
                                            @php
                                                $statusColors = [
                                                    'available' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                    'partial' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                    'fully_dispatched' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                                ];
                                            @endphp
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $statusColors[$batch['dispatch_status']] ?? '' }}">
                                                {{ ucfirst(str_replace('_', ' ', $batch['dispatch_status'])) }}
                                            </span>
                                        </td>

                                        <!-- Details -->
                                        <td class="px-3 py-2">
                                            <div class="space-y-1">
                                                <p class="text-zinc-700 dark:text-zinc-300">
                                                    <strong>Quality:</strong> {{ ucfirst($batch['quality_status']) }}
                                                </p>
                                                <p class="text-zinc-600 dark:text-zinc-400">
                                                    {{ $batch['production_time'] }}
                                                </p>
                                                <p class="text-zinc-500 dark:text-zinc-500">
                                                    By: {{ $batch['produced_by'] }}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach

                                    <!-- Totals Row -->
                                    <tr class="bg-blue-100 dark:bg-blue-900/30 font-bold">
                                        <td class="px-3 py-2 text-blue-900 dark:text-blue-100">TOTALS</td>
                                        <td class="px-3 py-2 text-center text-blue-900 dark:text-blue-100">
                                            {{ number_format(collect($produce['batches'])->sum('quantity_produced'), 2) }}
                                        </td>
                                        <td class="px-3 py-2 text-center text-green-700 dark:text-green-300">
                                            {{ number_format(collect($produce['batches'])->sum('quantity_approved'), 2) }}
                                        </td>
                                        <td class="px-3 py-2 text-center text-red-700 dark:text-red-300">
                                            {{ number_format(collect($produce['batches'])->sum('quantity_rejected'), 2) }}
                                        </td>
                                        <td class="px-3 py-2 text-center text-orange-700 dark:text-orange-300 bg-orange-100 dark:bg-orange-900/30">
                                            --
                                        </td>
                                        <td class="px-3 py-2 text-center text-green-800 dark:text-green-200 bg-green-100 dark:bg-green-900/30">
                                            {{ number_format(collect($produce['batches'])->sum('quantity_sent_out'), 2) }}
                                        </td>
                                        <td class="px-3 py-2 text-center text-purple-800 dark:text-purple-200 bg-purple-100 dark:bg-purple-900/30">
                                            {{ number_format(collect($produce['batches'])->sum('quantity_for_order'), 2) }}
                                        </td>
                                        <td class="px-3 py-2 text-center text-blue-800 dark:text-blue-200">
                                            {{ number_format(collect($produce['batches'])->sum('quantity_remaining'), 2) }}
                                        </td>
                                        <td colspan="2" class="px-3 py-2">
                                            <button wire:click="saveBatchQuantities({{ $produce['id'] }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="saveBatchQuantities({{ $produce['id'] }})"
                                                    @disabled(!empty($isSavingBatch[$produce['id']] ?? false))
                                                    class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                                💾 Save All Batches
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </td>
    </tr>
    @endforeach
</tbody>
                </table>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
             <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                 <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Total Produced</p>
                 <p class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">
                     {{ number_format(collect($dailyProduces)->where('produced_quantity', '>', 0)->sum('produced_quantity'), 2) }}
                 </p>
             </div>
             <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                 <p class="text-xs text-red-600 dark:text-red-400 font-medium">Total Callback (Damaged)</p>
                 <p class="text-2xl font-bold text-red-700 dark:text-red-300 mt-1">
                     {{ number_format(collect($dailyProduces)->where('produced_quantity', '>', 0)->sum('callback_quantity'), 2) }}
                 </p>
             </div>
             <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                 <p class="text-xs text-green-600 dark:text-green-400 font-medium">Total Net Available</p>
                 <p class="text-2xl font-bold text-green-700 dark:text-green-300 mt-1">
                     {{ number_format(collect($dailyProduces)->where('produced_quantity', '>', 0)->sum('net_available'), 2) }}
                 </p>
             </div>
             <div class="bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 rounded-lg p-4">
                 <p class="text-xs text-teal-600 dark:text-teal-400 font-medium">Total Sent Out</p>
                 <p class="text-2xl font-bold text-teal-700 dark:text-teal-300 mt-1">
                     {{ number_format(collect($dailyProduces)->where('produced_quantity', '>', 0)->sum('sent_out_quantity'), 2) }}
                 </p>
             </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Total Closing</p>
                <p class="text-2xl font-bold text-purple-700 dark:text-purple-300 mt-1">
                    {{ number_format(collect($dailyProduces)->where('produced_quantity', '>', 0)->sum('closing_quantity'), 2) }}
                </p>
            </div>
        </div>
    @endif

    <!-- Help Modal -->
    @if($showHelpModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="help-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="toggleHelpModal"></div>

            <!-- Spacing element for proper centering -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div class="relative inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full z-50">
                <!-- Header -->
                <div class="bg-blue-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-xl font-bold text-white">Daily Production Tracking - User Guide</h3>
                        </div>
                        <button wire:click="toggleHelpModal" class="text-white hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="bg-white dark:bg-zinc-800 px-6 py-6 max-h-[70vh] overflow-y-auto">

                    <!-- Overview Section -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-3">📊 Overview</h4>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">
                            This page helps you track daily production activities for your shift. It shows what you can produce based on available ingredients,
                            tracks actual production, manages damaged items, and calculates closing inventory.
                        </p>
                    </div>

                    <!-- Workflow Section -->
                    <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <h4 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">🔄 Production Workflow</h4>
                        <ol class="space-y-2 text-sm text-blue-800 dark:text-blue-200 list-decimal list-inside">
                            <li><strong>Create Production Request</strong> → System calculates ingredients needed</li>
                            <li><strong>Inventory Approves & Dispatches</strong> → Ingredients sent to production</li>
                            <li><strong>View This Page</strong> → See what you can produce with dispatched ingredients</li>
                            <li><strong>Record Batches</strong> → Click "Record Batch" to log production</li>
                            <li><strong>Track Distribution</strong> → Enter Sent Out, Orders, Callbacks, Closing</li>
                            <li><strong>Complete</strong> → Mark production as complete when done</li>
                        </ol>
                    </div>

                    <!-- Column Definitions -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-3">📋 Column Definitions</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <!-- Opening -->
                            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
                                <h5 class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm mb-2">🏁 Opening</h5>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                    <strong>What it is:</strong> Stock quantity at the start of this shift (from previous shift's closing)
                                </p>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">
                                    <strong>Status:</strong> <span class="text-zinc-500">Auto-calculated</span>
                                </p>
                            </div>

                            <!-- Requested -->
                            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
                                <h5 class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm mb-2">📝 Requested</h5>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                    <strong>What it is:</strong> How many units you planned to produce (from Production Request)
                                </p>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">
                                    <strong>Status:</strong> <span class="text-zinc-500">From request</span>
                                </p>
                            </div>

                            <!-- Produced -->
                            <div class="border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                                <h5 class="font-semibold text-blue-900 dark:text-blue-100 text-sm mb-2">🔄 Produced (Auto)</h5>
                                <p class="text-xs text-blue-700 dark:text-blue-300">
                                    <strong>What it is:</strong> Total quantity produced (automatically summed from all batches you record)
                                </p>
                                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                    <strong>Status:</strong> <span class="font-semibold">READ-ONLY - Auto-calculated</span>
                                </p>
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                    ℹ️ Updates automatically when you click "Record Batch"
                                </p>
                            </div>

                            <!-- Callback -->
                            <div class="border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                                <h5 class="font-semibold text-red-900 dark:text-red-100 text-sm mb-2">⚠ Callback (Damaged)</h5>
                                <p class="text-xs text-red-700 dark:text-red-300">
                                    <strong>What it is:</strong> Damaged, rejected, or unusable items
                                </p>
                                <p class="text-xs text-red-700 dark:text-red-300 mt-1">
                                    <strong>Status:</strong> <span class="font-semibold">EDITABLE</span>
                                </p>
                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                                    ℹ️ Enter damaged quantity here - reduces net available
                                </p>
                            </div>

                            <!-- Net Available -->
                            <div class="border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                                <h5 class="font-semibold text-green-900 dark:text-green-100 text-sm mb-2">✓ Net Available (Auto)</h5>
                                <p class="text-xs text-green-700 dark:text-green-300">
                                    <strong>What it is:</strong> Usable quantity after removing damaged items
                                </p>
                                <p class="text-xs text-green-700 dark:text-green-300 mt-1">
                                    <strong>Formula:</strong> <code>Produced - Callback</code>
                                </p>
                                <p class="text-xs text-green-700 dark:text-green-300 mt-1">
                                    <strong>Status:</strong> <span class="font-semibold">Auto-calculated</span>
                                </p>
                            </div>

                            <!-- Sent Out -->
                            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
                                <h5 class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm mb-2">📤 Sent Out</h5>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                    <strong>What it is:</strong> Quantity sent to sales departments or customers
                                </p>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">
                                    <strong>Status:</strong> <span class="font-semibold">EDITABLE</span>
                                </p>
                            </div>

                            <!-- Order -->
                            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
                                <h5 class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm mb-2">📋 Order</h5>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                    <strong>What it is:</strong> Quantity ordered but not yet sent out
                                </p>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">
                                    <strong>Status:</strong> <span class="font-semibold">EDITABLE</span>
                                </p>
                            </div>

                            <!-- Closing -->
                            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
                                <h5 class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm mb-2">🔒 Closing</h5>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                    <strong>What it is:</strong> Actual physical count at end of shift
                                </p>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">
                                    <strong>Status:</strong> <span class="font-semibold">EDITABLE</span>
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">
                                    ℹ️ This becomes next shift's opening
                                </p>
                            </div>

                            <!-- Expected Closing -->
                            <div class="border border-purple-200 dark:border-purple-700 bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3">
                                <h5 class="font-semibold text-purple-900 dark:text-purple-100 text-sm mb-2">🎯 Expected Closing (Auto)</h5>
                                <p class="text-xs text-purple-700 dark:text-purple-300">
                                    <strong>What it is:</strong> What closing SHOULD be based on calculations
                                </p>
                                <p class="text-xs text-purple-700 dark:text-purple-300 mt-1">
                                    <strong>Formula:</strong> <code>Opening + Net Available - Sent Out - Order</code>
                                </p>
                                <p class="text-xs text-purple-700 dark:text-purple-300 mt-1">
                                    <strong>Status:</strong> <span class="font-semibold">Auto-calculated</span>
                                </p>
                            </div>

                            <!-- Variance -->
                            <div class="border border-orange-200 dark:border-orange-700 bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3">
                                <h5 class="font-semibold text-orange-900 dark:text-orange-100 text-sm mb-2">📊 Variance & %</h5>
                                <p class="text-xs text-orange-700 dark:text-orange-300">
                                    <strong>What it is:</strong> Difference between actual closing and expected closing
                                </p>
                                <p class="text-xs text-orange-700 dark:text-orange-300 mt-1">
                                    <strong>Formula:</strong> <code>Closing - Expected Closing</code>
                                </p>
                                <p class="text-xs text-orange-700 dark:text-orange-300 mt-1">
                                    <strong>Status:</strong> <span class="font-semibold">Auto-calculated</span>
                                </p>
                                <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">
                                    ⚠️ High variance (>5) indicates possible issues
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Key Features -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-3">🎯 Key Features</h4>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center text-xs font-bold">1</span>
                                <div>
                                    <p class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">Producable Quantity Analysis</p>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                        Click the expandable row to see detailed ingredient analysis. Shows which ingredient is limiting your production capacity.
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center text-xs font-bold">2</span>
                                <div>
                                    <p class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">Smart Buttons</p>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                        "Record Batch" button automatically disables if you don't have enough ingredients. Tooltip shows what's missing.
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center text-xs font-bold">3</span>
                                <div>
                                    <p class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">Real-time Summary Cards</p>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                        Bottom cards show totals across all products: Total Produced, Damaged, Net Available, Sent Out, and Closing.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                        <h4 class="text-lg font-semibold text-yellow-900 dark:text-yellow-100 mb-3">💡 Tips</h4>
                        <ul class="space-y-2 text-sm text-yellow-800 dark:text-yellow-200 list-disc list-inside">
                            <li>Always check "Producable Quantity" before starting - it shows if you have enough ingredients</li>
                            <li>Record batches as you produce them, don't wait until end of shift</li>
                            <li>Enter callback/damaged items immediately to get accurate net available</li>
                            <li>At end of shift, do physical count and enter as "Closing"</li>
                            <li>If variance is high, investigate - it could indicate theft, waste, or counting errors</li>
                            <li>Click "Save All" button after making changes to multiple rows</li>
                        </ul>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-zinc-50 dark:bg-zinc-900 px-6 py-4 flex justify-end">
                    <button wire:click="toggleHelpModal"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                        Got it!
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Record Production Batch Modal -->
    @if($showRecordModal && $recordingProduce)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="record-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeRecordModal"></div>

            <!-- Spacing element for proper centering -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div class="relative inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full z-50">
                <!-- Header -->
                <div class="bg-blue-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-white">Record Production Batch</h3>
                            <p class="text-sm text-blue-100 mt-1">{{ $recordingProduce->recipe->product_name ?? 'N/A' }}</p>
                        </div>
                        <button wire:click="closeRecordModal" class="text-white hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="bg-white dark:bg-zinc-800 px-6 py-6 space-y-6">

                    <!-- Production Info -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-blue-600 dark:text-blue-400 font-medium">UOM</p>
                                <p class="text-blue-900 dark:text-blue-100 font-semibold">{{ $recordingProduce->recipe->uom ?? 'pcs' }}</p>
                            </div>
                            <div>
                                <p class="text-blue-600 dark:text-blue-400 font-medium">Already Produced</p>
                                <p class="text-blue-900 dark:text-blue-100 font-semibold">{{ number_format($recordingProduce->produced_quantity, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-blue-600 dark:text-blue-400 font-medium">Requested</p>
                                <p class="text-blue-900 dark:text-blue-100 font-semibold">{{ number_format($recordingProduce->requested_quantity, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-blue-600 dark:text-blue-400 font-medium">Batches</p>
                                <p class="text-blue-900 dark:text-blue-100 font-semibold">{{ $recordingProduce->productionRecords->count() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Number of Batches Produced -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-300 dark:border-blue-700 rounded-lg p-4">
                        <label class="block text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                            Number of Batches Produced <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" wire:model.live="batchesProduced"
                               placeholder="e.g., 1, 2, 3..."
                               class="w-full px-4 py-2 border border-blue-400 dark:border-blue-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 text-lg font-semibold">
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-2 font-medium">
                            📊 Recipe yield: {{ number_format($recordingProduce->recipe->yield_quantity ?? 0, 2) }} {{ $recordingProduce->recipe->uom ?? 'units' }} per batch
                        </p>
                        @error('batchesProduced')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror

                        <!-- Calculated Total Quantity -->
                        @if($batchQuantityProduced > 0)
                        <div class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700 rounded-lg">
                            <p class="text-sm text-green-900 dark:text-green-100 font-semibold">
                                📦 Total Quantity Produced:
                            </p>
                            <p class="text-2xl font-bold text-green-700 dark:text-green-300 mt-1">
                                {{ number_format($batchQuantityProduced, 2) }} {{ $recordingProduce->recipe->uom ?? 'units' }}
                            </p>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                                ({{ $batchesProduced }} batch{{ $batchesProduced > 1 ? 'es' : '' }} × {{ number_format($recordingProduce->recipe->yield_quantity ?? 0, 2) }} {{ $recordingProduce->recipe->uom ?? 'units' }})
                            </p>
                        </div>
                        @endif
                    </div>

                    <!-- Quality Control Section -->
                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Quality Control</h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Approved Quantity -->
                            <div>
                                <label class="block text-sm font-medium text-green-700 dark:text-green-300 mb-2">
                                    Quantity Approved <span class="text-red-500">*</span>
                                </label>
                                <input type="number" step="0.01" min="0" wire:model.live="batchQuantityApproved"
                                       class="w-full px-4 py-2 border border-green-300 dark:border-green-600 rounded-lg bg-green-50 dark:bg-green-900/20 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-green-500">
                                @error('batchQuantityApproved')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Rejected Quantity -->
                            <div>
                                <label class="block text-sm font-medium text-red-700 dark:text-red-300 mb-2">
                                    Quantity Rejected <span class="text-red-500">*</span>
                                </label>
                                <input type="number" step="0.01" min="0" wire:model.live="batchQuantityRejected"
                                       class="w-full px-4 py-2 border border-red-300 dark:border-red-600 rounded-lg bg-red-50 dark:bg-red-900/20 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-red-500">
                                @error('batchQuantityRejected')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Validation Helper -->
                        @if($batchQuantityProduced > 0)
                        <div class="mt-3 p-3 {{ abs(($batchQuantityApproved + $batchQuantityRejected) - $batchQuantityProduced) < 0.01 ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800' }} border rounded-lg">
                            <p class="text-sm">
                                <span class="font-semibold">Total: </span>
                                <span class="{{ abs(($batchQuantityApproved + $batchQuantityRejected) - $batchQuantityProduced) < 0.01 ? 'text-green-700 dark:text-green-300' : 'text-orange-700 dark:text-orange-300' }}">
                                    {{ number_format($batchQuantityApproved + $batchQuantityRejected, 2) }}
                                </span>
                                / {{ number_format($batchQuantityProduced, 2) }}
                                @if(abs(($batchQuantityApproved + $batchQuantityRejected) - $batchQuantityProduced) < 0.01)
                                    <span class="ml-2 text-green-600 dark:text-green-400">✓ Balanced</span>
                                @else
                                    <span class="ml-2 text-orange-600 dark:text-orange-400">⚠ Must equal produced quantity</span>
                                @endif
                            </p>
                        </div>
                        @endif
                    </div>

                    <!-- Quality Status -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Quality Status <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="batchQualityStatus"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                            <option value="excellent">Excellent</option>
                            <option value="good">Good</option>
                            <option value="acceptable">Acceptable</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        @error('batchQualityStatus')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Rejection Reason (if rejected quantity > 0) -->
                    @if($batchQuantityRejected > 0)
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                        <label class="block text-sm font-medium text-red-700 dark:text-red-300 mb-2">
                            Rejection Reason <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model="batchRejectionReason" rows="3"
                                  class="w-full px-4 py-2 border border-red-300 dark:border-red-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-red-500"
                                  placeholder="Explain why items were rejected..."></textarea>
                        @error('batchRejectionReason')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif

                    <!-- Notes (Optional) -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Notes (Optional)
                        </label>
                        <textarea wire:model="batchNotes" rows="2"
                                  class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                                  placeholder="Any additional notes about this batch..."></textarea>
                    </div>

                </div>

                <!-- Footer -->
                <div class="bg-zinc-50 dark:bg-zinc-900 px-6 py-4 flex justify-end gap-3">
                    <button wire:click="closeRecordModal"
                            class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-900 dark:text-zinc-100 rounded-lg font-medium">
                        Cancel
                    </button>
                    <button wire:click="recordBatch"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Record Batch
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
