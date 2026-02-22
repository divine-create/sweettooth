<div>
    <x-breadcrumb title="Inventory Helper" :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Inventory Helper']]" :compact="false" :with-icons="true" />

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
                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs font-medium">📦 12 Modules</span>
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-xs font-medium">🔄 5 Core Workflows</span>
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
                        <div class="text-2xl font-bold text-green-600">3</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Request Flows</div>
                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">Item Requests, Dispatches, Callbacks</div>
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
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="#items" class="group block bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 p-4 rounded-lg border border-blue-200 dark:border-blue-700 hover:shadow-lg transition-all">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-600">Items</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Master catalog management</div>
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
                                <div class="text-xs text-gray-600 dark:text-gray-400">Stock levels & adjustments</div>
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
                                <div class="text-xs text-gray-600 dark:text-gray-400">Purchase orders & suppliers</div>
                            </div>
                        </div>
                    </a>
                    <a href="#movements" class="group block bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 p-4 rounded-lg border border-orange-200 dark:border-orange-700 hover:shadow-lg transition-all">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white group-hover:text-orange-600">Stock Movements</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Track all inventory changes</div>
                            </div>
                        </div>
                    </a>
                    <a href="#requests" class="group block bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 p-4 rounded-lg border border-red-200 dark:border-red-700 hover:shadow-lg transition-all">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white group-hover:text-red-600">Item Requests</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Department request workflow</div>
                            </div>
                        </div>
                    </a>
                    <a href="#dispatches" class="group block bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/30 dark:to-yellow-800/30 p-4 rounded-lg border border-yellow-200 dark:border-yellow-700 hover:shadow-lg transition-all">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white group-hover:text-yellow-600">Item Dispatches</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Approve & dispatch items</div>
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
                        <strong>Purpose:</strong> Create and manage the master inventory catalog. Items are the foundation of your inventory system and are used in purchases, stocks, requests, and production recipes.
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
                                        Opens modal to create a new inventory item. Fields: Name, SKU (auto-generated), Category (raw_material/packaging/consumable/equipment), UOM, Reorder Level, Max Stock Level, Unit Price, Status, Requires Request toggle.
                                        <br><span class="text-orange-600 dark:text-orange-400 font-medium">Note:</span> Non-super admins will trigger an audit approval request after filling the form.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-gray-600 text-white text-sm rounded-md whitespace-nowrap">Edit</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Edit Item</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Opens modal pre-filled with item data. Allows modifying all fields except SKU. Changes are logged in audit trail showing old → new values. Non-super admins require audit approval.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-md whitespace-nowrap">Delete</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Delete Item</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Shows confirmation dialog with related data that will be deleted (recipes using item, stock records, purchase history, stock movements). Super admins delete immediately; others submit audit request.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-md whitespace-nowrap">Export CSV</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Export to CSV</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Downloads all items as CSV file with columns: SKU, Name, Category, Reorder Level, Unit Price, Status, UOM. Exports ALL items (ignores pagination/search filters).
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Workflow -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">🔄 Create Item Workflow</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div class="flex-1 min-w-[150px] bg-blue-100 dark:bg-blue-900 rounded-lg p-3 text-center">
                                    <div class="text-sm font-semibold text-blue-900 dark:text-blue-100">1. Click New Item</div>
                                </div>
                                <svg class="w-6 h-6 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="flex-1 min-w-[150px] bg-blue-100 dark:bg-blue-900 rounded-lg p-3 text-center">
                                    <div class="text-sm font-semibold text-blue-900 dark:text-blue-100">2. Fill Form</div>
                                </div>
                                <svg class="w-6 h-6 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="flex-1 min-w-[150px] bg-purple-100 dark:bg-purple-900 rounded-lg p-3 text-center">
                                    <div class="text-sm font-semibold text-purple-900 dark:text-purple-100">3. Save</div>
                                </div>
                                <svg class="w-6 h-6 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="flex-1 min-w-[150px] bg-green-100 dark:bg-green-900 rounded-lg p-3 text-center">
                                    <div class="text-sm font-semibold text-green-900 dark:text-green-100">4a. Super Admin: Immediate Save</div>
                                </div>
                                <svg class="w-6 h-6 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="flex-1 min-w-[150px] bg-orange-100 dark:bg-orange-900 rounded-lg p-3 text-center">
                                    <div class="text-sm font-semibold text-orange-900 dark:text-orange-100">4b. Others: Audit Request</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Key Fields -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">📝 Key Fields Explained</h3>
                        </div>
                        <div class="p-4 grid md:grid-cols-2 gap-3">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">SKU</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Auto-generated format: CATEGORY-CODE-RANDOM (e.g., RM-CHO-0042 for Raw Material Chocolate)</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Category</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">raw_material, packaging, consumable, equipment. Determines SKU prefix and filtering.</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Requires Request</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">When enabled, non-production departments can ONLY request this item through the Item Request workflow (not direct dispatch).</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Reorder Level</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Minimum stock threshold. Triggers low stock alerts when quantity_available falls below this value.</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Max Stock Level</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Maximum recommended stock. Warns when overstocking occurs.</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Unit Price</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Last purchase price. Auto-updated when new purchases are approved. Used for stock valuation.</div>
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
                        <strong>Purpose:</strong> View and manage real-time stock levels for each item in your branch. Stock records are automatically created when items are created, and quantities are updated through purchases, dispatches, and adjustments.
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
                                <button class="px-3 py-1.5 bg-purple-600 text-white text-sm rounded-md whitespace-nowrap">Edit Stock</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Edit Stock Levels</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Opens modal to manually adjust: Quantity Available, Quantity Reserved, Quantity Damaged, Average Cost, Health Status (good/warning/critical/expired), Expiry Date, Notes. Also allows updating item's Reorder Level and Max Stock Level. Non-super admins trigger audit approval modal.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-md whitespace-nowrap">Export CSV</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Export to CSV</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Downloads stock report with columns: SKU, Item Name, Category, Available, Reserved, Damaged, Total, UOM, Avg Cost, Total Value, Reorder Level, Max Level, Health Status, Expiry Date, Last Updated.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">🔍 Available Filters</h3>
                        </div>
                        <div class="p-4 grid md:grid-cols-3 gap-3">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Search</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Filter by item name or SKU</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Category</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Filter by item category</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Status</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">low_stock (below reorder), overstock (above max)</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Health Status</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">good, warning, critical, expired</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Date Range</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Filter by last stock take date</div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Adjustment Process -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">📊 Stock Adjustment Process</h3>
                        </div>
                        <div class="p-4">
                            <ol class="space-y-2 text-sm">
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-purple-500 text-white rounded-full flex items-center justify-center text-xs mr-2">1</span>
                                    <div>
                                        <span class="font-medium">Click Edit Stock button</span>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Opens modal with current stock values pre-filled</div>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-purple-500 text-white rounded-full flex items-center justify-center text-xs mr-2">2</span>
                                    <div>
                                        <span class="font-medium">Modify quantities or health status</span>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Adjust Available/Reserved/Damaged quantities, update cost, change health status</div>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-purple-500 text-white rounded-full flex items-center justify-center text-xs mr-2">3</span>
                                    <div>
                                        <span class="font-medium">Click Update</span>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Super admins: changes applied immediately. Others: audit modal appears</div>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-orange-500 text-white rounded-full flex items-center justify-center text-xs mr-2">4</span>
                                    <div>
                                        <span class="font-medium">Enter audit reason (non-super admins only)</span>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Provide justification for the adjustment (min 10 characters)</div>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs mr-2">5</span>
                                    <div>
                                        <span class="font-medium">Submit for approval</span>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Request routed to Audit > Inventory Approvals for manager review</div>
                                    </div>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <!-- Health Status Meanings -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">🏥 Health Status Meanings</h3>
                        </div>
                        <div class="p-4 grid md:grid-cols-2 gap-3">
                            <div class="flex items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                <div>
                                    <div class="text-sm font-medium text-green-900 dark:text-green-100">Good</div>
                                    <div class="text-xs text-green-700 dark:text-green-300">Stock levels optimal, no issues</div>
                                </div>
                            </div>
                            <div class="flex items-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></div>
                                <div>
                                    <div class="text-sm font-medium text-yellow-900 dark:text-yellow-100">Warning</div>
                                    <div class="text-xs text-yellow-700 dark:text-yellow-300">Approaching reorder level or minor issues</div>
                                </div>
                            </div>
                            <div class="flex items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <div class="w-3 h-3 bg-red-500 rounded-full mr-3"></div>
                                <div>
                                    <div class="text-sm font-medium text-red-900 dark:text-red-100">Critical</div>
                                    <div class="text-xs text-red-700 dark:text-red-300">Below reorder level or major issues</div>
                                </div>
                            </div>
                            <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-900/20 rounded-lg">
                                <div class="w-3 h-3 bg-gray-500 rounded-full mr-3"></div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Expired</div>
                                    <div class="text-xs text-gray-700 dark:text-gray-300">Past expiry date, requires removal</div>
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
                        <strong>Purpose:</strong> Create and manage purchase orders from suppliers. Approved purchases automatically increase stock levels and update item costs. Supports multi-item purchases with cost allocation.
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
                                <button class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-md whitespace-nowrap">+ New Purchase</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Create Purchase Order</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Opens modal to create purchase order. Fields: Purchase Date, Supplier Name, Supplier Contact, Purchase Items (dynamic rows with Item, Quantity, UOM, Unit Price), Other Costs, Notes, Payment Status. Super admins create with immediate stock updates; others save as draft and request approval.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md whitespace-nowrap">View Detail</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">View Purchase Details</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Opens read-only modal showing: Purchase Number, Supplier Info, All Items with quantities and costs, Total FOB, Landing Cost, Payment Status, Approval Status, Notes.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-md whitespace-nowrap">Delete</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Delete Purchase</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Only draft purchases can be deleted. Approved purchases cannot be deleted (would require reversal through stock adjustments). Shows confirmation before deletion.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-yellow-600 text-white text-sm rounded-md whitespace-nowrap">Request Approval</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Request Approval</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        For draft purchases created by non-super admins. Opens modal to add notes, then submits to Audit > Purchase Approvals for manager review.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-md whitespace-nowrap">Export CSV</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Export to CSV</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Downloads purchase report with all purchase data including items, costs, supplier info, and payment status.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Purchase Flow -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">🔄 Purchase Creation Flow</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div class="flex-1 min-w-[120px] bg-green-100 dark:bg-green-900 rounded-lg p-2 text-center">
                                    <div class="text-xs font-semibold text-green-900 dark:text-green-100">1. Add Items</div>
                                    <div class="text-xs text-green-700 dark:text-green-300 mt-1">Select items, quantities, prices</div>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="flex-1 min-w-[120px] bg-green-100 dark:bg-green-900 rounded-lg p-2 text-center">
                                    <div class="text-xs font-semibold text-green-900 dark:text-green-100">2. Add Costs</div>
                                    <div class="text-xs text-green-700 dark:text-green-300 mt-1">Other costs (shipping, etc)</div>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="flex-1 min-w-[120px] bg-blue-100 dark:bg-blue-900 rounded-lg p-2 text-center">
                                    <div class="text-xs font-semibold text-blue-900 dark:text-blue-100">3. Save</div>
                                    <div class="text-xs text-blue-700 dark:text-blue-300 mt-1">Super admin: immediate</div>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="flex-1 min-w-[120px] bg-orange-100 dark:bg-orange-900 rounded-lg p-2 text-center">
                                    <div class="text-xs font-semibold text-orange-900 dark:text-orange-100">4. Stock Updated</div>
                                    <div class="text-xs text-orange-700 dark:text-orange-300 mt-1">Quantities & costs updated</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cost Calculation -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">💰 Cost Calculation Explained</h3>
                        </div>
                        <div class="p-4 space-y-3 text-sm">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                                <div class="font-medium text-blue-900 dark:text-blue-100 mb-2">Landing Cost Allocation:</div>
                                <div class="text-xs text-blue-800 dark:text-blue-200 space-y-1">
                                    <div><strong>FOB Cost:</strong> Sum of (Quantity × Unit Price) for all items</div>
                                    <div><strong>Other Costs:</strong> Shipping, customs, handling fees (optional)</div>
                                    <div><strong>Landing Cost:</strong> FOB + Other Costs</div>
                                    <div><strong>Cost Per Unit:</strong> Landing cost allocated proportionally to each item based on FOB value</div>
                                </div>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                                <div class="font-medium text-green-900 dark:text-green-100 mb-2">Stock Impact:</div>
                                <div class="text-xs text-green-800 dark:text-green-200">
                                    When purchase is approved: Stock quantities increase, Average Cost is recalculated using weighted average method, Item's Unit Price is updated to latest purchase price.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STOCK MOVEMENTS MODULE -->
            <div id="movements" class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <span class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </span>
                    Stock Movements
                </h2>
                
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 mb-4">
                    <p class="text-gray-700 dark:text-gray-300 text-sm">
                        <strong>Purpose:</strong> Complete audit trail of all inventory changes. Every stock movement (in, out, transfer, adjustment, damaged) is recorded with timestamps, quantities, responsible users, and references to source documents.
                    </p>
                </div>

                <div class="space-y-4">
                    <!-- Movement Types -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">📊 Movement Types</h3>
                        </div>
                        <div class="p-4 grid md:grid-cols-2 gap-3">
                            <div class="flex items-start p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-green-900 dark:text-green-100">Stock In</div>
                                    <div class="text-xs text-green-700 dark:text-green-300">Increases stock. Sources: Purchases, Returns</div>
                                </div>
                            </div>
                            <div class="flex items-start p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-red-900 dark:text-red-100">Stock Out</div>
                                    <div class="text-xs text-red-700 dark:text-red-300">Decreases stock. Sources: Dispatches, Production usage</div>
                                </div>
                            </div>
                            <div class="flex items-start p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-blue-900 dark:text-blue-100">Transfer</div>
                                    <div class="text-xs text-blue-700 dark:text-blue-300">Moves stock between locations/branches</div>
                                </div>
                            </div>
                            <div class="flex items-start p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-yellow-900 dark:text-yellow-100">Damaged</div>
                                    <div class="text-xs text-yellow-700 dark:text-yellow-300">Records damaged/unsellable stock</div>
                                </div>
                            </div>
                            <div class="flex items-start p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-purple-900 dark:text-purple-100">Adjustment</div>
                                    <div class="text-xs text-purple-700 dark:text-purple-300">Manual corrections from stock takes or audits</div>
                                </div>
                            </div>
                            <div class="flex items-start p-3 bg-gray-50 dark:bg-gray-900/20 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 bg-gray-500 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Return</div>
                                    <div class="text-xs text-gray-700 dark:text-gray-300">Items returned to stock (from production, etc)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">🔍 Filters & Views</h3>
                        </div>
                        <div class="p-4">
                            <div class="grid md:grid-cols-3 gap-3 mb-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Search</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Item name/SKU, user name, notes</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Type</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Filter by movement type</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Shift</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Morning/Evening/Night</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Department</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Requesting department</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Date From/To</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Date range filter</div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-md">Table View</button>
                                <button class="px-3 py-1.5 bg-gray-600 text-white text-xs rounded-md">Feed View</button>
                                <button class="px-3 py-1.5 bg-purple-600 text-white text-xs rounded-md">Analytics View</button>
                            </div>
                        </div>
                    </div>

                    <!-- Export -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">📤 Export</h3>
                        </div>
                        <div class="p-4">
                            <button class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-md">Export CSV</button>
                            <div class="mt-3 text-xs text-gray-600 dark:text-gray-400">
                                Exports all movements with columns: Date, Time, Item Name, SKU, Type, Quantity Change, Qty Before, Qty After, UOM, Moved By, Department, Shift, Reference, Notes.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ITEM REQUESTS MODULE -->
            <div id="requests" class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <span class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </span>
                    Item Requests
                </h2>
                
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 mb-4">
                    <p class="text-gray-700 dark:text-gray-300 text-sm">
                        <strong>Purpose:</strong> Departments request items from inventory. Production departments can request any item; non-production departments can ONLY request items marked as "Requires Request" (typically durable equipment). Requests must be approved before dispatch.
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
                                <button class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-md whitespace-nowrap">+ New Request</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Create Item Request</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Opens modal: Select Department (with shift), Request Date, Items (with quantities). Validation: Non-production departments can only request items with "Requires Request" enabled. Quantities cannot exceed available stock.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md whitespace-nowrap">View</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">View Request Details</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Opens modal showing: Request Number, Department, Requester, Status, All requested items with quantities (requested/approved/dispatched), Notes, Timeline.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-md whitespace-nowrap">Export CSV</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Export to CSV</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Downloads request report with: Request Number, Department, Requester, Date, Status, Items Count, Total Quantities (requested/approved/dispatched), Notes.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Request Flow -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">🔄 Complete Request-to-Dispatch Flow</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div class="flex-1 min-w-[100px] bg-red-100 dark:bg-red-900 rounded-lg p-2 text-center">
                                    <div class="text-xs font-semibold text-red-900 dark:text-red-100">1. Create Request</div>
                                    <div class="text-xs text-red-700 dark:text-red-300 mt-1">Department submits</div>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 hidden lg:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="flex-1 min-w-[100px] bg-yellow-100 dark:bg-yellow-900 rounded-lg p-2 text-center">
                                    <div class="text-xs font-semibold text-yellow-900 dark:text-yellow-100">2. Pending</div>
                                    <div class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">Awaiting approval</div>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 hidden lg:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="flex-1 min-w-[100px] bg-green-100 dark:bg-green-900 rounded-lg p-2 text-center">
                                    <div class="text-xs font-semibold text-green-900 dark:text-green-100">3. Approved</div>
                                    <div class="text-xs text-green-700 dark:text-green-300 mt-1">Inventory approves</div>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 hidden lg:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="flex-1 min-w-[100px] bg-blue-100 dark:bg-blue-900 rounded-lg p-2 text-center">
                                    <div class="text-xs font-semibold text-blue-900 dark:text-blue-100">4. Dispatched</div>
                                    <div class="text-xs text-blue-700 dark:text-blue-300 mt-1">Items delivered</div>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 hidden lg:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="flex-1 min-w-[100px] bg-purple-100 dark:bg-purple-900 rounded-lg p-2 text-center">
                                    <div class="text-xs font-semibold text-purple-900 dark:text-purple-100">5. Completed</div>
                                    <div class="text-xs text-purple-700 dark:text-purple-300 mt-1">All items sent</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Meanings -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">📊 Request Status Meanings</h3>
                        </div>
                        <div class="p-4 space-y-2">
                            <div class="flex items-center justify-between p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                                <span class="text-sm font-medium text-yellow-900 dark:text-yellow-100">Pending</span>
                                <span class="text-xs text-yellow-700 dark:text-yellow-300">Awaiting inventory approval</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-green-50 dark:bg-green-900/20 rounded">
                                <span class="text-sm font-medium text-green-900 dark:text-green-100">Approved</span>
                                <span class="text-xs text-green-700 dark:text-green-300">All items approved, ready for dispatch</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-blue-50 dark:bg-blue-900/20 rounded">
                                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Partially Dispatched</span>
                                <span class="text-xs text-blue-700 dark:text-blue-300">Some items sent, remaining pending</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-purple-50 dark:bg-purple-900/20 rounded">
                                <span class="text-sm font-medium text-purple-900 dark:text-purple-100">Completed</span>
                                <span class="text-xs text-purple-700 dark:text-purple-300">All items dispatched successfully</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ITEM DISPATCHES MODULE -->
            <div id="dispatches" class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <span class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </span>
                    Item Dispatches
                </h2>
                
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 mb-4">
                    <p class="text-gray-700 dark:text-gray-300 text-sm">
                        <strong>Purpose:</strong> Inventory managers approve and dispatch items from approved requests. This module shows pending requests and allows managers to approve quantities (up to requested amount) and dispatch items, which reduces stock levels.
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
                                <button class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md whitespace-nowrap">Dispatch</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Open Dispatch Modal</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Opens modal showing all items in request with: Requested Qty, Approved Qty, Dispatched Qty, Remaining to Approve, Remaining to Dispatch, Stock Available. Two-step process: First approve quantities, then dispatch.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-md whitespace-nowrap">Approve All</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Approve All Items</button>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Auto-fills approval quantities to match remaining requested amounts for all items. Still requires clicking "Approve Items" button to submit. Validates stock availability before approval.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-purple-600 text-white text-sm rounded-md whitespace-nowrap">Approve Items</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Submit Approvals</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Validates and saves approval quantities. Updates request detail records. Blocks if insufficient stock. After approval, items can be dispatched.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <button class="px-3 py-1.5 bg-orange-600 text-white text-sm rounded-md whitespace-nowrap">Dispatch Items</button>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Dispatch Approved Items</button>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Dispatches all approved but not-yet-dispatched quantities. Creates ItemDispatch records, reduces stock levels, creates StockMovement (type: out). Updates request status to completed/partially_dispatched.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dispatch Process -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 border-b border-gray-200 dark:border-zinc-600">
                            <h3 class="font-semibold text-gray-900 dark:text-white">📋 Step-by-Step Dispatch Process</h3>
                        </div>
                        <div class="p-4">
                            <ol class="space-y-3 text-sm">
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-yellow-500 text-white rounded-full flex items-center justify-center text-xs mr-2">1</span>
                                    <div>
                                        <span class="font-medium">Click Dispatch on a pending request</span>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Modal opens showing all items with current approval/dispatch status</div>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-yellow-500 text-white rounded-full flex items-center justify-center text-xs mr-2">2</span>
                                    <div>
                                        <span class="font-medium">Review stock availability</span>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Check "Stock Available" column for each item. Warning shown if low stock.</div>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-yellow-500 text-white rounded-full flex items-center justify-center text-xs mr-2">3</span>
                                    <div>
                                        <span class="font-medium">Enter approval quantities</span>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Can approve full requested amount or partial. Cannot exceed requested qty or available stock.</div>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs mr-2">4</span>
                                    <div>
                                        <span class="font-medium">Click "Approve Items"</span>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Saves approvals. Request status updates to "approved" or "partially_dispatched"</div>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-2">5</span>
                                    <div>
                                        <span class="font-medium">Click "Dispatch Items"</span>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Dispatches all approved quantities. Stock is reduced, movement records created.</div>
                                    </div>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <!-- Important Notes -->
                    <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-orange-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div>
                                <div class="font-medium text-orange-900 dark:text-orange-100 text-sm">Important Rules:</div>
                                <ul class="text-xs text-orange-800 dark:text-orange-200 mt-1 space-y-1">
                                    <li>• Cannot approve more than requested quantity</li>
                                    <li>• Cannot approve more than available stock</li>
                                    <li>• Cannot dispatch unapproved items</li>
                                    <li>• Partial approvals/dispatches are allowed</li>
                                    <li>• Durable items (requires_request=true) MUST go through request flow</li>
                                    <li>• Stock movement is recorded at dispatch time, not approval</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADDITIONAL MODULES SUMMARY -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">📚 Additional Modules Summary</h2>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <!-- Stock Takes -->
                    <div class="bg-white dark:bg-zinc-700 rounded-lg p-4 border border-gray-200 dark:border-zinc-600">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Stock Takes</h3>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Physical inventory counting. Create stock take sessions, record physical counts, system calculates variances (surplus/shortage). Types: full, partial, cycle.</p>
                        <div class="text-xs text-gray-500 dark:text-gray-500"><strong>Key Actions:</strong> Create Stock Take, Enter Counts, Complete Stock Take, Export</div>
                    </div>

                    <!-- Health Checks -->
                    <div class="bg-white dark:bg-zinc-700 rounded-lg p-4 border border-gray-200 dark:border-zinc-600">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-pink-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Health Checks</h3>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Record item condition inspections. Fields: Stock, Check Date, Condition (good/fair/poor/damaged/expired), Quantity Affected, Observations, Action Taken.</p>
                        <div class="text-xs text-gray-500 dark:text-gray-500"><strong>Key Actions:</strong> Create Health Check, Filter by Condition, Export</div>
                    </div>

                    <!-- Analytics -->
                    <div class="bg-white dark:bg-zinc-700 rounded-lg p-4 border border-gray-200 dark:border-zinc-600">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-cyan-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Analytics</h3>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Dashboard with KPIs: Total Stock Value, Items Count, Purchase Metrics, Movement Analytics, Request Stats, Low Stock Alerts, Stock Health, Department Breakdown, Performance Metrics.</p>
                        <div class="text-xs text-gray-500 dark:text-gray-500"><strong>Key Features:</strong> Date Range Filters, Department Filter, Category Filter, Auto-refresh, Export</div>
                    </div>

                    <!-- Reports -->
                    <div class="bg-white dark:bg-zinc-700 rounded-lg p-4 border border-gray-200 dark:border-zinc-600">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Reports</h3>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Generate and view inventory reports. Stock Levels Report shows current stock status, variances, trends. Reports can be saved, submitted for review, and exported.</p>
                        <div class="text-xs text-gray-500 dark:text-gray-500"><strong>Report Types:</strong> Stock Levels, Reorder Reports, Movement Reports, Variance Reports</div>
                    </div>

                    <!-- Shift Closing -->
                    <div class="bg-white dark:bg-zinc-700 rounded-lg p-4 border border-gray-200 dark:border-zinc-600">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Shift Closing</h3>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">End-of-shift stock reconciliation. Compare expected vs actual stock, record variances, close shift. Creates stock movements for variances, notifies managers.</p>
                        <div class="text-xs text-gray-500 dark:text-gray-500"><strong>Process:</strong> Load Current Shift → Enter Actual Counts → Review Variances → Save Closing → Notify Manager</div>
                    </div>

                    <!-- Callbacks -->
                    <div class="bg-white dark:bg-zinc-700 rounded-lg p-4 border border-gray-200 dark:border-zinc-600">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-rose-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Callbacks</h3>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Production returns management. Raw materials returned from production or finished product rejects. Requires inventory approval before stock is updated.</p>
                        <div class="text-xs text-gray-500 dark:text-gray-500"><strong>Actions:</strong> View Details, Approve Callback, Reject Callback (with reason), Complete</div>
                    </div>
                </div>
            </div>

            <!-- APPROVAL SYSTEM -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <span class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </span>
                    Audit Approval System
                </h2>
                
                <div class="space-y-4">
                    <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-orange-200 dark:border-orange-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">🔐 How It Works</h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                            Non-super admin users require approval for critical inventory actions. When such users perform restricted actions, data is saved temporarily and an audit request is created for manager review.
                        </p>
                        <div class="grid md:grid-cols-2 gap-3">
                            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3">
                                <div class="font-medium text-orange-900 dark:text-orange-100 text-sm mb-2">Actions Requiring Approval:</div>
                                <ul class="text-xs text-orange-800 dark:text-orange-200 space-y-1">
                                    <li>• Create/Edit/Delete Items</li>
                                    <li>• Stock Adjustments</li>
                                    <li>• Purchase Orders (non-super admins)</li>
                                    <li>• Any action modifying inventory data</li>
                                </ul>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                                <div class="font-medium text-green-900 dark:text-green-100 text-sm mb-2">Approval Flow:</div>
                                <ol class="text-xs text-green-800 dark:text-green-200 space-y-1">
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
