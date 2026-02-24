<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Department;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$cornerStore = Department::where('slug', 'corner_store')->first();
if (!$cornerStore) {
    echo "ERROR: Corner Store department not found!\n";
    exit(1);
}

echo "Corner Store Department ID: {$cornerStore->id}\n\n";

$productData = [
    ['name' => 'SPICY QUARTER GRILLED CHICKEN & CHIPS', 'price' => 17500],
    ['name' => 'CAPPUCCINO COFFEE', 'price' => 4500],
    ['name' => 'NESTLE WATER', 'price' => 1500],
    ['name' => 'PINEAPPLE ZINGER SMOTHIE', 'price' => 5500],
    ['name' => 'HAMPSTEAD GREEN TEA AND CHAI', 'price' => 4000],
    ['name' => 'LONG BLACK OR AMERICANO COFFEE', 'price' => 4000],
    ['name' => 'TEQUILA SHOTS', 'price' => 4500],
    ['name' => 'JAMBALAYA RICE', 'price' => 13000],
    ['name' => 'JUMBO PRAWNS WITH POTATO MASH', 'price' => 18500],
    ['name' => 'BLACK FOREST SLICE', 'price' => 3500],
    ['name' => 'WHITE FOREST SLICE', 'price' => 3500],
    ['name' => 'BREAKFAST FOR CHAMPS', 'price' => 20000],
    ['name' => 'SPANISH LATTE', 'price' => 6000],
    ['name' => 'LATTE COFFEE', 'price' => 4500],
    ['name' => 'PRAWN PENNE PESTO', 'price' => 20000],
    ['name' => 'FRENCH FRIES (SIDE ATTRACTION)', 'price' => 8000],
    ['name' => 'SAUTEED MUSHROOMS', 'price' => 7000],
    ['name' => 'TWINNING EARL GREY', 'price' => 4000],
    ['name' => 'UHT MILK (CORNERSTORE)', 'price' => 2000],
    ['name' => 'BIGELOW GINGER PEACH TUMERIC', 'price' => 4000],
    ['name' => 'PASTA BOLOGNESE', 'price' => 12000],
    ['name' => 'BANANA PINK', 'price' => 5000],
    ['name' => 'PINEAPPLE JUICE', 'price' => 3000],
    ['name' => 'FOIL PLATE (SMALL)', 'price' => 400],
    ['name' => 'LONG ISLAND', 'price' => 8500],
    ['name' => 'SAMOSA', 'price' => 8500],
    ['name' => 'CLASSIC SPRINGROLLS', 'price' => 8500],
    ['name' => 'BIGELOW LEMON AND GINGER TEA', 'price' => 4000],
    ['name' => 'SPICY QUARTER GRILLED CHICKEN', 'price' => 15500],
    ['name' => 'FOIL PLATE BIG (ACTIVE)', 'price' => 600],
    ['name' => 'PLASTIC TAKEOUT', 'price' => 1000],
    ['name' => 'SPICY WINGS', 'price' => 15500],
    ['name' => 'ORANGE JUICE', 'price' => 3000],
    ['name' => 'RED VELVET SLICE CAKE', 'price' => 5500],
    ['name' => 'TUNA SANDWICH', 'price' => 13000],
    ['name' => 'SPICY BEEF WRAP', 'price' => 9000],
    ['name' => 'DOUBLE CHOCOLATE FRAPPUCCINO', 'price' => 8000],
    ['name' => 'COSSY CUP', 'price' => 8000],
    ['name' => 'DOUBLE ESPRESSO COFFEE', 'price' => 4500],
    ['name' => 'SINGLE ESPRESSO COFFEE', 'price' => 4000],
    ['name' => 'VANILLA ICE CREAM', 'price' => 2500],
    ['name' => 'CALL ME A SMOOTHSHAKE', 'price' => 5500],
    ['name' => 'BLUEBERRY PANCAKES', 'price' => 8000],
    ['name' => 'YANKEE BREAKFAST (SCRAMBLED)', 'price' => 12500],
    ['name' => 'STRAWBERRY MILKSHAKE', 'price' => 6000],
    ['name' => 'SHOT GLASS CUP', 'price' => 0],
    ['name' => 'VANILLA MILKSHAKE TAKE OUT', 'price' => 6000],
    ['name' => 'YANKEE BREAKFAST (OMELETTE)', 'price' => 12500],
    ['name' => "CORNERSTORE'S JUICY BURGER", 'price' => 15000],
    ['name' => 'DO IT FOR THE BRITS (SUNNY SIDE UP)', 'price' => 12500],
    ['name' => 'DO IT FOR THE BRITS(OMELETTE)', 'price' => 12500],
    ['name' => 'HAMPSTEAD GREEN TEA AND JASMINE', 'price' => 4000],
    ['name' => 'BELGIAN THICK HOT CHOCOLATE (NEW)', 'price' => 5500],
    ['name' => 'PLAIN CROISSANT', 'price' => 2200],
    ['name' => 'HAMPSTEAD GREEN TEA & MINT', 'price' => 4000],
    ['name' => 'YANKEE BREAKFAST (SUNNYSIDE)', 'price' => 12500],
    ['name' => 'FRAPUCCINO COFFEE (CARAMEL)', 'price' => 6000],
    ['name' => 'DOUBLE AGENT BURGER', 'price' => 17000],
    ['name' => 'COOKIES AND CREAM MILKSHAKE (T.O)', 'price' => 6000],
    ['name' => 'STRAWBERRY ICED TEA', 'price' => 4000],
    ['name' => 'PLAIN DOUGHNUT', 'price' => 1500],
    ['name' => 'PAIN AU CHOCOLATE CROISSANT', 'price' => 2500],
    ['name' => 'LATTE MACCHIATO', 'price' => 4000],
    ['name' => 'MOCHA OAT MILK', 'price' => 7000],
    ['name' => 'SPANISH LATTE OAT MILK', 'price' => 8000],
    ['name' => 'BEEF FRIAND', 'price' => 3000],
    ['name' => 'CAN FANTA', 'price' => 3500],
    ['name' => 'CAN MALT', 'price' => 3500],
    ['name' => 'WAFFLE CONE', 'price' => 500],
    ['name' => "CORNERSTORE'S SEAFOOD FETTUCCINE ALFREDO", 'price' => 21000],
    ['name' => 'WAFFLE MAGIC', 'price' => 14500],
    ['name' => 'BISCOF LOTUS MILKSHAKE', 'price' => 6000],
    ['name' => 'CARAMEL HOT CHOCOLATE', 'price' => 5500],
    ['name' => 'SPICY QUARTER CHICKEN AND CHIPS', 'price' => 17500],
    ['name' => 'CINNAMON ROLL', 'price' => 2500],
    ['name' => 'SAUSAGE CROISSANT', 'price' => 3000],
    ['name' => 'LEMON ICED TEA', 'price' => 4000],
    ['name' => 'MARCO POLO', 'price' => 4000],
    ['name' => 'CREAMY PENNE PASTA WITH CHICKEN', 'price' => 16000],
    ['name' => 'MOCHA COFFEE', 'price' => 5000],
    ['name' => 'BAILEYS ESPRESSO ON THE ROCK COFFEE', 'price' => 5500],
    ['name' => 'DO FOR THE BRITS OMELETE', 'price' => 12500],
    ['name' => 'DO IT FOR THE BRITS (SCRAMBLED)', 'price' => 12500],
    ['name' => 'WAFFLES WITH EGGS AND BACON (OMELETTE)', 'price' => 15500],
    ['name' => 'SMALL BANANA BREAD', 'price' => 4600],
    ['name' => 'FOIL PLATE (BIG)', 'price' => 600],
    ['name' => 'YOGI BEDTIME TEA', 'price' => 4000],
    ['name' => 'BIGELOW GREEN TEA AND GINGER', 'price' => 4000],
    ['name' => 'FOIL PLATE', 'price' => 400],
    ['name' => 'CHICKEN FRIAND', 'price' => 3000],
    ['name' => "CORNERSTORE'S CLUB SANDWICH", 'price' => 15000],
    ['name' => 'CARAMEL LATTE', 'price' => 6000],
    ['name' => 'HOUSE SPECIAL ORGANIC BREW', 'price' => 5000],
    ['name' => 'CHICKEN SAUSAGE', 'price' => 8000],
    ['name' => 'CAN COKE', 'price' => 3500],
    ['name' => 'CHICKEN WRAP', 'price' => 9000],
    ['name' => 'WAFFLES WITH ASSORTED FRUIT', 'price' => 14500],
    ['name' => 'THE GOAT BURGER', 'price' => 17000],
    ['name' => 'WAFFLES WITH WHIPPED CREAM & CHOCOLATE SAUCE', 'price' => 12500],
    ['name' => 'ICED LATTE COFFEE', 'price' => 4500],
    ['name' => 'BUTTERMILK PANCAKES', 'price' => 8000],
    ['name' => 'CAN SCHWEEPES TONIC', 'price' => 3500],
    ['name' => 'WAFFLES WITH BUTTERMILK CHICKEN TENDERS', 'price' => 15500],
    ['name' => 'MATCHA LATTE N', 'price' => 7000],
    ['name' => 'PANCAKES WITH WHIPPED CREAM & CHOCOLATE SAUCE', 'price' => 9500],
    ['name' => 'FROTHY TOP CHOCOLATE', 'price' => 5500],
    ['name' => 'FRENCH TOAST WITH ASSORTED FRUITS', 'price' => 14500],
    ['name' => 'GREEN/WHITE/BLACK/FRUITY TEA', 'price' => 4000],
    ['name' => 'CHARGRILLED PRAWNS', 'price' => 12000],
    ['name' => "CORNERSTORE'S WINGS AND CHOPS PLATTER", 'price' => 25000],
    ['name' => 'AHMAD CARDAMOM TEA', 'price' => 4000],
    ['name' => 'CARAMEL CROISSANT', 'price' => 2500],
    ['name' => 'CHOCOLATE NUTELLA CROISSANT', 'price' => 2500],
    ['name' => 'SCRAMBLED EGG (BREAKFAST)', 'price' => 6500],
    ['name' => 'COLESLAW (SIDE ATTRACTIONS)', 'price' => 8000],
    ['name' => 'GRILLED CHICKEN CAESAR SALAD', 'price' => 15000],
    ['name' => 'OMELETTE', 'price' => 6500],
    ['name' => "CORNERSTORE'S CHICKEN FETTUCCINE ALFREDO", 'price' => 14000],
    ['name' => 'TEQUILA SUNRISE', 'price' => 7000],
    ['name' => "CORNERSTORE'S MIXED GRILL PLATTER", 'price' => 30000],
    ['name' => 'BIGELOW GREEN TEA WITH POMMEGRANATE', 'price' => 4000],
    ['name' => 'DO IT FOR THE BRITS(OMELETTE)', 'price' => 12500],
    ['name' => 'CHEESE CROISSANT', 'price' => 2500],
    ['name' => 'CHAPMAN', 'price' => 7000],
    ['name' => 'FRENCH TOAST WITH EGGS & BACON (SUNNYSIDE)', 'price' => 14500],
    ['name' => 'TROPICAL BLAST SMOTHIE', 'price' => 7000],
    ['name' => 'WAFFLES WITH EGGS AND BACON (SCRAMBLED)', 'price' => 15500],
    ['name' => 'SAUTEED POTATOES', 'price' => 7500],
    ['name' => 'TRIO CLASSIC CAKE', 'price' => 5500],
    ['name' => 'CAIPIRINHA', 'price' => 8500],
    ['name' => 'VODKA', 'price' => 4500],
    ['name' => 'KALEMAZING SMOOTHIE', 'price' => 5000],
    ['name' => 'NUTTY PROFESSOR MILKSHAKE (WITH NUTS)', 'price' => 6000],
    ['name' => 'STRAWBERRY JAM DOUGHNUT', 'price' => 1500],
    ['name' => 'COCONUT DOUGHNUT', 'price' => 1500],
    ['name' => 'SPICY WINGS WITH FRENCH FRIES', 'price' => 17500],
    ['name' => 'CAN SPRITE', 'price' => 3500],
    ['name' => 'LEMONADE', 'price' => 3500],
    ['name' => 'CHOCOLATE OREO DOUGHNUT', 'price' => 1500],
    ['name' => '4 INCHES VANILLA BENTO CAKE', 'price' => 15000],
    ['name' => 'BIGELOW GREEN TEA CLASSIC', 'price' => 4000],
    ['name' => 'STRAWBERRY DAIQUIRI', 'price' => 8500],
    ['name' => 'BIGELOW CONSTANT COMMENT', 'price' => 4000],
    ['name' => 'SPIRAL COOKIES', 'price' => 3000],
    ['name' => 'CREPES WITH CHICKEN STRIPS & MUSHROOMS', 'price' => 12500],
    ['name' => 'HONEY GLAZED BACON', 'price' => 8500],
    ['name' => 'CHEESY BEEF PESTO MELT', 'price' => 12000],
    ['name' => 'CLASSIC FRENCH TOAST', 'price' => 11500],
    ['name' => 'CLASSIC BACON', 'price' => 8000],
    ['name' => 'CARAMEL LATTE ALMOND MILK', 'price' => 6500],
    ['name' => 'TWINING PURE CAMOMILE INFUSION', 'price' => 4000],
    ['name' => 'GIN N TONIC', 'price' => 5500],
    ['name' => 'MOJITO', 'price' => 8000],
    ['name' => 'NUTELLA BANANA BREAD', 'price' => 6000],
    ['name' => 'H&H CAMOMILE & MANUKA HONEY', 'price' => 4000],
    ['name' => 'CREPES WITH CHOCOLATE SAUCE', 'price' => 9000],
    ['name' => 'FRENCH TOAST WITH EGGS & BACON (OMELETTE)', 'price' => 14500],
    ['name' => 'FRENCH TOAST WITH EGGS & BACON (SCRAMBLED)', 'price' => 14500],
    ['name' => 'SUNNY SIDE UP', 'price' => 6500],
    ['name' => 'EXTRA CHEESE', 'price' => 2000],
    ['name' => 'BAILEYS SHOT', 'price' => 4500],
    ['name' => 'RED VELVET MARBLE', 'price' => 5500],
    ['name' => 'CHOCOLATE GLAZED DOUGHNUT', 'price' => 1800],
    ['name' => 'STRAWBERRY MATCHA', 'price' => 8000],
    ['name' => 'FRAPUCCINO COFFEE (CHOCOLATE)', 'price' => 6000],
    ['name' => 'PEACH ICED TEA', 'price' => 4000],
    ['name' => 'CHOCOLATE SPONGE CAKE SLICE', 'price' => 3500],
    ['name' => 'POWER HORSE', 'price' => 3700],
    ['name' => 'VANILLA SPONGE CAKE SLICE', 'price' => 3500],
    ['name' => 'STRACCIATELLA GELATO', 'price' => 2500],
    ['name' => 'WAFERS GELATO', 'price' => 2500],
    ['name' => 'DOUBLE AGENT BURGER', 'price' => 17000],
    ['name' => 'PRAWN AVOCADO SALAD', 'price' => 15000],
    ['name' => 'CARAMEL POPCORN', 'price' => 2000],
    ['name' => 'HAMPSTEAD PEPPERMINT & SPEARMINT', 'price' => 4000],
    ['name' => 'HAMPSTEAD PURE GREEN TEA', 'price' => 4000],
    ['name' => 'TIPSON MORINGA & MANGO', 'price' => 4000],
    ['name' => 'DECAF AMERICANO', 'price' => 4000],
    ['name' => 'DECAF SPANISH LATTE', 'price' => 6000],
    ['name' => 'DIPLOMAT PEPPERMINT', 'price' => 4000],
    ['name' => 'CELESTIAL MORNING THUNDER', 'price' => 4000],
    ['name' => 'ENGLISH TEA SHOP ORGANIC', 'price' => 4000],
    ['name' => 'LEMON AND GINGER INFUSO', 'price' => 4000],
    ['name' => 'CHOCO BISCOTTO', 'price' => 2500],
    ['name' => 'CARAMEL GELATO', 'price' => 2500],
    ['name' => 'WHISKY GELATO', 'price' => 2500],
    ['name' => 'COCONUT TOPPING', 'price' => 500],
    ['name' => 'ICING COOKIES', 'price' => 2000],
    ['name' => 'HAMPSTEAD GREEN TEA AND RASBERRY', 'price' => 4000],
    ['name' => 'TOAST BREAD', 'price' => 3000],
    ['name' => 'MARGARITA', 'price' => 8000],
    ['name' => 'FROZEN MARGARITA', 'price' => 8000],
    ['name' => 'SEX ON THE BEACH', 'price' => 8000],
    ['name' => 'SEX IN THE DRIVEWAY', 'price' => 8500],
    ['name' => 'DRY GIN SHOT', 'price' => 4500],
    ['name' => 'JAMAICAN RUM PUNCH', 'price' => 7500],
    ['name' => 'AFFOGATO COFFEE', 'price' => 7000],
    ['name' => 'ESPRESSO MARTINI COFFEE', 'price' => 5500],
    ['name' => 'MACCHIATO', 'price' => 4500],
    ['name' => 'MAC AND CHEESE', 'price' => 13000],
    ['name' => 'CLASSIC PANCAKE', 'price' => 7500],
    ['name' => 'CLASSIC WAFFLES', 'price' => 12500],
    ['name' => 'UNICORN', 'price' => 2500],
    ['name' => 'MASHMELLOW TOPPINGS', 'price' => 500],
    ['name' => 'COTTON CANDY', 'price' => 2000],
    ['name' => 'LATTE DECAF', 'price' => 4500],
    ['name' => 'DECAF MOCHA', 'price' => 5000],
    ['name' => 'LATTE WITH OAT MILK', 'price' => 6500],
    ['name' => 'CAPPUCCINO WITH OAT MILK', 'price' => 5500],
    ['name' => 'MATCHA OAT MILK', 'price' => 9000],
    ['name' => 'YOGI GREEN BLUEBERRY SLIM LIFE', 'price' => 4000],
    ['name' => 'HAZELNUT DOUGHNUT', 'price' => 1500],
    ['name' => 'SPECIAL DOUGHNUT', 'price' => 1500],
    ['name' => 'CRISPY DOUGHNUT', 'price' => 1500],
    ['name' => 'STRAWBERRY MUM', 'price' => 6000],
    ['name' => 'VANILLA MILKSHAKE', 'price' => 5500],
    ['name' => 'HAZELNUT GELATO', 'price' => 2500],
    ['name' => 'KINDER BUENO GELATO', 'price' => 2500],
    ['name' => 'BROWNIES GELATO', 'price' => 2500],
    ['name' => 'CARAMEL WAFER COOKIES GELATO', 'price' => 2500],
    ['name' => 'CARAMEL BLONDIES GELATO', 'price' => 2500],
    ['name' => 'GINGER SNAP COOKIES GELATO', 'price' => 2500],
    ['name' => 'CREAM BRULEE GELATO', 'price' => 2500],
    ['name' => 'BLACK DIAMOND GELATO', 'price' => 2500],
    ['name' => 'PEANUT BUTTER GELATO', 'price' => 2500],
    ['name' => 'DOLCE LATTE GELATO', 'price' => 2500],
    ['name' => 'BROWNIES CHEESE CAKE', 'price' => 7000],
    ['name' => 'WHIPPED CREAM (CORNER STORE)', 'price' => 2000],
    ['name' => 'CAN SCHWEPPS', 'price' => 3500],
    ['name' => 'STRAWBERRY ICED TEA', 'price' => 4000],
];

$updated = 0;
$notFound = [];
$attachedToDept = 0;

foreach ($productData as $item) {
    $searchName = strtoupper($item['name']);
    $searchName = str_replace(['&', "'", '&#039;'], ['AND', '', ''], $searchName);
    
    // Try to find product - case insensitive partial match
    $product = Product::whereRaw('UPPER(name) LIKE ?', ['%' . $searchName . '%'])
        ->orWhereRaw('UPPER(name) LIKE ?', ['%' . str_replace(' ', '%', $searchName) . '%'])
        ->first();
    
    // Also try with various normalizations
    if (!$product) {
        $normalizedSearch = preg_replace('/[^A-Z0-9]/', '', $searchName);
        $product = Product::whereRaw("UPPER(REPLACE(REPLACE(name, '&', 'AND'), ' ', '')) LIKE ?", ['%' . $normalizedSearch . '%'])
            ->first();
    }
    
    if ($product) {
        // Update product with Corner Store as sales department and set price
        $product->update([
            'sales_department_id' => $cornerStore->id,
            'price' => $item['price'],
            'is_active' => true,
            'is_available' => true,
        ]);
        
        // Also attach to department_product table
        DB::table('department_product')->updateOrInsert(
            [
                'department_id' => $cornerStore->id,
                'product_id' => $product->id,
            ],
            [
                'is_available' => true,
                'department_price' => $item['price'],
                'sort_order' => $attachedToDept + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        $updated++;
        $attachedToDept++;
        echo "UPDATED: {$product->name} -> {$item['price']}\n";
    } else {
        $notFound[] = $item['name'];
    }
}

echo "\n=== SUMMARY ===\n";
echo "Updated: {$updated}\n";
echo "Not Found: " . count($notFound) . "\n";

if (count($notFound) > 0) {
    echo "\nProducts not found:\n";
    foreach (array_chunk($notFound, 10) as $chunk) {
        echo "  - " . implode("\n  - ", $chunk) . "\n";
    }
}
