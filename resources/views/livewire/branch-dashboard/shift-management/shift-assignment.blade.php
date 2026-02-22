<div class="p-3 space-y-3">
    <x-breadcrumb
        title="Shift Assignment"
        :items="[
            ['label' => 'Dashboard', 'url' => route('branch-dashboard.index')],
            ['label' => 'Shift Management', 'url' => route('branch-dashboard.shift-management.index', ['b_id' => $b_id])],
            ['label' => 'Assignment']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Header -->
    <div class="flex justify-end items-center">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Branch: <strong class="text-zinc-900 dark:text-white">{{ $branch->name ?? 'All' }}</strong></p>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search Employees</label>
                <input type="text" wire:model.live="search" placeholder="Search by name or employee #" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Filter by Department</label>
                <select wire:model.live="selectedDepartment" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Select Date</label>
                <input type="date" wire:model.live="selectedShiftDate" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <!-- Assignment Form -->
    @if($showAssignmentForm)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-3">Assign Employee to Shift</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Employee *</label>
                    <select wire:model="employee_id" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->employee_number }})</option>
                        @endforeach
                    </select>
                    @error('employee_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Shift Date *</label>
                    <input type="date" wire:model="shift_date" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                    @error('shift_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Shift Type *</label>
                    <select wire:model="shift_configuration_id" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Shift Type</option>
                        @foreach($shiftConfigurations as $config)
                            <option value="{{ $config->id }}">{{ $config->name }} ({{ $config->start_time }} - {{ $config->end_time }})</option>
                        @endforeach
                    </select>
                    @error('shift_configuration_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Department</label>
                    <select wire:model="department_id" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Notes</label>
                    <textarea wire:model="notes" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Additional notes about this assignment"></textarea>
                </div>
            </div>

            <div class="mt-4 flex space-x-2">
                <button wire:click="assignShift" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition duration-200 font-medium">Assign Shift</button>
                <button wire:click="cancel" class="px-4 py-2 bg-zinc-600 hover:bg-zinc-700 text-white rounded-lg transition duration-200 font-medium">Cancel</button>
            </div>
        </div>
    @endif

    <!-- Controls -->
    <div class="flex justify-between items-center">
        <div>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Assigned Shifts for {{ $selectedShiftDate ? date('F j, Y', strtotime($selectedShiftDate)) : today()->format('F j, Y') }}</p>
        </div>
        <button wire:click="assign" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Assign Shift
        </button>
    </div>

    <!-- Assigned Shifts Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Employee</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Department</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Shift Type</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Times</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($shifts as $shift)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-750">
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $shift->employee->name }}</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $shift->employee->employee_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">{{ $shift->department ? $shift->department->name : 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 rounded text-xs font-semibold">
                                    {{ ucfirst($shift->shift_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">
                                @if($shift->configuration)
                                    <div>{{ $shift->configuration->start_time }} - {{ $shift->configuration->end_time }}</div>
                                @else
                                    <div>N/A</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($shift->status === 'scheduled')
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">Scheduled</span>
                                @elseif($shift->status === 'active')
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">Active</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ ucfirst($shift->status) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <button wire:click="removeAssignment({{ $shift->id }})"
                                        wire:confirm="Are you sure you want to remove this shift assignment?"
                                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg transition duration-200 font-medium text-sm">Remove</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                <p class="text-lg font-medium text-zinc-900 dark:text-white">No shifts assigned for this date</p>
                                <p class="text-sm">Click "Assign Shift" to assign an employee to a shift</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
