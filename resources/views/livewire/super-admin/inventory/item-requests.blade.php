<div class="p-3 space-y-3">
    <style>
        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            @apply bg-zinc-300 dark:bg-zinc-700 rounded-full;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            @apply bg-zinc-400 dark:bg-zinc-600;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>

    <x-breadcrumb title="Item Requests" :items="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Inventory'],
    ['label' => 'Item Requests'],
    ]" :compact="false" :with-icons="true" />

    @if (session()->has('success'))
        <div
            class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-100 px-3 py-2 rounded text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div
            class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-100 px-3 py-2 rounded text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filters -->
    <div x-data="{ open: false }"
        class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 transition-all duration-300">
        <div class="flex justify-between items-center px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14.293 13H10v5l-4-4v-3.586L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters
            </h2>

            <button @click="open = !open"
                class="flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 8h16M4 16h16" />
                    <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span x-text="open ? 'Close' : 'Show Filters'"></span>
            </button>
        </div>

        <div x-show="open" x-collapse class="p-3 space-y-3">
            <!-- Advanced Search -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Advanced Search</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search by request number, requester..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Branch</label>
            <x-select.styled
            wire:model.live="filterBranch"
            :options="$branches->map(fn($branch) => ['label' => $branch->name, 'value' => $branch->id])->toArray()"
            select="label:label|value:value"
            placeholder="All Branches"
            searchable
            />
            </div>
            <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Department</label>
            <x-select.styled
            wire:model.live="filterDepartment"
            :options="$departments->map(fn($department) => ['label' => $department->name, 'value' => $department->id])->toArray()"
            select="label:label|value:value"
            placeholder="All Departments"
            searchable
            />
            </div>
            <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
            <x-select.styled
            wire:model.live="filterStatus"
            :options="[
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Approved', 'value' => 'approved'],
                ['label' => 'Partially Dispatched', 'value' => 'partially_dispatched'],
                ['label' => 'Completed', 'value' => 'completed'],
                ['label' => 'Rejected', 'value' => 'rejected']
                ]"
                    select="label:label|value:value"
                    placeholder="All Status"
                />
            </div>
        </div>
    </div>


<!-- Requests Table -->
<div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
<div class="px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
<h3 class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Item Requests List</h3>
</div>
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
<thead class="bg-zinc-50 dark:bg-zinc-900">
<tr>
<th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Request #</th>
<th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Branch</th>
<th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Department</th>
<th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Requested By</th>
<th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Request Date</th>
<th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Required Date</th>
<th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Items</th>
<th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
<th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
</tr>
</thead>
<tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($requests as $request)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                <td class="px-3 py-2 text-xs font-medium text-zinc-900 dark:text-zinc-100">{{ $request->request_number }}</td>
                <td class="px-3 py-2 text-xs text-zinc-600 dark:text-zinc-400">{{ $request->branch->name }}</td>
                <td class="px-3 py-2 text-xs text-zinc-600 dark:text-zinc-400">{{ $request->department->name }}</td>
                <td class="px-3 py-2 text-xs text-zinc-600 dark:text-zinc-400">{{ $request->requester->name }}</td>
                <td class="px-3 py-2 text-xs text-zinc-600 dark:text-zinc-400">
                {{ $request->request_date ? $request->request_date->format('Y-m-d') : 'N/A' }}</td>
                <td class="px-3 py-2 text-xs text-zinc-600 dark:text-zinc-400">
                {{ $request->required_date ? $request->required_date->format('Y-m-d') : 'N/A' }}</td>
                <td class="px-3 py-2 text-xs text-zinc-600 dark:text-zinc-400">{{ $request->requestDetails->count() }}</td>
                        <td class="px-3 py-2 text-xs">
                        <span
                        class="px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $request->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200' : '' }}
                        {{ $request->status === 'approved' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200' : '' }}
                        {{ $request->status === 'partially_dispatched' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200' : '' }}
                        {{ $request->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200' : '' }}
                        {{ $request->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200' : '' }}">
                        {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                        </span>
                        </td>
                        <td class="px-3 py-2 text-xs">
                        <div class="flex space-x-2">
                        @can('manage-inventory')
                        @if ($request->status === 'pending')
                        <button wire:click="openApprovalModal({{ $request->id }})"
                        class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                        Approve
                        </button>
                        <button wire:click="rejectRequest({{ $request->id }})"
                        onclick="return confirm('Are you sure you want to reject this request?')"
                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                        Reject
                        </button>
                        @endif
                        @endcan
                        </div>
                        </td>
                    </tr>
                @empty
                <tr>
                <td colspan="9" class="px-3 py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">
                No item requests found.
                </td>
                </tr>
                @endforelse
                </tbody>
                </table>
                </div>
                <div class="px-3 py-2 border-t border-zinc-200 dark:border-zinc-700">
                {{ $requests->links() }}
                </div>
</div>

<!-- Approval Modal -->
@if ($showApprovalModal)
<div class="fixed inset-0 bg-zinc-900/50 dark:bg-zinc-900/80 overflow-y-auto h-full w-full z-50">
<div class="relative top-10 mx-auto p-4 border w-full max-w-md shadow-xl rounded-lg bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
<div class="px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
<h3 class="text-base font-medium text-zinc-900 dark:text-zinc-100">Approve Item Request</h3>
</div>
<div class="p-3">
<form wire:submit.prevent="approveRequest" class="space-y-3">
<div>
<label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Approval Notes</label>
<textarea wire:model="approvalNotes" rows="3"
class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
placeholder="Optional notes..."></textarea>
</div>

<div class="flex justify-end space-x-2 pt-2">
<button type="button" wire:click="closeModal"
class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
Cancel
</button>
<button type="submit"
class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">
Approve Request
</button>
</div>
</form>
</div>
</div>
</div>
@endif
</div>

</div>
