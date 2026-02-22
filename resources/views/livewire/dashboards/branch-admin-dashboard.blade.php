<div class="space-y-6">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Branch Management Dashboard</h1>
        <p class="text-gray-600 mt-2">Overview of all branch operations and performance</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Daily Revenue -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Today's Revenue</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $dailyRevenue ?? \App\Helpers\LocalizationHelper::formatCurrency(0) }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.16 2.75a.75.75 0 00-1.32 0l-.5 1.45H4.75a.75.75 0 000 1.5h1.13l-.5 1.45a.75.75 0 001.32.44l.5-1.45h1.62l-.5 1.45a.75.75 0 001.32.44l.5-1.45H10a.75.75 0 000-1.5h-1.13l.5-1.45a.75.75 0 00-1.32-.44l-.5 1.45H5.83l.5-1.45a.75.75 0 00-1.32-.44l-.5 1.45H2.75a.75.75 0 000-1.5h1.13l.5-1.45z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Inventory Value -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Inventory Value</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $inventoryValue ?? \App\Helpers\LocalizationHelper::formatCurrency(0) }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 16a1 1 0 102 0v-5.586l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 101.414 1.414L13 10.414V16z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Staff -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Staff</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $totalStaff ?? '0' }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v2h8v-2zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-2a4 4 0 00-8 0v2h8z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- System Alerts -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">System Alerts</p>
                    <p class="text-2xl font-bold text-red-600 mt-2">{{ $systemAlerts ?? '0' }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Module Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Production Module -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg border border-blue-200 p-6 cursor-pointer hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Production</h3>
                <div class="p-2 bg-blue-200 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v2h2a2 2 0 012 2v9a2 2 0 01-2 2h-2v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2H2a2 2 0 01-2-2V7a2 2 0 012-2h2V5z"></path>
                    </svg>
                </div>
            </div>
            <div class="space-y-2">
                <p class="text-sm text-gray-600"><span class="font-bold text-gray-900">12</span> in queue</p>
                <p class="text-sm text-gray-600"><span class="font-bold text-gray-900">8</span> in progress</p>
                <button class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                    Go to Dashboard →
                </button>
            </div>
        </div>

        <!-- Sales Module -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg border border-green-200 p-6 cursor-pointer hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Sales</h3>
                <div class="p-2 bg-green-200 rounded-lg">
                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.16 2.75a.75.75 0 00-1.32 0l-.5 1.45H4.75a.75.75 0 000 1.5h1.13l-.5 1.45a.75.75 0 001.32.44l.5-1.45h1.62l-.5 1.45a.75.75 0 001.32.44l.5-1.45H10a.75.75 0 000-1.5h-1.13l.5-1.45a.75.75 0 00-1.32-.44l-.5 1.45H5.83l.5-1.45a.75.75 0 00-1.32-.44l-.5 1.45H2.75a.75.75 0 000-1.5h1.13l.5-1.45z"></path>
                    </svg>
                </div>
            </div>
            <div class="space-y-2">
                <p class="text-sm text-gray-600"><span class="font-bold text-gray-900">{{ \App\Helpers\LocalizationHelper::formatCurrency((float) ($dailyRevenue ?? 0)) }}</span> today</p>
                <p class="text-sm text-gray-600"><span class="font-bold text-gray-900">127</span> transactions</p>
                <button class="mt-4 w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium">
                    Go to Dashboard →
                </button>
            </div>
        </div>

        <!-- Inventory Module -->
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg border border-orange-200 p-6 cursor-pointer hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Inventory</h3>
                <div class="p-2 bg-orange-200 rounded-lg">
                    <svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 16a1 1 0 102 0v-5.586l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 101.414 1.414L13 10.414V16z"></path>
                    </svg>
                </div>
            </div>
            <div class="space-y-2">
                <p class="text-sm text-gray-600"><span class="font-bold text-gray-900">{{ \App\Helpers\LocalizationHelper::formatCurrency((float) ($inventoryValue ?? 0)) }}</span> in stock</p>
                <p class="text-sm text-gray-600"><span class="font-bold text-gray-900">5</span> low stock alerts</p>
                <button class="mt-4 w-full px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition text-sm font-medium">
                    Go to Dashboard →
                </button>
            </div>
        </div>

        <!-- HR Module -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg border border-purple-200 p-6 cursor-pointer hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">HR</h3>
                <div class="p-2 bg-purple-200 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v2h8v-2zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-2a4 4 0 00-8 0v2h8z"></path>
                    </svg>
                </div>
            </div>
            <div class="space-y-2">
                <p class="text-sm text-gray-600"><span class="font-bold text-gray-900">{{ $totalStaff ?? '0' }}</span> employees</p>
                <p class="text-sm text-gray-600"><span class="font-bold text-gray-900">3</span> pending leaves</p>
                <button class="mt-4 w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-medium">
                    Go to Dashboard →
                </button>
            </div>
        </div>
    </div>

    <!-- Pending Approvals -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Pending Approval Requests</h3>
            <div class="space-y-3">
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-start justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Production Request #123</p>
                        <p class="text-sm text-gray-600 mt-1">Sourdough batch • Requested 2 hours ago</p>
                    </div>
                    <button class="text-blue-600 hover:underline text-sm font-medium">Review</button>
                </div>
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg flex items-start justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Leave Request - John Smith</p>
                        <p class="text-sm text-gray-600 mt-1">Annual leave • Requested yesterday</p>
                    </div>
                    <button class="text-green-600 hover:underline text-sm font-medium">Review</button>
                </div>
            </div>
        </div>

        <!-- Recent Audits -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
            <div class="space-y-3 text-sm">
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-green-500 rounded-full mt-1.5"></div>
                    <div>
                        <p class="text-gray-900"><span class="font-medium">Inventory Check</span> completed</p>
                        <p class="text-gray-600">15 minutes ago</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5"></div>
                    <div>
                        <p class="text-gray-900"><span class="font-medium">Production Batch</span> started</p>
                        <p class="text-gray-600">1 hour ago</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-orange-500 rounded-full mt-1.5"></div>
                    <div>
                        <p class="text-gray-900"><span class="font-medium">Low Stock Alert</span> triggered</p>
                        <p class="text-gray-600">3 hours ago</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
