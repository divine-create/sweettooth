<div>
    <x-breadcrumb title="Inventory Helper" :items="[['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')], ['label' => 'Inventory Helper']]" :compact="false" :with-icons="true" />

    <div class="max-w-7xl mx-auto py-6">
        <div class="bg-white dark:bg-zinc-800 overflow-hidden shadow-sm sm:rounded-lg">
            <!-- Header -->
            <div class="p-6 bg-white dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <h1 class="ml-2 text-2xl font-medium text-gray-900 dark:text-white">
                        Inventory Management System - Complete Guide
                    </h1>
                </div>
                <p class="mt-4 text-gray-600 dark:text-gray-400 leading-relaxed">
                    Comprehensive documentation for the SweetTooth Inventory Management System. This guide covers all modules, workflows, button actions, and system behaviors.
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs font-medium">📦 10 Modules</span>
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-xs font-medium">🔄 4 Core Workflows</span>
                    <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full text-xs font-medium">🔐 Audit Approval System</span>
                </div>
            </div>

            <!-- System Overview -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">🏗️ System Architecture Overview</h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
                        <div class="text-2xl font-bold text-blue-600">4</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Core Entities</div>
                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">Items, Stocks, Purchases, Movements</div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg border border-green-200 dark:border-green-700">
                        <div class="text-2xl font-bold text-green-600">1</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Fulfillment Flow</div>
                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">Material Requests & Approvals</div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg border border-purple-200 dark:border-purple-700">
                        <div class="text-2xl font-bold text-purple-600">2</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Approval Layers</div>
                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">Audit + Inventory Approvals</div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg border border-orange-200 dark:border-orange-700">
                        <div class="text-2xl font-bold text-orange-600">100%</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Audit Trail</div>
                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">All actions logged</div>
                    </div>
                </div>
            </div>

            <!-- Module Navigation Grid -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">📚 Module Documentation</h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="#items" class="group block bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 p-4 rounded-lg border border-blue-200 dark:border-blue-700 hover:shadow-lg transition-all">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-600">Items</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Master catalog</div>
                            </div>
                        </div>
                    </a>
                    <a href="#stocks" class="group block bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 p-4 rounded-lg border border-purple-200 dark:border-purple-700 hover:shadow-lg transition-all">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white group-hover:text-purple-600">Stocks</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Levels & Health</div>
                            </div>
                        </div>
                    </a>
                    <a href="#purchases" class="group block bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 p-4 rounded-lg border border-green-200 dark:border-green-700 hover:shadow-lg transition-all">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white group-hover:text-green-600">Purchases</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Orders & Suppliers</div>
                            </div>
                        </div>
                    </a>
                    <a href="#fulfillment" class="group block bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 p-4 rounded-lg border border-red-200 dark:border-red-700 hover:shadow-lg transition-all">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white group-hover:text-red-600">Fulfillment</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Material Requests</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- ITEMS MODULE -->
            <div id="items" class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <span class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </span>
                    Items Management
                </h2>
                
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-4">
                    <p class="text-gray-700 dark:text-gray-300 text-sm">
                        <strong>Purpose:</strong> Create and manage the master inventory catalog. Items are the foundation of your inventory system and are used in purchases, stocks, and production recipes.
                    </p>
                </div>

                <div class="space-y-4">
                    <!-- Button Actions -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">🔘 Button Actions</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md whitespace-nowrap">+ New Item</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Create New Item</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Opens modal to create a new inventory item. Fields: Name, SKU (auto-generated), Category, UOM, Reorder Level, Max Stock Level, Unit Price, Status.
                                        <br><span class="text-orange-600 dark:text-orange-400 font-medium">Note:</span> Non-super admins will trigger an audit approval request after filling the form.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-gray-600 text-white text-sm rounded-md whitespace-nowrap">Edit</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Edit Item</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Opens modal pre-filled with item data. Changes are logged in audit trail. Non-super admins require audit approval.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-md whitespace-nowrap">Delete</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Delete Item</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Shows confirmation dialog with related data that will be deleted. Super admins delete immediately; others submit audit request.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STOCKS MODULE -->
            <div id="stocks" class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <span class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </span>
                    Stocks Management
                </h2>
                
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 mb-4">
                    <p class="text-gray-700 dark:text-gray-300 text-sm">
                        <strong>Purpose:</strong> View and manage real-time stock levels. Stock quantities are updated through purchases, material fulfillments, and adjustments.
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">🔘 Key Features</h3>
                        </div>
                        <div class="p-4 space-y-3 text-sm">
                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded flex items-center justify-center mr-3 flex-shrink-0">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">Quantity Available Visibility</span>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">The dashboard clearly shows the "Qty Available" for each item, which is the amount usable for production or fulfillment.</div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded flex items-center justify-center mr-3 flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">Automated Costing</span>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Average costs are calculated automatically using the Weighted Average method. Manual editing of average cost is restricted to maintain accounting integrity.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PURCHASES MODULE -->
            <div id="purchases" class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <span class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </span>
                    Purchases Management
                </h2>
                
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 mb-4">
                    <p class="text-gray-700 dark:text-gray-300 text-sm">
                        <strong>Purpose:</strong> Create and manage purchase orders. Approved purchases automatically increase stock levels and update item costs.
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">💰 Purchase Workflow</h3>
                        </div>
                        <div class="p-4 space-y-3 text-sm">
                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded flex items-center justify-center mr-3 flex-shrink-0">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">Payment Status Persistence</span>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">When creating a purchase, the selected payment status (Paid, Partial, Pending) is correctly saved and visible across the system.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FULFILLMENT MODULE -->
            <div id="fulfillment" class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <span class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </span>
                    Material Fulfillment
                </h2>
                
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 mb-4">
                    <p class="text-gray-700 dark:text-gray-300 text-sm">
                        <strong>Purpose:</strong> Handle internal requests for raw materials and supplies from the production department.
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-4">
                            <h3 class="font-semibold text-sm text-gray-900 dark:text-white mb-2">Material Requests</h3>
                            <p class="text-xs text-gray-600 dark:text-gray-400">View and manage requests submitted by production departments for ingredients and materials.</p>
                        </div>
                        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-4">
                            <h3 class="font-semibold text-sm text-gray-900 dark:text-white mb-2">Material Approvals</h3>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Process and approve requested materials. Approval triggers stock deduction and transfers items to the production store.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADDITIONAL MODULES SUMMARY -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">📚 Additional Modules Summary</h2>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <!-- Health Checks -->
                    <div class="bg-white dark:bg-zinc-900 rounded-lg p-4 border border-gray-200 dark:border-zinc-700">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-pink-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Health Checks</h3>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Record condition inspections. Features a <strong>Searchable Item Selection</strong> with real-time quantity visibility.</p>
                    </div>

                    <!-- Analytics -->
                    <div class="bg-white dark:bg-zinc-900 rounded-lg p-4 border border-gray-100 dark:border-zinc-700">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-cyan-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Analytics</h3>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Dashboard with KPIs, Stock Health, and Purchase Performance. Legacy request/dispatch analytics have been streamlined.</p>
                    </div>
                </div>
            </div>

            <!-- Audit Approval System Details -->
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">🔐 Audit Approval System</h2>
                <div class="bg-orange-50 dark:bg-zinc-800 rounded-lg p-6 border border-orange-200 dark:border-zinc-700">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
                        Non-super admin users require approval for critical inventory actions. When such users perform restricted actions, data is saved temporarily and an audit request is created for manager review.
                    </p>
                    <div class="grid md:grid-cols-2 gap-3">
                        <div class="bg-white dark:bg-zinc-900 rounded-lg p-3 border border-gray-100 dark:border-zinc-800">
                            <div class="font-medium text-orange-900 dark:text-orange-400 text-sm mb-2">Actions Requiring Approval:</div>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• Create/Edit/Delete Items</li>
                                <li>• Stock Adjustments</li>
                                <li>• Purchase Orders (non-super admins)</li>
                                <li>• Any action modifying inventory data</li>
                            </ul>
                        </div>
                        <div class="bg-white dark:bg-zinc-900 rounded-lg p-3 border border-gray-100 dark:border-zinc-800">
                            <div class="font-medium text-green-900 dark:text-green-400 text-sm mb-2">Approval Flow:</div>
                            <ol class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                <li>1. User performs action → data saved temporarily</li>
                                <li>2. Audit modal appears → enter reason</li>
                                <li>3. Request sent to Audit > Inventory Approvals</li>
                                <li>4. Manager reviews and approves/rejects</li>
                                <li>5. If approved: changes applied to database</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Reference Footer -->
            <div class="p-6 bg-gray-50 dark:bg-zinc-800 border-t border-gray-200 dark:border-zinc-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">⚡ Quick Reference</h3>
                <div class="grid md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white mb-2">Keyboard Shortcuts</div>
                        <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <li>• <kbd class="px-2 py-1 bg-gray-200 dark:bg-zinc-700 rounded">Ctrl+N</kbd> New Item (when available)</li>
                            <li>• <kbd class="px-2 py-1 bg-gray-200 dark:bg-zinc-700 rounded">Ctrl+F</kbd> Focus search</li>
                            <li>• <kbd class="px-2 py-1 bg-gray-200 dark:bg-zinc-700 rounded">Esc</kbd> Close modal</li>
                        </ul>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white mb-2">Common Status Codes</div>
                        <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <li>• 🟢 Good / Active / Approved</li>
                            <li>• 🟡 Warning / Pending / Low Stock</li>
                            <li>• 🔴 Critical / Rejected / Out of Stock</li>
                            <li>• 🔵 Info / In Progress</li>
                        </ul>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white mb-2">Need Help?</div>
                        <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <li>• Check Audit > Inventory Approvals for pending requests</li>
                            <li>• View Stock Movements for complete history</li>
                            <li>• Use Analytics for insights and trends</li>
                            <li>• Export CSV for offline analysis</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin-slow {
            animation: spin-slow 3s linear infinite;
        }
    </style>
</div>
