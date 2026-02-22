<div class="p-3 space-y-3">
    <x-breadcrumb title="Leave Types Management" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Employees', 'url' => branch_route('branch-dashboard.employee.index')],
        ['label' => 'Leave Types'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Leave Types Management</h2>
                <p class="text-sm opacity-90 mt-1">
                    Configure leave types and policies for your organization
                </p>
            </div>
            <button wire:click="openCreateModal"
                class="px-4 py-2 bg-white text-indigo-600 rounded-lg font-medium hover:bg-indigo-50 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Leave Type
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
        <input type="text" wire:model.live.debounce.300ms="search"
            placeholder="Search leave types..."
            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-indigo-500">
    </div>

    <!-- Leave Types Table -->
    <x-table :$headers :$rows striped paginate persist
        :filter="['quantity' => 'quantity', 'search' => 'search']"
        :quantity="[10, 20, 50, 100]">

        @interact('column_name', $row)
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded" style="background-color: {{ $row->color }}"></div>
                <div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->name }}</div>
                    @if($row->description)
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row->description }}</div>
                    @endif
                </div>
            </div>
        @endinteract

        @interact('column_code', $row)
            <div class="text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">
                    {{ $row->code }}
                </span>
            </div>
        @endinteract

        @interact('column_default_days', $row)
            <div class="text-center">
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $row->default_days_per_year }} days
                </span>
            </div>
        @endinteract

        @interact('column_paid', $row)
            <div class="text-center">
                @if($row->is_paid)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        Paid
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                        Unpaid
                    </span>
                @endif
            </div>
        @endinteract

        @interact('column_requires_approval', $row)
            <div class="text-center">
                @if($row->requires_approval)
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                @else
                    <svg class="w-5 h-5 text-gray-400 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                @endif
            </div>
        @endinteract

        @interact('column_status', $row)
            <div class="text-center">
                @if($row->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        Active
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                        Inactive
                    </span>
                @endif
            </div>
        @endinteract

        @interact('column_action', $row)
            <div class="flex justify-center gap-2">
                <button wire:click="openEditModal({{ $row->id }})"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium transition-colors">
                    Edit
                </button>
                <button wire:click="toggleStatus({{ $row->id }})"
                    class="px-3 py-1.5 {{ $row->is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded text-xs font-medium transition-colors">
                    {{ $row->is_active ? 'Deactivate' : 'Activate' }}
                </button>
                @if($row->leaveApplications->count() === 0)
                    <button wire:click="delete({{ $row->id }})"
                        wire:confirm="Are you sure you want to delete this leave type?"
                        class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-medium transition-colors">
                        Delete
                    </button>
                @endif
            </div>
        @endinteract

    </x-table>

    <!-- Create/Edit Modal -->
    @if($showModal)
        <div x-data="{ open: @entangle('showModal') }" x-show="open" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="$wire.closeModal()">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 dark:bg-black/70" @click="$wire.closeModal()"></div>

                <div class="relative bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $editMode ? 'Edit Leave Type' : 'Create Leave Type' }}
                            </h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                Configure leave type details and policies
                            </p>
                        </div>
                        <button @click="$wire.closeModal()"
                            class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Form -->
                    <form wire:submit.prevent="save" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Leave Type Name *
                                </label>
                                <input type="text" wire:model="name"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500"
                                    placeholder="e.g., Annual Leave" required>
                                @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Code -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Code *
                                </label>
                                <input type="text" wire:model="code"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500"
                                    placeholder="e.g., ANNUAL" required>
                                @error('code') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Description
                            </label>
                            <textarea wire:model="description" rows="2"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500"
                                placeholder="Brief description..."></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Default Days Per Year -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Default Days/Year *
                                </label>
                                <input type="number" wire:model="default_days_per_year" min="0"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500"
                                    required>
                                @error('default_days_per_year') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Max Consecutive Days -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Max Consecutive Days
                                </label>
                                <input type="number" wire:model="max_consecutive_days" min="1"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500">
                                @error('max_consecutive_days') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Min Notice Days -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Min Notice Days *
                                </label>
                                <input type="number" wire:model="min_notice_days" min="0"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500"
                                    required>
                                @error('min_notice_days') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Color Picker -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Display Color
                            </label>
                            <input type="color" wire:model="color"
                                class="w-20 h-10 border border-zinc-300 dark:border-zinc-600 rounded-lg">
                        </div>

                        <!-- Checkboxes -->
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="is_paid"
                                    class="w-4 h-4 text-indigo-600 border-zinc-300 rounded focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Paid Leave</span>
                            </label>

                            <label class="flex items-center">
                                <input type="checkbox" wire:model="requires_approval"
                                    class="w-4 h-4 text-indigo-600 border-zinc-300 rounded focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Requires Approval</span>
                            </label>

                            <label class="flex items-center">
                                <input type="checkbox" wire:model="requires_document"
                                    class="w-4 h-4 text-indigo-600 border-zinc-300 rounded focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Requires Supporting Document</span>
                            </label>

                            <label class="flex items-center">
                                <input type="checkbox" wire:model="is_active"
                                    class="w-4 h-4 text-indigo-600 border-zinc-300 rounded focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Active</span>
                            </label>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <button type="button" @click="$wire.closeModal()"
                                class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                                {{ $editMode ? 'Update' : 'Create' }} Leave Type
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
