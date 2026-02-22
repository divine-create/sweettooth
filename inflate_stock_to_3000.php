<?php

// Script to inflate all items stock to above 3000 for specific branch

require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Item;
use App\Models\Stock;
use App\Models\Branch;
use Carbon\Carbon;

// Initialize Laravel application
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Define the target branch ID
$targetBranchId = '019c380f-c765-720c-9bae-159d1218f512';

// Check if branch exists
$branch = Branch::find($targetBranchId);
if (!$branch) {
    echo "Branch with ID $targetBranchId not found!\n";

    // Let's check what branches exist
    echo "Available branches:\n";
    $allBranches = Branch::all();
    foreach ($allBranches as $b) {
        echo "- {$b->id}: {$b->name}\n";
    }
    exit(1);
}

echo "Inflating stock to above 3000 for branch: {$branch->name} ({$branch->id})\n";

// Update Item Stocks for the specific branch
echo "Updating Item Stocks for branch {$branch->id}...\n";
$itemStocksUpdated = 0;
$newStockRecordsCreated = 0;

// Update existing stock records for this branch
$stocks = Stock::where('branch_id', $targetBranchId)->get();
foreach ($stocks as $stock) {
    $stock->update([
        'quantity_available' => 3500, // Setting to 3500 to ensure it's well above 3000
        'quantity_reserved' => 0,
        'quantity_damaged' => 0,
        'average_cost' => $stock->average_cost ?: 1.00, // Keep existing cost or set default
        'last_stock_take_date' => Carbon::today(),
        'health_status' => 'good',
        'notes' => $stock->notes ? $stock->notes . ' | Stock inflated to 3500 on ' . Carbon::now() : 'Stock inflated to 3500 on ' . Carbon::now()
    ]);
    $itemStocksUpdated++;
}

echo "Updated $itemStocksUpdated existing item stocks.\n";

// Create stock records for items in this branch that don't have stock records yet
echo "Creating stock records for items without existing stock in branch {$branch->id}...\n";
$itemsWithoutStock = Item::where('branch_id', $targetBranchId)->doesntHave('stocks')->get();

foreach ($itemsWithoutStock as $item) {
    Stock::create([
        'branch_id' => $targetBranchId,
        'item_id' => $item->id,
        'quantity_available' => 3500, // Setting to 3500 to ensure it's well above 3000
        'quantity_reserved' => 0,
        'quantity_damaged' => 0,
        'average_cost' => 1.00, // Default cost
        'last_stock_take_date' => Carbon::today(),
        'health_status' => 'good',
        'notes' => 'Initial stock inflated to 3500 on ' . Carbon::now()
    ]);
    $newStockRecordsCreated++;
}

echo "Created $newStockRecordsCreated new stock records for items without existing stock.\n";

// Also update any items that belong to this branch but might be in a different format
echo "Checking for any other items in branch that need stock updates...\n";
$branchItems = Item::where('branch_id', $targetBranchId)->get();
$additionalUpdates = 0;

foreach ($branchItems as $item) {
    $stock = Stock::firstOrCreate(
        [
            'branch_id' => $targetBranchId,
            'item_id' => $item->id,
        ],
        [
            'quantity_available' => 3500,
            'quantity_reserved' => 0,
            'quantity_damaged' => 0,
            'average_cost' => 1.00,
            'last_stock_take_date' => Carbon::today(),
            'health_status' => 'good',
            'notes' => 'Stock inflated to 3500 on ' . Carbon::now()
        ]
    );
    
    if (!$stock->wasRecentlyCreated) {
        // If the record existed, update it to ensure it has the correct quantity
        $stock->update(['quantity_available' => 3500]);
        $additionalUpdates++;
    }
}

echo "Ensured all items have stock records with at least 3500 quantity.\n";

echo "\nStock inflation completed for branch {$branch->id}!\n";
echo "Summary:\n";
echo "- $itemStocksUpdated existing item stocks updated\n";
echo "- $newStockRecordsCreated new item stock records created\n";
echo "- $additionalUpdates additional items checked/updated\n";
echo "- All items in branch {$branch->name} now have stock above 3000 (set to 3500)\n";