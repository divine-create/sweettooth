<div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Employee Appraisals</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Evaluate and provide feedback to employees</p>
                </div>
                <button wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center disabled:opacity-60 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="exportCsv" class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </span>
                    <span wire:loading wire:target="exportCsv" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Exporting...
                    </span>
                </button>
            </div>
        </div>
        
        <!-- Quick Create Appraisal Section -->
        <div class="px-6 py-4 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-medium text-blue-800 dark:text-blue-200">Start New Appraisal</h3>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">Select an employee to begin their performance evaluation</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2">
                    @if($employees->count() > 0)
                        <select wire:model="selectedEmployeeForAppraisal" 
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 min-w-[200px]">
                            <option value="">Select Employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->email }})</option>
                            @endforeach
                        </select>
                        <button wire:click="startAppraisal" 
                                :disabled="!selectedEmployeeForAppraisal"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Start Appraisal
                        </button>
                    @else
                        <p class="text-sm text-blue-700 dark:text-blue-300 italic">No employees available to appraise</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Search Employees</label>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Search by name or email..."
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Department</label>
                    <select wire:model.live="departmentFilter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button wire:click="$set('search', ''); $set('departmentFilter', '')"
                            class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Employees List -->
         <div class="p-6">
             @if($employees->isEmpty())
                 <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                     <p class="text-sm text-blue-800 dark:text-blue-300">
                         <strong>No employees found</strong> - There are no employees available for appraisal in this branch. Employees assigned to this branch will appear here.
                     </p>
                 </div>
             @endif
             <div class="flex flex-col space-y-4">
                @forelse($employees as $employee)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                            {{ substr($employee->name, 0, 1) }}
                                        </div>
                                         <div class="ml-4 min-w-0 flex-1">
                                             <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $employee->name }}</h3>
                                             <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $employee->email }}</p>
                                         </div>
                                    </div>

                                    <div class="mt-4 space-y-2">
                                         <div class="flex items-center justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Department:</span>
                                             <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                 {{ $employee->department ? $employee->department->name : 'Not Assigned' }}
                                             </span>
                                         </div>

                                         <div class="flex items-center justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Role:</span>
                                             <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                 {{ $employee->roles->first()?->name ?? 'No Role' }}
                                             </span>
                                         </div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Current Rating:</span>
                                            <div class="flex items-center">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <svg class="w-4 h-4 {{ $i <= ($employee->performance_rating ?? 0) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                    </svg>
                                                @endfor
                                                <span class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $employee->performance_rating ?? 'Not Rated' }}
                                                </span>
                                            </div>
                                        </div>

                                        @if($employee->last_performance_review_date)
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">Last Review:</span>
                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    @if($employee->last_performance_review_date instanceof \Carbon\Carbon)
                                                        {{ $employee->last_performance_review_date->format('M d, Y') }}
                                                    @else
                                                        {{ \Carbon\Carbon::parse($employee->last_performance_review_date)->format('M d, Y') }}
                                                    @endif
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                             <div class="mt-6 flex gap-2">
                                @can('manage-organization')
                                 <a href="{{ route('branch-dashboard.appraise-employee', ['employee' => $employee->id, 'b_id' => request()->query('b_id')]) }}" class="flex-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition text-center">
                                     Appraise
                                 </a>
                                 @endcan
                                 <a href="{{ route('branch-dashboard.employee-appraisal-history', ['employee' => $employee->id, 'b_id' => request()->query('b_id')]) }}" class="flex-1 px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition text-center">
                                     History
                                 </a>
                            </div>
                        </div>
                    </div>
                 @empty
                     <div class="text-center py-12">
                         <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 016 0z" />
                         </svg>
                         <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-gray-100">No employees found</h3>
                         <p class="mt-1 text-gray-600 dark:text-gray-400">Try adjusting your search or filter criteria.</p>
                     </div>
                 @endforelse
            </div>
        </div>

        <!-- Pagination -->
        @if($employees->hasPages())
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-700">
                {{ $employees->links() }}
            </div>
        @endif
    </div>



    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-md shadow-lg z-50">
            {{ session('error') }}
        </div>
    @endif

    @if (session()->has('info'))
        <div class="fixed bottom-4 right-4 bg-blue-500 text-white px-6 py-3 rounded-md shadow-lg z-50">
            {{ session('info') }}
        </div>
    @endif


</div>
