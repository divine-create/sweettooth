@if($showBatchPanel)
<div class="fixed inset-0 z-50 flex">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/40" wire:click="closeBatchPanel"></div>

    {{-- Slide-over panel --}}
    <div class="relative ml-auto w-full max-w-2xl bg-white dark:bg-zinc-900 shadow-xl flex flex-col h-full overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    Stock Batches
                </h2>
                @if($batchPanelStock)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        {{ $batchPanelStock->item?->name ?? 'Unknown Item' }}
                        &mdash; {{ number_format($batchPanelStock->quantity_available, 2) }} available
                    </p>
                @endif
            </div>
            <button wire:click="closeBatchPanel"
                class="p-2 rounded-lg text-zinc-500 hover:text-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Legend --}}
        <div class="flex items-center gap-4 px-6 py-2 text-xs border-b border-zinc-100 dark:border-zinc-800 text-zinc-500">
            <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500"></span> Expired</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-400"></span> Expiring soon</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-green-500"></span> Good</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-zinc-400"></span> No expiry / depleted</span>
        </div>

        {{-- Batch table --}}
        <div class="flex-1 overflow-y-auto px-6 py-4">
            @if($batchPanelData->isEmpty())
                <p class="text-center text-zinc-500 dark:text-zinc-400 py-12">No batch records found for this item.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left py-2 pr-3 font-semibold text-zinc-700 dark:text-zinc-300">Batch No.</th>
                            <th class="text-left py-2 pr-3 font-semibold text-zinc-700 dark:text-zinc-300">Received</th>
                            <th class="text-left py-2 pr-3 font-semibold text-zinc-700 dark:text-zinc-300">Expiry</th>
                            <th class="text-right py-2 pr-3 font-semibold text-zinc-700 dark:text-zinc-300">Received</th>
                            <th class="text-right py-2 font-semibold text-zinc-700 dark:text-zinc-300">Remaining</th>
                            <th class="text-center py-2 font-semibold text-zinc-700 dark:text-zinc-300">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($batchPanelData as $batch)
                            @php
                                $days = $batch->daysUntilExpiry();
                                $isExpired = $batch->expiry_date && $batch->expiry_date->isPast();
                                $warningDays = (int) \App\Helpers\Settings::inventoryManagement('expiry_warning_days', 7);
                                $isExpiringSoon = !$isExpired && $days !== null && $days <= $warningDays;

                                $expiryClass = match(true) {
                                    $isExpired        => 'text-red-600 dark:text-red-400 font-semibold',
                                    $isExpiringSoon   => 'text-amber-600 dark:text-amber-400 font-semibold',
                                    default           => 'text-zinc-700 dark:text-zinc-300',
                                };
                                $dotClass = match(true) {
                                    $batch->status === 'depleted' => 'bg-zinc-400',
                                    $isExpired                    => 'bg-red-500',
                                    $isExpiringSoon               => 'bg-amber-400',
                                    $batch->expiry_date === null  => 'bg-zinc-400',
                                    default                       => 'bg-green-500',
                                };
                            @endphp
                            <tr class="{{ $batch->status !== 'active' ? 'opacity-50' : '' }}">
                                <td class="py-2.5 pr-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block w-2 h-2 rounded-full {{ $dotClass }} flex-shrink-0"></span>
                                        <span class="text-zinc-800 dark:text-zinc-200">
                                            {{ $batch->batch_number ?? '—' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-2.5 pr-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $batch->received_at?->format('Y-m-d') ?? '—' }}
                                </td>
                                <td class="py-2.5 pr-3 {{ $expiryClass }}">
                                    @if($batch->expiry_date)
                                        {{ $batch->expiry_date->format('Y-m-d') }}
                                        @if($isExpired)
                                            <span class="ml-1 text-xs">(expired)</span>
                                        @elseif($isExpiringSoon)
                                            <span class="ml-1 text-xs">({{ $days }}d left)</span>
                                        @endif
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="py-2.5 pr-3 text-right text-zinc-700 dark:text-zinc-300">
                                    {{ number_format($batch->quantity_received, 2) }}
                                </td>
                                <td class="py-2.5 text-right font-semibold {{ (float)$batch->quantity_remaining <= 0 ? 'text-zinc-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                    {{ number_format($batch->quantity_remaining, 2) }}
                                </td>
                                <td class="py-2.5 text-center">
                                    @php
                                        $statusBadge = match($batch->status) {
                                            'active'   => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                            'depleted' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                                            'expired'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                            default    => '',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $statusBadge }}">
                                        {{ ucfirst($batch->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endif
