<div class="p-3 space-y-3">
    <x-breadcrumb
        title="Shift Management"
        :items="[
            ['label' => 'Dashboard', 'url' => route('branch-dashboard.index')],
            ['label' => 'Shift Management']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Header -->
    <div class="flex justify-end items-center gap-4">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Branch: <strong class="text-zinc-900 dark:text-white">{{ $branch->name ?? 'All' }}</strong></p>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Last refreshed: <span id="refresh-time" class="text-zinc-900 dark:text-white">now</span></p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['total_configs'] }}</div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">Shift Types</div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['today_active'] }}</div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">Active Today</div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['today_total'] }}</div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">Total Today</div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['upcoming_shifts'] }}</div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">Upcoming</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <a href="{{ route('branch-dashboard.shift-management.configuration', ['b_id' => $b_id]) }}" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 block hover:shadow-md transition-shadow">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Configure Shifts</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">Define shift types, times, and rules</p>
            <button class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Configure
            </button>
        </a>

        <a href="{{ route('branch-dashboard.shift-management.assignment', ['b_id' => $b_id]) }}" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 block hover:shadow-md transition-shadow">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Assign Shifts</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">Assign employees to specific shifts</p>
            <button class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Assign
            </button>
        </a>

    </div>

    <!-- Recent Configurations -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Recent Shift Configurations</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Start Time</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">End Time</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($recentConfigs as $config)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-750">
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">{{ $config->name }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 rounded text-xs font-semibold">
                                    {{ ucfirst($config->shift_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">{{ $config->start_time }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">{{ $config->end_time }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($config->is_active)
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">Active</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                No shift configurations found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Today's Shifts -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Today's Shifts</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Employee</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Shift Type</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Clock In</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Clock Out</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($todaysShifts as $shift)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-750">
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $shift->employee->name }}</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $shift->employee->employee_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 rounded text-xs font-semibold">
                                    {{ ucfirst($shift->shift_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">{{ $shift->clock_in ? $shift->clock_in->format('H:i') : '-' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">{{ $shift->clock_out ? $shift->clock_out->format('H:i') : '-' }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($shift->status === 'active')
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">{{ ucfirst($shift->status) }}</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ ucfirst($shift->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                No shifts recorded for today
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-refresh every 30 seconds
    document.addEventListener('livewire:initialized', () => {
        const refreshElement = document.getElementById('refresh-time');
        if (refreshElement) {
            setInterval(() => {
                Livewire.dispatch('$refresh');
                refreshElement.textContent = new Date().toLocaleTimeString();
            }, 30000);
        }
    });
</script>
@endpush
