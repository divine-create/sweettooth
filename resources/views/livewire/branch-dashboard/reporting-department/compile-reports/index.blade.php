<div>
    <div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
        @php
            $formatValue = function ($value) {
                if (is_null($value) || $value === '') {
                    return '—';
                }
                if (is_numeric($value)) {
                    return number_format($value, 2);
                }
                if (is_array($value)) {
                    if (empty($value)) {
                        return '—';
                    }
                    $isAssoc = \Illuminate\Support\Arr::isAssoc($value);
                    if (!$isAssoc && collect($value)->every(fn($item) => is_scalar($item))) {
                        return implode(', ', $value);
                    }
                    return 'Items: '.count($value);
                }
                if (is_object($value)) {
                    return method_exists($value, '__toString') ? (string) $value : '—';
                }
                return (string) $value;
            };
        @endphp
        <!-- Header Section -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Compile Reports</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Select and compile department reports</p>
            </div>

            <div class="flex gap-3">
                @if(count($selectedReports) > 0)
                    <button wire:click="deselectAll"
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 flex items-center gap-2">
                        Deselect All ({{ count($selectedReports) }})
                    </button>
                    <button wire:click="openCompileModal"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        Compile Selected
                    </button>
                @else
                    <button wire:click="selectAll"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                        Select All
                    </button>
                @endif
            </div>
        </div>

        <!-- Filters Section -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                    <input type="date" wire:model.live="periodFrom"
                           class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                    <input type="date" wire:model.live="periodTo"
                           class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                    <select wire:model.live="filterCategory"
                            class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="all">All Categories</option>
                        <option value="production">Production ({{ $categoryCounts['production'] }})</option>
                        <option value="sales">Sales ({{ $categoryCounts['sales'] }})</option>
                        <option value="inventory">Inventory ({{ $categoryCounts['inventory'] }})</option>
                        <option value="accounting">Accounting ({{ $categoryCounts['accounting'] }})</option>
                        <option value="hr">HR ({{ $categoryCounts['hr'] }})</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select wire:model.live="filterStatus"
                            class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="all">All Status</option>
                        <option value="pending_review">Pending Review</option>
                        <option value="reviewed">Reviewed</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Reports List -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <input type="checkbox" wire:click="toggleSelectAll"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Report Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Department
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Category
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Generated By
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($availableReports as $report)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ in_array($report->id, $selectedReports) ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox"
                                           wire:click="toggleReport('{{ $report->id }}')"
                                           {{ in_array($report->id, $selectedReports) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ \Carbon\Carbon::parse($report->report_date)->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $report->department?->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $report->report_category === 'production' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' : '' }}
                                        {{ $report->report_category === 'sales' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }}
                                        {{ $report->report_category === 'inventory' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                                        {{ $report->report_category === 'accounting' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }}">
                                        {{ ucfirst($report->report_category) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $report->generatedBy?->name ?? 'System' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $report->status === 'pending_review' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }}
                                        {{ $report->status === 'reviewed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }}">
                                        {{ str_replace('_', ' ', ucfirst($report->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($report->canBeReviewed())
                                        <button wire:click="openReviewModal('{{ $report->id }}')"
                                                class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                            Review
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Reviewed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto mb-4 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    <p class="text-lg font-medium">No reports available</p>
                                    <p class="text-sm mt-1">Try adjusting your filters or check back later</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Compile Modal -->
    @if($showCompileModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" wire:click="$set('showCompileModal', false)"></div>

            <!-- Modal Container -->
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <!-- Modal Content -->
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full overflow-hidden">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Compile Reports</h3>
                            <button wire:click="$set('showCompileModal', false)" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Compilation Title</label>
                                <input type="text" wire:model="compilationTitle"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                @error('compilationTitle') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea wire:model="compilationDescription" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Period From</label>
                                    <input type="date" wire:model="periodFrom"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @error('periodFrom') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Period To</label>
                                    <input type="date" wire:model="periodTo"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @error('periodTo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    You are compiling <strong class="text-blue-600 dark:text-blue-400">{{ count($selectedReports) }}</strong> report(s)
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 flex justify-end gap-3">
                        <button wire:click="$set('showCompileModal', false)" type="button"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button wire:click="compileReports" {{ $isCompiling ? 'disabled' : '' }}
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ $isCompiling ? 'Compiling...' : 'Compile Reports' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Review Modal -->
    @if($showReviewModal && $selectedReport)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" wire:click="closeReviewModal"></div>

            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Review Report</h3>
                            <button wire:click="closeReviewModal" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-4 max-h-[70vh] overflow-y-auto space-y-4">
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            <p><strong>Report:</strong> {{ $selectedReport->report_name }}</p>
                            <p><strong>Department:</strong> {{ $selectedReport->department?->name ?? 'N/A' }}</p>
                            <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($selectedReport->report_date)->format('M d, Y') }}</p>
                        </div>

                        <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $selectedReport->id, 'b_id' => $b_id]) }}"
                           class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700 w-fit">
                            View Full Report
                        </a>

                        @include('livewire.branch-dashboard.reporting-department.view-compiled.report-details', ['report' => $selectedReport, 'formatValue' => $formatValue])

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Review Notes</label>
                            <textarea wire:model="reviewNotes" rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 flex justify-end gap-3">
                        <button wire:click="closeReviewModal" type="button"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button wire:click="rejectReport"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            Reject
                        </button>
                        <button wire:click="approveReport"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Approve
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
