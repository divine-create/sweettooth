<div class="p-3 space-y-3">

    <div>
        <x-breadcrumb
        title="Raw Material Tracking"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Raw Material Tracking']
        ]"
        :compact="false"
        :with-icons="true"/>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <!-- View Mode Toggle -->
        <div class="mb-4 flex gap-2">
            <button wire:click="$set('viewMode', 'utilization')"
                    class="px-4 py-2 rounded-lg font-medium transition-colors {{ $viewMode === 'utilization' ? 'bg-blue-600 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 hover:bg-zinc-300 dark:hover:bg-zinc-600' }}">
                Material Utilization
            </button>
            <button wire:click="$set('viewMode', 'dispatches')"
                    class="px-4 py-2 rounded-lg font-medium transition-colors {{ $viewMode === 'dispatches' ? 'bg-blue-600 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 hover:bg-zinc-300 dark:hover:bg-zinc-600' }}">
                Dispatched Items
            </button>
        </div>

        <!-- Filter Mode Toggle -->
        <div class="mb-4 flex gap-2">
            <button wire:click="$set('filterMode', 'shift')"
                    class="px-4 py-2 rounded-lg font-medium transition-colors {{ $filterMode === 'shift' ? 'bg-green-600 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 hover:bg-zinc-300 dark:hover:bg-zinc-600' }}">
                Filter by Shift
            </button>
            <button wire:click="$set('filterMode', 'date_range')"
                    class="px-4 py-2 rounded-lg font-medium transition-colors {{ $filterMode === 'date_range' ? 'bg-green-600 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 hover:bg-zinc-300 dark:hover:bg-zinc-600' }}">
                Filter by Date Range
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Shift Filter (shown when filterMode is 'shift') -->
            @if($filterMode === 'shift')
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Shift</label>
                <select wire:model.live="selectedShiftId"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
                    <option value="">Select Shift</option>
                    @foreach($availableShifts as $shift)
                        <option value="{{ $shift->id }}">
                            {{ ucfirst($shift->shift_type) }} - {{ $shift->shift_date->format('M d, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Date Range Filters (shown when filterMode is 'date_range') -->
            @if($filterMode === 'date_range')
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Start Date</label>
                <input type="date" wire:model.live="filterStartDate"
                       class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">End Date</label>
                <input type="date" wire:model.live="filterEndDate"
                       class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Recipe</label>
                <select wire:model.live="filterRecipe"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
                    <option value="">All Recipes</option>
                    @foreach($recipes as $recipe)
                        <option value="{{ $recipe->id }}">{{ $recipe->product_name }}</option>
                    @endforeach
                </select>
            </div>

            @if($viewMode === 'utilization')
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Variance Type</label>
                <select wire:model.live="filterVarianceType"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
                    <option value="">All Types</option>
                    <option value="within_tolerance">Within Tolerance</option>
                    <option value="over_used">Over Used</option>
                    <option value="under_used">Under Used</option>
                </select>
            </div>
            @endif

            <div class="flex items-end">
                <button wire:click="resetFilters"
                        class="w-full px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-900 dark:text-zinc-100 rounded-lg font-medium">
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    @if(($filterMode === 'shift' && $selectedShiftId) || ($filterMode === 'date_range' && $filterStartDate && $filterEndDate))
        <!-- Summary Cards -->
        @if($viewMode === 'utilization')
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Total Items</p>
                <p class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">{{ $summary['total_items'] }}</p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-xs text-red-600 dark:text-red-400 font-medium">Over Used</p>
                <p class="text-2xl font-bold text-red-700 dark:text-red-300 mt-1">{{ $summary['over_used'] }}</p>
            </div>
            <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                <p class="text-xs text-orange-600 dark:text-orange-400 font-medium">Under Used</p>
                <p class="text-2xl font-bold text-orange-700 dark:text-orange-300 mt-1">{{ $summary['under_used'] }}</p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Cost Impact</p>
                <p class="text-2xl font-bold text-purple-700 dark:text-purple-300 mt-1">
                    {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['total_cost_impact'] ?? 0) }}
                </p>
            </div>
            <div class="bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 rounded-lg p-4">
                <p class="text-xs text-teal-600 dark:text-teal-400 font-medium">Efficiency</p>
                <p class="text-2xl font-bold text-teal-700 dark:text-teal-300 mt-1">
                    {{ number_format($summary['efficiency_percentage'], 1) }}%
                </p>
            </div>
        </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Items Dispatched</p>
                <p class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">{{ $summary['total_items'] }}</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <p class="text-xs text-green-600 dark:text-green-400 font-medium">Total Quantity</p>
                <p class="text-2xl font-bold text-green-700 dark:text-green-300 mt-1">{{ number_format($summary['total_dispatched'], 2) }}</p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Total Value</p>
                <p class="text-2xl font-bold text-purple-700 dark:text-purple-300 mt-1">
                    {{ number_format($summary['total_value'], 2) }}
                </p>
            </div>
            <div class="bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 rounded-lg p-4">
                <p class="text-xs text-teal-600 dark:text-teal-400 font-medium">Products</p>
                <p class="text-2xl font-bold text-teal-700 dark:text-teal-300 mt-1">{{ $summary['unique_products'] }}</p>
            </div>
        </div>
        @endif

        <!-- Data Table -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 mt-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                        @if($viewMode === 'utilization')
                        <tr>
                            @if($filterMode === 'date_range')
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Shift Date</th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Recipe</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Ingredient</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Units Produced</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Required</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Used</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Variance</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Type</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Cost Impact</th>
                        </tr>
                        @else
                        <tr>
                            @if($filterMode === 'date_range')
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Shift Date</th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Product</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Ingredient</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Requested</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Approved</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Dispatched</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Unit Cost</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Total Value</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Request #</th>
                        </tr>
                        @endif
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @if($viewMode === 'utilization')
                        @forelse($data as $utilization)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            @if($filterMode === 'date_range')
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $utilization->shift->shift_date->format('M d, Y') }}
                                <span class="block text-xs text-zinc-500">{{ ucfirst($utilization->shift->shift_type) }}</span>
                            </td>
                            @endif
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $utilization->recipe->product_name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $utilization->item->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                {{ number_format($utilization->units_produced, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                {{ number_format($utilization->quantity_required, 4) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                {{ number_format($utilization->quantity_used, 4) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-semibold {{ $utilization->variance > 0 ? 'text-red-600 dark:text-red-400' : ($utilization->variance < 0 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400') }}">
                                {{ number_format($utilization->variance, 4) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $utilization->variance_type === 'within_tolerance' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                    {{ $utilization->variance_type === 'over_used' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                    {{ $utilization->variance_type === 'under_used' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : '' }}">
                                    {{ ucfirst(str_replace('_', ' ', $utilization->variance_type)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-semibold {{ $utilization->cost_impact > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($utilization->cost_impact ?? 0) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $filterMode === 'date_range' ? '9' : '8' }}" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No raw material utilization records found{{ $filterMode === 'shift' ? ' for this shift' : ' for this date range' }}.
                            </td>
                        </tr>
                        @endforelse
                        @else
                        @forelse($data as $dispatch)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            @if($filterMode === 'date_range')
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                @if($dispatch->itemRequest && $dispatch->itemRequest->productionRequests->first())
                                {{ $dispatch->itemRequest->productionRequests->first()->shift->shift_date->format('M d, Y') }}
                                <span class="block text-xs text-zinc-500">{{ ucfirst($dispatch->itemRequest->productionRequests->first()->shift->shift_type) }}</span>
                                @else
                                N/A
                                @endif
                            </td>
                            @endif
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                @if($dispatch->itemRequest && $dispatch->itemRequest->productionRequests->count() > 0)
                                @foreach($dispatch->itemRequest->productionRequests as $prodRequest)
                                <div>{{ $prodRequest->recipe->product_name ?? 'N/A' }}</div>
                                @endforeach
                                @else
                                N/A
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $dispatch->item->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                {{ number_format($dispatch->quantity_requested, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                {{ number_format($dispatch->quantity_approved, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-semibold text-green-600 dark:text-green-400">
                                {{ number_format($dispatch->quantity_dispatched, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($dispatch->item->cost_per_unit ?? 0) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-semibold text-blue-600 dark:text-blue-400">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency(($dispatch->quantity_dispatched * ($dispatch->item->cost_per_unit ?? 0))) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $dispatch->itemRequest->request_number ?? 'N/A' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $filterMode === 'date_range' ? '9' : '8' }}" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No dispatched items found{{ $filterMode === 'shift' ? ' for this shift' : ' for this date range' }}.
                            </td>
                        </tr>
                        @endforelse
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($data->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $data->links() }}
            </div>
            @endif
        </div>

    @else
        <!-- No Filter Applied -->
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-8">
            <div class="text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-semibold text-yellow-900 dark:text-yellow-100 mb-2">No Filter Applied</h3>
                <p class="text-yellow-700 dark:text-yellow-300">
                    @if($filterMode === 'shift')
                        Please select a shift from the dropdown above to view raw material utilization.
                    @else
                        Please select a date range to view raw material utilization for that period.
                    @endif
                </p>
            </div>
        </div>
    @endif

    </div>
</div>
