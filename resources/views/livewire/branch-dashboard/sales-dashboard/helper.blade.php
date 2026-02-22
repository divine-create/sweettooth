<div>
    <x-breadcrumb title="Sales Helper" :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Sales Helper']]" :compact="false" :with-icons="true" />

    <div class="max-w-7xl mx-auto py-6">
        <div class="bg-white dark:bg-zinc-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <h1 class="ml-2 text-2xl font-medium text-gray-900 dark:text-white">
                        Sales Management Guide
                    </h1>
                </div>
                <p class="mt-6 text-gray-500 dark:text-gray-400 leading-relaxed">
                    Master the complete sales cycle with this interactive guide featuring animated workflows, POS operations, and inventory management integration.
                </p>
            </div>

            <!-- Sales Cycle Animation -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse"></div>
                    Complete Sales Cycle Flow
                </h2>

                <div class="bg-gradient-to-r from-green-50 via-blue-50 to-purple-50 dark:from-green-900/20 dark:via-blue-900/20 dark:to-purple-900/20 rounded-lg p-6">
                    <div class="flex flex-col lg:flex-row items-center justify-center space-y-6 lg:space-y-0 lg:space-x-8">
                        <!-- Preparation Phase -->
                        <div class="flex flex-col items-center animate-fade-in-left">
                            <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center text-white mb-3 shadow-lg animate-bounce">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-lg text-gray-900 dark:text-white">Preparation</div>
                                <div class="text-sm text-gray-600 dark:text-gray-300">Stock opening & setup</div>
                            </div>
                            <div class="mt-2 px-3 py-1 bg-green-100 dark:bg-green-800 rounded-full text-xs font-medium text-green-800 dark:text-green-200">
                                🏪 Opening
                            </div>
                        </div>

                        <!-- Animated Arrow -->
                        <div class="hidden lg:block animate-pulse">
                            <svg class="w-12 h-12 text-gray-400 animate-bounce" style="animation-delay: 0.5s" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>

                        <!-- Sales Phase -->
                        <div class="flex flex-col items-center animate-fade-in-up" style="animation-delay: 0.3s">
                            <div class="w-20 h-20 bg-blue-500 rounded-full flex items-center justify-center text-white mb-3 shadow-lg animate-pulse">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-lg text-gray-900 dark:text-white">Point of Sale</div>
                                <div class="text-sm text-gray-600 dark:text-gray-300">Customer transactions</div>
                            </div>
                            <div class="mt-2 px-3 py-1 bg-blue-100 dark:bg-blue-800 rounded-full text-xs font-medium text-blue-800 dark:text-blue-200">
                                💰 POS System
                            </div>
                        </div>

                        <!-- Animated Arrow -->
                        <div class="hidden lg:block animate-pulse">
                            <svg class="w-12 h-12 text-gray-400 animate-bounce" style="animation-delay: 0.7s" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>

                        <!-- Fulfillment Phase -->
                        <div class="flex flex-col items-center animate-fade-in-down" style="animation-delay: 0.5s">
                            <div class="w-20 h-20 bg-purple-500 rounded-full flex items-center justify-center text-white mb-3 shadow-lg animate-bounce">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-lg text-gray-900 dark:text-white">Fulfillment</div>
                                <div class="text-sm text-gray-600 dark:text-gray-300">Kitchen dispatch & delivery</div>
                            </div>
                            <div class="mt-2 px-3 py-1 bg-purple-100 dark:bg-purple-800 rounded-full text-xs font-medium text-purple-800 dark:text-purple-200">
                                🍽️ Dispatch
                            </div>
                        </div>

                        <!-- Animated Arrow -->
                        <div class="hidden lg:block animate-pulse">
                            <svg class="w-12 h-12 text-gray-400 animate-bounce" style="animation-delay: 0.9s" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>

                        <!-- Closing Phase -->
                        <div class="flex flex-col items-center animate-fade-in-right" style="animation-delay: 0.7s">
                            <div class="w-20 h-20 bg-orange-500 rounded-full flex items-center justify-center text-white mb-3 shadow-lg animate-pulse">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-lg text-gray-900 dark:text-white">Shift Closing</div>
                                <div class="text-sm text-gray-600 dark:text-gray-300">Reconciliation & reporting</div>
                            </div>
                            <div class="mt-2 px-3 py-1 bg-orange-100 dark:bg-orange-800 rounded-full text-xs font-medium text-orange-800 dark:text-orange-200">
                                📊 Closing
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Core Sales Features -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-3 animate-pulse"></div>
                    Core Sales Management Features
                </h2>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Stock Opening -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 rounded-xl p-6 border border-green-200 dark:border-green-700 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mr-4 animate-spin-slow">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Stock Opening</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">Initialize daily inventory levels and prepare for sales operations.</p>
                        <div class="space-y-2">
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-3 animate-ping"></span>
                                <span>Set opening stock quantities</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3 animate-ping" style="animation-delay: 0.2s"></span>
                                <span>Verify product availability</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3 animate-ping" style="animation-delay: 0.4s"></span>
                                <span>Record opening balances</span>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-white dark:bg-zinc-600 rounded-lg">
                            <div class="text-xs font-semibold text-gray-900 dark:text-white mb-2">🚀 Opening Checklist:</div>
                            <div class="grid grid-cols-2 gap-1 text-xs">
                                <div>• Count physical stock</div>
                                <div>• Update system</div>
                                <div>• Verify prices</div>
                                <div>• Check expiry dates</div>
                            </div>
                        </div>
                    </div>

                    <!-- Product List Management -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-xl p-6 border border-blue-200 dark:border-blue-700 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mr-4 animate-bounce">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Product Management</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">Configure products for sale with pricing, availability, and categorization.</p>
                        <div class="space-y-2">
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3 animate-pulse"></span>
                                <span>Set department-specific pricing</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-3 animate-pulse" style="animation-delay: 0.2s"></span>
                                <span>Control product availability</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3 animate-pulse" style="animation-delay: 0.4s"></span>
                                <span>Manage display order and categories</span>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-white dark:bg-zinc-600 rounded-lg">
                            <div class="text-xs font-semibold text-gray-900 dark:text-white mb-2">⚙️ Configuration Options:</div>
                            <div class="space-y-1 text-xs">
                                <div>• Department pricing</div>
                                <div>• Availability toggles</div>
                                <div>• Sort ordering</div>
                                <div>• Category assignment</div>
                            </div>
                        </div>
                    </div>

                    <!-- Point of Sale -->
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 rounded-xl p-6 border border-purple-200 dark:border-purple-700 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mr-4 animate-pulse">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Point of Sale</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">Complete customer transaction processing with integrated inventory updates.</p>
                        <div class="space-y-2">
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3 animate-bounce"></span>
                                <span>Add items to cart with quantities</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-3 animate-bounce" style="animation-delay: 0.2s"></span>
                                <span>Calculate totals and apply discounts</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3 animate-bounce" style="animation-delay: 0.4s"></span>
                                <span>Process payments and print receipts</span>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-white dark:bg-zinc-600 rounded-lg">
                            <div class="text-xs font-semibold text-gray-900 dark:text-white mb-2">💳 Transaction Flow:</div>
                            <div class="space-y-1 text-xs">
                                <div class="flex items-center">
                                    <span class="w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs mr-2">1</span>
                                    Select Products
                                </div>
                                <div class="flex items-center">
                                    <span class="w-4 h-4 bg-green-500 rounded-full flex items-center justify-center text-white text-xs mr-2">2</span>
                                    Process Payment
                                </div>
                                <div class="flex items-center">
                                    <span class="w-4 h-4 bg-purple-500 rounded-full flex items-center justify-center text-white text-xs mr-2">3</span>
                                    Update Inventory
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kitchen Dispatch Workflow -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                    <div class="w-3 h-3 bg-orange-500 rounded-full mr-3 animate-pulse"></div>
                    Kitchen Dispatch & Fulfillment Process
                </h2>

                <div class="bg-gradient-to-br from-orange-50 via-red-50 to-pink-50 dark:from-orange-900/20 dark:via-red-900/20 dark:to-pink-900/20 rounded-lg p-6">
                    <div class="flex flex-col lg:flex-row items-center justify-center space-y-8 lg:space-y-0 lg:space-x-12">
                        <!-- Order Placement -->
                        <div class="flex flex-col items-center text-center max-w-xs">
                            <div class="relative">
                                <div class="w-24 h-24 bg-orange-500 rounded-full flex items-center justify-center text-white mb-4 shadow-xl animate-pulse">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div class="absolute -top-2 -right-2 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center animate-ping">
                                    <span class="text-white text-xs font-bold">1</span>
                                </div>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2">Order Received</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">Customer places order through POS system</p>
                            <div class="bg-white dark:bg-zinc-700 rounded-lg p-3 w-full">
                                <div class="text-xs font-semibold mb-2">📝 Order Details:</div>
                                <ul class="text-xs space-y-1">
                                    <li>• Customer information</li>
                                    <li>• Ordered items & quantities</li>
                                    <li>• Special instructions</li>
                                    <li>• Order timestamp</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Animated Arrow -->
                        <div class="hidden lg:block animate-bounce">
                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>

                        <!-- Kitchen Preparation -->
                        <div class="flex flex-col items-center text-center max-w-xs">
                            <div class="relative">
                                <div class="w-24 h-24 bg-red-500 rounded-full flex items-center justify-center text-white mb-4 shadow-xl animate-pulse" style="animation-delay: 0.5s">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                                <div class="absolute -top-2 -right-2 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center animate-ping">
                                    <span class="text-white text-xs font-bold">2</span>
                                </div>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2">Kitchen Prep</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">Production team prepares ordered items</p>
                            <div class="bg-white dark:bg-zinc-700 rounded-lg p-3 w-full">
                                <div class="text-xs font-semibold mb-2">👨‍🍳 Preparation Steps:</div>
                                <ul class="text-xs space-y-1">
                                    <li>• Receive order notification</li>
                                    <li>• Gather ingredients</li>
                                    <li>• Prepare according to specs</li>
                                    <li>• Quality check completed</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Animated Arrow -->
                        <div class="hidden lg:block animate-bounce" style="animation-delay: 0.5s">
                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>

                        <!-- Dispatch & Delivery -->
                        <div class="flex flex-col items-center text-center max-w-xs">
                            <div class="relative">
                                <div class="w-24 h-24 bg-pink-500 rounded-full flex items-center justify-center text-white mb-4 shadow-xl animate-pulse" style="animation-delay: 1s">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <div class="absolute -top-2 -right-2 w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center animate-ping">
                                    <span class="text-white text-xs font-bold">3</span>
                                </div>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2">Dispatch</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">Items are dispatched to sales floor or customer</p>
                            <div class="bg-white dark:bg-zinc-700 rounded-lg p-3 w-full">
                                <div class="text-xs font-semibold mb-2">🚚 Dispatch Process:</div>
                                <ul class="text-xs space-y-1">
                                    <li>• Package completed items</li>
                                    <li>• Generate dispatch note</li>
                                    <li>• Update order status</li>
                                    <li>• Notify sales team</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Callbacks & Issue Resolution -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                    <div class="w-3 h-3 bg-red-500 rounded-full mr-3 animate-pulse"></div>
                    Callbacks & Quality Management
                </h2>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Product Callbacks -->
                    <div class="bg-gradient-to-br from-red-50 to-pink-50 dark:from-red-900/30 dark:to-pink-900/30 rounded-xl p-6 border border-red-200 dark:border-red-700">
                        <div class="flex items-center mb-4">
                            <div class="w-14 h-14 bg-red-500 rounded-xl flex items-center justify-center mr-4 animate-spin-slow">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l3-3m0 0l3 3m-3-3v6m4-13a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-xl text-gray-900 dark:text-white">Product Callbacks</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Handle returns and quality issues</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-700 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-red-500 rounded-full mr-3 animate-pulse"></div>
                                    <span class="text-sm font-medium">Quality Issues</span>
                                </div>
                                <span class="text-xs bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-200 px-2 py-1 rounded">Return to Kitchen</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-700 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full mr-3 animate-pulse" style="animation-delay: 0.3s"></div>
                                    <span class="text-sm font-medium">Customer Returns</span>
                                </div>
                                <span class="text-xs bg-yellow-100 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200 px-2 py-1 rounded">Refund Process</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-700 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-3 animate-pulse" style="animation-delay: 0.6s"></div>
                                    <span class="text-sm font-medium">Stock Adjustments</span>
                                </div>
                                <span class="text-xs bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">Inventory Update</span>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics & Reporting -->
                    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-900/30 dark:to-blue-900/30 rounded-xl p-6 border border-indigo-200 dark:border-indigo-700">
                        <div class="flex items-center mb-4">
                            <div class="w-14 h-14 bg-indigo-500 rounded-xl flex items-center justify-center mr-4 animate-bounce">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-xl text-gray-900 dark:text-white">Sales Analytics</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Performance insights and reporting</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="bg-white dark:bg-zinc-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                                    <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                    Revenue Trends
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Track sales performance over time</p>
                            </div>

                            <div class="bg-white dark:bg-zinc-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                                    <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                    Product Performance
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Best-selling items and categories</p>
                            </div>

                            <div class="bg-white dark:bg-zinc-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                                    <svg class="w-4 h-4 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Shift Performance
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Staff productivity and efficiency metrics</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Tips & Best Practices -->
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                    <div class="w-3 h-3 bg-indigo-500 rounded-full mr-3 animate-pulse"></div>
                    Sales Excellence Tips
                </h2>

                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6 border border-green-200 dark:border-green-700">
                        <h3 class="font-bold text-lg text-green-600 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Best Practices
                        </h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                            <li class="flex items-start">
                                <span class="w-2 h-2 bg-green-500 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                Always perform stock opening counts
                            </li>
                            <li class="flex items-start">
                                <span class="w-2 h-2 bg-green-500 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                Verify product availability before sales
                            </li>
                            <li class="flex items-start">
                                <span class="w-2 h-2 bg-green-500 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                Maintain accurate transaction records
                            </li>
                            <li class="flex items-start">
                                <span class="w-2 h-2 bg-green-500 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                Process callbacks promptly
                            </li>
                        </ul>
                    </div>

                    <div class="bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-lg p-6 border border-yellow-200 dark:border-yellow-700">
                        <h3 class="font-bold text-lg text-yellow-600 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            Common Issues
                        </h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                            <li class="flex items-start">
                                <span class="w-2 h-2 bg-yellow-500 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                Stock discrepancies during closing
                            </li>
                            <li class="flex items-start">
                                <span class="w-2 h-2 bg-yellow-500 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                Delayed production dispatch notifications
                            </li>
                            <li class="flex items-start">
                                <span class="w-2 h-2 bg-yellow-500 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                Incomplete customer order information
                            </li>
                            <li class="flex items-start">
                                <span class="w-2 h-2 bg-yellow-500 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                Payment processing errors
                            </li>
                        </ul>
                    </div>

                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-700">
                        <h3 class="font-bold text-lg text-blue-600 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Performance Goals
                        </h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                            <li class="flex items-start">
                                <span class="w-2 h-2 bg-blue-500 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                Average transaction time: < 3 minutes
                            </li>
                            <li class="flex items-start">
                                <span class="w-2 h-2 bg-blue-500 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                Order accuracy: > 98%
                            </li>
                            <li class="flex items-start">
                                <span class="w-2 h-2 bg-blue-500 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                Customer satisfaction: > 90%
                            </li>
                            <li class="flex items-start">
                                <span class="w-2 h-2 bg-blue-500 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                Inventory accuracy: > 95%
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Interactive Workflow Summary -->
                <div class="mt-8 bg-gradient-to-r from-purple-50 via-pink-50 to-red-50 dark:from-purple-900/20 dark:via-pink-900/20 dark:to-red-900/20 rounded-lg p-6 border border-purple-200 dark:border-purple-700">
                    <h3 class="font-bold text-xl text-gray-900 dark:text-white mb-4 text-center">🎯 Daily Sales Workflow Summary</h3>
                    <div class="flex flex-wrap justify-center items-center gap-4 text-sm">
                        <div class="flex items-center bg-white dark:bg-zinc-700 rounded-lg px-4 py-2 shadow">
                            <span class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white font-bold mr-3">1</span>
                            <span>Stock Opening</span>
                        </div>
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <div class="flex items-center bg-white dark:bg-zinc-700 rounded-lg px-4 py-2 shadow">
                            <span class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold mr-3">2</span>
                            <span>POS Operations</span>
                        </div>
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <div class="flex items-center bg-white dark:bg-zinc-700 rounded-lg px-4 py-2 shadow">
                            <span class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-white font-bold mr-3">3</span>
                            <span>Kitchen Dispatch</span>
                        </div>
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <div class="flex items-center bg-white dark:bg-zinc-700 rounded-lg px-4 py-2 shadow">
                            <span class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center text-white font-bold mr-3">4</span>
                            <span>Shift Closing</span>
                        </div>
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <div class="flex items-center bg-white dark:bg-zinc-700 rounded-lg px-4 py-2 shadow">
                            <span class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold mr-3">5</span>
                            <span>Reports & Analytics</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes fade-in-left {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fade-in-right {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fade-in-up {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fade-in-down {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin-slow {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .animate-fade-in-left {
            animation: fade-in-left 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-fade-in-right {
            animation: fade-in-right 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-fade-in-up {
            animation: fade-in-up 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-fade-in-down {
            animation: fade-in-down 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-spin-slow {
            animation: spin-slow 3s linear infinite;
        }
    </style>
</div>
