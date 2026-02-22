<div class="space-y-4 p-3">
    <x-breadcrumb
        title="UOM Units"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Organization'],
            ['label' => 'UOM Management', 'url' => branch_route('branch-dashboard.organization.uom.index')],
            ['label' => 'Units']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-300 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Name, code, symbol, description"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-300 mb-1">Category</label>
                <select wire:model.live="category"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100">
                    <option value="all">All Categories</option>
                    @foreach($categories as $name)
                        <option value="{{ $name }}">{{ ucfirst($name) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-300 mb-1">Status</label>
                <select wire:model.live="status"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-300 mb-1">Rows</label>
                <select wire:model.live="perPage"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100">
                    <option value="15">15</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="md:text-right">
                <a href="{{ branch_route('branch-dashboard.organization.uom.conversions') }}" wire:navigate
                    class="inline-flex items-center px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors">
                    View Conversions
                </a>
                <a href="{{ branch_route('branch-dashboard.organization.uom.manage') }}" wire:navigate
                    class="inline-flex items-center px-3 py-2 rounded-lg bg-zinc-700 hover:bg-zinc-800 text-white text-sm font-medium transition-colors ml-2">
                    Manage Rules
                </a>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Code</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Symbol</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Category</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Dispatch Mapping</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Order</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($units as $unit)
                    <tr>
                        <td class="px-4 py-2 text-sm font-mono text-zinc-800 dark:text-zinc-100">{{ $unit->code }}</td>
                        <td class="px-4 py-2 text-sm text-zinc-800 dark:text-zinc-100">{{ $unit->name }}</td>
                        <td class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300">{{ $unit->symbol }}</td>
                        <td class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300">{{ ucfirst($unit->category) }}</td>
                        <td class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                            {{ $unit->legacy_dispatch_uom ?: 'Not mapped' }}
                        </td>
                        <td class="px-4 py-2 text-sm">
                            @if($unit->is_active)
                                <span class="inline-flex px-2 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Active</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300">{{ $unit->sort_order }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            No units matched your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $units->links() }}
    </div>
</div>
