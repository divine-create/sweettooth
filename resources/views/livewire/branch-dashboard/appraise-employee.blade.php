<div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Employee Appraisal</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Complete performance evaluation for {{ $employee->name }}</p>
                </div>
                <a href="{{ route('branch-dashboard.employee-appraisals', ['b_id' => $branch]) }}" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition">
                    ← Back to List
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- Employee Info Card -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-600 rounded-lg p-6 mb-8">
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

              <!-- Cycle Selection -->
              <div class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-8">
                  <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                      <label class="block text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                          Appraisal Cycle
                      </label>
                      <select wire:model="selectedCycle" class="w-full px-4 py-3 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                          <option value="">Select Cycle</option>
                          @foreach($cycles as $cycle)
                              <option value="{{ $cycle->id }}">{{ $cycle->name }} ({{ $cycle->start_date->format('M Y') }} - {{ $cycle->end_date->format('M Y') }})</option>
                          @endforeach
                      </select>
                      @error('selectedCycle')
                          <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                      @enderror
                  </div>
              </div>

             <!-- Appraisal Form -->
             <form wire:submit.prevent="submitAppraisal" class="space-y-8">
                <!-- Rating -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <label class="block text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Performance Rating (1-5 Stars)
                    </label>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-3">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" wire:click="$set('appraisalData.rating', {{ $i }})"
                                        class="relative group">
                                    <svg class="w-12 h-12 transition-all duration-200 {{ isset($appraisalData['rating']) && $appraisalData['rating'] >= $i ? 'fill-yellow-400 text-yellow-400' : 'fill-gray-300 text-gray-300 hover:fill-yellow-200 dark:fill-gray-600 dark:hover:fill-yellow-300' }}" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    <span class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">{{ $i }} Star{{ $i !== 1 ? 's' : '' }}</span>
                                </button>
                            @endfor
                        </div>
                        @if(isset($appraisalData['rating']) && $appraisalData['rating'])
                            <span class="ml-4 text-lg font-bold text-yellow-500">{{ $appraisalData['rating'] }}/5</span>
                        @else
                            <span class="ml-4 text-lg font-bold text-gray-400">Not Rated</span>
                        @endif
                    </div>
                    @error('appraisalData.rating')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Overall Comments -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <label class="block text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        Overall Performance Comments
                    </label>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Provide detailed feedback about the employee's overall performance, achievements, and areas for growth.</p>
                    <textarea wire:model="appraisalData.comments" rows="5"
                              placeholder="Enter your assessment here..."
                              class="w-full px-4 py-3 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('appraisalData.comments')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Strengths -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <label class="block text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        Key Strengths
                    </label>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Highlight the employee's key strengths, competencies, and positive contributions.</p>
                    <textarea wire:model="appraisalData.strengths" rows="4"
                              placeholder="List the employee's strengths..."
                              class="w-full px-4 py-3 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('appraisalData.strengths')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Areas for Improvement -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <label class="block text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        Areas for Improvement
                    </label>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Identify areas where the employee can develop and improve their performance.</p>
                    <textarea wire:model="appraisalData.areas_for_improvement" rows="4"
                              placeholder="List areas that need improvement..."
                              class="w-full px-4 py-3 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('appraisalData.areas_for_improvement')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Development Goals -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <label class="block text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        Development Goals
                    </label>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Set specific, measurable goals for the employee's development over the next period.</p>
                    <textarea wire:model="appraisalData.goals" rows="4"
                              placeholder="Set development goals..."
                              class="w-full px-4 py-3 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('appraisalData.goals')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4 justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('branch-dashboard.employee-appraisals', ['b_id' => $branch]) }}"
                       class="px-6 py-3 rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        Cancel
                    </a>
                    <button type="submit" wire:loading.attr="disabled" wire:target="submit" wire:loading.class="opacity-50 cursor-not-allowed"
                            class="px-6 py-3 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition flex items-center gap-2">
                        <span wire:loading.remove wire:target="submit">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Submit Appraisal
                        </span>
                        <span wire:loading wire:target="submit" class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Submitting...
                        </span>
                    </button>
                </div>
            </form>
        </div>
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
</div>
