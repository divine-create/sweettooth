<div class="space-y-6">
    <!-- Header -->
    <div>
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white">Production Requests</h2>
        <p class="text-zinc-600 dark:text-zinc-400 text-sm mt-1">Manage incoming production requests for your department</p>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-4 border border-zinc-200 dark:border-zinc-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Filter by Status</label>
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
                </select>
            </div>

            <!-- Priority Filter -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Filter by Priority</label>
                <select 
                    wire:model.live="priorityFilter"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                >
                    <option value="">All Priorities</option>
                    <option value="urgent">Urgent Only</option>
                    <option value="normal">Normal Only</option>
                </select>
            </div>

            <!-- Auto-refresh indicator -->
            <div class="flex items-end">
                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                    <svg class="w-4 h-4 inline-block text-green-500 animate-pulse mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <circle cx="10" cy="10" r="8"/>
                    </svg>
                    Real-time updates enabled
                </div>
            </div>
        </div>
    </div>

    <!-- Requests Board -->
    <div class="space-y-4">
        @if ($requests->count() > 0)
            @php
                $groupedRequests = $requests->groupBy('status');
            @endphp

            <!-- Kanban View -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @foreach (['pending' => 'Pending', 'in_progress' => 'In Progress', 'quality_check' => 'Quality Check', 'completed' => 'Completed'] as $status => $label)
                    <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-4 border border-zinc-200 dark:border-zinc-600">
                        <h3 class="font-semibold text-zinc-900 dark:text-white mb-3 text-sm">
                            {{ $label }}
                            <span class="ml-2 bg-zinc-200 dark:bg-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-full px-2 py-1 text-xs">
                                {{ $groupedRequests->get($status, collect())->count() }}
                            </span>
                        </h3>
                        <div class="space-y-2">
                            @forelse ($groupedRequests->get($status, collect()) as $request)
                                <div class="bg-white dark:bg-zinc-800 rounded-lg p-3 border border-zinc-200 dark:border-zinc-600 hover:shadow-md transition cursor-pointer"
                                    wire:click="viewDetails({{ $request->id }})"
                                >
                                    <div class="flex justify-between items-start gap-2 mb-2">
                                        <p class="font-medium text-sm text-zinc-900 dark:text-white truncate">
                                            {{ $request->recipe?->product_name ?? 'Production Request' }}
                                        </p>
                                        @if ($request->priority === 'urgent')
                                            <span class="bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 px-2 py-1 rounded text-xs font-semibold">
                                                Urgent
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-2">
                                        Qty: {{ number_format($request->planned_production_quantity, 2) }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500">
                                        {{ $request->created_at->format('M d, H:i') }}
                                    </p>
                                </div>
                            @empty
                                <p class="text-xs text-zinc-400 text-center py-4">No requests</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- List View Table -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-700 border-b border-zinc-200 dark:border-zinc-600">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-white">Recipe / Department</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-white">Quantity</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-white">Priority</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-white">Status</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-white">Created</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-white">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($requests as $request)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-zinc-900 dark:text-white">
                                            {{ $request->recipe?->product_name ?? 'N/A' }}
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $request->productionDepartment?->name ?? 'Production' }} • By: {{ $request->createdBy?->name ?? 'N/A' }}
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
                                    <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $request->created_at->format('M d, Y H:i') }}
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
            </div>
        @else
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-12 text-center">
                <p class="text-zinc-500 dark:text-zinc-400">No production requests for your department</p>
            </div>
        @endif
    </div>

    <!-- Details Modal -->
    @if ($showDetails && $selectedRequest)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click="closeDetails">
            <div class="bg-white dark:bg-zinc-800 rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto" wire:click.stop>
                <div class="sticky top-0 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 p-6 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Request #{{ $selectedRequest->id }}</h3>
                    <button wire:click="closeDetails" class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Request Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Production Department</p>
                            <p class="font-semibold text-zinc-900 dark:text-white">{{ $selectedRequest->productionDepartment?->name ?? 'Production' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Requested By</p>
                            <p class="font-semibold text-zinc-900 dark:text-white">{{ $selectedRequest->createdBy?->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Recipe</p>
                            <p class="font-semibold text-zinc-900 dark:text-white">{{ $selectedRequest->recipe?->product_name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Quantity</p>
                            <p class="font-semibold text-zinc-900 dark:text-white">{{ number_format($selectedRequest->planned_production_quantity, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Priority</p>
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold"
                                :class="@if($selectedRequest->priority === 'urgent') 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' @else 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' @endif"
                            >
                                {{ ucfirst($selectedRequest->priority) }}
                            </span>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if ($selectedRequest->notes)
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Notes</p>
                            <p class="text-zinc-700 dark:text-zinc-300 text-sm">{{ $selectedRequest->notes }}</p>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 flex gap-3">
                        @if ($selectedRequest->status === 'pending')
                            <button 
                                wire:click="startProduction({{ $selectedRequest->id }})"
                                class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition"
                            >
                                Start Production
                            </button>
                        @elseif ($selectedRequest->status === 'in_progress')
                            <button 
                                wire:click="markCompleted({{ $selectedRequest->id }})"
                                class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition"
                            >
                                Mark as Completed
                            </button>
                        @endif

                        <button 
                            wire:click="$set('showProgressForm', true)"
                            class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition"
                        >
                            Update Progress
                        </button>
                    </div>

                    <!-- Progress Timeline -->
                    @if ($selectedRequest->progressFeedback->count() > 0)
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Progress Timeline</p>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @foreach ($selectedRequest->progressFeedback->sortByDesc('created_at') as $progress)
                                    <div class="p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="font-medium text-sm text-zinc-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $progress->milestone)) }}</p>
                                                <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $progress->created_at->format('M d, Y H:i') }}</p>
                                            </div>
                                            <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ $progress->progress_percentage }}%</span>
                                        </div>
                                        @if ($progress->notes)
                                            <p class="text-xs text-zinc-700 dark:text-zinc-300 mt-2">{{ $progress->notes }}</p>
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
