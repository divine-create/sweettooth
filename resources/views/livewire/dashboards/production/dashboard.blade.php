<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-40">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Production Dashboard</h1>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Today's production queue and status</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button wire:click="refresh" 
                        class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors"
                        title="Refresh">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="p-6 space-y-6">
        <!-- Production Alerts -->
        @if(!empty($productionAlerts))
            <div class="space-y-3">
                @foreach($productionAlerts as $alert)
                    <x-dashboard.alert-box :type="$alert['type']" :title="$alert['title']">
                        {{ $alert['message'] }}
                    </x-dashboard.alert-box>
                @endforeach
            </div>
        @endif

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Queue Count -->
            <x-dashboard.metric-card
                title="Today Queue"
                :value="$todayQueue->count()"
                color="blue"
                format="number"
                icon='<svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>'
            />

            <!-- Completed -->
            <x-dashboard.metric-card
                title="Completed Today"
                :value="$todayCompleted"
                color="green"
                format="number"
                :trend="$todayCompleted > 5 ? 'up' : 'neutral'"
                icon='<svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
            />

            <!-- Pending -->
            <x-dashboard.metric-card
                title="Pending Items"
                :value="$todayPending"
                :color="$todayPending > 10 ? 'red' : 'yellow'"
                format="number"
                :trend="$todayPending > 5 ? 'down' : 'neutral'"
                icon='<svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
            />

            <!-- Today Quantity -->
            <x-dashboard.metric-card
                title="Production Qty"
                :value="$todayQuantity"
                color="purple"
                format="number"
                icon='<svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>'
            />
        </div>

        <!-- Staff and Active Recipes -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Staff On Duty -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Staff On Duty</h2>
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM4.318 20H2v-2a6 6 0 0110.788-3.582A6 6 0 0122 20h-2.318" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Team Members</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $staffAssigned }}</p>
                    </div>
                </div>
            </div>

            <!-- Active Recipes -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">In Production</h2>
                @if($activeRecipes->count() > 0)
                    <div class="space-y-2">
                        @foreach($activeRecipes->take(5) as $recipe)
                            <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-700 rounded">
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $recipe->product->name ?? 'Unknown Product' }}</span>
                                <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded-full font-semibold">Running</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center py-4">No recipes in production</p>
                @endif
            </div>
        </div>

        <!-- Production Timeline -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Production Timeline</h2>
            @if($productionTimeline->count() > 0)
                <div class="space-y-3">
                    @foreach($productionTimeline as $slot)
                        <div class="border-l-4 border-blue-500 pl-4 py-3">
                            <div class="flex items-center justify-between mb-2">
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $slot['hour'] }}</p>
                                <div class="flex items-center space-x-2 text-xs">
                                    @if($slot['pending'] > 0)
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 rounded-full">{{ $slot['pending'] }} Pending</span>
                                    @endif
                                    @if($slot['in_progress'] > 0)
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded-full">{{ $slot['in_progress'] }} Active</span>
                                    @endif
                                    @if($slot['completed'] > 0)
                                        <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 rounded-full">{{ $slot['completed'] }} Done</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex gap-1 h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                @if($slot['pending'] > 0)
                                    <div class="bg-yellow-500" style="width: {{ ($slot['pending'] / ($slot['pending'] + $slot['in_progress'] + $slot['completed'])) * 100 }}%"></div>
                                @endif
                                @if($slot['in_progress'] > 0)
                                    <div class="bg-blue-500" style="width: {{ ($slot['in_progress'] / ($slot['pending'] + $slot['in_progress'] + $slot['completed'])) * 100 }}%"></div>
                                @endif
                                @if($slot['completed'] > 0)
                                    <div class="bg-green-500" style="width: {{ ($slot['completed'] / ($slot['pending'] + $slot['in_progress'] + $slot['completed'])) * 100 }}%"></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center py-6">No production scheduled</p>
            @endif
        </div>

        <!-- Production Queue Table -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Production Queue</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">Product</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">Recipe</th>
                            <th class="text-right py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">Qty</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">Time</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($todayQueue as $item)
                            <tr class="border-b border-zinc-100 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $item->recipe->product->name ?? 'N/A' }}</td>
                                <td class="py-3 px-4 text-zinc-600 dark:text-zinc-400">{{ $item->recipe->name ?? 'N/A' }}</td>
                                <td class="py-3 px-4 text-right font-semibold text-zinc-900 dark:text-zinc-100">{{ $item->quantity }}</td>
                                <td class="py-3 px-4 text-zinc-600 dark:text-zinc-400">{{ $item->scheduled_time ? $item->scheduled_time->format('g:i A') : 'Unscheduled' }}</td>
                                <td class="py-3 px-4">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                            'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$item->status] ?? 'bg-zinc-100 text-zinc-800' }}">
                                        {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 px-4 text-center text-zinc-600 dark:text-zinc-400">
                                    No production queued for today
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
