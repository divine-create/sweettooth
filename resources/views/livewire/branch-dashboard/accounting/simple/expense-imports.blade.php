<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-gradient-to-br from-white via-white to-zinc-50 p-6 shadow-sm dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900/60">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Expense Import (Wide)</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Upload your wide expense sheet. We convert it to long format and post to GL.</p>
            </div>
        </div>
    </div>

    {{-- Format Guide & Sample Download --}}
    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5 dark:border-blue-900/40 dark:bg-blue-950/30">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-blue-800 dark:text-blue-300">Expected Spreadsheet Format</p>
                <p class="mt-1 text-xs text-blue-700 dark:text-blue-400">
                    Your sheet must be <strong>wide format</strong>: fixed columns in positions 1–5, then one column per expense category from column 6 onwards.
                    Each row is one transaction; put the amount in the matching category column and leave the rest blank.
                </p>
                <div class="mt-3 overflow-x-auto rounded-lg border border-blue-200 bg-white dark:border-blue-800 dark:bg-zinc-900">
                    <table class="min-w-max text-xs">
                        <thead class="bg-blue-100 dark:bg-blue-900/40">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-semibold text-blue-700 dark:text-blue-300">Col 1 — Date</th>
                                <th class="px-3 py-1.5 text-left font-semibold text-blue-700 dark:text-blue-300">Col 2 — Particulars</th>
                                <th class="px-3 py-1.5 text-left font-semibold text-blue-700 dark:text-blue-300">Col 3 — Source</th>
                                <th class="px-3 py-1.5 text-left font-semibold text-blue-700 dark:text-blue-300">Col 4 — Bank</th>
                                <th class="px-3 py-1.5 text-left font-semibold text-blue-700 dark:text-blue-300">Col 5 — Amount*</th>
                                <th class="px-3 py-1.5 text-left font-semibold text-emerald-700 dark:text-emerald-400">Col 6+ — Category Names</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-100 dark:divide-blue-900/30">
                            <tr>
                                <td class="px-3 py-1.5 text-zinc-600 dark:text-zinc-400">2026-05-01</td>
                                <td class="px-3 py-1.5 text-zinc-600 dark:text-zinc-400">Diesel for generator</td>
                                <td class="px-3 py-1.5 text-zinc-600 dark:text-zinc-400">Cash</td>
                                <td class="px-3 py-1.5 text-zinc-600 dark:text-zinc-400">GTBank</td>
                                <td class="px-3 py-1.5 text-zinc-400">—</td>
                                <td class="px-3 py-1.5 font-semibold text-emerald-700 dark:text-emerald-400">45000 (Fuel & Diesel col)</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-1.5 text-zinc-600 dark:text-zinc-400">2026-05-03</td>
                                <td class="px-3 py-1.5 text-zinc-600 dark:text-zinc-400">Electricity bill</td>
                                <td class="px-3 py-1.5 text-zinc-600 dark:text-zinc-400">Transfer</td>
                                <td class="px-3 py-1.5 text-zinc-600 dark:text-zinc-400">First Bank</td>
                                <td class="px-3 py-1.5 text-zinc-400">—</td>
                                <td class="px-3 py-1.5 font-semibold text-emerald-700 dark:text-emerald-400">38500 (Utilities col)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-blue-600 dark:text-blue-500">* Col 5 is used only if no category columns are present. The column header row must be included (check the box below).</p>
            </div>
            <div class="flex-shrink-0">
                <button wire:click="downloadSample"
                    class="inline-flex items-center gap-2 rounded-full bg-blue-700 px-5 py-2.5 text-xs font-semibold uppercase tracking-wide text-white hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Sample CSV
                </button>
                <p class="mt-2 text-xs text-blue-600 dark:text-blue-500 text-center">Opens in Excel / Google Sheets</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs text-zinc-500">Upload File</label>
                <input type="file" wire:model="file" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" />
                @error('file') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" wire:model="hasHeaders" />
                <span class="text-xs text-zinc-600">First row contains headers</span>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs text-zinc-500">Paste Raw Data</label>
                <textarea wire:model="raw" rows="6" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" placeholder="Paste CSV/TSV here"></textarea>
                @error('raw') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs text-zinc-500">Default Bank Account (optional)</label>
                <select wire:model="defaultBankAccountId" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <option value="">No default</option>
                    @foreach($this->bankAccounts as $acct)
                        <option value="{{ $acct->id }}">{{ $acct->bank_name }} · {{ $acct->account_number }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-zinc-500">Fallback Bank GL (optional)</label>
                <select wire:model="defaultBankGlAccountId" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <option value="">Use 1050 if missing</option>
                    @foreach($this->glAccounts as $acct)
                        <option value="{{ $acct->id }}">{{ $acct->account_number }} · {{ $acct->account_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" wire:click="analyze" class="rounded-lg border border-zinc-900 bg-zinc-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800">Analyze</button>
            <button type="button" wire:click="import" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-emerald-700">Import</button>
        </div>
    </div>

    @if($showPreview)
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Category to GL Mapping</h2>
            <p class="mt-1 text-xs text-zinc-500">Map each category column to a GL account. You can also create a new GL account name.</p>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach($categoryKeys as $key => $category)
                    <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">{{ $category }}</p>
                        <select wire:model="categoryMap.{{ $key }}" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <option value="">Select GL account</option>
                            @foreach($this->glAccounts as $acct)
                                <option value="{{ $acct->id }}">{{ $acct->account_number }} · {{ $acct->account_name }}</option>
                            @endforeach
                        </select>
                        <input type="text" wire:model="categoryNewName.{{ $key }}" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900" placeholder="Or create new GL account name" />
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="p-4">
                <h2 class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Preview (Long Format)</h2>
                <p class="text-xs text-zinc-500">First 20 converted rows.</p>
            </div>
            <div class="overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-left">Particulars</th>
                            <th class="px-4 py-2 text-left">Source</th>
                            <th class="px-4 py-2 text-left">Bank</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2 text-left">Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($previewRows as $row)
                            <tr class="border-t border-zinc-100 dark:border-zinc-800">
                                <td class="px-4 py-2">{{ $row['date'] }}</td>
                                <td class="px-4 py-2">{{ $row['particulars'] }}</td>
                                <td class="px-4 py-2">{{ $row['source'] }}</td>
                                <td class="px-4 py-2">{{ $row['bank'] }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row['amount'], 2) }}</td>
                                <td class="px-4 py-2">{{ $row['category'] }}</td>
                            </tr>
                        @endforeach
                        @if(empty($previewRows))
                            <tr>
                                <td class="px-4 py-3 text-zinc-500" colspan="6">No preview rows.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
