<style>
    /* Clock-in Board specific styles */
    .clock-in-card {
        @apply bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6;
    }
    
    .stat-card {
        @apply bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4;
    }
    
    .stat-number {
        @apply text-2xl font-bold text-gray-900 dark:text-white;
    }
    
    .stat-label {
        @apply text-sm text-gray-600 dark:text-gray-400;
    }
    
    .filter-card {
        @apply bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4;
    }
    
    .table-container {
        @apply bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden;
    }
    
    .table-header {
        @apply bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold tracking-wider;
    }
    
    .table-row {
        @apply border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors;
    }
    
    .table-cell {
        @apply px-6 py-4 text-sm text-gray-900 dark:text-gray-100;
    }
    
    .status-on-time {
        @apply px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100;
    }
    
    .status-early {
        @apply px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100;
    }
    
    .status-late {
        @apply px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100;
    }
    
    .shift-type-badge {
        @apply px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 rounded text-xs font-semibold;
    }
    
    .active-duration {
        @apply text-yellow-600 dark:text-yellow-400 font-semibold;
    }
    
    .reset-filter-btn {
        @apply text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300;
    }
    
    .action-btn {
        @apply text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium;
    }
</style>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Clock-In Board</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ today()->format('l, F j, Y') }}</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-600 dark:text-gray-400">Branch: <strong class="text-gray-900 dark:text-white">{{ $branch->name ?? 'All' }}</strong></p>
            <p class="text-sm text-gray-600 dark:text-gray-400">Last refreshed: <span id="refresh-time" class="text-gray-900 dark:text-white">now</span></p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="stat-number text-blue-600 dark:text-blue-400">{{ $stats['total_clocked'] }}</div>
            <div class="stat-label">Total Clocked In</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-green-600 dark:text-green-400">{{ $stats['on_time_count'] }}</div>
            <div class="stat-label">On Time</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-yellow-600 dark:text-yellow-400">{{ $stats['early_count'] }}</div>
            <div class="stat-label">Early</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-red-600 dark:text-red-400">{{ $stats['late_count'] }}</div>
            <div class="stat-label">Late</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Filters</h3>
            <button wire:click="$set('selectedDepartment', null); $set('searchEmployee', '')"
                    class="reset-filter-btn">
                Reset Filters
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Department Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department</label>
                <select wire:model.live="selectedDepartment" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Search Employee -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search Employee</label>
                <input type="text" wire:model.live="searchEmployee"
                       placeholder="Name, email, or employee #"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Sort By -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
                <select wire:model.live="sortBy" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                    <option value="clock_in">Clock-In Time</option>
                    <option value="name">Employee Name</option>
                    <option value="department">Department</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Clock-In Table -->
    <div class="table-container">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Employee</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Employee #</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Department</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Shift Type</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Clock-In Time</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Duration</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $shift)
                        @php
                            $timeStatus = $this->getTimeStatus($shift);
                            $duration = $shift->clock_out
                                ? $shift->clock_in->diffInMinutes($shift->clock_out)
                                : now()->diffInMinutes($shift->clock_in);
                            $hours = intdiv($duration, 60);
                            $minutes = $duration % 60;
                        @endphp
                        <tr class="table-row">
                            <td class="table-cell">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $shift->employee->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $shift->employee->email }}</div>
                            </td>
                            <td class="table-cell text-gray-700 dark:text-gray-300">
                                {{ $shift->employee->employee_number }}
                            </td>
                            <td class="table-cell text-gray-700 dark:text-gray-300">
                                {{ $shift->department->name }}
                            </td>
                            <td class="table-cell">
                                <span class="shift-type-badge">
                                    {{ ucfirst($shift->shift_type) }}
                                </span>
                            </td>
                            <td class="table-cell text-gray-700 dark:text-gray-300">
                                {{ $shift->clock_in->format('H:i') }}
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $shift->clock_in->format('M d, Y') }}
                                </div>
                            </td>
                            <td class="table-cell">
                                <span class="
                                    @if($timeStatus['status'] === 'on_time') status-on-time
                                    @elseif($timeStatus['status'] === 'early') status-early
                                    @else status-late
                                    @endif">
                                    {{ $timeStatus['label'] }}
                                </span>
                            </td>
                            <td class="table-cell text-gray-700 dark:text-gray-300">
                                @if($shift->clock_out)
                                    {{ $hours }}h {{ $minutes }}m
                                @else
                                    <span class="active-duration">
                                        Active ({{ $hours }}h {{ $minutes }}m so far)
                                    </span>
                                @endif
                            </td>
                            <td class="table-cell">
                                <button wire:click="viewEmployeeHistory({{ $shift->employee->id }})"
                                        class="action-btn">
                                    History →
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <p class="text-lg font-medium text-gray-900 dark:text-white">No clock-ins yet today</p>
                                <p class="text-sm">Check back later as employees clock in</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-refresh every 30 seconds
    document.addEventListener('livewire:initialized', () => {
        const refreshElement = document.getElementById('refresh-time');
        if (refreshElement) {
            setInterval(() => {
                Livewire.dispatch('$refresh');
                refreshElement.textContent = new Date().toLocaleTimeString();
            }, 30000);
        }
    });
</script>
@endpush