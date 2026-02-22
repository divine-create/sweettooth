<div class="p-3 space-y-3">

    <x-breadcrumb
        title="Planning Reports"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Reports'],
            ['label' => 'Planning']
        ]"
        :compact="false"
        :with-icons="true"/>

    <!-- Report Type Tabs -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <nav class="flex">
                <button wire:click="switchReport('pipeline')"
                        class="flex-1 py-3 px-4 text-center text-sm font-medium border-b-2 transition-colors
                               {{ $activeReport === 'pipeline'
                                   ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                   : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Pipeline Status
                </button>
                <button wire:click="switchReport('capacity')"
                        class="flex-1 py-3 px-4 text-center text-sm font-medium border-b-2 transition-colors
                               {{ $activeReport === 'capacity'
                                   ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                   : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Capacity Planning
                </button>
            </nav>
        </div>
    </div>

    <!-- Report Content -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        @if($activeReport === 'pipeline')
            @livewire('branch-dashboard.production.reports.pipeline-status.index', ['b_id' => $b_id], key('pipeline'))
        @elseif($activeReport === 'capacity')
            @livewire('branch-dashboard.production.reports.capacity-planning.index', ['b_id' => $b_id], key('capacity'))
        @endif
    </div>

</div>