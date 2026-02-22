<div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Appraisal History</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Review past performance evaluations for {{ $employee->name }}</p>
                </div>
                <a href="{{ route('branch-dashboard.employee-appraisals', ['b_id' => $b_id]) }}" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition">
                    ← Back to Employees
                </a>
            </div>
        </div>

        <!-- Employee Info -->
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-600">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Employee Name</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $employee->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Email</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $employee->email }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Department</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $employee->department?->name ?? 'Not Assigned' }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Role</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $employee->roles->first()?->name ?? 'No Role' }}</p>
                </div>
            </div>
        </div>

        <!-- Appraisals List -->
        <div class="p-6">
            @if($appraisals->isEmpty())
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-gray-100">No formal appraisals found</h3>
                    <p class="mt-1 text-gray-600 dark:text-gray-400">This employee has not been formally evaluated yet.</p>
                    @if($currentRating)
                        <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Current Performance Summary</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Current Rating</p>
                                    <div class="flex items-center mt-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-6 h-6 {{ $i <= $currentRating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                        <span class="ml-2 text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $currentRating }}/5</span>
                                    </div>
                                </div>
                                @if($lastReviewDate)
                                    <div>
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Last Review Date</p>
                                        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                            @if($lastReviewDate instanceof \Carbon\Carbon)
                                                {{ $lastReviewDate->format('M d, Y') }}
                                            @else
                                                {{ \Carbon\Carbon::parse($lastReviewDate)->format('M d, Y') }}
                                            @endif
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="space-y-6">
                    @foreach($appraisals as $appraisal)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $appraisal->appraisalCycle->name ?? 'Unknown Cycle' }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        Completed on {{ $appraisal->completed_at?->format('M d, Y') ?? $appraisal->created_at->format('M d, Y') }}
                                    </p>
                                </div>
                                <span class="px-3 py-1 text-sm rounded-full
                                    @if($appraisal->status == 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                    @elseif($appraisal->status == 'draft') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                    @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 @endif">
                                    {{ ucfirst($appraisal->status) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Rating Section -->
                                @if($appraisal->final_rating)
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Overall Rating</h4>
                                        <div class="flex items-center space-x-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-6 h-6 {{ $i <= $appraisal->final_rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                            <span class="ml-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $appraisal->final_rating }}/5
                                            </span>
                                        </div>
                                    </div>
                                @endif

                                <!-- Manager Section -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Evaluated By</h4>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $appraisal->manager->name ?? 'Unknown Manager' }}</p>
                                </div>
                            </div>

                            <!-- Comments Section -->
                            @if($appraisal->final_comments)
                                <div class="mt-6">
                                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Comments</h4>
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                        <p class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $appraisal->final_comments }}</p>
                                    </div>
                                </div>
                            @endif

                            <!-- Development Plan -->
                            @if($appraisal->development_plan)
                                <div class="mt-6">
                                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Development Plan</h4>
                                    <div class="space-y-3">
                                        @if(isset($appraisal->development_plan['strengths']) && $appraisal->development_plan['strengths'])
                                            <div>
                                                <span class="text-xs font-medium text-green-600 dark:text-green-400">Strengths:</span>
                                                <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $appraisal->development_plan['strengths'] }}</p>
                                            </div>
                                        @endif
                                        @if(isset($appraisal->development_plan['areas_for_improvement']) && $appraisal->development_plan['areas_for_improvement'])
                                            <div>
                                                <span class="text-xs font-medium text-orange-600 dark:text-orange-400">Areas for Improvement:</span>
                                                <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $appraisal->development_plan['areas_for_improvement'] }}</p>
                                            </div>
                                        @endif
                                        @if(isset($appraisal->development_plan['goals']) && $appraisal->development_plan['goals'])
                                            <div>
                                                <span class="text-xs font-medium text-blue-600 dark:text-blue-400">Goals:</span>
                                                <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $appraisal->development_plan['goals'] }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>