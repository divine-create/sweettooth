<div class="p-3 space-y-3">
    <x-breadcrumb
        title="Shift Configuration"
        :items="[
            ['label' => 'Dashboard', 'url' => route('branch-dashboard.index')],
            ['label' => 'Shift Management', 'url' => route('branch-dashboard.shift-management.index', ['b_id' => $b_id])],
            ['label' => 'Configuration']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Header -->
    <div class="flex justify-end items-center">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Branch: <strong class="text-zinc-900 dark:text-white">{{ $branch->name ?? 'All' }}</strong></p>
    </div>

    <!-- Create Form -->
    @if($showCreateForm)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-3">Create New Shift Configuration</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name *</label>
                    <input type="text" wire:model="name" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500" placeholder="e.g. Morning Shift">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Shift Type *</label>
                    <select wire:model="shift_type" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                        <option value="">Select shift type</option>
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                        <option value="night">Night</option>
                        <option value="full_time">Full Time</option>
                    </select>
                    @error('shift_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Start Time *</label>
                    <input type="text" wire:model="start_time" data-timepicker="true" class="js-timepicker w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500" placeholder="e.g. 6:00 AM">
                    @error('start_time') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">End Time *</label>
                    <input type="text" wire:model="end_time" data-timepicker="true" class="js-timepicker w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500" placeholder="e.g. 2:00 PM">
                    @error('end_time') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Clock In Window Start *</label>
                    <input type="text" wire:model="clock_in_start" data-timepicker="true" class="js-timepicker w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500" placeholder="e.g. 6:00 AM">
                    @error('clock_in_start') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Clock In Window End *</label>
                    <input type="text" wire:model="clock_in_end" data-timepicker="true" class="js-timepicker w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500" placeholder="e.g. 12:00 PM">
                    @error('clock_in_end') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Auto Clock Out Minutes</label>
                    <input type="number" wire:model="auto_clock_out_minutes" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                    @error('auto_clock_out_minutes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Max Overtime Hours</label>
                    <input type="number" step="0.01" wire:model="max_overtime_hours" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                    @error('max_overtime_hours') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Break Duration (minutes)</label>
                    <input type="number" wire:model="break_duration_minutes" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                    @error('break_duration_minutes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Timezone</label>
                    <input type="text" wire:model="timezone" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                    @error('timezone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                    <select wire:model="is_active" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    @error('is_active') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-4 flex space-x-2">
                <button wire:click="store" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition duration-200 font-medium">Save Configuration</button>
                <button wire:click="cancel" class="px-4 py-2 bg-zinc-600 hover:bg-zinc-700 text-white rounded-lg transition duration-200 font-medium">Cancel</button>
            </div>
        </div>
    @endif

    <!-- Edit Form -->
    @if($showEditForm)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-3">Edit Shift Configuration</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name *</label>
                    <input type="text" wire:model="name" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500" placeholder="e.g. Morning Shift">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Shift Type *</label>
                    <select wire:model="shift_type" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                        <option value="">Select shift type</option>
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                        <option value="night">Night</option>
                        <option value="full_time">Full Time</option>
                    </select>
                    @error('shift_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Start Time *</label>
                    <input type="text" wire:model="start_time" data-timepicker="true" class="js-timepicker w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500" placeholder="e.g. 6:00 AM">
                    @error('start_time') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">End Time *</label>
                    <input type="text" wire:model="end_time" data-timepicker="true" class="js-timepicker w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500" placeholder="e.g. 2:00 PM">
                    @error('end_time') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Clock In Window Start *</label>
                    <input type="text" wire:model="clock_in_start" data-timepicker="true" class="js-timepicker w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500" placeholder="e.g. 6:00 AM">
                    @error('clock_in_start') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Clock In Window End *</label>
                    <input type="text" wire:model="clock_in_end" data-timepicker="true" class="js-timepicker w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500" placeholder="e.g. 12:00 PM">
                    @error('clock_in_end') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Auto Clock Out Minutes</label>
                    <input type="number" wire:model="auto_clock_out_minutes" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                    @error('auto_clock_out_minutes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Max Overtime Hours</label>
                    <input type="number" step="0.01" wire:model="max_overtime_hours" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                    @error('max_overtime_hours') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Break Duration (minutes)</label>
                    <input type="number" wire:model="break_duration_minutes" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                    @error('break_duration_minutes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Timezone</label>
                    <input type="text" wire:model="timezone" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                    @error('timezone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                    <select wire:model="is_active" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    @error('is_active') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-4 flex space-x-2">
                <button wire:click="update" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition duration-200 font-medium">Update Configuration</button>
                <button wire:click="cancel" class="px-4 py-2 bg-zinc-600 hover:bg-zinc-700 text-white rounded-lg transition duration-200 font-medium">Cancel</button>
            </div>
        </div>
    @endif

    <!-- Controls -->
    <div class="flex justify-between items-center">
        <div class="flex space-x-3">
            <input type="text" wire:model.live="search" placeholder="Search configurations..." class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
        </div>
        <button wire:click="create" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add New Configuration
        </button>
    </div>

    <!-- Configurations Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        <th wire:click="sortBy('name')" class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider cursor-pointer">
                            <div class="flex items-center">
                                Name
                                @if($sortBy === 'name')
                                    <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('shift_type')" class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider cursor-pointer">
                            <div class="flex items-center">
                                Type
                                @if($sortBy === 'shift_type')
                                    <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Times</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Clock In Window</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Auto Clock Out</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($configs as $config)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-750">
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">{{ $config->name }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 rounded text-xs font-semibold">
                                    {{ ucfirst($config->shift_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">
                                <div>{{ $config->start_time }} - {{ $config->end_time }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $config->getDurationMinutes() }} mins</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">
                                <div>{{ $config->clock_in_start }} - {{ $config->clock_in_end }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">
                                <div>{{ $config->auto_clock_out_minutes }} mins</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">after end time</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($config->is_active)
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">Active</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex space-x-2">
                                    <button wire:click="edit({{ $config->id }})" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200 font-medium text-sm">Edit</button>
                                    <button wire:click="destroy({{ $config->id }})"
                                            wire:confirm="Are you sure you want to delete this shift configuration?"
                                            class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg transition duration-200 font-medium text-sm">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                <p class="text-lg font-medium text-zinc-900 dark:text-white">No shift configurations found</p>
                                <p class="text-sm">Click "Add New Configuration" to create one</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
