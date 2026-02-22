<?php

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Product;
use App\Models\Item;
use App\Models\UnitOfMeasure;
use App\Models\Department;

// Add more gelato base recipes for testing

// Get the current branch ID (assuming we're using the first active branch)
$branch = \App\Models\Branch::first();
if (!$branch) {
    echo "No branch found\n";
    exit(1);
}

// Get the gelato department
$gelatoDepartment = Department::where('name', 'Gelato Production')->first();
if (!$gelatoDepartment) {
    echo "Gelato Production department not found\n";
    exit(1);
}

// Get the unit of measure for grams
$gramUom = UnitOfMeasure::where('symbol', 'g')->first();
if (!$gramUom) {
    echo "Gram unit of measure not found\n";
    exit(1);
}

// Define additional gelato base recipes
$additionalGelatoBases = [
    [
        'product_name' => 'Strawberry Gelato Base',
        'sku' => 'GB-STR-002',
        'description' => 'Premium strawberry gelato base mixture',
        'allergens' => ['dairy'],
        'tags' => ['gelato', 'base', 'fruit'],
        'price' => 0.00, // Base products typically have 0 price
        'cost' => 1.90,
        'ingredients' => [
            ['name' => 'Fresh Strawberries', 'quantity' => 300, 'uom' => 'g', 'cost_per_unit' => 0.015],
            ['name' => 'Whole Milk', 'quantity' => 400, 'uom' => 'ml', 'cost_per_unit' => 0.003],
            ['name' => 'Heavy Cream', 'quantity' => 200, 'uom' => 'ml', 'cost_per_unit' => 0.008],
            ['name' => 'Sugar', 'quantity' => 120, 'uom' => 'g', 'cost_per_unit' => 0.001],
            ['name' => 'Egg Yolks', 'quantity' => 4, 'uom' => 'pcs', 'cost_per_unit' => 0.15],
        ],
        'instructions' => [
            "Blend fresh strawberries to puree",
            "Heat milk, cream, and sugar to 85°C",
            "Mix with egg yolks",
            "Cool completely",
            "Add strawberry puree",
            "Process in gelato machine for 25 minutes",
            "Store at -18°C"
        ],
        'yield_quantity' => 50,
        'preparation_time' => 60
    ],
    [
        'product_name' => 'Raspberry Gelato Base',
        'sku' => 'GB-RAS-003',
        'description' => 'Tangy raspberry gelato base mixture',
        'allergens' => ['dairy'],
        'tags' => ['gelato', 'base', 'fruit'],
        'price' => 0.00,
        'cost' => 2.10,
        'ingredients' => [
            ['name' => 'Fresh Raspberries', 'quantity' => 250, 'uom' => 'g', 'cost_per_unit' => 0.02],
            ['name' => 'Whole Milk', 'quantity' => 420, 'uom' => 'ml', 'cost_per_unit' => 0.003],
            ['name' => 'Heavy Cream', 'quantity' => 180, 'uom' => 'ml', 'cost_per_unit' => 0.008],
            ['name' => 'Sugar', 'quantity' => 110, 'uom' => 'g', 'cost_per_unit' => 0.001],
            ['name' => 'Egg Yolks', 'quantity' => 4, 'uom' => 'pcs', 'cost_per_unit' => 0.15],
            ['name' => 'Lemon Juice', 'quantity' => 15, 'uom' => 'ml', 'cost_per_unit' => 0.01],
        ],
        'instructions' => [
            "Blend fresh raspberries to puree, strain to remove seeds",
            "Heat milk, cream, and sugar to 85°C",
            "Mix with egg yolks",
            "Cool completely",
            "Add raspberry puree and lemon juice",
            "Process in gelato machine for 25 minutes",
            "Store at -18°C"
        ],
        'yield_quantity' => 50,
        'preparation_time' => 65
    ],
    [
        'product_name' => 'Salted Caramel Gelato Base',
        'sku' => 'GB-CAR-004',
        'description' => 'Rich salted caramel gelato base mixture',
        'allergens' => ['dairy'],
        'tags' => ['gelato', 'base', 'caramel'],
        'price' => 0.00,
        'cost' => 2.30,
        'ingredients' => [
            ['name' => 'Granulated Sugar', 'quantity' => 200, 'uom' => 'g', 'cost_per_unit' => 0.001],
            ['name' => 'Heavy Cream', 'quantity' => 300, 'uom' => 'ml', 'cost_per_unit' => 0.008],
            ['name' => 'Whole Milk', 'quantity' => 200, 'uom' => 'ml', 'cost_per_unit' => 0.003],
            ['name' => 'Sea Salt', 'quantity' => 5, 'uom' => 'g', 'cost_per_unit' => 0.01],
            ['name' => 'Butter', 'quantity' => 30, 'uom' => 'g', 'cost_per_unit' => 0.02],
        ],
        'instructions' => [
            "Melt sugar in saucepan until golden caramel color",
            "Slowly add warm cream (mixture will bubble vigorously)",
            "Stir in butter and sea salt",
            "Cool completely",
            "Process in gelato machine for 25 minutes",
            "Store at -18°C"
        ],
        'yield_quantity' => 40,
        'preparation_time' => 70
    ],
    [
        'product_name' => 'Mint Chip Gelato Base',
        'sku' => 'GB-MIN-005',
        'description' => 'Refreshing mint chip gelato base mixture',
        'allergens' => ['dairy'],
        'tags' => ['gelato', 'base', 'mint'],
        'price' => 0.00,
        'cost' => 2.00,
        'ingredients' => [
            ['name' => 'Fresh Mint Leaves', 'quantity' => 20, 'uom' => 'g', 'cost_per_unit' => 0.03],
            ['name' => 'Whole Milk', 'quantity' => 450, 'uom' => 'ml', 'cost_per_unit' => 0.003],
            ['name' => 'Heavy Cream', 'quantity' => 250, 'uom' => 'ml', 'cost_per_unit' => 0.008],
            ['name' => 'Sugar', 'quantity' => 130, 'uom' => 'g', 'cost_per_unit' => 0.001],
            ['name' => 'Egg Yolks', 'quantity' => 5, 'uom' => 'pcs', 'cost_per_unit' => 0.15],
            ['name' => 'Dark Chocolate Chunks', 'quantity' => 80, 'uom' => 'g', 'cost_per_unit' => 0.025],
        ],
        'instructions' => [
            "Infuse milk with fresh mint leaves for 30 minutes",
            "Remove mint leaves and heat milk with cream and sugar to 85°C",
            "Mix with egg yolks",
            "Cool completely",
            "Add chocolate chunks",
            "Process in gelato machine for 25 minutes",
            "Store at -18°C"
        ],
        'yield_quantity' => 50,
        'preparation_time' => 75
    ]
];

// Create each new gelato base recipe
foreach ($additionalGelatoBases as $recipeData) {
    // Check if product already exists
    $existingProduct = Product::where('sku', $recipeData['sku'])->first();
    
    if ($existingProduct) {
        echo "Product with SKU {$recipeData['sku']} already exists, skipping...\n";
        continue;
    }
    
    // Create the product first
    $product = Product::create([
        'name' => $recipeData['product_name'],
        'sku' => $recipeData['sku'],
        'branch_id' => $branch->id,
        'product_type_id' => 9, // Assuming Gelato Base product type ID is 9
        'description' => $recipeData['description'],
        'price' => $recipeData['price'],
        'cost' => $recipeData['cost'],
        'shelf_life_days' => 30,
        'uom_id' => $gramUom->id,
        'unit_weight' => 100.00,
        'is_active' => true,
        'is_available' => true,
        'allergens' => $recipeData['allergens'],
        'tags' => $recipeData['tags'],
    ]);
    
    echo "Created product: {$product->name}\n";
    
    // Create the recipe
    $recipe = Recipe::create([
        'branch_id' => $branch->id,
        'department_id' => $gelatoDepartment->id,
        'product_id' => $product->id,
        'product_name' => $recipeData['product_name'],
        'sku' => $recipeData['sku'] . '-RCP', // Recipe SKU
        'product_type_id' => 9, // Gelato Base
        'cost_per_unit' => $recipeData['cost'] / $recipeData['yield_quantity'], // Cost per unit
        'uom_id' => $gramUom->id,
        'yield_quantity' => $recipeData['yield_quantity'],
        'preparation_time' => $recipeData['preparation_time'],
        'instructions' => json_encode($recipeData['instructions']),
        'status' => 'active',
        'created_by_id' => 1, // Assuming a default user
        'created_by_type' => 'App\Models\User',
    ]);
    
    echo "Created recipe: {$recipe->product_name}\n";
    
    // Create recipe ingredients
    foreach ($recipeData['ingredients'] as $ingredientData) {
        // Find the item by name
        $item = Item::where('name', $ingredientData['name'])->first();
        
        if (!$item) {
            echo "Item {$ingredientData['name']} not found, creating...\n";
            
            // Create the item if it doesn't exist
            $uom = UnitOfMeasure::where('symbol', $ingredientData['uom'])->first();
            if (!$uom) {
                $uom = UnitOfMeasure::create([
                    'name' => ucfirst($ingredientData['uom']),
                    'symbol' => $ingredientData['uom'],
                    'code' => strtoupper($ingredientData['uom']),
                    'description' => ucfirst($ingredientData['uom']) . ' unit of measure',
                ]);
            }
            
            $item = Item::create([
                'name' => $ingredientData['name'],
                'sku' => 'ING-' . strtoupper(str_replace(' ', '_', $ingredientData['name'])),
                'uom_id' => $uom->id,
                'branch_id' => $branch->id, // Add branch_id
                'category_id' => 1, // Assuming a default category
                'reorder_level' => 10,
                'is_active' => true,
                'is_available' => true,
            ]);
        }
        
        // Create the recipe ingredient
        RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'item_id' => $item->id,
            'quantity' => $ingredientData['quantity'],
            'uom_id' => $item->uom_id,
            'cost_per_unit' => $ingredientData['cost_per_unit'],
            'waste_percentage' => 5.00, // 5% waste
            'sort_order' => 0,
            'notes' => "Required for {$recipeData['product_name']}",
            'preparation_notes' => 'Standard preparation',
        ]);
        
        echo "Added ingredient: {$ingredientData['name']} to recipe\n";
    }
    
    echo "Successfully created recipe for {$recipeData['product_name']}\n\n";
}

echo "All new gelato base recipes have been created!\n";