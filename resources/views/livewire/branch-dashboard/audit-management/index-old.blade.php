<div class="p-3 space-y-4" wire:poll.120s="$refresh" wire:poll:keep-alive 
    x-data="{ 
        viewingRequest: false,
        selectedApproval: null,
        loading: false,
        viewRequest(id) {
            this.loading = true;
            this.viewingRequest = true;
            // Simulate data being already loaded from pagination
            const approval = document.querySelector(`[data-approval-id='${id}']`);
            if (approval) {
                this.selectedApproval = {
                    id: approval.dataset.approvalId,
                    action: approval.dataset.action,
                    description: approval.dataset.description,
                    requesterName: approval.dataset.requesterName,
                    requesterType: approval.dataset.requesterType,
                    status: approval.dataset.status,
                    branchName: approval.dataset.branchName || 'N/A',
                    payload: JSON.parse(approval.dataset.payload || '{}'),
                    createdAt: approval.dataset.createdAt
                };
            }
            this.loading = false;
        },
        closeModal() {
            this.viewingRequest = false;
            this.selectedApproval = null;
        }
    }">
    <!-- Breadcrumb -->
    <x-breadcrumb title="Audit Management" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Organization'],
        ['label' => 'Audit Management'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 dark:from-indigo-900 dark:to-indigo-950 text-white rounded-lg shadow-lg p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">Audit Management</h2>
                <p class="text-sm opacity-90 mt-1">Complete audit trail and approval workflow</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="$refresh"
                    class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-2 border dark:border-gray-700">
        <button wire:click="$set('tab', 'logs')" 
            class="px-4 py-2 rounded-lg font-medium transition-all {{ $tab === 'logs' 
                ? 'bg-indigo-600 text-white' 
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
            wire:loading.attr="disabled">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Audit Logs
        </button>
        <button wire:click="$set('tab', 'approvals')" 
            class="px-4 py-2 rounded-lg font-medium transition-all {{ $tab === 'approvals' 
                ? 'bg-indigo-600 text-white' 
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
            wire:loading.attr="disabled">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Approval Requests
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-white">Filters</h3>
            @if($search || $filterAction || $filterStatus || $filterDateFrom || $filterDateTo)
                <button wire:click="clearFilters" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                    Clear All
                </button>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Search actions..." 
                    class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Department Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department</label>
                <select wire:model.live="filterDepartment" 
                    class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Departments</option>
                    @php
                        $departments = \App\Models\Department::where('branch_id', $branchId)->pluck('name', 'id');
                    @endphp
                    @foreach($departments as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Action Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Action</label>
                <select wire:model.live="filterAction" 
                    class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Actions</option>
                    @foreach($actions ?? [] as $action)
                        <option value="{{ $action }}">{{ ucfirst($action) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select wire:model.live="filterStatus" 
                    class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Statuses</option>
                    @foreach($statuses ?? [] as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
                <input type="date" wire:model.live="filterDateFrom" 
                    class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
                <input type="date" wire:model.live="filterDateTo" 
                    class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    <!-- Audit Logs Tab -->
    @if($tab === 'logs' && $logs)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-700 border-b dark:border-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <button wire:click="sort('logged_at')" class="font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center gap-1">
                                    Timestamp
                                    @if($sortBy === 'logged_at')
                                        @if($sortDirection === 'asc')
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" /></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 13a1 1 0 10-2 0V7.414l-1.293 1.293a1 1 0 00-1.414-1.414l3-3a1 1 0 001.414 0l3 3a1 1 0 00-1.414 1.414L15 7.414V13z" /></svg>
                                        @endif
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Action</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Actor</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Target</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    {{ $log->logged_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded-full text-xs font-semibold">
                                        {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                                    <div class="font-medium">{{ $log->causer_name ?? 'System' }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ class_basename($log->causer_type ?? 'System') }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                                    <div class="font-medium">{{ class_basename($log->auditable_type) }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">#{{ $log->auditable_id }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($log->status === 'pending')
                                        <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 rounded-full text-xs font-semibold">
                                            ⏳ Pending
                                        </span>
                                    @elseif($log->status === 'completed')
                                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full text-xs font-semibold">
                                            ✓ Completed
                                        </span>
                                    @else
                                        <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-full text-xs font-semibold">
                                            ✗ {{ ucfirst($log->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm max-w-xs truncate">
                                    {{ $log->description ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p>No audit logs found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($logs->hasPages())
                <div class="px-4 py-3 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- Approval Requests Tab -->
    @if($tab === 'approvals' && $approvals)
        <div class="space-y-4">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 dark:from-yellow-900 dark:to-yellow-950 text-white rounded-lg shadow-lg p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm opacity-90">Pending</p>
                            <p class="text-3xl font-bold mt-1">{{ count($approvals->items()) ? collect($approvals->items())->filter(fn($a) => $a->status === 'pending')->count() : 0 }}</p>
                        </div>
                        <svg class="w-8 h-8 opacity-30" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-900 dark:to-green-950 text-white rounded-lg shadow-lg p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm opacity-90">Approved</p>
                            <p class="text-3xl font-bold mt-1">{{ count($approvals->items()) ? collect($approvals->items())->filter(fn($a) => $a->status === 'approved')->count() : 0 }}</p>
                        </div>
                        <svg class="w-8 h-8 opacity-30" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-red-500 to-red-600 dark:from-red-900 dark:to-red-950 text-white rounded-lg shadow-lg p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm opacity-90">Rejected</p>
                            <p class="text-3xl font-bold mt-1">{{ count($approvals->items()) ? collect($approvals->items())->filter(fn($a) => $a->status === 'rejected')->count() : 0 }}</p>
                        </div>
                        <svg class="w-8 h-8 opacity-30" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Approvals Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-700 border-b dark:border-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Requested By</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Action</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Description</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Status</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-white">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            @forelse($approvals as $approval)
                                @php
                                    $branchId = $approval->branch_id ?? ($approval->payload['branch_id'] ?? null);
                                    $branch = $branchId ? \App\Models\Branch::find($branchId) : null;
                                    $branchName = $branch?->name ?? 'N/A';
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                    data-approval-id="{{ $approval->id }}"
                                    data-action="{{ $approval->action }}"
                                    data-description="{{ $approval->description }}"
                                    data-requester-name="{{ $approval->requester?->name ?? 'Unknown' }}"
                                    data-requester-type="{{ class_basename($approval->requester_type ?? 'Unknown') }}"
                                    data-status="{{ $approval->status }}"
                                    data-branch-name="{{ $branchName }}"
                                    data-payload="{{ json_encode($approval->payload ?? []) }}"
                                    data-created-at="{{ $approval->created_at->format('M d, Y H:i') }}">
                                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                                        <div class="font-medium">{{ $approval->requester?->name ?? 'Unknown' }}</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">{{ class_basename($approval->requester_type ?? 'Unknown') }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200 rounded-full text-xs font-semibold">
                                            {{ ucfirst(str_replace('_', ' ', str_replace(':department', '', $approval->action))) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs max-w-xs truncate">
                                        {{ $approval->description ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($approval->status === 'pending')
                                            <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 rounded-full text-xs font-semibold">
                                                ⏳ Pending
                                            </span>
                                        @elseif($approval->status === 'approved')
                                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full text-xs font-semibold">
                                                ✓ Approved
                                            </span>
                                        @elseif($approval->status === 'rejected')
                                            <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-full text-xs font-semibold">
                                                ✗ Rejected
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="viewRequest({{ $approval->id }})"
                                                class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold transition-colors">
                                                View
                                            </button>
                                            @if($approval->status === 'pending')
                                                <button wire:click="approveRequest({{ $approval->id }})"
                                                    class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-semibold transition-colors">
                                                    Approve
                                                </button>
                                                <button wire:click="rejectRequest({{ $approval->id }})"
                                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold transition-colors">
                                                    Reject
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <svg class="w-12 h-12 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p>No approval requests found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($approvals->hasPages())
                    <div class="px-4 py-3 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                        {{ $approvals->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- View Request Modal -->
    <div x-show="viewingRequest" 
        x-transition
        class="fixed inset-0 bg-black/50 dark:bg-black/70 z-50 flex items-center justify-center p-4"
        @click.outside="closeModal()">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
            @click.stop>
            <!-- Header -->
            <div class="sticky top-0 bg-gradient-to-r from-indigo-600 to-indigo-700 dark:from-indigo-900 dark:to-indigo-950 text-white p-6 border-b dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-bold">Approval Request Details</h3>
                <button @click="closeModal()" class="text-white/80 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="p-6">
                <!-- Loader -->
                <div x-show="loading" x-transition class="flex flex-col items-center justify-center py-12">
                    <div class="relative w-12 h-12 mb-4">
                        <div class="absolute inset-0 bg-indigo-600 rounded-full opacity-20 animate-pulse"></div>
                        <svg class="absolute inset-0 w-12 h-12 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400">Loading request details...</p>
                </div>

                <!-- Data Display -->
                <div x-show="!loading && selectedApproval" x-transition class="space-y-6">
                    <!-- Status Badge -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Status</label>
                        <div class="flex items-center gap-2">
                            <template x-if="selectedApproval.status === 'pending'">
                                <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 rounded-full text-sm font-semibold">
                                    ⏳ Pending
                                </span>
                            </template>
                            <template x-if="selectedApproval.status === 'approved'">
                                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full text-sm font-semibold">
                                    ✓ Approved
                                </span>
                            </template>
                            <template x-if="selectedApproval.status === 'rejected'">
                                <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-full text-sm font-semibold">
                                    ✗ Rejected
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Requester & Branch Info -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Requested By</label>
                            <p class="text-gray-900 dark:text-gray-100 font-medium" x-text="selectedApproval.requesterName"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <p class="text-gray-900 dark:text-gray-100 font-medium" x-text="selectedApproval.requesterType"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Branch</label>
                            <p class="text-gray-900 dark:text-gray-100 font-medium" x-text="selectedApproval.branchName"></p>
                        </div>
                    </div>

                    <!-- Action & Created Date -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Action</label>
                            <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200 rounded-full text-xs font-semibold inline-block" x-text="selectedApproval.action.replace(':department', '').toUpperCase()"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Requested At</label>
                            <p class="text-gray-900 dark:text-gray-100 text-sm" x-text="selectedApproval.createdAt"></p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Description</label>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border dark:border-gray-600">
                            <p class="text-gray-900 dark:text-gray-100 text-sm whitespace-pre-wrap" x-text="selectedApproval.description || '-'"></p>
                        </div>
                    </div>

                    <!-- Payload -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Payload</label>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border dark:border-gray-600 overflow-x-auto">
                            <pre class="text-gray-900 dark:text-gray-100 text-xs font-mono" x-text="JSON.stringify(selectedApproval.payload, null, 2)"></pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="sticky bottom-0 bg-gray-50 dark:bg-gray-700/30 border-t dark:border-gray-700 p-4 flex items-center justify-end gap-2">
                <button @click="closeModal()"
                    class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white rounded-lg font-medium hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
