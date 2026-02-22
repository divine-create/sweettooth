<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h3 class="font-semibold text-zinc-900 dark:text-white">Performance Records</h3>
        <button
            wire:click="toggleForm"
            class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
        >
            {{ $showForm ? 'Cancel' : 'Add Record' }}
        </button>
    </div>

    <!-- Add Performance Form -->
    @if($showForm)
        <form wire:submit="recordPerformance" class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Record Date *</label>
                    <input
                        type="date"
                        wire:model="recordDate"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    @error('recordDate')<span class="text-red-600 text-sm">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Overall Score (0-100) *</label>
                    <input
                        type="number"
                        wire:model="overallScore"
                        min="0"
                        max="100"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    @error('overallScore')<span class="text-red-600 text-sm">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Quality Score (0-100) *</label>
                    <input
                        type="number"
                        wire:model="qualityScore"
                        min="0"
                        max="100"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    @error('qualityScore')<span class="text-red-600 text-sm">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Delivery Score (0-100) *</label>
                    <input
                        type="number"
                        wire:model="deliveryScore"
                        min="0"
                        max="100"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    @error('deliveryScore')<span class="text-red-600 text-sm">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Price Competitiveness (0-100) *</label>
                    <input
                        type="number"
                        wire:model="priceCompetitivenessScore"
                        min="0"
                        max="100"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    @error('priceCompetitivenessScore')<span class="text-red-600 text-sm">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Response Time Score (0-100) *</label>
                    <input
                        type="number"
                        wire:model="responseTimeScore"
                        min="0"
                        max="100"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    @error('responseTimeScore')<span class="text-red-600 text-sm">{{ $message }}</span>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Compliance Score (0-100) *</label>
                    <input
                        type="number"
                        wire:model="complianceScore"
                        min="0"
                        max="100"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    @error('complianceScore')<span class="text-red-600 text-sm">{{ $message }}</span>@enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Notes</label>
                <textarea
                    wire:model="notes"
                    rows="3"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Additional notes about this performance record..."
                ></textarea>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Save Record
                </button>
            </div>
        </form>
    @endif

    <!-- Performance History -->
    <div class="space-y-3">
        @forelse($performanceHistory as $record)
            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $record->record_date->format('M d, Y') }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Recorded by {{ $record->recordedBy?->name ?? 'System' }}</p>
                    </div>
                    <button
                        wire:click="deletePerformanceRecord({{ $record->id }})"
                        class="text-red-600 dark:text-red-400 hover:text-red-800 text-sm"
                    >
                        Delete
                    </button>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-3">
                    <div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Quality</p>
                        <p class="text-lg font-bold {{ $this->getScoreColor($record->quality_score) }} px-2 py-1 rounded">
                            {{ $record->quality_score }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Delivery</p>
                        <p class="text-lg font-bold {{ $this->getScoreColor($record->delivery_score) }} px-2 py-1 rounded">
                            {{ $record->delivery_score }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Price</p>
                        <p class="text-lg font-bold {{ $this->getScoreColor($record->price_competitiveness_score) }} px-2 py-1 rounded">
                            {{ $record->price_competitiveness_score }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Response</p>
                        <p class="text-lg font-bold {{ $this->getScoreColor($record->response_time_score) }} px-2 py-1 rounded">
                            {{ $record->response_time_score }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Overall</p>
                        <p class="text-lg font-bold {{ $this->getScoreColor($record->overall_score) }} px-2 py-1 rounded">
                            {{ $record->overall_score }}
                        </p>
                    </div>
                </div>

                @if($record->notes)
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 p-2 rounded border-l-4 border-blue-500">
                        {{ $record->notes }}
                    </p>
                @endif
            </div>
        @empty
            <p class="text-zinc-500 dark:text-zinc-400 text-sm">No performance records yet</p>
        @endforelse
    </div>
</div>
