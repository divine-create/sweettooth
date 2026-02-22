<div class="flex items-center space-x-4">
    <!-- Shift Status Indicator -->
    @if($hasActiveShift)
        <div class="flex items-center space-x-2 bg-green-100 dark:bg-green-900/30 px-3 py-1.5 rounded-full border border-green-200 dark:border-green-800">
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span class="text-sm font-medium text-green-800 dark:text-green-200">
                {{ $shiftType }} Shift
            </span>
            <span class="text-xs text-green-600 dark:text-green-400">
                {{ $timeWorked }}
            </span>
        </div>

        <!-- Clock Out Button -->
        <button
            wire:click="clockOut"
            wire:confirm="Are you sure you want to clock out?"
            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-700 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-800/50 rounded-md border border-red-200 dark:border-red-800 transition duration-150"
            title="Clock Out"
        >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Clock Out
        </button>
    @else
        @if(!is_super_admin())
        <!-- Clock In Button -->
        <a
            href="{{ route('branch-dashboard.select_shift') }}"
            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-800/50 rounded-md border border-blue-200 dark:border-blue-800 transition duration-150"
            title="Clock In"
        >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Clock In
        </a>
        @endif
    @endif

    <!-- Shift Warnings/Alerts -->
    @if($shiftWarnings)
        <div class="flex items-center space-x-2">
            @if($shiftEndingSoon)
                <div class="inline-flex items-center px-2 py-1 text-xs font-medium text-orange-800 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-300 rounded-full border border-orange-200 dark:border-orange-800">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    Shift ends in {{ $minutesToEnd }}m
                </div>
            @endif

            @if($autoClockOutWarning)
                <div class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-800 bg-red-100 dark:bg-red-900/30 dark:text-red-300 rounded-full border border-red-200 dark:border-red-800 animate-pulse">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    Auto clock out in {{ $minutesToAutoClock }}m
                </div>
            @endif
        </div>
    @endif
</div>
