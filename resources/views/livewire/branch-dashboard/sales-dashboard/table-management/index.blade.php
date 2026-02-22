<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Table Management</h1>
            <p class="mt-1 text-sm text-gray-600">Manage tables for {{ $departmentName }}</p>
        </div>
        <div class="flex gap-3">
            <button wire:click="createBulkTables"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="mr-2 inline-block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create 8 Tables
            </button>
            <button wire:click="openCreateModal"
                    class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <svg class="mr-2 inline-block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Table
            </button>
        </div>
    </div>

    @if($this->tables->isEmpty())
        <div class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No tables</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new table or bulk creating 8 tables.</p>
            <div class="mt-6 flex justify-center gap-3">
                <button wire:click="createBulkTables" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Create 8 Tables
                </button>
                <button wire:click="openCreateModal" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Add Single Table
                </button>
            </div>
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            @foreach($this->tables as $table)
                <livewire:branch-dashboard.sales-dashboard.table-management.table-view
                    :table="$table"
                    :key="'table-'.$table->id"
                    wire:key="table-{{ $table->id }}" />
            @endforeach
        </div>

        <div class="mt-6">
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="text-sm font-medium text-gray-900">Table Statistics</h3>
                <dl class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="overflow-hidden rounded-lg bg-gray-50 px-4 py-3">
                        <dt class="truncate text-sm font-medium text-gray-500">Total Tables</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $this->tables->count() }}</dd>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-green-50 px-4 py-3">
                        <dt class="truncate text-sm font-medium text-green-600">Available</dt>
                        <dd class="mt-1 text-2xl font-semibold text-green-900">{{ $this->tables->where('status', 'available')->count() }}</dd>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-red-50 px-4 py-3">
                        <dt class="truncate text-sm font-medium text-red-600">Occupied</dt>
                        <dd class="mt-1 text-2xl font-semibold text-red-900">{{ $this->tables->where('status', 'occupied')->count() }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="mt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-3">All Tables</h3>
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">Table #</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Name</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Capacity</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Active</th>
                            <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($this->tables as $table)
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900">
                                    {{ $table->table_number }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $table->table_name }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $table->capacity }} seats
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5
                                        @if($table->status === 'available') bg-green-100 text-green-800
                                        @elseif($table->status === 'occupied') bg-red-100 text-red-800
                                        @else bg-yellow-100 text-yellow-800
                                        @endif">
                                        {{ ucfirst($table->status) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    @if($table->is_active)
                                        <span class="text-green-600">Active</span>
                                    @else
                                        <span class="text-gray-400">Inactive</span>
                                    @endif
                                </td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <button wire:click="openEditModal({{ $table->id }})"
                                            class="text-blue-600 hover:text-blue-900 mr-3">
                                        Edit
                                    </button>
                                    <button wire:click="toggleTableStatus({{ $table->id }})"
                                            class="text-yellow-600 hover:text-yellow-900 mr-3">
                                        {{ $table->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    <button wire:click="deleteTable({{ $table->id }})"
                                            wire:confirm="Are you sure you want to delete this table?"
                                            class="text-red-600 hover:text-red-900">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Table Modal -->
    @if($showTableModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showTableModal', false)"></div>

                <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <form wire:submit.prevent="saveTable">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="mb-4">
                                <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                    {{ $editingTableId ? 'Edit Table' : 'Create Table' }}
                                </h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label for="tableNumber" class="block text-sm font-medium text-gray-700">Table Number *</label>
                                    <input type="text" wire:model="tableNumber" id="tableNumber"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @error('tableNumber') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="tableName" class="block text-sm font-medium text-gray-700">Table Name</label>
                                    <input type="text" wire:model="tableName" id="tableName"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @error('tableName') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="tableCapacity" class="block text-sm font-medium text-gray-700">Capacity *</label>
                                    <input type="number" wire:model="tableCapacity" id="tableCapacity" min="1" max="20"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @error('tableCapacity') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="tableStatus" class="block text-sm font-medium text-gray-700">Status *</label>
                                    <select wire:model="tableStatus" id="tableStatus"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="available">Available</option>
                                        <option value="occupied">Occupied</option>
                                        <option value="reserved">Reserved</option>
                                    </select>
                                    @error('tableStatus') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                    <textarea wire:model="notes" id="notes" rows="3"
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                                    @error('notes') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="isActive" id="isActive"
                                           class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <label for="isActive" class="ml-2 block text-sm text-gray-900">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit"
                                    class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                                {{ $editingTableId ? 'Update' : 'Create' }}
                            </button>
                            <button type="button" wire:click="$set('showTableModal', false)"
                                    class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
