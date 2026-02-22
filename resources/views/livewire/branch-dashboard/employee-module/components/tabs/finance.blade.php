<div x-show="activeTab === 'financial'" class="tab-content">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg border-2 border-blue-200 dark:border-blue-800">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Monthly Salary</label>
            <p class="text-zinc-900 dark:text-white font-bold text-lg">
                {{ $employee->salary ? \App\Helpers\LocalizationHelper::formatCurrency($employee->salary) : '—' }}
            </p>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Hourly Rate</label>
            <p class="text-zinc-900 dark:text-white font-medium">
                {{ $employee->hourly_rate ? \App\Helpers\LocalizationHelper::formatCurrency($employee->hourly_rate) : '—' }}
            </p>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Tax ID</label>
            <p class="text-zinc-900 dark:text-white font-medium">{{ $employee->tax_id ?? '—' }}</p>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Bank Account</label>
            <p class="text-zinc-900 dark:text-white font-medium text-sm break-all">{{ $employee->bank_account ?? '—' }}</p>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Last Performance Review</label>
            <p class="text-zinc-900 dark:text-white font-medium">
                {{ $employee->last_performance_review_date ? \Carbon\Carbon::parse($employee->last_performance_review_date)->format('M d, Y') : '—' }}
            </p>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Performance Rating</label>
            <p class="text-zinc-900 dark:text-white font-medium">
                @if($employee->performance_rating)
                    <span class="inline-flex items-center gap-1">
                        {{ number_format($employee->performance_rating, 1) }}/5.0
                        <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </span>
                @else
                    —
                @endif
            </p>
        </div>
    </div>
</div>
