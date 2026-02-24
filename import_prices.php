<?php

$data = [
    // GELATO (Ice Cream) - 100g, 3000 each - Department 3 (GELATO)
    'gelato' => [
        ['name' => 'Blue Sky', 'price' => 3000, 'weight' => 100],
        ['name' => 'Cream Brulee', 'price' => 3000, 'weight' => 100],
        ['name' => 'Stracciatella', 'price' => 3000, 'weight' => 100],
        ['name' => 'Amarena', 'price' => 3000, 'weight' => 100],
        ['name' => 'Caramel', 'price' => 3000, 'weight' => 100],
        ['name' => 'Choco Coconut', 'price' => 3000, 'weight' => 100],
        ['name' => 'Green Apple', 'price' => 3000, 'weight' => 100],
        ['name' => 'Black Diamond', 'price' => 3000, 'weight' => 100],
        ['name' => 'Biscotto', 'price' => 3000, 'weight' => 100],
        ['name' => 'Ginger Snap', 'price' => 3000, 'weight' => 100],
        ['name' => 'Peach', 'price' => 3000, 'weight' => 100],
        ['name' => 'Pistachio', 'price' => 3000, 'weight' => 100],
        ['name' => 'Midnight Chocolate', 'price' => 3000, 'weight' => 100],
        ['name' => 'Cookies and Cream', 'price' => 3000, 'weight' => 100],
        ['name' => 'Vanilla', 'price' => 3000, 'weight' => 100],
        ['name' => 'Funky Malty', 'price' => 3000, 'weight' => 100],
        ['name' => 'Apple', 'price' => 3000, 'weight' => 100],
        ['name' => 'Peanut Butter', 'price' => 3000, 'weight' => 100],
        ['name' => 'Salted Butter Caramel', 'price' => 3000, 'weight' => 100],
        ['name' => 'Malaga', 'price' => 3000, 'weight' => 100],
        ['name' => 'Bubble Gum', 'price' => 3000, 'weight' => 100],
        ['name' => 'After Eight', 'price' => 3000, 'weight' => 100],
        ['name' => 'Whisky', 'price' => 3000, 'weight' => 100],
        ['name' => 'Strawberry', 'price' => 3000, 'weight' => 100],
        ['name' => 'Ruby Cheese Cake', 'price' => 3000, 'weight' => 100],
        ['name' => 'Brownies', 'price' => 3000, 'weight' => 100],
        ['name' => 'Funky Malty', 'price' => 3000, 'weight' => 100], // duplicate
        ['name' => 'Cream Brulee', 'price' => 3000, 'weight' => 100], // duplicate
        ['name' => 'Malaga', 'price' => 3000, 'weight' => 100], // duplicate
        ['name' => 'Cookies Lemon', 'price' => 3000, 'weight' => 100],
    ],
    // PASTRY (Doughnuts, Croissants, Breads) - Department 2 (PASTRY)
    'pastry' => [
        ['name' => 'Plain Doughnut', 'price' => 1500, 'weight' => null],
        ['name' => 'Cinnamon Doughnut', 'price' => 1500, 'weight' => null],
        ['name' => 'Coconut Doughnut', 'price' => 1500, 'weight' => null],
        ['name' => 'Jam Doughnut', 'price' => 1500, 'weight' => null],
        ['name' => 'Oreo Doughnut', 'price' => 1500, 'weight' => null],
        ['name' => 'Crispy Doughnut', 'price' => 1500, 'weight' => null],
        ['name' => 'Hazelnut Doughnut', 'price' => 1500, 'weight' => null],
        ['name' => 'Special Doughnut', 'price' => 1500, 'weight' => null],
        ['name' => 'Pain au Chocolate', 'price' => 2500, 'weight' => null],
        ['name' => 'Chocolate Glazed Doughnut', 'price' => 1800, 'weight' => null],
        ['name' => 'Plain Croissant', 'price' => 2200, 'weight' => null],
        ['name' => 'Cheese Croissant', 'price' => 2200, 'weight' => null],
        ['name' => 'Nutella Croissant', 'price' => 2500, 'weight' => null],
        ['name' => 'Sausage Croissant', 'price' => 3000, 'weight' => null],
        ['name' => 'Caramel Croissant', 'price' => 2500, 'weight' => null],
        ['name' => 'Beef Friand', 'price' => 3000, 'weight' => null],
        ['name' => 'Chicken Friand', 'price' => 3000, 'weight' => null],
        ['name' => 'Cinnamon Roll', 'price' => 2500, 'weight' => null],
        ['name' => 'Almond Croissant', 'price' => 2200, 'weight' => null],
        ['name' => 'Big Banana Bread', 'price' => 10000, 'weight' => null],
        ['name' => 'Cream Filling', 'price' => 2200, 'weight' => null],
        ['name' => 'Small Banana Bread', 'price' => 4600, 'weight' => null],
        ['name' => 'Nutella Banana Bread', 'price' => 6000, 'weight' => null],
        ['name' => 'Coconut Banana Bread', 'price' => 4600, 'weight' => null],
        ['name' => 'Baguette Bread', 'price' => 2500, 'weight' => null],
        ['name' => 'Macarons', 'price' => 2500, 'weight' => null],
        ['name' => 'Mini Croissant', 'price' => 3000, 'weight' => null],
        ['name' => 'Yurgi Cookies', 'price' => 3000, 'weight' => null],
        ['name' => 'Icing Cookies', 'price' => 2000, 'weight' => null],
        ['name' => 'Millian', 'price' => 3000, 'weight' => null],
        ['name' => 'Chocolate Chips Cookies', 'price' => 3000, 'weight' => null],
        ['name' => 'Double Chocolate Chips Cookies', 'price' => 4000, 'weight' => null],
    ],
    // CAKES/SLICES/CUPCAKES - Can be TILL (5) or CONCESSION (6) for sales, PASTRY (2) for production
    'cakes' => [
        ['name' => 'White Forest', 'price' => 3500, 'weight' => null, 'dept' => 6],
        ['name' => 'Black Forest Slice', 'price' => 3500, 'weight' => null, 'dept' => 6],
        ['name' => 'Vanilla Sponge', 'price' => 3500, 'weight' => null, 'dept' => 6],
        ['name' => 'Chocolate Sponge', 'price' => 3500, 'weight' => null, 'dept' => 6],
        ['name' => 'Redvelvet Slice', 'price' => 5500, 'weight' => null, 'dept' => 6],
        ['name' => 'Redvelvet Marble', 'price' => 5500, 'weight' => null, 'dept' => 6],
        ['name' => 'Caramel Cupcake', 'price' => 3000, 'weight' => null, 'dept' => 6],
        ['name' => 'Vanilla Cupcake', 'price' => 3500, 'weight' => null, 'dept' => 6],
        ['name' => '6inch Vanilla Bento', 'price' => 3000, 'weight' => null, 'dept' => 6],
        ['name' => 'Blackforest Cup Cake', 'price' => 2200, 'weight' => null, 'dept' => 6],
        ['name' => 'Trio Classic', 'price' => 5500, 'weight' => null, 'dept' => 6],
        ['name' => 'Mocha Cake', 'price' => 3500, 'weight' => null, 'dept' => 6],
        ['name' => 'Red Velvet Cup Cake', 'price' => 3000, 'weight' => null, 'dept' => 6],
        ['name' => 'Strawberry Cheese Cake', 'price' => 5500, 'weight' => null, 'dept' => 6],
        ['name' => 'Raspberry Cheese Cake', 'price' => 7000, 'weight' => null, 'dept' => 6],
        ['name' => 'Brownies Cheese Cake', 'price' => 7000, 'weight' => null, 'dept' => 6],
        ['name' => 'Full Cake', 'price' => 7000, 'weight' => null, 'dept' => 6],
        ['name' => 'Oreos Cup Cake', 'price' => 7000, 'weight' => null, 'dept' => 6],
        ['name' => '6 inches Chocolate Bento', 'price' => 24000, 'weight' => null, 'dept' => 6],
        ['name' => 'Ginger Bread House', 'price' => 3500, 'weight' => null, 'dept' => 6],
        ['name' => '8 inches Vanilla Dry Cake', 'price' => 20000, 'weight' => null, 'dept' => 6],
        ['name' => 'Tres Leches', 'price' => 5500, 'weight' => null, 'dept' => 6],
        ['name' => 'Big Chin Chin', 'price' => 7000, 'weight' => null, 'dept' => 6],
        ['name' => 'Small Chin Chin', 'price' => 4000, 'weight' => null, 'dept' => 6],
        ['name' => 'Plain Popcorn', 'price' => 1500, 'weight' => null, 'dept' => 6],
        ['name' => 'Chocolate Chips', 'price' => 24000, 'weight' => null, 'dept' => 6],
        ['name' => 'Chocolate Mouse', 'price' => 2200, 'weight' => null, 'dept' => 6],
        ['name' => '6 inches Vanilla Chocolate', 'price' => 3500, 'weight' => null, 'dept' => 6],
        ['name' => 'Caramel Cheesecake', 'price' => 7000, 'weight' => null, 'dept' => 6],
        ['name' => 'Pelite Gateau (Strous Mouse)', 'price' => 7000, 'weight' => null, 'dept' => 6],
    ],
];

$updated = 0;
$notFound = [];

// Gelato - Dept 3 (GELATO) - Product Type 3
foreach ($data['gelato'] as $item) {
    $product = \App\Models\Product::where('name', 'LIKE', '%' . $item['name'] . '%')
        ->where('sales_department_id', 3)
        ->first();
    
    if (!$product) {
        // Try any department
        $product = \App\Models\Product::where('name', 'LIKE', '%' . $item['name'] . '%')->first();
    }
    
    if ($product) {
        $product->update([
            'price' => $item['price'],
            'unit_weight' => $item['weight'],
            'sales_department_id' => 3,
            'product_type_id' => 3,
            'is_active' => true,
            'is_available' => true,
        ]);
        $updated++;
        echo "Updated: {$product->name} -> {$item['price']}\n";
    } else {
        $notFound[] = "GELATO: {$item['name']}";
    }
}

// Pastry - Dept 2 (PASTRY) - Product Type 2
foreach ($data['pastry'] as $item) {
    $product = \App\Models\Product::where('name', 'LIKE', '%' . $item['name'] . '%')
        ->where('sales_department_id', 2)
        ->first();
    
    if (!$product) {
        // Try any department
        $product = \App\Models\Product::where('name', 'LIKE', '%' . $item['name'] . '%')->first();
    }
    
    if ($product) {
        $product->update([
            'price' => $item['price'],
            'unit_weight' => $item['weight'],
            'sales_department_id' => 2,
            'product_type_id' => 2,
            'is_active' => true,
            'is_available' => true,
        ]);
        $updated++;
        echo "Updated: {$product->name} -> {$item['price']}\n";
    } else {
        $notFound[] = "PASTRY: {$item['name']}";
    }
}

// Cakes - Dept 6 (CONCESSION) or Dept 5 (TILL) - Product Type 2
foreach ($data['cakes'] as $item) {
    $dept = $item['dept'] ?? 6;
    $product = \App\Models\Product::where('name', 'LIKE', '%' . $item['name'] . '%')
        ->where('sales_department_id', $dept)
        ->first();
    
    if (!$product) {
        // Try any department
        $product = \App\Models\Product::where('name', 'LIKE', '%' . $item['name'] . '%')->first();
    }
    
    if ($product) {
        $product->update([
            'price' => $item['price'],
            'unit_weight' => $item['weight'],
            'sales_department_id' => $dept,
            'product_type_id' => 2,
            'is_active' => true,
            'is_available' => true,
        ]);
        $updated++;
        echo "Updated: {$product->name} -> {$item['price']}\n";
    } else {
        $notFound[] = "CAKES: {$item['name']}";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Updated: {$updated}\n";
echo "Not Found: " . count($notFound) . "\n";
foreach ($notFound as $nf) {
    echo "  - {$nf}\n";
}
