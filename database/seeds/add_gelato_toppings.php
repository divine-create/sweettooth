<?php

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Product;
use App\Models\Item;
use App\Models\UnitOfMeasure;
use App\Models\Department;

// Add gelato topping recipes for testing

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

// Define gelato topping recipes
$gelatoToppings = [
    [
        'product_name' => 'Hot Fudge Topping',
        'sku' => 'GT-FUD-001',
        'description' => 'Rich chocolate hot fudge topping for gelato',
        'allergens' => ['dairy'],
        'tags' => ['gelato', 'topping', 'chocolate'],
        'price' => 0.00,
        'cost' => 1.50,
        'ingredients' => [
            ['name' => 'Dark Chocolate', 'quantity' => 200, 'uom' => 'g', 'cost_per_unit' => 0.02],
            ['name' => 'Heavy Cream', 'quantity' => 200, 'uom' => 'ml', 'cost_per_unit' => 0.008],
            ['name' => 'Butter', 'quantity' => 20, 'uom' => 'g', 'cost_per_unit' => 0.02],
            ['name' => 'Corn Syrup', 'quantity' => 50, 'uom' => 'ml', 'cost_per_unit' => 0.01],
            ['name' => 'Vanilla Extract', 'quantity' => 5, 'uom' => 'ml', 'cost_per_unit' => 0.05],
        ],
        'instructions' => [
            "Chop chocolate finely",
            "Heat cream until simmering",
            "Pour hot cream over chocolate, stir until melted",
            "Add butter, corn syrup, and vanilla",
            "Mix until smooth",
            "Cool to room temperature before serving",
            "Store refrigerated for up to 2 weeks"
        ],
        'yield_quantity' => 400,
        'preparation_time' => 30
    ],
    [
        'product_name' => 'Caramel Sauce Topping',
        'sku' => 'GT-CAR-002',
        'description' => 'Smooth caramel sauce for gelato',
        'allergens' => ['dairy'],
        'tags' => ['gelato', 'topping', 'caramel'],
        'price' => 0.00,
        'cost' => 1.20,
        'ingredients' => [
            ['name' => 'Granulated Sugar', 'quantity' => 200, 'uom' => 'g', 'cost_per_unit' => 0.001],
            ['name' => 'Heavy Cream', 'quantity' => 150, 'uom' => 'ml', 'cost_per_unit' => 0.008],
            ['name' => 'Butter', 'quantity' => 30, 'uom' => 'g', 'cost_per_unit' => 0.02],
            ['name' => 'Sea Salt', 'quantity' => 3, 'uom' => 'g', 'cost_per_unit' => 0.01],
        ],
        'instructions' => [
            "Melt sugar in heavy-bottomed saucepan until golden brown",
            "Carefully add warm cream (mixture will bubble vigorously)",
            "Stir in butter and sea salt",
            "Cook for 2 minutes until smooth",
            "Remove from heat and cool",
            "Store in refrigerator for up to 3 weeks"
        ],
        'yield_quantity' => 300,
        'preparation_time' => 40
    ],
    [
        'product_name' => 'Berry Compote Topping',
        'sku' => 'GT-BER-003',
        'description' => 'Mixed berry compote for gelato',
        'allergens' => [],
        'tags' => ['gelato', 'topping', 'fruit'],
        'price' => 0.00,
        'cost' => 1.80,
        'ingredients' => [
            ['name' => 'Mixed Berries', 'quantity' => 400, 'uom' => 'g', 'cost_per_unit' => 0.015],
            ['name' => 'Sugar', 'quantity' => 80, 'uom' => 'g', 'cost_per_unit' => 0.001],
            ['name' => 'Lemon Juice', 'quantity' => 15, 'uom' => 'ml', 'cost_per_unit' => 0.01],
            ['name' => 'Cornstarch', 'quantity' => 10, 'uom' => 'g', 'cost_per_unit' => 0.005],
            ['name' => 'Water', 'quantity' => 30, 'uom' => 'ml', 'cost_per_unit' => 0.001],
        ],
        'instructions' => [
            "Combine berries and half the sugar, let macerate for 30 mins",
            "Cook over medium heat until berries break down",
            "Mix remaining sugar with cornstarch and water",
            "Add slurry to berry mixture, stirring constantly",
            "Cook until thickened, about 5 minutes",
            "Add lemon juice",
            "Cool completely before serving",
            "Store refrigerated for up to 1 week"
        ],
        'yield_quantity' => 450,
        'preparation_time' => 60
    ],
    [
        'product_name' => 'Nut Praline Topping',
        'sku' => 'GT-NUT-004',
        'description' => 'Crunchy nut praline for gelato',
        'allergens' => ['nuts'],
        'tags' => ['gelato', 'topping', 'nuts'],
        'price' => 0.00,
        'cost' => 2.00,
        'ingredients' => [
            ['name' => 'Mixed Nuts', 'quantity' => 200, 'uom' => 'g', 'cost_per_unit' => 0.025],
            ['name' => 'Granulated Sugar', 'quantity' => 100, 'uom' => 'g', 'cost_per_unit' => 0.001],
            ['name' => 'Butter', 'quantity' => 20, 'uom' => 'g', 'cost_per_unit' => 0.02],
            ['name' => 'Sea Salt', 'quantity' => 2, 'uom' => 'g', 'cost_per_unit' => 0.01],
        ],
        'instructions' => [
            "Toast nuts in dry pan until fragrant",
            "Add sugar and cook until sugar melts and turns amber",
            "Add butter and salt, stir to coat",
            "Spread on parchment paper to cool",
            "Break into small pieces when completely cooled",
            "Store in airtight container for up to 2 weeks"
        ],
        'yield_quantity' => 250,
        'preparation_time' => 45
    ]
];

// Create each new gelato topping recipe
foreach ($gelatoToppings as $recipeData) {
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
        'product_type_id' => 14, // Assuming Gelato Toppings product type ID is 14
        'description' => $recipeData['description'],
        'price' => $recipeData['price'],
        'cost' => $recipeData['cost'],
        'shelf_life_days' => 14,
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
        'product_type_id' => 14, // Gelato Toppings
        'cost_per_unit' => $recipeData['cost'] / ($recipeData['yield_quantity'] / 100), // Cost per 100g
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

echo "All new gelato topping recipes have been created!\n";