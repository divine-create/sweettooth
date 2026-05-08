<div class="p-3 space-y-4">
    <x-breadcrumb title="Variance Resolution" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Sales Dashboard'],
        ['label' => 'Variance Resolution'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-amber-600 to-amber-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Stock Variance Resolution</h2>
                <p class="text-sm opacity-90 mt-1">Review and resolve closing stock variances</p>
            </div>
            @if($pendingCount > 0)
                <div class="bg-white/20 px-4 py-2 rounded-lg text-center">
                    <div class="text-2xl font-bold">{{ $pendingCount }}</div>
                    <div class="text-xs opacity-90">Pending</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Search Product</label>
                <input type="text" wire:model.live.debounce.400ms="search"
                    placeholder="Product name..."
                    class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-amber-500" />
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Reason</label>
                <select wire:model.live="filterReason"
                    class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
                    <option value="">All Reasons</option>
                    <option value="shortage">Shortage</option>
                    <option value="excess">Excess</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Status</label>
                <select wire:model.live="filterStatus"
                    class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Resolved</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">From</label>
                    <input type="date" wire:model.live="startDate"
                        class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">To</label>
                    <input type="date" wire:model.live="endDate"
                        class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200" />
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        @foreach($headers as $header)
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                                {{ $header['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse($rows as $row)
                        @php
                            $variance = ($row->productStock?->closing_quantity ?? $row->quantity) - ($row->expected_quantity ?? 0);
                            $actualQty = $row->productStock?->closing_quantity ?? null;
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                            <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-200">
                                {{ $row->product?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $row->callback_date?->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 capitalize">
                                {{ $row->shift_type }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $row->expected_quantity !== null ? number_format($row->expected_quantity, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $actualQty !== null ? number_format($actualQty, 2) : '—' }}
                                @if($row->resolution_type === 'correction' && $row->corrected_quantity !== null)
                                    <span class="ml-1 text-xs text-blue-600 dark:text-blue-400">(corrected)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($row->expected_quantity !== null && $actualQty !== null)
                                    @php $diff = $actualQty - $row->expected_quantity; @endphp
                                    <span class="font-semibold {{ $diff < 0 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}">
                                        {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2) }}
                                    </span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $row->reason === 'shortage' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                    {{ ucfirst($row->reason) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($row->status === 'pending')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                        Pending
                                    </span>
                                @elseif($row->resolution_type === 'write_off')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                                        Written Off
                                    </span>
                                @elseif($row->resolution_type === 'correction')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        Corrected
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        Resolved
                                    </span>
                                @endif
                                @if($row->resolvedBy)
                                    <div class="text-xs text-zinc-400 mt-0.5">by {{ $row->resolvedBy->name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($row->status === 'pending')
                                    <div class="flex items-center gap-2">
                                        <button wire:click="openWriteOffModal({{ $row->id }})"
                                            class="px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-600 rounded hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                                            Write Off
                                        </button>
                                        <button wire:click="openCorrectionModal({{ $row->id }})"
                                            class="px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 border border-blue-300 dark:border-blue-700 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                            Correct Count
                                        </button>
                                    </div>
                                @else
                                    @if($row->resolution_notes)
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-[200px] truncate" title="{{ $row->resolution_notes }}">
                                            {{ $row->resolution_notes }}
                                        </p>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($headers) }}" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-8 h-8 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm">No variances found for the selected filters</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $rows->links() }}
            </div>
        @endif
    </div>

    <!-- Write-Off Modal -->
    @if($showWriteOffModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6 space-y-4">
                <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200">Write Off Variance</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Mark this variance as a confirmed loss. The stock record will remain unchanged — the actual closing quantity is already recorded.
                </p>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Reason / Notes <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="writeOffNotes" rows="3"
                        placeholder="e.g. Confirmed theft, spillage during service, counting error confirmed..."
                        class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-amber-500 resize-none"></textarea>
                    @error('writeOffNotes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button wire:click="$set('showWriteOffModal', false)"
                        class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                    <button wire:click="confirmWriteOff"
                        class="px-4 py-2 text-sm font-medium text-white bg-zinc-700 dark:bg-zinc-600 rounded-lg hover:bg-zinc-800 dark:hover:bg-zinc-500">
                        Confirm Write-Off
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Correction Modal -->
    @if($showCorrectionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6 space-y-4">
                <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200">Correct Stock Count</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Update the recorded closing quantity. This will become the opening baseline for the next shift.
                </p>

                @if($correctionExpectedQty !== null)
                    <div class="grid grid-cols-2 gap-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3 text-sm">
                        <div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">Expected Closing</div>
                            <div class="font-semibold text-zinc-800 dark:text-zinc-200">{{ number_format($correctionExpectedQty, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">Currently Recorded</div>
                            <div class="font-semibold text-zinc-800 dark:text-zinc-200">{{ number_format($correctionCurrentQty, 2) }}</div>
                        </div>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Corrected Quantity <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" wire:model.live="correctedQuantity"
                        class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500" />
                    @error('correctedQuantity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror

                    @if($correctedQuantity !== '' && is_numeric($correctedQuantity) && $correctionCurrentQty !== null)
                        @php $diff = (float)$correctedQuantity - $correctionCurrentQty; @endphp
                        <p class="text-xs mt-1 {{ $diff == 0 ? 'text-zinc-400' : ($diff > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400') }}">
                            Change: {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2) }} from currently recorded
                        </p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Reason / Notes <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="correctionNotes" rows="3"
                        placeholder="e.g. Recount showed different result, previous entry was misread..."
                        class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    @error('correctionNotes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button wire:click="$set('showCorrectionModal', false)"
                        class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                    <button wire:click="confirmCorrection"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Apply Correction
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
