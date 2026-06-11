<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">Import Bank Statement</h1>
                <p class="text-zinc-600 dark:text-zinc-400">Upload your bank statement CSV to bring bank transactions into the system for reconciliation</p>
            </div>
            <button type="button" wire:click="downloadSample" class="self-start px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download Sample CSV
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 text-green-800 dark:text-green-200">
            {{ session('message') }}
            <div class="mt-2">
                <a href="{{ route('branch-dashboard.accounting.bank-reconciliation') }}" class="text-green-700 dark:text-green-300 font-medium underline">
                    Go to Bank Reconciliation →
                </a>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-red-800 dark:text-red-200">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif


    <!-- Import Form -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Bank Account *</label>
                <select wire:model="selectedBankAccountId" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                    <option value="">-- Select Bank Account --</option>
                    @foreach ($bankAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->bank_name }} - {{ $account->account_number }}</option>
                    @endforeach
                </select>
                @error('selectedBankAccountId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">CSV File *</label>
                <input type="file" wire:model="upload" accept=".csv,.txt" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="text-xs text-zinc-500 mt-1">Accepts .csv files up to 5MB</p>
                @error('upload') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 text-sm text-amber-800 dark:text-amber-200">
            <p class="font-semibold mb-1">CSV Format Requirements</p>
            <p>Your CSV must include these columns: <strong>Date</strong>, <strong>Narration</strong> (description), <strong>Debit</strong> (withdrawals), <strong>Credit</strong> (deposits), and optionally <strong>Balance</strong>.</p>
            <p class="mt-1">The system will auto-detect column headers like "Date", "Description", "Withdrawal", "Deposit", etc. Click <strong>Download Sample CSV</strong> above to see the expected format.</p>
        </div>
    </div>

    <!-- Preview Table -->
    @if ($showPreview && count($preview) > 0)
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Preview — First {{ count($preview) }} Rows</h2>

        <div class="overflow-x-auto mb-4">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-900 dark:text-white text-sm">Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-900 dark:text-white text-sm">Narration</th>
                        <th class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-white text-sm">Debit (Out)</th>
                        <th class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-white text-sm">Credit (In)</th>
                        <th class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-white text-sm">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($preview as $row)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $row[$columnMapping['date']] ?? '' }}</td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-white">{{ $row[$columnMapping['narration']] ?? '' }}</td>
                            <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ number_format((float) str_replace(',', '', $row[$columnMapping['debit']] ?? '0'), 2) }}</td>
                            <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ number_format((float) str_replace(',', '', $row[$columnMapping['credit']] ?? '0'), 2) }}</td>
                            <td class="px-4 py-3 text-right text-zinc-700 dark:text-zinc-300">{{ number_format((float) str_replace(',', '', $row[$columnMapping['balance']] ?? '0'), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end">
            <button wire:click="import" wire:loading.attr="disabled" class="px-6 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-medium transition disabled:opacity-50">
                <span wire:loading.remove wire:target="import">Import {{ count($preview) }}+ Transactions</span>
                <span wire:loading wire:target="import">Importing...</span>
            </button>
        </div>
    </div>
    @endif

    <!-- Import Errors -->
    @if (count($importErrors) > 0)
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <h3 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">Import Notes ({{ count($importErrors) }})</h3>
        <ul class="list-disc list-inside text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
            @foreach ($importErrors as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
</div>