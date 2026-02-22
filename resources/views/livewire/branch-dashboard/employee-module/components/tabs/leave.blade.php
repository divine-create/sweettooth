<div x-show="activeTab === 'leave'" class="tab-content space-y-6">
    <!-- Leave Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Total Allocated</p>
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">{{ number_format($leaveStats['total_allocated'], 1) }}</p>
                </div>
                <svg class="w-10 h-10 text-blue-600/30 dark:text-blue-400/30" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase">Used</p>
                    <p class="text-2xl font-bold text-red-700 dark:text-red-300 mt-1">{{ number_format($leaveStats['total_used'], 1) }}</p>
                </div>
                <svg class="w-10 h-10 text-red-600/30 dark:text-red-400/30" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/30 dark:to-yellow-800/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-yellow-600 dark:text-yellow-400 uppercase">Pending</p>
                    <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300 mt-1">{{ number_format($leaveStats['total_pending'], 1) }}</p>
                </div>
                <svg class="w-10 h-10 text-yellow-600/30 dark:text-yellow-400/30" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase">Remaining</p>
                    <p class="text-2xl font-bold text-green-700 dark:text-green-300 mt-1">{{ number_format($leaveStats['total_remaining'], 1) }}</p>
                </div>
                <svg class="w-10 h-10 text-green-600/30 dark:text-green-400/30" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Leave Balance by Type -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Leave Balance by Type ({{ now()->year }})</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($leaveBalances as $balance)
                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-3 h-3 rounded" style="background-color: {{ $balance->leaveType->color }}"></div>
                        <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $balance->leaveType->name }}</h4>
                    </div>
                    <div class="grid grid-cols-4 gap-2 text-center text-xs">
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">Total</p>
                            <p class="font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($balance->total_days, 1) }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">Used</p>
                            <p class="font-bold text-red-600 dark:text-red-400">{{ number_format($balance->used_days, 1) }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">Pending</p>
                            <p class="font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($balance->pending_days, 1) }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">Left</p>
                            <p class="font-bold text-green-600 dark:text-green-400">{{ number_format($balance->remaining_days, 1) }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-8 text-zinc-500 dark:text-zinc-400">
                    No leave balances found for {{ now()->year }}
                </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Leave Applications -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Recent Leave Applications</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Application #</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Leave Type</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Dates</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Days</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($recentLeaveApplications as $application)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $application->application_number }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded" style="background-color: {{ $application->leaveType->color }}"></div>
                                    <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $application->leaveType->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $application->start_date->format('M d') }} - {{ $application->end_date->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($application->total_days, 1) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $application->status_badge_class }}">
                                    {{ ucfirst($application->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No leave applications found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>