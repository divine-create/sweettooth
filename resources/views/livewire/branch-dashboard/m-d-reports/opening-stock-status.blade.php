<div>
    <div class="mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">Global Opening Stock Status</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Track which staff members have verified opening stock across all departments.</p>
        </div>
        
        <div class="w-full md:w-64">
            <flux:input type="date" wire:model.live="dateFilter" label="Select Date" />
        </div>
    </div>

    @php
        $sections = [
            ['title' => 'Sales Units', 'data' => $salesUnits],
            ['title' => 'Production Units', 'data' => $productionUnits],
            ['title' => 'Inventory & Store Units', 'data' => $inventoryUnits],
        ];
    @endphp

    <div class="space-y-8">
        @foreach($sections as $section)
            @if(count($section['data']) > 0)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">{{ $section['title'] }}</flux:heading>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
                            <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3 rounded-tl-lg">Department Unit</th>
                                    <th scope="col" class="px-6 py-3">Staff on Duty</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3 rounded-tr-lg">Completed At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($section['data'] as $unit)
                                    <tr class="border-b border-zinc-200 dark:border-zinc-700 last:border-0 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                                            {{ $unit['department_name'] }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ $unit['staff_name'] }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($unit['status'] === 'verified')
                                                <flux:badge color="green" icon="check-circle" size="sm">Verified</flux:badge>
                                            @elseif($unit['status'] === 'in_progress')
                                                <flux:badge color="amber" icon="clock" size="sm">In Progress</flux:badge>
                                            @else
                                                <flux:badge color="red" icon="x-circle" size="sm">Not Started</flux:badge>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ $unit['verified_at'] ?? '--' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </flux:card>
            @endif
        @endforeach

        @if(count($salesUnits) === 0 && count($productionUnits) === 0 && count($inventoryUnits) === 0)
            <div class="text-center py-12 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                <p class="text-zinc-500 dark:text-zinc-400">No departments found matching the criteria.</p>
            </div>
        @endif
    </div>
</div>
