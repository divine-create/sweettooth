<div class="p-3 space-y-3">

<style>
.scrollbar-thin::-webkit-scrollbar {
    width: 8px;
    }
        .scrollbar-thin::-webkit-scrollbar-track {
        background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    @apply bg-zinc-300 dark:bg-zinc-700 rounded-full;
    }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        @apply bg-zinc-400 dark:bg-zinc-600;
}
[x-cloak] {
    display: none !important;
    }
    </style>

<x-breadcrumb
        title="Purchases Management"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Inventory'],
            ['label' => 'Purchases']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Header with Action Buttons -->
    <div class="flex justify-end gap-2 mb-3">
        <button wire:click="exportCSV" 
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export CSV
        </button>
        <button x-data="{ loadingNew: false }"
            @click="loadingNew = true; $wire.openCreateModal().finally(() => { loadingNew = false })"
            :disabled="loadingNew"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
            <svg x-show="!loadingNew" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <svg x-show="loadingNew" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            New Purchase
        </button>
</div>

    <!-- Filters -->
    <div x-data="{ open: false }"
        class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 transition-all duration-300">
        <div class="flex justify-between items-center px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
        <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 flex items-center">
            <svg class="w-4 h-4 mr-1.5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14.293 13H10v5l-4-4v-3.586L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
            Filters
        </h2>

        <button @click="open = !open"
        class="flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
        <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span x-text="open ? 'Close' : 'Show Filters'"></span>
        </button>
        </div>

        <div x-show="open" x-collapse class="p-3 space-y-3">
        <!-- Advanced Search -->
        <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Advanced Search</label>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by purchase number, supplier..."
        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Purchase Status</label>
            <select wire:model.live="filterStatus"
                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                <option value="">All</option>
                <option value="draft">Draft</option>
                <option value="pending_approval">Pending Approval</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Payment Status</label>
            <select wire:model.live="filterPaymentStatus"
                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                <option value="">All</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
                <option value="pending">Pending</option>
            </select>
        </div>
            </div>

            <div class="flex flex-wrap gap-2 justify-end pt-2.5 border-t border-zinc-200 dark:border-zinc-700">
                <button wire:click="resetFilters"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-sm p-3">
            <p class="text-xs opacity-90">Total Purchases</p>
            <p class="text-2xl font-bold">{{ $summary['total_purchases'] }}</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow-sm p-3 border border-green-200 dark:border-green-700">
            <p class="text-xs text-gray-600 dark:text-gray-400">Total Cost</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-500">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['total_cost'] ?? 0) }}
            </p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow-sm p-3 border border-blue-200 dark:border-blue-700">
            <p class="text-xs text-gray-600 dark:text-gray-400">Paid</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-500">{{ $summary['paid_count'] }}</p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg shadow-sm p-3 border border-yellow-200 dark:border-yellow-700">
            <p class="text-xs text-gray-600 dark:text-gray-400">Partial</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-500">{{ $summary['partial_count'] }}</p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg shadow-sm p-3 border border-red-200 dark:border-red-700">
            <p class="text-xs text-gray-600 dark:text-gray-400">Pending</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-500">{{ $summary['pending_count'] }}</p>
        </div>
    </div>

    <!-- View Mode Tabs -->
    <div x-data="{ viewMode: 'table' }" x-cloak>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-2 mb-4">
            <div class="flex flex-wrap gap-1 bg-zinc-100 dark:bg-zinc-700 p-1 rounded-lg">
                <button @click="viewMode = 'table'"
                    :class="viewMode === 'table' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'"
                    class="px-3 py-2 text-xs font-medium rounded transition-all whitespace-nowrap">
                    📋 Table
                </button>
                <button @click="viewMode = 'timeline'"
                    :class="viewMode === 'timeline' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'"
                    class="px-3 py-2 text-xs font-medium rounded transition-all whitespace-nowrap">
                    📅 Timeline
                </button>
                <button @click="viewMode = 'stats'"
                    :class="viewMode === 'stats' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'"
                    class="px-3 py-2 text-xs font-medium rounded transition-all whitespace-nowrap">
                    📊 Analysis
                </button>
            </div>
        </div>

    <!-- TABLE VIEW -->
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
    <div class="px-3 py-2 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-center">
    <h3 class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Purchases List</h3>
             <button wire:click="openCreateModal"
                 class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-xs font-medium">
                 New Purchase
             </button>
         </div>
         <div class="overflow-x-auto">
             <table class="min-w-full divide-y divide-gray-200">
                 <thead class="bg-gray-50 dark:bg-zinc-700">
                     <tr>
                         <th wire:click="sortByColumn('purchase_number')" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-600">Purchase #</th>
                         <th wire:click="sortByColumn('purchase_date')" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-600">Date</th>
                         <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Supplier</th>
                         <th wire:click="sortByColumn('landing_cost')" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-600">Total Cost</th>
                         <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Payment</th>
                         <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                         <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Items</th>
                         <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                     </tr>
                 </thead>
                 <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                     @forelse ($purchases as $purchase)
                         <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                             <td class="px-3 py-2 text-xs font-medium text-gray-900 dark:text-zinc-100">{{ $purchase->purchase_number }}</td>
                             <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400">{{ $purchase->purchase_date->format('d M Y') }}</td>
                             <td class="px-3 py-2 text-xs text-gray-900 dark:text-zinc-100">{{ $purchase->supplier_name }}</td>
                             <td class="px-3 py-2 text-xs font-medium text-gray-900 dark:text-zinc-100">
                                 {{ \App\Helpers\LocalizationHelper::formatCurrency($purchase->landing_cost ?? 0) }}
                             </td>
                             <td class="px-3 py-2 text-xs">
                                 <div class="flex items-center gap-2">
                                     <select wire:change="updatePaymentStatus({{ $purchase->id }}, $event.target.value)"
                                         class="px-2 py-1 text-xs rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                                         <option value="paid" {{ $purchase->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                                         <option value="partial" {{ $purchase->payment_status === 'partial' ? 'selected' : '' }}>Partial</option>
                                         <option value="pending" {{ $purchase->payment_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                     </select>
                                     <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                     {{ $purchase->payment_status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                     {{ $purchase->payment_status === 'partial' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                     {{ $purchase->payment_status === 'pending' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                                         {{ ucfirst($purchase->payment_status) }}
                                     </span>
                                 </div>
                             </td>
                             <td class="px-3 py-2 text-xs">
                                 <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                 {{ $purchase->status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' : '' }}
                                 {{ $purchase->status === 'pending_approval' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                 {{ $purchase->status === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                 {{ $purchase->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}">
                                     {{ ucfirst(str_replace('_', ' ', $purchase->status)) }}
                                 </span>
                             </td>
                             <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400">{{ $purchase->purchaseItems->count() }}</td>
                             <td class="px-3 py-2 text-xs flex gap-2">
                                 <button wire:click="viewDetail({{ $purchase->id }})" class="text-green-600 hover:text-green-800 dark:text-green-400">View</button>
                                 @if($purchase->status === 'draft')
                                     <button wire:click="requestPurchaseApproval({{ $purchase->id }})" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">Request Approval</button>
                                     <button wire:click="delete({{ $purchase->id }})" onclick="return confirm('Delete this purchase?')" class="text-red-600 hover:text-red-800 dark:text-red-400">Delete</button>
                                 @endif
                             </td>
                         </tr>
                     @empty
                         <tr>
                             <td colspan="8" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No purchases found.</td>
                         </tr>
                     @endforelse
                 </tbody>
             </table>
         </div>
         <div class="px-3 py-2 border-t border-gray-200 dark:border-zinc-700">
             {{ $purchases->links() }}
         </div>
     </div>


    <!-- TIMELINE VIEW -->
    <div x-show="viewMode === 'timeline'" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-6">Purchase Timeline</h3>
        <div class="space-y-4">
            @forelse($purchases as $purchase)
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="w-4 h-4 rounded-full border-2 border-blue-600 dark:border-blue-400 bg-white dark:bg-zinc-800"></div>
                        @if(!$loop->last)
                            <div class="w-1 h-12 bg-zinc-200 dark:bg-zinc-700"></div>
                        @endif
                    </div>
                    <div class="flex-1 pb-4 cursor-pointer" wire:click="viewDetail({{ $purchase->id }})">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h4 class="font-medium text-zinc-900 dark:text-zinc-100">{{ $purchase->purchase_number }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $purchase->purchase_date->format('d M Y H:i') }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded {{ $purchase->payment_status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($purchase->payment_status === 'partial' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400') }}">
                                {{ ucfirst($purchase->payment_status) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $purchase->supplier_name }}</p>
                        <p class="text-sm font-bold text-blue-600 dark:text-blue-400">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($purchase->landing_cost ?? 0) }}
                            • {{ $purchase->purchaseItems->count() }} items
                        </p>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">No purchases found</p>
                </div>
            @endforelse
        </div>
        <div class="mt-4">{{ $purchases->links() }}</div>
    </div>

    <!-- ANALYSIS/STATS VIEW -->
    <div x-show="viewMode === 'stats'" class="space-y-4">
        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Purchase Analysis</h3>
        
        <!-- By Payment Status -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h4 class="font-semibold text-zinc-800 dark:text-zinc-100 mb-4">By Payment Status</h4>
            <div class="space-y-3">
                @foreach($purchasesByStatus as $statusData)
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ ucfirst($statusData['status']) }}</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $statusData['count'] }} purchases</p>
                            </div>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($statusData['total_cost'] ?? 0) }}
                            </span>
                        </div>
                        @php
                            $percentage = ($statusData['total_cost'] / ($summary['total_cost'] ?: 1)) * 100;
                        @endphp
                        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                            <div class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    </div>

    <!-- Create Modal -->
    <div x-data="{ show: @entangle('showModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-hidden"
        @keydown.escape.window="show = false">
        <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50"
            @click="$wire.closeModal()">
        </div>

        <div x-show="show" x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-full md:w-2/3 lg:w-3/4 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">New Purchase</h2>
                <button wire:click="closeModal"
                    class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin" id="purchaseFormContainer"
                >
                <form id="purchaseForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Purchase Date *</label>
                            <input type="date" wire:model="purchase_date"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                            @error('purchase_date')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div x-data="{ open: false }" class="relative">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Supplier Name *</label>
                            @if($supplierId)
                                <div class="flex items-center gap-2">
                                    <input type="text" wire:model="supplier_name" readonly
                                        class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                                    <button type="button" wire:click="clearSupplier"
                                        class="px-3 py-2 text-sm text-red-600 hover:text-red-800 dark:text-red-400 border border-red-300 dark:border-red-600 rounded-lg">
                                        Clear
                                    </button>
                                </div>
                            @else
                                <input type="text" wire:model.live="supplierSearch"
                                    @focus="open = true" @click.outside="open = false"
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Search registered suppliers...">
                                <div x-show="open && {{ count($suppliersList) }} > 0" x-cloak
                                    class="absolute z-20 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                    @forelse($suppliersList as $s)
                                        <button type="button"
                                            wire:click="selectSupplier({{ $s->id }})"
                                            @click="open = false"
                                            class="w-full px-4 py-2 text-left hover:bg-zinc-100 dark:hover:bg-zinc-700 text-sm text-zinc-900 dark:text-zinc-100">
                                            <span class="font-medium">{{ $s->name }}</span>
                                            <span class="text-zinc-500 dark:text-zinc-400 text-xs ml-2">{{ $s->code }}</span>
                                        </button>
                                    @empty
                                        <div class="px-4 py-2 text-sm text-zinc-500 dark:text-zinc-400">No suppliers found</div>
                                    @endforelse
                                </div>
                                {{-- manual fallback when no supplier selected --}}
                                @if(!$supplierId)
                                    <input type="text" wire:model="supplier_name"
                                        class="w-full mt-2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Or type supplier name manually">
                                @endif
                            @endif
                            @error('supplier_name')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Supplier Contact</label>
                            <input type="text" wire:model="supplier_contact"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter contact">
                            @error('supplier_contact')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Other Costs</label>
                            <input type="number" step="0.01" wire:model="other_costs"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                            @error('other_costs')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Payment Status *</label>
                            <select wire:model="payment_status"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                                <option value="pending">Pending</option>
                                <option value="partial">Partial</option>
                                <option value="paid">Paid</option>
                            </select>
                            @error('payment_status')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Notes</label>
                        <textarea wire:model="notes" rows="3"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter notes"></textarea>
                        @error('notes')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Purchase Items</h3>
                            <button type="button" x-data="{ loadingAdd: false }"
                                @click="loadingAdd = true; $wire.addPurchaseItem().finally(() => { loadingAdd = false })"
                                :disabled="loadingAdd"
                                class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">
                                <span x-show="!loadingAdd">Add Item</span>
                                <span x-show="loadingAdd" class="flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Adding...
                                </span>
                            </button>
                        </div>
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-zinc-50 dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Item</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Quantity</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Unit Price</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Expiry Date</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Batch No.</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($purchaseItems as $index => $item)
                                    <tr class="border-t border-zinc-200 dark:border-zinc-700" wire:key="purchase-item-{{ $item['id'] ?? $index }}">
                                        <td class="px-4 py-2">
                                            <select wire:key="select-item-{{ $item['id'] ?? $index }}" wire:model.live="purchaseItems.{{ $index }}.item_id"
                                                wire:change="updateItemUom({{ $index }}, $event.target.value)"
                                                class="w-full min-w-[26rem] px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                                                <option value="">Select Item</option>
                                                 @foreach($items as $availableItem)
                                                     <option value="{{ $availableItem->id }}">{{ $availableItem->name }} ({{ $availableItem->uomSymbol }})</option>
                                                 @endforeach
                                            </select>
                                            @error('purchaseItems.'.$index.'.item_id')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                            @error('purchaseItems.'.$index.'.uom')
                                                <span class="block text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" step="0.5" min="1" wire:model.live="purchaseItems.{{ $index }}.quantity"
                                                class="w-24 px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                                            @error('purchaseItems.'.$index.'.quantity')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" step="0.01" wire:model.live="purchaseItems.{{ $index }}.unit_price"
                                                class="w-32 px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                                                placeholder="0.00">
                                            @error('purchaseItems.'.$index.'.unit_price')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="date" wire:model="purchaseItems.{{ $index }}.expiry_date"
                                                class="w-36 px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-amber-500">
                                            @error('purchaseItems.'.$index.'.expiry_date')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="inline-block px-2 py-1 text-xs font-mono bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 rounded border border-zinc-200 dark:border-zinc-600 whitespace-nowrap">
                                                {{ $purchaseItems[$index]['batch_number'] ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <button type="button" wire:click="removePurchaseItem({{ $index }})"
                                                class="p-1 text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No items added. Click "Add Item" to begin.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg px-4 py-2 text-sm font-semibold text-blue-900 dark:text-blue-100">
                                @php $total = collect($purchaseItems)->sum(fn($item) => floatval($item['quantity'] ?? 0) * floatval($item['unit_price'] ?? 0)); @endphp
                                Total: {{ number_format($total, 2) }}
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end space-x-3">
                <button type="button" wire:click="closeModal"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Cancel
                </button>
                <button type="button" wire:click="save" wire:loading.attr="disabled"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                    <span wire:loading wire:target="save" class="mr-2">
                        <svg class="inline-block w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="save">Save Purchase</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Request Approval Modal -->
    <div x-data="{ show: @entangle('showRequestModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-hidden"
        @keydown.escape.window="show = false">
        <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50"
            @click="$wire.closeRequestModal()">
        </div>

        <div x-show="show" x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-full md:w-2/3 lg:w-1/2 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Request Purchase Approval</h2>
                <button wire:click="closeRequestModal"
                    class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                @if($pendingPurchaseData)
                <div class="space-y-4">
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <h3 class="font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Purchase Summary</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Purchase #:</span>
                                <span class="text-zinc-900 dark:text-zinc-100 font-mono">{{ $pendingPurchaseData['purchase_number'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Supplier:</span>
                                <span class="text-zinc-900 dark:text-zinc-100">{{ $pendingPurchaseData['supplier_name'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Total FOB (NGN):</span>
                                <span class="text-zinc-900 dark:text-zinc-100">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($pendingPurchaseData['total_fob_ngn'] ?? 0) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Landing Cost:</span>
                                <span class="text-zinc-900 dark:text-zinc-100 font-semibold">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($pendingPurchaseData['landing_cost'] ?? 0) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Items:</span>
                                <span class="text-zinc-900 dark:text-zinc-100">{{ count($pendingPurchaseData['purchaseItems'] ?? []) }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Notes (Optional)</label>
                        <textarea wire:model="requestNotes" rows="4"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                            placeholder="Add any notes for the auditor (e.g., reason for purchase)"></textarea>
                        @error('requestNotes')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                This purchase will be sent to the auditor for approval. Stock and inventory will be updated once approved.
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end space-x-3">
                <button type="button" wire:click="closeRequestModal"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Cancel
                </button>
                <button type="button" wire:click="submitPurchaseApprovalRequest" wire:loading.attr="disabled"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                    <span wire:loading wire:target="submitPurchaseApprovalRequest" class="mr-2">
                        <svg class="inline-block w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="submitPurchaseApprovalRequest">Request Approval</span>
                    <span wire:loading wire:target="submitPurchaseApprovalRequest">Submitting...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div x-data="{ show: @entangle('showDetailModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-hidden"
        @keydown.escape.window="show = false">
        <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50"
            @click="$wire.closeDetailModal()">
        </div>

        <div x-show="show" x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-full md:w-2/3 lg:w-1/2 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Purchase Details</h2>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="window.print()"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-1">
                        Print
                    </button>
                    <button wire:click="closeDetailModal"
                        class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin">
                @if($detailPurchase)
                @php
                    $statusColors = [
                        'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                        'pending_approval' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                    ];
                    $statusLabel = [
                        'draft' => 'Draft',
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ];
                @endphp
                <div class="space-y-6">
                    <!-- Header Info -->
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">Purchase Number</p>
                                <p class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">{{ $detailPurchase->purchase_number }}</p>
                            </div>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $statusColors[$detailPurchase->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabel[$detailPurchase->status] ?? ucfirst($detailPurchase->status) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">Purchase Date</p>
                                <p class="text-zinc-900 dark:text-zinc-100">{{ $detailPurchase->purchase_date->format('M d, Y') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">Supplier</p>
                                <p class="text-zinc-900 dark:text-zinc-100">{{ $detailPurchase->supplier_name }}</p>
                            </div>
                            @if($detailPurchase->supplier_contact)
                            <div>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">Contact</p>
                                <p class="text-zinc-900 dark:text-zinc-100">{{ $detailPurchase->supplier_contact }}</p>
                            </div>
                            @endif
                            <div>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">Recorded By</p>
                                <p class="text-zinc-900 dark:text-zinc-100">{{ $detailPurchase->recorder->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Purchase Items ({{ $detailPurchase->purchaseItems->count() }})</h3>
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-zinc-100 dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300">Item</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300">Qty</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300">UOM</th>
                                        <th class="px-4 py-2 text-right text-xs font-semibold text-zinc-700 dark:text-zinc-300">Unit Price</th>
                                        <th class="px-4 py-2 text-right text-xs font-semibold text-zinc-700 dark:text-zinc-300">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @forelse($detailPurchase->purchaseItems as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-zinc-900 dark:text-zinc-100">
                                            <p class="font-medium">{{ $item->item->name ?? 'N/A' }}</p>
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400">SKU: {{ $item->item->sku ?? 'N/A' }}</p>
                                        </td>
                                        <td class="px-4 py-2 text-zinc-900 dark:text-zinc-100">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="px-4 py-2 text-zinc-900 dark:text-zinc-100">{{ $item->uom }}</td>
                                        <td class="px-4 py-2 text-right text-zinc-900 dark:text-zinc-100">
                                            {{ \App\Helpers\LocalizationHelper::formatCurrency($item->unit_fob_ngn ?? $item->cost_per_unit ?? 0) }}
                                        </td>
                                        <td class="px-4 py-2 text-right font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ \App\Helpers\LocalizationHelper::formatCurrency($item->total_cost ?? 0) }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500">No items in this purchase</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">Items Subtotal:</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($detailPurchase->purchaseItems->sum('total_cost') ?? 0) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">Other Costs:</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($detailPurchase->other_costs ?? 0) }}
                            </span>
                        </div>
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2 mt-2 flex justify-between text-base font-bold">
                            <span class="text-zinc-900 dark:text-zinc-100">Total Cost:</span>
                            <span class="text-zinc-900 dark:text-zinc-100">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($detailPurchase->landing_cost ?? 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-3">
                        <div>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Payment Status</p>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                @if($detailPurchase->payment_status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($detailPurchase->payment_status === 'partial') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @endif">
                                {{ ucfirst($detailPurchase->payment_status) }}
                            </span>
                        </div>
                        @if($detailPurchase->notes)
                        <div>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Notes</p>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100">{{ $detailPurchase->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end">
                <button type="button" wire:click="closeDetailModal"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
