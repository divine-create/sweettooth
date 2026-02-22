<div class="flex items-center justify-center min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="text-center">
        <div class="animate-spin">
            <svg class="w-12 h-12 text-orange-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
            </svg>
        </div>
        <h2 class="mt-4 text-xl font-semibold text-gray-900">Loading your dashboard...</h2>
        <p class="mt-2 text-gray-600">{{ current_actor()?->name }}, please wait while we prepare your dashboard.</p>
    </div>
</div>
