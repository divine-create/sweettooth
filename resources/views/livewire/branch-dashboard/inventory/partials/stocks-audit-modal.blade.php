@if($showAuditModal && $auditAction)
    <div x-data="{ show: @entangle('showAuditModal').live }" x-show="show" x-cloak 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @keydown.escape.window="show = false; $wire.closeAuditModal()">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    @if($auditAction === 'stock_adjustment')
                        Request Stock Adjustment
                    @else
                        Confirm Action
                    @endif
                </h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                    @if($auditAction === 'stock_adjustment')
                        Please provide a detailed reason for this stock adjustment. Your request will be reviewed by an administrator.
                    @else
                        Please provide details about this action for audit purposes.
                    @endif
                </p>
            </div>

            <!-- Body -->
            <div class="px-6 py-4">
                <div class="space-y-4">
                    <!-- Reason/Notes Field -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Reason <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            wire:model="auditReason"
                            rows="4"
                            placeholder="Enter reason or notes for this action..."
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        ></textarea>
                        @error('auditReason')
                            <p class="text-red-500 text-xs mt-1 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18.101 12.93a1 1 0 00-1.415-1.414L11 16.586V9.5a1 1 0 10-2 0v7.086L3.314 11.516a1 1 0 00-1.414 1.414l9 9a1 1 0 001.414 0l9-9z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Info Box -->
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <p class="text-xs text-yellow-800 dark:text-yellow-200">
                                This action will be logged for audit purposes and requires administrator approval.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex gap-3 justify-end">
                <button
                    @click="show = false; $wire.closeAuditModal()"
                    type="button"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors duration-150"
                >
                    Cancel
                </button>
                @if($auditAction === 'stock_adjustment')
                    <button x-data="{ submitting: false }"
                        @click="submitting = true; $wire.proceedWithStockAdjustment().finally(() => { submitting = false })"
                        :disabled="submitting"
                        type="button"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                    >
                        <svg x-show="!submitting" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        <svg x-show="submitting" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        <span x-show="!submitting">Submit Request</span>
                        <span x-show="submitting">Submitting...</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif
