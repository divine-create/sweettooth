<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white">My Production Requests</h2>
        <a href="" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
            New Request
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-4 border border-zinc-200 dark:border-zinc-700">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                <input 
                    wire:model.live="searchTerm"
                    type="text" 
                    placeholder="Search requests..."
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                />
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                <select 
                    wire:model.live="statusFilter"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                >
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="quality_check">Quality Check</option>
                    <option value="completed">Completed</option>
                    <option value="dispatched">Dispatched</option>
                    <option value="accepted">Accepted</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <!-- Priority Filter -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Priority</label>
                <select 
                    wire:model.live="priorityFilter"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                >
                    <option value="">All Priorities</option>
                    <option value="normal">Normal</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>

            <!-- Sort -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sort</label>
                <div class="flex gap-2">
                    <select 
                        wire:model.live="sortBy"
                        class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm"
                    >
                        <option value="created_at">Created</option>
                        <option value="priority">Priority</option>
                        <option value="status">Status</option>
                    </select>
                    <button 
                        wire:click="$set('sortDirection', sortDirection === 'asc' ? 'desc' : 'asc')"
                        class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white hover:bg-zinc-50 dark:hover:bg-zinc-600 transition"
                    >
                        @if($sortDirection === 'desc')
                            ↓
                        @else
                            ↑
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        @if ($requests->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-700 border-b border-zinc-200 dark:border-zinc-600">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-white">Department</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-white">Quantity</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-white">Priority</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-white">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-white">Progress</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-white">Created</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($requests as $request)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-zinc-900 dark:text-white">
                                        {{ $request->productionDepartment?->name ?? 'N/A' }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ number_format($request->planned_production_quantity, 2) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold"
                                        :class="@if($request->priority === 'urgent') 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' @else 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' @endif"
                                    >
                                        {{ ucfirst($request->priority) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold {{ $this->getStatusBadgeClass($request->status) }}">
                                        {{ $this->getStatusLabel($request->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $latestProgress = $request->progressFeedback->sortByDesc('created_at')->first();
                                    @endphp
                                    @if ($latestProgress)
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 max-w-xs">
                                                <div 
                                                    class="bg-blue-600 h-2 rounded-full transition-all"
                                                    style="width: {{ $latestProgress->progress_percentage }}%"
                                                ></div>
                                            </div>
                                            <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ $latestProgress->progress_percentage }}%</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-zinc-400">No progress</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $request->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <button 
                                        wire:click="viewDetails({{ $request->id }})"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium"
                                    >
                                        View
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $requests->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <p class="text-zinc-500 dark:text-zinc-400">No production requests found</p>
            </div>
        @endif
    </div>

    <!-- Details Modal -->
    @if ($showDetails && $selectedRequest)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click="closeDetails">
            <div class="bg-white dark:bg-zinc-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto" wire:click.stop>
                <div class="sticky top-0 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 p-6 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Request Details</h3>
                    <button wire:click="closeDetails" class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Department</p>
                            <p class="font-semibold text-zinc-900 dark:text-white">{{ $selectedRequest->productionDepartment?->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Status</p>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold {{ $this->getStatusBadgeClass($selectedRequest->status) }}">
                                {{ $this->getStatusLabel($selectedRequest->status) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Quantity</p>
                            <p class="font-semibold text-zinc-900 dark:text-white">{{ number_format($selectedRequest->planned_production_quantity, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Priority</p>
                            <p class="font-semibold text-zinc-900 dark:text-white">{{ ucfirst($selectedRequest->priority) }}</p>
                        </div>
                    </div>

                    @if ($selectedRequest->notes)
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Notes</p>
                            <p class="text-zinc-700 dark:text-zinc-300">{{ $selectedRequest->notes }}</p>
                        </div>
                    @endif

                    @if ($selectedRequest->progressFeedback->count() > 0)
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Progress Timeline</p>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @foreach ($selectedRequest->progressFeedback->sortByDesc('created_at') as $progress)
                                    <div class="p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="font-medium text-zinc-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $progress->milestone)) }}</p>
                                                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $progress->created_at->format('M d, Y H:i') }}</p>
                                            </div>
                                            <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ $progress->progress_percentage }}%</span>
                                        </div>
                                        @if ($progress->notes)
                                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mt-2">{{ $progress->notes }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
