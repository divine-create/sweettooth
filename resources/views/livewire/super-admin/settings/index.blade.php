<div class="">
    <style>
        .active-tab {
            background-color: rgb(59 130 246);
            color: white;
        }
        .dark .active-tab {
            background-color: rgb(37 99 235);
        }
        .setting-group {
            border-left: 3px solid rgb(59 130 246);
        }
        .dark .setting-group {
            border-left-color: rgb(96 165 250);
        }
    </style>
</head>
<body class="h-full bg-zinc-100 dark:bg-zinc-900 transition-colors duration-300">
  
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-800 shadow rounded-lg overflow-hidden transition-colors duration-300">
                <div class="flex flex-col md:flex-row">
                    <!-- Vertical Tabs -->
                    <div class="w-full md:w-1/4 bg-zinc-50 dark:bg-zinc-700 p-4 border-r border-zinc-200 dark:border-zinc-600 transition-colors duration-300 sticky">
                        <nav class="space-y-1">
                            @foreach($tabs as $tab)
                                <button
                                    wire:click="setActiveTab('{{ $tab['id'] }}')"
                                    class="w-full text-left px-4 py-2 rounded-md text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-blue-100 dark:hover:bg-blue-900 transition-colors duration-200 mb-1 {{ $activeTab === $tab['id'] ? 'active-tab' : '' }}"
                                >
                                    {{ $tab['name'] }}
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="w-full md:w-3/4 p-6">
                        @if($activeTab === 'business-config')
                            @livewire('super-admin.settings.business-configuration')
                        @elseif($activeTab === 'currency-localization')
                            @livewire('super-admin.settings.currency-localization')
                        @elseif($activeTab === 'branch-management')
                            @livewire('super-admin.settings.branch-management')
                        @elseif($activeTab === 'inventory-management')
                            @livewire('super-admin.settings.inventory-management')
                        @elseif($activeTab === 'employee-management')
                            @livewire('super-admin.settings.employee-management')
                        @elseif($activeTab === 'pos-configuration')
                            @livewire('super-admin.settings.pos-configuration')
                        @elseif($activeTab === 'accounting-cash')
                            @livewire('super-admin.settings.accounting-cash')
                        @elseif($activeTab === 'customer-supplier')
                            @livewire('super-admin.settings.customer-supplier')
                        @elseif($activeTab === 'reports-analytics')
                            @livewire('super-admin.settings.reports-analytics')
                        @elseif($activeTab === 'security-access')
                            @livewire('super-admin.settings.security-access')
                        @elseif($activeTab === 'notifications-alerts')
                            @livewire('super-admin.settings.notifications-alerts')
                        @endif
                    </div>
                </div>
            </div>
        </main>
    </div>

</div>
   