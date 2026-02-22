<div>
    @if($this->isSuperAdmin && $this->branches->count() > 0)
    <div class="branch-selector-card sticky top-0 z-40 flex items-center gap-3 px-4 py-3 border-b-2 shadow-sm mb-10">
        <div class="flex items-center gap-2 flex-shrink-0">
            <div class="p-2 bg-purple-600 dark:bg-purple-500 rounded-lg shadow-md">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div>
            <span class="text-xs font-semibold uppercase tracking-wide">Super Admin View</span>
            <p class="text-xs">Multi-Branch Access</p>
            </div>
        </div>

        <div class="flex-1 max-w-md">
            <div class="flex items-end gap-2">
                <div class="relative flex-1">
                    <label class="block text-xs font-medium text-zinc-700 mb-1">
                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                        </svg>
                        Select Branch
                    </label>
                    <select wire:model="selectedBranch"
                            class="branch-selector-select w-full rounded-lg border-2 bg-white dark:bg-zinc-900 text-sm font-medium text-zinc-900 dark:text-zinc-100 shadow-sm transition-all duration-200"
                            style="border-color: var(--color-accent);">
                        <option value="">-- Select a Branch --</option>
                        @foreach($this->branches as $branch)
                            <option value="{{ $branch->id }}">
                                {{ $branch->name }} @if($branch->code)({{ $branch->code }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="button"
                        wire:click="changeBranch"
                        wire:loading.attr="disabled" wire:target="changeBranch"
                        class="branch-selector-button px-4 py-2 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        style="background-color: var(--color-primary); border: 1px solid var(--color-accent);">
                    <span wire:loading.remove wire:target="changeBranch">Switch</span>
                    <span wire:loading wire:target="changeBranch" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Updating...
                    </span>
                </button>
            </div>
        </div>

            <div class="flex items-center gap-2 px-4 py-2 rounded-lg border-2 shadow-sm branch-selector-status">
            <div class="flex flex-col">
                <span class="text-[10px] font-medium text-purple-600 dark:text-purple-400 uppercase tracking-wider">Acting as</span>
                <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $this->currentBranchName }}</span>
            </div>
            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse shadow-lg shadow-green-500/50"></div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <div class="text-xs text-purple-700 dark:text-purple-300">
                <span class="font-medium">{{ $this->branches->count() }}</span> branches available
            </div>
        </div>
    </div>
    @endif
    <style>
        .branch-selector-card {
            background: var(--color-primary-muted);
            border-color: color-mix(in srgb, var(--color-primary) 50%, #ffffff);
            color: var(--color-primary-foreground);
        }

        .branch-selector-card svg {
            color: var(--color-primary-foreground);
        }

        .branch-selector-select:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 70%, #ffffff);
        }

        .branch-selector-button {
            color: var(--color-primary-foreground);
        }

        .branch-selector-button:hover:not(:disabled),
        .branch-selector-button:focus-visible {
            background-color: var(--color-accent);
            border-color: var(--color-accent);
            color: var(--color-accent-foreground);
        }

        .branch-selector-status {
            background: color-mix(in srgb, var(--color-primary) 55%, #ffffff);
            border-color: color-mix(in srgb, var(--color-accent) 70%, transparent);
            color: var(--color-primary-foreground);
        }
    </style>
</div>
