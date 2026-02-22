<div class="max-w-4xl">
    @if ($title)
        <h1 class="text-xl font-semibold text-zinc-800 dark:text-zinc-100 mb-2">{{ $title }}</h1>
    @endif

    @php
        $finalItem = array_pop($items);
    @endphp

    <nav class="flex items-center text-xs {{ $compact ? 'space-x-0.5' : 'space-x-1' }}">
        @foreach ($items as $item)
            <a href="{{ $item['url'] ?? '#' }}"
               class="flex items-center {{ $compact ? 'px-1.5 py-0.5' : 'px-2 py-0.5' }} text-zinc-600 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 rounded {{ $compact ? 'hover:bg-zinc-100 dark:hover:bg-zinc-700' : 'hover:bg-white dark:hover:bg-zinc-800' }}">
                @if ($withIcons && isset($item['icon']))
                    <x-dynamic-component :component="$item['icon']" class="w-3.5 h-3.5 mr-1"/>
                @endif
                <span>{{ $item['label'] }}</span>
            </a>

            <!-- Separator -->
            <svg class="{{ $compact ? 'w-2.5 h-2.5' : 'w-3 h-3' }} text-zinc-400 dark:text-zinc-500"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        @endforeach

        <!-- Current Page -->
        <span class="flex items-center text-zinc-800 dark:text-zinc-200 font-medium
                     {{ $compact ? 'px-1.5 py-0.5' : 'px-2 py-0.5' }}
                     bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded">
            @if ($withIcons && isset($finalItem['icon']))
                <x-dynamic-component :component="$finalItem['icon']" class="w-3.5 h-3.5 mr-1"/>
            @endif
            <span>{{ $finalItem['label'] ?? '' }}</span>
        </span>
    </nav>
</div>
