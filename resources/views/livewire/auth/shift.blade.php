<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-white via-slate-50 to-gray-100 dark:from-zinc-900 dark:via-zinc-800 dark:to-zinc-900 p-4 transition-colors duration-300">
    <style>
        .dropdown-menu {
            transition: all 0.3s ease-in-out;
        }
        .dropdown-menu.hidden {
            opacity: 0;
            transform: translateY(-10px);
        }
        .dropdown-menu:not(.hidden) {
            opacity: 1;
            transform: translateY(0);
        }
        .shift-option:hover {
            @apply bg-blue-100 dark:bg-zinc-700;
            transform: scale(1.02);
            transition: all 0.2s ease;
        }
        .error-alert {
            animation: shake 0.3s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25%, 75% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
        }
        .card {
            @apply bg-white dark:bg-zinc-900 shadow-lg dark:shadow-2xl border border-gray-200 dark:border-zinc-700;
            transition: all 0.3s ease;
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(34, 197, 94, 0.3), 0 10px 20px rgba(0, 0, 0, 0.1); }
            50% { box-shadow: 0 0 30px rgba(34, 197, 94, 0.5), 0 10px 20px rgba(0, 0, 0, 0.15); }
        }
        .active-shift-card {
            animation: pulse-glow 2s ease-in-out infinite;
        }
    </style>

    <!-- Active Shift View -->
    @if($hasActiveShift && $currentShift)
    <div class="w-full max-w-4xl p-8 md:p-12 card rounded-3xl active-shift-card">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-green-500 dark:bg-green-600 rounded-full mb-6">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold text-green-600 dark:text-green-400">Active Shift</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-3 text-lg">You are currently clocked in</p>
        </div>

        <!-- Shift Details Card -->
        <div class="bg-gray-50 dark:bg-zinc-800 rounded-2xl p-8 mb-8 border border-gray-200 dark:border-zinc-700">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Shift Number</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $currentShift->shift_number }}</p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Shift Type</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white capitalize">{{ str_replace('_', ' ', $currentShift->shift_type) }}</p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Clock In</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $currentShift->clock_in->format('h:i A') }}</p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Time Worked</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->getTotalHoursWorked() }}</p>
                </div>
            </div>

            @if($currentShift->notes)
            <div class="mt-8 pt-8 border-t border-gray-200 dark:border-zinc-700">
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-3 uppercase tracking-wider">Notes</p>
                <p class="text-gray-800 dark:text-gray-200 text-lg leading-relaxed">{{ $currentShift->notes }}</p>
            </div>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <button wire:click="continueToWork"
                    class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 text-white px-8 py-4 rounded-xl shadow-lg hover:shadow-xl transition duration-200 font-semibold flex items-center justify-center gap-3 text-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                Continue to Work
            </button>

            <button wire:click="clockOut"
                    wire:confirm="Are you sure you want to clock out?"
                    class="bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 text-white px-8 py-4 rounded-xl shadow-lg hover:shadow-xl transition duration-200 font-semibold flex items-center justify-center gap-3 text-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span wire:loading.remove wire:target="clockOut">Clock Out</span>
                <span wire:loading wire:target="clockOut">Clocking out...</span>
            </button>
        </div>
    </div>

    @else
    <!-- Clock In View -->
    <div x-data="{
        selectedShift: '',
        isOpen: false
    }" class="w-full max-w-2xl p-8 md:p-12 card rounded-3xl">

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-500 dark:bg-blue-600 rounded-full mb-6">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-gray-100">Clock In</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-3 text-lg">Select your shift to start working</p>
        </div>

        <!-- Current Date and Time -->
        <div class="bg-blue-50 dark:bg-zinc-800 rounded-2xl p-6 mb-8 text-center border border-blue-200 dark:border-zinc-700">
            <p class="text-gray-700 dark:text-gray-300 text-sm font-semibold">{{ now()->format('l, F j, Y') }}</p>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2" x-data x-text="new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })"></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Server-side time validation enabled</p>
        </div>

        <!-- Shift Selection -->
        <div class="relative mb-8">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wider">Select Shift</label>
            <button
                @click="isOpen = !isOpen"
                class="w-full bg-white dark:bg-zinc-800 text-left px-6 py-4 rounded-xl shadow-md dark:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-zinc-700 transition duration-200 border border-gray-300 dark:border-zinc-600"
            >
                <span x-text="selectedShift || 'Choose a shift'" class="text-gray-900 dark:text-gray-200 font-medium text-base"></span>
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transform transition-transform duration-300" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div
                x-show="isOpen"
                @click.away="isOpen = false"
                class="dropdown-menu absolute w-full mt-2 bg-white dark:bg-zinc-800 rounded-xl shadow-xl z-10 border border-gray-300 dark:border-zinc-600"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                x-cloak
            >
                @forelse($availableShifts as $index => $shiftConfig)
                    <div
                        @click="selectedShift = '{{ addslashes($shiftConfig->name) }} ({{ $shiftConfig->start_time }} - {{ $shiftConfig->end_time }})';
                                isOpen = false;
                                $wire.set('shift_config_id', {{ $shiftConfig->id }});"
                        class="shift-option px-6 py-4 cursor-pointer text-gray-900 dark:text-gray-200 {{ $index === 0 ? 'rounded-t-xl' : '' }} {{ $loop->last ? 'rounded-b-xl' : 'border-b border-gray-200 dark:border-zinc-700' }} transition duration-200"
                    >
                        <div class="font-semibold">{{ $shiftConfig->name }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $shiftConfig->start_time }} - {{ $shiftConfig->end_time }}
                            @if($shiftConfig->auto_clock_out_minutes > 0)
                                (Auto-clock-out after {{ $shiftConfig->auto_clock_out_minutes }} mins)
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-4 text-gray-500 dark:text-gray-400 text-center">
                        No shift configurations available for this branch
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Notes (Optional) -->
        <div class="mb-8">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wider">Notes (Optional)</label>
            <textarea wire:model="notes" rows="4"
                      class="w-full px-6 py-4 bg-white dark:bg-zinc-800 border border-gray-300 dark:border-zinc-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 font-normal"
                      placeholder="Add any notes about your shift..."></textarea>
        </div>

        <!-- Selected Shift Display and Actions -->
        <div x-show="selectedShift" class="mb-8" x-cloak>
            <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-400 dark:border-blue-700 rounded-xl p-6 mb-6">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-400 mb-2 uppercase tracking-wider">Selected Shift</p>
                <p class="text-xl font-bold text-blue-600 dark:text-blue-400" x-text="selectedShift"></p>
            </div>

            <button
                wire:click="clockIn"
                class="w-full bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-800 text-white px-8 py-4 rounded-xl shadow-lg hover:shadow-xl transition duration-200 font-semibold flex items-center justify-center gap-3 text-lg"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span wire:loading.remove wire:target="clockIn">Clock In</span>
                <span wire:loading wire:target="clockIn">Validating time...</span>
            </button>
        </div>

        <div class="mt-8 text-center">
            <p class="text-gray-600 dark:text-gray-500 text-sm">Time-based shift validation enabled</p>
        </div>
    </div>
    @endif

</div>
