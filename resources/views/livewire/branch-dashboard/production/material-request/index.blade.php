<div class="p-3 space-y-3">

    <x-breadcrumb
        title="Material Request"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Material Request']
        ]"
        :compact="false"
        :with-icons="true"/>

    <div class="flex justify-end mb-2">
        <button type="button" wire:click="openCreateModal"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            New Material Request
        </button>
    </div>

    <!-- Filter -->
    <div class="flex flex-wrap gap-2">
        <button type="button" wire:click="statusFilter = 'all'" class="px-3 py-1.5 rounded text-sm font-medium {{ $statusFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700' }}">All</button>
        <button type="button" wire:click="statusFilter = 'pending'" class="px-3 py-1.5 rounded text-sm font-medium {{ $statusFilter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700' }}">Pending</button>
        <button type="button" wire:click="statusFilter = 'approved'" class="px-3 py-1.5 rounded text-sm font-medium {{ $statusFilter === 'approved' ? 'bg-green-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700' }}">Approved</button>
        <button type="button" wire:click="statusFilter = 'completed'" class="px-3 py-1.5 rounded text-sm font-medium {{ $statusFilter === 'completed' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700' }}">Completed</button>
        <button type="button" wire:click="statusFilter = 'cancelled'" class="px-3 py-1.5 rounded text-sm font-medium {{ $statusFilter === 'cancelled' ? 'bg-red-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700' }}">Cancelled</button>
    </div>

    <!-- Requests Table -->
    @if($requests->isEmpty())
        <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-8 text-center">
            <h3 class="text-lg font-semibold text-zinc-600 dark:text-zinc-400">No Material Requests</h3>
            <p class="text-zinc-500 mt-1">Create your first material request</p>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow border overflow-hidden">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Request #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($requests as $request)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $request->request_number }}</td>
                            <td class="px-4 py-3">{{ $request->request_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3">{{ $request->details->count() }} items</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    @if($request->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($request->status === 'approved') bg-green-100 text-green-800
                                    @elseif($request->status === 'completed') bg-blue-100 text-blue-800
                                    @else bg-red-100 text-red-800 @endif">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <button wire:click="viewDetail({{ $request->id }})" class="text-blue-600 hover:underline">View</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Create Modal -->
    @if($showCreateModal)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">New Material Request</h3>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Required Date</label>
                            <input type="date" wire:model="newRequestDate" class="w-full rounded border p-2"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Shift</label>
                            <select wire:model="newShift" class="w-full rounded border p-2">
                                <option value="morning">Morning</option>
                                <option value="afternoon">Afternoon</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Notes</label>
                        <textarea wire:model="newNotes" rows="2" class="w-full rounded border p-2"></textarea>
                    </div>
                    
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-medium">Items</label>
                            <button type="button" wire:click="addItem" class="text-sm text-blue-600 hover:underline">+ Add Item</button>
                        </div>
                        
                        @forelse($newItems as $index => $item)
                            <div class="grid grid-cols-4 gap-2 mb-2 items-end">
                                <div class="col-span-2">
                                    <select wire:model="newItems.{{ $index }}.item_id" 
                                        wire:change="fillUom({{ $index }})"
                                        class="w-full rounded border p-2">
                                        <option value="">Select Item</option>
                                        @foreach(\App\Models\Item::forBranch(current_branch_id())->orderBy('name')->get() as $i)
                                            <option value="{{ $i->id }}">{{ $i->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <input type="number" wire:model="newItems.{{ $index }}.quantity" placeholder="Qty" class="rounded border p-2"/>
                                <div class="flex gap-1">
                                    <div class="rounded border p-2 text-sm w-20 flex items-center bg-zinc-100 dark:bg-zinc-700">
                                        @php
                                            $uom = \App\Models\UnitOfMeasure::find($item['uom_id']);
                                        @endphp
                                        {{ $uom?->symbol ?? '-' }}
                                    </div>
                                    <button type="button" wire:click="removeItem({{ $index }})" class="text-red-600">×</button>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">No items added. Click "Add Item" to add.</p>
                        @endforelse
                    </div>
                </div>
                
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" wire:click="closeModal" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="button" wire:click="saveRequest" class="px-4 py-2 bg-blue-600 text-white rounded">Create Request</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Detail Modal -->
    @if($showDetailModal && $selectedRequest)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl w-full max-w-xl">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-2">Request: {{ $selectedRequest->request_number }}</h3>
                <p class="text-sm text-zinc-500 mb-4">{{ $selectedRequest->request_date->format('M d, Y') }} - {{ ucfirst($selectedRequest->shift) }} Shift</p>
                
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-700">
                        <tr>
                            <th class="px-2 py-2 text-left">Item</th>
                            <th class="px-2 py-2 text-right">Requested</th>
                            <th class="px-2 py-2 text-right">Approved</th>
                            <th class="px-2 py-2 text-right">Dispatched</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requestDetails as $detail)
                            <tr class="border-b">
                                <td class="px-2 py-2">{{ $detail['item_name'] }}</td>
                                <td class="px-2 py-2 text-right">{{ $detail['quantity_requested'] }}</td>
                                <td class="px-2 py-2 text-right">{{ $detail['quantity_approved'] }}</td>
                                <td class="px-2 py-2 text-right">{{ $detail['quantity_dispatched'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <div class="flex justify-end mt-4">
                    <button type="button" wire:click="closeModal" class="px-4 py-2 border rounded">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>