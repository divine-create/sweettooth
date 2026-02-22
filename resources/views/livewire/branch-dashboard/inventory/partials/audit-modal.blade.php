@if($showAuditModal && $auditAction)
    <div x-data="{ open: @entangle('showAuditModal').live }" 
         x-show="open" 
         x-cloak
         x-transition
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="$wire.closeAuditModal()">

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl max-w-md w-full overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-5 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                    @switch($auditAction)
                        @case('create_item')
                            Request New Item Creation
                            @break
                        @case('update_item')
                            Request Item Update
                            @break
                        @case('delete_item')
                            Request Item Deletion
                            @break
                        @case('stock_adjustment')
                            Request Stock Adjustment
                            @break
                        @default
                            Approval Required
                    @endswitch
                </h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2">
                    @switch($auditAction)
                        @case('create_item')
                            Please explain why this new item is needed.
                            @break
                        @case('update_item')
                            Please explain the changes being made to this item.
                            @break
                        @case('delete_item')
                            This item will be permanently removed. This action cannot be undone.
                            @break
                        @case('stock_adjustment')
                            Please provide a detailed reason for adjusting the stock levels.
                            @break
                        @default
                            Your request will be reviewed by an administrator.
                    @endswitch
                </p>
            </div>

            <!-- Body -->
            <div class="px-6 py-5 max-h-96 overflow-y-auto">
                <div class="space-y-4">
                    <!-- Deletion Warning - Show related data for delete_item actions -->
                    @if($auditAction === 'delete_item' && !empty($relatedDataToDelete))
                        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <div class="flex gap-2 mb-3">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-sm font-semibold text-red-900 dark:text-red-100">
                                    Related Data Will Be Deleted
                                </p>
                            </div>
                            
                            <ul class="space-y-2 text-sm text-red-800 dark:text-red-200">
                                @if(!empty($relatedDataToDelete['recipes']))
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span><strong>{{ $relatedDataToDelete['recipes']['count'] }}</strong> Recipe(s)
                                            @if(count($relatedDataToDelete['recipes']['items']) <= 3)
                                                : {{ implode(', ', array_column($relatedDataToDelete['recipes']['items'], 'product_name')) }}
                                            @else
                                                using this item
                                            @endif
                                        </span>
                                    </li>
                                @endif

                                @if(!empty($relatedDataToDelete['products']))
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-orange-600 dark:text-orange-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <div>
                                            <p><strong>{{ $relatedDataToDelete['products']['count'] }}</strong> Product(s) affected: 
                                                {{ implode(', ', array_column($relatedDataToDelete['products']['items'], 'name')) }}
                                            </p>
                                        </div>
                                    </li>
                                @endif

                                @if(!empty($relatedDataToDelete['stocks']))
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span><strong>{{ $relatedDataToDelete['stocks']['count'] }}</strong> Stock Record(s) across branches</span>
                                    </li>
                                @endif

                                @if(!empty($relatedDataToDelete['purchases']))
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span><strong>{{ $relatedDataToDelete['purchases']['count'] }}</strong> Purchase Order(s)</span>
                                    </li>
                                @endif

                                @if(!empty($relatedDataToDelete['stock_movements']))
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span><strong>{{ $relatedDataToDelete['stock_movements']['count'] }}</strong> Stock Movement(s)</span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif

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
                    
                    @error('auditReason')
                        <div class="mt-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <div class="flex gap-3">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-sm text-red-800 dark:text-red-200">
                                    {{ $message }}
                                </p>
                            </div>
                        </div>
                    @enderror
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-3">
                <button
                    @click="$wire.closeAuditModal()"
                    class="px-5 py-2.5 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg font-medium hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                    Cancel
                </button>

                <!-- Unified Submit Button -->
                <button
                    x-data="{ loading: false }"
                    @click="loading = true; 
                            $wire
                                .call(
                                    @js(match($auditAction) {
                                        'create_item' => 'submitAuditRequest',
                                        'update_item' => 'submitAuditRequest',
                                        'delete_item' => 'submitItemDeletionRequest',
                                        'stock_adjustment' => 'submitAuditRequest',
                                        default => 'submitAuditRequest'
                                    }))
                                .finally(() => loading = false)"
                    :disabled="loading"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg font-medium transition flex items-center gap-2">
                    
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