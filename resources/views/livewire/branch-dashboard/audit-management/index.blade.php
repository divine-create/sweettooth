<div class="p-3 space-y-4" wire:poll.120s="$refresh" wire:poll:keep-alive
    x-data="{ 
        viewingRequest: false,
        selectedApproval: null,
        loading: false,
        showDetails: false,
        viewRequest(id) {
            this.loading = true;
            this.viewingRequest = true;
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
                this.showDetails = false;
            }
            this.loading = false;
        },
        closeModal() {
            this.viewingRequest = false;
            this.selectedApproval = null;
            this.showDetails = false;
        },
        getReadableAction(action) {
            // Convert action format: 'update:roles:uuid' -> 'Update Roles'
            const base = action.split(':')[0];
            return base.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        },
        getActionIcon(action) {
            const base = action.split(':')[0];
            const icons = {
                'create': '➕',
                'update': '✏️',
                'delete': '🗑️',
                'approve': '✅',
                'reject': '❌',
                'assign': '👤'
            };
            return icons[base] || '📝';
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
                <p class="text-sm opacity-90 mt-1">Track all system changes and approval requests</p>
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
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Search actions..." 
                    class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Action</label>
                <select wire:model.live="filterAction" 
                    class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Actions</option>
                    @foreach($actions ?? [] as $action)
                        <option value="{{ $action }}">{{ ucfirst(str_replace('_', ' ', $action)) }}</option>
                    @endforeach
                </select>
            </div>

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

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Range</label>
                <div class="flex gap-2">
                    <input type="date" wire:model.live="filterDateFrom" 
                        class="w-1/2 px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500" title="From">
                    <input type="date" wire:model.live="filterDateTo" 
                        class="w-1/2 px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500" title="To">
                </div>
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
                                    Time
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
                            <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">User</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Affected</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <!-- Time -->
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap text-xs">
                                    <div class="font-medium">{{ $log->logged_at->format('M d') }}</div>
                                    <div class="text-gray-500 dark:text-gray-500">{{ $log->logged_at->format('H:i') }}</div>
                                </td>

                                <!-- Action with Icon -->
                                <td class="px-4 py-3">
                                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded-full text-xs font-semibold inline-flex items-center gap-2">
                                        <span x-text="`{{ str_replace('_', ' ', $log->action) }}`.split(' ')[0] === 'approve' ? '✅' : 
                                                   `{{ str_replace('_', ' ', $log->action) }}`.split(' ')[0] === 'reject' ? '❌' :
                                                   `{{ str_replace('_', ' ', $log->action) }}`.split(' ')[0] === 'create' ? '➕' :
                                                   `{{ str_replace('_', ' ', $log->action) }}`.split(' ')[0] === 'update' ? '✏️' :
                                                   `{{ str_replace('_', ' ', $log->action) }}`.split(' ')[0] === 'delete' ? '🗑️' : '📝'"></span>
                                        {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                    </span>
                                </td>

                                <!-- User Info -->
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                                    <div class="font-medium text-sm">{{ $log->causer_name ?? 'System' }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ class_basename($log->causer_type ?? 'System') }}</div>
                                </td>

                                <!-- Affected Item -->
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                                    <div class="font-medium text-sm">{{ class_basename($log->auditable_type) }}</div>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3">
                                    @if($log->status === 'pending')
                                        <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 rounded-full text-xs font-semibold">
                                            ⏳ Pending
                                        </span>
                                    @elseif($log->status === 'completed')
                                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full text-xs font-semibold">
                                            ✓ Done
                                        </span>
                                    @else
                                        <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-full text-xs font-semibold">
                                            ✗ {{ ucfirst($log->status) }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Description -->
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm max-w-sm truncate" title="{{ $log->description ?? '-' }}">
                                    {{ Str::limit($log->description ?? '-', 50) }}
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
                        <span class="text-xl">⏳</span>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-900 dark:to-green-950 text-white rounded-lg shadow-lg p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm opacity-90">Approved</p>
                            <p class="text-3xl font-bold mt-1">{{ count($approvals->items()) ? collect($approvals->items())->filter(fn($a) => $a->status === 'approved')->count() : 0 }}</p>
                        </div>
                        <span class="text-xl">✅</span>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-red-500 to-red-600 dark:from-red-900 dark:to-red-950 text-white rounded-lg shadow-lg p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm opacity-90">Rejected</p>
                            <p class="text-3xl font-bold mt-1">{{ count($approvals->items()) ? collect($approvals->items())->filter(fn($a) => $a->status === 'rejected')->count() : 0 }}</p>
                        </div>
                        <span class="text-xl">❌</span>
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
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">What</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Reason</th>
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
                                    
                                    <!-- Requested By -->
                                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                                        <div class="font-medium text-sm">{{ $approval->requester?->name ?? 'Unknown' }}</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">{{ $branchName }}</div>
                                    </td>

                                    <!-- What -->
                                    <td class="px-4 py-3">
                                        <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200 rounded-full text-xs font-semibold inline-flex items-center gap-2">
                                            <span x-text="`{{ $approval->action }}`.split(':')[0] === 'update' ? '✏️' : 
                                                       `{{ $approval->action }}`.split(':')[0] === 'create' ? '➕' :
                                                       `{{ $approval->action }}`.split(':')[0] === 'delete' ? '🗑️' : '📝'"></span>
                                            {{ ucfirst(str_replace(['_', ':'], [' ', ' '], explode(':', $approval->action)[0])) }}
                                        </span>
                                    </td>

                                    <!-- Reason/Description -->
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs max-w-xs truncate" title="{{ $approval->description ?? '-' }}">
                                        {{ Str::limit($approval->description ?? 'No reason provided', 50) }}
                                    </td>

                                    <!-- Status -->
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

                                    <!-- Actions -->
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="viewRequest({{ $approval->id }})"
                                                class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold transition-colors">
                                                View
                                            </button>
                                            @if($approval->status === 'pending')
                                                <button wire:click="approveRequest({{ $approval->id }})"
                                                    class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-semibold transition-colors">
                                                    ✓
                                                </button>
                                                <button wire:click="rejectRequest({{ $approval->id }})"
                                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold transition-colors">
                                                    ✗
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

                @if($approvals->hasPages())
                    <div class="px-4 py-3 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                        {{ $approvals->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- View Request Modal - User Friendly -->
    <div x-show="viewingRequest" 
        x-transition
        class="fixed inset-0 bg-black/50 dark:bg-black/70 z-50 flex items-center justify-center p-4"
        @click.outside="closeModal()">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
            @click.stop>
            <!-- Header -->
            <div class="sticky top-0 bg-gradient-to-r from-indigo-600 to-indigo-700 dark:from-indigo-900 dark:to-indigo-950 text-white p-6 border-b dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-bold">Request Details</h3>
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
                    <p class="text-gray-600 dark:text-gray-400">Loading...</p>
                </div>

                <!-- Data Display -->
                <template x-if="selectedApproval">
                <div x-show="!loading && selectedApproval" x-transition class="space-y-6">
                    <!-- Status Badge -->
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            <span x-text="selectedApproval ? `{{ 'Status' }}: ` + (selectedApproval.status?.charAt(0).toUpperCase() + selectedApproval.status?.slice(1)) : ''"></span>
                        </h2>
                        <template x-if="selectedApproval && selectedApproval.status === 'pending'">
                            <span class="px-4 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 rounded-full text-sm font-semibold">
                                ⏳ Awaiting Decision
                            </span>
                        </template>
                        <template x-if="selectedApproval && selectedApproval.status === 'approved'">
                            <span class="px-4 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full text-sm font-semibold">
                                ✅ Approved
                            </span>
                        </template>
                        <template x-if="selectedApproval && selectedApproval.status === 'rejected'">
                            <span class="px-4 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-full text-sm font-semibold">
                                ❌ Rejected
                            </span>
                        </template>
                    </div>

                    <!-- Key Information Cards -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border dark:border-gray-600">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-1">Requested By</label>
                            <p class="text-gray-900 dark:text-gray-100 font-medium text-sm" x-text="selectedApproval?.requesterName || 'Unknown'"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-500" x-text="selectedApproval?.branchName || 'N/A'"></p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border dark:border-gray-600">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-1">Request Type</label>
                            <p class="text-gray-900 dark:text-gray-100 font-medium text-sm">
                                <span x-text="selectedApproval ? `{{ 'What' }}: ` + selectedApproval.action?.split(':')[0].charAt(0).toUpperCase() + selectedApproval.action?.split(':')[0].slice(1).replace(/_/g, ' ') : ''"></span>
                            </p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border dark:border-gray-600">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-1">Requested At</label>
                            <p class="text-gray-900 dark:text-gray-100 font-medium text-sm" x-text="selectedApproval?.createdAt || 'N/A'"></p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border dark:border-gray-600">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-1">Request ID</label>
                            <p class="text-gray-900 dark:text-gray-100 font-mono text-xs" x-text="selectedApproval?.id || 'N/A'"></p>
                        </div>
                    </div>

                    <!-- Reason/Description -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">💬 Reason Provided</label>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                            <p class="text-gray-900 dark:text-gray-100 text-sm whitespace-pre-wrap" x-text="selectedApproval?.description || 'No reason provided'"></p>
                        </div>
                    </div>

                    <!-- Technical Details Toggle -->
                    <div>
                        <button @click="showDetails = !showDetails" 
                            class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 font-medium">
                            <span x-show="!showDetails">▶</span>
                            <span x-show="showDetails">▼</span>
                            <span>Technical Details (for admins)</span>
                        </button>
                        
                        <div x-show="showDetails" x-transition class="mt-4 space-y-4">
                            <!-- Affected Data -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-2">Data Being Changed</label>
                                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border dark:border-gray-700 overflow-x-auto max-h-64 overflow-y-auto">
                                    <div class="text-xs font-mono text-gray-700 dark:text-gray-300 space-y-2">
                                        <template x-if="selectedApproval && selectedApproval.payload && Object.keys(selectedApproval.payload).length > 0">
                                            <div>
                                                <p class="font-bold text-gray-900 dark:text-white mb-2">Change Details:</p>
                                                <template x-for="(value, key) in selectedApproval.payload" :key="key">
                                                    <div class="mb-2 pb-2 border-b dark:border-gray-700">
                                                        <span class="text-blue-600 dark:text-blue-400 font-semibold" x-text="key + ':'"></span>
                                                        <template x-if="typeof value === 'string' || typeof value === 'number'">
                                                            <span class="text-green-600 dark:text-green-400 ml-2" x-text="value"></span>
                                                        </template>
                                                        <template x-if="typeof value === 'object'">
                                                            <pre class="ml-2 mt-1 bg-gray-900 dark:bg-gray-950 p-2 rounded text-gray-300" x-text="JSON.stringify(value, null, 2)"></pre>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!selectedApproval || !selectedApproval.payload || Object.keys(selectedApproval.payload).length === 0">
                                            <p class="text-gray-500">No payload data available</p>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Raw JSON (Developer Info) -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-2">Raw Data</label>
                                <div class="bg-gray-900 dark:bg-gray-950 rounded-lg p-4 overflow-x-auto max-h-64 overflow-y-auto">
                                    <pre class="text-gray-300 text-xs font-mono" x-text="selectedApproval ? JSON.stringify(selectedApproval.payload, null, 2) : '{}'"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </template>
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
