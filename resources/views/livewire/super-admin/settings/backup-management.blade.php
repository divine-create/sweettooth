<div>
    <h2 class="text-2xl font-bold mb-6 text-zinc-900 dark:text-zinc-100">Database Backup Management</h2>

    {{-- Backup Status Card --}}
    <div class="mb-6 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
        <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Backup Status</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Auto Backup</p>
                <p class="text-lg font-semibold {{ $backupInfo['auto_backup_enabled'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $backupInfo['auto_backup_enabled'] ? 'Enabled' : 'Disabled' }}
                </p>
                @if($backupInfo['auto_backup_enabled'])
                    <p class="text-xs text-zinc-500 dark:text-zinc-500">
                        Every {{ $backupInfo['backup_interval'] }} {{ $backupInfo['backup_period'] }}
                    </p>
                @endif
            </div>
            <div>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Last Backup</p>
                <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $backupInfo['last_backup_human'] ?? 'Never' }}
                </p>
                @if($backupInfo['last_backup'])
                    <p class="text-xs text-zinc-500 dark:text-zinc-500">
                        {{ $backupInfo['last_backup'] }}
                    </p>
                @endif
            </div>
            <div>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Next Scheduled</p>
                <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $backupInfo['next_backup_human'] ?? 'Not scheduled' }}
                </p>
                @if($backupInfo['is_backup_due'])
                    <span class="inline-block px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-200 dark:bg-yellow-700 dark:text-yellow-100 rounded">
                        Backup Due Now
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="mb-6 flex flex-wrap gap-3">
        <button wire:click="createBackup"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200 flex items-center gap-2"
                {{ $isBackupRunning ? 'disabled' : '' }}>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
            </svg>
            {{ $isBackupRunning ? 'Backing Up...' : 'Create Backup Now' }}
        </button>

        <button wire:click="refreshBackups"
                class="bg-zinc-500 hover:bg-zinc-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
        </button>
    </div>

    {{-- Backup Comparison Section --}}
    @if(count($backups) >= 2)
    <div class="mb-6 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
        <h3 class="text-lg font-semibold mb-3 text-zinc-900 dark:text-zinc-100">Compare Backups</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">First Backup</label>
                <select wire:model="compareBackup1" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                    <option value="">Select backup...</option>
                    @foreach($backups as $backup)
                        <option value="{{ $backup['filename'] }}">{{ $backup['filename'] }} ({{ $backup['age'] }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Second Backup</label>
                <select wire:model="compareBackup2" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                    <option value="">Select backup...</option>
                    @foreach($backups as $backup)
                        <option value="{{ $backup['filename'] }}">{{ $backup['filename'] }} ({{ $backup['age'] }})</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button wire:click="compareBackups"
                        class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                    Compare
                </button>
                @if($comparisonResult)
                    <button wire:click="clearComparison"
                            class="bg-zinc-400 hover:bg-zinc-600 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                        Clear
                    </button>
                @endif
            </div>
        </div>

        {{-- Comparison Results --}}
        @if($comparisonResult)
        <div class="mt-4 p-4 bg-white dark:bg-zinc-700 rounded border border-zinc-200 dark:border-zinc-600">
            <h4 class="font-semibold mb-3 text-zinc-900 dark:text-zinc-100">Comparison Results</h4>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $comparisonResult['backup1']['filename'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $comparisonResult['backup1']['timestamp'] }}</p>
                    <p class="text-sm mt-2">Records: <strong>{{ number_format($comparisonResult['backup1']['total_records']) }}</strong></p>
                    <p class="text-sm">Tables: <strong>{{ $comparisonResult['backup1']['total_tables'] }}</strong></p>
                </div>
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded">
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $comparisonResult['backup2']['filename'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $comparisonResult['backup2']['timestamp'] }}</p>
                    <p class="text-sm mt-2">Records: <strong>{{ number_format($comparisonResult['backup2']['total_records']) }}</strong></p>
                    <p class="text-sm">Tables: <strong>{{ $comparisonResult['backup2']['total_tables'] }}</strong></p>
                </div>
            </div>

            <div class="mb-3">
                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                    Total Record Change:
                    <span class="{{ $comparisonResult['record_change'] > 0 ? 'text-green-600' : ($comparisonResult['record_change'] < 0 ? 'text-red-600' : 'text-zinc-600') }}">
                        {{ $comparisonResult['record_change'] > 0 ? '+' : '' }}{{ number_format($comparisonResult['record_change']) }}
                    </span>
                </p>
            </div>

            @if(count($comparisonResult['differences']) > 0)
            <div>
                <p class="text-sm font-semibold mb-2 text-zinc-700 dark:text-zinc-300">Table Changes:</p>
                <div class="space-y-1 max-h-60 overflow-y-auto">
                    @foreach($comparisonResult['differences'] as $table => $change)
                    <div class="flex justify-between items-center text-sm p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                        <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ $table }}</span>
                        <span>
                            <span class="text-zinc-500 dark:text-zinc-400">{{ number_format($change['before']) }}</span>
                            →
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($change['after']) }}</span>
                            <span class="ml-2 {{ $change['change'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                ({{ $change['change'] > 0 ? '+' : '' }}{{ number_format($change['change']) }})
                            </span>
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <p class="text-sm text-zinc-500 dark:text-zinc-400">No differences found between these backups.</p>
            @endif
        </div>
        @endif
    </div>
    @endif

    {{-- Backup List --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                Backup History ({{ $backupInfo['total_backups'] }} backups)
            </h3>
        </div>

        @if(count($backups) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Backup File
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Date Created
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Size
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Records
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($backups as $index => $backup)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                                </svg>
                                <div>
                                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $backup['filename'] }}
                                    </div>
                                    @if($backup['metadata'])
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $backup['metadata']['total_tables'] ?? 0 }} tables
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-zinc-900 dark:text-zinc-100">{{ $backup['date'] }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $backup['age'] }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                            {{ $backup['size'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                            @if($backup['metadata'])
                                {{ number_format($backup['metadata']['total_records'] ?? 0) }}
                            @else
                                <span class="text-zinc-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <button wire:click="viewBackupDetails({{ $index }})"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                        title="View Details">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <button wire:click="downloadBackup('{{ $backup['filename'] }}')"
                                        class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                        title="Download">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </button>
                                <button wire:click="confirmRestore('{{ $backup['filename'] }}')"
                                        class="text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300"
                                        title="Restore">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>
                                <button wire:click="confirmDelete('{{ $backup['filename'] }}')"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                        title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-6 py-12 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-zinc-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <p class="text-zinc-500 dark:text-zinc-400">No backups found. Create your first backup!</p>
        </div>
        @endif
    </div>

    {{-- Backup Details Modal --}}
    @if($showBackupDetails && $selectedBackup)
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" wire:click="closeBackupDetails">
        <div class="bg-white dark:bg-zinc-800 rounded-lg max-w-2xl w-full max-h-[80vh] overflow-hidden" wire:click.stop>
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Backup Details</h3>
                <button wire:click="closeBackupDetails" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[calc(80vh-80px)]">
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Filename</p>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedBackup['filename'] }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">Created</p>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedBackup['date'] }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-500">{{ $selectedBackup['age'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">Size</p>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedBackup['size'] }}</p>
                        </div>
                    </div>

                    @if($selectedBackup['metadata'])
                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <h4 class="font-semibold mb-3 text-zinc-900 dark:text-zinc-100">Database Statistics</h4>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Tables</p>
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $selectedBackup['metadata']['total_tables'] ?? 0 }}</p>
                            </div>
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded">
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Records</p>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($selectedBackup['metadata']['total_records'] ?? 0) }}</p>
                            </div>
                        </div>

                        <h5 class="font-semibold mb-2 text-zinc-900 dark:text-zinc-100">Records per Table</h5>
                        <div class="space-y-1 max-h-60 overflow-y-auto">
                            @foreach(($selectedBackup['metadata']['tables'] ?? []) as $table => $count)
                            <div class="flex justify-between items-center p-2 bg-zinc-50 dark:bg-zinc-700 rounded text-sm">
                                <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ $table }}</span>
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ is_numeric($count) ? number_format($count) : $count }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
