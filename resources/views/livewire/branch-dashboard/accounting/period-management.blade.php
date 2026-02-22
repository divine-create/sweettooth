<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">Accounting Periods</h1>
                <p class="text-zinc-600 dark:text-zinc-400">Create and manage accounting periods</p>
            </div>
            <button 
                wire:click="$toggle('showCreateForm')"
                class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition"
            >
                {{ $showCreateForm ? '✕ Close' : '+ New Period' }}
            </button>
        </div>
    </div>

    <!-- Create Period Form -->
    @if ($showCreateForm)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Create New Period</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Year *</label>
                    <input 
                        wire:model="newYear" 
                        type="number" 
                        min="2020" 
                        :max="now().year + 1"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                    @error('newYear') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Month *</label>
                    <select 
                        wire:model="newMonth"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Select Month</option>
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}">{{ Carbon\Carbon::create(2000, $i, 1)->format('F') }}</option>
                        @endfor
                    </select>
                    @error('newMonth') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Status *</label>
                    <select 
                        wire:model="newStatus"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-4 justify-end">
                <button 
                    type="button"
                    wire:click="$toggle('showCreateForm')"
                    class="px-6 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white hover:bg-zinc-50 dark:hover:bg-zinc-700 font-medium transition"
                >
                    Cancel
                </button>
                <button 
                    type="button"
                    wire:click="createPeriod"
                    class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition"
                >
                    Create Period
                </button>
            </div>
        </div>
    @endif

    <!-- Messages -->
    @if (session()->has('message'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <p class="text-green-800 dark:text-green-300">{{ session('message') }}</p>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-red-800 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Periods Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-zinc-900 dark:text-white">Period</th>
                        <th class="px-6 py-3 text-left font-semibold text-zinc-900 dark:text-white">Start Date</th>
                        <th class="px-6 py-3 text-left font-semibold text-zinc-900 dark:text-white">End Date</th>
                        <th class="px-6 py-3 text-left font-semibold text-zinc-900 dark:text-white">Status</th>
                        <th class="px-6 py-3 text-center font-semibold text-zinc-900 dark:text-white">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($periods as $period)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition" wire:key="period-{{ $period->id }}">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-zinc-900 dark:text-white">
                                    {{ $period->name ?? "{$period->month}/{$period->year}" }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    @if ($selectedPeriodId === $period->id)
                                        <span class="px-2 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 rounded">Selected</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">{{ $period->period_start->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">{{ $period->period_end->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if ($period->status === 'open') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                    @elseif ($period->status === 'closed') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300
                                    @else bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                    @endif
                                ">
                                    {{ ucfirst($period->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button 
                                        wire:click="selectPeriod({{ $period->id }})"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium text-sm"
                                    >
                                        Select
                                    </button>

                                    @if ($period->status === 'open')
                                        <button 
                                            wire:click="closePeriod({{ $period->id }})"
                                            class="text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-300 font-medium text-sm"
                                        >
                                            Close
                                        </button>
                                    @elseif ($period->status === 'closed')
                                        <button 
                                            wire:click="reopenPeriod({{ $period->id }})"
                                            class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 font-medium text-sm"
                                        >
                                            Reopen
                                        </button>
                                    @endif

                                    @if ($period->status !== 'locked')
                                        <button 
                                            wire:click="lockPeriod({{ $period->id }})"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 font-medium text-sm"
                                        >
                                            Lock
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No accounting periods found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
