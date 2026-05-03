<div class="p-3 space-y-3">

    <x-breadcrumb
        title="Material Approvals"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Inventory'],
            ['label' => 'Material Approvals']
        ]"
        :compact="false"
        :with-icons="true"/>

    <!-- Filter -->
    <div class="flex flex-wrap gap-2">
        <button type="button" wire:click="statusFilter = 'all'" class="px-3 py-1.5 rounded text-sm font-medium {{ $statusFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700' }}">All</button>
        <button type="button" wire:click="statusFilter = 'pending'" class="px-3 py-1.5 rounded text-sm font-medium {{ $statusFilter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700' }}">Pending</button>
        <button type="button" wire:click="statusFilter = 'approved'" class="px-3 py-1.5 rounded text-sm font-medium {{ $statusFilter === 'approved' ? 'bg-green-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700' }}">Approved</button>
        <button type="button" wire:click="statusFilter = 'completed'" class="px-3 py-1.5 rounded text-sm font-medium {{ $statusFilter === 'completed' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700' }}">Completed</button>
    </div>

    <!-- Pending Requests Table -->
    @if($requests->isEmpty())
        <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-8 text-center">
            <h3 class="text-lg font-semibold text-zinc-600 dark:text-zinc-400">No Pending Approvals</h3>
            <p class="text-zinc-500 mt-1">All requests have been processed</p>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow border overflow-hidden">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Request #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Requesting Dept</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Target Dept</th>
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
                            <td class="px-4 py-3">{{ $request->requester?->name ?? 'System' }}</td>
                            <td class="px-4 py-3">{{ $request->department?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $request->request_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3">{{ $request->details->count() }} items</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    @if($request->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($request->status === 'approved') bg-green-100 text-green-800
                                    @else bg-blue-100 text-blue-800 @endif">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($request->status === 'pending')
                                    <button wire:click="openApprovalModal({{ $request->id }})" class="text-blue-600 hover:underline">Approve & Dispatch</button>
                                @else
                                    <button wire:click="openApprovalModal({{ $request->id }})" class="text-blue-600 hover:underline">View</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Approval Modal -->
    @if($showApprovalModal && $selectedRequest)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-2">Request: {{ $selectedRequest->request_number }}</h3>
                <p class="text-sm text-zinc-500 mb-4">
                    Requesting Dept: {{ $selectedRequest->requester?->name ?? 'System' }} → 
                    Target: {{ $selectedRequest->department?->name }}
                </p>
                
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-700">
                        <tr>
                            <th class="px-2 py-2 text-left">Item</th>
                            <th class="px-2 py-2 text-right">Requested</th>
                            <th class="px-2 py-2 text-right">Approved Qty</th>
                            <th class="px-2 py-2 text-right">Dispatched</th>
                            <th class="px-2 py-2 text-center">UOM</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($approvalItems as $index => $item)
                            <tr class="border-b">
                                <td class="px-2 py-2">{{ $item['item_name'] }}</td>
                                <td class="px-2 py-2 text-right">{{ $item['quantity_requested'] }}</td>
                                <td class="px-2 py-2 text-right">
                                    <input type="number" 
                                        wire:model="approvalItems.{{ $index }}.quantity_approved" 
                                        min="0"
                                        step="0.01"
                                        class="w-20 text-right rounded border p-1"/>
                                </td>
                                <td class="px-2 py-2 text-right">{{ $item['quantity_dispatched'] }}</td>
                                <td class="px-2 py-2 text-center">{{ $item['uom_symbol'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" wire:click="closeModal" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="button" wire:click="approveAndDispatch" class="px-4 py-2 bg-green-600 text-white rounded">Approve & Dispatch</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>