<div
    x-data="{
        productView: 'grid',
        showReceipt: false,
        receiptContent: ''
    }"
    @pos-receipt-ready.window="showReceipt = true"
    @keydown.window.prevent.f1="$wire.clearCart()"
    @keydown.window.prevent.f2="$wire.holdSale()"
    @keydown.window.prevent.f3="alert('Sales history feature - integrate with reports')"
    @keydown.window.prevent.f9="$wire.completeSale()"
    class="p-4 space-y-4">

    <!-- Branch & Department Context Bar -->
    <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30 p-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                        <path d="M11.584 2.376a.75.75 0 0 1 .832 0l9 6a.75.75 0 1 1-.832 1.248L12 3.901 3.416 9.624a.75.75 0 0 1-.832-1.248l9-6Z" />
                        <path fill-rule="evenodd" d="M20.25 10.332v9.918H21a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1 0-1.5h.75v-9.918a.75.75 0 0 1 .634-.74A49.109 49.109 0 0 1 12 9c2.59 0 5.134.202 7.616.592a.75.75 0 0 1 .634.74Zm-7.5 2.418a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Zm3-.75a.75.75 0 0 1 .75.75v6.75a.75.75 0 0 1-1.5 0v-6.75a.75.75 0 0 1 .75-.75ZM9 12.75a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Z" clip-rule="evenodd" />
                        <path d="M12 7.875a1.125 1.125 0 1 0 0-2.25 1.125 1.125 0 0 0 0 2.25Z" />
                    </svg>
                </div>
                <div>
                    <div class="font-semibold text-blue-900 dark:text-blue-100">
                        {{ $branchName }}
                        @if($departmentName)
                            <span class="text-blue-700 dark:text-blue-300">/ {{ $departmentName }}</span>
                        @endif
                    </div>
                    <div class="text-xs text-blue-700 dark:text-blue-300">
                        Point of Sale System • All Branch Products
                        @if($departmentId)
                            • Sales tracked to {{ $departmentName }}
                        @endif
                    </div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">Branch ID</div>
                <div class="text-sm font-mono text-blue-900 dark:text-blue-100">{{ $branchId ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Shift Status Bar -->
    @if(!$this->hasActiveShift())
        <div class="rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/30 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-red-600 text-white flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd"/></svg>
                    </div>
                    <div>
                        <div class="font-semibold text-red-900 dark:text-red-100">No Active Shift</div>
                        <div class="text-sm text-red-700 dark:text-red-300">Please start a shift to begin making sales</div>
                    </div>
                </div>
                {{-- <a href="{{ route('branch-dashboard.sales-dashboard.shift-management.index', ['b_id' => request('b_id')]) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-500 font-medium shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd"/></svg>
                    Start Shift
                </a> --}}
            </div>
        </div>
    @else
        <div class="rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/30 p-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd"/></svg>
                    </div>
                    <div>
                        <div class="font-semibold text-emerald-900 dark:text-emerald-100">Active Shift</div>
                        <div class="text-xs text-emerald-700 dark:text-emerald-300">
                            Shift #{{ $this->activeShift->shift_number ?? 'N/A' }} • Started {{ $this->activeShift->clock_in?->format('h:i A') ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Dispatch Verification Bar -->
    @if($this->pendingDispatches > 0)
    <div class="rounded-xl border border-amber-300 dark:border-amber-800 bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-950/30 dark:to-yellow-950/30 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-amber-600 text-white flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path fill-rule="evenodd" d="M5.25 9a6.75 6.75 0 0113.5 0v.75c0 2.123.8 4.057 2.118 5.52a.75.75 0 01-.297 1.206c-1.544.57-3.16.99-4.831 1.243a3.75 3.75 0 11-7.48 0 24.585 24.585 0 01-4.831-1.244.75.75 0 01-.298-1.205A8.217 8.217 0 005.25 9.75V9zm4.502 8.9a2.25 2.25 0 104.496 0 25.057 25.057 0 01-4.496 0z" clip-rule="evenodd"/></svg>
                </div>
                <div>
                    <div class="font-semibold text-zinc-900 dark:text-zinc-100">Pending Dispatch Verification</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $this->pendingDispatches }} dispatch{{ $this->pendingDispatches > 1 ? 'es' : '' }} awaiting receiving for this sales department
                    </div>
                </div>
            </div>
            <a href="{{ branch_route('branch-dashboard.sales-dashboard.dispatches.index', ['salesDeptSlug' => $salesDeptSlug, 'sales_dept_slug' => $salesDeptSlug, 'b_id' => $branchId, 'page' => 'Production Dispatches' . '_' . $salesDeptSlug]) }}"
                wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-500 font-medium shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 100 13.5 6.75 6.75 0 000-13.5zM2.25 10.5a8.25 8.25 0 1114.59 5.28l4.69 4.69a.75.75 0 11-1.06 1.06l-4.69-4.69A8.25 8.25 0 012.25 10.5z" clip-rule="evenodd"/></svg>
                Open Receiving
            </a>
        </div>
    </div>
    @endif

    <!-- Table Management Toggle & Section -->
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-zinc-600 dark:text-zinc-400">
                    <path fill-rule="evenodd" d="M1.5 5.625c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v12.75c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 18.375V5.625ZM21 9.375A.375.375 0 0 0 20.625 9h-7.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375h7.5a.375.375 0 0 0 .375-.375v-1.5Zm0 3.75a.375.375 0 0 0-.375-.375h-7.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375h7.5a.375.375 0 0 0 .375-.375v-1.5Zm0 3.75a.375.375 0 0 0-.375-.375h-7.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375h7.5a.375.375 0 0 0 .375-.375v-1.5ZM10.875 18.75a.375.375 0 0 0 .375-.375v-1.5a.375.375 0 0 0-.375-.375h-7.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375h7.5ZM3.375 15h7.5a.375.375 0 0 0 .375-.375v-1.5a.375.375 0 0 0-.375-.375h-7.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375Zm0-3.75h7.5a.375.375 0 0 0 .375-.375v-1.5A.375.375 0 0 0 10.875 9h-7.5A.375.375 0 0 0 3 9.375v1.5c0 .207.168.375.375.375Z" clip-rule="evenodd" />
                </svg>
                <div>
                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Table Management</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        @if($showTableManagement)
                            Manage customer tables and tabs
                        @else
                            Enable to manage customer tables
                        @endif
                    </p>
                </div>
                @if($selectedTableId)
                    @php
                        $selectedTable = $this->tables->firstWhere('id', $selectedTableId);
                    @endphp
                    @if($selectedTable)
                        <span class="px-2 py-1 text-xs rounded-md bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">
                            {{ $selectedTable->table_name }} Selected
                        </span>
                    @endif
                @endif
            </div>
            <div class="flex items-center gap-2">
                <!-- Toggle Switch -->
                <button
                    type="button"
                    wire:click="toggleTableManagement"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2
                        {{ $showTableManagement ? 'bg-emerald-600' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                        {{ $showTableManagement ? 'translate-x-6' : 'translate-x-1' }}"></span>
                </button>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ $showTableManagement ? 'ON' : 'OFF' }}
                </span>
            </div>
        </div>

        @if($showTableManagement)
            <div class="border-t border-zinc-200 dark:border-zinc-800 pt-4 mt-1">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        @if($this->tables->count() > 0)
                            {{ $this->tables->count() }} Table(s)
                        @else
                            No tables yet
                        @endif
                    </h4>
                    <button type="button" wire:click="$set('showTableModal', true)" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-md bg-emerald-600 text-white hover:bg-emerald-500">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                            <path fill-rule="evenodd" d="M12 4.5a.75.75 0 0 1 .75.75v6h6a.75.75 0 0 1 0 1.5h-6v6a.75.75 0 0 1-1.5 0v-6h-6a.75.75 0 0 1 0-1.5h6v-6A.75.75 0 0 1 12 4.5Z" clip-rule="evenodd" />
                        </svg>
                        Add Table
                    </button>
                </div>

            @if($this->tables->count() > 0)
            <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2">
                @foreach($this->tables as $table)
                    @php
                        $hasOrder = $table->hasActiveSale();
                        $isSelected = $selectedTableId === $table->id;
                        $orderTotal = $hasOrder ? $table->getTotalAmount() : 0;
                    @endphp
                    <button
                        type="button"
                        wire:click="selectTable({{ $table->id }})"
                        class="group relative rounded-lg border-2 p-3 transition-all duration-200 hover:shadow-md
                            @if($isSelected)
                                border-blue-500 bg-blue-50 dark:bg-blue-950/30
                            @elseif($hasOrder)
                                border-orange-300 dark:border-orange-700 bg-orange-50 dark:bg-orange-950/20
                            @else
                                border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 hover:border-emerald-300 dark:hover:border-emerald-700
                            @endif
                        ">
                        <div class="flex flex-col items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8
                                @if($isSelected)
                                    text-blue-600 dark:text-blue-400
                                @elseif($hasOrder)
                                    text-orange-600 dark:text-orange-400
                                @else
                                    text-zinc-400 dark:text-zinc-500 group-hover:text-emerald-600 dark:group-hover:text-emerald-400
                                @endif
                            ">
                                <path d="M11.584 2.376a.75.75 0 0 1 .832 0l9 6a.75.75 0 1 1-.832 1.248L12 3.901 3.416 9.624a.75.75 0 0 1-.832-1.248l9-6Z" />
                                <path fill-rule="evenodd" d="M20.25 10.332v9.918H21a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1 0-1.5h.75v-9.918a.75.75 0 0 1 .634-.74A49.109 49.109 0 0 1 12 9c2.59 0 5.134.202 7.616.592a.75.75 0 0 1 .634.74Zm-7.5 2.418a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Zm3-.75a.75.75 0 0 1 .75.75v6.75a.75.75 0 0 1-1.5 0v-6.75a.75.75 0 0 1 .75-.75ZM9 12.75a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Z" clip-rule="evenodd" />
                                <path d="M12 7.875a1.125 1.125 0 1 0 0-2.25 1.125 1.125 0 0 0 0 2.25Z" />
                            </svg>
                            <div class="text-xs font-semibold
                                @if($isSelected)
                                    text-blue-900 dark:text-blue-100
                                @elseif($hasOrder)
                                    text-orange-900 dark:text-orange-100
                                @else
                                    text-zinc-700 dark:text-zinc-300
                                @endif
                            ">{{ $table->table_number }}</div>
                            @if($hasOrder)
                                <div class="text-[10px] font-medium text-orange-700 dark:text-orange-400">
                                    {{ $this->formatCurrency($orderTotal) }}
                                </div>
                            @endif
                        </div>

                        <!-- Delete button on hover -->
                        @if(!$hasOrder)
                            <button
                                type="button"
                                wire:click.stop="deleteTable({{ $table->id }})"
                                class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-500 text-white opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3">
                                    <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endif
                    </button>
                @endforeach
            </div>

            @if($selectedTableId)
                <div class="mt-3 flex items-center justify-between gap-2 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                    <button
                        type="button"
                        wire:click="saveTableTab"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                            <path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0 1 11.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 0 1-1.085.67L12 18.089l-7.165 3.583A.75.75 0 0 1 3.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93Z" clip-rule="evenodd" />
                        </svg>
                        Save Tab
                    </button>
                    <button
                        type="button"
                        wire:click="clearTable"
                        class="inline-flex items-center justify-center gap-2 px-3 py-2 text-sm rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                            <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
                        </svg>
                        Clear
                    </button>
                </div>
            @endif
            @else
                <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto mb-3 opacity-50">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                    <p class="text-sm font-medium">No tables created yet</p>
                    <p class="text-xs mt-1">Click "Add Table" to create your first table</p>
                </div>
            @endif
            </div>
        @endif
    </div>

    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-8 space-y-4">
            <div class="flex items-center gap-3">
                <input type="text" wire:model.live="search" placeholder="Search product..." class="flex-1 rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500" />
                <div class="flex items-center gap-1 rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-1">
                    <button type="button" @click="productView = 'grid'" :class="productView === 'grid' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'text-zinc-600 dark:text-zinc-400'" class="inline-flex items-center justify-center w-8 h-8 rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M3 6a3 3 0 0 1 3-3h2.25a3 3 0 0 1 3 3v2.25a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6Zm9.75 0a3 3 0 0 1 3-3H18a3 3 0 0 1 3 3v2.25a3 3 0 0 1-3 3h-2.25a3 3 0 0 1-3-3V6ZM3 15.75a3 3 0 0 1 3-3h2.25a3 3 0 0 1 3 3V18a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-2.25Zm9.75 0a3 3 0 0 1 3-3H18a3 3 0 0 1 3 3V18a3 3 0 0 1-3 3h-2.25a3 3 0 0 1-3-3v-2.25Z" clip-rule="evenodd"/></svg>
                    </button>
                    <button type="button" @click="productView = 'list'" :class="productView === 'list' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'text-zinc-600 dark:text-zinc-400'" class="inline-flex items-center justify-center w-8 h-8 rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M2.625 6.75a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Zm4.875 0A.75.75 0 0 1 8.25 6h12a.75.75 0 0 1 0 1.5h-12a.75.75 0 0 1-.75-.75ZM2.625 12a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0ZM7.5 12a.75.75 0 0 1 .75-.75h12a.75.75 0 0 1 0 1.5h-12A.75.75 0 0 1 7.5 12Zm-4.875 5.25a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Zm4.875 0a.75.75 0 0 1 .75-.75h12a.75.75 0 0 1 0 1.5h-12a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </div>

            <!-- Grid View -->
            <div x-show="productView === 'grid'" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                @forelse($this->products as $product)
                    @php
                        $available = $this->getAvailableForProduct($product->id);
                        $stockDisplay = $this->getPosStockDisplay($product);
                    @endphp
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 p-3 flex flex-col gap-2 bg-white dark:bg-zinc-900">
                        <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $product->name }}</div>
                        <div class="text-xs text-zinc-500">
                            {{ $this->formatCurrency($product->price ?? 0) }}
                            <span class="ml-2 text-[11px] text-zinc-400">/ {{ $product['sales_uom'] ?? $product['base_uom'] ?? 'unit' }}</span>
                        </div>
                        <div class="text-xs">
                            @if($stockDisplay['sales_qty'] === 0.0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-900/30">Out of Stock</span>
                            @elseif($stockDisplay['sales_qty'] < 10)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-orange-700 bg-orange-100 dark:text-orange-400 dark:bg-orange-900/30">
                                    Low Stock ({{ number_format($stockDisplay['sales_qty'], 2) }} {{ $stockDisplay['sales_symbol'] }})
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-emerald-700 bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-900/30">
                                    In Stock ({{ number_format($stockDisplay['sales_qty'], 2) }} {{ $stockDisplay['sales_symbol'] }})
                                </span>
                            @endif
                            @if($stockDisplay['converted'] && $stockDisplay['base_symbol'])
                                <div class="mt-1 text-[11px] text-zinc-400">
                                    ({{ number_format($stockDisplay['base_qty'], 2) }} {{ $stockDisplay['base_symbol'] }})
                                </div>
                            @endif
                        </div>
                        <button type="button" wire:click="addToCart('{{ $product->id }}')"
                            @if($stockDisplay['sales_qty'] <= 0) disabled @endif
                            class="mt-auto inline-flex items-center justify-center gap-2 px-3 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-zinc-400">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M12 4.5a.75.75 0 0 1 .75.75v6h6a.75.75 0 0 1 0 1.5h-6v6a.75.75 0 0 1-1.5 0v-6h-6a.75.75 0 0 1 0-1.5h6v-6A.75.75 0 0 1 12 4.5Z" clip-rule="evenodd"/></svg>
                            Add
                        </button>
                    </div>
                @empty
                    <div class="col-span-full text-center py-10 text-zinc-500 dark:text-zinc-400">
                        <div class="text-sm font-medium">No products found</div>
                        <div class="text-xs mt-1">Try a different search or check stock availability.</div>
                    </div>
                @endforelse
            </div>

            <!-- List View -->
            <div x-show="productView === 'list'" class="space-y-2">
                @forelse($this->products as $product)
                    @php
                        $available = $this->getAvailableForProduct($product->id);
                        $stockDisplay = $this->getPosStockDisplay($product);
                    @endphp
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 flex items-center gap-4">
                        <div class="flex-1">
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $product->name }}</div>
                            <div class="flex items-center gap-3 mt-1">
                                <span class="text-sm text-zinc-500">
                                    {{ $this->formatCurrency($product->price ?? 0) }}
                                    <span class="ml-1 text-[11px] text-zinc-400">/ {{ $product['sales_uom'] ?? $product['base_uom'] ?? 'unit' }}</span>
                                </span>
                                <span class="text-xs">
                                    @if($stockDisplay['sales_qty'] === 0.0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-900/30">Out of Stock</span>
                                    @elseif($stockDisplay['sales_qty'] < 10)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-orange-700 bg-orange-100 dark:text-orange-400 dark:bg-orange-900/30">
                                            Low Stock ({{ number_format($stockDisplay['sales_qty'], 2) }} {{ $stockDisplay['sales_symbol'] }})
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-emerald-700 bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-900/30">
                                            In Stock ({{ number_format($stockDisplay['sales_qty'], 2) }} {{ $stockDisplay['sales_symbol'] }})
                                        </span>
                                    @endif
                                </span>
                                @if($stockDisplay['converted'] && $stockDisplay['base_symbol'])
                                    <span class="text-[11px] text-zinc-400">
                                        ({{ number_format($stockDisplay['base_qty'], 2) }} {{ $stockDisplay['base_symbol'] }})
                                    </span>
                                @endif
                            </div>
                        </div>
                        <button type="button" wire:click="addToCart('{{ $product->id }}')"
                            @if($stockDisplay['sales_qty'] <= 0) disabled @endif
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-zinc-400">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M12 4.5a.75.75 0 0 1 .75.75v6h6a.75.75 0 0 1 0 1.5h-6v6a.75.75 0 0 1-1.5 0v-6h-6a.75.75 0 0 1 0-1.5h6v-6A.75.75 0 0 1 12 4.5Z" clip-rule="evenodd"/></svg>
                            Add to Cart
                        </button>
                    </div>
                @empty
                    <div class="text-center py-10 text-zinc-500 dark:text-zinc-400">
                        <div class="text-sm font-medium">No products found</div>
                        <div class="text-xs mt-1">Try a different search or check stock availability.</div>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $this->products->links() }}
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="sticky top-4 space-y-3 max-h-[calc(100vh-2rem)] overflow-y-auto">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                <div class="p-3 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                    <div class="font-semibold text-zinc-900 dark:text-zinc-100">Cart</div>
                    <div class="flex items-center gap-2">
                        <select wire:model.live="orderType" class="rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-2 py-1 text-sm">
                            <option value="dine-in">Dine in</option>
                            <option value="takeaway">Takeaway</option>
                            <option value="delivery">Delivery</option>
                        </select>
                        <button type="button" wire:click="clearCart" class="inline-flex items-center gap-1.5 px-2 py-1.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0A48.11 48.11 0 0 1 8.28 5.25m-3.507.562L5.84 19.673A2.25 2.25 0 0 0 8.084 21.75h7.832a2.25 2.25 0 0 0 2.244-2.077L18.16 5.79m-10.706 0A48.111 48.111 0 0 1 12 5.25c1.306 0 2.593.046 3.852.135" /></svg>
                            Clear
                        </button>
                    </div>
                </div>

                <div class="max-h-[40vh] overflow-auto divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($cart as $key => $line)
                        <div class="p-3 flex items-center gap-2">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $line['name'] }}</div>
                                <div class="text-xs text-zinc-500">
                                    {{ $this->formatCurrency($line['price']) }}
                                    @if(!empty($line['has_conversion']) && !empty($line['base_uom']))
                                        <span class="ml-1 text-emerald-600 dark:text-emerald-400">
                                            (1 {{ $line['sales_uom'] ?? 'unit' }} = {{ $line['base_quantity'] / $line['qty'] }} {{ $line['base_uom'] }})
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs mt-0.5">
                                    @if($line['available'] === 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-900/30">Out of Stock</span>
                                    @elseif($line['available'] < 10)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-orange-700 bg-orange-100 dark:text-orange-400 dark:bg-orange-900/30">
                                            Low Stock 
                                            @if(!empty($line['available_sales_qty']) && !empty($line['has_conversion']))
                                                ({{ $line['available_sales_qty'] }} {{ $line['sales_uom'] ?? 'units' }})
                                            @else
                                                ({{ $line['available'] }})
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" wire:click="decrement('{{ $key }}')" class="inline-flex items-center justify-center w-7 h-8 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-sm">
                                    −
                                </button>
                                <div class="relative">
                                    <input
                                        type="number"
                                        min="1"
                                        max="{{ $line['available_sales_qty'] ?? $line['available'] }}"
                                        wire:model.blur="cart.{{ $key }}.qty"
                                        wire:change="updateQuantity('{{ $key }}', $event.target.value)"
                                        class="w-12 text-center rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-1 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    />
                                    @if(!empty($line['sales_uom']))
                                        <span class="absolute right-1 top-1/2 -translate-y-1/2 text-[10px] text-zinc-400 pointer-events-none">{{ $line['sales_uom'] }}</span>
                                    @endif
                                </div>
                                <button type="button" wire:click="increment('{{ $key }}')"
                                    @if($line['qty'] >= ($line['available_sales_qty'] ?? $line['available'])) disabled @endif
                                    class="inline-flex items-center justify-center w-7 h-8 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-700 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                                    +
                                </button>
                            </div>
                            <div class="w-20 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->formatCurrency($line['qty'] * $line['price']) }}</div>
                            <button type="button" wire:click="remove('{{ $key }}')" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-rose-600 text-white hover:bg-rose-500">
                                ×
                            </button>
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-zinc-500">No items</div>
                    @endforelse
                </div>

                <div class="p-3 space-y-2 border-t border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center justify-between text-sm text-zinc-700 dark:text-zinc-300">
                        <span>Subtotal</span>
                        <span>{{ $this->formatCurrency($subtotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm text-zinc-700 dark:text-zinc-300">
                        <span>Discount</span>
                        <input type="number" step="0.01" wire:model.live="discount" class="w-28 text-right rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-2 py-1" />
                    </div>
                    <div class="flex items-center justify-between text-sm text-zinc-700 dark:text-zinc-300">
                        <span>Tax</span>
                        <span>{{ $this->formatCurrency($tax) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        <span>Total</span>
                        <span>{{ $this->formatCurrency($total) }}</span>
                    </div>
                    <div class="border-t border-zinc-200 dark:border-zinc-800 pt-2 mt-2">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Payment Methods</span>
                            <button type="button" wire:click="addPaymentRow" class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-md bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-200 dark:hover:bg-emerald-900/50">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M12 4.5a.75.75 0 0 1 .75.75v6h6a.75.75 0 0 1 0 1.5h-6v6a.75.75 0 0 1-1.5 0v-6h-6a.75.75 0 0 1 0-1.5h6v-6A.75.75 0 0 1 12 4.5Z" clip-rule="evenodd"/></svg>
                                Add
                            </button>
                        </div>
                        <div class="space-y-2">
                            @foreach($payments as $index => $payment)
                                <div class="flex flex-col gap-2 rounded-lg border border-zinc-200/70 p-2 dark:border-zinc-800">
                                    <div class="flex items-center gap-2">
                                    <select wire:model.live="payments.{{ $index }}.method" class="flex-1 text-xs rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-2 py-1">
                                        <option value="cash">Cash</option>
                                        <option value="transfer">Transfer</option>
                                        <option value="pos">POS Machine</option>
                                    </select>
                                    <input type="number" step="0.01" wire:model.live="payments.{{ $index }}.amount" placeholder="0.00" class="w-24 text-xs text-right rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-2 py-1" />
                                    @if(count($payments) > 1)
                                        <button type="button" wire:click="removePaymentRow({{ $index }})" class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 hover:bg-rose-200 dark:hover:bg-rose-900/50">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                                        </button>
                                    @endif
                                </div>
                                    @if(($payment['method'] ?? '') === 'transfer')
                                        @php
                                            $defaultBank = $this->bankAccounts->firstWhere('id', $payment['bank_account_id'] ?? null);
                                        @endphp
                                        <div class="w-full text-xs rounded-md border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/60 text-zinc-700 dark:text-zinc-200 px-2 py-1">
                                            {{ $defaultBank ? ($defaultBank->bank_name . ' · ' . $defaultBank->account_number) : 'No bank linked to department' }}
                                        </div>
                                    @endif
                                    @if(in_array(($payment['method'] ?? ''), ['transfer', 'pos'], true))
                                        <input type="text" wire:model.live="payments.{{ $index }}.payer_bank" placeholder="Customer bank (optional)" class="w-full text-xs rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-2 py-1" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-2 space-y-1">
                            <div class="flex items-center justify-between text-xs text-zinc-700 dark:text-zinc-300">
                                <span>Total Paid</span>
                                <span class="font-semibold">{{ $this->formatCurrency($paymentTotal) }}</span>
                            </div>
                            @if($paymentRemaining > 0)
                                <div class="flex items-center justify-between text-xs text-orange-700 dark:text-orange-400">
                                    <span>Remaining</span>
                                    <span class="font-semibold">{{ $this->formatCurrency($paymentRemaining) }}</span>
                                </div>
                            @endif
                            @if($changeDue > 0)
                                <div class="flex items-center justify-between text-xs text-emerald-700 dark:text-emerald-400">
                                    <span>Change Due</span>
                                    <span class="font-semibold">{{ $this->formatCurrency($changeDue) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 pt-2">
                        <button type="button" wire:click="holdSale"
                            @if(!$this->hasActiveShift()) disabled title="No active shift" @endif
                            class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            Hold
                        </button>
                        <button type="button" wire:click="completeSale"
                            :disabled="{{ !$this->hasActiveShift() || count($cart) === 0 ? 'true' : 'false' }} || $wire.total <= 0 || $wire.paymentRemaining > 0.01"
                            @if(!$this->hasActiveShift()) title="No active shift" @endif
                            class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                            Pay
                        </button>
                        <button type="button" @click="showReceipt = true" class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-sm">Print</button>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3">
                <div class="font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Quick Actions</div>
                <div class="grid grid-cols-3 gap-2 mb-3">
                    <button type="button" class="px-2 py-1.5 text-xs rounded-md bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-200 dark:hover:bg-emerald-900/50">
                        <div class="font-semibold">F1</div>
                        <div class="text-[10px] opacity-70">New Sale</div>
                    </button>
                    <button type="button" wire:click="holdSale" class="px-2 py-1.5 text-xs rounded-md bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 hover:bg-orange-200 dark:hover:bg-orange-900/50">
                        <div class="font-semibold">F2</div>
                        <div class="text-[10px] opacity-70">Hold</div>
                    </button>
                    <button type="button" class="px-2 py-1.5 text-xs rounded-md bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50">
                        <div class="font-semibold">F3</div>
                        <div class="text-[10px] opacity-70">History</div>
                    </button>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3">
                <div class="font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Low Stock Alerts</div>
                <div class="space-y-1 max-h-40 overflow-auto">
                    @php
                        $alerts = \App\Models\ProductStock::query()
                            ->whereDate('stock_date', \Carbon\Carbon::today())
                            ->with('product')
                            ->get()
                            ->filter(function($s){ $s->updateCalculatedFields(); return $s->closing_quantity < 10; });
                    @endphp
                    @forelse($alerts as $s)
                        <div class="flex items-center justify-between text-xs">
                            <div class="text-zinc-800 dark:text-zinc-200">{{ $s->product->name ?? 'Product' }}</div>
                            <div>
                                @if($s->closing_quantity <= 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-900/30">Out</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-orange-700 bg-orange-100 dark:text-orange-400 dark:bg-orange-900/30">Low ({{ (float)$s->closing_quantity }})</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-zinc-500">No alerts</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3">
                <div class="font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Today's Summary</div>
                @php
                    $todaySales = \App\Models\Sale::whereDate('sale_time', \Carbon\Carbon::today())
                        ->where('status', 'completed')
                        ->get();
                    $todayTotal = $todaySales->sum('total');
                    $todayCount = $todaySales->count();
                @endphp
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between">
                        <span class="text-zinc-600 dark:text-zinc-400">Sales Count:</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $todayCount }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-600 dark:text-zinc-400">Total Revenue:</span>
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $this->formatCurrency($todayTotal) }}</span>
                    </div>
                    @if($todayCount > 0)
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Avg Sale:</span>
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->formatCurrency($todayTotal / $todayCount) }}</span>
                        </div>
                    @endif
                </div>
            </div>
            </div>
        </div>
    </div>

    <!-- Receipt Print Modal -->
    <div x-show="showReceipt" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" @pos-receipt-ready.window="showReceipt = true">
        <div class="absolute inset-0 bg-black/40" @click="showReceipt = false"></div>
        <div class="relative w-full max-w-sm mx-auto bg-white shadow-2xl" id="receipt-to-print">
            <div class="p-6">
                @if($currentSaleId)
                    @php
                        $sale = \App\Models\Sale::with('saleItems.product')->find($currentSaleId);
                        $receipt = \App\Models\Receipt::where('sale_id', $currentSaleId)->latest()->first();
                    @endphp
                    @if($sale)
                        <div class="text-center mb-4">
                            <div class="text-2xl font-bold">SWEET TOOTH</div>
                            <div class="text-sm text-zinc-600">Sales Receipt</div>
                            <div class="text-xs text-zinc-500 mt-1">{{ $sale->sale_number }}</div>
                            <div class="text-xs text-zinc-500">{{ $sale->sale_time->format('d M Y, h:i A') }}</div>
                        </div>

                        <div class="border-t-2 border-b-2 border-dashed border-zinc-300 py-3 my-3">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200">
                                        <th class="text-left pb-1">Item</th>
                                        <th class="text-center pb-1">Qty</th>
                                        <th class="text-right pb-1">Price</th>
                                        <th class="text-right pb-1">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sale->saleItems as $item)
                                        <tr>
                                            <td class="py-1">{{ $item->product->name ?? 'Product' }}</td>
                                            <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                                            <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                                            <td class="text-right font-semibold">{{ number_format($item->total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="space-y-1 text-sm mb-3">
                            <div class="flex justify-between">
                                <span>Subtotal:</span>
                                <span>{{ $this->formatCurrency($sale->subtotal) }}</span>
                            </div>
                            @if($sale->discount > 0)
                                <div class="flex justify-between text-orange-600">
                                    <span>Discount:</span>
                                    <span>-{{ $this->formatCurrency($sale->discount) }}</span>
                                </div>
                            @endif
                            @if($sale->tax > 0)
                                <div class="flex justify-between">
                                    <span>Tax:</span>
                                    <span>{{ $this->formatCurrency($sale->tax) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-lg font-bold border-t border-zinc-300 pt-1 mt-2">
                                <span>TOTAL:</span>
                                <span>{{ $this->formatCurrency($sale->total) }}</span>
                            </div>
                        </div>

                        @if($receipt && !empty($receipt->payments))
                            <div class="border-t border-dashed border-zinc-300 pt-2 mb-3">
                                <div class="text-xs font-semibold mb-1">Payment Method(s):</div>
                                @foreach($receipt->payments as $payment)
                                    <div class="flex justify-between text-sm">
                                        <span class="capitalize">{{ $payment['method'] ?? 'Cash' }}:</span>
                                        <span>{{ $this->formatCurrency($payment['amount'] ?? 0) }}</span>
                                    </div>
                                @endforeach
                                @if($receipt->change_due > 0)
                                    <div class="flex justify-between text-sm font-semibold mt-1 text-emerald-600">
                                        <span>Change:</span>
                                        <span>{{ $this->formatCurrency($receipt->change_due) }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="text-center text-xs text-zinc-500 border-t border-zinc-200 pt-3">
                            <p>Thank you for your patronage!</p>
                            <p class="mt-1">{{ $sale->order_type ? ucfirst(str_replace('-', ' ', $sale->order_type)) : '' }}</p>
                        </div>
                    @endif
                @else
                    <div class="text-center py-8 text-zinc-500">
                        <p>Complete a sale to view receipt</p>
                    </div>
                @endif
            </div>

            <div class="bg-zinc-100 px-4 py-3 flex items-center justify-end gap-2 print:hidden">
                <button type="button" @click="showReceipt = false" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-white text-zinc-700 hover:bg-zinc-200 border border-zinc-300">
                    Close
                </button>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-500">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M7.875 1.5C6.839 1.5 6 2.34 6 3.375v2.99c-.426.053-.851.11-1.274.174-1.454.218-2.476 1.483-2.476 2.917v6.294a3 3 0 0 0 3 3h.27l-.155 1.705A1.875 1.875 0 0 0 7.232 22.5h9.536a1.875 1.875 0 0 0 1.867-2.045l-.155-1.705h.27a3 3 0 0 0 3-3V9.456c0-1.434-1.022-2.7-2.476-2.917A48.716 48.716 0 0 0 18 6.366V3.375c0-1.036-.84-1.875-1.875-1.875h-8.25ZM16.5 6.205v-2.83A.375.375 0 0 0 16.125 3h-8.25a.375.375 0 0 0-.375.375v2.83a49.353 49.353 0 0 1 9 0Zm-.217 8.265c.178.018.317.16.333.337l.526 5.784a.375.375 0 0 1-.374.409H7.232a.375.375 0 0 1-.374-.409l.526-5.784a.373.373 0 0 1 .333-.337 41.741 41.741 0 0 1 8.566 0Zm.967-3.97a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H18a.75.75 0 0 1-.75-.75V10.5ZM15 9.75a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V10.5a.75.75 0 0 0-.75-.75H15Z" clip-rule="evenodd"/></svg>
                    Print Receipt
                </button>
            </div>
        </div>
    </div>

    <!-- Add Table Modal -->
    @if($showTableModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data="{ show: @entangle('showTableModal') }">
            <div class="absolute inset-0 bg-black/50" @click="$wire.set('showTableModal', false)"></div>
            <div class="relative w-full max-w-md bg-white dark:bg-zinc-900 rounded-xl shadow-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Add New Table</h3>
                    <button type="button" @click="$wire.set('showTableModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                            <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Table Number *</label>
                        <input
                            type="text"
                            wire:model="newTableNumber"
                            placeholder="e.g., 9 or A1"
                            class="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                        @error('newTableNumber') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Table Name (Optional)</label>
                        <input
                            type="text"
                            wire:model="newTableName"
                            placeholder="e.g., VIP Table, Window Table"
                            class="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                        @error('newTableName') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Capacity *</label>
                        <input
                            type="number"
                            wire:model="newTableCapacity"
                            min="1"
                            max="20"
                            class="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                        @error('newTableCapacity') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-3 pt-4">
                        <button
                            type="button"
                            @click="$wire.set('showTableModal', false)"
                            class="flex-1 px-4 py-2 rounded-md border border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            Cancel
                        </button>
                        <button
                            type="button"
                            wire:click="createTable"
                            class="flex-1 px-4 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-500">
                            Create Table
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Dispatch Verification Modal -->
    <div
        x-data="{ showDispatchModal: false }"
        @open-dispatch-verification.window="showDispatchModal = true"
        class="relative">
        @if($this->pendingDispatches > 0)
            <div x-show="showDispatchModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50" @click="showDispatchModal = false"></div>
                <div class="relative w-full max-w-4xl bg-white dark:bg-zinc-900 rounded-xl shadow-2xl max-h-[90vh] overflow-hidden">
                    <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Dispatch Verification</h3>
                        <button @click="showDispatchModal = false" class="text-zinc-400 hover:text-zinc-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                        <livewire:branch-dashboard.sales-dashboard.dispatch-verification />
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #receipt-to-print, #receipt-to-print * {
                visibility: visible;
            }
            #receipt-to-print {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
            }
            .print\:hidden {
                display: none !important;
            }
        }
    </style>
</div>
