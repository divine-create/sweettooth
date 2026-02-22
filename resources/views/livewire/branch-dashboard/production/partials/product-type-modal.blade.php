<!-- Create/Edit Product Type Modal (Slide-in) -->
<div x-data="{ show: @entangle('showModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-hidden"
    @keydown.escape.window="show = false">
    <!-- Backdrop -->
    <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50" @click="$wire.closeModal()">
    </div>

    <!-- Slide-in Panel -->
    <div x-show="show" x-transition:enter="transform transition ease-in-out duration-300"
        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 w-full md:w-1/2 lg:w-1/3 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

        <!-- Header -->
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                {{ $isEditing ? 'Edit Product Type' : 'Add New Product Type' }}</h2>
            <button wire:click="closeModal"
                class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Scrollable Form Content -->
        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-6">
            <form wire:submit.prevent="save">
                <!-- Department -->
                <div>
                    <label for="department_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Select Department
                    </label>

                    <select wire:model="department_id" id="department_id"
                        class="w-full px-3 py-2 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700
                                   rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500
                                   focus:border-indigo-500 text-zinc-900 dark:text-zinc-100 transition duration-150 ease-in-out"
                        :disabled="$isEditing">
                        @if(is_super_admin())
                            <option value="">Select Department</option>
                            @foreach($productionDepartments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        @else
                            <option value="{{ $department->id }}" selected>
                                {{ $department->name }}
                            </option>
                        @endif
                    </select>

                    @error('department_id')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Name *</label>
                    <input type="text" wire:model="name"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter product type name" required>
                    @error('name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Code -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Code *</label>
                    <input type="text" wire:model="code"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 font-mono uppercase"
                        placeholder="e.g., PT, GB, GF" maxlength="50" required>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Short code for product type (will be
                        converted to uppercase)</p>
                    @error('code')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Description</label>
                    <textarea wire:model="description" rows="3"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter description"></textarea>
                    @error('description')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>


                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Status *</label>
                    <x-select.styled wire:model="status" :options="[
                        ['label' => 'Active', 'value' => 'active'],
                        ['label' => 'Inactive', 'value' => 'inactive'],
                    ]" select="label:label|value:value"
                        placeholder="Select Status" required />
                    @error('status')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end space-x-3">
            <button wire:click="closeModal"
                class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                Cancel
            </button>
            <button wire:click="save" wire:loading.attr="disabled"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center"
                :class="{ 'opacity-75 cursor-not-allowed': $wire.loading }">
                <span wire:loading.remove wire:target="save" class="flex items-center">
                    {{ $isEditing ? 'Update' : 'Create' }}
                </span>
                <span wire:loading wire:target="save" class="flex items-center">
                    <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                </span>
            </button>
        </div>
    </div>
</div>
