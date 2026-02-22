<?php

// Script to inflate all items and products stock to 10,000 for testing

require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Item;
use App\Models\Stock;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesShift;
use Carbon\Carbon;

// Initialize Laravel application
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting stock inflation to 10,000 for all items and products...\n";

// Update Item Stocks
echo "Updating Item Stocks...\n";
$itemStocksUpdated = 0;

$stocks = Stock::all();
foreach ($stocks as $stock) {
    $stock->update([
        'quantity_available' => 10000,
        'total_available' => 10000, // In case there's a total_available field
        'closing_quantity' => 10000, // In case there's a closing_quantity field
        'notes' => $stock->notes . ' | Stock inflated to 10000 on ' . Carbon::now()
    ]);
    $itemStocksUpdated++;
}

echo "Updated $itemStocksUpdated item stocks.\n";

// Update Product Stocks
echo "Updating Product Stocks...\n";
$productStocksUpdated = 0;

$productStocks = ProductStock::all();
foreach ($productStocks as $productStock) {
    $productStock->update([
        'opening_quantity' => 10000,
        'addition_quantity' => 0,
        'total_available' => 10000,
        'transfer_quantity' => 0,
        'glovo_quantity' => 0,
        'quantity_sold' => 0,
        'closing_quantity' => 10000,
        'notes' => $productStock->notes . ' | Stock inflated to 10000 on ' . Carbon::now()
    ]);
    $productStocksUpdated++;
}

echo "Updated $productStocksUpdated product stocks.\n";

// Also update any items that might not have stock records yet
echo "Creating stock records for items without existing stock...\n";
$itemsWithoutStock = Item::doesntHave('stocks')->get();
$itemsAddedStock = 0;

foreach ($itemsWithoutStock as $item) {
    // Get the branch_id from the item
    $branchId = $item->branch_id;

    Stock::create([
        'branch_id' => $branchId,
        'item_id' => $item->id,
        'quantity_available' => 10000,
        'quantity_reserved' => 0,
        'quantity_damaged' => 0,
        'average_cost' => $item->cost ?? 1.00, // Use item's cost if available, otherwise default to 1.00
        'health_status' => 'good',
        'notes' => 'Initial stock inflated to 10000 on ' . Carbon::now()
    ]);
    $itemsAddedStock++;
}

echo "Added stock records for $itemsAddedStock items without existing stock.\n";

// Note: ProductStock records are associated with SalesShift, not directly with Product
// So we won't attempt to create them here as it requires a sales shift which may not exist
echo "Skipping ProductStock creation as there are no existing ProductStock records and no direct relationship defined in Product model.\n";
$productsAddedStock = 0;

echo "\nStock inflation completed!\n";
echo "Summary:\n";
echo "- $itemStocksUpdated item stocks updated\n";
echo "- $productStocksUpdated product stocks updated\n";
echo "- $itemsAddedStock new item stock records created\n";
echo "- $productsAddedStock new product stock records created\n";