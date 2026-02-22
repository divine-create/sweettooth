<div class="p-3 space-y-3">
    <x-breadcrumb title="Approve Leaves" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Leave Management'],
        ['label' => 'Approve Leaves'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Approve Leave Applications</h2>
                <p class="text-sm opacity-90 mt-1">
                    Review and manage employee leave requests
                </p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by application number, employee name, leave type, or reason..."
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Status</label>
                <select wire:model.live="status_filter"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Leave Applications Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700 border-b border-zinc-200 dark:border-zinc-600">
                    <tr>
                        @foreach($headers as $header)
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                {{ $header['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($rows as $leave)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                            <!-- Application Number -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $leave->application_number }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $leave->created_at->format('M d, Y') }}
                                </div>
                            </td>

                            <!-- Employee -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $leave->employee->name }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $leave->employee->employee_id }}</div>
                            </td>

                            <!-- Leave Type -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded" style="background-color: {{ $leave->leaveType->color }}"></div>
                                    <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $leave->leaveType->name }}</span>
                                </div>
                            </td>

                            <!-- Dates -->
                            <td class="px-4 py-3">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $leave->start_date->format('M d, Y') }} - {{ $leave->end_date->format('M d, Y') }}
                                </div>
                            </td>

                            <!-- Total Days -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ number_format($leave->total_days, 1) }} days
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $leave->status_badge_class }}">
                                    {{ ucfirst($leave->status) }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <div class="flex items-center gap-2">
                                    <button wire:click="viewDetails({{ $leave->id }})"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                        title="View Details">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    @if($leave->status === 'pending')
                                        <button wire:click="openApprovalModal({{ $leave->id }})"
                                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                            title="Approve">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>

                                        <button wire:click="openRejectionModal({{ $leave->id }})"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            title="Reject">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    @endif

                                    @if($leave->supporting_document)
                                        <button wire:click="viewDocument('{{ $leave->supporting_document }}')"
                                            class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300"
                                            title="View Document">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-12 h-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-zinc-500 dark:text-zinc-400">No leave applications found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($rows->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $rows->links() }}
            </div>
        @endif
    </div>

    <!-- Details Modal -->
    @if($showDetailsModal && $selectedLeave)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full mx-4 shadow-xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Leave Application Details</h3>
                    <button wire:click="closeDetailsModal" class="text-zinc-400 hover:text-zinc-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Application Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Application Number</label>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedLeave->application_number }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Status</label>
                            <p><span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $selectedLeave->status_badge_class }}">
                                {{ ucfirst($selectedLeave->status) }}
                            </span></p>
                        </div>
                    </div>

                    <!-- Employee Info -->
                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Employee Information</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Name</label>
                                <p class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedLeave->employee->name }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Employee ID</label>
                                <p class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedLeave->employee->employee_id }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Leave Details -->
                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Leave Details</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Leave Type</label>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="w-3 h-3 rounded" style="background-color: {{ $selectedLeave->leaveType->color }}"></div>
                                    <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedLeave->leaveType->name }}</span>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Days</label>
                                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($selectedLeave->total_days, 1) }} days</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Start Date</label>
                                <p class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedLeave->start_date->format('M d, Y') }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">End Date</label>
                                <p class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedLeave->end_date->format('M d, Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Reason</label>
                        <p class="text-sm text-zinc-900 dark:text-zinc-100 mt-1">{{ $selectedLeave->reason }}</p>
                    </div>

                    @if($selectedLeave->emergency_contact)
                        <div>
                            <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Emergency Contact</label>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100 mt-1">{{ $selectedLeave->emergency_contact }}</p>
                        </div>
                    @endif

                    <!-- Approval/Rejection Info -->
                    @if($selectedLeave->status === 'approved')
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                            <h4 class="text-sm font-semibold text-green-900 dark:text-green-100 mb-2">Approval Details</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-medium text-green-700 dark:text-green-400">Approved By</label>
                                    <p class="text-sm text-green-900 dark:text-green-100">{{ $selectedLeave->approvedBy->name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-green-700 dark:text-green-400">Approved At</label>
                                    <p class="text-sm text-green-900 dark:text-green-100">{{ $selectedLeave->approved_at?->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>
                            @if($selectedLeave->approval_notes)
                                <div class="mt-2">
                                    <label class="text-xs font-medium text-green-700 dark:text-green-400">Notes</label>
                                    <p class="text-sm text-green-900 dark:text-green-100">{{ $selectedLeave->approval_notes }}</p>
                                </div>
                            @endif
                        </div>
                    @elseif($selectedLeave->status === 'rejected')
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
                            <h4 class="text-sm font-semibold text-red-900 dark:text-red-100 mb-2">Rejection Details</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-medium text-red-700 dark:text-red-400">Rejected By</label>
                                    <p class="text-sm text-red-900 dark:text-red-100">{{ $selectedLeave->rejectedBy->name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-red-700 dark:text-red-400">Rejected At</label>
                                    <p class="text-sm text-red-900 dark:text-red-100">{{ $selectedLeave->rejected_at?->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>
                            @if($selectedLeave->rejection_reason)
                                <div class="mt-2">
                                    <label class="text-xs font-medium text-red-700 dark:text-red-400">Reason</label>
                                    <p class="text-sm text-red-900 dark:text-red-100">{{ $selectedLeave->rejection_reason }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button wire:click="closeDetailsModal" type="button"
                        class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                        Close
                    </button>
                    @if($selectedLeave->status === 'pending')
                        <button wire:click="openApprovalModal({{ $selectedLeave->id }})" type="button"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                            Approve
                        </button>
                        <button wire:click="openRejectionModal({{ $selectedLeave->id }})" type="button"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                            Reject
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Approval Modal -->
    @if($showApprovalModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Approve Leave Application</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Approval Notes (Optional)
                    </label>
                    <textarea wire:model="approval_notes" rows="4"
                        placeholder="Add any notes or comments about this approval..."
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-green-500"></textarea>
                    @error('approval_notes') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="closeApprovalModal" type="button"
                        class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                    <button wire:click="approveLeave" type="button"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Approve Leave
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Rejection Modal -->
    @if($showRejectionModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Reject Leave Application</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Rejection Reason *
                    </label>
                    <textarea wire:model="rejection_reason" rows="4" required
                        placeholder="Please provide a reason for rejecting this leave application..."
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-red-500"></textarea>
                    @error('rejection_reason') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="closeRejectionModal" type="button"
                        class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                    <button wire:click="rejectLeave" type="button"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reject Leave
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
