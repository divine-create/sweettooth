<div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6 border border-zinc-200 dark:border-zinc-700">
    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if (!$request)
        <p class="text-zinc-500 dark:text-zinc-400">No production request selected</p>
    @else
        <div class="space-y-6">
            <!-- Request Info -->
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Request #{{ $request->id }}</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase">Quantity</p>
                        <p class="font-semibold text-zinc-900 dark:text-white">
                            {{ number_format($request->planned_production_quantity, 2) }}
                        </p>
                        @if($request->requested_units && $request->requested_units != $request->planned_production_quantity)
                            <p class="text-xs text-orange-600 dark:text-orange-400">
                                Sales Requested: {{ number_format($request->requested_units, 2) }}
                            </p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase">Status</p>
                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold"
                            :class="@switch($request->status)
                                @case('pending') 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' @break
                                @case('in_progress') 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' @break
                                @case('quality_check') 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' @break
                                @case('completed') 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' @break
                                @default 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-300'
                            @endswitch"
                        >
                            {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase">Priority</p>
                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold"
                            :class="$request->priority === 'urgent' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'"
                        >
                            {{ ucfirst($request->priority) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase">Dispatch Target</p>
                        <p class="font-semibold text-zinc-900 dark:text-white">
                            {{ $dispatchSalesDepartmentName ?? 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase">Created</p>
                        <p class="font-semibold text-zinc-900 dark:text-white">{{ $request->created_at->format('M d') }}</p>
                    </div>
                </div>
            </div>

            <!-- Progress Form -->
            @if ($showForm)
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-6">
                    <h4 class="font-semibold text-zinc-900 dark:text-white mb-4">Update Progress</h4>
                    <form wire:submit="updateProgress" class="space-y-4">
                        <!-- Milestone Selection -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Milestone <span class="text-red-500">*</span>
                            </label>
                            <select 
                                wire:model="selectedMilestone"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                            >
                                @foreach ($milestones as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('selectedMilestone')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Progress Percentage -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Progress: <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $progressPercentage }}%</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <input 
                                    wire:model="progressPercentage"
                                    type="range" 
                                    min="0" 
                                    max="100" 
                                    step="5"
                                    class="flex-1"
                                />
                                <input 
                                    wire:model="progressPercentage"
                                    type="number" 
                                    min="0" 
                                    max="100"
                                    class="w-20 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                                />
                            </div>
                            @error('progressPercentage')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Notes (Optional)
                            </label>
                            <textarea 
                                wire:model="notes"
                                rows="3"
                                placeholder="Add any notes about progress..."
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400"
                            ></textarea>
                        </div>

                        <!-- ETA Override -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                ETA Override (Minutes)
                            </label>
                            <input
                                type="number"
                                min="0"
                                wire:model="etaOverrideMinutes"
                                placeholder="Leave blank for auto ETA"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                            />
                            @error('etaOverrideMinutes')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-3 justify-end pt-2">
                            <button 
                                type="button"
                                wire:click="$set('showForm', false)"
                                class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition"
                            >
                                Update Progress
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div>
                    <button 
                        wire:click="$set('showForm', true)"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition"
                    >
                        Add Progress Update
                    </button>
                </div>
            @endif

            @if($dispatchSalesDepartmentId || count($availableSalesDepartments) > 0)
                @php
                    $dispatched = $request->dispatches->sum('quantity');
                    $remaining = max(0, ($request->planned_production_quantity ?? 0) - $dispatched);
                    $isSalesDemand = ($request->source_type ?? null) === 'SALES_DEMAND';
                @endphp
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-6">
                    <h4 class="font-semibold text-zinc-900 dark:text-white mb-4">Dispatch to {{ $dispatchSalesDepartmentName ?? 'Sales Dept' }}</h4>
                    <div class="flex flex-wrap items-center gap-3 mb-3 text-sm text-zinc-600 dark:text-zinc-300">
                        <span>Dispatched: <strong>{{ number_format($dispatched,2) }}</strong></span>
                        <span>Remaining: <strong>{{ number_format($remaining,2) }}</strong></span>
                    </div>
                    <form wire:submit.prevent="dispatchToSales" class="flex flex-wrap items-center gap-3">
                        <select wire:model="dispatchSalesDepartmentId"
                                @disabled($isSalesDemand)
                                class="w-52 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-sm">
                            <option value="">Select sales department</option>
                            @foreach($availableSalesDepartments as $dept)
                                <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                            @endforeach
                        </select>
                        <input type="number" min="0.01" step="0.01" wire:model="dispatchQuantity"
                               class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-sm"
                               placeholder="Qty">
                        <button type="submit"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold">
                            Dispatch
                        </button>
                        @error('dispatchQuantity')
                            <p class="text-red-500 text-sm">{{ $message }}</p>
                        @enderror
                        @error('dispatchSalesDepartmentId')
                            <p class="text-red-500 text-sm">{{ $message }}</p>
                        @enderror
                    </form>
                    @if($request->dispatches->count())
                        <div class="mt-4 space-y-2">
                            @foreach($request->dispatches as $dispatch)
                                <div class="text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ number_format($dispatch->quantity,2) }} sent • Status: {{ $dispatch->status }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <!-- Progress History -->
            @if ($request->progressFeedback->count() > 0)
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-6">
                    <h4 class="font-semibold text-zinc-900 dark:text-white mb-4">Progress History</h4>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @foreach ($request->progressFeedback->sortByDesc('created_at') as $progress)
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg border border-zinc-200 dark:border-zinc-600">
                                <div class="flex justify-between items-start gap-2 mb-2">
                                    <div>
                                        <p class="font-semibold text-zinc-900 dark:text-white">
                                            {{ $milestones[$progress->milestone] ?? 'Unknown' }}
                                        </p>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                            {{ $progress->created_at->format('M d, Y H:i') }}
                                        </p>
                                    </div>
                                    <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                        {{ $progress->progress_percentage }}%
                                    </span>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mb-2">
                                    <div class="w-full bg-zinc-300 dark:bg-zinc-600 rounded-full h-2">
                                        <div 
                                            class="bg-blue-600 h-2 rounded-full transition-all"
                                            style="width: {{ $progress->progress_percentage }}%"
                                        ></div>
                                    </div>
                                </div>

                                @if ($progress->notes)
                                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $progress->notes }}</p>
                                @endif

                                @if ($progress->updatedBy)
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-2">
                                        Updated by: {{ $progress->updatedBy->name }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-6 text-center">
                    <p class="text-zinc-500 dark:text-zinc-400 text-sm">No progress updates yet</p>
                </div>
            @endif
        </div>
    @endif
</div>
