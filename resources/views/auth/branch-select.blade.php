<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select a Branch - SweetTooth POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-lg">ST</span>
                </div>
            </div>
            <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
                Select a Branch
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Welcome, {{ Auth::user()->name }}
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                @php
                    $branches = \App\Models\Branch::where('is_active', true)->get();
                @endphp

                @if($branches->count() > 0)
                    <form method="POST" action="{{ route('branch-dashboard.index') }}" class="space-y-6">
                        @csrf
                        
                        <div>
                            <label for="branch" class="block text-sm font-medium text-gray-700">
                                Choose a Branch
                            </label>
                            <select name="b_id" id="branch" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required onchange="document.getElementById('branch-form').submit()">
                                <option value="">-- Select a branch --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <noscript>
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                Continue
                            </button>
                        </noscript>
                    </form>
                @else
                    <div class="rounded-md bg-yellow-50 p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-800">
                                    No branches available. Please contact your administrator.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mt-6 text-center">
                    <a href="{{ route('logout') }}" id="logout-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-sm text-amber-600 hover:text-amber-500">
                        Sign out
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle 419 CSRF errors on logout forms
        document.getElementById('logout-link').addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('logout-form');
            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(response => {
                if (response.status === 419) {
                    // CSRF token expired, redirect to login
                    window.location.href = '{{ route("login") }}';
                } else if (response.ok) {
                    window.location.href = '/';
                } else {
                    // Other error, still redirect to login
                    window.location.href = '{{ route("login") }}';
                }
            }).catch(error => {
                console.error('Logout failed:', error);
                window.location.href = '{{ route("login") }}';
            });
        });
    </script>
</body>
</html>
