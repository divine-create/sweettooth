<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Department;
use Carbon\Carbon;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== POS Stock Availability Test ===\n\n";

// Get the Till department
$till = Department::where('name', 'Till')->first();
if (!$till) {
    echo "ERROR: Till department not found!\n";
    exit(1);
}

echo "Till Department ID: {$till->id}\n\n";

// Get today's date
$today = Carbon::today();
$todayFormatted = $today->format('Y-m-d');

echo "Today's Date: {$todayFormatted}\n\n";

// Get active sales shift
$shift = \App\Models\SalesShift::where('shift_date', $todayFormatted)
    ->where('status', 'active')
    ->first();

if (!$shift) {
    echo "ERROR: No active sales shift found for today!\n";
    exit(1);
}

echo "Active Shift ID: {$shift->id}\n\n";

// Get products available in POS (linked to Till and active)
$posProducts = Product::whereHas('departments', function($q) use ($till) {
    $q->where('department_id', $till->id)
      ->where('is_available', true);
})
->where('is_active', true)
->where('is_available', true)
->get();

echo "Total POS-available products: {$posProducts->count()}\n\n";

// Check stock availability for each product
$inStockCount = 0;
$outOfStockCount = 0;

foreach ($posProducts as $product) {
    $stock = ProductStock::where('product_id', $product->id)
        ->where('stock_date', $todayFormatted)
        ->where('sales_shift_id', $shift->id)
        ->first();
    
    if ($stock && $stock->total_available > 0) {
        echo "✓ {$product->name}: IN STOCK ({$stock->total_available})\n";
        $inStockCount++;
    } else {
        echo "✗ {$product->name}: OUT OF STOCK";
        if (!$stock) {
            echo " (No stock record)";
        } else {
            echo " (Stock: {$stock->total_available})";
        }
        echo "\n";
        $outOfStockCount++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Products in stock: {$inStockCount}\n";
echo "Products out of stock: {$outOfStockCount}\n";

if ($outOfStockCount == 0) {
    echo "\n✅ All products are properly stocked for POS testing!\n";
} else {
    echo "\n❌ Some products are still showing as out of stock.\n";
    echo "This may indicate an issue with the POS frontend logic.\n";
}

echo "\n=== END TEST ===\n";