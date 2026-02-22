<x-layouts.app :title="__('Dashboard')">
<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <!-- Top Grid -->
    <div class="grid auto-rows-min gap-6 md:grid-cols-3">
        <!-- Branches Card -->
        <div
            class="relative overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition-all p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Total Branches</h3>
                    <p class="text-4xl font-bold text-indigo-600 mt-2">18</p>
                </div>
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl">
                    <!-- Heroicon: Building Office -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-indigo-600 dark:text-indigo-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 21v-9a1 1 0 011-1h3V4a1 1 0 011-1h8a1 1 0 011 1v7h3a1 1 0 011 1v9M3 21h18M9 21v-3a1 1 0 011-1h4a1 1 0 011 1v3" />
                    </svg>
                </div>
            </div>
            <button
                class="mt-6 inline-flex items-center justify-center gap-2 text-sm font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-2 transition-all">
                <!-- Heroicon: Eye -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 12c0 0 3.75-7.5 9.75-7.5S21.75 12 21.75 12s-3.75 7.5-9.75 7.5S2.25 12 2.25 12z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                View Branches
            </button>
        </div>

        <!-- Staffs Card -->
        <div
            class="relative overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition-all p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Total Staffs</h3>
                    <p class="text-4xl font-bold text-green-600 mt-2">245</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                    <!-- Heroicon: Users -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-green-600 dark:text-green-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 20h5v-2a3 3 0 00-3-3h-2m-6 5v-2a3 3 0 013-3h2m-6 5H4v-2a3 3 0 013-3h2m-6 5v-2a3 3 0 013-3h2M12 12a4 4 0 100-8 4 4 0 000 8z" />
                    </svg>
                </div>
            </div>
            <button
                class="mt-6 inline-flex items-center justify-center gap-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700 px-4 py-2 transition-all">
                <!-- Heroicon: User Group -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16 13a4 4 0 00-8 0m8 0a4 4 0 018 0m-8 0v5m-8-5v5M4 21h16" />
                </svg>
                Manage Staffs
            </button>
        </div>

        <!-- Inventory Card -->
        <div
            class="relative overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition-all p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Inventory Items</h3>
                    <p class="text-4xl font-bold text-amber-500 mt-2">1,892</p>
                </div>
                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl">
                    <!-- Heroicon: Cube -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-amber-500 dark:text-amber-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 8.25l-9-4.5-9 4.5m18 0L12 12m9-3.75v9l-9 4.5-9-4.5v-9M12 12l-9-3.75" />
                    </svg>
                </div>
            </div>
            <button
                class="mt-6 inline-flex items-center justify-center gap-2 text-sm font-medium rounded-md bg-amber-500 text-white hover:bg-amber-600 px-4 py-2 transition-all">
                <!-- Heroicon: Clipboard Document List -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h6m-6 4h6m-3-10h.01M5 7h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z" />
                </svg>
                Manage Inventory
            </button>
        </div>
    </div>

    <!-- Bottom Section -->
    <div
        class="relative h-full flex-1 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-semibold text-zinc-800 dark:text-zinc-100">Production Overview</h3>
            <button
                class="inline-flex items-center gap-2 text-sm font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-2 transition-all">
                <!-- Heroicon: Arrow Down Tray -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-9-9v9m0 0l3.75-3.75M12 16.5l-3.75-3.75" />
                </svg>
                Export Report
            </button>
        </div>
        <div class="h-[400px] rounded-lg bg-zinc-100 dark:bg-zinc-800/60 flex items-center justify-center">
            <p class="text-zinc-500 dark:text-zinc-400 text-sm">[Chart or production data placeholder]</p>
        </div>
    </div>
</div>

</x-layouts.app>
