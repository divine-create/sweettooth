<div>
    <x-breadcrumb title="Organization Helper" :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Organization Helper']]" :compact="false" :with-icons="true" />

    {{-- Stats Overview --}}
    <div class="max-w-7xl mx-auto py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Employee Stats --}}
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Employees</p>
                        <p class="text-3xl font-bold mt-1">{{ $employeeStats['total_employees'] }}</p>
                        <div class="mt-2 text-xs text-blue-100">
                            <span class="inline-block mr-2">✓ {{ $employeeStats['active_employees'] }} Active</span>
                            <span class="inline-block">📅 {{ $employeeStats['on_probation'] }} Probation</span>
                        </div>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center text-3xl">
                        👥
                    </div>
                </div>
            </div>

            {{-- Shift Stats --}}
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm font-medium">Today's Shifts</p>
                        <p class="text-3xl font-bold mt-1">{{ $shiftStats['today_active'] }}/{{ $shiftStats['today_total'] }}</p>
                        <div class="mt-2 text-xs text-orange-100">
                            <span class="inline-block mr-2">⚙️ {{ $shiftStats['total_configs'] }} Configs</span>
                            <span class="inline-block">📅 {{ $shiftStats['upcoming_shifts'] }} Upcoming</span>
                        </div>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center text-3xl">
                        ⏰
                    </div>
                </div>
            </div>

            {{-- Leave Stats --}}
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Leave Requests</p>
                        <p class="text-3xl font-bold mt-1">{{ $leaveStats['pending_applications'] }}</p>
                        <div class="mt-2 text-xs text-green-100">
                            <span class="inline-block mr-2">⏳ Pending</span>
                            <span class="inline-block">✅ {{ $leaveStats['approved_this_year'] }} Approved</span>
                        </div>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center text-3xl">
                        📝
                    </div>
                </div>
            </div>

            {{-- Performance Stats --}}
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Performance Goals</p>
                        <p class="text-3xl font-bold mt-1">{{ $performanceStats['total_goals'] }}</p>
                        <div class="mt-2 text-xs text-purple-100">
                            <span class="inline-block mr-2">✓ {{ $performanceStats['completed_goals'] }} Done</span>
                            <span class="inline-block">🔄 {{ $performanceStats['in_progress_goals'] }} In Progress</span>
                        </div>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center text-3xl">
                        🎯
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h1 class="ml-2 text-2xl font-medium text-gray-900 dark:text-white">
                        Organization Workflow Guide
                    </h1>
                </div>
                <p class="mt-6 text-gray-500 dark:text-gray-400 leading-relaxed">
                    This comprehensive guide explains how to use all Organization features including Employee Management, Shift Management, Leave Management, and Performance Management. Each section includes step-by-step workflows with visual aids.
                </p>
            </div>

            {{-- ===================== EMPLOYEE MANAGEMENT SECTION ===================== --}}
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <div class="flex items-center mb-4">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h2 class="ml-2 text-xl font-semibold text-gray-900 dark:text-white">Employee Management</h2>
                </div>

                {{-- Employee Lifecycle Illustration --}}
                <div class="mb-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3 text-center">Employee Lifecycle</h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center text-white mb-2">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                            </div>
                            <div class="font-semibold text-sm">Hire</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300">Create profile</div>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center text-white mb-2">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="font-semibold text-sm">Onboard</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300">Assign role</div>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-orange-500 rounded-full flex items-center justify-center text-white mb-2">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="font-semibold text-sm">Work</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300">Clock in/out</div>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-purple-500 rounded-full flex items-center justify-center text-white mb-2">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div class="font-semibold text-sm">Manage</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300">Leave & reviews</div>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-red-500 rounded-full flex items-center justify-center text-white mb-2">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </div>
                            <div class="font-semibold text-sm">Offboard</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300">Termination</div>
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {{-- All Employees --}}
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 border-t-4 border-indigo-500">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-indigo-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">All Employees</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• View employee list with details</div>
                            <div>• Advanced search and filtering</div>
                            <div>• Bulk operations and exports</div>
                            <div>• Department and role overview</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="text-center">
                                    <div class="font-bold text-green-600">👥</div>
                                    <div>Active Staff</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-bold text-blue-600">📊</div>
                                    <div>Analytics</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Create Employee --}}
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 border-t-4 border-green-500">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Create Employee</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• Complete employee information</div>
                            <div>• Upload profile photo</div>
                            <div>• Set compensation details</div>
                            <div>• Assign department and role</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2">
                            <div class="text-xs font-semibold mb-2">📋 Form Sections:</div>
                            <div class="grid grid-cols-2 gap-1 text-xs">
                                <div>👤 Personal</div>
                                <div>💼 Employment</div>
                                <div>💰 Salary</div>
                                <div>🔐 Access</div>
                            </div>
                        </div>
                    </div>

                    {{-- Employee Details --}}
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 border-t-4 border-blue-500">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Employee Details</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• Personal information tab</div>
                            <div>• Employment history</div>
                            <div>• Leave balances & history</div>
                            <div>• Performance metrics</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <div class="text-xs font-semibold mb-2">📊 Tabs:</div>
                            <div class="grid grid-cols-2 gap-1 text-xs">
                                <div>👤 Personal</div>
                                <div>💼 Employment</div>
                                <div>💰 Finance</div>
                                <div>📝 Leave</div>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Employee --}}
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 border-t-4 border-yellow-500">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Edit Employee</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• Update personal details</div>
                            <div>• Modify employment info</div>
                            <div>• Change department/role</div>
                            <div>• Approval workflow for changes</div>
                        </div>
                        <div class="bg-yellow-100 dark:bg-yellow-900/30 rounded p-2 text-xs">
                            <strong>⚠️ Note:</strong> Non-super admins require approval for employee updates
                        </div>
                    </div>
                </div>

                {{-- Role Management & Clock-In --}}
                <div class="grid md:grid-cols-2 gap-6 mt-6">
                    {{-- Roles & Permissions --}}
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 border-l-4 border-red-500">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Roles & Permissions</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• Define user roles and permissions</div>
                            <div>• Set access control levels</div>
                            <div>• Configure security policies</div>
                            <div>• Manage role hierarchies</div>
                        </div>
                        <div class="bg-red-100 dark:bg-red-900/30 rounded p-2 text-xs">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <strong>Super Admin Only</strong>
                            </div>
                        </div>
                        <div class="mt-2 bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <strong>🔑 Permission Types:</strong>
                            <div class="grid grid-cols-2 gap-1 mt-1">
                                <div>• View Access</div>
                                <div>• Edit Rights</div>
                                <div>• Delete Permissions</div>
                                <div>• Admin Controls</div>
                            </div>
                        </div>
                    </div>

                    {{-- Clock-In Board --}}
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 border-l-4 border-orange-500">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Clock-In Board</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• Real-time attendance tracking</div>
                            <div>• Shift start/end times</div>
                            <div>• Break time management</div>
                            <div>• Work hour calculations</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2">
                            <div class="text-xs font-semibold mb-2">⏰ Daily Flow:</div>
                            <div class="space-y-1 text-xs">
                                <div class="flex items-center">
                                    <span class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center text-white text-xs mr-2">✓</span>
                                    Clock In
                                </div>
                                <div class="flex items-center">
                                    <span class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs mr-2">✓</span>
                                    Work Period
                                </div>
                                <div class="flex items-center">
                                    <span class="w-6 h-6 bg-orange-500 rounded-full flex items-center justify-center text-white text-xs mr-2">✓</span>
                                    Clock Out
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== SHIFT MANAGEMENT SECTION ===================== --}}
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <div class="flex items-center mb-4">
                    <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h2 class="ml-2 text-xl font-semibold text-gray-900 dark:text-white">Shift Management</h2>
                </div>

                {{-- Shift Workflow Illustration --}}
                <div class="mb-6 bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3 text-center">Shift Management Workflow</h3>
                    <div class="flex flex-col md:flex-row items-center justify-center space-y-4 md:space-y-0 md:space-x-4">
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg mb-2">1</div>
                            <div class="text-center">
                                <div class="font-semibold text-sm">Configure Shift</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">Define times & rules</div>
                            </div>
                        </div>
                        <svg class="w-8 h-8 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white font-bold text-lg mb-2">2</div>
                            <div class="text-center">
                                <div class="font-semibold text-sm">Assign Employees</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">Schedule shifts</div>
                            </div>
                        </div>
                        <svg class="w-8 h-8 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white font-bold text-lg mb-2">3</div>
                            <div class="text-center">
                                <div class="font-semibold text-sm">Clock In/Out</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">Track attendance</div>
                            </div>
                        </div>
                        <svg class="w-8 h-8 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold text-lg mb-2">4</div>
                            <div class="text-center">
                                <div class="font-semibold text-sm">Monitor & Report</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">Track hours</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    {{-- Shift Configuration --}}
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 border-t-4 border-blue-500">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Shift Configuration</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• Create shift templates (Morning, Afternoon, Night)</div>
                            <div>• Set start/end times</div>
                            <div>• Configure clock-in windows</div>
                            <div>• Define break durations</div>
                            <div>• Set overtime rules</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <strong>⚙️ Configuration Fields:</strong>
                            <ul class="mt-1 space-y-1">
                                <li>• Shift name & type</li>
                                <li>• Start/End times</li>
                                <li>• Clock-in window</li>
                                <li>• Auto clock-out (min)</li>
                                <li>• Max overtime hours</li>
                                <li>• Break duration</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Shift Assignment --}}
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 border-t-4 border-green-500">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Shift Assignment</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• Assign shifts to employees</div>
                            <div>• Schedule by date</div>
                            <div>• Filter by department</div>
                            <div>• View assigned shifts</div>
                            <div>• Remove assignments</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <strong>📋 Assignment Process:</strong>
                            <ol class="mt-1 space-y-1 list-decimal list-inside">
                                <li>Select employee</li>
                                <li>Choose shift date</li>
                                <li>Pick shift configuration</li>
                                <li>Select department</li>
                                <li>Add notes (optional)</li>
                            </ol>
                        </div>
                    </div>

                    {{-- Shift Monitoring --}}
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 border-t-4 border-purple-500">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Shift Monitoring</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• View today's active shifts</div>
                            <div>• Track clock-in/clock-out</div>
                            <div>• Monitor shift status</div>
                            <div>• View upcoming schedules</div>
                            <div>• Generate shift reports</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <strong>📊 Shift Statuses:</strong>
                            <div class="grid grid-cols-2 gap-1 mt-1">
                                <div class="text-blue-600">📅 Scheduled</div>
                                <div class="text-green-600">✓ Active</div>
                                <div class="text-orange-600">⏸️ On Break</div>
                                <div class="text-gray-600">✓ Completed</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Shift Types Info --}}
                <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
                    <div class="flex items-center mb-3">
                        <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Shift Types</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Standard shift configurations for different work periods</p>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div class="bg-white dark:bg-zinc-600 rounded p-3 text-center">
                            <div class="text-2xl mb-1">🌅</div>
                            <div class="font-semibold text-sm">Morning</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300">6:00 AM - 2:00 PM</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-3 text-center">
                            <div class="text-2xl mb-1">☀️</div>
                            <div class="font-semibold text-sm">Afternoon</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300">2:00 PM - 10:00 PM</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-3 text-center">
                            <div class="text-2xl mb-1">🌙</div>
                            <div class="font-semibold text-sm">Night</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300">10:00 PM - 6:00 AM</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-3 text-center">
                            <div class="text-2xl mb-1">⏱️</div>
                            <div class="font-semibold text-sm">Full Time</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300">Standard hours</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== LEAVE MANAGEMENT SECTION ===================== --}}
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <div class="flex items-center mb-4">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h2 class="ml-2 text-xl font-semibold text-gray-900 dark:text-white">Leave Management</h2>
                </div>

                {{-- Leave Request Flow Illustration --}}
                <div class="mb-6 bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3 text-center">Leave Request Process Flow</h3>
                    <div class="flex flex-col lg:flex-row items-center justify-center space-y-4 lg:space-y-0 lg:space-x-6">
                        {{-- Employee Side --}}
                        <div class="flex flex-col items-center">
                            <div class="text-lg font-semibold text-blue-600 mb-2">👤 Employee</div>
                            <div class="flex flex-col space-y-2">
                                <div class="flex items-center bg-white dark:bg-zinc-700 rounded-lg p-3 shadow">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm mr-3">1</div>
                                    <div>
                                        <div class="font-semibold text-sm">Apply for Leave</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-300">Select dates & reason</div>
                                    </div>
                                </div>
                                <div class="flex items-center bg-white dark:bg-zinc-700 rounded-lg p-3 shadow">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm mr-3">3</div>
                                    <div>
                                        <div class="font-semibold text-sm">Receive Response</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-300">Approved/Rejected</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Arrow --}}
                        <div class="hidden lg:block">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l3-3m0 0l3 3m-3-3v6m4-13a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>

                        {{-- Manager Side --}}
                        <div class="flex flex-col items-center">
                            <div class="text-lg font-semibold text-green-600 mb-2">👔 Manager</div>
                            <div class="flex flex-col space-y-2">
                                <div class="flex items-center bg-white dark:bg-zinc-700 rounded-lg p-3 shadow">
                                    <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-white text-sm mr-3">2</div>
                                    <div>
                                        <div class="font-semibold text-sm">Review Request</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-300">Check balance & dates</div>
                                    </div>
                                </div>
                                <div class="flex items-center bg-white dark:bg-zinc-700 rounded-lg p-3 shadow">
                                    <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white text-sm mr-3">4</div>
                                    <div>
                                        <div class="font-semibold text-sm">Update Status</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-300">Approve/Reject with notes</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {{-- Apply Leave --}}
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Apply Leave</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• Select leave type</div>
                            <div>• Choose start/end dates</div>
                            <div>• Provide reason</div>
                            <div>• Upload supporting documents</div>
                            <div>• Auto-calculate working days</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <strong>📋 Required Fields:</strong>
                            <ul class="mt-1 space-y-1">
                                <li>• Leave type</li>
                                <li>• Start date</li>
                                <li>• End date</li>
                                <li>• Reason (min 10 chars)</li>
                                <li>• Emergency contact</li>
                            </ul>
                        </div>
                    </div>

                    {{-- My Leaves --}}
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 rounded-lg p-4 border border-green-200 dark:border-green-700">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h6a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">My Leaves</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• View all leave requests</div>
                            <div>• Track application status</div>
                            <div>• View leave history</div>
                            <div>• Cancel pending/approved leaves</div>
                            <div>• Download supporting docs</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <strong>📊 Status Types:</strong>
                            <div class="grid grid-cols-2 gap-1 mt-1">
                                <div class="text-yellow-600">⏳ Pending</div>
                                <div class="text-green-600">✅ Approved</div>
                                <div class="text-red-600">❌ Rejected</div>
                                <div class="text-blue-600">📅 Scheduled</div>
                            </div>
                        </div>
                    </div>

                    {{-- Leave Balance --}}
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Leave Balance</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• View allocated leave days</div>
                            <div>• Track used vs remaining</div>
                            <div>• Check pending requests</div>
                            <div>• Year-over-year comparison</div>
                            <div>• Leave type breakdown</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <strong>📈 Balance Metrics:</strong>
                            <ul class="mt-1 space-y-1">
                                <li>• Total allocated</li>
                                <li>• Total used</li>
                                <li>• Total pending</li>
                                <li>• Total remaining</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Approve Leaves --}}
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 rounded-lg p-4 border border-orange-200 dark:border-orange-700">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Approve Leaves</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• Review pending requests</div>
                            <div>• Check employee leave balance</div>
                            <div>• Approve with notes</div>
                            <div>• Reject with reason</div>
                            <div>• View application details</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <strong>⚡ Quick Actions:</strong>
                            <div class="grid grid-cols-2 gap-1 mt-1">
                                <div class="text-green-600">✓ Approve</div>
                                <div class="text-red-600">✗ Reject</div>
                                <div class="text-blue-600">💬 Comment</div>
                                <div class="text-purple-600">📋 Details</div>
                            </div>
                        </div>
                    </div>

                    {{-- Leave Types --}}
                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/30 dark:to-indigo-800/30 rounded-lg p-4 border border-indigo-200 dark:border-indigo-700">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-indigo-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Leave Types</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• Create custom leave types</div>
                            <div>• Set default days per year</div>
                            <div>• Configure approval rules</div>
                            <div>• Set document requirements</div>
                            <div>• Define notice periods</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <strong>⚙️ Configuration:</strong>
                            <ul class="mt-1 space-y-1">
                                <li>• Name & code</li>
                                <li>• Default days/year</li>
                                <li>• Requires approval</li>
                                <li>• Requires document</li>
                                <li>• Max consecutive days</li>
                                <li>• Min notice period</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Manage Allocations --}}
                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 dark:from-pink-900/30 dark:to-pink-800/30 rounded-lg p-4 border border-pink-200 dark:border-pink-700">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-pink-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Manage Allocations</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• Allocate leave to employees</div>
                            <div>• Set custom days per type</div>
                            <div>• Bulk allocate defaults</div>
                            <div>• Track allocation history</div>
                            <div>• Year-based management</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <strong>📊 Allocation Process:</strong>
                            <ol class="mt-1 space-y-1 list-decimal list-inside">
                                <li>Select employee</li>
                                <li>Choose year</li>
                                <li>Set days per type</li>
                                <li>Add notes</li>
                                <li>Save allocations</li>
                            </ol>
                        </div>
                    </div>
                </div>

                {{-- Leave Types Reference --}}
                <div class="mt-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
                    <div class="flex items-center mb-3">
                        <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Common Leave Types</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Standard leave categories available in the system</p>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div class="bg-white dark:bg-zinc-600 rounded p-3">
                            <div class="font-semibold text-sm text-blue-600">Annual Leave</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">Paid vacation time for rest and recreation</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-3">
                            <div class="font-semibold text-sm text-green-600">Sick Leave</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">Medical leave with doctor's note</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-3">
                            <div class="font-semibold text-sm text-orange-600">Emergency Leave</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">Unplanned urgent matters</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-3">
                            <div class="font-semibold text-sm text-purple-600">Maternity/Paternity</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">Parental leave for new parents</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== PERFORMANCE MANAGEMENT SECTION ===================== --}}
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <div class="flex items-center mb-4">
                    <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <h2 class="ml-2 text-xl font-semibold text-gray-900 dark:text-white">Performance Management</h2>
                </div>

                {{-- Performance Cycle Illustration --}}
                <div class="mb-6 bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3 text-center">Performance Review Cycle</h3>
                    <div class="flex flex-col md:flex-row items-center justify-center space-y-4 md:space-y-0 md:space-x-4">
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg mb-2">1</div>
                            <div class="text-center">
                                <div class="font-semibold text-sm">Set Goals</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">Define objectives</div>
                            </div>
                        </div>
                        <svg class="w-8 h-8 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white font-bold text-lg mb-2">2</div>
                            <div class="text-center">
                                <div class="font-semibold text-sm">Self Assessment</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">Employee review</div>
                            </div>
                        </div>
                        <svg class="w-8 h-8 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white font-bold text-lg mb-2">3</div>
                            <div class="text-center">
                                <div class="font-semibold text-sm">Manager Review</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">Evaluation</div>
                            </div>
                        </div>
                        <svg class="w-8 h-8 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold text-lg mb-2">4</div>
                            <div class="text-center">
                                <div class="font-semibold text-sm">HR Calibration</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">Final approval</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    {{-- Performance Goals --}}
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 border-t-4 border-purple-500">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Performance Goals</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• Create individual/team goals</div>
                            <div>• Set target values & deadlines</div>
                            <div>• Track progress in real-time</div>
                            <div>• Update goal status</div>
                            <div>• Categorize by type</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <strong>📊 Goal Categories:</strong>
                            <div class="grid grid-cols-2 gap-1 mt-1">
                                <div>• Individual</div>
                                <div>• Team</div>
                                <div>• Operational</div>
                                <div>• Strategic</div>
                            </div>
                            <strong class="mt-2 block">🎯 Priority Levels:</strong>
                            <div class="grid grid-cols-3 gap-1 mt-1">
                                <div class="text-red-600">High</div>
                                <div class="text-yellow-600">Medium</div>
                                <div class="text-green-600">Low</div>
                            </div>
                        </div>
                    </div>

                    {{-- Employee Appraisals --}}
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 border-t-4 border-green-500">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Employee Appraisals</h3>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-3">
                            <div>• Conduct performance reviews</div>
                            <div>• Rate employee performance (1-5)</div>
                            <div>• Document strengths & areas</div>
                            <div>• Set development goals</div>
                            <div>• Track appraisal history</div>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-2 text-xs">
                            <strong>⭐ Rating Scale:</strong>
                            <div class="grid grid-cols-5 gap-1 mt-1 text-center">
                                <div class="bg-red-100 text-red-600 rounded p-1">1<br><span class="text-xs">Poor</span></div>
                                <div class="bg-orange-100 text-orange-600 rounded p-1">2<br><span class="text-xs">Fair</span></div>
                                <div class="bg-yellow-100 text-yellow-600 rounded p-1">3<br><span class="text-xs">Good</span></div>
                                <div class="bg-blue-100 text-blue-600 rounded p-1">4<br><span class="text-xs">Very Good</span></div>
                                <div class="bg-green-100 text-green-600 rounded p-1">5<br><span class="text-xs">Excellent</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Appraisal History --}}
                <div class="mt-6 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
                    <div class="flex items-center mb-3">
                        <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Appraisal History</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Complete performance review timeline for each employee</p>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-600 rounded p-4">
                        <div class="grid md:grid-cols-3 gap-4">
                            <div>
                                <h4 class="font-semibold text-sm mb-2">📋 Appraisal Details:</h4>
                                <ul class="text-xs text-gray-600 dark:text-gray-300 space-y-1">
                                    <li>• Appraisal cycle name</li>
                                    <li>• Review dates</li>
                                    <li>• Final rating</li>
                                    <li>• Manager comments</li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-semibold text-sm mb-2">📈 Development Plan:</h4>
                                <ul class="text-xs text-gray-600 dark:text-gray-300 space-y-1">
                                    <li>• Key strengths</li>
                                    <li>• Areas for improvement</li>
                                    <li>• Future goals</li>
                                    <li>• Training needs</li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-semibold text-sm mb-2">📊 Status Tracking:</h4>
                                <ul class="text-xs text-gray-600 dark:text-gray-300 space-y-1">
                                    <li>• Draft</li>
                                    <li>• Self Review</li>
                                    <li>• Manager Review</li>
                                    <li>• Completed</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== WORKFLOW SUMMARY ===================== --}}
            <div class="p-6 bg-gray-50 dark:bg-zinc-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">🎯 Quick Reference Guide</h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                            <span class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs mr-2">1</span>
                            Employee Actions
                        </h3>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            <li>• Apply for leave → Check balance</li>
                            <li>• Clock in/out → View attendance</li>
                            <li>• View goals → Update progress</li>
                            <li>• Complete self-assessment</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                            <span class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center text-white text-xs mr-2">2</span>
                            Manager Actions
                        </h3>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            <li>• Approve leave requests</li>
                            <li>• Assign shifts to team</li>
                            <li>• Conduct appraisals</li>
                            <li>• Set team goals</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                            <span class="w-6 h-6 bg-orange-500 rounded-full flex items-center justify-center text-white text-xs mr-2">3</span>
                            Admin Actions
                        </h3>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            <li>• Create employees/departments</li>
                            <li>• Configure shifts & leave types</li>
                            <li>• Assign roles & permissions</li>
                            <li>• Manage allocations</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                            <span class="w-6 h-6 bg-purple-500 rounded-full flex items-center justify-center text-white text-xs mr-2">4</span>
                            HR Actions
                        </h3>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            <li>• Review appraisals</li>
                            <li>• Calibrate ratings</li>
                            <li>• Manage leave policies</li>
                            <li>• Generate reports</li>
                        </ul>
                    </div>
                </div>

                {{-- Access Levels --}}
                <div class="mt-6 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">🔐 Access Level Reference</h3>
                    <div class="grid md:grid-cols-3 gap-4 text-sm">
                        <div class="bg-white dark:bg-zinc-600 rounded p-3">
                            <div class="font-semibold text-green-600 mb-2">✓ All Employees</div>
                            <ul class="text-xs text-gray-600 dark:text-gray-300 space-y-1">
                                <li>• Apply leave</li>
                                <li>• View own records</li>
                                <li>• Clock in/out</li>
                                <li>• Update goals</li>
                            </ul>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-3">
                            <div class="font-semibold text-blue-600 mb-2">👔 Managers</div>
                            <ul class="text-xs text-gray-600 dark:text-gray-300 space-y-1">
                                <li>• Approve leaves</li>
                                <li>• Assign shifts</li>
                                <li>• Conduct appraisals</li>
                                <li>• Create goals</li>
                            </ul>
                        </div>
                        <div class="bg-white dark:bg-zinc-600 rounded p-3">
                            <div class="font-semibold text-red-600 mb-2">🔑 Super Admin</div>
                            <ul class="text-xs text-gray-600 dark:text-gray-300 space-y-1">
                                <li>• All permissions</li>
                                <li>• Create leave types</li>
                                <li>• Assign roles</li>
                                <li>• System configuration</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
