<div class="p-3 space-y-3">

    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <x-breadcrumb
            title="Operations Reports"
            :items="[
                ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
                ['label' => 'Production'],
                ['label' => 'Reports'],
                ['label' => 'Operations']
            ]"
            :compact="false"
            :with-icons="true"/>

        <a class="inline-flex items-center justify-center rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
           href="{{ branch_route('branch-dashboard.production.reporting.index', ['b_id' => $b_id]) }}">
            Reports Library
        </a>
    </div>

    <!-- Report Type Tabs -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <nav class="flex">
                <button wire:click="switchReport('efficiency')"
                        class="flex-1 py-3 px-4 text-center text-sm font-medium border-b-2 transition-colors
                               {{ $activeReport === 'efficiency'
                                   ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                   : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Efficiency
                </button>
                <button wire:click="switchReport('quality')"
                        class="flex-1 py-3 px-4 text-center text-sm font-medium border-b-2 transition-colors
                               {{ $activeReport === 'quality'
                                   ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                   : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Quality
                </button>
                <button wire:click="switchReport('waste')"
                        class="flex-1 py-3 px-4 text-center text-sm font-medium border-b-2 transition-colors
                               {{ $activeReport === 'waste'
                                   ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                   : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Waste
                </button>
                <button wire:click="switchReport('cost')"
                        class="flex-1 py-3 px-4 text-center text-sm font-medium border-b-2 transition-colors
                               {{ $activeReport === 'cost'
                                   ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                   : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                    Cost
                </button>
            </nav>
        </div>
    </div>

    <!-- Report Content -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        @if($activeReport === 'efficiency')
            @livewire('branch-dashboard.production.reports.production-efficiency.index', ['b_id' => $b_id], key('efficiency'))
        @elseif($activeReport === 'quality')
            @livewire('branch-dashboard.production.reports.quality-metrics.index', ['b_id' => $b_id], key('quality'))
        @elseif($activeReport === 'waste')
            @livewire('branch-dashboard.production.reports.waste-analysis.index', ['b_id' => $b_id], key('waste'))
        @elseif($activeReport === 'cost')
            @livewire('branch-dashboard.production.reports.cost-analysis.index', ['b_id' => $b_id], key('cost'))
        @endif
    </div>

</div>
