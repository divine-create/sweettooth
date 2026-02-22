<div class="p-3 space-y-3" wire:poll.20s="$refresh" wire:poll:keep-alive>
    <x-breadcrumb
        title="Sales Request Review"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Sales Request Review']
        ]"
        :compact="false"
        :with-icons="true"/>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Incoming Sales Requests</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Review and approve sales-demand items for production
                    @if($department)
                        ({{ $department->name }})
                    @endif
                </p>
            </div>

            <button type="button"
                    wire:click="approveFilteredPending"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold">
                Approve Pending (Filtered)
            </button>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search request, recipe, dept..."
                   class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">

            <select wire:model.live="statusFilter"
                    class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved_by_production">Approved by Production</option>
                <option value="materials_requested">Materials Requested</option>
                <option value="materials_approved">Materials Approved</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="dispatched">Dispatched</option>
                <option value="received_by_sales">Received by Sales</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>

            <select wire:model.live="priorityFilter"
                    class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
                <option value="all">All Priority</option>
                <option value="normal">Normal</option>
                <option value="urgent">Urgent</option>
            </select>

            <select wire:model.live="perPage"
                    class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
                <option value="15">15 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
            </select>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Request #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Sales Dept</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Recipe</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Production Dept</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Qty Req.</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Materials Req</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Priority</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($items as $item)
                        @php
                            $status = is_string($item->status) ? $item->status : $item->status->value;
                            $statusClass = match($status) {
                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                'approved_by_production' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                'materials_requested' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                                'materials_approved' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300',
                                'processing' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                                'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                                'dispatched' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300',
                                'received_by_sales' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                'cancelled' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-300',
                                default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-300',
                            };
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="px-4 py-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $item->request?->request_number ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $item->request?->salesDepartment?->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $item->recipe?->product_name ?? $item->product?->name ?? 'Unknown Recipe' }}
                                @if($item->recipe?->sku || $item->product?->sku)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $item->recipe?->sku ?? $item->product?->sku }}
                                    </div>
                                @endif
                                @if((float) ($item->recipe?->yield_quantity ?? 0) > 0)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Yield: {{ number_format((float) $item->recipe?->yield_quantity, 2) }}
                                        {{ $item->recipe?->unitOfMeasure?->symbol }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $item->productionDepartment?->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-zinc-700 dark:text-zinc-300">
                                @php
                                    $qtyDisplay = $this->getQuantityDisplay($item);
                                @endphp
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ number_format((float) $qtyDisplay['production_qty'], 2) }}
                                    @if($qtyDisplay['production_symbol'])
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $qtyDisplay['production_symbol'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ number_format((float) $qtyDisplay['sales_qty'], 2) }}
                                    @if($qtyDisplay['sales_symbol'])
                                        {{ $qtyDisplay['sales_symbol'] }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                @if($item->latestMaterialRequest?->itemRequest)
                                    <div class="font-medium">
                                        {{ $item->latestMaterialRequest->itemRequest->request_number }}
                                    </div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ ucwords(str_replace('_', ' ', $item->latestMaterialRequest->itemRequest->status)) }}
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Not requested</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                    {{ ($item->request?->priority ?? 'normal') === 'urgent'
                                        ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                                        : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' }}">
                                    {{ ucfirst($item->request?->priority ?? 'normal') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $statusClass }}">
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $item->created_at?->format('M d, Y H:i') ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($status === 'pending')
                                        <button type="button"
                                                wire:click="approveItem({{ $item->id }})"
                                                class="px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded">
                                            Approve
                                        </button>
                                    @endif

                                    @if($status === 'approved_by_production')
                                        <button type="button"
                                                wire:click="requestMaterials({{ $item->id }})"
                                                class="px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded">
                                            Request Materials
                                        </button>
                                    @endif

                                    @if($status === 'materials_requested')
                                        <button type="button"
                                                wire:click="syncMaterialsStatus({{ $item->id }})"
                                                class="px-3 py-1.5 text-xs font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded">
                                            Sync Materials
                                        </button>
                                    @endif

                                    @if(in_array($status, ['pending', 'approved_by_production', 'materials_requested', 'materials_approved', 'processing'], true))
                                        <button type="button"
                                                wire:click="rejectItem({{ $item->id }})"
                                                class="px-3 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded">
                                            Reject
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                No sales request items found for current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($items, 'links'))
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>
