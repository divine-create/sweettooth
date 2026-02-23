<div class="p-6 space-y-6">

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
        title="Employee Management"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('dashboard')],
            ['label' => 'Employee Management']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Header with Add Button -->
    <div class="flex justify-between items-center">
        <a href="{{ branch_route('branch-dashboard.employee.create', ['b_id' => $b_id]) }}"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add New Employee
        </a>
    </div>

    <!-- Export Buttons -->
    <div class="flex justify-end items-center space-x-2">
        <button wire:click="exportCSV" wire:loading.attr="disabled" wire:target="exportCSV"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
            <span wire:loading.remove wire:target="exportCSV" class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </span>
            <span wire:loading wire:target="exportCSV" class="flex items-center">
                <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" fill="none"></circle>
                    <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Exporting...
            </span>
        </button>
    </div>

    <!-- Bulk Actions Bar -->
    <div x-data="{ selectedIds: @entangle('selectedIds') }" x-show="selectedIds.length > 0" x-cloak
        class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    <span x-text="selectedIds.length"></span> item(s) selected
                </span>
                <button wire:click="toggleBulkMode"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline text-left">
                    Clear Selection
                </button>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <button wire:click="bulkDeleteEmployees" wire:loading.attr="disabled" wire:target="bulkDeleteEmployees"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="bulkDeleteEmployees" class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Selected
                    </span>
                    <span wire:loading wire:target="bulkDeleteEmployees" class="flex items-center">
                        <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" fill="none"></circle>
                            <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Deleting...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div x-data="{ open: false, advanced: false }"
        class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md border border-zinc-200 dark:border-zinc-700 transition-all duration-300">
        <!-- Header / Toggle Button -->
        <div class="flex justify-between items-center p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 flex items-center">
                <svg class="w-5 h-5 mr-2 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14.293 13H10v5l-4-4v-3.586L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters
            </h2>

            <button @click="open = !open"
                class="flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 8h16M4 16h16" />
                    <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span x-text="open ? 'Close' : 'Show Filters'"></span>
            </button>
        </div>

        <!-- Filter Body -->
        <div x-show="open" x-collapse class="p-4 space-y-6" x-cloak>
            <!-- Basic Filters -->
            <div class="">
                <!-- Advanced Search Toggle -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Advanced
                        Search</label>
                    <button @click="advanced = !advanced"
                        class="flex items-center w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-600 transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2 text-zinc-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path x-show="!advanced" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14M12 5l7 7-7 7" />
                            <path x-show="advanced" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 12H5m7 7l-7-7 7-7" />
                        </svg>
                        <span x-text="advanced ? 'Hide Advanced' : 'Show Advanced'"></span>
                    </button>
                </div>
            </div>

            <!-- Advanced Search Dropdown -->
            <div x-show="advanced" x-collapse
                class="p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 mt-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                        <div class="relative">
                            <input type="text" wire:model.live="advancedSearch" placeholder="Search keyword..."
                                class="w-full pl-10 pr-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                            <svg class="absolute left-3 top-2.5 w-5 h-5 text-zinc-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date
                            Range</label>
                        <div class="flex space-x-2">
                            <input type="date" wire:model.live="dateFrom"
                                class="w-1/2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                            <input type="date" wire:model.live="dateTo"
                                class="w-1/2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                    <select wire:model.live="filterStatus"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Department Filter -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Department</label>
                    <select wire:model.live="filterDepartment"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>


                <!-- Gender Filter -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Gender</label>
                    <select wire:model.live="filterGender"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Genders</option>
                        @foreach ($genders as $gender)
                            <option value="{{ $gender }}">{{ ucfirst(str_replace('_', ' ', $gender)) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Shift Preference Filter -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Shift</label>
                    <select wire:model.live="filterShift"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Shifts</option>
                        @foreach ($shifts as $shift)
                            <option value="{{ $shift }}">{{ ucfirst($shift) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Hire Date Range -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Hire Date Range</label>
                    <div class="flex space-x-2">
                        <input type="date" wire:model.live="hireDateFrom"
                            class="w-1/2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <input type="date" wire:model.live="hireDateTo"
                            class="w-1/2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Termination Date Range -->
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Termination Date</label>
                    <div class="flex space-x-2">
                        <input type="date" wire:model.live="terminationDateFrom"
                            class="w-1/2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <input type="date" wire:model.live="terminationDateTo"
                            class="w-1/2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="flex flex-wrap gap-3 justify-end pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <button wire:click="applyFilters" wire:loading.attr="disabled" wire:target="applyFilters"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="applyFilters" class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z" />
                        </svg>
                        Apply
                    </span>
                    <span wire:loading wire:target="applyFilters" class="flex items-center">
                        <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" fill="none"></circle>
                            <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Applying...
                    </span>
                </button>
                <button wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="resetFilters" class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        Reset
                    </span>
                    <span wire:loading wire:target="resetFilters" class="flex items-center">
                        <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" fill="none"></circle>
                            <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Resetting...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <x-table :$headers :$rows selectable wire:model="selectedIds" striped paginate persist
        :filter="['quantity' => 'quantity', 'search' => 'search']" :quantity="[10, 25, 50, 100]">
        @interact('row', $row)
            @php
                $params = ['id' => $row->id, 'b_id' => $b_id];
                if ($row->employee_number) {
                    $params['employee_number'] = $row->employee_number;
                }
                $detailsUrl = branch_route('branch-dashboard.employee.details', $params);
            @endphp
            <tr wire:navigate href="{{ $detailsUrl }}" class="cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700/50 transition-colors">
        @endinteract

        @interact('column_name', $row)
            <a class="flex items-center"
                href="{{ branch_route('branch-dashboard.employee.details', $row->employee_number ? ['employee_number' => $row->employee_number, 'id' => $row->id] : ['id' => $row->id]) }}"
                wire:navigate>
                <div
                    class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium text-sm mr-2">
                    {{ strtoupper(substr($row->name, 0, 2)) }}
                </div>
                <div>
                    <span class="text-zinc-900 dark:text-zinc-100">{{ $row->name }}</span>
                    <p class="text-gray-400">{{ $row->employee_number }}</p>
                </div>
            </a>
        @endinteract

        @interact('column_department', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->department ? $row->department->name : 'N/A' }}
            </span>
        @endinteract

        @interact('column_branch', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->branch ? $row->branch->name : 'N/A' }}
            </span>
        @endinteract

        @interact('column_status', $row)
            @php
                $statusColors = [
                    'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    'inactive' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                    'terminated' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                    'on_probation' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    'on_leave' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                ];
                $statusLabel = $row->employment_status ?? ($row->is_active ? 'active' : 'inactive');
            @endphp
            <span class="px-2 py-1 text-xs font-semibold rounded-full border border-zinc-300 dark:border-zinc-600 {{ $statusColors[$statusLabel] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                {{ ucfirst(str_replace('_', ' ', $statusLabel)) }}
            </span>
        @endinteract

        @interact('column_salary', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($row->salary ?? 0) }}
            </span>
        @endinteract

        @interact('column_roles', $row)
            <div class="flex flex-wrap gap-1">
                @forelse($row->roles as $role)
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                        {{ ucfirst($role->name) }}
                    </span>
                @empty
                    <span class="text-zinc-400 dark:text-zinc-500 text-sm">No roles</span>
                @endforelse
            </div>
        @endinteract

        @interact('column_action', $row)
            <div class="flex items-center space-x-2">
                <button wire:click="openRoleModal('{{ $row->id }}')" wire:loading.attr="disabled" wire:target="openRoleModal('{{ $row->id }}')"
                    class="p-2 text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-colors disabled:opacity-50"
                    title="Assign Roles">
                    <svg wire:loading.remove wire:target="openRoleModal('{{ $row->id }}')" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg wire:loading wire:target="openRoleModal('{{ $row->id }}')" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" fill="none"></circle>
                        <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
                @if (auth()->id() == $row->id)
                <i>(You)</i>
                @else
                  <a href="{{ branch_route('branch-dashboard.employee.edit', ['id'=>$row->id]) }}"
                    class="p-2 text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-lg transition-colors"
                    title="Edit Employee">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </a>
                <button wire:click="deleteEmployee('{{ $row->id }}')" wire:loading.attr="disabled" wire:target="deleteEmployee('{{ $row->id }}')"
                    class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors disabled:opacity-50"
                    title="Delete Employee">
                    <svg wire:loading.remove wire:target="deleteEmployee('{{ $row->id }}')" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <svg wire:loading wire:target="deleteEmployee('{{ $row->id }}')" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" fill="none"></circle>
                        <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
                @endif
            </div>
        @endinteract
    </x-table>

    <!-- Assign Roles Modal (Livewire/Alpine) -->
    <div x-data="{ show: @entangle('showRoleModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-hidden"
        @keydown.escape.window="show = false; $wire.closeRoleModal()">
        
        <!-- Backdrop -->
        <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50"
            @click="show = false; $wire.closeRoleModal()">
        </div>

        <!-- Slide-in Panel -->
        <div x-show="show" x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-full md:w-1/2 lg:w-1/3 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Assign Roles</h2>
                <button @click="show = false; $wire.closeRoleModal()"
                    class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Search Box -->
            <div class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                <div class="relative">
                    <input type="text" 
                        wire:model.live="roleSearch" 
                        placeholder="Search roles..."
                        class="w-full pl-10 pr-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-purple-500">
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin">
                <div class="space-y-6">
                    @if(count($rolesGroupedByDepartment) > 0)
                        @foreach($rolesGroupedByDepartment as $category => $categoryRoles)
                            <div class="space-y-2">
                                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">{{ $category }}</h3>
                                <div class="space-y-2 pl-2 border-l-2 border-purple-300 dark:border-purple-700">
                                    @foreach($categoryRoles as $role)
                                        <label class="flex items-start p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition-colors">
                                            <input type="checkbox"
                                                wire:model="selectedRoles"
                                                value="{{ $role->id }}"
                                                class="w-5 h-5 accent-purple-600 bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-600 rounded focus:ring-purple-500 dark:focus:ring-purple-600 focus:ring-2 mt-0.5">
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ ucfirst($role->name) }}</p>
                                                @if($role->description)
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $role->description }}</p>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-zinc-300 dark:text-zinc-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No roles found matching "{{ $roleSearch }}"</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end space-x-3">
                <button @click="show = false; $wire.closeRoleModal()"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Cancel
                </button>
                <button @click="$wire.initiateRoleSave()"
                   wire:loading.attr="disabled"
                   class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2">
                    <span wire:loading.remove wire:target="initiateRoleSave,saveRoles">Save Roles</span>
                    <span wire:loading wire:target="initiateRoleSave,saveRoles" class="flex items-center space-x-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" fill="none"></circle>
                            <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Saving...</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Role Reason Modal (For Non-Super-Admins) -->
    @if (!is_super_admin())
    <div x-data="{ show: @entangle('showRoleReasonModal') }" x-show="show" x-cloak
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
                   Provide Reason for Role Update
               </h3>
               <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                   As a non-admin user, please provide a reason for updating this employee's roles.
               </p>
           </div>

           <!-- Modal Body -->
           <div class="px-6 py-4">
               <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                   Reason *
               </label>
               <textarea wire:model.live="roleReason" rows="4"
                   class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                   placeholder="Explain why this employee's roles need to be changed..."></textarea>
               <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                   Minimum {{ strlen($roleReason) }}/5 characters
               </p>
           </div>

           <!-- Modal Footer -->
           <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex gap-3 justify-end">
               <button type="button" wire:click="closeRoleReasonModal"
                   class="px-4 py-2 text-sm font-medium text-zinc-700 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 rounded-lg">
                   Cancel
               </button>
               <button type="button" wire:click="proceedWithRoleReason"
                   {{ strlen($roleReason) < 5 ? 'disabled' : '' }}
                   class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed rounded-lg flex items-center">
                   <span wire:loading.remove wire:target="proceedWithRoleReason">
                       <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                       </svg>
                       Submit Request
                   </span>
                   <span wire:loading wire:target="proceedWithRoleReason" class="inline-flex items-center gap-2">
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

    <!-- Delete Reason Modal (For Non-Super-Admins) -->
    @if (!is_super_admin())
    <div x-data="{ show: @entangle('showDeleteReasonModal') }" x-show="show" x-cloak
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
                  Provide Reason for Employee Deletion
              </h3>
              <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                  As a non-admin user, please provide a reason for deleting this employee.
              </p>
          </div>

          <!-- Modal Body -->
          <div class="px-6 py-4">
              <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                  Reason *
              </label>
              <textarea wire:model.live="deleteReason" rows="4"
                  class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                  placeholder="Explain why this employee needs to be deleted..."></textarea>
              <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                  Minimum {{ strlen($deleteReason) }}/5 characters
              </p>
          </div>

          <!-- Modal Footer -->
          <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex gap-3 justify-end">
              <button type="button" wire:click="closeDeleteReasonModal"
                  class="px-4 py-2 text-sm font-medium text-zinc-700 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 rounded-lg">
                  Cancel
              </button>
              <button type="button" wire:click="proceedWithDeleteReason"
                  {{ strlen($deleteReason) < 5 ? 'disabled' : '' }}
                  class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed rounded-lg flex items-center">
                  <span wire:loading.remove wire:target="proceedWithDeleteReason">
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      Submit Request
                  </span>
                  <span wire:loading wire:target="proceedWithDeleteReason" class="inline-flex items-center gap-2">
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
