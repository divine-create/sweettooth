<div class="p-3 space-y-3" wire:poll.60s>
    <x-breadcrumb
        title="Production Requests"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Sales Dashboard'],
            ['label' => 'Production Requests']
        ]"
        :compact="false"
        :with-icons="true"/>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Sales Production Requests</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Create and track production requests from {{ $departmentName }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search request # or recipe..."
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

                <a href="{{ branch_route('branch-dashboard.sales-dashboard.production-requests.create', ['salesDeptSlug' => $salesDeptSlug, 'sales_dept_slug' => $salesDeptSlug, 'b_id' => $branchId, 'page' => 'Create Production Request' . '_' . $salesDeptSlug]) }}"
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold whitespace-nowrap"
                   wire:navigate>
                    Request Item
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Request #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Production Depts</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Total Qty</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Priority</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($requests as $request)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 align-top">
                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $request['request_number'] }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    ID: {{ $request['id'] }}
                                </div>
                            </td>

                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                <div>{{ $request['items_count'] }} item(s)</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 space-y-1">
                                    @foreach($request['preview'] as $preview)
                                        <div>{{ $preview['name'] }} • {{ number_format($preview['quantity'], 2) }}</div>
                                    @endforeach
                                    @if($request['has_more_preview'])
                                        <div>+ more...</div>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $request['departments'] }}
                            </td>

                            <td class="px-4 py-3 text-center text-sm text-zinc-700 dark:text-zinc-300">
                                {{ number_format($request['total_quantity'], 2) }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                    {{ $request['priority'] === 'urgent'
                                        ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                                        : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' }}">
                                    {{ ucfirst($request['priority']) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center">
                                @php
                                    $status = $request['status'];
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
                                <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $statusClass }}">
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $request['created_at'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                No sales production requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
