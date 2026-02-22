<div class="w-full">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold">Managing Director Reports</h2>
        </div>
    </x-slot>

    <div class="p-6">
        <!-- Description -->
        <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <p class="text-blue-800 dark:text-blue-200">
                <strong>Managing Director Reports:</strong> This section contains compiled reports from various departments across all branches.
                These reports are reviewed by the Managing Director to make strategic decisions for the organization.
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow">
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Reports</p>
                <p class="text-3xl font-bold mt-2">{{ $stats['total_reports'] }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow">
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Pending Review</p>
                <p class="text-3xl font-bold mt-2">{{ $stats['pending_review'] }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow">
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Reviewed</p>
                <p class="text-3xl font-bold mt-2">{{ $stats['reviewed'] }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow">
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">This Month</p>
                <p class="text-3xl font-bold mt-2">{{ $stats['this_month'] }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Status</label>
                    <select wire:model="filterStatus" class="w-full rounded-lg border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700">
                        <option value="all">All Statuses</option>
                        <option value="sent_to_md">Sent to MD</option>
                        <option value="reviewed_by_md">Reviewed by MD</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Date From</label>
                    <input type="date" wire:model="dateFrom" class="w-full rounded-lg border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Date To</label>
                    <input type="date" wire:model="dateTo" class="w-full rounded-lg border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700">
                </div>
            </div>
        </div>

        <!-- Reports Table -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-100 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Branch Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Compiled By</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Compilation Date</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($compiledReports as $report)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <td class="px-6 py-4">{{ $report->branch?->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $report->compiledBy?->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $report->compilation_date?->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $report->status === 'reviewed_by_md' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                    {{ ucfirst(str_replace('_', ' ', $report->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('branch-dashboard.md-reports.view', $report->id) }}" wire:navigate class="text-blue-600 hover:text-blue-800">
                                    View Report
                                </a>
                                @if($report->status === 'sent_to_md')
                                    <button wire:click="markAsReviewed({{ $report->id }})" class="ml-3 text-green-600 hover:text-green-800">
                                        Mark as Reviewed
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No reports found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $compiledReports->links() }}
        </div>
    </div>
</div>
