<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Bank Reconciliation</h1>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Match General Ledger entries with bank transactions</p>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
        <div class="flex space-x-1 bg-zinc-100 dark:bg-zinc-800 p-1 rounded-lg">
            <button class="flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 @if($activeTab === 'select') bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-sm @else text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 @endif"
                wire:click="$set('activeTab', 'select')">
                <span class="flex items-center gap-2">
                    <span class="bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 text-xs px-2 py-0.5 rounded-full">1</span>
                    Select Bank Account
                </span>
            </button>
            <button class="flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 @if($activeTab === 'matching') bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-sm @else text-zinc-600 dark:text-zinc-400 @if(!$selectedReconciliationId) opacity-50 cursor-not-allowed @endif @endif"
                wire:click="$set('activeTab', 'matching')" @if(!$selectedReconciliationId) disabled @endif>
                <span class="flex items-center gap-2">
                    <span class="bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 text-xs px-2 py-0.5 rounded-full">2</span>
                    Match Transactions
                </span>
            </button>
            <button class="flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 @if($activeTab === 'results') bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-sm @else text-zinc-600 dark:text-zinc-400 @if(!$selectedReconciliationId) opacity-50 cursor-not-allowed @endif @endif"
                wire:click="$set('activeTab', 'results')" @if(!$selectedReconciliationId) disabled @endif>
                <span class="flex items-center gap-2">
                    <span class="bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 text-xs px-2 py-0.5 rounded-full">3</span>
                    Results
                </span>
            </button>
        </div>
    </div>

    <!-- STEP 1: SELECT BANK ACCOUNT -->
    @if($activeTab === 'select')
    <div class="flex justify-center">
        <div class="w-full max-w-2xl">
            <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Start New Reconciliation
                    </h3>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Errors -->
                    @if ($errors->any())
                        <div class="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-sm text-red-800 dark:text-red-200">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Bank Account Selection -->
                    <div>
                        <label for="bankAccount" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Select Bank Account
                        </label>
                        <select id="bankAccount" wire:model="selectedBankAccountId" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                            <option value="">-- Select Bank Account --</option>
                            @foreach($bankAccounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->bank_name }} - {{ $account->account_number }}
                                </option>
                            @endforeach
                        </select>
                        @error('selectedBankAccountId')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reconciliation Date -->
                    <div>
                        <label for="reconciliationDate" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Reconciliation Date
                        </label>
                        <input type="date" id="reconciliationDate" wire:model="reconciliationDate" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                        @error('reconciliationDate')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Bank Balance -->
                    <div>
                        <label for="bankBalance" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Bank Balance (from statement)
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-zinc-500 dark:text-zinc-400 text-sm">{{ $this->getCurrencySymbol() }}</span>
                            </div>
                            <input type="number" id="bankBalance" wire:model="bankBalance" placeholder="0.00" step="0.01" class="w-full pl-8 pr-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                        </div>
                        @error('bankBalance')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end">
                        <button wire:click="startReconciliation" wire:loading.attr="disabled" wire:target="startReconciliation" class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-md font-medium transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="startReconciliation">Start Reconciliation</span>
                            <span wire:loading wire:target="startReconciliation">
                                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- STEP 2: MATCH TRANSACTIONS -->
    @if($activeTab === 'matching' && $selectedReconciliationId)
    <div class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 text-amber-900 dark:text-amber-100 text-sm">
        <div class="font-semibold mb-1">How this works</div>
        <div>Bank Balance is your statement balance. Book Balance is the GL balance for the linked bank account as of the reconciliation date.</div>
        <div>Match transactions to clear items on both sides. Reconciliation completes only when the Bank and Book balances match and all items are matched.</div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/20 dark:to-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-blue-700 dark:text-blue-300 font-medium">Bank Balance</p>
                    <p class="text-xl font-bold text-blue-900 dark:text-blue-100">{{ $this->formatCurrency($stats['bank_balance'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/20 dark:to-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-green-700 dark:text-green-300 font-medium">Book Balance</p>
                    <p class="text-xl font-bold text-green-900 dark:text-green-100">{{ $this->formatCurrency($stats['book_balance'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950/20 dark:to-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-amber-700 dark:text-amber-300 font-medium">Matched</p>
                    <p class="text-xl font-bold text-amber-900 dark:text-amber-100">{{ $stats['matched_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br @if($isBalanced) from-green-50 to-green-100 dark:from-green-950/20 dark:to-green-900/20 border-green-200 dark:border-green-800 @else from-red-50 to-red-100 dark:from-red-950/20 dark:to-red-900/20 border-red-200 dark:border-red-800 @endif border rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 @if($isBalanced) bg-green-600 @else bg-red-600 @endif rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($isBalanced)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        @endif
                    </svg>
                </div>
                <div>
                    <p class="text-sm @if($isBalanced) text-green-700 dark:text-green-300 @else text-red-700 dark:text-red-300 @endif font-medium">Difference</p>
                    <p class="text-xl font-bold @if($isBalanced) text-green-900 dark:text-green-100 @else text-red-900 dark:text-red-100 @endif">{{ $this->formatCurrency(abs($stats['difference'] ?? 0)) }}</p>
                    <p class="text-xs @if($isBalanced) text-green-600 dark:text-green-400 @else text-red-600 dark:text-red-400 @endif font-medium">
                        @if($isBalanced) ✓ Balanced @else ✗ Unbalanced @endif
                    </p>
                    <p class="text-xs mt-1 text-zinc-600 dark:text-zinc-400">
                        Unmatched: Bank {{ $stats['unmatched_bank_count'] ?? 0 }} ({{ $this->formatCurrency(abs($stats['unmatched_bank_total'] ?? 0)) }}),
                        GL {{ $stats['unmatched_gl_count'] ?? 0 }} ({{ $this->formatCurrency(abs($stats['unmatched_gl_total'] ?? 0)) }})
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if (count($glEntries) === 0 && count($bankTransactions) === 0)
        <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-blue-900 dark:text-blue-100 text-sm">
            No GL entries or bank transactions were found for the selected date. Add posted GL entries or import bank transactions, then refresh this reconciliation.
        </div>
    @endif

    <!-- Auto Match Section -->
    <div class="mb-6">
        <button wire:click="performAutoMatch" wire:loading.attr="disabled" wire:target="performAutoMatch" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-700 hover:to-purple-600 text-white rounded-lg font-medium transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm hover:shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span wire:loading.remove wire:target="performAutoMatch">Auto-Match Transactions</span>
            <span wire:loading wire:target="performAutoMatch">
                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Matching...</span>
            </span>
        </button>
        @if($autoMatchCount > 0)
            <span class="inline-flex items-center gap-1 ml-3 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm font-medium rounded-full">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </path>
                </svg>
                {{ $autoMatchCount }} matched
            </span>
        @endif
    </div>

    <!-- Three Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- General Ledger Entries Column -->
        <div class="lg:col-span-5">
            <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        General Ledger Entries ({{ count($glEntries) }})
                    </h4>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    @if(count($glEntries) > 0)
                        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($glEntries as $entry)
                                <div class="p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100 text-sm mb-1">{{ $entry['account_name'] }}</p>
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">{{ $entry['date'] }} • {{ $entry['reference'] }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-500 line-clamp-2">{{ $entry['description'] }}</p>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <p class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm">{{ $this->formatCurrency($entry['amount']) }}</p>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium @if($entry['type'] === 'debit') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 @else bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 @endif">
                                                {{ ucfirst($entry['type']) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center">
                            <svg class="w-12 h-12 text-zinc-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">All General Ledger entries have been reconciled!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bank Transactions Column -->
        <div class="lg:col-span-5">
            <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        Bank Transactions ({{ count($bankTransactions) }})
                    </h4>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    @if(count($bankTransactions) > 0)
                        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($bankTransactions as $transaction)
                                <div class="p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100 text-sm mb-1">{{ $transaction['description'] }}</p>
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">{{ $transaction['date'] }} • {{ $transaction['reference'] }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-500 capitalize">{{ $transaction['type'] }}</p>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <p class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm">{{ $this->formatCurrency($transaction['amount']) }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center">
                            <svg class="w-12 h-12 text-zinc-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">All bank transactions have been reconciled!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Matched Pairs Column -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Matched ({{ count($matchedPairs) }})
                    </h4>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    @if(count($matchedPairs) > 0)
                        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($matchedPairs as $pair)
                                <div class="p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100 text-xs">{{ $this->formatCurrency($pair['amount']) }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $pair['matched_at'] }}</p>
                                        </div>
                                        <button wire:click="unmatchTransactions({{ $pair['id'] }})"
                                            class="flex-shrink-0 p-1 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-950/20 rounded transition-colors"
                                            title="Remove match">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center">
                            <svg class="w-8 h-8 text-zinc-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">No matches yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-between pt-6 border-t border-zinc-200 dark:border-zinc-700">
        <button wire:click="resetReconciliation" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back
        </button>

        @if($isBalanced)
            <button wire:click="completeReconciliation" wire:loading.attr="disabled" wire:target="completeReconciliation" class="inline-flex items-center gap-2 px-6 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg font-medium transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="completeReconciliation">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Complete Reconciliation
                </span>
                <span wire:loading wire:target="completeReconciliation">
                    <svg class="w-4 h-4 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Completing...
                </span>
            </button>
        @else
            <div class="flex items-center gap-2 px-4 py-2 bg-red-100 dark:bg-red-950/20 text-red-800 dark:text-red-200 rounded-lg text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cannot complete - Not balanced (Diff: {{ $this->formatCurrency(abs($stats['difference'] ?? 0)) }})
            </div>
        @endif
    </div>
    @endif

    <!-- STEP 3: RESULTS -->
    @if($activeTab === 'results' && $selectedReconciliationId && $reconciliation)
    <div class="flex justify-center">
        <div class="w-full max-w-4xl">
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-950/20 dark:to-emerald-950/20 border border-green-200 dark:border-green-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-green-200 dark:border-green-800 bg-gradient-to-r from-green-600 to-emerald-600">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Reconciliation Complete
                    </h3>
                </div>
                <div class="p-6">
                    <!-- Date Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white dark:bg-zinc-900 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                            <h4 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Reconciliation Date</h4>
                            <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $reconciliation->reconciliation_date->format('M d, Y') }}</p>
                        </div>
                        <div class="bg-white dark:bg-zinc-900 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                            <h4 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Completed At</h4>
                            <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $reconciliation->completed_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>

                    <!-- Balance Information -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-white dark:bg-zinc-900 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                            <h4 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Bank Balance</h4>
                            <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->formatCurrency($reconciliation->bank_balance) }}</p>
                        </div>
                        <div class="bg-white dark:bg-zinc-900 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                            <h4 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Book Balance</h4>
                            <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->formatCurrency($reconciliation->book_balance) }}</p>
                        </div>
                        <div class="bg-white dark:bg-zinc-900 rounded-lg p-4 border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/20">
                            <h4 class="text-sm font-medium text-green-700 dark:text-green-300 mb-1">Difference</h4>
                            <p class="text-xl font-bold text-green-800 dark:text-green-200">{{ $this->formatCurrency($reconciliation->difference) }}</p>
                        </div>
                    </div>

                    <!-- Match Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-white dark:bg-zinc-900 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                            <h4 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Matched Pairs</h4>
                            <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $stats['matched_count'] ?? 0 }}</p>
                        </div>
                        <div class="bg-white dark:bg-zinc-900 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                            <h4 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Matched Amount</h4>
                            <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->formatCurrency($stats['matched_amount'] ?? 0) }}</p>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="flex justify-center pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <button wire:click="resetReconciliation" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-medium transition-colors duration-200 shadow-sm hover:shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Start New Reconciliation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
