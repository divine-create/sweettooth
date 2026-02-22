<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-40">
        <div class="px-6 py-4">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Super Admin Dashboard</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Global system overview and management</p>
        </div>
    </div>

    <div class="p-6 space-y-6">
        <!-- Global KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <x-dashboard.metric-card title="Global Revenue" :value="$globalRevenue" color="green" format="currency" />
            <x-dashboard.metric-card title="Global Inventory" :value="$globalInventoryValue" color="blue" format="currency" />
            <x-dashboard.metric-card title="Total Staff" :value="$globalStaffCount" color="purple" format="number" />
            <x-dashboard.metric-card title="Pending Items" :value="$globalAlerts" :color="$globalAlerts > 10 ? 'red' : 'yellow'" format="number" />
        </div>

        <!-- Branch Selector -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Branches</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($branches as $branch)
                    <button wire:click="selectBranch('{{ $branch->id }}')"
                        class="px-4 py-2 rounded-lg font-medium transition-all {{ $selectedBranchId === $branch->id ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                        {{ $branch->name }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Branch Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($branchMetrics as $branch)
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 hover:shadow-lg transition-shadow">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">{{ $branch['name'] }}</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Revenue</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($branch['revenue'] ?? 0) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Inventory</p>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($branch['inventory'] ?? 0) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Staff</p>
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $branch['staff'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pending Approvals and Audit Log -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Pending Approvals (2 cols) -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Global Pending Approvals</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="text-left py-3 px-4 font-semibold">Action</th>
                                    <th class="text-left py-3 px-4 font-semibold">Requested By</th>
                                    <th class="text-left py-3 px-4 font-semibold">Date</th>
                                    <th class="text-left py-3 px-4 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingApprovals as $approval)
                                    <tr class="border-b border-zinc-100 dark:border-zinc-700">
                                        <td class="py-3 px-4 font-medium capitalize">{{ str_replace('_', ' ', str_replace(':', ' ', $approval->action)) }}</td>
                                        <td class="py-3 px-4">{{ $approval->requester?->name ?? 'N/A' }}</td>
                                        <td class="py-3 px-4">{{ $approval->created_at->format('M d') }}</td>
                                        <td class="py-3 px-4"><span class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 text-xs font-semibold rounded-full">Pending</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 px-4 text-center text-zinc-600 dark:text-zinc-400">No pending approvals</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Activity (1 col) -->
            <div>
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Recent Activity</h2>
                    <div class="space-y-3">
                        @forelse($globalAuditLog->take(10) as $log)
                            <div class="border-l-2 border-blue-500 pl-3 py-2">
                                <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 capitalize">{{ str_replace('_', ' ', str_replace(':', ' ', $log->action)) }}</p>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $log->created_at->diffForHumans() }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center py-4">No activity</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
