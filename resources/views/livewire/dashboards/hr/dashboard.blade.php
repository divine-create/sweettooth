<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-40">
        <div class="px-6 py-4">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">HR Dashboard</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Employee management and attendance overview</p>
        </div>
    </div>

    <div class="p-6 space-y-6">
        <!-- Alerts -->
        @if(!empty($hrAlerts))
            <div class="space-y-3">
                @foreach($hrAlerts as $alert)
                    <x-dashboard.alert-box :type="$alert['type']" :title="$alert['title']">
                        {{ $alert['message'] }}
                    </x-dashboard.alert-box>
                @endforeach
            </div>
        @endif

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <x-dashboard.metric-card title="Total Employees" :value="$totalEmployees" color="blue" format="number" />
            <x-dashboard.metric-card title="On Duty Today" :value="$onDutyToday" color="green" format="number" />
            <x-dashboard.metric-card title="On Leave" :value="$onLeaveToday" color="yellow" format="number" />
            <x-dashboard.metric-card title="Attendance" :value="$attendancePercentage" color="purple" format="percentage" />
            <x-dashboard.metric-card title="Pending Leaves" :value="$pendingLeaveRequests" color="red" format="number" />
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Employees by Department -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">By Department</h2>
                <div class="space-y-3">
                    @forelse($employeesByDepartment as $dept)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $dept->name }}</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded-full text-sm font-semibold">{{ $dept->count }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">No departments</p>
                    @endforelse
                </div>
            </div>

            <!-- Pending Leave Requests (2 cols) -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Pending Leave Requests</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="text-left py-3 px-4 font-semibold">Employee</th>
                                    <th class="text-left py-3 px-4 font-semibold">Type</th>
                                    <th class="text-left py-3 px-4 font-semibold">Date</th>
                                    <th class="text-left py-3 px-4 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingLeaves as $leave)
                                    <tr class="border-b border-zinc-100 dark:border-zinc-700">
                                        <td class="py-3 px-4 font-medium">{{ $leave->employee_name ?? 'N/A' }}</td>
                                        <td class="py-3 px-4">{{ $leave->leave_type ?? 'N/A' }}</td>
                                        <td class="py-3 px-4">{{ Carbon\Carbon::parse($leave->date)->format('M d') }}</td>
                                        <td class="py-3 px-4"><span class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 text-xs font-semibold rounded-full">Pending</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 px-4 text-center text-zinc-600 dark:text-zinc-400">No pending requests</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
