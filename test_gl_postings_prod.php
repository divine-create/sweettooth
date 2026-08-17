<?php

require '/var/www/sweettooth/vendor/autoload.php';
$app = require '/var/www/sweettooth/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Sale;
use App\Models\GlEntry;
use Illuminate\Support\Facades\DB;

echo "--- GL Posting Audit on Live DB ---\n\n";

$latestGlEntry = GlEntry::where('reference_type', Sale::class)->orderBy('id', 'desc')->first();

if (!$latestGlEntry) {
    echo "No GL entries for Sales found in the database.\n";
    exit;
}

$sale = Sale::find($latestGlEntry->reference_id);

if (!$sale) {
    echo "Sale associated with the latest GL entry not found.\n";
    exit;
}

echo "Auditing Sale #" . $sale->sale_number . " (ID: " . $sale->id . ")\n";
echo "Date: " . $sale->created_at . "\n";
echo "Gross Total: " . $sale->total . "\n";
echo "Net Subtotal: " . $sale->subtotal . "\n";
echo "Tax: " . $sale->tax . "\n";
echo "Total Payments: " . $sale->payments->sum('amount') . "\n";

// Calculate cost from items
$totalCost = 0;
foreach ($sale->saleItems as $item) {
    $totalCost += (float) ($item->line_cost ?? 0);
}
echo "Calculated COGS (Cost of Goods Sold): " . $totalCost . "\n\n";

echo "--- General Ledger Entries ---\n";
$glEntries = GlEntry::with('glAccount')
                    ->where('reference_type', Sale::class)
                    ->where('reference_id', $sale->id)
                    ->get();

$totalDebit = 0;
$totalCredit = 0;

if ($glEntries->isEmpty()) {
    echo "No GL Entries found for this sale.\n";
} else {
    foreach ($glEntries as $entry) {
        $accountName = $entry->glAccount ? $entry->glAccount->name : 'UNKNOWN ACCOUNT';
        $accountType = $entry->glAccount ? $entry->glAccount->account_type : 'UNKNOWN';
        
        echo sprintf(
            "%-15s | %-30s (%-10s) | Debit: %8.2f | Credit: %8.2f\n",
            $entry->entry_type,
            substr($accountName, 0, 30),
            $accountType,
            $entry->debit,
            $entry->credit
        );

        $totalDebit += (float) $entry->debit;
        $totalCredit += (float) $entry->credit;
    }

    echo "\n--- Posting Balance Check ---\n";
    echo sprintf("Total Debits:  %8.2f\n", $totalDebit);
    echo sprintf("Total Credits: %8.2f\n", $totalCredit);

    if (abs($totalDebit - $totalCredit) < 0.01) {
        echo "STATUS: BALANCED (OK)\n";
    } else {
        echo "STATUS: UNBALANCED (ERROR)\n";
    }
}
echo "\nDone.\n";
