<?php

// Missing products to create - capitalized names
$newProducts = [
    // GELATO - Dept 3, Type 3
    ['name' => 'BLUE SKY', 'price' => 3000, 'weight' => 100, 'dept' => 3, 'type' => 3],
    ['name' => 'GREEN APPLE', 'price' => 3000, 'weight' => 100, 'dept' => 3, 'type' => 3],
    ['name' => 'BLACK DIAMOND', 'price' => 3000, 'weight' => 100, 'dept' => 3, 'type' => 3],
    ['name' => 'FUNKY MALTY', 'price' => 3000, 'weight' => 100, 'dept' => 3, 'type' => 3],
    ['name' => 'SALTED BUTTER CARAMEL', 'price' => 3000, 'weight' => 100, 'dept' => 3, 'type' => 3],
    ['name' => 'MALAGA', 'price' => 3000, 'weight' => 100, 'dept' => 3, 'type' => 3],
    ['name' => 'WHISKY', 'price' => 3000, 'weight' => 100, 'dept' => 3, 'type' => 3],
    
    // PASTRY - Dept 2, Type 2
    ['name' => 'YURGI COOKIES', 'price' => 3000, 'weight' => null, 'dept' => 2, 'type' => 2],
    ['name' => 'MILLIAN', 'price' => 3000, 'weight' => null, 'dept' => 2, 'type' => 2],
    ['name' => 'CHOCOLATE CHIPS COOKIES', 'price' => 3000, 'weight' => null, 'dept' => 2, 'type' => 2],
    ['name' => 'DOUBLE CHOCOLATE CHIPS COOKIES', 'price' => 4000, 'weight' => null, 'dept' => 2, 'type' => 2],
    
    // CONCESSION (Cakes) - Sales Dept: 6 (Concession), Production: HOT KITCHEN (Dept 1), Type: 1 (Hot Kitchen Items)
    ['name' => 'RED VELVET SLICE', 'price' => 5500, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => 'RED VELVET MARBLE', 'price' => 5500, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => '6 INCH VANILLA BENTO', 'price' => 3000, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => 'BLACKFOREST CUPCAKE', 'price' => 2200, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => 'RED VELVET CUPCAKE', 'price' => 3000, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => 'FULL CAKE', 'price' => 7000, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => 'OREOS CUPCAKE', 'price' => 7000, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => '6 INCHES CHOCOLATE BENTO', 'price' => 24000, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => 'BIG CHIN CHIN', 'price' => 7000, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => 'SMALL CHIN CHIN', 'price' => 4000, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => 'CHOCOLATE CHIPS', 'price' => 24000, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => 'CHOCOLATE MOUSE', 'price' => 2200, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => '6 INCHES VANILLA CHOCOLATE', 'price' => 3500, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => 'CARAMEL CHEESECAKE', 'price' => 7000, 'weight' => null, 'dept' => 6, 'type' => 1],
    ['name' => 'PELITE GATEAU (STROUS MOUSE)', 'price' => 7000, 'weight' => null, 'dept' => 6, 'type' => 1],
];

$created = 0;
$errors = [];

// Get default branch
$branch = \App\Models\Branch::first();
if (!$branch) {
    echo "ERROR: No branch found\n";
    exit;
}
$branchId = $branch->id;

// Get default UOM (grams)
$uom = \App\Models\UnitOfMeasure::where('code', 'g')->first();
$uomId = $uom ? $uom->id : null;

foreach ($newProducts as $item) {
    // Check if already exists
    $exists = \App\Models\Product::where('name', $item['name'])->first();
    if ($exists) {
        echo "EXISTS: {$item['name']}\n";
        continue;
    }
    
    try {
        $product = \App\Models\Product::create([
            'name' => $item['name'],
            'sku' => \Illuminate\Support\Str::random(8),
            'branch_id' => $branchId,
            'sales_department_id' => $item['dept'],
            'product_type_id' => $item['type'],
            'price' => $item['price'],
            'unit_weight' => $item['weight'],
            'uom_id' => $uomId,
            'is_active' => true,
            'is_available' => true,
        ]);
        $created++;
        echo "CREATED: {$product->name} | Price: {$item['price']} | SalesDept: {$item['dept']} | Type: {$item['type']}\n";
    } catch (\Exception $e) {
        $errors[] = "{$item['name']}: " . $e->getMessage();
    }
}

echo "\n=== SUMMARY ===\n";
echo "Created: {$created}\n";
if (count($errors) > 0) {
    echo "Errors: " . count($errors) . "\n";
    foreach ($errors as $e) {
        echo "  - $e\n";
    }
}
