#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SweetTooth POS Testing Data Verification ===\n\n";

// Check if we have products with stock
$products = Product::with('productStocks')->get();
echo "Total products: " . $products->count() . "\n";

// Check today's stock
$today = Carbon::today();
$todayStock = ProductStock::where('stock_date', $today)->get();
echo "Products with stock today: " . $todayStock->count() . "\n";

// Show sample products with stock
echo "\nSample products with stock:\n";
foreach ($todayStock->take(5) as $stock) {
    echo "- {$stock->product->name}: ";
    echo "Opening: {$stock->opening_quantity}, ";
    echo "Available: {$stock->total_available}, ";
    echo "Sold: {$stock->quantity_sold}\n";
}

// Verify that we have enough data for POS operations
$hasStock = $todayStock->count() > 0;
$hasProducts = $products->count() > 0;

echo "\nPOS System Readiness Check:\n";
echo "- Products available: " . ($hasProducts ? "YES" : "NO") . "\n";
echo "- Stock data available: " . ($hasStock ? "YES" : "NO") . "\n";

if ($hasProducts && $hasStock) {
    echo "\n✅ POS system is ready for testing!\n";
    echo "You can now test sales transactions with realistic stock data.\n";
    
    // Show some sample operations that can be performed
    echo "\nSample POS operations you can test:\n";
    echo "- View available products and their stock levels\n";
    echo "- Process sales transactions\n";
    echo "- Track inventory changes\n";
    echo "- Generate sales reports\n";
    echo "- Manage stock adjustments\n";
} else {
    echo "\n❌ POS system is NOT ready for testing!\n";
}

echo "\n=== Verification Complete ===\n";