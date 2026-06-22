<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Modal for Creation Reason (Non-Super Admins) -->
    @if(!is_super_admin())
    {{-- Show modal if open OR if there are validation errors for creationReason --}}
    <div class="fixed inset-0 z-40 bg-black bg-opacity-50 transition-opacity {{ ($showReasonModal || $errors->has('creationReason')) ? 'opacity-100 visible' : 'opacity-0 invisible' }}"
        wire:click="closeReasonModal">
    </div>
    <div class="fixed inset-0 z-50 flex items-center justify-center {{ ($showReasonModal || $errors->has('creationReason')) ? 'pointer-events-auto' : 'pointer-events-none' }}">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all {{ ($showReasonModal || $errors->has('creationReason')) ? 'scale-100 opacity-100' : 'scale-95 opacity-0' }}"
            @click.stop>
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                    {{ $isEditing ? 'Update Department - Reason Required' : 'Create Department - Reason Required' }}
                </h3>
                <button type="button" wire:click="closeReasonModal"
                    class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-4" @click.stop>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                    {{ $isEditing 
                        ? 'Please provide a reason for updating this department. This will be reviewed by an administrator.' 
                        : 'Please provide a reason for creating this department. This will be reviewed by an administrator.' 
                    }}
                </p>
                <textarea wire:model.live="creationReason" rows="4"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                    placeholder="Explain your reason (minimum 5 characters)..."
                    @click.stop></textarea>
                @error('creationReason')
                    <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span>
                @enderror
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                    {{ strlen($creationReason) }}/5 minimum characters required
                </p>
            </div>
            
            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex gap-3 justify-end">
                <button type="button" wire:click="closeReasonModal"
                    class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="button" wire:click="proceedWithReasonSubmitted" 
                    {{ strlen($creationReason) < 5 ? 'disabled' : '' }}
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed rounded-lg transition-colors">
                    <span wire:loading.remove wire:target="proceedWithReasonSubmitted">Submit Request</span>
                    <span wire:loading wire:target="proceedWithReasonSubmitted">Submitting...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
            {{ $isEditing ? 'Edit Department' : 'Add New Department' }}</h2>

    </div>

    <!-- Scrollable Form Content -->
    <div
        class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin scrollbar-thumb-zinc-300 dark:scrollbar-thumb-zinc-700 scrollbar-track-transparent">
        <form class="space-y-6" @keydown.enter.prevent>
            <!-- Department Name -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Department Name
                    *</label>
                <input type="text" wire:model="name"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter department name" required>
                @error('name')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Category Selection -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Category
                    *</label>
                <x-select.styled wire:model="category_id" :options="$this->getDepartmentCategories()
                    ->map(fn($cat) => ['label' => $cat->name, 'value' => $cat->id])
                    ->toArray()" select="label:label|value:value"
                    placeholder="Select Category" searchable required />
                @error('category_id')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            @if(is_super_admin())
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Branch
                    *</label>
                <x-select.styled wire:model="branch_id" :options="$this->getBranches()
                    ->map(fn($branch) => ['label' => $branch->name, 'value' => $branch->id])
                    ->toArray()" select="label:label|value:value"
                    placeholder="Select Department" searchable required />
                @error('category_id')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>
            @endif
            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Description</label>
                <textarea wire:model="description" rows="4"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                    placeholder="Department description (optional)"></textarea>
                @error('description')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Default Bank Account for Transfers -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Default Bank Account (Transfers) *</label>
                <x-select.styled
                    wire:model="bank_account_id"
                    :options="$this->getBankAccounts()
                        ->map(fn($acct) => ['label' => $acct->bank_name . ' · ' . $acct->account_number, 'value' => $acct->id])
                        ->toArray()"
                    select="label:label|value:value"
                    placeholder="Select Bank Account"
                    searchable
                />
                @error('bank_account_id')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
                <p class="text-xs text-zinc-500 mt-1">Required. Used when POS Transfer is selected for this department.</p>
            </div>


            <!-- GL Account Mappings -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">GL Account Mappings</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Optional. Used by the GL posting engine. Wrong account types will be rejected.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Revenue Account <span class="text-xs text-zinc-400">(Revenue type only)</span></label>
                    <select wire:model="revenue_account_id" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-sm">
                        <option value="">-- None --</option>
                        @foreach($this->getGlAccountsByType()['revenue'] as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->account_number }} — {{ $acct->account_name }}</option>
                        @endforeach
                    </select>
                    @error('revenue_account_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Tax Account <span class="text-xs text-zinc-400">(Liability/Tax type only)</span></label>
                    <select wire:model="tax_account_id" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-sm">
                        <option value="">-- None --</option>
                        @foreach($this->getGlAccountsByType()['tax'] as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->account_number }} — {{ $acct->account_name }}</option>
                        @endforeach
                    </select>
                    @error('tax_account_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Receivable Account <span class="text-xs text-zinc-400">(Asset type only)</span></label>
                    <select wire:model="receivable_account_id" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-sm">
                        <option value="">-- None --</option>
                        @foreach($this->getGlAccountsByType()['asset'] as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->account_number }} — {{ $acct->account_name }}</option>
                        @endforeach
                    </select>
                    @error('receivable_account_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Cash Account <span class="text-xs text-zinc-400">(Asset type only)</span></label>
                    <select wire:model="cash_account_id" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-sm">
                        <option value="">-- None --</option>
                        @foreach($this->getGlAccountsByType()['asset'] as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->account_number }} — {{ $acct->account_name }}</option>
                        @endforeach
                    </select>
                    @error('cash_account_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Expense Account <span class="text-xs text-zinc-400">(Expense type — non-production material consumption posts here)</span></label>
                    <select wire:model="expense_account_id" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-sm">
                        <option value="">-- None (uses default Consumables Expense) --</option>
                        @foreach($this->getGlAccountsByType()['expense'] as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->account_number }} — {{ $acct->account_name }}</option>
                        @endforeach
                    </select>
                    @error('expense_account_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <button type="button" wire:click="initiateSave"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                <span wire:loading.remove wire:target="initiateSave">
                    {{ $isEditing ? 'Update Department' : 'Create Department' }}
                </span>
                <span wire:loading wire:target="initiateSave">
                    {{ $isEditing ? 'Updating...' : 'Creating...' }}
                </span>
            </button>
        </form>

    </div>
