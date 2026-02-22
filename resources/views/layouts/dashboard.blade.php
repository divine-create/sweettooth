<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ \App\Helpers\Settings::businessConfiguration('business_name', config('app.name')) }} - Dashboard</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="flex h-screen bg-gray-50">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-gray-200 overflow-y-auto">
            <!-- Logo/Brand -->
            <div class="p-6 border-b border-gray-200">
                <a href="{{ route('home') }}" class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-lg">ST</span>
                    </div>
                    <span class="font-bold text-xl text-gray-900">SweetTooth</span>
                </a>
            </div>

            <!-- User Info -->
            <div class="p-4 bg-gray-50 border-b border-gray-200 mx-4 my-4 rounded-lg">
                <p class="text-sm font-semibold text-gray-900">{{ current_actor()?->name ?? 'User' }}</p>
                <p class="text-xs text-gray-600 mt-1">
                    {{ ucwords(str_replace('_', ' ', current_actor()?->roles()->first()?->name ?? 'No Role')) }}
                </p>
                @if($branchName = \App\Models\Branch::find(current_branch_id())?->name)
                    <p class="text-xs text-gray-500 mt-1">{{ $branchName }}</p>
                @endif
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-2">
                {{ $navigation ?? '' }}
            </nav>

            <!-- Quick Actions -->
            <div class="p-4 border-t border-gray-200 mt-auto space-y-2">
                <a href="{{ route('settings.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">
                    ⚙️ Settings
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg">
                        🚪 Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-gray-200 px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $title ?? 'Dashboard' }}</h1>
                    @if($subtitle ?? false)
                        <p class="text-sm text-gray-600 mt-1">{{ $subtitle }}</p>
                    @endif
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Refresh Button -->
                    <button wire:click="refresh" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600 hover:text-gray-900" title="Refresh">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>

                    <!-- Branch Selector (for non-super-admin) -->
                    @if(auth()->check())
                        <select onchange="window.location.href = '{{ url('branch-dashboard/dashboard/router') }}?b_id=' + this.value" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @foreach(\App\Models\Branch::where('is_active', true)->get() as $branch)
                                <option value="{{ $branch->id }}" {{ current_branch_id() === $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <!-- User Avatar -->
                    <div class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                            {{ current_actor()?->initials() ?? '?' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto">
                <div class="p-8">
                    <!-- Flash Messages -->
                    @if ($message = session('success'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
                            {{ $message }}
                        </div>
                    @endif

                    @if ($message = session('error'))
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                            {{ $message }}
                        </div>
                    @endif

                    @if ($message = session('warning'))
                        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-700">
                            {{ $message }}
                        </div>
                    @endif

                    <!-- Main Content Slot -->
                    {{ $slot }}
                </div>
            </div>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 px-8 py-4 text-sm text-gray-600">
                <div class="flex items-center justify-between">
                    <p>Dashboard • SweetTooth POS System</p>
                    <p>Last updated: <span wire:poll-5000="refresh">{{ now()->format('H:i:s') }}</span></p>
                </div>
            </footer>
        </main>
    </div>

    @livewireScripts

    <script>
        // Handle 419 CSRF errors on logout forms
        if (!window.originalFetch) {
            window.originalFetch = window.fetch;
        }
        window.fetch = function(...args) {
            return window.originalFetch.apply(this, args).then(response => {
                if (response.status === 419 && args[0].includes('/logout')) {
                    // CSRF token expired on logout, redirect to login
                    window.location.href = '{{ route("login") }}';
                    return response;
                }
                return response;
            }).catch(error => {
                // Handle network errors on logout
                if (args[0].includes('/logout')) {
                    window.location.href = '{{ route("login") }}';
                }
                throw error;
            });
        };
    </script>
</body>
</html>
