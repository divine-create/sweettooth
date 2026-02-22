@props([
    'dateFrom' => null,
    'dateTo' => null,
    'showDateRange' => true,
    'filters' => []
])

<div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 mb-6">
    <form class="space-y-4">
        <!-- Date Range -->
        @if($showDateRange)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        From
                    </label>
                    <input type="date" 
                        wire:model.live="dateFrom"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        To
                    </label>
                    <input type="date" 
                        wire:model.live="dateTo"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        @endif

        <!-- Custom Filters -->
        @if(!empty($filters))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($filters as $filter)
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            {{ $filter['label'] }}
                        </label>
                        @if($filter['type'] === 'select')
                            <select wire:model.live="{{ $filter['name'] }}"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                                <option value="">{{ $filter['placeholder'] ?? 'Select...' }}</option>
                                @foreach($filter['options'] ?? [] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @elseif($filter['type'] === 'search')
                            <input type="text" 
                                wire:model.live.debounce.300ms="{{ $filter['name'] }}"
                                placeholder="{{ $filter['placeholder'] ?? 'Search...' }}"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
            {{ $slot }}
        </div>
    </form>
</div>
