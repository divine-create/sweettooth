<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $stock = \App\Models\ProductStock::first();
    if ($stock) {
        \Illuminate\Support\Facades\DB::table('product_stocks')
            ->where('id', $stock->id)
            ->update([
                'shift_id' => null,
                'opening_quantity' => 10,
                'addition_quantity' => 5,
                'production_date' => null,
                'expiry_date' => null,
                'notes' => '',
                'shift_type' => 'morning',
                'is_workflow_verified' => true,
                'verified_at' => now(),
                'verified_by' => 1,
                'workflow_step' => 'opening_verified',
                'total_available' => 15,
                'closing_quantity' => 15,
                'updated_at' => now()
            ]);
        echo "Success\n";
    } else {
        echo "No stock found\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
