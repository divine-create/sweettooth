<div class="space-y-6">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">HR Dashboard</h1>
        <p class="text-gray-600 mt-2">Manage workforce, attendance, and leave requests</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 flex flex-wrap gap-4 items-center justify-between">
        <div class="flex gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                <select wire:model.debounce-500ms="selectedDepartment" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All Departments</option>
                    <option value="production">Production</option>
                    <option value="sales">Sales</option>
                    <option value="admin">Administration</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2">
            <button wire:click="refresh" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Refresh
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <!-- Total Employees -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Employees</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $totalEmployees ?? '0' }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v2h8v-2zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-2a4 4 0 00-8 0v2h8z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- On Duty Today -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">On Duty Today</p>
                    <p class="text-2xl font-bold text-green-600 mt-2">{{ $onDuty ?? '0' }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- On Leave Today -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">On Leave Today</p>
                    <p class="text-2xl font-bold text-orange-600 mt-2">{{ $onLeave ?? '0' }}</p>
                </div>
                <div class="p-3 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v2h16V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5H4v8a2 2 0 002 2h8a2 2 0 002-2V7h-2v1a1 1 0 11-2 0V7H8v1a1 1 0 11-2 0V7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending Requests -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Pending Requests</p>
                    <p class="text-2xl font-bold text-red-600 mt-2">{{ $pendingRequests ?? '0' }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0zm3 1a1 1 0 100-2 1 1 0 000 2zm3-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Attendance % -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Attendance %</p>
                    <p class="text-2xl font-bold text-purple-600 mt-2">{{ $attendancePercentage ?? '0%' }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2 1 1 0 010 2zM15 7a1 1 0 110-2 1 1 0 010 2zM9 7a1 1 0 110-2 1 1 0 010 2zM9 11a1 1 0 110-2 1 1 0 010 2zM15 11a1 1 0 110-2 1 1 0 010 2zM12 11a1 1 0 110-2 1 1 0 010 2zM9 15a1 1 0 110-2 1 1 0 010 2zM15 15a1 1 0 110-2 1 1 0 010 2zM12 15a1 1 0 110-2 1 1 0 010 2z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Access</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ branch_route('branch-dashboard.hr.reports.workforce-overview') }}"
               class="flex items-center justify-between p-4 border border-emerald-200 rounded-lg hover:bg-emerald-50 transition">
                <div>
                    <p class="text-sm text-emerald-700 font-medium">HR Workforce Report</p>
                    <p class="text-xs text-emerald-600 mt-1">Generate and review workforce insights</p>
                </div>
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Department Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Department Breakdown</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Production</span>
                        <span class="text-sm font-bold text-gray-900">45 employees</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: 45%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Sales</span>
                        <span class="text-sm font-bold text-gray-900">32 employees</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: 32%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Administration</span>
                        <span class="text-sm font-bold text-gray-900">23 employees</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: 23%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Upcoming Events</h3>
            <div class="space-y-3">
                <div class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-600">
                    <p class="text-sm font-medium text-gray-900">Team Meeting</p>
                    <p class="text-xs text-gray-600 mt-1">Tomorrow, 10:00 AM</p>
                </div>
                <div class="p-3 bg-green-50 rounded-lg border-l-4 border-green-600">
                    <p class="text-sm font-medium text-gray-900">Performance Review</p>
                    <p class="text-xs text-gray-600 mt-1">Dec 15, 2025</p>
                </div>
                <div class="p-3 bg-orange-50 rounded-lg border-l-4 border-orange-600">
                    <p class="text-sm font-medium text-gray-900">Training Session</p>
                    <p class="text-xs text-gray-600 mt-1">Dec 20, 2025</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Leave Requests -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Pending Leave Requests</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-900">Employee</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-900">Leave Type</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-900">From - To</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-900">Days</th>
                        <th class="px-4 py-2 text-center font-medium text-gray-900">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-900">John Smith</td>
                        <td class="px-4 py-3 text-gray-600">Annual Leave</td>
                        <td class="px-4 py-3 text-gray-600">Dec 20 - Dec 27</td>
                        <td class="px-4 py-3 text-gray-600">5</td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <button class="text-green-600 hover:underline text-xs font-medium">Approve</button>
                            <button class="text-red-600 hover:underline text-xs font-medium">Reject</button>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-900">Maria Garcia</td>
                        <td class="px-4 py-3 text-gray-600">Sick Leave</td>
                        <td class="px-4 py-3 text-gray-600">Dec 12 - Dec 13</td>
                        <td class="px-4 py-3 text-gray-600">2</td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <button class="text-green-600 hover:underline text-xs font-medium">Approve</button>
                            <button class="text-red-600 hover:underline text-xs font-medium">Reject</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
