<div x-show="activeTab === 'employment'" class="tab-content">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Branch</label>
                <p class="text-zinc-900 dark:text-white font-medium">{{ $employee->branch->name ?? '—' }}</p>
            </div>

            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Department</label>
                <p class="text-zinc-900 dark:text-white font-medium">{{ $employee->department->name ?? '—' }}</p>
            </div>

            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Position</label>
                <p class="text-zinc-900 dark:text-white font-medium">{{ $employee->position ?? '—' }}</p>
            </div>

            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Hire Date</label>
                <p class="text-zinc-900 dark:text-white font-medium">
                    {{ $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('M d, Y') : '—' }}
                    @if($employee->hire_date)
                        <span class="text-xs text-zinc-500">({{ \Carbon\Carbon::parse($employee->hire_date)->diffForHumans() }})</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Status</label>
                <p class="text-zinc-900 dark:text-white font-medium">{{ ucfirst($employee->status ?? '—') }}</p>
            </div>

            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Shift Preference</label>
                <p class="text-zinc-900 dark:text-white font-medium">{{ ucfirst($employee->shift_preference ?? '—') }}</p>
            </div>

            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Probation End Date</label>
                <p class="text-zinc-900 dark:text-white font-medium">
                    {{ $employee->probation_end_date ? \Carbon\Carbon::parse($employee->probation_end_date)->format('M d, Y') : '—' }}
                </p>
            </div>

            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Termination Date</label>
                <p class="text-zinc-900 dark:text-white font-medium">
                    {{ $employee->termination_date ? \Carbon\Carbon::parse($employee->termination_date)->format('M d, Y') : '—' }}
                </p>
            </div>
        </div>
    </div>
</div>