<div x-data="{ showDeleteModal: false, loading: false }">
    {{-- TODO:: use a dispatch to close the delet emodel when done --}}



    <style>
        /* Custom scrollbar styles */
        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            @apply bg-zinc-300 dark:bg-zinc-700 rounded-full;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            @apply bg-zinc-400 dark:bg-zinc-600;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>

    <x-breadcrumb title="Department Category Management" :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Department Category Management']]" :compact="false" :with-icons="true" />

    @if (is_super_admin())
        <div class="flex justify-between items-center mt-7">
            <a href="{{ route('branch-dashboard.department.category.create', request()->query()) }}" wire:navigate="true"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add New Category
            </a>
        </div>
    @endif

    <!-- Export Buttons -->
    <div class="flex justify-end items-center space-x-2 mt-7">
        <button wire:click="exportCSV"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export CSV
        </button>
    </div>

    <!-- Bulk Actions Bar -->
    <div x-data="{ selectedIds: @entangle('selectedIds') }" x-show="selectedIds.length > 0" x-cloak
        class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    <span x-text="selectedIds.length"></span> item(s) selected
                </span>
                <button wire:click="toggleBulkMode"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline text-left">
                    Clear Selection
                </button>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <button wire:click="bulkDeleteCategories"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete Selected
                </button>
            </div>
        </div>
    </div>


    <!-- Table -->
    <x-table :$headers :$rows  striped paginate persist :filter="['quantity' => 'quantity', 'search' => 'search']"
        :quantity="[10, 25, 50, 100]">
        @interact('column_description', $row)
            <span class="text-zinc-600 dark:text-zinc-400 text-sm">
                {{ $row->description ? Str::limit($row->description, 50) : 'N/A' }}
            </span>
        @endinteract

        @interact('column_created_at', $row)
            <span class="text-zinc-600 dark:text-zinc-400 text-sm">
                {{ $row->created_at->format('M d, Y') }}
            </span>
        @endinteract

        @interact('column_action', $row)
            <a href="{{ route('branch-dashboard.department.category.edit', ['id' => $row->id]) }}"
                class="ml-2 bg-green-700 text-white rounded-md px-2 py-1">
                Edit
            </a>
            <button
                class="ml-2 bg-red-700 text-white rounded-md px-2 py-1"
                @click="
                showDeleteModal = true;
                loading = true;
                $wire.getSelectedData('{{ $row->id }}')
                    .then(() => { loading = false; })
                    .catch(() => { loading = false; });
            ">
                Delete
            </button>
        @endinteract


    </x-table>

    <div class="fixed inset-0 z-[999999] flex items-center justify-center" x-cloak x-show="showDeleteModal"
        @keydown.escape.window="showDeleteModal = false">
        <div x-init="
            window.addEventListener('close-delete-modal', () => {
                showDeleteModal = false;
                loading = false;
            });
        "></div>
        <!-- Dark backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300"
            @click="showDeleteModal = false"></div>

        <!-- Modal box -->
        <div
            class="relative bg-white dark:bg-gray-900 rounded-xl shadow-2xl p-8 w-full max-w-md mx-4
                transform transition-all duration-300">

            <!-- Loading spinner -->
            <template x-if="loading">
                <div class="flex flex-col items-center justify-center py-8">
                    <div
                        class="animate-spin w-10 h-10 border-4 border-gray-300 border-t-transparent rounded-full mb-4">
                    </div>
                    <p class="text-lg font-medium text-gray-700 dark:text-gray-200">Loading...</p>
                </div>
            </template>

            <!-- Content -->
            <div x-show="!loading">
                <p class="text-xl font-semibold text-gray-800 dark:text-gray-100">Are you sure?</p>
                <p class="text-gray-600 dark:text-gray-300">
                    This action <span class="font-semibold text-red-600">cannot be undone</span>.
                </p>

                @if ($selectedCategoryDeprtament && $selectedCategoryDeprtament->count())
                    <p>The following departments will be deleted:</p>
                    <ul class="space-y-2">
                        @foreach ($selectedCategoryDeprtament as $item)
                            <li
                                class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 transition duration-200">
                                {{ $item->name }}
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="flex items-center justify-center space-x-4 mt-6">
                    <button
                        class="px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                        @click="showDeleteModal = false">Cancel</button>
                    <button class="px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-700 transition shadow-sm"
                       wire:click="initiateDelete('{{ $selectedCategoryId }}')">
                        <span wire:loading.remove wire:target="initiateDelete">Confirm Delete</span>
                        <span wire:loading wire:target="initiateDelete">Deleting...</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

</div>
