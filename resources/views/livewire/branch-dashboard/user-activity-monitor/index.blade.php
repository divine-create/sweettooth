@once
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endonce

<div class="p-3 space-y-4"
     @if(in_array($tab, ['overview', 'active'])) wire:poll.30s="$refresh" @endif>

    {{-- Breadcrumb --}}
    <x-breadcrumb title="User Activity Monitor" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Audit'],
        ['label' => 'User Activity Monitor'],
    ]" :compact="false" :with-icons="true" />

    {{-- Header --}}
    <div class="bg-gradient-to-r from-slate-700 to-slate-800 dark:from-slate-900 dark:to-slate-950 text-white rounded-lg shadow-lg p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">User Activity Monitor</h2>
                <p class="text-sm opacity-80 mt-1">Real-time visibility into what your team is doing</p>
            </div>
            <button wire:click="$refresh"
                class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition-colors flex items-center gap-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-5">
            <div class="bg-white/10 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold">{{ number_format($todayLogins) }}</p>
                <p class="text-xs opacity-75 mt-1 uppercase tracking-wide">Today's Logins</p>
            </div>
            <div class="bg-white/10 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-green-300">{{ $onlineCount }}</p>
                <p class="text-xs opacity-75 mt-1 uppercase tracking-wide">Currently Active</p>
            </div>
            <div class="bg-white/10 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold {{ $failedToday > 0 ? 'text-red-300' : '' }}">{{ $failedToday }}</p>
                <p class="text-xs opacity-75 mt-1 uppercase tracking-wide">Wrong Password Attempts</p>
            </div>
            <div class="bg-white/10 rounded-lg p-4 text-center">
                <p class="text-base font-bold truncate leading-tight mt-1">{{ $topModule }}</p>
                <p class="text-xs opacity-75 mt-1 uppercase tracking-wide">Most Used Section</p>
            </div>
            <div class="bg-white/10 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-blue-300">{{ number_format($totalActionsToday) }}</p>
                <p class="text-xs opacity-75 mt-1 uppercase tracking-wide">Total Actions Today</p>
            </div>
        </div>
    </div>

    {{-- Tab Strip --}}
    <div class="flex gap-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-2 border dark:border-gray-700 flex-wrap">
        @foreach ([
            'overview' => 'Overview',
            'active'   => "Who's Active",
            'user'     => 'Staff Activity',
            'alerts'   => 'Alerts',
        ] as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')"
                class="px-4 py-2 rounded-lg font-medium text-sm transition-all
                    {{ $tab === $key
                        ? 'bg-slate-700 text-white shadow-sm'
                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                @if ($key === 'active')
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full {{ $onlineCount > 0 ? 'bg-green-400 animate-pulse' : 'bg-gray-400' }}"></span>
                        {{ $label }}
                        @if ($onlineCount > 0)
                            <span class="bg-green-100 text-green-700 text-xs px-1.5 py-0.5 rounded-full font-semibold">{{ $onlineCount }}</span>
                        @endif
                    </span>
                @elseif ($key === 'alerts' && $alertBadgeCount > 0)
                    <span class="inline-flex items-center gap-1.5">
                        {{ $label }}
                        <span class="bg-amber-100 text-amber-700 text-xs px-1.5 py-0.5 rounded-full font-semibold">{{ $alertBadgeCount }}</span>
                    </span>
                @else
                    {{ $label }}
                @endif
            </button>
        @endforeach
    </div>

    {{-- ───── OVERVIEW TAB ───── --}}
    @if ($tab === 'overview')
        <div class="space-y-4">

            {{-- A. Activity by Hour --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Activity Today by Hour</h3>
                @if ($chartData)
                    <div
                        x-data="{
                            chart: null,
                            init() {
                                const isDark = document.documentElement.classList.contains('dark');
                                const data = @js($chartData);
                                this.chart = new ApexCharts(this.$el, {
                                    chart: {
                                        type: 'area',
                                        height: 256,
                                        background: 'transparent',
                                        toolbar: { show: false },
                                        animations: { enabled: true },
                                        sparkline: { enabled: false }
                                    },
                                    theme: { mode: isDark ? 'dark' : 'light' },
                                    series: [{ name: 'Events', data: data.counts }],
                                    xaxis: {
                                        categories: data.labels,
                                        labels: { style: { fontSize: '11px' }, rotate: -45 }
                                    },
                                    yaxis: { labels: { style: { fontSize: '11px' } }, min: 0 },
                                    fill: {
                                        type: 'gradient',
                                        gradient: {
                                            shadeIntensity: 1,
                                            opacityFrom: 0.45,
                                            opacityTo: 0.02,
                                            stops: [0, 100]
                                        }
                                    },
                                    colors: ['#6366f1'],
                                    stroke: { curve: 'smooth', width: 2 },
                                    dataLabels: { enabled: false },
                                    grid: { borderColor: isDark ? '#374151' : '#f3f4f6', strokeDashArray: 4 },
                                    tooltip: { theme: isDark ? 'dark' : 'light' }
                                });
                                this.chart.render();
                            }
                        }"
                        class="h-64">
                    </div>
                @endif
            </div>

            {{-- B. Most Used Sections --}}
            @if ($moduleBreakdown && count($moduleBreakdown) > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700 p-5">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Most Used Sections Today</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($moduleBreakdown as $mod => $count)
                            <span class="inline-flex items-center gap-2 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-3 py-1.5 rounded-full text-sm font-medium">
                                {{ \App\Services\ActivityDescriptionService::moduleLabel($mod) }}
                                <span class="bg-indigo-200 dark:bg-indigo-800 text-indigo-900 dark:text-indigo-100 text-xs px-1.5 py-0.5 rounded-full font-semibold">{{ $count }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- C. Recent Activity --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Recent Activity</h3>
                @if ($recentActivity && $recentActivity->isNotEmpty())
                    <div class="space-y-2.5">
                        @foreach ($recentActivity as $item)
                            @php
                                $dot = match($item['event_type']) {
                                    'login'        => 'bg-green-400',
                                    'failed_login' => 'bg-red-400',
                                    'logout'       => 'bg-red-300',
                                    default        => 'bg-indigo-400',
                                };
                            @endphp
                            <div class="flex items-start gap-3">
                                <span class="w-2 h-2 rounded-full {{ $dot }} mt-1.5 flex-shrink-0"></span>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $item['sentence'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-500">No activity recorded yet today.</p>
                @endif
            </div>

            {{-- D. Raw Log (disclosure) --}}
            <details class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700">
                <summary class="px-5 py-3 text-sm font-medium text-gray-500 dark:text-gray-400 cursor-pointer select-none hover:bg-gray-50 dark:hover:bg-gray-700/40 rounded-lg list-none flex items-center gap-2">
                    <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Show raw activity log
                </summary>
                <div class="border-t dark:border-gray-700">
                    {{-- Filters --}}
                    <div class="p-4 border-b dark:border-gray-700 space-y-3">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                            <input wire:model.live.debounce.400ms="feedSearch" type="text"
                                placeholder="Search action, route…"
                                class="col-span-2 border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-500 focus:outline-none" />

                            <select wire:model.live="feedModule"
                                class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-500 focus:outline-none">
                                <option value="">All Modules</option>
                                @foreach ($modules as $mod)
                                    <option value="{{ $mod }}">{{ ucfirst($mod) }}</option>
                                @endforeach
                            </select>

                            <select wire:model.live="feedEventType"
                                class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-500 focus:outline-none">
                                <option value="">All Events</option>
                                @foreach ($eventTypes as $et)
                                    <option value="{{ $et }}">{{ ucwords(str_replace('_', ' ', $et)) }}</option>
                                @endforeach
                            </select>

                            <select wire:model.live="feedUserId"
                                class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-500 focus:outline-none">
                                <option value="">All Users</option>
                                @foreach ($allUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>

                            <div class="flex gap-2">
                                <input wire:model.live="feedDateFrom" type="date"
                                    class="flex-1 border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-slate-500 focus:outline-none" />
                                <input wire:model.live="feedDateTo" type="date"
                                    class="flex-1 border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-slate-500 focus:outline-none" />
                            </div>
                        </div>

                        @if ($feedSearch || $feedModule || $feedEventType || $feedUserId || $feedDateFrom || $feedDateTo)
                            <button wire:click="clearFeedFilters"
                                class="text-xs text-slate-600 dark:text-slate-300 underline hover:no-underline">
                                Clear filters
                            </button>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wide">
                                <tr>
                                    <th class="px-4 py-3 text-left">Time</th>
                                    <th class="px-4 py-3 text-left">User</th>
                                    <th class="px-4 py-3 text-left">Event</th>
                                    <th class="px-4 py-3 text-left">Module</th>
                                    <th class="px-4 py-3 text-left">Action / Page</th>
                                    <th class="px-4 py-3 text-left">IP</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($feedLogs as $log)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap text-xs">
                                            <span title="{{ $log->created_at }}">
                                                {{ $log->created_at->format('M d H:i:s') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 dark:text-gray-200">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-full bg-slate-600 text-white flex items-center justify-center text-xs font-semibold flex-shrink-0">
                                                    {{ strtoupper(substr($log->user?->name ?? '?', 0, 1)) }}
                                                </div>
                                                <div>
                                                    <p class="font-medium text-xs leading-tight">{{ $log->user?->name ?? 'Unknown' }}</p>
                                                    <p class="text-gray-400 text-xs leading-tight">{{ $log->user?->email ?? '' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $badgeColors = [
                                                    'login'            => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                                    'logout'           => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                                    'failed_login'     => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                                    'page_view'        => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                                    'component_action' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                                ];
                                                $color = $badgeColors[$log->event_type] ?? 'bg-gray-100 text-gray-600';
                                            @endphp
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                                                {{ ucwords(str_replace('_', ' ', $log->event_type)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-xs capitalize">
                                            {{ $log->module ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 dark:text-gray-300 text-xs max-w-xs">
                                            @if ($log->event_type === 'component_action')
                                                <p class="font-medium text-gray-800 dark:text-gray-100 truncate">
                                                    {{ $log->readable_action }}
                                                </p>
                                                <p class="text-gray-400 truncate" title="{{ $log->component_class }}">
                                                    {{ $log->readable_component }}
                                                </p>
                                            @elseif ($log->event_type === 'page_view')
                                                <p class="text-gray-700 dark:text-gray-300 truncate" title="{{ $log->url }}">
                                                    {{ $log->route_name ?: $log->url }}
                                                </p>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-400 dark:text-gray-500 text-xs font-mono whitespace-nowrap">
                                            {{ $log->ip_address ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                                            No activity logs found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($feedLogs && $feedLogs->hasPages())
                        <div class="px-4 py-3 border-t dark:border-gray-700">
                            {{ $feedLogs->links() }}
                        </div>
                    @endif
                </div>
            </details>
        </div>

    {{-- ───── WHO'S ACTIVE TAB ───── --}}
    @elseif ($tab === 'active')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700">
            <div class="p-4 border-b dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-green-400 animate-pulse inline-block"></span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Active in the last</span>
                    <select wire:model.live="onlineMinutes"
                        class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-slate-500 focus:outline-none">
                        <option value="5">5 minutes</option>
                        <option value="15">15 minutes</option>
                        <option value="30">30 minutes</option>
                        <option value="60">1 hour</option>
                    </select>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $onlineUsers?->count() ?? 0 }} person(s) active
                </p>
            </div>

            @if ($onlineUsers && $onlineUsers->isNotEmpty())
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($onlineUsers as $row)
                        @php
                            $u        = $allUsers->firstWhere('id', $row->user_id);
                            $name     = $u?->name ?? 'Unknown';
                            $email    = $u?->email ?? '';
                            $parts    = array_slice(explode(' ', $name), 0, 2);
                            $initials = strtoupper(implode('', array_map(fn ($p) => $p[0] ?? '', $parts)));
                            $module   = \App\Services\ActivityDescriptionService::moduleLabel($row->module);
                        @endphp
                        <div class="border dark:border-gray-700 rounded-lg p-4 space-y-2 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="relative flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-slate-600 text-white flex items-center justify-center text-sm font-semibold">
                                        {{ $initials }}
                                    </div>
                                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-green-400 ring-2 ring-white dark:ring-gray-800 animate-pulse"></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-800 dark:text-gray-100 text-sm truncate">{{ $name }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $email }}</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Currently in: <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ $module }}</span>
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Last seen {{ $row->created_at->diffForHumans() }}
                                · {{ $row->activity_count }} actions this session
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center text-gray-400 dark:text-gray-500">
                    <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    No one active in the last {{ $onlineMinutes }} minutes.
                </div>
            @endif
        </div>

    {{-- ───── STAFF ACTIVITY TAB ───── --}}
    @elseif ($tab === 'user')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700">
            <div class="p-4 border-b dark:border-gray-700">
                <div class="max-w-sm">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Staff Member</label>
                    <select wire:model.live="userTabUserId"
                        class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-500 focus:outline-none">
                        <option value="">— Choose a person —</option>
                        @foreach ($allUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if (!$userTabUserId)
                <div class="p-12 text-center text-gray-400 dark:text-gray-500">
                    Select a staff member above to see their activity.
                </div>
            @elseif ($timelineLogs && $timelineLogs->isEmpty())
                <div class="p-12 text-center text-gray-400 dark:text-gray-500">
                    No activity recorded for this person yet.
                </div>
            @else
                {{-- Summary Banner --}}
                @if ($activitySummary)
                    <div class="mx-4 mt-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4 space-y-1">
                        <p class="text-sm text-indigo-800 dark:text-indigo-200">{{ $activitySummary['login_sentence'] }}</p>
                        @if ($activitySummary['module_sentence'])
                            <p class="text-sm text-indigo-800 dark:text-indigo-200">{{ $activitySummary['module_sentence'] }}</p>
                        @endif
                        <p class="text-sm text-indigo-800 dark:text-indigo-200">{{ $activitySummary['total_sentence'] }}</p>
                    </div>
                @endif

                {{-- Timeline --}}
                @php
                    $grouped = $timelineLogs?->groupBy(fn ($log) => $log->created_at->toDateString()) ?? collect();
                @endphp

                <div class="p-6 space-y-8">
                    @foreach ($grouped as $date => $dayLogs)
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
                                <span class="text-xs font-normal bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                    {{ $dayLogs->count() }} events
                                </span>
                            </h4>

                            <div class="relative ml-2">
                                <div class="absolute left-3 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"></div>
                                <div class="space-y-3">
                                    @foreach ($dayLogs as $log)
                                        @php
                                            $dotColors = [
                                                'login'            => 'bg-green-400',
                                                'logout'           => 'bg-gray-400',
                                                'failed_login'     => 'bg-red-400',
                                                'page_view'        => 'bg-blue-400',
                                                'component_action' => 'bg-indigo-400',
                                            ];
                                            $dot = $dotColors[$log->event_type] ?? 'bg-gray-300';
                                        @endphp
                                        <div class="flex items-start gap-4 pl-8 relative">
                                            <div class="absolute left-1.5 top-1.5 w-3 h-3 rounded-full {{ $dot }} ring-2 ring-white dark:ring-gray-800 flex-shrink-0"></div>
                                            <div class="flex-1 min-w-0 flex items-start justify-between gap-2">
                                                <p class="text-sm text-gray-800 dark:text-gray-100 truncate">
                                                    {{ \App\Services\ActivityDescriptionService::describe($log) }}
                                                </p>
                                                <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap flex-shrink-0">
                                                    {{ $log->created_at->format('g:ia') }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    {{-- ───── ALERTS TAB ───── --}}
    @elseif ($tab === 'alerts')
        @if ($alerts && count($alerts) > 0)
            <div class="space-y-3">
                @foreach ($alerts as $alert)
                    @php
                        $isRed = $alert->severity === 'red';
                        $border  = $isRed ? 'border-l-4 border-red-500'   : 'border-l-4 border-amber-400';
                        $bg      = $isRed ? 'bg-red-50 dark:bg-red-900/10' : 'bg-amber-50 dark:bg-amber-900/10';
                        $title   = $isRed ? 'text-red-800 dark:text-red-300'   : 'text-amber-800 dark:text-amber-300';
                        $body    = $isRed ? 'text-red-700 dark:text-red-400'   : 'text-amber-700 dark:text-amber-400';
                        $badge   = $isRed ? 'Needs Attention' : 'Worth Watching';
                        $badgeCls = $isRed
                            ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
                    @endphp
                    <div class="rounded-lg shadow-sm {{ $border }} {{ $bg }} p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-semibold text-sm {{ $title }}">{{ $alert->title }}</p>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $badgeCls }}">{{ $badge }}</span>
                                </div>
                                <p class="text-sm {{ $body }}">{{ $alert->description }}</p>
                            </div>
                            <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap flex-shrink-0">{{ $alert->timestamp }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700 p-14 text-center">
                <svg class="w-12 h-12 mx-auto mb-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="font-semibold text-gray-700 dark:text-gray-200 text-lg">No unusual activity detected</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Everything looks normal for today.</p>
            </div>
        @endif
    @endif
</div>
