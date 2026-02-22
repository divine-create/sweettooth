<div class="space-y-6">
    @php
        $productionDepartments = \App\Services\SidebarVisibilityService::getProductionDepartments(auth()->user());
        $defaultProductionSlug = $deptSlug ?? ($userDepartment?->slug ?? ($productionDepartments->first()?->slug ?? null));
    @endphp

    <!-- Page Title with Department Badge -->
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold text-gray-900">Production Dashboard</h1>
            @if($deptSlug)
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                    {{ ucwords(str_replace('-', ' ', $deptSlug)) }}
                </span>
            @endif
        </div>
        <p class="text-gray-600 mt-2">Monitor production queue, recipes, and team status</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 flex flex-wrap gap-4 items-center justify-between">
        <div class="flex gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Production Department</label>
                <select wire:model="deptSlug" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">My Department</option>
                    @foreach($productionDepartments as $dept)
                        <option value="{{ $dept->slug }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex gap-2">
            <button wire:click="$refresh" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Refresh
            </button>
        </div>
    </div>

    <!-- Department Navigation Links -->
    @if($defaultProductionSlug)
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">
                {{ ucwords(str_replace('-', ' ', $deptSlug ?? ($userDepartment?->name ?? 'Production'))) }} Module
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Products -->
                <a href="{{ route('branch-dashboard.production.products', $defaultProductionSlug) }}" 
                   class="p-4 border border-gray-200 rounded-lg hover:shadow-md hover:border-blue-300 transition flex items-center gap-3">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1h7.586a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM5 16a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Products</p>
                        <p class="text-sm text-gray-600">Manage products</p>
                    </div>
                </a>

                <!-- Product Types -->
                <a href="{{ route('branch-dashboard.production.product-types', $defaultProductionSlug) }}" 
                   class="p-4 border border-gray-200 rounded-lg hover:shadow-md hover:border-blue-300 transition flex items-center gap-3">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Product Types</p>
                        <p class="text-sm text-gray-600">Categorize products</p>
                    </div>
                </a>

                <!-- Recipes -->
                <a href="{{ route('branch-dashboard.production.recipes.index', $deptSlug ?? ($userDepartment?->slug ?? 'kitchen')) }}" 
                   class="p-4 border border-gray-200 rounded-lg hover:shadow-md hover:border-blue-300 transition flex items-center gap-3">
                    <div class="p-2 bg-orange-100 rounded-lg">
                        <svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Recipes</p>
                        <p class="text-sm text-gray-600">View recipes</p>
                    </div>
                </a>

                <!-- Production Requests -->
                <a href="{{ route('branch-dashboard.production.request.index', $deptSlug ?? ($userDepartment?->slug ?? 'kitchen')) }}" 
                   class="p-4 border border-gray-200 rounded-lg hover:shadow-md hover:border-blue-300 transition flex items-center gap-3">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H7a1 1 0 01-1-1v-6z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Production Requests</p>
                        <p class="text-sm text-gray-600">View requests</p>
                    </div>
                </a>

                <!-- Daily Produce -->
                <a href="{{ route('branch-dashboard.production.daily-produce.index', $deptSlug ?? ($userDepartment?->slug ?? 'kitchen')) }}" 
                   class="p-4 border border-gray-200 rounded-lg hover:shadow-md hover:border-blue-300 transition flex items-center gap-3">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Daily Produce</p>
                        <p class="text-sm text-gray-600">Track daily output</p>
                    </div>
                </a>

                <!-- Raw Material Tracking -->
                <a href="{{ route('branch-dashboard.production.raw-material-tracking') }}" 
                   class="p-4 border border-gray-200 rounded-lg hover:shadow-md hover:border-blue-300 transition flex items-center gap-3">
                    <div class="p-2 bg-indigo-100 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h12a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Raw Material Tracking</p>
                        <p class="text-sm text-gray-600">Track materials</p>
                    </div>
                </a>

                <!-- Shift Closing -->
                <a href="{{ route('branch-dashboard.production.shift-closing.index', $deptSlug ?? ($userDepartment?->slug ?? 'kitchen')) }}" 
                   class="p-4 border border-gray-200 rounded-lg hover:shadow-md hover:border-blue-300 transition flex items-center gap-3">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Shift Closing</p>
                        <p class="text-sm text-gray-600">Close shifts</p>
                    </div>
                </a>


                <!-- Production Callbacks -->
                <a href="{{ route('branch-dashboard.production.callbacks.index') }}" 
                   class="p-4 border border-gray-200 rounded-lg hover:shadow-md hover:border-blue-300 transition flex items-center gap-3">
                    <div class="p-2 bg-pink-100 rounded-lg">
                        <svg class="w-5 h-5 text-pink-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Production Callbacks</p>
                        <p class="text-sm text-gray-600">View callbacks</p>
                    </div>
                </a>

            </div>
        </div>
    @endif

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Today's Queue -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Today's Queue</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $queueCount ?? '0' }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v2h2a2 2 0 012 2v9a2 2 0 01-2 2h-2v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2H2a2 2 0 01-2-2V7a2 2 0 012-2h2V5z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Completed Today -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Completed Today</p>
                    <p class="text-2xl font-bold text-green-600 mt-2">{{ $completedCount ?? '0' }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- In Progress -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">In Progress</p>
                    <p class="text-2xl font-bold text-orange-600 mt-2">{{ $inProgressCount ?? '0' }}</p>
                </div>
                <div class="p-3 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Staff on Duty -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Staff on Duty</p>
                    <p class="text-2xl font-bold text-purple-600 mt-2">{{ $staffOnDuty ?? '0' }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM9 6a3 3 0 00-6 0v1H3a2 2 0 00-2 2v1a2 2 0 002 2h12a2 2 0 002-2v-1a2 2 0 00-2-2h-3V6z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline View -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Production Timeline</h3>
        <div class="space-y-4">
            <div class="flex gap-4">
                <div class="w-24 text-sm font-medium text-gray-600">06:00 AM</div>
                <div class="flex-1 border-l-4 border-green-500 pl-4 pb-4">
                    <p class="font-medium text-gray-900">Morning Batch - Sourdough</p>
                    <p class="text-sm text-gray-600">Oven ready • 24 loaves produced</p>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="w-24 text-sm font-medium text-gray-600">10:00 AM</div>
                <div class="flex-1 border-l-4 border-blue-500 pl-4 pb-4">
                    <p class="font-medium text-gray-900">Gelato Production</p>
                    <p class="text-sm text-gray-600">In progress • Vanilla & Chocolate</p>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="w-24 text-sm font-medium text-gray-600">02:00 PM</div>
                <div class="flex-1 border-l-4 border-yellow-500 pl-4 pb-4">
                    <p class="font-medium text-gray-900">Afternoon Batch - Croissants</p>
                    <p class="text-sm text-gray-600">Pending • Est. 30 units</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Production Queue -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Production Queue</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-900">Recipe</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-900">Quantity</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-900">Status</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-900">Assigned To</th>
                        <th class="px-4 py-2 text-center font-medium text-gray-900">Est. Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-900">Sourdough Bread</td>
                        <td class="px-4 py-3 text-right text-gray-600">24 units</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">Completed</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">John Smith</td>
                        <td class="px-4 py-3 text-center text-gray-600">2h 15m</td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-900">Vanilla Gelato</td>
                        <td class="px-4 py-3 text-right text-gray-600">15 L</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium">In Progress</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">Maria Garcia</td>
                        <td class="px-4 py-3 text-center text-gray-600">45m</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
