<form wire:submit="createContact" class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Contact Name</label>
        <input
            type="text"
            wire:model="name"
            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Full name"
        />
        @error('name')<span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span>@enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Email</label>
            <input
                type="email"
                wire:model="email"
                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="email@example.com"
            />
            @error('email')<span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Phone</label>
            <input
                type="tel"
                wire:model="phone"
                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="+254..."
            />
            @error('phone')<span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span>@enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Role</label>
        <input
            type="text"
            wire:model="role"
            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="e.g., Sales Manager, Account Manager"
        />
        @error('role')<span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span>@enderror
    </div>

    <label class="flex items-center gap-2">
        <input
            type="checkbox"
            wire:model="isPrimary"
            class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 focus:ring-2 focus:ring-blue-500"
        />
        <span class="text-sm text-zinc-700 dark:text-zinc-300">Set as primary contact</span>
    </label>

    <div class="flex gap-2">
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            Add Contact
        </button>
    </div>
</form>
