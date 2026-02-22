<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">Manual General Ledger Entry</h1>
        <p class="text-zinc-600 dark:text-zinc-400">Create and post manual general ledger entries</p>
    </div>

    @if (session()->has('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <p class="text-green-800 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-red-800 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    @error('general')
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-red-800 dark:text-red-300">{{ $message }}</p>
        </div>
    @enderror

    <!-- Entry Form -->
    <form wire:submit="submit" class="space-y-6">
        <!-- Header Fields -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Entry Details</h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 -mt-2">Complete the header, then add line items. Each line should be either debit or credit, not both.</p>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Reference (Auto-generated)</label>
                    <input 
                        wire:model="reference" 
                        type="text" 
                        readonly
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-100 dark:bg-zinc-700/60 text-zinc-900 dark:text-white focus:outline-none"
                    >
                    <p class="text-xs text-zinc-500 mt-1">Generated automatically for consistency.</p>
                    @error('reference') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Period *</label>
                    <select 
                        wire:model="periodId"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Select Period</option>
                        @forelse ($this->periods as $period)
                            <option value="{{ $period->id }}">{{ $period->name ?? "{$period->month}/{$period->year}" }}</option>
                        @empty
                            <option disabled>No open periods available</option>
                        @endforelse
                    </select>
                    <p class="text-xs text-zinc-500 mt-1">Only open periods are available.</p>
                    @error('periodId') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Entry Date *</label>
                    <input 
                        wire:model="entryDate" 
                        type="date"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                    @error('entryDate') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Posting *</label>
                    <select
                        wire:model="status"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="draft">Save as Draft</option>
                        <option value="posted">Create and Post</option>
                    </select>
                    <p class="text-xs text-zinc-500 mt-1">Choose whether to post immediately.</p>
                    @error('status') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Description *</label>
                <textarea 
                    wire:model="description" 
                    placeholder="Enter journal entry description"
                    rows="2"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                ></textarea>
                @error('description') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Line Items -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 space-y-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Line Items</h2>
                <button 
                    type="button"
                    wire:click="addLine"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition"
                >
                    + Add Another Line
                </button>
            </div>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Tip: every line needs an account and amount. Use debit or credit only on each line.</p>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-zinc-900 dark:text-white">Account</th>
                            <th class="px-4 py-3 text-left font-semibold text-zinc-900 dark:text-white">Description</th>
                            <th class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-white">Debit</th>
                            <th class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-white">Credit</th>
                            <th class="px-4 py-3 text-center font-semibold text-zinc-900 dark:text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($lines as $index => $line)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                                <td class="px-4 py-3">
                                    <select 
                                        wire:model="lines.{{ $index }}.account_id"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="">Select Account</option>
                                        @foreach ($this->glAccounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->account_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('lines.' . $index . '.account_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-3">
                                    <input 
                                        wire:model="lines.{{ $index }}.description" 
                                        type="text"
                                        placeholder="Optional"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <input 
                                        wire:model.live="lines.{{ $index }}.debit" 
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm text-right placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    >
                                    @error('lines.' . $index . '.debit') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-3">
                                    <input 
                                        wire:model.live="lines.{{ $index }}.credit" 
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm text-right placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    >
                                    @error('lines.' . $index . '.credit') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if (count($lines) > 2)
                                        <button 
                                            type="button"
                                            wire:click="removeLine({{ $index }})"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 font-medium text-sm"
                                        >
                                            Remove
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-700/30">
                        <tr>
                            <td colspan="2" class="px-4 py-3 font-semibold text-zinc-900 dark:text-white text-right">Totals:</td>
                            <td class="px-4 py-3 text-right font-semibold {{ $this->totalDebits > 0 ? 'text-green-600 dark:text-green-400' : 'text-zinc-900 dark:text-white' }}">
                                {{ $this->formatCurrency($this->totalDebits) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold {{ $this->totalCredits > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">
                                {{ $this->formatCurrency($this->totalCredits) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $this->isBalanced ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' }}
                                ">
                                    {{ $this->isBalanced ? '✓ Balanced' : '✗ Not Balanced' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5" class="px-4 pb-4 text-sm text-right {{ $this->imbalanceAmount > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400' }}">
                                Difference: {{ $this->formatCurrency($this->imbalanceAmount) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @error('lines') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-4 justify-end">
            <a 
                href="{{ route('branch-dashboard.accounting.dashboard') }}"
                class="px-6 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white hover:bg-zinc-50 dark:hover:bg-zinc-700 font-medium transition"
            >
                Cancel
            </a>
            <button 
                type="submit"
                @disabled(! $this->canSubmit)
                class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-zinc-400 disabled:cursor-not-allowed text-white rounded-lg font-medium transition"
            >
                {{ $status === 'posted' ? 'Create & Post' : 'Create as Draft' }}
            </button>
        </div>
        @if (! $this->canSubmit)
            <p class="text-sm text-zinc-600 dark:text-zinc-400 text-right">To submit: add amounts on lines, include at least one debit and one credit, and keep totals balanced.</p>
        @endif
    </form>
</div>
