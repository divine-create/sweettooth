<div>
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">360-Degree Feedback</h2>
                <button wire:click="$set('showCreateModal', true)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i>Request Feedback
                </button>
            </div>
        </div>

        <!-- Pending Feedback Alert -->
        @if($this->myPendingFeedback->count() > 0)
            <div class="px-6 py-4 bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 dark:text-yellow-200">
                            You have {{ $this->myPendingFeedback->count() }} pending feedback request(s) to complete.
                            <button wire:click="$set('showProvideFeedbackModal', true)"
                                    class="font-medium underline text-yellow-700 dark:text-yellow-200 hover:text-yellow-600">
                                Review them now
                            </button>
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Filters -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Search by reviewer name..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <select wire:model.live="statusFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Feedback Requests Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Reviewee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Reviewer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($feedbackRequests as $request)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $request->feedback_data['reviewee_id'] ? \App\Models\User::find($request->feedback_data['reviewee_id'])->name : 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $request->reviewer->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $request->reviewer->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @if($request->reviewer_type === 'peer') bg-blue-100 text-blue-800
                                    @elseif($request->reviewer_type === 'manager') bg-green-100 text-green-800
                                    @else bg-purple-100 text-purple-800 @endif">
                                    {{ ucfirst($request->reviewer_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($request->submitted_at)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Completed
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $request->feedback_data['due_date'] ? \Carbon\Carbon::parse($request->feedback_data['due_date'])->format('M d, Y') : 'N/A' }}
                                @if($request->feedback_data['due_date'] && \Carbon\Carbon::parse($request->feedback_data['due_date'])->isPast() && !$request->submitted_at)
                                    <span class="text-red-500">(Overdue)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                @if(!$request->submitted_at && $request->reviewer_id === auth()->id())
                                    <button wire:click="provideFeedback({{ $request->id }})"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                        <i class="fas fa-edit"></i> Provide Feedback
                                    </button>
                                @endif
                                @if(!$request->submitted_at)
                                    <button wire:click="cancelFeedbackRequest({{ $request->id }})"
                                            onclick="return confirm('Are you sure you want to cancel this feedback request?')"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                @else
                                    <span class="text-gray-400">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No feedback requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $feedbackRequests->links() }}
        </div>
    </div>

    <!-- Create Feedback Request Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Request 360-Degree Feedback</h3>

                    <form wire:submit.prevent="createFeedbackRequest" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Person to Review</label>
                            <select wire:model="requestData.reviewee_id"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Select Employee</option>
                                @foreach($this->employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            @error('requestData.reviewee_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reviewers</label>
                            <select multiple wire:model="requestData.reviewer_ids"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                @foreach($this->employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Hold Ctrl/Cmd to select multiple reviewers</p>
                            @error('requestData.reviewer_ids') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Relationship Type</label>
                                <select wire:model="requestData.reviewer_type"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="peer">Peer</option>
                                    <option value="manager">Manager</option>
                                    <option value="subordinate">Subordinate</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                                <input type="date" wire:model="requestData.due_date"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                @error('requestData.due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message to Reviewers</label>
                            <textarea wire:model="requestData.message" rows="3"
                                      placeholder="Optional message explaining why you're requesting feedback..."
                                      class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" wire:click="$set('showCreateModal', false)"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                                Send Feedback Requests
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Provide Feedback Modal -->
    @if($showProvideFeedbackModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Provide 360-Degree Feedback</h3>

                    @if($this->myPendingFeedback->count() > 0)
                        <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900 rounded-md">
                            <p class="text-sm text-blue-700 dark:text-blue-200">
                                You have {{ $this->myPendingFeedback->count() }} feedback request(s) pending. Please complete them.
                            </p>
                        </div>

                        <div class="space-y-4">
                            @foreach($this->myPendingFeedback as $feedback)
                                <div class="border border-gray-200 dark:border-gray-600 rounded-md p-4">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h4 class="font-medium text-gray-900 dark:text-white">
                                                Feedback for: {{ \App\Models\User::find($feedback->feedback_data['reviewee_id'])->name }}
                                            </h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Due: {{ \Carbon\Carbon::parse($feedback->feedback_data['due_date'])->format('M d, Y') }}
                                            </p>
                                            @if($feedback->feedback_data['message'])
                                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                                                    "{{ $feedback->feedback_data['message'] }}"
                                                </p>
                                            @endif
                                        </div>
                                        <button wire:click="provideFeedback({{ $feedback->id }})"
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                            Provide Feedback
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($editingRequest)
                        <form wire:submit.prevent="submitFeedback" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Overall Rating</label>
                                <div class="flex items-center space-x-2 mt-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" wire:click="$set('feedbackData.rating', {{ $i }})"
                                                class="w-8 h-8 rounded-full border-2 transition-all duration-200
                                                       {{ isset($feedbackData['rating']) && $feedbackData['rating'] >= $i ? 'bg-yellow-400 border-yellow-400 text-yellow-900' : 'border-gray-300 text-gray-400 hover:border-yellow-300' }}">
                                            {{ $i }}
                                        </button>
                                    @endfor
                                </div>
                                @error('feedbackData.rating') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Strengths</label>
                                <textarea wire:model="feedbackData.strengths" rows="3"
                                          placeholder="What are this person's key strengths?"
                                          class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                                @error('feedbackData.strengths') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Areas for Improvement</label>
                                <textarea wire:model="feedbackData.areas_for_improvement" rows="3"
                                          placeholder="What areas could this person improve in?"
                                          class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                                @error('feedbackData.areas_for_improvement') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Additional Comments</label>
                                <textarea wire:model="feedbackData.comments" rows="3"
                                          placeholder="Any additional feedback or suggestions?"
                                          class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" wire:model="feedbackData.is_anonymous" id="anonymous"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="anonymous" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                    Submit feedback anonymously
                                </label>
                            </div>

                            <div class="flex justify-end space-x-3 pt-4">
                                <button type="button" wire:click="$set('showProvideFeedbackModal', false)"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                                    Submit Feedback
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>