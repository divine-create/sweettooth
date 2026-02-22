<div class="p-3 space-y-3">
    <x-breadcrumb title="Manage Leave Allocations" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Leave Management'],
        ['label' => 'Manage Allocations'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Manage Leave Allocations</h2>
                <p class="text-sm opacity-90 mt-1">
                    Allocate leave days to employees for the selected year
                </p>
            </div>
            <button wire:click="bulkAllocateDefaults"
                class="px-4 py-2 bg-white text-emerald-600 rounded-lg font-medium hover:bg-emerald-50 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Bulk Allocate Defaults
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by employee name or number..."
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-emerald-500">
            </div>

            <!-- Year Selection -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Year</label>
                <select wire:model.live="selectedYear"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-emerald-500">
                    @for($year = now()->year - 1; $year <= now()->year + 1; $year++)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endfor
                </select>
            </div>
        </div>
    </div>

    <!-- Employees Table -->
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
                    @forelse($rows as $employee)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                            <!-- Employee -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $employee->name }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $employee->employee_number }}</div>
                            </td>

                            <!-- Allocations -->
                            <td class="px-4 py-3">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    @php
                                        $allocationCount = \App\Models\EmployeeLeaveAllocation::where('employee_id', $employee->id)
                                            ->where('year', $selectedYear)
                                            ->count();
                                    @endphp
                                    {{ $allocationCount }} leave type(s) allocated
                                </div>
                            </td>

                            <!-- Action -->
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <button wire:click="openAllocationModal({{ $employee->id }})"
                                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-medium transition-colors">
                                    Allocate Days
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-12 h-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3a6 6 0 016-6h6a6 6 0 016 6h-4m0 0h.01M9 20h6" />
                                    </svg>
                                    <p class="text-zinc-500 dark:text-zinc-400">No employees found</p>
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

    <!-- Allocation Modal -->
    @if($showAllocationModal && $selectedEmployee)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full mx-4 shadow-xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                            Allocate Leave for {{ $selectedEmployee->name }}
                        </h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Year: {{ $selectedYear }}</p>
                    </div>
                    <button wire:click="closeAllocationModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveAllocations" class="space-y-4">
                    <!-- Leave Type Allocations -->
                    <div class="space-y-3">
                        @forelse($allocations as $index => $allocation)
                            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $allocation['leave_type_name'] }}
                                        </span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                            (Default: {{ $allocation['default_days'] }} days)
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <!-- Allocated Days -->
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                            Days to Allocate
                                        </label>
                                        <input type="number" wire:model.lazy="allocations.{{ $index }}.allocated_days" min="0" step="0.5"
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-emerald-500">
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                            Notes (Optional)
                                        </label>
                                        <textarea wire:model.lazy="allocations.{{ $index }}.notes" rows="2"
                                            placeholder="Add any notes about this allocation..."
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-emerald-500"></textarea>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-4">
                                No leave types available
                            </p>
                        @endforelse
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <button type="button" wire:click="closeAllocationModal"
                            class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Save Allocations
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
