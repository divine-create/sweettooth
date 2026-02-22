<?php

// Script to update product stock for specific branch to show as in stock

require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesShift;
use Carbon\Carbon;

// Initialize Laravel application
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Define the target branch ID
$targetBranchId = '019c1e63-b2e7-70f2-81ab-c6347efd07f0';  // SweetTooth Calabar

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

echo "Updating stock for branch: {$branch->name} ({$branch->id})\n";

// Define the products and their prices from the user's list
$productsData = [
    ['name' => 'Almond Danish', 'price' => 4.00],
    ['name' => 'Banana Bread', 'price' => 5.99],
    ['name' => 'Berry Compote Topping', 'price' => 0.00], // Topping
    ['name' => 'Butter Croissant', 'price' => 3.50],
    ['name' => 'Caramel Sauce Topping', 'price' => 0.00], // Topping
    ['name' => 'Chocolate Cake Slice', 'price' => 7.50],
    ['name' => 'Chocolate Chip Cookie', 'price' => 2.50],
    ['name' => 'Chocolate Gelato', 'price' => 4.50],
    ['name' => 'Coconut Gelato', 'price' => 5.25],
    ['name' => 'Coffee Gelato', 'price' => 5.50],
    ['name' => 'Dark Chocolate Truffle', 'price' => 2.50],
    ['name' => 'Fruit Gummies', 'price' => 1.50],
    ['name' => 'Hazelnut Gelato', 'price' => 6.00],
    ['name' => 'Hot Fudge Topping', 'price' => 0.00], // Topping
    ['name' => 'Mango Gelato', 'price' => 5.00],
    ['name' => 'Matcha Gelato', 'price' => 6.50],
    ['name' => 'Mint Chip Gelato Base', 'price' => 0.00], // Base
    ['name' => 'Nut Praline Topping', 'price' => 0.00], // Topping
    ['name' => 'Oatmeal Raisin Cookie', 'price' => 2.50],
    ['name' => 'Pistachio Gelato', 'price' => 5.50],
    ['name' => 'Raspberry Gelato Base', 'price' => 0.00], // Base
    ['name' => 'Salted Caramel Chocolate', 'price' => 2.75],
    ['name' => 'Salted Caramel Gelato Base', 'price' => 0.00], // Base
];

// Find the Till department for sales operations
$tillDepartment = \App\Models\Department::where('name', 'Till')->first();
if (!$tillDepartment) {
    echo "Till department not found! Creating one...\n";
    $tillDepartment = \App\Models\Department::create([
        'name' => 'Till',
        'code' => 'TILL',
        'description' => 'Point of sale department',
        'category_id' => 1, // Assuming a default category
        'is_active' => true,
    ]);
}

// Get the first user to assign as employee
$user = \App\Models\User::first();
if (!$user) {
    echo "No users found! Please seed users first.\n";
    exit(1);
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

$updatedCount = 0;
$notFoundCount = 0;

foreach ($productsData as $productInfo) {
    $productName = $productInfo['name'];
    $expectedPrice = $productInfo['price'];
    
    // Look for the product by name in the target branch
    $product = Product::where('name', $productName)
                     ->where('branch_id', $targetBranchId)
                     ->first();
    
    // If not found in the target branch, try to find it in any branch
    if (!$product) {
        $product = Product::where('name', $productName)->first();
    }
    
    if ($product) {
        // Determine appropriate stock quantity based on product type
        $defaultQuantity = 50.0; // Default stock level
        
        if (strpos(strtolower($productName), 'croissant') !== false || strpos(strtolower($productName), 'danish') !== false) {
            $defaultQuantity = 50.0; // Pastries typically have higher stock
        } elseif (strpos(strtolower($productName), 'bread') !== false) {
            $defaultQuantity = 20.0; // Breads have moderate stock
        } elseif (strpos(strtolower($productName), 'cake') !== false) {
            $defaultQuantity = 10.0; // Cakes have lower stock (made fresh)
        } elseif (strpos(strtolower($productName), 'cookie') !== false) {
            $defaultQuantity = 100.0; // Cookies have high stock (longer shelf life)
        } elseif (strpos(strtolower($productName), 'gelato') !== false) {
            $defaultQuantity = 500.0; // Gelato measured in grams, higher quantities
        } elseif (strpos(strtolower($productName), 'chocolate') !== false || strpos(strtolower($productName), 'candy') !== false) {
            $defaultQuantity = 200.0; // Confectionery has higher stock
        } elseif (strpos(strtolower($productName), 'topping') !== false || strpos(strtolower($productName), 'base') !== false) {
            $defaultQuantity = 100.0; // Toppings and bases
        }
        
        // Check if stock record already exists for this product today
        $productStock = ProductStock::firstOrCreate([
            'product_id' => $product->id,
            'stock_date' => Carbon::today(),
            'sales_shift_id' => $salesShift->id,
        ], [
            'shift_type' => 'morning',
            'opening_quantity' => $defaultQuantity,
            'addition_quantity' => 0,
            'production_date' => Carbon::today(),
            'expiry_date' => Carbon::today()->addDays($product->shelf_life_days ?? 7),
            'callback_quantity' => 0,
            'redress_quantity' => 0,
            'total_available' => $defaultQuantity,
            'transfer_quantity' => 0,
            'glovo_quantity' => 0,
            'quantity_sold' => 0,
            'closing_quantity' => $defaultQuantity,
            'amount' => 0,
            'notes' => 'Updated stock to show as available',
        ]);
        
        if ($productStock->wasRecentlyCreated) {
            echo "Created new stock record for: $productName\n";
        } else {
            // Update the existing stock record to ensure it has sufficient quantity
            $productStock->update([
                'opening_quantity' => $defaultQuantity,
                'total_available' => $defaultQuantity,
                'closing_quantity' => $defaultQuantity,
                'notes' => 'Updated stock to show as available - ' . Carbon::now()
            ]);
            echo "Updated stock record for: $productName\n";
        }
        
        $updatedCount++;
    } else {
        echo "Product not found in database: $productName\n";
        $notFoundCount++;
    }
}

echo "\nSummary:\n";
echo "Products updated: $updatedCount\n";
echo "Products not found: $notFoundCount\n";
echo "Stock updates completed for branch: {$branch->name}\n";