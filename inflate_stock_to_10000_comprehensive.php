<?php

// Script to inflate all items and products stock to 10,000 for testing across all branches

require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Item;
use App\Models\Stock;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesShift;
use App\Models\Branch;
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

// Update any products that might not have stock records yet
// This will look for products that don't have any ProductStock records associated with any SalesShift
echo "Looking for products without any existing ProductStock records...\n";

// Get all products that don't have any ProductStock records
$allProducts = Product::all();
$productsWithStock = DB::table('product_stocks')
    ->join('sales_shifts', 'product_stocks.sales_shift_id', '=', 'sales_shifts.id')
    ->select('product_stocks.product_id')
    ->distinct()
    ->pluck('product_id');

$productsWithoutAnyStock = $allProducts->filter(function ($product) use ($productsWithStock) {
    return !$productsWithStock->contains($product->id);
});

echo "Found " . $productsWithoutAnyStock->count() . " products without any existing ProductStock records.\n";

// Create ProductStock records for these products
$productsAddedStock = 0;

if ($productsWithoutAnyStock->count() > 0) {
    // Get or create a default sales shift for today for each branch
    $branches = Branch::all();
    
    foreach ($branches as $branch) {
        // Get or create a Till department
        $tillDepartment = \App\Models\Department::where('name', 'Till')->first();
        if (!$tillDepartment) {
            $tillDepartment = \App\Models\Department::create([
                'name' => 'Till',
                'code' => 'TILL',
                'description' => 'Point of sale department',
                'category_id' => 1, // Assuming a default category
                'is_active' => true,
                'branch_id' => $branch->id,
            ]);
        }

        $user = \App\Models\User::first();
        if (!$user) {
            echo "No users found! Please seed users first.\n";
            continue;
        }

        // Create a sales shift for today if it doesn't exist
        $salesShift = SalesShift::firstOrCreate([
            'shift_date' => Carbon::today(),
            'shift_type' => 'morning',
            'branch_id' => $branch->id,
            'department_id' => $tillDepartment->id,
        ], [
            'shift_number' => 'SHFT-' . Carbon::today()->format('Ymd') . '-001',
            'status' => 'active',
            'employee_id' => $user->id,
        ]);

        // Create ProductStock records for products without any stock
        foreach ($productsWithoutAnyStock as $product) {
            // Only create stock for products in the same branch as the sales shift
            if ($product->branch_id === $branch->id) {
                ProductStock::create([
                    'product_id' => $product->id,
                    'sales_shift_id' => $salesShift->id,
                    'stock_date' => Carbon::today(),
                    'shift_type' => 'morning',
                    'opening_quantity' => 10000,
                    'addition_quantity' => 0,
                    'production_date' => Carbon::today(),
                    'expiry_date' => Carbon::today()->addDays($product->shelf_life_days ?? 7),
                    'callback_quantity' => 0,
                    'redress_quantity' => 0,
                    'total_available' => 10000,
                    'transfer_quantity' => 0,
                    'glovo_quantity' => 0,
                    'quantity_sold' => 0,
                    'closing_quantity' => 10000,
                    'amount' => 0,
                    'notes' => 'Initial stock inflated to 10000 on ' . Carbon::now()
                ]);
                $productsAddedStock++;
            }
        }
    }
}

echo "Added stock records for $productsAddedStock products without existing stock.\n";

echo "\nStock inflation completed!\n";
echo "Summary:\n";
echo "- $itemStocksUpdated item stocks updated\n";
echo "- $productStocksUpdated product stocks updated\n";
echo "- $itemsAddedStock new item stock records created\n";
echo "- $productsAddedStock new product stock records created\n";