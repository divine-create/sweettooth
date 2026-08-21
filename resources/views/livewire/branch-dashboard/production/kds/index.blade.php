<div wire:poll.5s class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
            Kitchen Display System (KDS)
        </h2>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-500">Auto-updating every 5s</span>
            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
        </div>
    </div>

    @if($items->isEmpty())
        <div class="flex flex-col items-center justify-center h-64 bg-white dark:bg-zinc-800 rounded-xl border border-dashed border-gray-300 dark:border-zinc-700">
            <x-heroicon-o-check-circle class="w-16 h-16 text-gray-400 mb-4" />
            <h3 class="text-xl font-medium text-gray-900 dark:text-gray-100">All Caught Up!</h3>
            <p class="text-gray-500 mt-2">No pending production requests at the moment.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($items->groupBy('sales_production_request_id') as $requestId => $groupedItems)
                @php 
                    $request = $groupedItems->first()->request; 
                    $isUrgent = $request->priority === 'urgent' || $request->priority === 'high';
                @endphp
                
                <div class="flex flex-col bg-white dark:bg-zinc-800 rounded-xl shadow-md overflow-hidden border-2 {{ $isUrgent ? 'border-red-500' : 'border-transparent' }}">
                    <!-- Ticket Header -->
                    <div class="p-4 {{ $isUrgent ? 'bg-red-50 dark:bg-red-900/30' : 'bg-gray-50 dark:bg-zinc-900' }} border-b border-gray-100 dark:border-zinc-700">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100">
                                    {{ $request->request_number }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    From: {{ $request->salesDepartment->name ?? 'Sales' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $isUrgent ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ ucfirst($request->priority) }}
                                </span>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $request->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket Body (Items) -->
                    <div class="p-4 flex-grow space-y-4">
                        @foreach($groupedItems as $item)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700/50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $item->product->name ?? $item->recipe->product_name ?? 'Unknown Product' }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Qty: <span class="font-bold text-gray-700 dark:text-gray-300">{{ number_format($item->quantity_requested, 2) }}</span>
                                    </p>
                                </div>
                                
                                <button 
                                    wire:click="approveItem({{ $item->id }})"
                                    wire:loading.attr="disabled"
                                    class="p-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1"
                                    title="Mark as Approved/Started"
                                >
                                    <x-heroicon-o-check class="w-5 h-5" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Audio Element for Notification -->
    <audio id="kdsNotificationSound" preload="auto">
        <source src="{{ asset('audio/kds-notification.ogg') }}" type="audio/ogg">
    </audio>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('play-kds-notification', (event) => {
                const audio = document.getElementById('kdsNotificationSound');
                if (audio) {
                    // Modern browsers block autoplay without user interaction.
                    // If it fails, we catch the promise error silently.
                    audio.play().catch(error => {
                        console.log("Audio autoplay blocked by browser until user interacts with the page.");
                    });
                }
            });
        });
    </script>
</div>
