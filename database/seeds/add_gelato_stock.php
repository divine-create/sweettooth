<?php

use App\Models\Item;
use App\Models\ProductStock;
use App\Models\Branch;

// Script to add stock for all gelato ingredients to ensure they're available for production

// Get the first branch (assuming it's the main branch)
$branch = Branch::first();
if (!$branch) {
    echo "No branch found\n";
    exit(1);
}

echo "Adding stock for gelato ingredients in branch: {$branch->name}\n";

// Get all items that were created as gelato ingredients
$gelatoIngredients = Item::where('name', 'LIKE', '%Gelato%')
    ->orWhere('name', 'LIKE', '%Milk%')
    ->orWhere('name', 'LIKE', '%Cream%')
    ->orWhere('name', 'LIKE', '%Sugar%')
    ->orWhere('name', 'LIKE', '%Egg%')
    ->orWhere('name', 'LIKE', '%Chocolate%')
    ->orWhere('name', 'LIKE', '%Cocoa%')
    ->orWhere('name', 'LIKE', '%Strawberry%')
    ->orWhere('name', 'LIKE', '%Raspberry%')
    ->orWhere('name', 'LIKE', '%Mango%')
    ->orWhere('name', 'LIKE', '%Hazelnut%')
    ->orWhere('name', 'LIKE', '%Pistachio%')
    ->orWhere('name', 'LIKE', '%Coffee%')
    ->orWhere('name', 'LIKE', '%Coconut%')
    ->orWhere('name', 'LIKE', '%Matcha%')
    ->orWhere('name', 'LIKE', '%Vanilla%')
    ->orWhere('name', 'LIKE', '%Butter%')
    ->orWhere('name', 'LIKE', '%Salt%')
    ->orWhere('name', 'LIKE', '%Lemon%')
    ->orWhere('name', 'LIKE', '%Berries%')
    ->orWhere('name', 'LIKE', '%Nuts%')
    ->orWhere('name', 'LIKE', '%Mint%')
    ->orWhere('name', 'LIKE', '%Chocolate Chunks%')
    ->orWhere('name', 'LIKE', '%Corn Syrup%')
    ->orWhere('name', 'LIKE', '%Cornstarch%')
    ->orWhere('name', 'LIKE', '%Water%')
    ->orWhere('name', 'LIKE', '%Vanilla Extract%')
    ->get();

echo "Found " . $gelatoIngredients->count() . " gelato ingredients\n";

foreach ($gelatoIngredients as $item) {
    // Check if stock already exists for this item in this branch
    $existingStock = ProductStock::where('item_id', $item->id)
        ->where('branch_id', $branch->id)
        ->first();
    
    if ($existingStock) {
        // Update existing stock to have sufficient quantity
        $existingStock->update([
            'quantity' => 1500, // Set to 1500 units
            'reserved_quantity' => 0,
            'available_quantity' => 1500,
        ]);
        echo "Updated stock for {$item->name}: 1500 units\n";
    } else {
        // Create new stock record
        ProductStock::create([
            'item_id' => $item->id,
            'branch_id' => $branch->id,
            'quantity' => 1500, // Start with 1500 units
            'reserved_quantity' => 0,
            'available_quantity' => 1500,
            'minimum_stock_level' => 100,
            'maximum_stock_level' => 3000,
        ]);
        echo "Created stock for {$item->name}: 1500 units\n";
    }
}

// Also add stock for any other items that might be used in gelato production
$otherCommonIngredients = [
    'Whole Milk', 'Heavy Cream', 'Sugar', 'Egg Yolks', 'Dark Chocolate', 
    'Cocoa Powder', 'Fresh Strawberries', 'Hazelnut Paste', 'Pistachio Paste',
    'Espresso Coffee', 'Coconut Milk', 'Coconut Cream', 'Matcha Powder',
    'Vanilla Extract', 'Butter', 'Sea Salt', 'Lemon Juice', 'Mixed Berries',
    'Corn Syrup', 'Cornstarch', 'Water', 'Granulated Sugar', 'Fresh Mint Leaves',
    'Dark Chocolate Chunks', 'Mixed Nuts'
];

foreach ($otherCommonIngredients as $ingredientName) {
    $item = Item::where('name', $ingredientName)->first();
    
    if ($item) {
        $existingStock = ProductStock::where('item_id', $item->id)
            ->where('branch_id', $branch->id)
            ->first();
        
        if ($existingStock) {
            $existingStock->update([
                'quantity' => 1500,
                'reserved_quantity' => 0,
                'available_quantity' => 1500,
            ]);
            echo "Updated stock for {$ingredientName}: 1500 units\n";
        } else {
            ProductStock::create([
                'item_id' => $item->id,
                'branch_id' => $branch->id,
                'quantity' => 1500,
                'reserved_quantity' => 0,
                'available_quantity' => 1500,
                'minimum_stock_level' => 100,
                'maximum_stock_level' => 3000,
            ]);
            echo "Created stock for {$ingredientName}: 1500 units\n";
        }
    } else {
        echo "Item not found: {$ingredientName}\n";
    }
}

echo "\nAll gelato ingredient stocks have been updated to 1500 units!\n";
echo "This ensures sufficient inventory for production testing.\n";