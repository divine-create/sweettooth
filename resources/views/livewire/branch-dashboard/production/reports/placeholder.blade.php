<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12">
        <div class="text-center">
            <x-icon name="document-chart-bar" class="mx-auto h-24 w-24 text-gray-400" />
            <h3 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Report Coming Soon</h3>
            <p class="mt-2 text-lg text-gray-500 dark:text-gray-400">
                This report is currently under development and will be available soon.
            </p>
            <div class="mt-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Selected Period: {{ $customDateFrom }} to {{ $customDateTo }}
                </p>
            </div>
            <div class="mt-8">
                <x-button onclick="window.history.back()" color="primary">
                    <x-icon name="arrow-left" class="w-5 h-5 mr-2" />
                    Go Back
                </x-button>
            </div>
        </div>
    </div>
</div>
