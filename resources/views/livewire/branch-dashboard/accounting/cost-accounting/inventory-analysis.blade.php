<div class="p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Cost Accounting - Inventory Analysis</h1>
            <p class="text-gray-500 mt-1">Track current inventory levels, values, and average costs across branches.</p>
        </div>

        <div class="flex flex-wrap gap-4 mt-4 sm:mt-0">
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Item</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search items..." class="w-full sm:w-64 px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit / Branch</label>
                <select wire:model.live="selectedBranch" class="w-full sm:w-48 px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Units (Global)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
            <p class="text-sm font-medium text-gray-600">Total Inventory Value</p>
            <p class="mt-2 text-3xl font-bold text-gray-800">
                ₦{{ number_format($totalInventoryValue, 2) }}
            </p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
            <p class="text-sm font-medium text-gray-600">Total Items in Stock</p>
            <p class="mt-2 text-3xl font-bold text-gray-800">
                {{ number_format($totalItems) }}
            </p>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-800">Current Inventory Value</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-sm text-gray-600">
                        <th class="px-6 py-3 font-medium">Item Name</th>
                        <th class="px-6 py-3 font-medium">Branch</th>
                        <th class="px-6 py-3 font-medium text-right">Available Qty</th>
                        <th class="px-6 py-3 font-medium text-right">Avg Cost</th>
                        <th class="px-6 py-3 font-medium text-right">Total Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($stocks as $stock)
                        <tr class="hover:bg-gray-50 text-sm">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ optional($stock->item)->name ?? 'Unknown Item' }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ optional($stock->branch)->name ?? 'Unknown Branch' }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($stock->quantity_available) }}</td>
                            <td class="px-6 py-4 text-right">₦{{ number_format($stock->average_cost, 2) }}</td>
                            <td class="px-6 py-4 text-right font-medium">₦{{ number_format($stock->quantity_available * $stock->average_cost, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">No inventory items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($stocks->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $stocks->links() }}
            </div>
        @endif
    </div>
</div>
