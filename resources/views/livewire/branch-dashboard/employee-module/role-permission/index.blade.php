<div class="p-3 space-y-3">

<style>
    /* Custom scrollbar styles */
    .scrollbar-thin::-webkit-scrollbar {
        width: 8px;
    }

    .scrollbar-thin::-webkit-scrollbar-track {
        background: transparent;
    }

    .scrollbar-thin::-webkit-scrollbar-thumb {
        @apply bg-zinc-300 dark:bg-zinc-700 rounded-full;
    }

    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        @apply bg-zinc-400 dark:bg-zinc-600;
    }

    [x-cloak] {
        display: none !important;
    }
</style>
<x-breadcrumb
    title="Role Management"
    :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Role Management']
    ]"
    :compact="false"
    :with-icons="true"
/>
    <!-- Header with Add Button -->
    <div class="flex justify-between items-center">
        <div class="flex gap-3">
            <button wire:click="openRoleModal" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add New Role
            </button>
            <button wire:click="openStandalonePermissionModal" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Create Permission
            </button>
        </div>
    </div>

    <!-- Export Buttons -->
    <div class="flex justify-end items-center space-x-2">
        <button wire:click="exportCSV" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export CSV
        </button>

    </div>

    <!-- Bulk Actions Bar -->
    <div x-data="{ selectedIds: @entangle('selectedIds') }" x-show="selectedIds.length > 0" x-cloak class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    <span x-text="selectedIds.length"></span> item(s) selected
                </span>
                <button wire:click="toggleBulkMode" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline text-left">
                    Clear Selection
                </button>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <button wire:click="bulkDeleteRoles"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Selected
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div x-data="{ open: false, advanced: false }" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 transition-all duration-300">
        <!-- Header / Toggle Button -->
        <div class="flex justify-between items-center px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14.293 13H10v5l-4-4v-3.586L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filters
            </h2>

            <button @click="open = !open" class="flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                    <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span x-text="open ? 'Close' : 'Show Filters'"></span>
            </button>
        </div>

        <!-- Filter Body -->
        <div x-show="open" x-collapse class="p-3 space-y-3">
            <!-- Basic Filters -->
            <div class="">
                <!-- Advanced Search Toggle -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Advanced Search</label>
                    <button @click="advanced = !advanced" class="flex items-center w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-600 transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!advanced" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/>
                            <path x-show="advanced" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m7 7l-7-7 7-7"/>
                        </svg>
                        <span x-text="advanced ? 'Hide Advanced' : 'Show Advanced'"></span>
                    </button>
                </div>
            </div>

            <!-- Advanced Search Dropdown -->
            <div x-show="advanced" x-collapse class="p-2.5 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 mt-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <!-- Search -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                        <div class="relative">
                            <input type="text" wire:model.live="advancedSearch" placeholder="Search keyword..." class="w-full pl-10 pr-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                            <svg class="absolute left-3 top-2.5 w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date Range</label>
                        <div class="flex space-x-2">
                            <input type="date" wire:model.live="dateFrom" class="w-1/2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                            <input type="date" wire:model.live="dateTo" class="w-1/2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="flex flex-wrap gap-2 justify-end pt-2.5 border-t border-zinc-200 dark:border-zinc-700">
                <button wire:click="applyFilters" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
                    </svg>
                    Apply
                </button>
                <button wire:click="resetFilters" class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <x-table
        :$headers
        :$rows
        selectable
        wire:model="selectedIds"
        striped
        paginate
        persist
        :filter="['quantity' => 'quantity', 'search' => 'search']"
        :quantity="[2, 10, 25, 50, 100]"
    >
        @interact('column_action', $row)
            <div class="flex items-center space-x-2">
                <button wire:click="viewPermissions({{ $row->id }})" class="p-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="View Permissions">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
                <button wire:click="editRole({{ $row->id }})" class="p-2 text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-lg transition-colors" title="Edit Role">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button wire:click="deleteRole({{ $row->id }})" class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Delete Role">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        @endinteract
    </x-table>

    <!-- View Permissions Modal (Slide-in) -->
    <div x-data="{ show: @entangle('showPermissionsModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 z-50 overflow-hidden"
         @keydown.escape.window="show = false">
        <!-- Backdrop -->
        <div x-show="show"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black bg-opacity-50"
             @click="$wire.closePermissionsModal()">
        </div>

        <!-- Slide-in Panel -->
        <div x-show="show"
             x-transition:enter="transform transition ease-in-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in-out duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="fixed inset-y-0 right-0 w-full md:w-1/2 lg:w-1/2 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Role Permissions</h2>
                <button wire:click="closePermissionsModal" class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin scrollbar-thumb-zinc-300 dark:scrollbar-thumb-zinc-700 scrollbar-track-transparent">
                @if(count($rolePermissions) > 0)
                    <div class="space-y-2">
                        @foreach($rolePermissions as $permission)
                            <div class="flex items-center p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-blue-400 dark:hover:border-blue-600 transition-colors">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $permission['name'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-zinc-500 dark:text-zinc-400">
                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="text-lg font-medium">No permissions assigned</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Add/Edit Role Modal (Slide-in) -->
    <div x-data="{ show: @entangle('showRoleModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 z-50 overflow-hidden"
         @keydown.escape.window="show = false">
        <!-- Backdrop -->
        <div x-show="show"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black bg-opacity-50"
             @click="$wire.closeRoleModal()">
        </div>

        <!-- Slide-in Panel -->
        <div x-show="show"
             x-transition:enter="transform transition ease-in-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in-out duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="fixed inset-y-0 right-0 w-full md:w-1/2 lg:w-1/2 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $isEditing ? 'Edit Role' : 'Add New Role' }}</h2>
                <button wire:click="closeRoleModal" class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Scrollable Form Content -->
            <div class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin scrollbar-thumb-zinc-300 dark:scrollbar-thumb-zinc-700 scrollbar-track-transparent">
                <form wire:submit.prevent="saveRole" class="space-y-6">
                    <!-- Role Name -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Role Name *</label>
                        <input type="text" wire:model="roleName" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500" placeholder="Enter role name" required>
                        @error('roleName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Guard Name -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Guard Name *</label>
                        <x-select.styled
                            wire:model="roleGuard"
                            :options="[
                                ['label' => 'Web', 'value' => 'web'],
                                ['label' => 'Employee', 'value' => 'employee']
                            ]"
                            select="label:label|value:value"
                            placeholder="Select Guard"
                        />
                        @error('roleGuard') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Permissions -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Assign Permissions</label>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="openCreatePermissionModal" class="text-sm px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    New
                                </button>
                                <button type="button" wire:click="toggleAllPermissions" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline">
                                    {{ count($selectedPermissions) === count($allPermissions) ? 'Deselect All' : 'Select All' }}
                                </button>
                            </div>
                        </div>
                        <div class="space-y-2 max-h-64 overflow-y-auto p-3 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                            @foreach($allPermissions as $permission)
                                <label class="flex items-center p-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg cursor-pointer transition-colors">
                                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->id }}" class="w-4 h-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end space-x-3">
                <button wire:click="closeRoleModal" class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Cancel
                </button>
                <button wire:click="saveRole" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                    {{ $isEditing ? 'Update Role' : 'Create Role' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Create Permission Modal (Nested Slide-in) -->
    <div x-data="{ show: @entangle('showCreatePermissionModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 z-[60] overflow-hidden"
         @keydown.escape.window="show = false">
        <!-- Backdrop -->
        <div x-show="show"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black bg-opacity-70"
             @click="$wire.closeCreatePermissionModal()">
        </div>

        <!-- Slide-in Panel -->
        <div x-show="show"
             x-transition:enter="transform transition ease-in-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in-out duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="fixed inset-y-0 right-0 w-full md:w-1/2 bg-white dark:bg-zinc-900 shadow-2xl flex flex-col border-l-4 border-green-500">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between bg-green-50 dark:bg-green-900/20">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Create New Permission</h2>
                <button wire:click="closeCreatePermissionModal" class="p-2 hover:bg-green-100 dark:hover:bg-green-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Form Content -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <form wire:submit.prevent="createPermission" class="space-y-4">
                    <!-- Permission Name -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Permission Name *</label>
                        <input type="text" wire:model="permissionName" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-green-500" placeholder="e.g. create-users" required>
                        @error('permissionName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Use lowercase with hyphens (e.g., view-reports, edit-posts)</p>
                    </div>

                    <!-- Guard Name -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Guard Name *</label>
                        <x-select.styled
                            wire:model="permissionGuard"
                            :options="[
                                ['label' => 'Web', 'value' => 'web'],
                                ['label' => 'API', 'value' => 'api']
                            ]"
                            select="label:label|value:value"
                            placeholder="Select Guard"
                        />
                        @error('permissionGuard') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Info Box -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-blue-800 dark:text-blue-200">This permission will be automatically added to the role you're currently creating/editing.</p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end space-x-3 bg-zinc-50 dark:bg-zinc-800">
                <button wire:click="closeCreatePermissionModal" class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Cancel
                </button>
                <button wire:click="createPermission" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create Permission
                </button>
            </div>
        </div>
    </div>

    <!-- Standalone Create Permission Modal -->
    <div x-data="{ show: @entangle('showStandalonePermissionModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 z-50 overflow-hidden"
         @keydown.escape.window="show = false">
        <!-- Backdrop -->
        <div x-show="show"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black bg-opacity-50"
             @click="$wire.closeStandalonePermissionModal()">
        </div>

        <!-- Slide-in Panel -->
        <div x-show="show"
             x-transition:enter="transform transition ease-in-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in-out duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="fixed inset-y-0 right-0 w-full md:w-1/2 bg-white dark:bg-zinc-900 shadow-2xl flex flex-col">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between bg-green-50 dark:bg-green-900/20">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Create New Permission</h2>
                <button wire:click="closeStandalonePermissionModal" class="p-2 hover:bg-green-100 dark:hover:bg-green-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Form Content -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <form wire:submit.prevent="createStandalonePermission" class="space-y-4">
                    <!-- Permission Name -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Permission Name *</label>
                        <input type="text" wire:model="standalonePermissionName" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-green-500" placeholder="e.g. create-users" required>
                        @error('standalonePermissionName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Use lowercase with hyphens (e.g., view-reports, edit-posts)</p>
                    </div>

                    <!-- Guard Name -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Guard Name *</label>
                        <x-select.styled
                            wire:model="standalonePermissionGuard"
                            :options="[
                                ['label' => 'Web', 'value' => 'web'],
                                ['label' => 'API', 'value' => 'api']
                            ]"
                            select="label:label|value:value"
                            placeholder="Select Guard"
                        />
                        @error('standalonePermissionGuard') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Info Box -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-blue-800 dark:text-blue-200">This permission can be assigned to roles later. You can create permissions for different guards (web for admins, employee for staff).</p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end space-x-3 bg-zinc-50 dark:bg-zinc-800">
                <button wire:click="closeStandalonePermissionModal" class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Cancel
                </button>
                <button wire:click="createStandalonePermission" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create Permission
                </button>
            </div>
        </div>
    </div>

    <!-- Reason Modal (For Non-Super-Admins) -->
    @if (!is_super_admin())
    <div x-data="{ show: @entangle('showReasonModal') }" x-show="show" x-cloak
       class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto" @keydown.escape.window="show = false">
       <!-- Backdrop -->
       <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-300"
           x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
           x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50"
           @click="show = false"></div>

       <!-- Modal Panel -->
       <div x-show="show" x-transition:enter="transform transition ease-in-out duration-300"
           x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100"
           x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="scale-100 opacity-100"
           x-transition:leave-end="scale-95 opacity-0"
           class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-xl max-w-md w-full mx-4 border border-zinc-200 dark:border-zinc-700">

           <!-- Modal Header -->
           <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
               <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                   Provide Reason for Operation
               </h3>
               <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                   As a non-admin user, please provide a reason for this operation.
               </p>
           </div>

           <!-- Modal Body -->
           <div class="px-6 py-4">
               <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                   Reason *
               </label>
               <textarea wire:model.live="operationReason" rows="4"
                   class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                   placeholder="Explain why this operation is needed..."></textarea>
               <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                   Minimum {{ strlen($operationReason) }}/5 characters
               </p>
           </div>

           <!-- Modal Footer -->
           <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex gap-3 justify-end">
               <button type="button" wire:click="closeReasonModal"
                   class="px-4 py-2 text-sm font-medium text-zinc-700 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 rounded-lg">
                   Cancel
               </button>
               <button type="button" wire:click="proceedWithRoleOperation"
                   {{ strlen($operationReason) < 5 ? 'disabled' : '' }}
                   class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed rounded-lg flex items-center">
                   <span wire:loading.remove wire:target="proceedWithRoleOperation">
                       <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                       </svg>
                       Submit Request
                   </span>
                   <span wire:loading wire:target="proceedWithRoleOperation" class="inline-flex items-center gap-2">
                       <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" fill="none"></circle>
                           <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                       </svg>
                       Submitting...
                   </span>
               </button>
           </div>
       </div>
    </div>
    @endif

</div>
