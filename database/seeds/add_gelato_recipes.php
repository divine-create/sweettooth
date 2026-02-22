<?php

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Product;
use App\Models\Item;
use App\Models\UnitOfMeasure;
use App\Models\Department;

// Add more gelato recipes for testing

// First, let's create some additional gelato products if they don't exist
$gelatoDepartment = Department::where('name', 'Gelato Production')->first();
if (!$gelatoDepartment) {
    echo "Gelato Production department not found\n";
    exit(1);
}

// Get existing gelato products
$gelatoProducts = Product::whereHas('productType', function($q) {
    $q->where('name', 'Gelato Flavors');
})->get();

// Define new gelato recipes to create
$newGelatoRecipes = [
    [
        'product_name' => 'Mango Gelato',
        'sku' => 'GF-MAN-004',
        'description' => 'Fresh mango gelato with tropical flavor',
        'allergens' => ['dairy'],
        'tags' => ['gelato', 'fruit', 'summer'],
        'price' => 5.00,
        'cost' => 2.00,
        'ingredients' => [
            ['name' => 'Mango Puree', 'quantity' => 300, 'uom' => 'g', 'cost_per_unit' => 0.02],
            ['name' => 'Whole Milk', 'quantity' => 400, 'uom' => 'ml', 'cost_per_unit' => 0.003],
            ['name' => 'Heavy Cream', 'quantity' => 200, 'uom' => 'ml', 'cost_per_unit' => 0.008],
            ['name' => 'Sugar', 'quantity' => 100, 'uom' => 'g', 'cost_per_unit' => 0.001],
            ['name' => 'Egg Yolks', 'quantity' => 4, 'uom' => 'pcs', 'cost_per_unit' => 0.15],
        ],
        'instructions' => [
            "Blend fresh mangoes to smooth puree",
            "Heat milk, cream, and sugar to 85°C",
            "Mix with egg yolks",
            "Cool completely",
            "Add mango puree",
            "Process in gelato machine for 25 minutes",
            "Store at -18°C"
        ],
        'yield_quantity' => 50,
        'preparation_time' => 60
    ],
    [
        'product_name' => 'Hazelnut Gelato',
        'sku' => 'GF-HAZ-005',
        'description' => 'Rich hazelnut gelato with authentic Italian flavor',
        'allergens' => ['dairy', 'nuts'],
        'tags' => ['gelato', 'premium', 'nuts'],
        'price' => 6.00,
        'cost' => 2.50,
        'ingredients' => [
            ['name' => 'Hazelnut Paste', 'quantity' => 200, 'uom' => 'g', 'cost_per_unit' => 0.03],
            ['name' => 'Whole Milk', 'quantity' => 450, 'uom' => 'ml', 'cost_per_unit' => 0.003],
            ['name' => 'Heavy Cream', 'quantity' => 250, 'uom' => 'ml', 'cost_per_unit' => 0.008],
            ['name' => 'Sugar', 'quantity' => 120, 'uom' => 'g', 'cost_per_unit' => 0.001],
            ['name' => 'Egg Yolks', 'quantity' => 5, 'uom' => 'pcs', 'cost_per_unit' => 0.15],
        ],
        'instructions' => [
            "Grind hazelnuts into fine paste",
            "Heat milk and cream to 85°C",
            "Mix sugar with egg yolks",
            "Combine hot milk with egg mixture",
            "Add hazelnut paste and mix well",
            "Cool to 4°C",
            "Process in gelato machine for 25 minutes",
            "Store at -18°C"
        ],
        'yield_quantity' => 50,
        'preparation_time' => 70
    ],
    [
        'product_name' => 'Coffee Gelato',
        'sku' => 'GF-COF-006',
        'description' => 'Intense coffee gelato with espresso flavor',
        'allergens' => ['dairy'],
        'tags' => ['gelato', 'coffee', 'premium'],
        'price' => 5.50,
        'cost' => 2.20,
        'ingredients' => [
            ['name' => 'Espresso Coffee', 'quantity' => 100, 'uom' => 'ml', 'cost_per_unit' => 0.05],
            ['name' => 'Whole Milk', 'quantity' => 400, 'uom' => 'ml', 'cost_per_unit' => 0.003],
            ['name' => 'Heavy Cream', 'quantity' => 300, 'uom' => 'ml', 'cost_per_unit' => 0.008],
            ['name' => 'Sugar', 'quantity' => 110, 'uom' => 'g', 'cost_per_unit' => 0.001],
            ['name' => 'Egg Yolks', 'quantity' => 4, 'uom' => 'pcs', 'cost_per_unit' => 0.15],
            ['name' => 'Cocoa Powder', 'quantity' => 20, 'uom' => 'g', 'cost_per_unit' => 0.02],
        ],
        'instructions' => [
            "Brew strong espresso coffee",
            "Heat milk, cream, and sugar to 85°C",
            "Mix with egg yolks",
            "Add cooled espresso and cocoa powder",
            "Mix well and cool to 4°C",
            "Process in gelato machine for 25 minutes",
            "Store at -18°C"
        ],
        'yield_quantity' => 50,
        'preparation_time' => 65
    ],
    [
        'product_name' => 'Coconut Gelato',
        'sku' => 'GF-COC-007',
        'description' => 'Creamy coconut gelato with tropical taste',
        'allergens' => ['dairy'],
        'tags' => ['gelato', 'coconut', 'vegan_option'],
        'price' => 5.25,
        'cost' => 2.10,
        'ingredients' => [
            ['name' => 'Coconut Milk', 'quantity' => 500, 'uom' => 'ml', 'cost_per_unit' => 0.006],
            ['name' => 'Coconut Cream', 'quantity' => 200, 'uom' => 'ml', 'cost_per_unit' => 0.012],
            ['name' => 'Sugar', 'quantity' => 130, 'uom' => 'g', 'cost_per_unit' => 0.001],
            ['name' => 'Egg Yolks', 'quantity' => 5, 'uom' => 'pcs', 'cost_per_unit' => 0.15],
            ['name' => 'Shredded Coconut', 'quantity' => 50, 'uom' => 'g', 'cost_per_unit' => 0.015],
        ],
        'instructions' => [
            "Heat coconut milk and cream to 85°C",
            "Mix sugar with egg yolks",
            "Combine hot coconut mixture with egg mixture",
            "Add shredded coconut",
            "Cool to 4°C",
            "Process in gelato machine for 25 minutes",
            "Store at -18°C"
        ],
        'yield_quantity' => 50,
        'preparation_time' => 60
    ],
    [
        'product_name' => 'Matcha Gelato',
        'sku' => 'GF-MAT-008',
        'description' => 'Premium matcha green tea gelato',
        'allergens' => ['dairy'],
        'tags' => ['gelato', 'matcha', 'premium'],
        'price' => 6.50,
        'cost' => 3.00,
        'ingredients' => [
            ['name' => 'Matcha Powder', 'quantity' => 30, 'uom' => 'g', 'cost_per_unit' => 0.10],
            ['name' => 'Whole Milk', 'quantity' => 450, 'uom' => 'ml', 'cost_per_unit' => 0.003],
            ['name' => 'Heavy Cream', 'quantity' => 250, 'uom' => 'ml', 'cost_per_unit' => 0.008],
            ['name' => 'Sugar', 'quantity' => 100, 'uom' => 'g', 'cost_per_unit' => 0.001],
            ['name' => 'Egg Yolks', 'quantity' => 4, 'uom' => 'pcs', 'cost_per_unit' => 0.15],
        ],
        'instructions' => [
            "Sift matcha powder with some warm milk",
            "Heat remaining milk and cream to 85°C",
            "Mix sugar with egg yolks",
            "Combine hot milk with egg mixture",
            "Add matcha mixture and mix well",
            "Cool to 4°C",
            "Process in gelato machine for 25 minutes",
            "Store at -18°C"
        ],
        'yield_quantity' => 50,
        'preparation_time' => 65
    ]
];

// Get the current branch ID (assuming we're using the first active branch)
$branch = \App\Models\Branch::first();
if (!$branch) {
    echo "No branch found\n";
    exit(1);
}

// Get the unit of measure for grams
$gramUom = UnitOfMeasure::where('symbol', 'g')->first();
if (!$gramUom) {
    echo "Gram unit of measure not found\n";
    exit(1);
}

// Create each new gelato recipe
foreach ($newGelatoRecipes as $recipeData) {
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
        'product_type_id' => 10, // Assuming Gelato Flavors product type ID is 10
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
        'product_type_id' => 10, // Gelato Flavors
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

echo "All new gelato recipes have been created!\n";