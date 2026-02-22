<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Products for {{ $departmentName }}</h1>
            <p class="mt-1 text-sm text-gray-600">Manage products available in this department</p>
        </div>
        <button wire:click="openAddProductsModal"
                class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
            <svg class="mr-2 inline-block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Products
        </button>
    </div>

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search products..."
               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
    </div>

    @if($this->products->isEmpty())
        <div class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No products</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by adding products to this department.</p>
            <div class="mt-6">
                <button wire:click="openAddProductsModal" class="inline-flex items-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    Add Products
                </button>
            </div>
        </div>
    @else
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">SKU</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Product Name</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Base Price</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Dept. Price</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Sort Order</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Available</th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach($this->products as $product)
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900">
                                {{ $product->sku }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                <div class="font-medium">{{ $product->name }}</div>
                                @if($product->description)
                                    <div class="text-gray-500">{{ Str::limit($product->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ $this->formatPrice($product->price) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                @if($product->pivot->department_price)
                                    <span class="text-green-600 font-medium">{{ $this->formatPrice($product->pivot->department_price) }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ $product->pivot->sort_order }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                @if($product->pivot->is_available)
                                    <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                                        Available
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">
                                        Unavailable
                                    </span>
                                @endif
                            </td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                <button wire:click="openEditModal('{{ $product->id }}')"
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                    Edit
                                </button>
                                <button wire:click="toggleAvailability('{{ $product->id }}')"
                                        class="text-yellow-600 hover:text-yellow-900 mr-3">
                                    {{ $product->pivot->is_available ? 'Disable' : 'Enable' }}
                                </button>
                                <button wire:click="removeProduct('{{ $product->id }}')"
                                        wire:confirm="Are you sure you want to remove this product from {{ $departmentName }}?"
                                        class="text-red-600 hover:text-red-900">
                                    Remove
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->products->links() }}
        </div>
    @endif

    <!-- Add Products Modal -->
    @if($showAddProductsModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showAddProductsModal', false)"></div>

                <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                Add Products to {{ $departmentName }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">Select products to add to this department</p>
                        </div>

                        @if(empty($availableProducts))
                            <div class="rounded-md bg-yellow-50 p-4">
                                <p class="text-sm text-yellow-700">No available products to add. All products are already in this department.</p>
                            </div>
                        @else
                            <div class="max-h-96 overflow-y-auto">
                                <div class="space-y-2">
                                    @foreach($availableProducts as $product)
                                        <label class="flex items-center rounded-lg border p-3 hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox" wire:model="selectedProducts" value="{{ $product['id'] }}"
                                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <div class="ml-3 flex-1">
                                                <div class="font-medium text-gray-900">{{ $product['name'] }}</div>
                                                <div class="text-sm text-gray-500">
                                                    SKU: {{ $product['sku'] }} | Price: {{ $this->formatPrice($product['price']) }}
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-4 rounded-md bg-blue-50 p-3">
                                <p class="text-sm text-blue-700">
                                    Selected: <span class="font-medium">{{ count($selectedProducts) }}</span> product(s)
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" wire:click="addProducts"
                                @if(empty($availableProducts)) disabled @endif
                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto sm:text-sm">
                            Add Selected
                        </button>
                        <button type="button" wire:click="$set('showAddProductsModal', false)"
                                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Product Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showEditModal', false)"></div>

                <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <form wire:submit.prevent="updateProduct">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="mb-4">
                                <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                    Edit Product Settings
                                </h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label for="departmentPrice" class="block text-sm font-medium text-gray-700">
                                        Department Price Override
                                        <span class="text-gray-500 font-normal">(Leave empty to use base price)</span>
                                    </label>
                                    <input type="number" step="0.01" wire:model="departmentPrice" id="departmentPrice"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>

                                <div>
                                    <label for="sortOrder" class="block text-sm font-medium text-gray-700">Sort Order</label>
                                    <input type="number" wire:model="sortOrder" id="sortOrder"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <p class="mt-1 text-xs text-gray-500">Lower numbers appear first</p>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="isAvailable" id="isAvailable"
                                           class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <label for="isAvailable" class="ml-2 block text-sm text-gray-900">
                                        Available for sale
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit"
                                    class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                                Update
                            </button>
                            <button type="button" wire:click="$set('showEditModal', false)"
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
