<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Clock-In Board - All Employees</h1>
            <p class="text-gray-600">View all clock-ins for a date range</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-600">Branch: <strong>{{ $branch->name ?? 'All' }}</strong></p>
            <p class="text-sm text-gray-600">Period: {{ Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} to {{ Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-5 gap-4">
        <div class="card bg-blue-50 border-l-4 border-blue-500">
            <div class="text-3xl font-bold text-blue-600">{{ $stats['total_clocked'] }}</div>
            <div class="text-gray-600 text-sm">Total Clocked</div>
        </div>
        <div class="card bg-green-50 border-l-4 border-green-500">
            <div class="text-3xl font-bold text-green-600">{{ $stats['on_time_count'] }}</div>
            <div class="text-gray-600 text-sm">On Time</div>
        </div>
        <div class="card bg-yellow-50 border-l-4 border-yellow-500">
            <div class="text-3xl font-bold text-yellow-600">{{ $stats['early_count'] }}</div>
            <div class="text-gray-600 text-sm">Early</div>
        </div>
        <div class="card bg-red-50 border-l-4 border-red-500">
            <div class="text-3xl font-bold text-red-600">{{ $stats['late_count'] }}</div>
            <div class="text-gray-600 text-sm">Late</div>
            <div class="text-xs text-gray-500">{{ $stats['late_percentage'] }}%</div>
        </div>
        <div class="card bg-purple-50 border-l-4 border-purple-500">
            <div class="text-3xl font-bold text-purple-600">{{ $stats['total_hours_worked'] }}h</div>
            <div class="text-gray-600 text-sm">Total Hours</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card space-y-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Filters</h3>
            <div class="flex gap-2">
                <button wire:click="resetFilters" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    Reset Filters
                </button>
                <button wire:click="exportCsv" class="text-sm bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
                    Export CSV
                </button>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
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

            <!-- Department Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select wire:model.live="selectedDepartment" class="form-control w-full">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Search Employee -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" wire:model.live="searchEmployee" 
                       placeholder="Name, email, or employee #"
                       class="form-control w-full">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <!-- Sort By -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                <select wire:model.live="sortBy" class="form-control w-full">
                    <option value="clock_in">Clock-In Time</option>
                    <option value="name">Employee Name</option>
                    <option value="department">Department</option>
                </select>
            </div>

            <!-- Sort Direction -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Direction</label>
                <select wire:model.live="sortDirection" class="form-control w-full">
                    <option value="desc">Descending (Newest First)</option>
                    <option value="asc">Ascending (Oldest First)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Clock-In Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Date</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Employee</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Employee #</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Department</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Shift Type</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Clock-In</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Clock-Out</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Duration</th>
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
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $shift->employee->name }}</div>
                                <div class="text-sm text-gray-500">{{ $shift->employee->email }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $shift->employee->employee_number }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $shift->department->name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
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
                                    <span class="text-yellow-600 font-semibold">Active</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                @if($shift->clock_out)
                                    {{ $hours }}h {{ $minutes }}m
                                @else
                                    <span class="text-yellow-600">Ongoing</span>
                                @endif
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
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <p class="text-lg font-medium">No clock-in records found</p>
                                <p class="text-sm">Try adjusting the date range, department, or search filters</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Showing {{ $shifts->count() }} of {{ $shifts->total() }} records
        </div>
        <div>
            {{ $shifts->links() }}
        </div>
    </div>
</div>
