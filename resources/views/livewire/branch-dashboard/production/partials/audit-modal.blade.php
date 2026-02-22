@if($showAuditModal && $auditAction)
    <div x-data="{ open: @entangle('showAuditModal').live }" 
         x-show="open" 
         x-cloak
         x-transition
         class="fixed inset-0 bg-black/50 z-[60] flex items-center justify-center p-4"
         @keydown.escape.window="$wire.closeAuditModal()">

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl max-w-md w-full overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-5 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                    @switch($auditAction)
                        @case('create')
                            Request Product Creation
                            @break
                        @case('edit')
                            Request Product Update
                            @break
                        @case('delete')
                            Request Item Deletion
                            @break
                        @case('create_recipe')
                            Request Recipe Creation
                            @break
                        @case('edit_recipe')
                            Request Recipe Update
                            @break
                        @case('delete_recipe')
                            Request Recipe Deletion
                            @break
                        @default
                            Approval Required
                    @endswitch
                </h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2">
                    @switch($auditAction)
                        @case('create')
                            Please explain why this new product is needed.
                            @break
                        @case('edit')
                            Please explain the changes being made to this product.
                            @break
                        @case('delete')
                            This item will be permanently removed. This action cannot be undone.
                            @break
                        @case('create_recipe')
                            Please explain why this new recipe is needed.
                            @break
                        @case('edit_recipe')
                            Please explain the changes being made to this recipe.
                            @break
                        @case('delete_recipe')
                            This recipe will be permanently removed. This action cannot be undone.
                            @break
                        @default
                            Your request will be reviewed by an administrator.
                    @endswitch
                </p>
            </div>

            <!-- Body -->
            <div class="px-6 py-5 max-h-96 overflow-y-auto">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Reason <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            wire:model.live="auditReason"
                            rows="4"
                            placeholder="Provide a clear and detailed explanation..."
                            class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        ></textarea>
                        @error('auditReason')
                            <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm text-amber-800 dark:text-amber-200">
                                This action requires approval. It will be reviewed and processed by a super administrator.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-3">
                <button
                    @click="$wire.closeAuditModal()"
                    class="px-5 py-2.5 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg font-medium hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                    Cancel
                </button>

                <button
                    x-data="{ loading: false }"
                    @click="loading = true; $wire.submitAuditRequest().finally(() => loading = false)"
                    :disabled="loading"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg font-medium transition flex items-center gap-2 cursor-pointer">
                    
                    <template x-if="!loading">
                        <span>Submit Request</span>
                    </template>
                    <template x-if="loading">
                        <span class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Submitting...
                        </span>
                    </template>
                </button>
            </div>
        </div>
    </div>
@endif
