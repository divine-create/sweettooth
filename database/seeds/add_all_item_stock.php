<?php

use App\Models\Item;
use App\Models\Stock;
use App\Models\Branch;

// Script to add stock for ALL items to ensure they're available for production

// Get the first branch (assuming it's the main branch)
$branch = Branch::first();
if (!$branch) {
    echo "No branch found\n";
    exit(1);
}

echo "Adding stock for ALL items in branch: {$branch->name}\n";

// Get all items in the system
$allItems = Item::all();

echo "Found " . $allItems->count() . " total items\n";

$processed = 0;
$updated = 0;
$created = 0;

foreach ($allItems as $item) {
    // Check if stock already exists for this item in this branch
    $existingStock = Stock::where('item_id', $item->id)
        ->where('branch_id', $branch->id)
        ->first();

    if ($existingStock) {
        // Update existing stock to have sufficient quantity
        $existingStock->update([
            'quantity_available' => 1500, // Set to 1500 units
            'quantity_reserved' => 0,
            'quantity_damaged' => 0,
        ]);
        $updated++;
        echo "Updated stock for {$item->name}: 1500 units\n";
    } else {
        // Create new stock record
        Stock::create([
            'item_id' => $item->id,
            'branch_id' => $branch->id,
            'quantity_available' => 1500, // Start with 1500 units
            'quantity_reserved' => 0,
            'quantity_damaged' => 0,
            'average_cost' => 1.00, // Default cost
            'health_status' => 'good', // Default health status
        ]);
        $created++;
        echo "Created stock for {$item->name}: 1500 units\n";
    }

    $processed++;

    // Show progress every 50 items
    if ($processed % 50 == 0) {
        echo "Processed {$processed}/{$allItems->count()} items...\n";
    }
}

echo "\nSummary:\n";
echo "- Total items processed: {$processed}\n";
echo "- Stock records updated: {$updated}\n";
echo "- Stock records created: {$created}\n";
echo "\nAll item stocks have been updated to 1500 units!\n";
echo "This ensures sufficient inventory for production testing.\n";