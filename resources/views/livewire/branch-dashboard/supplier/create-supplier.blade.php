<div class="p-6">
    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">Create New Supplier</h2>


    <form wire:submit="createSupplier" class="space-y-6">
        <!-- Basic Information -->
        <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Basic Information</h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">Supplier code is generated automatically.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Supplier Name</label>
                    <input
                        type="text"
                        wire:model="name"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Company name"
                    />
                    @error('name')<span class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Email</label>
                    <input
                        type="email"
                        wire:model="email"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="supplier@example.com"
                    />
                    @error('email')<span class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Phone</label>
                    <input
                        type="tel"
                        wire:model="phone"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="+254..."
                    />
                    @error('phone')<span class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Website</label>
                    <input
                        type="url"
                        wire:model="website"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="https://example.com"
                    />
                    @error('website')<span class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Tax ID <span class="text-zinc-400 font-normal">(optional)</span></label>
                    <input
                        type="text"
                        wire:model="taxId"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="PIN/VAT number"
                    />
                    @error('taxId')<span class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        <!-- Address -->
        <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Address</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Street Address</label>
                    <input
                        type="text"
                        wire:model="address"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="123 Main Street"
                    />
                    @error('address')<span class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">City</label>
                    <input
                        type="text"
                        wire:model="city"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Nairobi"
                    />
                    @error('city')<span class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">State</label>
                    <input
                        type="text"
                        wire:model="state"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="County"
                    />
                    @error('state')<span class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Postal Code</label>
                    <input
                        type="text"
                        wire:model="postalCode"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="00100"
                    />
                    @error('postalCode')<span class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Country</label>
                    <input
                        type="text"
                        wire:model="country"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Kenya"
                    />
                    @error('country')<span class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        <!-- Payment Terms -->
        <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Payment Terms</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Credit Limit <span class="text-zinc-400 font-normal">(optional)</span></label>
                    <input
                        type="number"
                        wire:model="creditLimit"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Defaults to 0"
                        min="0"
                        step="0.01"
                    />
                    @error('creditLimit')<span class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Payment Terms (Days) <span class="text-zinc-400 font-normal">(optional)</span></label>
                    <input
                        type="number"
                        wire:model="paymentTermsDays"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Defaults to 30 days"
                        min="0"
                    />
                    @error('paymentTermsDays')<span class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Notes</label>
            <textarea
                wire:model="notes"
                rows="3"
                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Additional notes about the supplier..."
            ></textarea>
            @error('notes')<span class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</span>@enderror
        </div>

        <!-- Form Actions -->
        <div class="flex gap-3 justify-end">
            <button
                type="button"
                @click="$dispatch('close-form')"
                class="px-4 py-2 text-zinc-700 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
                Create Supplier
            </button>
        </div>
    </form>
</div>
