<div class="space-y-6 p-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Super Admin Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400">System-wide administrative view</p>
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Branches -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Branches</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_branches'] }}</p>
                </div>
                <div class="text-4xl text-blue-500">📍</div>
            </div>
        </div>

        <!-- Active Branches -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Branches</p>
                    <p class="text-3xl font-bold text-green-600">{{ $stats['active_branches'] }}</p>
                </div>
                <div class="text-4xl text-green-500">✓</div>
            </div>
        </div>

        <!-- Total Roles -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Roles</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_roles'] }}</p>
                </div>
                <div class="text-4xl text-purple-500">👥</div>
            </div>
        </div>

        <!-- Total Employees -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Employees</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_employees'] }}</p>
                </div>
                <div class="text-4xl text-orange-500">👤</div>
            </div>
        </div>

        <!-- Total Items -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Inventory Items</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_items'] }}</p>
                </div>
                <div class="text-4xl text-cyan-500">📦</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ branch_route('branch-dashboard.roles.index') }}" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-center">
                Manage Roles
            </a>
            <a href="{{ branch_route('branch-dashboard.branches.index') }}" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-center">
                Manage Branches
            </a>
            <a href="{{ branch_route('branch-dashboard.settings.index') }}" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition text-center">
                System Settings
            </a>
            <a href="{{ branch_route('branch-dashboard.md-reports.dashboard') }}" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition text-center">
                MD Reports
            </a>
        </div>
    </div>

    <!-- Recent Branches -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Branches</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b dark:border-gray-700">
                    <tr>
                        <th class="text-left py-2 px-4 font-medium text-gray-700 dark:text-gray-300">Name</th>
                        <th class="text-left py-2 px-4 font-medium text-gray-700 dark:text-gray-300">Code</th>
                        <th class="text-left py-2 px-4 font-medium text-gray-700 dark:text-gray-300">Location</th>
                        <th class="text-left py-2 px-4 font-medium text-gray-700 dark:text-gray-300">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBranches as $branch)
                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="py-3 px-4">{{ $branch->name }}</td>
                        <td class="py-3 px-4">{{ $branch->code }}</td>
                        <td class="py-3 px-4">{{ $branch->location ?? '-' }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded text-xs font-medium {{ $branch->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $branch->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-3 px-4 text-center text-gray-500">No branches found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Roles -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Roles</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b dark:border-gray-700">
                    <tr>
                        <th class="text-left py-2 px-4 font-medium text-gray-700 dark:text-gray-300">Role Name</th>
                        <th class="text-left py-2 px-4 font-medium text-gray-700 dark:text-gray-300">Guard</th>
                        <th class="text-left py-2 px-4 font-medium text-gray-700 dark:text-gray-300">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRoles as $role)
                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="py-3 px-4">{{ $role->name }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $role->guard_name }}
                            </span>
                        </td>
                        <td class="py-3 px-4">{{ $role->created_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="py-3 px-4 text-center text-gray-500">No roles found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
