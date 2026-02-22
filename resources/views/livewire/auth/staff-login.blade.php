<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in to your account')" :description="__('Select branch and enter credentials')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="login" class="flex flex-col gap-6">
        <!-- Branch Selection -->
        <flux:select
            wire:model="branch_id"
            :label="__('Select Branch')"
            required
        >
            <option value="">{{ __('-- Choose a Branch --') }}</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
            @endforeach
        </flux:select>

        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <!-- Password -->
        <div class="relative">
            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            @if (Route::has('password.request'))
                <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                    {{ __('Forgot your password?') }}
                </flux:link>
            @endif
        </div>

        <!-- Remember Me -->
        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('Log in') }}
            </flux:button>
        </div>
    </form>

    <!-- Admin Login Button -->
    <div class="text-center">
        <flux:link :href="route('login')" variant="outline" class="w-full">
            {{ __('I am an admin') }}
        </flux:link>
    </div>
</div>
