<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Create / Edit Form Card -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-8 py-6 bg-gradient-to-r from-blue-600 to-blue-700">
            <h2 class="text-2xl font-bold text-white">
              Edit Category: <small><i>{{$name}}</i></small> 
            </h2>
        </div>

        <div class="p-8">
            <form wire:submit.prevent="editCategory" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Category Name -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">
                            Category Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            wire:model="name"
                            class="w-full px-5 py-4 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-shadow"
                            placeholder="e.g., Human Resources, IT Support, Finance"
                            required
                        >
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">
                            Description <span class="text-zinc-500 font-medium">(Optional)</span>
                        </label>
                        <textarea 
                            wire:model="description" 
                            rows="5"
                            class="w-full px-5 py-4 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 resize-none transition-shadow"
                            placeholder="Describe what this category is used for..."
                        ></textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-4 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                  
                    <button 
                        type="submit"
                        class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                               M12 4v16m8-8H4" />
                        </svg>
                        <span wire:loading.remove wire:target="saveCategory">Edit Category</span>
                        <span wire:loading wire:target="saveCategory">Editing...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>