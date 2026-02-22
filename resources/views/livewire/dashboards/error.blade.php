<div class="min-h-screen bg-gradient-to-br from-red-50 to-zinc-100 dark:from-zinc-900 dark:to-zinc-800 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white dark:bg-zinc-800 rounded-lg shadow-lg p-8 text-center">
        <!-- Icon -->
        <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <!-- Title -->
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mb-2">
            Oops! Something Went Wrong
        </h1>

        <!-- Message -->
        <p class="text-zinc-600 dark:text-zinc-400 mb-6">
            We encountered an error while loading the dashboard. Please try refreshing the page or contact support.
        </p>

        <!-- Actions -->
        <div class="flex items-center justify-center space-x-3">
            <button onclick="location.reload()"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                Try Again
            </button>
            <a href="{{ route('branch-dashboard.index') }}"
                class="px-6 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                Go Back
            </a>
        </div>
    </div>
</div>
