<div class="p-3 space-y-3">

    <x-breadcrumb
        title="Performance Reports"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Reports'],
            ['label' => 'Performance']
        ]"
        :compact="false"
        :with-icons="true"/>

    <!-- Report Type Tabs -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <nav class="flex flex-wrap">
                <button wire:click="switchReport('ingredient-utilization')"
                        class="flex-1 py-3 px-4 text-center text-sm font-medium border-b-2 transition-colors
                               {{ $activeReport === 'ingredient-utilization'
                                   ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                   : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    Ingredient Utilization
                </button>
                <button wire:click="switchReport('production-activities')"
                        class="flex-1 py-3 px-4 text-center text-sm font-medium border-b-2 transition-colors
                               {{ $activeReport === 'production-activities'
                                   ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                   : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18v6H3V3zm0 8h18v10H3V11zm4 2v6m4-6v6m4-6v6"/>
                    </svg>
                    Production Activities
                </button>
            </nav>
        </div>
    </div>

    <!-- Report Content -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        @if($activeReport === 'ingredient-utilization')
            @livewire('branch-dashboard.production.reports.ingredient-utilization.index', ['b_id' => $b_id], key('ingredient-utilization'))
        @elseif($activeReport === 'production-activities')
            @livewire('branch-dashboard.production.reports.production-activities.index', ['b_id' => $b_id], key('production-activities'))
        @endif
    </div>

</div>
