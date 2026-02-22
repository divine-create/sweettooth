<div class="space-y-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Manual Journal Entry</h2>
            <button wire:click="$toggle('showForm')" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                {{ $showForm ? 'Cancel' : 'New Entry' }}
            </button>
        </div>

        @if ($showForm)
            <!-- Entry Form -->
            <div class="space-y-6 p-6 bg-blue-50 border border-blue-200 rounded">
                <!-- Header Section -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Entry Date *</label>
                        <input type="date" wire:model="entryDate" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period *</label>
                        <select wire:model="selectedPeriodId" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Select Period</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}">{{ $period->getDisplayName() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                        <input type="text" wire:model="referenceNumber" placeholder="MAN-001" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cost Center</label>
                        <input type="text" wire:model="costCenter" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                    <textarea wire:model="description" placeholder="Detailed description of the journal entry..." 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="2"></textarea>
                </div>

                <!-- Remarks -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                    <textarea wire:model="remarks" placeholder="Additional notes..." 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="2"></textarea>
                </div>

                <!-- Line Items -->
                <div>
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-semibold">Journal Entry Lines</h3>
                        <button wire:click="addLineItem" type="button" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                            + Add Line
                        </button>
                    </div>

                    <div class="overflow-x-auto border rounded">
                        <table class="w-full">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="px-4 py-2 text-left">General Ledger Account</th>
                                    <th class="px-4 py-2 text-right">Debit</th>
                                    <th class="px-4 py-2 text-right">Credit</th>
                                    <th class="px-4 py-2 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lineItems as $lineId => $item)
                                    <tr class="border-b">
                                        <td class="px-4 py-2">
                                            <select wire:model="lineItems.{{ $lineId }}.account_id" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                                <option value="">Select Account</option>
                                                @foreach ($accounts as $account)
                                                    <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->account_name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" step="0.01" wire:model.lazy="lineItems.{{ $lineId }}.debit" 
                                                   class="w-full px-2 py-1 border border-gray-300 rounded text-right text-sm" placeholder="0.00">
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" step="0.01" wire:model.lazy="lineItems.{{ $lineId }}.credit" 
                                                   class="w-full px-2 py-1 border border-gray-300 rounded text-right text-sm" placeholder="0.00">
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <button wire:click="removeLineItem({{ $lineId }})" type="button" class="text-red-600 hover:text-red-800 text-sm font-semibold">
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-2 text-center text-gray-500 text-sm">No line items added yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totals -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="p-3 bg-white border rounded">
                        <p class="text-xs text-gray-600 mb-1">Total Debit</p>
                        <p class="text-xl font-bold">{{ number_format($totals['debit'] ?? 0, 2) }}</p>
                    </div>
                    <div class="p-3 bg-white border rounded">
                        <p class="text-xs text-gray-600 mb-1">Total Credit</p>
                        <p class="text-xl font-bold">{{ number_format($totals['credit'] ?? 0, 2) }}</p>
                    </div>
                    <div class="p-3 {{ ($totals['balanced'] ?? false) ? 'bg-green-100 border border-green-300' : 'bg-red-100 border border-red-300' }} rounded">
                        <p class="text-xs font-semibold mb-1">{{ ($totals['balanced'] ?? false) ? '✓ Balanced' : '✗ Not Balanced' }}</p>
                        <p class="text-xl font-bold">Diff: {{ number_format(abs(($totals['debit'] ?? 0) - ($totals['credit'] ?? 0)), 2) }}</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2">
                    <button wire:click="saveDraft" type="button" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        Save Draft
                    </button>
                    <button wire:click="postEntry" type="button" 
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            @disabled(!($totals['balanced'] ?? false))>
                        Post Entry
                    </button>
                    <button wire:click="$toggle('showForm')" type="button" class="px-4 py-2 bg-gray-400 text-white rounded-md hover:bg-gray-500">
                        Cancel
                    </button>
                </div>
            </div>
        @endif

        <!-- Draft Entries -->
        @if (!empty($draftEntries))
            <div class="mt-6">
                <h3 class="text-lg font-bold mb-3">Saved Drafts</h3>
                <div class="space-y-2">
                    @foreach ($draftEntries as $idx => $draft)
                        <div class="p-3 border rounded hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-semibold">{{ $draft['description'] }}</p>
                                    <p class="text-xs text-gray-600">{{ $draft['created_at'] }}</p>
                                </div>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">Draft</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
