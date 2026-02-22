@if($this->progress)
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-4">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Shift Progress</h3>
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
            {{ $this->progress['percentage_complete'] }}% Complete
        </span>
    </div>

    <!-- Progress Bar -->
    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mb-4">
        <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-500"
             style="width: {{ $this->progress['percentage_complete'] }}%"></div>
    </div>

    <!-- Steps -->
    <div class="grid grid-cols-5 gap-2">
        @foreach($this->progress['steps'] as $step)
            <div class="flex flex-col items-center text-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center mb-1 transition-colors
                    {{ $step['completed'] ? 'bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400' :
                       ($step['current'] ? 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 ring-2 ring-blue-500' :
                       'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500') }}">
                    @if($step['completed'])
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    @else
                        <span class="text-xs font-medium">{{ $loop->iteration }}</span>
                    @endif
                </div>
                <span class="text-[10px] font-medium leading-tight {{ $step['current'] ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400' }}">
                    {{ $step['label'] }}
                </span>
            </div>
        @endforeach
    </div>

    <!-- Next Action Guidance -->
    @if($this->progress['current_state'] !== 'completed' && $this->progress['next_action'])
        <div class="mt-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-200 dark:border-blue-800">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-blue-700 dark:text-blue-300">
                    {{ $this->progress['next_action'] }}
                </p>
            </div>
        </div>
    @endif

    @if($this->progress['current_state'] === 'completed')
        <div class="mt-3 p-2 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-green-700 dark:text-green-300">
                    Shift completed successfully!
                </p>
            </div>
        </div>
    @endif
</div>
@endif
