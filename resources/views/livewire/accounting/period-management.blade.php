<div class="space-y-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Accounting Periods</h2>
            <button wire:click="$toggle('showCreateForm')" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                {{ $showCreateForm ? 'Cancel' : 'Create Period' }}
            </button>
        </div>

        <!-- Create Form -->
        @if ($showCreateForm)
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
                <h3 class="text-lg font-semibold mb-4">Create New Period</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                        <input type="number" wire:model="createYear" class="w-full px-3 py-2 border border-gray-300 rounded-md" min="2020" max="2100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                        <select wire:model="createMonth" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Select Month</option>
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}">{{ \Carbon\Carbon::createFromFormat('n', $i)->format('F') }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button wire:click="createPeriod" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Create
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Periods List -->
        @if (!empty($periods))
            <div class="space-y-3">
                @foreach ($periods as $period)
                    <div class="border rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="text-lg font-semibold">{{ $period['name'] }}</h4>
                                <p class="text-sm text-gray-600">{{ $period['start'] }} to {{ $period['end'] }}</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $period['status'] === 'Open' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $period['status'] === 'Closed' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $period['status'] === 'Locked' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ $period['status'] }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-sm text-gray-600 mb-3">
                            <span>{{ $period['entries_count'] }} entries posted</span>
                        </div>

                        <div class="flex gap-2">
                            @if ($period['status'] === 'Open')
                                <button wire:click="$set('periodToClose', {{ $period['id'] }})" 
                                        class="px-3 py-1 bg-yellow-500 text-white text-xs rounded hover:bg-yellow-600">
                                    Close Period
                                </button>
                            @elseif ($period['status'] === 'Closed')
                                <button wire:click="lockPeriod({{ $period['id'] }})" 
                                        class="px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600">
                                    Lock Period
                                </button>
                                <button wire:click="reopenPeriod({{ $period['id'] }})" 
                                        class="px-3 py-1 bg-gray-500 text-white text-xs rounded hover:bg-gray-600">
                                    Reopen
                                </button>
                            @elseif ($period['status'] === 'Locked')
                                <span class="px-3 py-1 bg-gray-200 text-gray-700 text-xs rounded">
                                    Locked - No Actions Available
                                </span>
                            @endif
                        </div>

                        <!-- Close Dialog -->
                        @if ($periodToClose === $period['id'])
                            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                <p class="text-sm font-semibold mb-2">Close Period: {{ $period['name'] }}</p>
                                <textarea wire:model="closingNotes" placeholder="Closing notes (optional)..." 
                                          class="w-full px-3 py-2 border border-gray-300 rounded text-sm mb-2"></textarea>
                                <div class="flex gap-2">
                                    <button wire:click="closePeriod({{ $period['id'] }})" 
                                            class="px-3 py-1 bg-yellow-600 text-white text-xs rounded hover:bg-yellow-700">
                                        Confirm Close
                                    </button>
                                    <button wire:click="$set('periodToClose', null)" 
                                            class="px-3 py-1 bg-gray-400 text-white text-xs rounded hover:bg-gray-500">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-4 bg-gray-50 border border-gray-200 rounded text-center text-gray-500">
                No accounting periods yet
            </div>
        @endif
    </div>
</div>
