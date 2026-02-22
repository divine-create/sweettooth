<div class="p-6 space-y-6">

    <x-breadcrumb
        title="Assign Department Managers"
        :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Employee Management', 'url' => route('super-admin.employee.index')],
            ['label' => 'Department Manager Assignments']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Assignment Form -->
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-zinc-800 dark:text-zinc-100 flex items-center">
                <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Assign Manager to Department
            </h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Select a branch, then a department, then choose an employee to assign as the department manager.
            </p>
        </div>

        <form wire:submit.prevent="assignManager">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Step 1: Select Branch -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 text-xs font-bold mr-2">1</span>
                        Select Branch *
                    </label>
                    <x-select.styled
                        wire:model.live="selected_branch_id"
                        :options="$branches->map(fn($branch) => ['label' => $branch->name, 'value' => $branch->id])->toArray()"
                        select="label:label|value:value"
                        placeholder="Choose a branch..."
                        searchable
                        required
                    />
                </div>

                <!-- Step 2: Select Department -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 text-xs font-bold mr-2">2</span>
                        Select Department *
                    </label>
                    <x-select.styled
                        wire:model.live="selected_department_id"
                        :options="$departments->map(fn($dept) => ['label' => $dept->name, 'value' => $dept->id])->toArray()"
                        select="label:label|value:value"
                        :placeholder="$selected_branch_id ? 'Choose a department...' : 'Select a branch first'"
                        searchable
                        :disabled="!$selected_branch_id"
                        required
                    />
                </div>

                <!-- Step 3: Select Employee -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 text-xs font-bold mr-2">3</span>
                        Select Employee *
                    </label>
                    <x-select.styled
                        wire:model.live="selected_employee_id"
                        :options="$employees->map(fn($emp) => ['label' => $emp->first_name . ' ' . $emp->last_name, 'value' => $emp->id])->toArray()"
                        select="label:label|value:value"
                        :placeholder="$selected_department_id ? 'Choose an employee...' : 'Select a department first'"
                        searchable
                        :disabled="!$selected_department_id"
                        required
                    />
                </div>

                <!-- Start Date -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Start Date *
                    </label>
                    <input type="date" wire:model="start_date" required
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Notes (Optional)
                </label>
                <textarea wire:model="notes" rows="2"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500"
                    placeholder="Add any additional notes about this assignment..."></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3">
                <button type="button" wire:click="resetForm"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors duration-200">
                    Clear Form
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Assign as Manager
                </button>
            </div>
        </form>
    </div>

    <!-- Filters for viewing assignments -->
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Filter Assignments</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Branch</label>
                <x-select.styled
                    wire:model.live="filterBranch"
                    :options="$filterBranches->map(fn($branch) => ['label' => $branch->name, 'value' => $branch->id])->toArray()"
                    select="label:label|value:value"
                    placeholder="All Branches"
                    searchable
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Department</label>
                <x-select.styled
                    wire:model.live="filterDepartment"
                    :options="$filterDepartments->map(fn($dept) => ['label' => $dept->name, 'value' => $dept->id])->toArray()"
                    select="label:label|value:value"
                    :placeholder="$filterBranch ? 'All Departments' : 'Select a branch first'"
                    searchable
                    :disabled="!$filterBranch"
                />
            </div>
        </div>
    </div>

    <!-- Assignments List -->
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Current Department Managers</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                List of all employees assigned as department managers
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Branch
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Department
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Manager
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Start Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($assignments as $assignment)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $assignment->branch?->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $assignment->department?->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium text-xs mr-3">
                                        {{ $assignment->employee ? strtoupper(substr($assignment->employee->first_name, 0, 1) . substr($assignment->employee->last_name, 0, 1)) : 'NA' }}
                                    </div>
                                    <span class="text-sm text-zinc-900 dark:text-zinc-100">
                                        {{ $assignment->employee ? $assignment->employee->first_name . ' ' . $assignment->employee->last_name : 'N/A' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $assignment->start_date ? \Carbon\Carbon::parse($assignment->start_date)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($assignment->is_active)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Active
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center space-x-2">
                                    @if ($assignment->is_active)
                                        <button wire:click="deactivateAssignment({{ $assignment->id }})"
                                            class="p-2 text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-lg transition-colors"
                                            title="Deactivate">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    @else
                                        <button wire:click="activateAssignment({{ $assignment->id }})"
                                            class="p-2 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                                            title="Activate">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    @endif
                                    <button wire:click="removeAssignment({{ $assignment->id }})"
                                        class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                        title="Remove Assignment">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">No department managers assigned yet.</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Use the form above to assign your first manager.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($assignments->hasPages())
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $assignments->links() }}
            </div>
        @endif
    </div>
</div>
