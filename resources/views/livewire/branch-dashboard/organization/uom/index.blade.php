<div class="space-y-6 p-3">
    <x-breadcrumb
        title="UOM Management"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Organization'],
            ['label' => 'UOM Management']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-xl border border-blue-200/70 dark:border-blue-900 bg-gradient-to-br from-blue-50 to-white dark:from-blue-900/30 dark:to-zinc-900 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-blue-700 dark:text-blue-300">Units</p>
            <p class="mt-2 text-3xl font-semibold text-blue-900 dark:text-blue-100">{{ number_format($unitsCount) }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200/70 dark:border-emerald-900 bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-900/30 dark:to-zinc-900 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Categories</p>
            <p class="mt-2 text-3xl font-semibold text-emerald-900 dark:text-emerald-100">{{ number_format($categoriesCount) }}</p>
        </div>
        <div class="rounded-xl border border-indigo-200/70 dark:border-indigo-900 bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-900/30 dark:to-zinc-900 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-indigo-700 dark:text-indigo-300">Active Conversions</p>
            <p class="mt-2 text-3xl font-semibold text-indigo-900 dark:text-indigo-100">{{ number_format($activeConversionsCount) }}</p>
        </div>
        <div class="rounded-xl border border-amber-200/70 dark:border-amber-900 bg-gradient-to-br from-amber-50 to-white dark:from-amber-900/30 dark:to-zinc-900 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-amber-700 dark:text-amber-300">Scoped Rules</p>
            <p class="mt-2 text-3xl font-semibold text-amber-900 dark:text-amber-100">{{ number_format($scopedConversionsCount) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <a href="{{ branch_route('branch-dashboard.organization.uom.units') }}" wire:navigate
            class="group rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Units Catalog</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                        Browse all unit definitions, categories, symbols, and status.
                    </p>
                </div>
                <svg class="w-8 h-8 text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-200 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h4m0 0v4m0-4l-9 9m-4-4v5h5"></path>
                </svg>
            </div>
        </a>

        <a href="{{ branch_route('branch-dashboard.organization.uom.conversions') }}" wire:navigate
            class="group rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Conversion Rules</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                        Review global, branch, item, and product conversion mappings.
                    </p>
                </div>
                <svg class="w-8 h-8 text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-200 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h11M9 21V3m12 11H10m11 0l-3-3m3 3l-3 3"></path>
                </svg>
            </div>
        </a>
    </div>

    <div class="flex justify-end">
        <a href="{{ branch_route('branch-dashboard.organization.uom.manage') }}" wire:navigate
            class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors">
            Create / Edit Conversion Rules
        </a>
    </div>
</div>
