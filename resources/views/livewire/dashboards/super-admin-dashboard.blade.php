<div class="space-y-6">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Executive Dashboard</h1>
        <p class="text-gray-600 mt-2">Multi-branch overview and system management</p>
    </div>

    <!-- Global KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Revenue -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Revenue (Today)</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency($totalRevenue ?? 0) }}
                    </p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.16 2.75a.75.75 0 00-1.32 0l-.5 1.45H4.75a.75.75 0 000 1.5h1.13l-.5 1.45a.75.75 0 001.32.44l.5-1.45h1.62l-.5 1.45a.75.75 0 001.32.44l.5-1.45H10a.75.75 0 000-1.5h-1.13l.5-1.45a.75.75 0 00-1.32-.44l-.5 1.45H5.83l.5-1.45a.75.75 0 00-1.32-.44l-.5 1.45H2.75a.75.75 0 000-1.5h1.13l.5-1.45z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Inventory -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Inventory Value</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency($totalInventory ?? 0) }}
                    </p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 16a1 1 0 102 0v-5.586l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 101.414 1.414L13 10.414V16z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Employees -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Employees</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $totalEmployees ?? '0' }}</p>
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

    <!-- Branch Selector Tabs -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="border-b border-gray-200 p-4 flex gap-2 overflow-x-auto">
            <button wire:click="selectBranch('all')" class="px-4 py-2 text-sm font-medium {{ $selectedBranch === 'all' ? 'bg-blue-100 text-blue-700 rounded-lg' : 'text-gray-600 hover:text-gray-900' }}">
                All Branches
            </button>
            @forelse($branches ?? [] as $branch)
                <button wire:click="selectBranch('{{ $branch->id }}')" class="px-4 py-2 text-sm font-medium whitespace-nowrap {{ $selectedBranch === $branch->id ? 'bg-blue-100 text-blue-700 rounded-lg' : 'text-gray-600 hover:text-gray-900' }}">
                    {{ $branch->name }}
                </button>
            @empty
                <p class="text-sm text-gray-600">No branches available</p>
            @endforelse
        </div>

        <!-- Branch-Specific Content -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm text-gray-600">Branch Revenue</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency($branchRevenue ?? 0) }}
                    </p>
                </div>
                <div class="p-4 bg-green-50 rounded-lg">
                    <p class="text-sm text-gray-600">Branch Inventory</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency($branchInventory ?? 0) }}
                    </p>
                </div>
                <div class="p-4 bg-purple-50 rounded-lg">
                    <p class="text-sm text-gray-600">Branch Staff</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $branchStaff ?? '0' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Comparison Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue by Branch (Last 30 Days)</h3>
            <div class="flex items-center justify-center h-64 bg-gray-50 rounded-lg">
                <p class="text-gray-500">Chart placeholder - Connect to real data</p>
            </div>
        </div>

        <!-- Top Branches -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Performing Branches</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-900">Downtown Branch</span>
                    <span class="text-sm font-bold text-green-600">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency(5200) }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-900">Mall Branch</span>
                    <span class="text-sm font-bold text-green-600">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency(4800) }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-900">Airport Branch</span>
                    <span class="text-sm font-bold text-green-600">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency(3500) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- System Management Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- User Management -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">User Management</h3>
            <div class="space-y-3">
                <a href="{{ route('branch-dashboard.employee.index') }}" class="block p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition">
                    <p class="font-medium text-gray-900">Manage Employees</p>
                    <p class="text-sm text-gray-600 mt-1">Create, edit, and assign roles</p>
                </a>
                <a href="{{ route('branch-dashboard.role-assignments.index') }}" class="block p-4 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition">
                    <p class="font-medium text-gray-900">Role Assignments</p>
                    <p class="text-sm text-gray-600 mt-1">Manage user roles and permissions</p>
                </a>
            </div>
        </div>

        <!-- System Settings -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">System Settings</h3>
            <div class="space-y-3">
                <button class="w-full text-left p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition">
                    <p class="font-medium text-gray-900">Branch Management</p>
                    <p class="text-sm text-gray-600 mt-1">Create and configure branches</p>
                </button>
                <button class="w-full text-left p-4 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 transition">
                    <p class="font-medium text-gray-900">System Configuration</p>
                    <p class="text-sm text-gray-600 mt-1">General settings and preferences</p>
                </button>
            </div>
        </div>
    </div>

    <!-- Global Audit Log -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent System Activity</h3>
        <div class="space-y-3 text-sm">
            <div class="flex items-start space-x-3">
                <div class="w-2 h-2 bg-green-500 rounded-full mt-1.5"></div>
                <div>
                    <p class="text-gray-900"><span class="font-medium">User Login</span> - admin@sweettooth.com</p>
                    <p class="text-gray-600">5 minutes ago</p>
                </div>
            </div>
            <div class="flex items-start space-x-3">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5"></div>
                <div>
                    <p class="text-gray-900"><span class="font-medium">Role Changed</span> - John Smith (Manager)</p>
                    <p class="text-gray-600">1 hour ago</p>
                </div>
            </div>
            <div class="flex items-start space-x-3">
                <div class="w-2 h-2 bg-orange-500 rounded-full mt-1.5"></div>
                <div>
                    <p class="text-gray-900"><span class="font-medium">Backup Completed</span> - Database</p>
                    <p class="text-gray-600">2 hours ago</p>
                </div>
            </div>
            <div class="flex items-start space-x-3">
                <div class="w-2 h-2 bg-purple-500 rounded-full mt-1.5"></div>
                <div>
                    <p class="text-gray-900"><span class="font-medium">Batch Process</span> - Reconciliation</p>
                    <p class="text-gray-600">3 hours ago</p>
                </div>
            </div>
        </div>
    </div>
</div>
