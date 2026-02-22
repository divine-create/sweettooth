<div class="p-3 space-y-3">
    <x-breadcrumb
        title="Account Security"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Settings'],
            ['label' => 'Security']
        ]"
        :compact="false"
        :with-icons="true" />

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ branch_route('branch-dashboard.profile') }}"
               class="px-3 py-2 text-sm rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                Profile
            </a>
            <span class="px-3 py-2 text-sm rounded-md bg-blue-600 text-white">
                Security
            </span>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mb-5">Change Password</h2>

        <form wire:submit="updatePassword" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Current Password</label>
                    <input type="password" wire:model="current_password" autocomplete="current-password"
                           class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500"
                           required />
                    @error('current_password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div></div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">New Password</label>
                    <input type="password" wire:model="password" autocomplete="new-password"
                           class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500"
                           required />
                    @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Confirm New Password</label>
                    <input type="password" wire:model="password_confirmation" autocomplete="new-password"
                           class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500"
                           required />
                    @error('password_confirmation') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200">
                    Update Password
                </button>
                @if (session()->has('password_message'))
                    <span class="text-sm text-green-600 dark:text-green-400">{{ session('password_message') }}</span>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-red-200 dark:border-red-800 p-6">
        <h2 class="text-xl font-bold text-red-700 dark:text-red-400 mb-2">Danger Zone</h2>
        <p class="text-sm text-zinc-600 dark:text-zinc-300 mb-4">
            Deleting your account is permanent and cannot be undone.
        </p>

        <form wire:submit="deleteAccount" onsubmit="return confirm('Delete this account permanently? This cannot be undone.');" class="space-y-3">
            <div class="max-w-md">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Confirm Password</label>
                <input type="password" wire:model="delete_password" autocomplete="current-password"
                       class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-red-500"
                       required />
                @error('delete_password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <button type="submit"
                    class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors duration-200">
                Delete Account
            </button>
        </form>
    </div>
</div>
