<div class="p-3 space-y-3">

    <x-breadcrumb
        title="Production Requests"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Requests']
        ]"
        :compact="false"
        :with-icons="true"/>

    <!-- Status Summary Alerts -->
    @if(isset($statusSummary) && $statusSummary->count() > 0)
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
        @if($statusSummary->has('pending'))
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-yellow-600 dark:text-yellow-400 font-medium">Pending</p>
                    <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $statusSummary['pending'] }}</p>
                </div>
                <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        @endif

        @if($statusSummary->has('approved'))
        <div class="bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 rounded-lg p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-teal-600 dark:text-teal-400 font-medium">Approved</p>
                    <p class="text-2xl font-bold text-teal-700 dark:text-teal-300">{{ $statusSummary['approved'] }}</p>
                </div>
                <div class="w-8 h-8 bg-teal-100 dark:bg-teal-900 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        @endif

        @if($statusSummary->has('in_progress'))
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">In Progress</p>
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $statusSummary['in_progress'] }}</p>
                </div>
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>
        @endif

        @if($statusSummary->has('completed'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-green-600 dark:text-green-400 font-medium">Completed</p>
                    <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $statusSummary['completed'] }}</p>
                </div>
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>
        @endif

        @if($statusSummary->has('rejected'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-red-600 dark:text-red-400 font-medium">Rejected</p>
                    <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $statusSummary['rejected'] }}</p>
                </div>
                <div class="w-8 h-8 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
            </div>
        </div>
        @endif

        @if($statusSummary->has('cancelled'))
        <div class="bg-zinc-50 dark:bg-zinc-900/20 border border-zinc-200 dark:border-zinc-800 rounded-lg p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 font-medium">Cancelled</p>
                    <p class="text-2xl font-bold text-zinc-700 dark:text-zinc-300">{{ $statusSummary['cancelled'] }}</p>
                </div>
                <div class="w-8 h-8 bg-zinc-100 dark:bg-zinc-900 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Header Actions -->
    <div class="flex flex-wrap justify-between items-center gap-3">
        <div class="flex flex-wrap gap-2">
            <input type="text" wire:model.live="search" placeholder="Search requests or recipes..."
                   class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">

            <select wire:model.live="statusFilter"
                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>

            <select wire:model.live="shiftFilter"
                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
                <option value="all">All Shifts</option>
                @foreach($allShifts as $shift)
                    <option value="{{ $shift->id }}">
                        {{ ucfirst($shift->shift_type) }} - {{ $shift->shift_date->format('M d') }}
                    </option>
                @endforeach
            </select>
        </div>
        <a href="{{ branch_route('branch-dashboard.production.request.create', ['deptSlug' => $dept_slug]) }}"
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center gap-2 text-sm" wire:navigate>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Request
        </a>
    </div>

    <!-- Requests Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Request</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">From</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Recipe</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Qty</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($requests as $request)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                Store Request
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                            {{ $request->itemRequest->request_number ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $request->productionDepartment->name ?? 'Production' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($request->recipe)
                                <span class="text-zinc-900 dark:text-zinc-100">{{ $request->recipe->product_name }}</span>
                            @else
                                <button wire:click="openAssignRecipeModal({{ $request->id }})"
                                        class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Assign Recipe
                                </button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ number_format($request->planned_production_quantity, 0) }}
                            @if($request->recipe && $request->recipe->unitOfMeasure)
                                {{ $request->recipe->unitOfMeasure->symbol }}
                            @else
                                units
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $status = $this->getDisplayStatus($request);
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'approved' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
                                    'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'quality_check' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'dispatched' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
                                    'partially_dispatched' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                    'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'cancelled' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900 dark:text-zinc-200',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$status] ?? $statusColors['pending'] }}">
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $request->created_at->format('M d, H:i') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <div class="flex items-center justify-end gap-1">
                                <!-- View -->
                                <button wire:click="openViewModal({{ $request->id }})"
                                        class="p-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                        title="View Details">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>

                                <!-- Send to Store (only for approved requests with recipe) -->
                                @if($request->recipe && in_array($request->status, ['approved', 'pending']) && !$request->item_request_id)
                                <button wire:click="sendToStore({{ $request->id }})"
                                        wire:confirm="Send raw material request to store?"
                                        class="p-1 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        title="Send to Store">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </button>
                                @endif

                                <!-- Complete (only for in_progress requests) -->
                                @if(in_array($request->status, ['approved', 'in_progress', 'quality_check']))
                                <button wire:click="completeRequest({{ $request->id }})"
                                        wire:confirm="Mark this request as completed?"
                                        class="p-1 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                        title="Mark Complete">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                                @endif

                                <!-- Reject (only for pending/approved requests) -->
                                @if(in_array($request->status, ['pending', 'approved']))
                                <button wire:click="rejectRequest({{ $request->id }})"
                                        wire:confirm="Reject this production request?"
                                        class="p-1 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                        title="Reject">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            No production requests found. Create your first request!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($requests->hasPages())
        <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
            {{ $requests->links() }}
        </div>
        @endif
    </div>

    <!-- View Request Modal -->
    @if($showViewModal && $viewingRequest)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeViewModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="relative inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full z-50">
                <!-- Header -->
                <div class="bg-white dark:bg-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                Production Request Details
                            </h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                                @if($viewingRequest->itemRequest)
                                    Request #{{ $viewingRequest->itemRequest->request_number ?? 'N/A' }}
                                @else
                                    Request #PR-{{ $viewingRequest->id }}
                                @endif
                            </p>
                        </div>
                        <button wire:click="closeViewModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="bg-white dark:bg-zinc-800 px-6 py-4 space-y-4">
                    <!-- Request Info Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Type</p>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100 mt-1">
                                @if($viewingRequest->item_request_id)
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                        Production to Store
                                    </span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                        Other
                                    </span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Recipe</p>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100 mt-1">
                                {{ $viewingRequest->recipe->product_name ?? 'Not Assigned' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Planned Quantity</p>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100 mt-1">
                                {{ number_format($viewingRequest->planned_production_quantity, 2) }}
                                {{ $viewingRequest->recipe->unitOfMeasure->symbol ?? 'units' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Status</p>
                            @php
                                $status = $this->getDisplayStatus($viewingRequest);
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'approved' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
                                    'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'partially_dispatched' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                    'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'cancelled' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900 dark:text-zinc-200',
                                ];
                            @endphp
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$status] ?? $statusColors['pending'] }} mt-1">
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">From Department</p>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100 mt-1">
                                {{ $viewingRequest->productionDepartment->name ?? 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">To Department</p>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100 mt-1">
                                {{ $viewingRequest->productionDepartment->name ?? 'Production' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Requested By</p>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100 mt-1">
                                {{ $viewingRequest->createdBy->name ?? 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Created At</p>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100 mt-1">
                                {{ $viewingRequest->created_at->format('M d, Y H:i') }}
                            </p>
                        </div>
                    </div>

                    @if($viewingRequest->notes)
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Notes</p>
                        <p class="text-sm text-zinc-900 dark:text-zinc-100 mt-1 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                            {{ $viewingRequest->notes }}
                        </p>
                    </div>
                    @endif

                    <!-- Request Items Table -->
                    @if(count($requestItems) > 0)
                    <div class="mt-6">
                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Requested Items</h4>
                        <div class="overflow-x-auto border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <table class="w-full">
                                <thead class="bg-zinc-50 dark:bg-zinc-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Item</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Requested</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Approved</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Dispatched</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach($requestItems as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $item['item_name'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400 text-right">
                                            {{ number_format($item['quantity_requested'], 2) }} {{ $item['uom'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400 text-right">
                                            {{ number_format($item['quantity_approved'], 2) }} {{ $item['uom'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400 text-right">
                                            {{ number_format($item['quantity_dispatched'], 2) }} {{ $item['uom'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-center">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $item['status']['class'] }}">
                                                {{ $item['status']['label'] }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="bg-zinc-50 dark:bg-zinc-900 px-6 py-4 flex flex-wrap justify-end gap-3">
                    @if($viewingRequest)
                    <a href="{{ branch_route('branch-dashboard.production.request.progress', [
                        'deptSlug' => $dept_slug,
                        'requestId' => $viewingRequest->id,
                        'page' => 'Progress' . '_' . $dept_slug
                    ]) }}"
                       class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                        Track Progress
                    </a>
                    @endif

                    @if($viewingRequest->recipe && in_array($viewingRequest->status, ['approved', 'pending']) && !$viewingRequest->item_request_id)
                    <button wire:click="sendToStore({{ $viewingRequest->id }})"
                            wire:confirm="Send raw material request to store?"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium">
                        Send to Store
                    </button>
                    @endif

                    @if(in_array($viewingRequest->status, ['approved', 'in_progress', 'quality_check']))
                    <button wire:click="completeRequest({{ $viewingRequest->id }})"
                            wire:confirm="Mark this request as completed?"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">
                        Mark Complete
                    </button>
                    @endif

                    @if(in_array($viewingRequest->status, ['pending', 'approved']))
                    <button wire:click="rejectRequest({{ $viewingRequest->id }})"
                            wire:confirm="Reject this production request?"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
                        Reject
                    </button>
                    @endif

                    <button wire:click="closeViewModal"
                            class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-900 dark:text-zinc-100 rounded-lg font-medium">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Assign Recipe Modal -->
    @if($showAssignRecipeModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeAssignRecipeModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="relative inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-50">
                <div class="bg-white dark:bg-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Assign Recipe</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Select a recipe for this production request</p>
                </div>

                <div class="bg-white dark:bg-zinc-800 px-6 py-4">
                    <select wire:model="selectedRecipeId"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
                        <option value="">Select a recipe...</option>
                        @foreach($availableRecipes as $recipe)
                            <option value="{{ $recipe->id }}">
                                {{ $recipe->product_name }} (Yield: {{ $recipe->yield_quantity }} {{ $recipe->unitOfMeasure->symbol ?? 'units' }})
                            </option>
                        @endforeach
                    </select>

                    @error('selectedRecipeId')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    @if(count($availableRecipes) === 0)
                        <p class="mt-3 text-sm text-yellow-600 dark:text-yellow-400">
                            No active recipes found for this department. Please create recipes first.
                        </p>
                    @endif
                </div>

                <div class="bg-zinc-50 dark:bg-zinc-900 px-6 py-4 flex justify-end gap-3">
                    <button wire:click="closeAssignRecipeModal"
                            class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-900 dark:text-zinc-100 rounded-lg font-medium">
                        Cancel
                    </button>
                    <button wire:click="assignRecipe"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium"
                            @if(!$selectedRecipeId) disabled @endif>
                        Assign Recipe
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
