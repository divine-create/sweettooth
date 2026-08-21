<div wire:poll.5s class="p-2 md:p-4">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 bg-white dark:bg-zinc-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-zinc-800">
        <div>
            <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center">
                <x-icon name="fire" class="w-8 h-8 text-orange-500 mr-2" />
                Kitchen Display System
            </h2>
            <p class="text-sm text-gray-500 dark:text-zinc-400 mt-1">Live Production Orders</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 md:mt-0 bg-gray-50 dark:bg-zinc-800 px-4 py-2 rounded-lg border border-gray-100 dark:border-zinc-700">
            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30">
                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
            </div>
            <span class="text-sm font-medium text-gray-700 dark:text-zinc-300">Auto-sync Active</span>
        </div>
    </div>

    @if($items->isEmpty())
        <div class="flex flex-col items-center justify-center h-80 bg-white dark:bg-zinc-900 rounded-2xl border-2 border-dashed border-gray-300 dark:border-zinc-700 shadow-sm">
            <div class="p-6 bg-gray-50 dark:bg-zinc-800 rounded-full mb-4">
                <x-icon name="check-badge" class="w-16 h-16 text-gray-400 dark:text-zinc-500" />
            </div>
            <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-200">All Clear!</h3>
            <p class="text-gray-500 dark:text-zinc-400 mt-2 text-lg">No pending orders in the queue.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 md:gap-6">
            @foreach($items->groupBy('sales_production_request_id') as $requestId => $groupedItems)
                @php 
                    $request = $groupedItems->first()->request; 
                    $isUrgent = $request->priority === 'urgent' || $request->priority === 'high';
                    
                    // Calculate wait time in minutes and ensure it's an integer
                    $waitTime = (int) $request->created_at->diffInMinutes(now());
                    $isWarning = $waitTime > 15 && !$isUrgent;
                    $isDanger = $waitTime > 30 || $isUrgent;
                    
                    $headerBg = $isDanger ? 'bg-red-600' : ($isWarning ? 'bg-amber-500' : 'bg-gray-800 dark:bg-zinc-950');
                    $headerText = 'text-white';
                    $ticketBorder = $isDanger ? 'border-red-500' : ($isWarning ? 'border-amber-400' : 'border-gray-200 dark:border-zinc-700');
                @endphp
                
                <!-- Ticket Card -->
                <div class="flex flex-col bg-white dark:bg-zinc-900 rounded-xl shadow-lg overflow-hidden border-2 {{ $ticketBorder }} transition-all duration-300 hover:shadow-xl relative">
                    
                    <!-- Ticket Header -->
                    <div class="p-4 {{ $headerBg }} {{ $headerText }}">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-black text-xl tracking-wider font-mono">
                                #{{ substr($request->request_number, -5) }}
                            </h3>
                            
                            @if($isDanger || $isWarning)
                                <span class="flex items-center animate-pulse">
                                    <x-icon name="clock" class="w-5 h-5 mr-1" />
                                    <span class="font-bold text-lg">{{ $waitTime }}m</span>
                                </span>
                            @else
                                <span class="font-medium text-sm opacity-80">{{ $waitTime }}m ago</span>
                            @endif
                        </div>
                        <div class="flex justify-between items-end">
                            <p class="text-sm font-medium opacity-90 truncate max-w-[70%]">
                                {{ $request->salesDepartment->name ?? 'Sales' }}
                            </p>
                            @if($isUrgent)
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-white text-red-600 uppercase tracking-widest shadow-sm">
                                    Urgent
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Ticket separator (jagged edge effect using SVG or CSS) -->
                    <div class="h-2 w-full {{ $headerBg }} opacity-20" style="background-image: radial-gradient(circle at 4px 0px, transparent 4px, currentColor 5px); background-size: 10px 10px; background-repeat: repeat-x;"></div>

                    <!-- Ticket Body (Items) -->
                    <div class="p-3 flex-grow flex flex-col gap-2 bg-[#fdfbf7] dark:bg-zinc-800">
                        @foreach($groupedItems as $item)
                            <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-700 rounded-lg shadow-sm hover:border-gray-300 dark:hover:border-zinc-500 transition-colors group">
                                <div class="flex items-start gap-3 w-full">
                                    <div class="flex-shrink-0 pt-0.5">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-100 dark:bg-zinc-800 text-gray-800 dark:text-gray-200 font-bold font-mono text-lg border border-gray-200 dark:border-zinc-700 shadow-inner">
                                            {{ round($item->quantity_requested) }}
                                        </span>
                                    </div>
                                    <div class="flex-grow">
                                        <p class="font-bold text-gray-800 dark:text-gray-100 text-lg leading-tight group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                            {{ $item->product->name ?? $item->recipe->product_name ?? 'Unknown Product' }}
                                        </p>
                                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mt-1">
                                            {{ $item->product->category->name ?? 'Kitchen' }}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0 ml-2">
                                        <button 
                                            wire:click="approveItem({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                            class="p-2.5 bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/40 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-800/50 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 hover:scale-105 active:scale-95"
                                            title="Mark as Ready"
                                        >
                                            <x-icon name="check" class="w-6 h-6 stroke-2" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Ticket Footer -->
                    <div class="px-4 py-3 bg-gray-50 dark:bg-zinc-900 border-t border-gray-200 dark:border-zinc-800 flex justify-between items-center text-xs text-gray-500 font-mono">
                        <span>#{{ $request->request_number }}</span>
                        <span>{{ $groupedItems->count() }} items</span>
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
                    audio.play().catch(error => {
                        console.log("Audio autoplay blocked by browser until user interacts with the page.");
                    });
                }
            });
        });
    </script>
</div>
