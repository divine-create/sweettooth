<div class="space-y-6">
    <!-- Header with Back Button -->
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('branch-dashboard.clock-in-board.today', ['b_id' => $b_id]) }}" 
           class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
            <span>← Back to Clock-In Board</span>
        </a>
    </div>

    <!-- Employee Header -->
    @if($currentEmployee)
        <div class="card bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-500">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $currentEmployee->name }}</h1>
                    <div class="mt-2 space-y-1 text-gray-700">
                        <p><strong>Employee #:</strong> {{ $currentEmployee->employee_number }}</p>
                        <p><strong>Department:</strong> {{ $currentEmployee->department->name ?? 'N/A' }}</p>
                        <p><strong>Email:</strong> {{ $currentEmployee->email }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">Hire Date</p>
                    <p class="text-lg font-semibold">{{ $currentEmployee->hire_date->format('M d, Y') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-5 gap-4">
        <div class="card border-l-4 border-blue-500">
            <div class="text-2xl font-bold text-blue-600">{{ $stats['total_shifts'] }}</div>
            <div class="text-sm text-gray-600">Total Shifts</div>
        </div>
        <div class="card border-l-4 border-green-500">
            <div class="text-2xl font-bold text-green-600">{{ $stats['total_hours_worked'] }}h</div>
            <div class="text-sm text-gray-600">Total Hours</div>
            <div class="text-xs text-gray-500">{{ $stats['average_hours_per_shift'] }}h/shift avg</div>
        </div>
        <div class="card border-l-4 border-yellow-500">
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['early_count'] }}</div>
            <div class="text-sm text-gray-600">Early Arrivals</div>
        </div>
        <div class="card border-l-4 border-red-500">
            <div class="text-2xl font-bold text-red-600">{{ $stats['late_count'] }}</div>
            <div class="text-sm text-gray-600">Late Arrivals</div>
            <div class="text-xs text-gray-500">{{ $stats['late_percentage'] }}% of shifts</div>
        </div>
        <div class="card border-l-4 border-blue-500">
            <div class="text-2xl font-bold text-blue-600">{{ $stats['on_time_count'] }}</div>
            <div class="text-sm text-gray-600">On Time</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card space-y-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Filters</h3>
            <div class="flex gap-2">
                <button wire:click="resetFilters" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    Reset
                </button>
                <button wire:click="exportCsv" class="text-sm bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
                    Export CSV
                </button>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                <input type="date" wire:model.live="dateFrom" class="form-control w-full">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                <input type="date" wire:model.live="dateTo" class="form-control w-full">
            </div>

            <!-- Department -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select wire:model.live="selectedDepartment" class="form-control w-full">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Clock-In History Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Date</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Shift Type</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Clock In</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Clock Out</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Duration</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Department</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($shifts as $shift)
                        @php
                            $timeStatus = $this->getTimeStatus($shift);
                            $duration = $shift->clock_out
                                ? $shift->clock_in->diffInMinutes($shift->clock_out)
                                : 0;
                            $hours = intdiv($duration, 60);
                            $minutes = $duration % 60;
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ $shift->shift_date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">
                                    {{ ucfirst($shift->shift_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $shift->clock_in->format('H:i') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                @if($shift->clock_out)
                                    {{ $shift->clock_out->format('H:i') }}
                                @else
                                    <span class="text-yellow-600">Active</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                @if($shift->clock_out)
                                    {{ $hours }}h {{ $minutes }}m
                                @else
                                    <span class="text-yellow-600">Ongoing</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $shift->department->name }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded text-sm font-medium
                                    @if($timeStatus['status'] === 'on_time') bg-green-100 text-green-800
                                    @elseif($timeStatus['status'] === 'early') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ $timeStatus['label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <p class="text-lg font-medium">No clock-in records found</p>
                                <p class="text-sm">Try adjusting the date range or department filter</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center">
        {{ $shifts->links() }}
    </div>
</div>
