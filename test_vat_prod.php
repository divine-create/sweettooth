<?php

require '/var/www/sweettooth/vendor/autoload.php';
$app = require '/var/www/sweettooth/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

echo "--- Verifying VAT Processing on Live DB ---\n\n";

$totalSales = Sale::count();
$taxSales = Sale::where('tax', '>', 0)->count();

echo "Total completed sales on Live: " . Sale::where('status', 'completed')->count() . "\n";
echo "Completed sales with tax > 0: " . Sale::where('status', 'completed')->where('tax', '>', 0)->count() . "\n";

$config = \App\Models\BranchPosConfiguration::first();
echo "Branch POS Config VAT Rate: " . ($config ? ($config->vat_rate ?? 'null') : 'No config found') . "\n";

$latestSale = Sale::orderBy('id', 'desc')->first();
if ($latestSale) {
    echo "Latest Sale #" . $latestSale->sale_number . " (ID: " . $latestSale->id . ")\n";
    echo "Total: " . $latestSale->total . "\n";
    echo "Subtotal: " . $latestSale->subtotal . "\n";
    echo "Tax: " . $latestSale->tax . "\n";
}

echo "\nDone.\n";
