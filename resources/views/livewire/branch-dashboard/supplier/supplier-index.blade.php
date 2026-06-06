<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Supplier Management</h1>
            <p class="text-zinc-600 dark:text-zinc-400 mt-1">Manage suppliers and track their performance</p>
        </div>
        <button
            wire:click="openCreateForm"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
            <span class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Supplier
            </span>
        </button>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Search</label>
                <input
                    type="text"
                    wire:model.live="searchTerm"
                    placeholder="Code, name, or email..."
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Status</label>
                <select
                    wire:model.live="statusFilter"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="all">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                    <option value="pending">Pending</option>
                </select>
            </div>

            <!-- Sort -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Sort By</label>
                <select
                    wire:model.live="sortBy"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="created_at">Recently Added</option>
                    <option value="name">Name (A-Z)</option>
                    <option value="status">Status</option>
                    <option value="quality_rating">Quality Rating</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Suppliers Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <button
                                wire:click="sortBy('code')"
                                class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white"
                            >
                                Code
                                @if($sortBy === 'code')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left">
                            <button
                                wire:click="sortBy('name')"
                                class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white"
                            >
                                Name
                                @if($sortBy === 'name')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300">Contact</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300">Quality</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $supplier->code }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-zinc-900 dark:text-white">{{ $supplier->name }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    <div>{{ $supplier->email }}</div>
                                    <div class="text-xs">{{ $supplier->phone }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full {{ $this->getStatusBadgeClass($supplier->status) }}">
                                    {{ $this->getStatusLabel($supplier->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    @if($supplier->quality_rating)
                                        <span class="text-yellow-600 dark:text-yellow-400 font-semibold">
                                            {{ number_format($supplier->quality_rating, 1) }}/5
                                        </span>
                                    @else
                                        <span class="text-zinc-500 dark:text-zinc-400">N/A</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <button
                                        wire:click="viewDetails({{ $supplier->id }})"
                                        class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                                    >
                                        View Details
                                    </button>
                                    <button
                                        wire:click="deleteSupplier({{ $supplier->id }})"
                                        wire:confirm="Delete this supplier? Suppliers with purchase history will be archived; those without any purchases will be permanently removed."
                                        class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center">
                                <p class="text-zinc-500 dark:text-zinc-400">No suppliers found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 px-6 py-4">
            {{ $suppliers->links() }}
        </div>
    </div>

    <!-- Create Supplier Modal -->
    @if($showCreateForm)
        <div class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-lg max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
                <livewire:branch-dashboard.supplier.create-supplier @close-form="closeCreateForm" />
            </div>
        </div>
    @endif

    <!-- Supplier Details Modal -->
    @if($showDetails && $selectedSupplier)
        <div class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-lg max-w-4xl w-full mx-4 max-h-screen overflow-y-auto">
                <div class="sticky top-0 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 px-6 py-4 flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $selectedSupplier->name }}</h2>
                    <div class="flex items-center gap-3">
                        <button
                            wire:click="deleteSupplier({{ $selectedSupplier->id }})"
                            wire:confirm="Delete this supplier? Suppliers with purchase history will be archived; those without any purchases will be permanently removed."
                            class="text-sm font-semibold text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300"
                        >
                            Delete
                        </button>
                        <button
                            wire:click="closeDetails"
                            class="text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <livewire:branch-dashboard.supplier.supplier-details :supplier="$selectedSupplier" />
                </div>
            </div>
        </div>
    @endif
</div>
