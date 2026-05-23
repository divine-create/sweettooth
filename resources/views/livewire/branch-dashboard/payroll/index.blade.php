<div class="space-y-6">
    {{-- Header --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">HR / Payroll</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Payroll</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Manage payroll runs and individual payslips.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ branch_route('branch-dashboard.accounting.payroll.run.create') }}" wire:navigate
                   class="rounded-full bg-zinc-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    + New Payrun
                </a>
                <a href="{{ branch_route('branch-dashboard.accounting.payroll.payslip.create') }}" wire:navigate
                   class="rounded-full border border-zinc-300 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300">
                    + Individual Payslip
                </a>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Payroll Runs</p>
            <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">{{ $stats['total_runs'] }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Pending Approval</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['pending_approval'] }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Paid This Month (Net)</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ \App\Helpers\LocalizationHelper::formatCurrency($stats['total_paid_this_month']) }}</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 rounded-xl border border-zinc-200 bg-zinc-50 p-1 dark:border-zinc-700 dark:bg-zinc-800/50 w-fit">
        <button wire:click="$set('tab', 'runs')"
            class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === 'runs' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
            Payroll Runs
        </button>
        <button wire:click="$set('tab', 'standalone')"
            class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === 'standalone' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
            Individual Payslips
        </button>
    </div>

    {{-- Filters --}}
    <div class="flex gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search..."
               class="w-64 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" />
        <select wire:model.live="status_filter"
                class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="approved">Approved</option>
            <option value="paid">Paid</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>

    {{-- Runs Table --}}
    @if ($tab === 'runs')
    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Run Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Period</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Employees</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Total Gross</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Total Net</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($runs as $run)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $run->name }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                        {{ $run->pay_period_start->format('d M') }} – {{ $run->pay_period_end->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ $run->payrolls_count ?? $run->payrolls()->count() }}</td>
                    <td class="px-4 py-3 text-right font-medium text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($run->total_gross) }}</td>
                    <td class="px-4 py-3 text-right font-medium text-emerald-600">{{ \App\Helpers\LocalizationHelper::formatCurrency($run->total_net) }}</td>
                    <td class="px-4 py-3 text-center">
                        @include('livewire.branch-dashboard.payroll.partials.status-badge', ['status' => $run->status])
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ branch_route('branch-dashboard.accounting.payroll.run.view', ['run' => $run->id]) }}"
                           wire:navigate class="text-xs font-medium text-blue-600 hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-400">No payroll runs found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">
            {{ $runs->links() }}
        </div>
    </div>
    @endif

    {{-- Standalone Payslips Table --}}
    @if ($tab === 'standalone')
    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Employee</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Period</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Base Salary</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Gross</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Net</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($standalone as $payslip)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $payslip->employee?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                        {{ \Carbon\Carbon::parse($payslip->pay_period_start)->format('d M') }} – {{ \Carbon\Carbon::parse($payslip->pay_period_end)->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ \App\Helpers\LocalizationHelper::formatCurrency($payslip->base_salary) }}</td>
                    <td class="px-4 py-3 text-right font-medium text-zinc-900 dark:text-white">{{ \App\Helpers\LocalizationHelper::formatCurrency($payslip->gross_salary) }}</td>
                    <td class="px-4 py-3 text-right font-medium text-emerald-600">{{ \App\Helpers\LocalizationHelper::formatCurrency($payslip->net_salary) }}</td>
                    <td class="px-4 py-3 text-center">
                        @include('livewire.branch-dashboard.payroll.partials.status-badge', ['status' => $payslip->status])
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ branch_route('branch-dashboard.accounting.payroll.payslip.view', ['payroll' => $payslip->id]) }}"
                           wire:navigate class="text-xs font-medium text-blue-600 hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-400">No individual payslips found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">
            {{ $standalone->links() }}
        </div>
    </div>
    @endif
</div>
