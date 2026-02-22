<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Bank Reconciliation</h1>
            <p class="mt-2 text-gray-600">Match General Ledger entries with bank transactions</p>
        </div>
    </div>

    <!-- Bank Selection -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Select Bank Account</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse ($bankAccounts as $account)
                <button
                    wire:click="selectBank({{ $account->id }})"
                    @class([
                        'p-4 rounded-lg border-2 transition text-left',
                        'border-blue-600 bg-blue-50' => $selectedBankId === $account->id,
                        'border-gray-200 hover:border-gray-300' => $selectedBankId !== $account->id,
                    ])
                >
                    <div class="font-semibold text-gray-900">{{ $account->account_name }}</div>
                    <div class="text-sm text-gray-600">{{ $account->account_number }}</div>
                    <div class="text-sm text-gray-500 mt-1">Balance: {{ number_format($account->current_balance ?? 0, 2) }}</div>
                </button>
            @empty
                <div class="col-span-3 text-center py-8">
                    <p class="text-gray-500">No active bank accounts found.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if ($selectedBankId)
        <!-- Statement Date -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Bank Statement Details</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Statement Date</label>
                    <input 
                        type="date"
                        wire:model.live="statementDate"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Statement Balance</label>
                    <input 
                        type="number"
                        wire:model.live="statementBalance"
                        step="0.01"
                        placeholder="0.00"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>
            </div>

            <button
                wire:click="autoMatch"
                class="mt-4 px-4 py-2 bg-green-600 text-white rounded-md font-medium hover:bg-green-700 transition"
            >
                Auto-Match by Amount & Date
            </button>
        </div>

        <!-- Reconciliation Summary -->
        @if (count($summary) > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Reconciliation Status</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="border rounded-lg p-4">
                        <div class="text-sm text-gray-600">GL Total</div>
                        <div class="text-2xl font-bold text-gray-900">{{ number_format($summary['gl_total'], 2) }}</div>
                        <div class="text-xs text-gray-500 mt-1">{{ $summary['gl_count'] }} items</div>
                    </div>

                    <div class="border rounded-lg p-4">
                        <div class="text-sm text-gray-600">Bank Total</div>
                        <div class="text-2xl font-bold text-gray-900">{{ number_format($summary['bank_total'], 2) }}</div>
                        <div class="text-xs text-gray-500 mt-1">{{ $summary['bank_count'] }} items</div>
                    </div>

                    <div class="border rounded-lg p-4">
                        <div class="text-sm text-gray-600">Difference</div>
                        <div @class([
                            'text-2xl font-bold mt-1',
                            'text-green-600' => $summary['is_balanced'],
                            'text-red-600' => !$summary['is_balanced'],
                        ])>
                            {{ number_format($summary['difference'], 2) }}
                        </div>
                    </div>

                    <div class="border rounded-lg p-4">
                        <div class="text-sm text-gray-600">Matched</div>
                        <div class="text-2xl font-bold text-blue-600">{{ $summary['matched_count'] }}</div>
                        <div class="text-xs text-gray-500 mt-1">items</div>
                    </div>

                    <div class="border rounded-lg p-4">
                        <div class="text-sm text-gray-600">Status</div>
                        @if ($summary['is_balanced'])
                            <div class="text-sm font-bold text-green-600 mt-2">✓ Balanced</div>
                        @else
                            <div class="text-sm font-bold text-red-600 mt-2">✗ Not Balanced</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Unmatched Items -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- General Ledger Entries -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-900">General Ledger Entries (Not Reconciled)</h3>
                </div>

                @if (count($unmatchedGlItems) > 0)
                    <div class="overflow-x-auto max-h-96 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600">Reference</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-600">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($unmatchedGlItems as $item)
                                    <tr class="hover:bg-gray-50 cursor-pointer" 
                                        @if($selectedBankId)
                                            x-data="{ glId: {{ $item['id'] }} }"
                                        @endif>
                                        <td class="px-4 py-2">{{ $item['date'] }}</td>
                                        <td class="px-4 py-2 font-medium">{{ $item['reference'] }}</td>
                                        <td class="px-4 py-2 text-right font-semibold">{{ number_format($item['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-8 text-center">
                        <p class="text-gray-500">No unmatched General Ledger entries for this period.</p>
                    </div>
                @endif
            </div>

            <!-- Bank Transactions -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-900">Bank Transactions (Not Reconciled)</h3>
                </div>

                @if (count($unmatchedBankItems) > 0)
                    <div class="overflow-x-auto max-h-96 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600">Reference</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-600">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($unmatchedBankItems as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2">{{ $item['date'] }}</td>
                                        <td class="px-4 py-2 font-medium">{{ $item['reference'] }}</td>
                                        <td class="px-4 py-2 text-right font-semibold">{{ number_format($item['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-8 text-center">
                        <p class="text-gray-500">No unmatched bank transactions for this period.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-900 mb-2">How to Reconcile</h3>
            <ol class="list-decimal list-inside space-y-2 text-sm text-blue-800">
                <li>Enter the statement date and closing balance from your bank statement</li>
                <li>Review General Ledger entries and bank transactions in the lists above</li>
                <li>Click "Auto-Match" to automatically match items by amount and date</li>
                <li>Manually match any remaining items by selecting them from both lists</li>
                <li>When the difference becomes zero, reconciliation is complete</li>
            </ol>
        </div>
    @else
        <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-12 text-center">
            <p class="text-gray-600 text-lg">Select a bank account to begin reconciliation</p>
        </div>
    @endif
</div>
