<div class="p-3 space-y-3">
    <x-breadcrumb title="Callbacks Management" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Sales Dashboard'],
        ['label' => 'Callbacks'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-600 to-orange-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Sales Callbacks Management</h2>
                <p class="text-sm opacity-90 mt-1">
                    Track and manage all dispatch callbacks to production
                </p>
            </div>
            <div class="flex gap-2">
                <button wire:click="exportCSV" 
                    class="px-4 py-2 bg-white text-green-600 rounded-lg font-medium hover:bg-green-50 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="rou nd" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </button>
                @php
                    $deptSlug = $sales_dept_slug ?? request()->query('salesDeptSlug') ?? request()->query('sales_dept_slug');
                @endphp
                <a href="{{ branch_route('branch-dashboard.sales-dashboard.callbacks.dispatch-callbacks', [
                        'salesDeptSlug' => $deptSlug,
                        'sales_dept_slug' => $deptSlug,
                        'page' => $deptSlug ? 'Callbacks_' . $deptSlug : null,
                    ]) }}"
                    class="px-4 py-2 bg-white text-orange-600 rounded-lg font-medium hover:bg-orange-50 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    From Dispatch
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        @php
            $totalCallbacks = \App\Models\ProductDispatchCallback::whereHas('salesShift', function($q) {
                $q->where('branch_id', $this->getBranchId());
            })->count();

            $pendingCallbacks = \App\Models\ProductDispatchCallback::whereHas('salesShift', function($q) {
                $q->where('branch_id', $this->getBranchId());
            })->where('status', 'pending')->count();

            $approvedCallbacks = \App\Models\ProductDispatchCallback::whereHas('salesShift', function($q) {
                $q->where('branch_id', $this->getBranchId());
            })->where('status', 'approved_by_production')->count();

            $completedCallbacks = \App\Models\ProductDispatchCallback::whereHas('salesShift', function($q) {
                $q->where('branch_id', $this->getBranchId());
            })->where('status', 'completed')->count();
        @endphp

        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Callbacks</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $totalCallbacks }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $pendingCallbacks }}</p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Approved</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $approvedCallbacks }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Completed</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $completedCallbacks }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div x-data="{ open: false }"
        class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div class="flex justify-between items-center px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14.293 13H10v5l-4-4v-3.586L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters
            </h2>
            <button @click="open = !open"
                class="flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 8h16M4 16h16" />
                    <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span x-text="open ? 'Close' : 'Show Filters'"></span>
            </button>
        </div>

        <div x-show="open" x-collapse class="p-3 space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Search by product or employee..."
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                    <select wire:model.live="filterStatus"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">All Statuses</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Start Date</label>
                    <input type="date" wire:model.live="startDate"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">End Date</label>
                    <input type="date" wire:model.live="endDate"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
            </div>


        </div>
    </div>

    <!-- Callbacks Table -->
    <x-table :$headers :$rows striped paginate persist collapsible
        :filter="['quantity' => 'quantity', 'search' => 'search', 'filterStatus' => 'filterStatus', 'startDate' => 'startDate', 'endDate' => 'endDate']"
        :quantity="[10, 20, 50, 100]">

        @interact('column_callback_id', $row)
            <div class="text-center">
                <span class="font-mono text-sm text-zinc-600 dark:text-zinc-400">#{{ $row->id }}</span>
            </div>
        @endinteract

        @interact('column_product', $row)
            <div>
                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->product->name }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">SKU: {{ $row->product->sku }}</div>
            </div>
        @endinteract

        @interact('column_quantity', $row)
            <div class="text-center">
                <span class="font-semibold text-orange-600 dark:text-orange-400">
                    {{ number_format($row->quantity, 2) }} {{ $row->uom }}
                </span>
            </div>
        @endinteract

        @interact('column_reason', $row)
            <div class="text-center">
                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    {{ $row->formatted_reason }}
                </span>
            </div>
        @endinteract

        @interact('column_status', $row)
            <div class="text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusBadgeClass($row->status) }}">
                    {{ $row->formatted_status }}
                </span>
            </div>
        @endinteract

        @interact('column_callback_time', $row)
            <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                {{ $row->callback_time->format('M d, Y H:i') }}
            </div>
        @endinteract

        @interact('column_recorded_by', $row)
            <div class="text-sm text-zinc-700 dark:text-zinc-300">
                {{ $row->recordedBy->name ?? 'N/A' }}
            </div>
        @endinteract

        @interact('column_action', $row)
            <div class="flex justify-center gap-2">
                <button wire:click="viewDetails({{ $row->id }})"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium transition-colors">
                    View Details
                </button>
            </div>
        @endinteract

    </x-table>

</div>
