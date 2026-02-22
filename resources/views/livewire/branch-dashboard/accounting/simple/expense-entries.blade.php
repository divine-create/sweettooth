<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-gradient-to-br from-white via-white to-zinc-50 p-6 shadow-sm dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900/60">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Expense Entries</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Capture and review expense ledger entries.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <!-- Help Button -->
                <button 
                    class="rounded-full border border-blue-600 bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-blue-700" 
                    type="button" 
                    wire:click="$set('showHelpModal', true)">
                    Help
                </button>
                
                <input type="date" wire:model.live="dateFrom" class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900" />
                <input type="date" wire:model.live="dateTo" class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900" />
                <select wire:model.live="bankAccountId" class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <option value="">All Banks</option>
                    @foreach($bankAccounts as $acct)
                        <option value="{{ $acct->id }}">{{ $acct->bank_name }} · {{ $acct->account_number }}</option>
                    @endforeach
                </select>
                <select wire:model.live="glAccountId" class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <option value="">All Categories</option>
                    @foreach($glAccounts as $acct)
                        <option value="{{ $acct->id }}">{{ $acct->account_number }} · {{ $acct->account_name }}</option>
                    @endforeach
                </select>
                <button class="rounded-full border border-emerald-600 bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-emerald-700" type="button" wire:click="toggleForm">
                    {{ $showForm ? 'Close Form' : 'New Expense' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Help Modal -->
    <div x-data="{ show: @entangle('showHelpModal') }" x-show="show" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true"
         x-cloak>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="show" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" 
                 aria-hidden="true"
                 @click="show = false"></div>
            
            <div x-show="show" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl dark:bg-zinc-800">
                
                <div class="flex items-start justify-between border-b border-zinc-200 pb-4 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold leading-6 text-zinc-900 dark:text-white" id="modal-title">
                        Expense Entries Help
                    </h3>
                    <button 
                        type="button" 
                        class="ml-auto rounded-md text-zinc-400 hover:text-zinc-500 focus:outline-none" 
                        @click="show = false">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="mt-4 max-h-[60vh] overflow-y-auto pr-2">
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">Overview</h4>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                The Expense Entries page allows you to record and manage business expenses. Each expense entry creates corresponding general ledger entries to maintain accurate financial records.
                            </p>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">Filtering Options</h4>
                            <ul class="mt-2 space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                                <li class="flex">
                                    <span class="mr-2 font-semibold w-40">Date Range:</span>
                                    <span>Select the date range to filter expense entries by entry date</span>
                                </li>
                                <li class="flex">
                                    <span class="mr-2 font-semibold w-40">Bank Filter:</span>
                                    <span>Filter expense entries by specific bank account</span>
                                </li>
                                <li class="flex">
                                    <span class="mr-2 font-semibold w-40">Category Filter:</span>
                                    <span>Filter expense entries by expense category (GL account)</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">Creating New Expenses</h4>
                            <ul class="mt-2 space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                                <li class="flex">
                                    <span class="mr-2 font-semibold w-40">Date:</span>
                                    <span>The date when the expense occurred</span>
                                </li>
                                <li class="flex">
                                    <span class="mr-2 font-semibold w-40">Amount:</span>
                                    <span>The monetary value of the expense (required)</span>
                                </li>
                                <li class="flex">
                                    <span class="mr-2 font-semibold w-40">Particulars:</span>
                                    <span>A brief description of the expense (required)</span>
                                </li>
                                <li class="flex">
                                    <span class="mr-2 font-semibold w-40">Category:</span>
                                    <span>Select the appropriate expense category (GL account) (required)</span>
                                </li>
                                <li class="flex">
                                    <span class="mr-2 font-semibold w-40">Source:</span>
                                    <span>Optional field to specify the source of the expense</span>
                                </li>
                                <li class="flex">
                                    <span class="mr-2 font-semibold w-40">Bank Account:</span>
                                    <span>Select the bank account if the expense was paid from a specific account</span>
                                </li>
                                <li class="flex">
                                    <span class="mr-2 font-semibold w-40">Reference:</span>
                                    <span>Optional reference number for the transaction</span>
                                </li>
                                <li class="flex">
                                    <span class="mr-2 font-semibold w-40">Notes:</span>
                                    <span>Additional details about the expense</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">Viewing Expenses</h4>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                The table displays all recorded expenses with columns for Date, Particulars, Source, Amount, Category, and Bank. You can search through entries using the search bar at the top of the table.
                            </p>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">Accounting Impact</h4>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                Each expense entry automatically creates corresponding General Ledger entries. The expense amount is debited to the selected expense account and credited to the bank account (or cash account if no bank is specified), maintaining double-entry bookkeeping principles.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button 
                        type="button" 
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none" 
                        @click="show = false">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($showForm)
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="text-xs text-zinc-500">Date *</label>
                    <input type="date" wire:model="entryDate" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                    @error('entryDate') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Amount *</label>
                    <input type="number" step="0.01" wire:model="amount" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                    @error('amount') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Particulars *</label>
                    <input type="text" wire:model="description" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                    @error('description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Category *</label>
                    <select wire:model="glAccountId" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <option value="">Select Category</option>
                        @foreach($glAccounts as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->account_number }} · {{ $acct->account_name }}</option>
                        @endforeach
                    </select>
                    @error('glAccountId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Source</label>
                    <input type="text" wire:model="source" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Bank Account</label>
                    <select wire:model="bankAccountId" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <option value="">Select Bank</option>
                        @foreach($bankAccounts as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->bank_name }} · {{ $acct->account_number }}</option>
                        @endforeach
                    </select>
                    @error('bankAccountId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-xs text-zinc-500">Reference</label>
                    <input type="text" wire:model="reference" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs text-zinc-500">Notes</label>
                    <textarea wire:model="notes" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" rows="2"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="button" wire:click="save" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-emerald-700">Save Expense</button>
                </div>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center justify-between p-4">
            <div class="flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <span class="text-zinc-400">Search</span>
                <input class="w-56 border-none bg-transparent p-0 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none dark:text-white" type="text" placeholder="Particulars, source" wire:model.debounce.300ms="search" />
            </div>
        </div>
        <x-table :$headers :$rows striped paginate persist :filter="['quantity' => 'quantity', 'search' => 'search']" :quantity="[10,25,50,100]">
            @interact('column_entry_date', $row)
                <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ optional($row->entry_date)->format('Y-m-d') }}</div>
            @endinteract

            @interact('column_particulars', $row)
                <div class="text-zinc-900 dark:text-white">{{ $row->description }}</div>
            @endinteract

            @interact('column_source', $row)
                <div class="text-zinc-600 dark:text-zinc-400 text-xs">{{ $row->source ?? '-' }}</div>
            @endinteract

            @interact('column_amount', $row)
                <span class="font-semibold text-zinc-900 dark:text-white">{{ number_format($row->amount, 2) }}</span>
            @endinteract

            @interact('column_category', $row)
                <div class="text-zinc-900 dark:text-white">{{ $row->glAccount?->account_name ?? '-' }}</div>
            @endinteract

            @interact('column_bank', $row)
                <div class="text-xs text-zinc-500">{{ $row->bankAccount?->bank_name ?? '-' }}</div>
            @endinteract
        </x-table>
    </div>
</div>
