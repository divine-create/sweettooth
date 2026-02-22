<div class="p-3 space-y-3">

    <style>
        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            @apply bg-zinc-300 dark:bg-zinc-700 rounded-full;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            @apply bg-zinc-400 dark:bg-zinc-600;
        }
    </style>

    <x-breadcrumb
        title="Request & Dispatch Analytics"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Analytics'],
            ['label' => 'Request & Dispatch']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <div class="flex justify-end">
        <button wire:click="generateReport"
                wire:loading.attr="disabled"
                wire:target="generateReport"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm disabled:opacity-60">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m8 4H4a1 1 0 01-1-1V6a1 1 0 011-1h10l6 6v7a1 1 0 01-1 1z"/>
            </svg>
            <span wire:loading.remove wire:target="generateReport">Generate Report</span>
            <span wire:loading wire:target="generateReport">Generating...</span>
        </button>
    </div>

    @if (session('success'))
        <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded text-sm text-green-800 dark:text-green-200">
            <div class="flex flex-wrap items-center gap-2">
                <span>{{ session('success') }}</span>
                @if($generatedReportId)
                    <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $generatedReportId]) }}"
                       class="inline-flex items-center px-2 py-1 rounded bg-green-700 text-white hover:bg-green-800 text-xs">
                        View Report
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if (session('warning'))
        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded text-sm text-yellow-800 dark:text-yellow-200">
            {{ session('warning') }}
        </div>
    @endif

    {{-- Smart Insights Panel --}}
    @if(count($smartInsights) > 0)
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-zinc-800 dark:to-zinc-700 rounded-lg shadow-sm border border-blue-200 dark:border-zinc-600 p-4">
            <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-3 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Smart Insights
            </h3>
            <div class="space-y-2">
                @foreach($smartInsights as $insight)
                    @php
                        $bgColor = match($insight['type']) {
                            'success' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
                            'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
                            'critical' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
                            default => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'
                        };
                    @endphp
                    <div class="flex items-start gap-3 p-3 {{ $bgColor }} border rounded-lg">
                        <span class="text-2xl">{{ $insight['icon'] }}</span>
                        <p class="text-sm text-zinc-800 dark:text-zinc-200 flex-1">{{ $insight['message'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Request Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Total Requests</p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                        {{ number_format($requestSummary['total_requests']) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">In period</p>
                </div>
                <div class="text-3xl text-blue-500 opacity-20">📋</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Pending</p>
                    <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ number_format($requestSummary['pending']) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Awaiting</p>
                </div>
                <div class="text-3xl text-yellow-500 opacity-20">⏳</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Approved</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">
                        {{ number_format($requestSummary['approved']) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Ready</p>
                </div>
                <div class="text-3xl text-green-500 opacity-20">✓</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Completed</p>
                    <p class="text-xl font-bold text-purple-600 dark:text-purple-400">
                        {{ number_format($requestSummary['completed']) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Fulfilled</p>
                </div>
                <div class="text-3xl text-purple-500 opacity-20">✔</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Rejected</p>
                    <p class="text-xl font-bold text-red-600 dark:text-red-400">
                        {{ number_format($requestSummary['rejected']) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Denied</p>
                </div>
                <div class="text-3xl text-red-500 opacity-20">✖</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Fulfillment</p>
                    <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400">
                        {{ number_format($fulfillmentRate['rate'], 1) }}%
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Rate</p>
                </div>
                <div class="text-3xl text-indigo-500 opacity-20">📊</div>
            </div>
        </div>
    </div>

    {{-- Dispatch Summary --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-3">Dispatch Summary</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Total</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($dispatchSummary['total_dispatches']) }}</p>
            </div>
            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Completed</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($dispatchSummary['completed']) }}</p>
            </div>
            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Pending</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($dispatchSummary['pending']) }}</p>
            </div>
            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Failed</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($dispatchSummary['failed']) }}</p>
            </div>
            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Quantity</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($dispatchSummary['total_quantity_dispatched'], 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14.293 13H10v5l-4-4v-3.586L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters & Search
            </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Request #, requester..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                <select wire:model.live="statusFilter" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Department</label>
                <select wire:model.live="departmentFilter" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Shift</label>
                <select wire:model.live="shiftFilter" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Shifts</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift }}">{{ ucfirst($shift) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">To</label>
                <input type="date" wire:model.live="dateTo" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    {{-- Period Comparison --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-3 flex items-center">
            <svg class="w-4 h-4 mr-1.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Period Comparison
            <span class="ml-2 text-xs font-normal text-zinc-600 dark:text-zinc-400">
                ({{ \Carbon\Carbon::parse($dateFrom)->format('M d') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }})
            </span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Current Period</p>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($periodComparison['current']) }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">requests</p>
            </div>

            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Previous Period</p>
                <p class="text-3xl font-bold text-zinc-600 dark:text-zinc-400">{{ number_format($periodComparison['previous']) }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">requests</p>
            </div>

            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Change</p>
                <p class="text-3xl font-bold {{ $periodComparison['change'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $periodComparison['change'] >= 0 ? '↑' : '↓' }} {{ number_format(abs($periodComparison['change']), 1) }}%
                </p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">vs previous</p>
            </div>
        </div>
    </div>

    {{-- Approval Turnaround Time --}}
    @if($approvalTurnaround['average_hours'])
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Approval Turnaround Time</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Average</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($approvalTurnaround['average_hours'], 1) }} hrs</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ number_format($approvalTurnaround['average_days'], 1) }} days</p>
                </div>

                @if($approvalTurnaround['fastest'])
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Fastest</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($approvalTurnaround['fastest']['hours'], 1) }} hrs</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $approvalTurnaround['fastest']['request_number'] }}</p>
                    </div>
                @endif

                @if($approvalTurnaround['slowest'])
                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3">
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Slowest</p>
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($approvalTurnaround['slowest']['hours'], 1) }} hrs</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $approvalTurnaround['slowest']['request_number'] }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Grid: Pending Requests & Most Requested --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        {{-- Pending Requests --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Pending Requests</h3>
            <div class="space-y-2 max-h-[500px] overflow-y-auto scrollbar-thin">
                @forelse($pendingRequests as $item)
                    @php
                        $urgencyColors = [
                            'Critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                            'High' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                            'Medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                        ];
                    @endphp
                    <div class="flex items-start justify-between p-3 bg-gray-50 dark:bg-zinc-700/50 rounded hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item['request']->request_number }}</p>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded {{ $urgencyColors[$item['urgency']] ?? '' }}">{{ $item['urgency'] }}</span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item['request']->department->name }} • {{ ucfirst($item['request']->shift) }}</p>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">By {{ $item['request']->requestedBy?->name ?? 'Unknown User' }}</p>
                        </div>
                        <div class="text-right ml-3">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Waiting</p>
                            <p class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ $item['waiting_time'] }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">hours</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
                        <p class="text-2xl mb-2">✅</p>
                        <p class="text-sm">No pending requests!</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Most Requested Items --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Top 10 Most Requested</h3>
            <div class="space-y-2 max-h-[500px] overflow-y-auto scrollbar-thin">
                @forelse($mostRequestedItems as $index => $item)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700/50 rounded">
                        <div class="flex items-center flex-1">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-sm mr-3">{{ $index + 1 }}</div>
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item->sku }} • {{ number_format($item->request_count) }} requests</p>
                            </div>
                        </div>
                        <div class="text-right ml-3">
                            <p class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($item->total_quantity, 0) }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item->uom }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-zinc-500 dark:text-zinc-400 py-8">No data</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Department & Shift Breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        {{-- Department --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Department Breakdown</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-100 dark:bg-zinc-700">
                        <tr>
                            <th class="px-3 py-2 text-left text-zinc-700 dark:text-zinc-300">Department</th>
                            <th class="px-3 py-2 text-center text-zinc-700 dark:text-zinc-300">Total</th>
                            <th class="px-3 py-2 text-center text-zinc-700 dark:text-zinc-300">Pending</th>
                            <th class="px-3 py-2 text-center text-zinc-700 dark:text-zinc-300">Completed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($departmentBreakdown as $dept)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-3 py-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $dept['department'] }}</td>
                                <td class="px-3 py-2 text-center font-bold text-blue-600 dark:text-blue-400">{{ $dept['total'] }}</td>
                                <td class="px-3 py-2 text-center text-yellow-600 dark:text-yellow-400">{{ $dept['pending'] }}</td>
                                <td class="px-3 py-2 text-center text-purple-600 dark:text-purple-400">{{ $dept['completed'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-8 text-center text-zinc-500 dark:text-zinc-400">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Shift --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Shift Breakdown</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-100 dark:bg-zinc-700">
                        <tr>
                            <th class="px-3 py-2 text-left text-zinc-700 dark:text-zinc-300">Shift</th>
                            <th class="px-3 py-2 text-center text-zinc-700 dark:text-zinc-300">Total</th>
                            <th class="px-3 py-2 text-center text-zinc-700 dark:text-zinc-300">Pending</th>
                            <th class="px-3 py-2 text-center text-zinc-700 dark:text-zinc-300">Completed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($shiftBreakdown as $shift)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-3 py-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $shift['shift'] }}</td>
                                <td class="px-3 py-2 text-center font-bold text-blue-600 dark:text-blue-400">{{ $shift['total'] }}</td>
                                <td class="px-3 py-2 text-center text-yellow-600 dark:text-yellow-400">{{ $shift['pending'] }}</td>
                                <td class="px-3 py-2 text-center text-purple-600 dark:text-purple-400">{{ $shift['completed'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-8 text-center text-zinc-500 dark:text-zinc-400">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Daily Trend --}}
    @if($dailyTrend->isNotEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Daily Trend (Last 14 Days)</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-100 dark:bg-zinc-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-zinc-700 dark:text-zinc-300">Date</th>
                            <th class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">Total</th>
                            <th class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">Pending</th>
                            <th class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">Approved</th>
                            <th class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">Completed</th>
                            <th class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">Rejected</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($dailyTrend as $trend)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $trend['date'] }}</td>
                                <td class="px-4 py-2 text-right font-bold text-blue-600 dark:text-blue-400">{{ $trend['total'] }}</td>
                                <td class="px-4 py-2 text-right text-yellow-600 dark:text-yellow-400">{{ $trend['pending'] }}</td>
                                <td class="px-4 py-2 text-right text-green-600 dark:text-green-400">{{ $trend['approved'] }}</td>
                                <td class="px-4 py-2 text-right text-purple-600 dark:text-purple-400">{{ $trend['completed'] }}</td>
                                <td class="px-4 py-2 text-right text-red-600 dark:text-red-400">{{ $trend['rejected'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Requests Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Request Details</h3>
        <div class="overflow-x-auto" wire:loading.class="opacity-50" wire:target="updatedSearchTerm, updatedSelectedStatus, updatedDateFrom, updatedDateTo">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
    dark:text-zinc-300">
                    <tr>
                        <th class="px-4 py-3">Request #</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Shift</th>
                        <th class="px-4 py-3">Requested By</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Items</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Approved By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($requests as $request)
                        <tr class="bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $request->request_number }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $request->request_date->format('M d, Y') }}</div>
                            </td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $request->department->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ ucfirst($request->shift) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $request->requestedBy->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $request->request_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $request->requestDetails->count() }} items</td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeColors = match($request->status) {
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'completed' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                        'partially_fulfilled' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                    };
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded {{ $badgeColors }}">
                                    {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $request->approver->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No requests found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $requests->links() }}</div>
    </div>

</div>
