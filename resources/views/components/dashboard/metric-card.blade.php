@props([
    'title',
    'value',
    'icon' => null,
    'trend' => null,
    'trendIcon' => 'trending-up',
    'color' => 'blue',
    'format' => 'text',
    'loading' => false,
    'action' => null,
    'actionLabel' => 'View Details'
])

@php
    $currencyService = new \App\Services\CurrencyFormattingService();

    $colorClasses = match($color) {
        'blue' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
        'green' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
        'red' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
        'yellow' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
        'purple' => 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800',
        'pink' => 'bg-pink-50 dark:bg-pink-900/20 border-pink-200 dark:border-pink-800',
        default => 'bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700',
    };

    $iconColorClasses = match($color) {
        'blue' => 'text-blue-600 dark:text-blue-400',
        'green' => 'text-green-600 dark:text-green-400',
        'red' => 'text-red-600 dark:text-red-400',
        'yellow' => 'text-yellow-600 dark:text-yellow-400',
        'purple' => 'text-purple-600 dark:text-purple-400',
        'pink' => 'text-pink-600 dark:text-pink-400',
        default => 'text-zinc-600 dark:text-zinc-400',
    };

    $trendColorClasses = match($trend) {
        'up' => 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20',
        'down' => 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20',
        'neutral' => 'text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800',
        default => '',
    };
@endphp

<div class="bg-white dark:bg-zinc-800 rounded-lg border {{ $colorClasses }} p-6 hover:shadow-lg transition-shadow duration-200">
    @if($loading)
        <!-- Loading Skeleton -->
        <div class="animate-pulse space-y-3">
            <div class="h-4 bg-zinc-300 dark:bg-zinc-600 rounded w-1/3"></div>
            <div class="h-8 bg-zinc-300 dark:bg-zinc-600 rounded w-1/2"></div>
            <div class="h-3 bg-zinc-300 dark:bg-zinc-600 rounded w-1/4"></div>
        </div>
    @else
        <!-- Header with Icon and Title -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                    {{ $title }}
                </p>
            </div>
            @if($icon)
                <div class="flex-shrink-0 ml-2">
                    {!! $icon !!}
                </div>
            @endif
        </div>

        <!-- Value -->
        <div class="mb-4">
            @if($format === 'currency')
                <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ $currencyService->format($value) }}
                </p>
            @elseif($format === 'percentage')
                <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($value, 1) }}%
                </p>
            @elseif($format === 'number')
                <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($value) }}
                </p>
            @else
                <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ $value }}
                </p>
            @endif
        </div>

        <!-- Trend -->
        @if($trend)
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $trendColorClasses }}">
                    @if($trendIcon === 'trending-up')
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8L5.257 19.28M5 21H3a2 2 0 01-2-2V5a2 2 0 012-2h2" />
                        </svg>
                    @elseif($trendIcon === 'trending-down')
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8L5.257 4.72M5 3H3a2 2 0 00-2 2v14a2 2 0 002 2h2" />
                        </svg>
                    @endif
                    {{ $trend }}
                </span>
                {{ $slot }}
            </div>
        @endif

        <!-- Action Link -->
        @if($action)
            <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <a href="{{ $action }}" class="text-sm font-medium {{ $iconColorClasses }} hover:underline">
                    {{ $actionLabel }} →
                </a>
            </div>
        @endif
    @endif
</div>
