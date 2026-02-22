<div>
    <x-breadcrumb title="Production Helper" :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Production Helper']]" :compact="false" :with-icons="true" />

    <div class="max-w-7xl mx-auto py-6">
        <div class="bg-white dark:bg-zinc-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <h1 class="ml-2 text-2xl font-medium text-gray-900 dark:text-white">
                        Production Management Guide
                    </h1>
                </div>
                <p class="mt-6 text-gray-500 dark:text-gray-400 leading-relaxed">
                    Master the complete production lifecycle with this interactive guide featuring animated workflows, recipe management, and quality control processes.
                </p>
            </div>

            <!-- Production Lifecycle Animation -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <div class="w-3 h-3 bg-orange-500 rounded-full mr-3 animate-pulse"></div>
                    Production Lifecycle Flow
                </h2>

                <div class="bg-gradient-to-r from-orange-50 via-red-50 to-pink-50 dark:from-orange-900/20 dark:via-red-900/20 dark:to-pink-900/20 rounded-lg p-6">
                    <div class="flex flex-col lg:flex-row items-center justify-center space-y-6 lg:space-y-0 lg:space-x-8">
                        <!-- Planning Phase -->
                        <div class="flex flex-col items-center animate-fade-in-left">
                            <div class="w-20 h-20 bg-orange-500 rounded-full flex items-center justify-center text-white mb-3 shadow-lg animate-bounce">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-lg text-gray-900 dark:text-white">Planning</div>
                                <div class="text-sm text-gray-600 dark:text-gray-300">Recipe development & scheduling</div>
                            </div>
                            <div class="mt-2 px-3 py-1 bg-orange-100 dark:bg-orange-800 rounded-full text-xs font-medium text-orange-800 dark:text-orange-200">
                                📋 Recipes
                            </div>
                        </div>

                        <!-- Animated Arrow -->
                        <div class="hidden lg:block animate-pulse">
                            <svg class="w-12 h-12 text-gray-400 animate-bounce" style="animation-delay: 0.5s" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>

                        <!-- Production Phase -->
                        <div class="flex flex-col items-center animate-fade-in-up" style="animation-delay: 0.3s">
                            <div class="w-20 h-20 bg-red-500 rounded-full flex items-center justify-center text-white mb-3 shadow-lg animate-pulse">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-lg text-gray-900 dark:text-white">Production</div>
                                <div class="text-sm text-gray-600 dark:text-gray-300">Batching & quality control</div>
                            </div>
                            <div class="mt-2 px-3 py-1 bg-red-100 dark:bg-red-800 rounded-full text-xs font-medium text-red-800 dark:text-red-200">
                                🏭 Manufacturing
                            </div>
                        </div>

                        <!-- Animated Arrow -->
                        <div class="hidden lg:block animate-pulse">
                            <svg class="w-12 h-12 text-gray-400 animate-bounce" style="animation-delay: 0.7s" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>

                        <!-- Quality Control Phase -->
                        <div class="flex flex-col items-center animate-fade-in-down" style="animation-delay: 0.5s">
                            <div class="w-20 h-20 bg-pink-500 rounded-full flex items-center justify-center text-white mb-3 shadow-lg animate-bounce">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-lg text-gray-900 dark:text-white">Quality Control</div>
                                <div class="text-sm text-gray-600 dark:text-gray-300">Inspection & approval</div>
                            </div>
                            <div class="mt-2 px-3 py-1 bg-pink-100 dark:bg-pink-800 rounded-full text-xs font-medium text-pink-800 dark:text-pink-200">
                                ✅ QC Check
                            </div>
                        </div>

                        <!-- Animated Arrow -->
                        <div class="hidden lg:block animate-pulse">
                            <svg class="w-12 h-12 text-gray-400 animate-bounce" style="animation-delay: 0.9s" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>

                        <!-- Dispatch Phase -->
                        <div class="flex flex-col items-center animate-fade-in-right" style="animation-delay: 0.7s">
                            <div class="w-20 h-20 bg-purple-500 rounded-full flex items-center justify-center text-white mb-3 shadow-lg animate-pulse">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-lg text-gray-900 dark:text-white">Dispatch</div>
                                <div class="text-sm text-gray-600 dark:text-gray-300">Delivery to sales floor</div>
                            </div>
                            <div class="mt-2 px-3 py-1 bg-purple-100 dark:bg-purple-800 rounded-full text-xs font-medium text-purple-800 dark:text-purple-200">
                                🚚 Ready for Sale
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Core Production Features -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                    <div class="w-3 h-3 bg-red-500 rounded-full mr-3 animate-pulse"></div>
                    Core Production Features
                </h2>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Recipe Management -->
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 rounded-xl p-6 border border-orange-200 dark:border-orange-700 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mr-4 animate-spin-slow">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Recipe Management</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">Create and manage production recipes with ingredient tracking and cost calculations.</p>
                        <div class="space-y-2">
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3 animate-ping"></span>
                                <span>Define ingredient quantities & costs</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-3 animate-ping" style="animation-delay: 0.2s"></span>
                                <span>Calculate production yields</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-pink-500 rounded-full mr-3 animate-ping" style="animation-delay: 0.4s"></span>
                                <span>Set quality standards</span>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-white dark:bg-zinc-600 rounded-lg">
                            <div class="text-xs font-semibold text-gray-900 dark:text-white mb-2">📋 Recipe Components:</div>
                            <div class="grid grid-cols-2 gap-1 text-xs">
                                <div>• Ingredients</div>
                                <div>• Quantities</div>
                                <div>• Instructions</div>
                                <div>• Cost Analysis</div>
                            </div>
                        </div>
                    </div>

                    <!-- Daily Production -->
                    <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 rounded-xl p-6 border border-red-200 dark:border-red-700 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-red-500 rounded-xl flex items-center justify-center mr-4 animate-bounce">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Daily Production</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">Track daily production activities, monitor progress, and manage batch production.</p>
                        <div class="space-y-2">
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-3 animate-pulse"></span>
                                <span>Schedule production batches</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3 animate-pulse" style="animation-delay: 0.2s"></span>
                                <span>Record production quantities</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-pink-500 rounded-full mr-3 animate-pulse" style="animation-delay: 0.4s"></span>
                                <span>Track quality metrics</span>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-white dark:bg-zinc-600 rounded-lg">
                            <div class="text-xs font-semibold text-gray-900 dark:text-white mb-2">🏭 Production Metrics:</div>
                            <div class="space-y-1 text-xs">
                                <div class="flex justify-between">
                                    <span>Planned:</span>
                                    <span class="font-bold text-blue-600">100 units</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Produced:</span>
                                    <span class="font-bold text-green-600">95 units</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Quality Rate:</span>
                                    <span class="font-bold text-purple-600">98%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quality Control -->
                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 dark:from-pink-900/30 dark:to-pink-800/30 rounded-xl p-6 border border-pink-200 dark:border-pink-700 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-pink-500 rounded-xl flex items-center justify-center mr-4 animate-pulse">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Quality Control</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">Implement quality assurance processes and maintain production standards.</p>
                        <div class="space-y-2">
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-pink-500 rounded-full mr-3 animate-bounce"></span>
                                <span>Define quality checkpoints</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-3 animate-bounce" style="animation-delay: 0.2s"></span>
                                <span>Record inspection results</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3 animate-bounce" style="animation-delay: 0.4s"></span>
                                <span>Manage non-conformance</span>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-white dark:bg-zinc-600 rounded-lg">
                            <div class="text-xs font-semibold text-gray-900 dark:text-white mb-2">✅ Quality Gates:</div>
                            <div class="space-y-1 text-xs">
                                <div class="flex items-center">
                                    <span class="w-4 h-4 bg-green-500 rounded-full flex items-center justify-center text-white text-xs mr-2">✓</span>
                                    Raw Materials
                                </div>
                                <div class="flex items-center">
                                    <span class="w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs mr-2">✓</span>
                                    In-Process
                                </div>
                                <div class="flex items-center">
                                    <span class="w-4 h-4 bg-purple-500 rounded-full flex items-center justify-center text-white text-xs mr-2">✓</span>
                                    Final Product
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recipe Creation Workflow -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                    <div class="w-3 h-3 bg-purple-500 rounded-full mr-3 animate-pulse"></div>
                    Recipe Development Process
                </h2>

                <div class="bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50 dark:from-purple-900/20 dark:via-indigo-900/20 dark:to-blue-900/20 rounded-lg p-6">
                    <div class="flex flex-col lg:flex-row items-center justify-center space-y-8 lg:space-y-0 lg:space-x-12">
                        <!-- Product Selection -->
                        <div class="flex flex-col items-center text-center max-w-xs">
                            <div class="relative">
                                <div class="w-24 h-24 bg-purple-500 rounded-full flex items-center justify-center text-white mb-4 shadow-xl animate-pulse">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                </div>
                                <div class="absolute -top-2 -right-2 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center animate-ping">
                                    <span class="text-white text-xs font-bold">1</span>
                                </div>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2">Select Product</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">Choose the product to create a recipe for</p>
                            <div class="bg-white dark:bg-zinc-700 rounded-lg p-3 w-full">
                                <div class="text-xs font-semibold mb-2">🏷️ Product Details:</div>
                                <ul class="text-xs space-y-1">
                                    <li>• Product name & type</li>
                                    <li>• Expected yield</li>
                                    <li>• Unit of measure</li>
                                    <li>• Department assignment</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Animated Arrow -->
                        <div class="hidden lg:block animate-bounce">
                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>

                        <!-- Ingredient Setup -->
                        <div class="flex flex-col items-center text-center max-w-xs">
                            <div class="relative">
                                <div class="w-24 h-24 bg-indigo-500 rounded-full flex items-center justify-center text-white mb-4 shadow-xl animate-pulse" style="animation-delay: 0.5s">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <div class="absolute -top-2 -right-2 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center animate-ping">
                                    <span class="text-white text-xs font-bold">2</span>
                                </div>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2">Add Ingredients</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">Specify ingredients, quantities, and costs</p>
                            <div class="bg-white dark:bg-zinc-700 rounded-lg p-3 w-full">
                                <div class="text-xs font-semibold mb-2">🥕 Ingredient List:</div>
                                <ul class="text-xs space-y-1">
                                    <li>• Ingredient selection</li>
                                    <li>• Quantity per batch</li>
                                    <li>• Unit cost calculation</li>
                                    <li>• Total cost analysis</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Animated Arrow -->
                        <div class="hidden lg:block animate-bounce" style="animation-delay: 0.5s">
                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>

                        <!-- Instructions & Testing -->
                        <div class="flex flex-col items-center text-center max-w-xs">
                            <div class="relative">
                                <div class="w-24 h-24 bg-blue-500 rounded-full flex items-center justify-center text-white mb-4 shadow-xl animate-pulse" style="animation-delay: 1s">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div class="absolute -top-2 -right-2 w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center animate-ping">
                                    <span class="text-white text-xs font-bold">3</span>
                                </div>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2">Production Steps</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">Document preparation instructions and quality standards</p>
                            <div class="bg-white dark:bg-zinc-700 rounded-lg p-3 w-full">
                                <div class="text-xs font-semibold mb-2">📝 Documentation:</div>
                                <ul class="text-xs space-y-1">
                                    <li>• Step-by-step instructions</li>
                                    <li>• Quality control points</li>
                                    <li>• Safety precautions</li>
                                    <li>• Equipment requirements</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics & Reporting -->
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                    <div class="w-3 h-3 bg-indigo-500 rounded-full mr-3 animate-pulse"></div>
                    Production Analytics & Reporting
                </h2>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Production Reports -->
                    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-900/30 dark:to-blue-900/30 rounded-xl p-6 border border-indigo-200 dark:border-indigo-700">
                        <div class="flex items-center mb-4">
                            <div class="w-14 h-14 bg-indigo-500 rounded-xl flex items-center justify-center mr-4 animate-spin-slow">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-xl text-gray-900 dark:text-white">Production Reports</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Comprehensive production analytics and performance metrics</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-700 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse"></div>
                                    <span class="text-sm font-medium">Efficiency Rate</span>
                                </div>
                                <span class="text-xs bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200 px-2 py-1 rounded">94.2%</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-700 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-3 animate-pulse" style="animation-delay: 0.3s"></div>
                                    <span class="text-sm font-medium">Yield Variance</span>
                                </div>
                                <span class="text-xs bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">+2.1%</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-700 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-purple-500 rounded-full mr-3 animate-pulse" style="animation-delay: 0.6s"></div>
                                    <span class="text-sm font-medium">Quality Score</span>
                                </div>
                                <span class="text-xs bg-purple-100 dark:bg-purple-800 text-purple-800 dark:text-purple-200 px-2 py-1 rounded">96.8%</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-700 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-orange-500 rounded-full mr-3 animate-pulse" style="animation-delay: 0.9s"></div>
                                    <span class="text-sm font-medium">Cost Efficiency</span>
                                </div>
                                <span class="text-xs bg-orange-100 dark:bg-orange-800 text-orange-800 dark:text-orange-200 px-2 py-1 rounded">98.3%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Department Performance -->
                    <div class="bg-gradient-to-br from-teal-50 to-cyan-50 dark:from-teal-900/30 dark:to-cyan-900/30 rounded-xl p-6 border border-teal-200 dark:border-teal-700">
                        <div class="flex items-center mb-4">
                            <div class="w-14 h-14 bg-teal-500 rounded-xl flex items-center justify-center mr-4 animate-bounce">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-xl text-gray-900 dark:text-white">Department Performance</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Track performance across different production departments</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="bg-white dark:bg-zinc-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                                    <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                    Baking Department
                                </h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>Output: <span class="font-bold text-green-600">245 units</span></div>
                                    <div>Efficiency: <span class="font-bold text-blue-600">96%</span></div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-zinc-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                                    <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    Confectionery
                                </h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>Output: <span class="font-bold text-green-600">189 units</span></div>
                                    <div>Efficiency: <span class="font-bold text-blue-600">92%</span></div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-zinc-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                                    <svg class="w-4 h-4 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"></path>
                                    </svg>
                                    Gelato Production
                                </h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>Output: <span class="font-bold text-green-600">156 units</span></div>
                                    <div>Efficiency: <span class="font-bold text-blue-600">98%</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Best Practices & Tips -->
                <div class="mt-8 bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-800 dark:to-blue-900/20 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="font-bold text-xl text-gray-900 dark:text-white mb-4 text-center">🎯 Production Excellence Guidelines</h3>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="bg-white dark:bg-zinc-700 rounded-lg p-4">
                            <h4 class="font-semibold text-green-600 mb-2 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Quality Standards
                            </h4>
                            <ul class="text-sm space-y-1 text-gray-600 dark:text-gray-300">
                                <li>• Consistent ingredient measurements</li>
                                <li>• Temperature control protocols</li>
                                <li>• Hygiene and safety compliance</li>
                                <li>• Regular equipment calibration</li>
                            </ul>
                        </div>

                        <div class="bg-white dark:bg-zinc-700 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-600 mb-2 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Efficiency Tips
                            </h4>
                            <ul class="text-sm space-y-1 text-gray-600 dark:text-gray-300">
                                <li>• Batch production optimization</li>
                                <li>• Minimize setup times</li>
                                <li>• Cross-train staff members</li>
                                <li>• Regular maintenance schedules</li>
                            </ul>
                        </div>

                        <div class="bg-white dark:bg-zinc-700 rounded-lg p-4">
                            <h4 class="font-semibold text-purple-600 mb-2 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Performance Goals
                            </h4>
                            <ul class="text-sm space-y-1 text-gray-600 dark:text-gray-300">
                                <li>• 95%+ production efficiency</li>
                                <li>• < 2% waste reduction target</li>
                                <li>• 98% quality acceptance rate</li>
                                <li>• On-time delivery: 99%</li>
                            </ul>
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