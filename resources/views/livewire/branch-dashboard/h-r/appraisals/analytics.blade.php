<div>
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Appraisal Analytics Dashboard</h2>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Time Period</label>
                    <select wire:model.live="selectedPeriod"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="last_3_months">Last 3 Months</option>
                        <option value="last_6_months">Last 6 Months</option>
                        <option value="last_12_months">Last 12 Months</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>

                @if($selectedPeriod === 'custom')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                        <input type="date" wire:model.live="customStartDate"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                        <input type="date" wire:model.live="customEndDate"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                @else
                    <div></div>
                    <div></div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department</label>
                    <select wire:model.live="departmentFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All Departments</option>
                        @foreach($this->departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="px-6 py-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Appraisals Completion -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clipboard-check text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold">{{ $this->analyticsData['completion_rate'] }}%</div>
                            <div class="text-blue-100">Appraisal Completion Rate</div>
                        </div>
                    </div>
                    <div class="mt-4 text-sm">
                        {{ $this->analyticsData['completed_appraisals'] }} of {{ $this->analyticsData['total_appraisals'] }} completed
                    </div>
                </div>

                <!-- Average Rating -->
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-star text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold">{{ $this->analyticsData['average_rating'] }}</div>
                            <div class="text-green-100">Average Rating</div>
                        </div>
                    </div>
                    <div class="mt-4 text-sm">
                        Out of 5.0 scale
                    </div>
                </div>

                <!-- Goal Completion -->
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-target text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold">{{ $this->analyticsData['goal_completion_rate'] }}%</div>
                            <div class="text-purple-100">Goal Completion Rate</div>
                        </div>
                    </div>
                    <div class="mt-4 text-sm">
                        {{ $this->analyticsData['completed_goals'] }} of {{ $this->analyticsData['total_goals'] }} completed
                    </div>
                </div>

                <!-- Feedback Completion -->
                <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-comments text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold">{{ $this->analyticsData['feedback_completion_rate'] }}%</div>
                            <div class="text-orange-100">Feedback Completion Rate</div>
                        </div>
                    </div>
                    <div class="mt-4 text-sm">
                        {{ $this->analyticsData['completed_feedbacks'] }} of {{ $this->analyticsData['total_feedback_requests'] }} completed
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Rating Distribution -->
                <div class="bg-white dark:bg-gray-700 p-6 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Rating Distribution</h3>
                    <div class="space-y-3">
                        @for($i = 5; $i >= 1; $i--)
                            <div class="flex items-center">
                                <div class="w-12 text-sm font-medium text-gray-600 dark:text-gray-300">{{ $i }} Star{{ $i > 1 ? 's' : '' }}</div>
                                <div class="flex-1 mx-4">
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-4">
                                        <div class="bg-yellow-400 h-4 rounded-full"
                                             style="width: {{ $this->analyticsData['total_appraisals'] > 0 ? (($this->analyticsData['rating_distribution'][$i] ?? 0) / $this->analyticsData['total_appraisals']) * 100 : 0 }}%"></div>
                                    </div>
                                </div>
                                <div class="w-8 text-sm font-medium text-gray-900 dark:text-white">{{ $this->analyticsData['rating_distribution'][$i] ?? 0 }}</div>
                            </div>
                        @endfor
                    </div>
                </div>

                <!-- Monthly Trends -->
                <div class="bg-white dark:bg-gray-700 p-6 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Monthly Completion Trends</h3>
                    <div class="h-64">
                        <canvas id="monthlyTrendsChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Monthly Trends Table -->
            <div class="mt-8 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Monthly Performance</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Month</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Appraisals</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Completed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-700 divide-y divide-gray-200 dark:divide-gray-600">
                            @foreach($this->analyticsData['monthly_trends'] as $month)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $month['month'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $month['total'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $month['completed'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            @if($month['completion_rate'] >= 80) bg-green-100 text-green-800
                                            @elseif($month['completion_rate'] >= 60) bg-yellow-100 text-yellow-800
                                            @else bg-red-100 text-red-800 @endif">
                                            {{ $month['completion_rate'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:loaded', function () {
            const ctx = document.getElementById('monthlyTrendsChart');
            if (ctx) {
                const monthlyData = @json($this->analyticsData['monthly_trends']);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: monthlyData.map(item => item.month),
                        datasets: [{
                            label: 'Completion Rate (%)',
                            data: monthlyData.map(item => item.completion_rate),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</div>