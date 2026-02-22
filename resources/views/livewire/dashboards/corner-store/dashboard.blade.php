<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Corner Store Dashboard</h1>
            <p class="mt-2 text-gray-600">Track your sales and inventory in real-time</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Today's Sales -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Today's Sales</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($todaySales ?? 0) }}
            </dd>
            <p class="mt-2 text-sm text-gray-600">
                {{ $todayTransactionCount }} {{ \Illuminate\Support\Str::plural('transaction', $todayTransactionCount) }}
            </p>
        </div>

        <!-- Average Transaction -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Average Transaction</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($averageTransactionValue ?? 0) }}
            </dd>
            <p class="mt-2 text-sm text-gray-600">Per transaction</p>
        </div>

        <!-- Period Sales -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Period Sales</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($totalSales ?? 0) }}
            </dd>
            <p class="mt-2 text-sm text-gray-600">
                {{ $transactionCount }} total
            </p>
        </div>

        <!-- Items Sold -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Items Sold</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
                {{ collect($topSellingItems)->sum('total_qty') ?? 0 }}
            </dd>
            <p class="mt-2 text-sm text-gray-600">Today</p>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Top Selling Items -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Top Selling Items</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Product
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Qty
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Sales
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($topSellingItems as $item)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ $item->name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ (int)$item->total_qty }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($item->total_sales ?? 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No sales data yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Recent Transactions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Time
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Amount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Method
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($recentTransactions as $transaction)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($transaction->created_at)->format('H:i') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($transaction->total_amount ?? 0) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ $transaction->payment_method ?? 'Cash' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No transactions yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
