<div class="flex items-center gap-3" wire:poll.60s="calculateTimeWorked" wire:poll:keep-alive>
    @if($hasActiveShift && $currentShift)
        <!-- Active Shift Display -->
        <div class="flex items-center gap-3">
            <!-- Clock Out Button -->
            <button wire:click="clockOut"
                    wire:confirm="Are you sure you want to clock out?"
                    class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span wire:loading.remove wire:target="clockOut">Clock Out</span>
                <span wire:loading wire:target="clockOut">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </button>

            <!-- Active Time Display -->
             <div class="flex items-center gap-2 px-3 py-1.5 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg" wire:loading.class="opacity-50" wire:target="calculateTimeWorked">
                 <div class="flex items-center gap-1.5">
                     <span class="relative flex h-2 w-2">
                         <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                         <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                     </span>
                     <span class="text-sm font-medium text-green-700 dark:text-green-400">Active</span>
                 </div>
                 <span class="text-xs text-green-600 dark:text-green-500">{{ $timeWorked }}</span>
             </div>

            <!-- Shift Details (Desktop only) -->
            <div class="hidden lg:flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                <span class="capitalize">{{ str_replace('_', ' ', $currentShift->shift_type) }}</span>
                <span>•</span>
                <span>Since {{ \Carbon\Carbon::parse($currentShift->clock_in)->format('h:i A') }}</span>
            </div>
        </div>

    @else
        <!-- Clock In Button -->
        <button wire:click="redirectToShiftPage"
                class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Clock In</span>
        </button>
    @endif
</div>
