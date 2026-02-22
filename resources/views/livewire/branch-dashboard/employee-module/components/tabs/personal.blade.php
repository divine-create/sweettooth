<div x-show="activeTab === 'personal'" class="tab-content">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Employee Number</label>
            <p class="text-zinc-900 dark:text-white font-medium">{{ $employee->employee_number ?? '—' }}</p>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Email</label>
            <p class="text-zinc-900 dark:text-white font-medium break-all text-sm">{{ $employee->email ?? '—' }}</p>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Phone</label>
            <p class="text-zinc-900 dark:text-white font-medium">{{ $employee->phone ?? '—' }}</p>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Date of Birth</label>
            <p class="text-zinc-900 dark:text-white font-medium">
                {{ $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('M d, Y') : '—' }}
                @if($employee->date_of_birth)
                    <span class="text-xs text-zinc-500 block">({{ \Carbon\Carbon::parse($employee->date_of_birth)->age }} years old)</span>
                @endif
            </p>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Gender</label>
            <p class="text-zinc-900 dark:text-white font-medium">{{ ucfirst($employee->gender ?? '—') }}</p>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Nationality</label>
            <p class="text-zinc-900 dark:text-white font-medium">{{ $employee->nationality ?? '—' }}</p>
        </div>

        <div class="lg:col-span-2 bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Address</label>
            <p class="text-zinc-900 dark:text-white font-medium">{{ $employee->address ?? '—' }}</p>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg lg:col-span-3">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Allergies / Medical Notes</label>
            <p class="text-zinc-900 dark:text-white font-medium">{{ $employee->allergies ?? '—' }}</p>
        </div>
    </div>
</div>