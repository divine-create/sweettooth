<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$items = App\Models\SaleItem::with('product')->whereHas('product', function($q) { $q->where('name', 'like', '%RED VELVET GELATO%'); })->take(2)->get();
echo json_encode($items);
