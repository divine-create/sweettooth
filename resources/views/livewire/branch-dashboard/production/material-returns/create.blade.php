<div class="p-3 space-y-3">
    <x-breadcrumb :title="__('New Material Return')" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Production'],
        ['label' => $deptSlug ? ucfirst(str_replace('-', ' ', $deptSlug)) : 'Material Returns'],
        ['label' => 'New Return'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">{{ $deptSlug ? ucfirst(str_replace('-', ' ', $deptSlug)) . ' - ' : '' }} Create Material Return</h2>
                <p class="text-sm opacity-90 mt-1">
                    Select a material dispatch below to report issues or return excess stock to inventory.
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ branch_route('branch-dashboard.production.material-returns.history', ['deptSlug' => $deptSlug]) }}"
                    class="px-4 py-2 bg-white text-blue-600 rounded-lg font-medium hover:bg-purple-50 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Return History
                </a>
            </div>
        </div>
    </div>

    <!-- Active Shift Selector -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div class="max-w-xs">
            <x-select.styled wire:model.live="selectedShiftId" label="Active Production Shift" 
                placeholder="Select a shift"
                :options="$availableShifts" 
                select="label:shift_number|value:id" />
            @error('selectedShiftId')
                <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>
            @enderror
            <p class="text-xs text-zinc-500 mt-1 italic">
                Only showing shifts from the last 7 days.
            </p>
        </div>
    </div>

    <!-- Dispatches Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/20">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                        Current Production Store Shelf
                    </h3>
                    <p class="text-xs text-zinc-500">Active Department: <span class="font-bold">{{ $deptSlug }}</span></p>
                </div>
                <div class="w-full md:w-64">
                    <x-input icon="magnifying-glass" wire:model.live.debounce.300ms="search" placeholder="Search item or SKU..." />
                </div>
            </div>
        </div>

        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Material</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider text-center">Available on Shelf</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($dispatches as $row)
                    <tr wire:key="stock-{{ $row->id }}">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row->material_name }}</div>
                            <div class="text-xs text-zinc-500">{{ $row->material_sku }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-zinc-900 dark:text-zinc-100 font-bold">
                            {{ number_format($row->net_available, 2) }} {{ $row->material_uom }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <button wire:click="openReturnModal('{{ $row->id }}')" 
                                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                                Return Item
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-zinc-500 italic text-sm">
                            No items found on the {{ $deptSlug }} shelf. Try a different search or department.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($dispatches->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $dispatches->links() }}
            </div>
        @endif
    </div>

    <!-- Manual Return Modal (Bypassing x-modal to rule out library issues) -->
    @if($showReturnModal)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl w-full max-w-md overflow-hidden border dark:border-zinc-700">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Return Material to Inventory</h3>
                <button wire:click="$set('showReturnModal', false)" class="text-zinc-500 hover:text-zinc-700">&times;</button>
            </div>
            
            <div class="p-6">
                @if($this->selectedDispatch)
                    <div class="mb-6 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                        <div class="text-xs text-blue-600 dark:text-blue-400 uppercase font-bold mb-1">Selected Material</div>
                        <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $this->selectedDispatch->material_name }}
                        </div>
                        <div class="text-xs text-zinc-500">
                            Max available to return: {{ number_format($returnQuantity, 2) }} {{ $this->selectedDispatch->material_uom }}
                        </div>
                    </div>

                    <form wire:submit.prevent="submitReturn" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Quantity to Return</label>
                            <input type="number" wire:model="returnQuantity" step="0.01" min="0.01" 
                                class="w-full rounded border p-2 dark:bg-zinc-700 dark:border-zinc-600 dark:text-white"
                                placeholder="0.00" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Reason for Return</label>
                            <select wire:model="returnReason" class="w-full rounded border p-2 dark:bg-zinc-700 dark:border-zinc-600 dark:text-white">
                                <option value="">Select a reason</option>
                                @foreach($reasonOptions as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Details / Observation</label>
                            <textarea wire:model="returnNotes" rows="3"
                                class="w-full rounded border p-2 dark:bg-zinc-700 dark:border-zinc-600 dark:text-white"
                                placeholder="Describe the issue in detail..."></textarea>
                            <p class="text-[10px] text-zinc-500 mt-1">Minimum 5 characters required</p>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <button type="button" wire:click="$set('showReturnModal', false)" 
                                class="px-4 py-2 text-sm text-zinc-600 hover:text-zinc-800">Cancel</button>
                            <button type="submit" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitReturn">Submit Return</span>
                                <span wire:loading wire:target="submitReturn">Submitting...</span>
                            </button>
                        </div>
                    </form>
                @else
                    <div class="text-center py-8">
                        <p class="text-zinc-500">Loading material details...</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
