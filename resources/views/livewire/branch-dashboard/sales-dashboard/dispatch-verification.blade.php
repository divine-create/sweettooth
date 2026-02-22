<div>
    @if($request)
        <div class="dispatch-verification">
            <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            Dispatch Verification
                        </h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            Review and accept/reject dispatched products
                        </p>
                    </div>
                    <div class="text-sm text-zinc-500">
                        Request #{{ $request->id }}
                    </div>
                </div>

                <!-- Request Info -->
                <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Department:</span>
                            <div class="text-zinc-900 dark:text-zinc-100">{{ $request->productionDepartment->name ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Status:</span>
                            <div class="text-zinc-900 dark:text-zinc-100">{{ ucfirst(str_replace('_', ' ', $request->status)) }}</div>
                        </div>
                        <div>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Created:</span>
                            <div class="text-zinc-900 dark:text-zinc-100">{{ $request->created_at->format('M d, Y H:i') }}</div>
                        </div>
                        <div>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Priority:</span>
                            <div class="text-zinc-900 dark:text-zinc-100">{{ ucfirst($request->priority ?? 'normal') }}</div>
                        </div>
                    </div>
                    @if($request->notes)
                        <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Notes:</span>
                            <div class="text-zinc-900 dark:text-zinc-100 text-sm mt-1">{{ $request->notes }}</div>
                        </div>
                    @endif
                </div>

                <!-- Dispatches -->
                @if($dispatches->count() > 0)
                    <div class="space-y-4">
                        <h4 class="font-medium text-zinc-900 dark:text-zinc-100">Pending Verification</h4>

                        @foreach($dispatches as $dispatch)
                            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex-1">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $dispatch->product->name ?? 'Unknown Product' }}
                                        </div>
                                        <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                            SKU: {{ $dispatch->product->sku ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ number_format($dispatch->quantity_dispatched, 2) }} units dispatched
                                        </div>
                                        <div class="text-xs text-zinc-500">
                                            Requested: {{ number_format($dispatch->quantity_produced ?? 0, 2) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <button
                                        wire:click="acceptDispatch({{ $dispatch->id }})"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-500 text-sm font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Accept
                                    </button>

                                    <button
                                        wire:click="showRejectionForm"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-500 text-sm font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Reject
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-zinc-500">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm font-medium">No pending dispatches</p>
                        <p class="text-xs mt-1">All dispatches have been verified</p>
                    </div>
                @endif

                <!-- Progress Timeline -->
                @if($request->progressFeedback->count() > 0)
                    <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                        <h4 class="font-medium text-zinc-900 dark:text-zinc-100 mb-3">Progress Timeline</h4>
                        <div class="space-y-2">
                            @foreach($request->progressFeedback->sortByDesc('created_at') as $feedback)
                                <div class="flex items-start gap-3 text-sm">
                                    <div class="w-2 h-2 rounded-full bg-blue-500 mt-2 flex-shrink-0"></div>
                                    <div class="flex-1">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ ucfirst(str_replace('_', ' ', $feedback->milestone)) }}
                                            @if($feedback->progress_percentage)
                                                ({{ $feedback->progress_percentage }}%)
                                            @endif
                                        </div>
                                        <div class="text-xs text-zinc-500 mt-1">
                                            {{ $feedback->created_at->format('M d, H:i') }} • {{ $feedback->updatedBy->name ?? 'System' }}
                                        </div>
                                        @if($feedback->notes)
                                            <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">
                                                {{ $feedback->notes }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Rejection Modal -->
            @if($showRejectionForm)
                <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50" wire:click="cancelRejection"></div>
                    <div class="relative w-full max-w-md bg-white dark:bg-zinc-900 rounded-xl shadow-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Reject Dispatch</h3>
                            <button wire:click="cancelRejection" class="text-zinc-400 hover:text-zinc-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <form wire:submit="rejectDispatch" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    Reason for Rejection *
                                </label>
                                <select wire:model="rejectionReason" class="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2">
                                    <option value="">Select reason</option>
                                    <option value="quality">Quality Issues</option>
                                    <option value="quantity">Incorrect Quantity</option>
                                    <option value="missing">Missing Items</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('rejectionReason')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    Details *
                                </label>
                                <textarea
                                    wire:model="rejectionNotes"
                                    rows="4"
                                    class="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2"
                                    placeholder="Please provide details about why this dispatch is being rejected..."
                                ></textarea>
                                @error('rejectionNotes')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="flex items-center gap-3 pt-4">
                                <button
                                    type="button"
                                    wire:click="cancelRejection"
                                    class="flex-1 px-4 py-2 rounded-md border border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="flex-1 px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-500">
                                    Reject Dispatch
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="text-center py-8 text-zinc-500">
            <p>No request selected</p>
        </div>
    @endif
</div>