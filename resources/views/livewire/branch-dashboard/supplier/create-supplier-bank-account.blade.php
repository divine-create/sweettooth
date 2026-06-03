<form wire:submit="createBankAccount" class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Bank Name</label>
        <input
            type="text"
            wire:model="bankName"
            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Bank name"
        />
        @error('bankName')<span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span>@enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Account Number</label>
            <input
                type="text"
                wire:model="accountNumber"
                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="1234567890"
            />
            @error('accountNumber')<span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Account Type</label>
            <select
                wire:model="accountType"
                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="checking">Checking</option>
                <option value="savings">Savings</option>
                <option value="business">Business</option>
            </select>
            @error('accountType')<span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span>@enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Account Holder Name</label>
        <input
            type="text"
            wire:model="accountHolderName"
            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Account holder full name"
        />
        @error('accountHolderName')<span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">SWIFT Code</label>
        <input
            type="text"
            wire:model="swiftCode"
            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="e.g., KENYAA2X"
        />
        @error('swiftCode')<span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span>@enderror
    </div>

    <label class="flex items-center gap-2">
        <input
            type="checkbox"
            wire:model="isPrimary"
            class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 focus:ring-2 focus:ring-blue-500"
        />
        <span class="text-sm text-zinc-700 dark:text-zinc-300">Set as primary account</span>
    </label>

    <div class="flex gap-2">
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            Add Bank Account
        </button>
    </div>
</form>
