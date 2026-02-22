<div class="space-y-6">
    <!-- Tabs Navigation -->
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <div class="flex gap-8 overflow-x-auto">
            <button
                @click="$wire.activeTab = 'overview'"
                :class="$wire.activeTab === 'overview' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-zinc-600 dark:text-zinc-400'"
                class="px-4 py-3 font-medium whitespace-nowrap"
            >
                Overview
            </button>
            <button
                @click="$wire.activeTab = 'contacts'"
                :class="$wire.activeTab === 'contacts' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-zinc-600 dark:text-zinc-400'"
                class="px-4 py-3 font-medium whitespace-nowrap"
            >
                Contacts
            </button>
            <button
                @click="$wire.activeTab = 'banking'"
                :class="$wire.activeTab === 'banking' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-zinc-600 dark:text-zinc-400'"
                class="px-4 py-3 font-medium whitespace-nowrap"
            >
                Banking
            </button>
            <button
                @click="$wire.activeTab = 'documents'"
                :class="$wire.activeTab === 'documents' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-zinc-600 dark:text-zinc-400'"
                class="px-4 py-3 font-medium whitespace-nowrap"
            >
                Documents
            </button>
            <button
                @click="$wire.activeTab = 'performance'"
                :class="$wire.activeTab === 'performance' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-zinc-600 dark:text-zinc-400'"
                class="px-4 py-3 font-medium whitespace-nowrap"
            >
                Performance
            </button>
        </div>
    </div>

    <!-- Overview Tab -->
    <div class="space-y-6">
        <!-- Header with Edit Button -->
        <div class="flex justify-between items-start">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 flex-1">
                <div>
                    <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2">Company Information</h3>
                    <div class="space-y-3">
                        <div>
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">Code</span>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $supplier->code }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">Name</span>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $supplier->name }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">Status</span>
                            <p class="inline-block px-3 py-1 text-xs font-semibold rounded-full {{ $this->getStatusBadgeClass() }}">
                                {{ $this->getStatusLabel() }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">Verified</span>
                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ $supplier->is_verified ? '✓ Yes' : '✗ No' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2">Contact Information</h3>
                    <div class="space-y-3">
                        <div>
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">Email</span>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $supplier->email }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">Phone</span>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $supplier->phone }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">Website</span>
                            @if($supplier->website)
                                <a href="{{ $supplier->website }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $supplier->website }}
                                </a>
                            @else
                                <p class="text-zinc-500 dark:text-zinc-400">N/A</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <button
                wire:click="toggleEditMode"
                class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap"
            >
                {{ $editMode ? 'Cancel' : 'Edit' }}
            </button>
        </div>

        <!-- Edit Form -->
        @if($editMode)
            <form wire:submit="saveChanges" class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="text" wire:model="editName" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Name" />
                    <input type="email" wire:model="editEmail" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Email" />
                    <input type="tel" wire:model="editPhone" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Phone" />
                    <input type="url" wire:model="editWebsite" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Website" />
                    <input type="text" wire:model="editAddress" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Address" />
                    <input type="text" wire:model="editCity" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="City" />
                    <input type="text" wire:model="editState" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="State" />
                    <input type="text" wire:model="editPostalCode" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Postal Code" />
                    <input type="text" wire:model="editCountry" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Country" />
                    <input type="text" wire:model="editTaxId" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Tax ID" />
                    <select wire:model="editStatus" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                        <option value="pending">Pending</option>
                    </select>
                    <input type="number" wire:model="editCreditLimit" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Credit Limit" min="0" step="0.01" />
                    <input type="number" wire:model="editPaymentTermsDays" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Payment Terms Days" min="0" />
                </div>
                <textarea wire:model="editNotes" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Notes" rows="3"></textarea>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Save Changes</button>
                    <button type="button" wire:click="toggleEditMode" class="px-4 py-2 bg-zinc-400 text-white rounded-lg hover:bg-zinc-500">Cancel</button>
                </div>
            </form>
        @endif

        <!-- Credit Information -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Credit Limit</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($supplier->credit_limit, 2) }}</p>
            </div>
            <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Outstanding Balance</p>
                <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($supplier->outstanding_balance, 2) }}</p>
                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-2">{{ number_format($this->getCreditUtilizationPercentage(), 1) }}% utilized</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Available Credit</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($this->getAvailableCredit(), 2) }}</p>
            </div>
        </div>

        <!-- Performance Overview -->
        @if($supplier->getLatestPerformance())
            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                <h3 class="font-semibold text-zinc-900 dark:text-white mb-3">Latest Performance</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Quality</p>
                        <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $supplier->getLatestPerformance()->quality_score ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Delivery</p>
                        <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $supplier->getLatestPerformance()->delivery_score ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Price</p>
                        <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $supplier->getLatestPerformance()->price_competitiveness_score ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Response Time</p>
                        <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $supplier->getLatestPerformance()->response_time_score ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Overall</p>
                        <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $supplier->getLatestPerformance()->overall_score ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Contacts Tab -->
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="font-semibold text-zinc-900 dark:text-white">Contacts</h3>
            <button
                wire:click="$toggle('showContactForm')"
                class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
            >
                Add Contact
            </button>
        </div>

        @if($showContactForm)
            <livewire:branch-dashboard.supplier.create-supplier-contact :supplier="$supplier" @contactCreated="$refresh" />
        @endif

        <div class="space-y-2">
            @forelse($supplier->contacts as $contact)
                <div class="bg-zinc-50 dark:bg-zinc-900 p-3 rounded-lg flex justify-between items-center">
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $contact->name }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $contact->email }} | {{ $contact->phone }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $contact->role }} @if($contact->is_primary)<span class="badge">Primary</span>@endif</p>
                    </div>
                    <button
                        wire:click="deleteContact({{ $contact->id }})"
                        class="text-red-600 dark:text-red-400 hover:text-red-800"
                    >
                        Delete
                    </button>
                </div>
            @empty
                <p class="text-zinc-500 dark:text-zinc-400 text-sm">No contacts added yet</p>
            @endforelse
        </div>
    </div>

    <!-- Banking Tab -->
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="font-semibold text-zinc-900 dark:text-white">Bank Accounts</h3>
            <button
                wire:click="$toggle('showBankAccountForm')"
                class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
            >
                Add Account
            </button>
        </div>

        @if($showBankAccountForm)
            <livewire:branch-dashboard.supplier.create-supplier-bank-account :supplier="$supplier" @accountCreated="$refresh" />
        @endif

        <div class="space-y-2">
            @forelse($supplier->bankAccounts as $account)
                <div class="bg-zinc-50 dark:bg-zinc-900 p-3 rounded-lg flex justify-between items-center">
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $account->bank_name }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $account->account_number }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $account->account_holder_name }} @if($account->is_primary)<span class="badge">Primary</span>@endif</p>
                    </div>
                    <button
                        wire:click="deleteBankAccount({{ $account->id }})"
                        class="text-red-600 dark:text-red-400 hover:text-red-800"
                    >
                        Delete
                    </button>
                </div>
            @empty
                <p class="text-zinc-500 dark:text-zinc-400 text-sm">No bank accounts added yet</p>
            @endforelse
        </div>
    </div>

    <!-- Documents Tab -->
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="font-semibold text-zinc-900 dark:text-white">Documents</h3>
            <button
                class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
            >
                Upload Document
            </button>
        </div>

        <div class="space-y-2">
            @forelse($supplier->documents as $document)
                <div class="bg-zinc-50 dark:bg-zinc-900 p-3 rounded-lg flex justify-between items-center">
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $document->document_type }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $document->file_name }}</p>
                        @if($document->expiry_date)
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Expires: {{ $document->expiry_date->format('M d, Y') }}</p>
                        @endif
                    </div>
                    <button
                        wire:click="deleteDocument({{ $document->id }})"
                        class="text-red-600 dark:text-red-400 hover:text-red-800"
                    >
                        Delete
                    </button>
                </div>
            @empty
                <p class="text-zinc-500 dark:text-zinc-400 text-sm">No documents uploaded yet</p>
            @endforelse
        </div>
    </div>

    <!-- Performance Tab -->
    <livewire:branch-dashboard.supplier.supplier-performance :supplier="$supplier" />
</div>
