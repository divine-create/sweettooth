<div wire:click="selectTable"
     class="cursor-pointer rounded-lg border-2 p-4 transition-all duration-200 {{ $this->getStatusColorClass() }}">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-lg font-bold text-gray-900">{{ $table->table_number }}</span>
                <span class="h-2 w-2 rounded-full {{ $this->getStatusBadgeClass() }}"></span>
            </div>
            <div class="mt-1 text-xs text-gray-600">{{ $table->table_name }}</div>
        </div>
        <div class="text-right">
            <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
        </div>
    </div>

    <div class="mt-3 flex items-center justify-between border-t pt-2">
        <div class="flex items-center gap-1 text-xs text-gray-500">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span>{{ $table->capacity }}</span>
        </div>
        <span class="rounded-full px-2 py-0.5 text-xs font-medium
            @if($table->status === 'available') bg-green-500 text-white
            @elseif($table->status === 'occupied') bg-red-500 text-white
            @else bg-yellow-500 text-white
            @endif">
            {{ ucfirst($table->status) }}
        </span>
    </div>

    @if($table->hasActiveSale())
        <div class="mt-2 rounded bg-white bg-opacity-50 p-2 text-xs">
            <div class="font-medium text-gray-700">Active Order</div>
            <div class="text-gray-600">{{ $this->formatCurrency($table->getTotalAmount()) }}</div>
        </div>
    @endif

    @if(!$table->is_active)
        <div class="mt-2 rounded bg-gray-200 px-2 py-1 text-center text-xs font-medium text-gray-600">
            Inactive
        </div>
    @endif
</div>
