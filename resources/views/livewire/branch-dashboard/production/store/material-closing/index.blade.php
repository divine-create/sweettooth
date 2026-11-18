<div>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-slate-800 dark:text-slate-100 font-bold">Material Shift Closing 🏭</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Date: {{ $stockDate }} | Department: <span class="uppercase font-semibold">{{ $deptSlug }}</span>
                </p>
            </div>
        </div>

        @if (session()->has('status'))
            <div class="mb-4 px-4 py-3 rounded text-sm bg-emerald-100 border border-emerald-200 text-emerald-600">
                {{ session('status') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="mb-4 px-4 py-3 rounded text-sm bg-rose-100 border border-rose-200 text-rose-600">
                {{ session('error') }}
            </div>
        @endif

        @if(empty($materialStocks))
            <div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700 p-5 text-center">
                <p class="text-slate-500 dark:text-slate-400">No materials trackable for this store today.</p>
            </div>
        @else
            <div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
                <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                    <h2 class="font-semibold text-slate-800 dark:text-slate-100">Physical Stock Count</h2>
                </header>
                <div class="p-3">
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full dark:text-slate-300">
                            <thead class="text-xs uppercase text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-700 dark:bg-opacity-50 rounded-sm">
                                <tr>
                                    <th class="p-2 whitespace-nowrap"><div class="font-semibold text-left">Item Name</div></th>
                                    <th class="p-2 whitespace-nowrap"><div class="font-semibold text-right">Opening</div></th>
                                    <th class="p-2 whitespace-nowrap"><div class="font-semibold text-right">Received (+)</div ></th>
                                    <th class="p-2 whitespace-nowrap"><div class="font-semibold text-right">Used (-)</div></th>
                                    <th class="p-2 whitespace-nowrap"><div class="font-semibold text-right">Expected</div></th>
                                    <th class="p-2 whitespace-nowrap"><div class="font-semibold text-center">Physical Count (Closing)</div></th>
                                </tr>
                            </thead>
                            <tbody class="text-sm font-medium divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($materialStocks as $itemId => $stock)
                                    <tr>
                                        <td class="p-2 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="text-slate-800 dark:text-slate-100">{{ $stock['item_name'] }}</div>
                                            </div>
                                        </td>
                                        <td class="p-2 whitespace-nowrap text-right text-slate-500">
                                            {{ number_format($stock['opening_quantity'], 2) }} {{ $stock['uom'] }}
                                        </td>
                                        <td class="p-2 whitespace-nowrap text-right text-emerald-500">
                                            +{{ number_format($stock['addition_quantity'], 2) }}
                                        </td>
                                        <td class="p-2 whitespace-nowrap text-right text-rose-500">
                                            -{{ number_format($stock['quantity_used'], 2) }}
                                        </td>
                                        <td class="p-2 whitespace-nowrap text-right text-slate-800 dark:text-slate-100 font-semibold bg-slate-50 dark:bg-slate-700">
                                            {{ number_format($stock['expected_closing'], 2) }} {{ $stock['uom'] }}
                                        </td>
                                        <td class="p-2 whitespace-nowrap">
                                            <div class="flex justify-center items-center gap-2">
                                                <input type="number" step="0.01" min="0"
                                                    wire:model="materialStocks.{{ $itemId }}.closing_quantity"
                                                    class="form-input w-24 text-right @error('materialStocks.'.$itemId.'.closing_quantity') border-rose-300 @enderror" 
                                                />
                                                <span class="text-slate-400 text-xs">{{ $stock['uom'] }}</span>
                                            </div>
                                            @error('materialStocks.'.$itemId.'.closing_quantity')
                                                <div class="text-xs text-rose-500 mt-1 text-center">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-700 flex justify-end">
                    <button wire:click="saveClosing" 
                            wire:loading.attr="disabled"
                            class="btn bg-indigo-500 hover:bg-indigo-600 text-white">
                        <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16" wire:loading.class="animate-spin hidden" wire:target="saveClosing">
                            <path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                        </svg>
                        <span class="ml-2" wire:loading.remove wire:target="saveClosing">Submit Shift Closing</span>
                        <span class="ml-2" wire:loading wire:target="saveClosing">Saving...</span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
