<div class="p-3 space-y-3">

    <x-breadcrumb
        title="Profile Settings"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Settings'],
            ['label' => 'Profile']
        ]"
        :compact="false"
        :with-icons="true" />

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="px-3 py-2 text-sm rounded-md bg-blue-600 text-white">
                Profile
            </span>
            <a href="{{ branch_route('branch-dashboard.profile.security') }}"
               class="px-3 py-2 text-sm rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                Security
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mb-6">Profile Information</h2>
        
        <form wire:submit="updateProfileInformation" class="w-full space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Name</label>
                    <input type="text" wire:model="name" 
                           class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500"
                           required />
                    @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Email</label>
                    <input type="email" wire:model="email" 
                           class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500"
                           required />
                    @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Email Verification Required</h3>
                            <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                <p>Your email address is unverified.</p>
                                <p class="mt-1">
                                    <button type="button" wire:click="resendVerificationNotification" 
                                            class="font-medium text-yellow-700 underline hover:text-yellow-600 dark:text-yellow-400 dark:hover:text-yellow-300">
                                        Click here to re-send the verification email.
                                    </button>
                                </p>
                            </div>
                            
                            @if (session('status') === 'verification-link-sent')
                                <div class="mt-2 text-sm text-green-600 dark:text-green-400">
                                    A new verification link has been sent to your email address.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-end">
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center">
                    <span wire:loading.remove wire:target="updateProfileInformation">Save Changes</span>
                    <span wire:loading wire:target="updateProfileInformation" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>
                
                <span class="ml-3 text-green-600 dark:text-green-400" wire:loading.remove wire:target="updateProfileInformation">
                    @if(session()->has('message'))
                        {{ session('message') }}
                    @endif
                    <span wire:dirty wire:target="updateProfileInformation">Saving...</span>
                    <span wire:offline>Offline</span>
                </span>
            </div>
        </form>
    </div>
</div>
