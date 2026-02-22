<div class="p-3 space-y-3">
    <x-breadcrumb title="Stock Takes" :links="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Inventory'], ['label' => 'Stock Takes']]" />

    @if (session()->has('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-3 py-2 rounded text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-3 py-2 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-sm font-medium text-gray-700">Filters</h3>
            <button wire:click="resetFilters" class="text-xs text-blue-600 hover:text-blue-800">
                Reset Filters
            </button>
        </div>
        <div class="p-3">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" wire:model.live="search" placeholder="Stock take # or conductor..."
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Branch</label>
                    <select wire:model.live="filterBranch"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                    <select wire:model.live="filterType"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Types</option>
                        <option value="full">Full Count</option>
                        <option value="partial">Partial Count</option>
                        <option value="cycle">Cycle Count</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select wire:model.live="filterStatus"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="verified">Verified</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Takes Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-3 py-2 border-b border-gray-200">
            <h3 class="text-sm font-medium text-gray-700">Stock Takes List</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stock Take #</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Conducted By</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Variances</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($stockTakes as $stockTake)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-xs font-medium text-gray-900">{{ $stockTake->stock_take_number }}</td>
                            <td class="px-3 py-2 text-xs text-gray-600">{{ $stockTake->branch->name }}</td>
                            <td class="px-3 py-2 text-xs text-gray-600">{{ $stockTake->stock_take_date->format('Y-m-d') }}</td>
                            <td class="px-3 py-2 text-xs">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $stockTake->type === 'full' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $stockTake->type === 'partial' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $stockTake->type === 'cycle' ? 'bg-green-100 text-green-800' : '' }}">
                                    {{ ucfirst($stockTake->type) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-600">{{ $stockTake->conductor->name }}</td>
                            <td class="px-3 py-2 text-xs text-gray-600">{{ $stockTake->stockTakeDetails->count() }}</td>
                            <td class="px-3 py-2 text-xs">
                                @if ($stockTake->hasVariances())
                                    <span class="font-medium text-orange-600">
                                        {{ $stockTake->getTotalVariances() }}
                                    </span>
                                @else
                                    <span class="text-gray-500">None</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $stockTake->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $stockTake->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $stockTake->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $stockTake->status === 'verified' ? 'bg-green-100 text-green-800' : '' }}">
                                    {{ ucfirst(str_replace('_', ' ', $stockTake->status)) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs">
                                @can('manage-stock-takes')
                                    @if ($stockTake->status === 'completed')
                                        <button wire:click="openVerificationModal({{ $stockTake->id }})"
                                            class="text-green-600 hover:text-green-800">
                                            Verify
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-4 text-center text-sm text-gray-500">
                                No stock takes found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-3 py-2 border-t border-gray-200">
            {{ $stockTakes->links() }}
        </div>
    </div>

    <!-- Verification Modal -->
    @if ($showVerificationModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-10 mx-auto p-4 border w-full max-w-md shadow-lg rounded-lg bg-white">
                <div class="px-3 py-2 border-b border-gray-200">
                    <h3 class="text-base font-medium text-gray-900">Verify Stock Take</h3>
                </div>
                <div class="p-3">
                    <form wire:submit.prevent="verifyStockTake" class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Verification Notes</label>
                            <textarea wire:model="verificationNotes" rows="3"
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Optional notes..."></textarea>
                        </div>

                        <div class="flex justify-end space-x-2 pt-2">
                            <button type="button" wire:click="closeModal"
                                class="px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">
                                Verify Stock Take
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
