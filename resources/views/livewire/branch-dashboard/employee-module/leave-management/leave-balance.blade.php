<div class="p-3 space-y-3">
    <x-breadcrumb title="Leave Balance" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Leave Management'],
        ['label' => 'Leave Balance'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-teal-600 to-teal-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Leave Balance</h2>
                <p class="text-sm opacity-90 mt-1">
                    View your leave allocations and usage
                </p>
            </div>
            <div class="flex items-center gap-3">
                <!-- Year Selector -->
                <select wire:model.live="selectedYear"
                    class="px-3 py-2 bg-white text-teal-700 rounded-lg font-medium border-0 focus:ring-2 focus:ring-teal-300">
                    @foreach($yearOptions as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
                <a href="{{ branch_route('leave.apply') }}"
                    class="px-4 py-2 bg-white text-teal-600 rounded-lg font-medium hover:bg-teal-50 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Apply Leave
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Total Allocated</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                        {{ number_format($totalAllocated, 1) }}
                    </p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Used</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                        {{ number_format($totalUsed, 1) }}
                    </p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                        {{ number_format($totalPending, 1) }}
                    </p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Remaining</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                        {{ number_format($totalRemaining, 1) }}
                    </p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Balance by Leave Type -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Leave Balance by Type</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($balances as $balance)
                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded" style="background-color: {{ $balance->leaveType->color }}"></div>
                            <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $balance->leaveType->name }}</h4>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full {{ $balance->leaveType->is_paid ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' }}">
                            {{ $balance->leaveType->is_paid ? 'Paid' : 'Unpaid' }}
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-3">
                        @php
                            $usedPercentage = $balance->total_days > 0 ? ($balance->used_days / $balance->total_days) * 100 : 0;
                            $pendingPercentage = $balance->total_days > 0 ? ($balance->pending_days / $balance->total_days) * 100 : 0;
                        @endphp
                        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 overflow-hidden">
                            <div class="flex h-full">
                                <div class="bg-red-500" style="width: {{ $usedPercentage }}%"></div>
                                <div class="bg-yellow-500" style="width: {{ $pendingPercentage }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="grid grid-cols-4 gap-2 text-center">
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Total</p>
                            <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($balance->total_days, 1) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Used</p>
                            <p class="text-sm font-bold text-red-600 dark:text-red-400">{{ number_format($balance->used_days, 1) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Pending</p>
                            <p class="text-sm font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($balance->pending_days, 1) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Remaining</p>
                            <p class="text-sm font-bold text-green-600 dark:text-green-400">{{ number_format($balance->remaining_days, 1) }}</p>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    @if($balance->leaveType->max_consecutive_days)
                        <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700 text-xs text-zinc-600 dark:text-zinc-400">
                            Max consecutive: {{ $balance->leaveType->max_consecutive_days }} days
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full text-center py-8">
                    <svg class="w-12 h-12 text-zinc-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-zinc-500 dark:text-zinc-400">No leave balances found for {{ $selectedYear }}</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Leave History -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Leave History ({{ $selectedYear }})</h3>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700 border-b border-zinc-200 dark:border-zinc-600">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase">Application #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase">Leave Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase">Dates</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase">Days</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($leaveHistory as $leave)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $leave->application_number }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $leave->created_at->format('M d, Y') }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded" style="background-color: {{ $leave->leaveType->color }}"></div>
                                    <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $leave->leaveType->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $leave->start_date->format('M d') }} - {{ $leave->end_date->format('M d, Y') }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($leave->total_days, 1) }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $leave->status_badge_class }}">
                                    {{ ucfirst($leave->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center">
                                <p class="text-zinc-500 dark:text-zinc-400">No leave history for {{ $selectedYear }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
