<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Payment;
use App\Models\Stock;
use App\Models\Branch;
use App\Models\Product;
use App\Models\SalesShift;
use Illuminate\Console\Command;

class SeedAccountingData extends Command
{
    protected $signature = 'accounting:seed-data {--count=5}';
    protected $description = 'Seed sample sales, purchases, and payments for accounting';

    public function handle()
    {
        $count = $this->option('count');
        $this->info("🌱 Seeding accounting data ($count transactions each)...\n");

        $this->seedSales($count);
        $this->seedPurchases($count);
        $this->seedPayments($count);

        $this->info("\n✓ Seeding complete!");
        return 0;
    }

    private function seedSales(int $count)
    {
        $this->info("📊 Creating $count sales...");
        $bar = $this->output->createProgressBar($count);

        $branch = Branch::first();
        $shift = SalesShift::first();
        $products = Product::limit(5)->get();

        if (!$branch || !$shift || $products->isEmpty()) {
            $this->warn('  ⚠ Missing branch, shift, or products. Skipping sales.');
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            try {
                $sale = Sale::create([
                    'sale_number' => 'SAL-' . date('YmdHis') . '-' . rand(1000, 9999),
                    'branch_id' => $branch->id,
                    'sales_shift_id' => $shift->id,
                    'status' => 'completed',
                    'sale_time' => now()->subDays(rand(0, 30)),
                    'subtotal' => 0,
                    'tax' => 0,
                    'total' => 0,
                    'customer_name' => 'Customer ' . ($i + 1),
                    'gl_posting_status' => 'pending',
                ]);

                // Add items
                $itemCount = rand(1, 3);
                $subtotal = 0;
                for ($j = 0; $j < $itemCount; $j++) {
                    $product = $products->random();
                    $quantity = rand(1, 5);
                    $price = rand(1000, 50000);
                    $itemTotal = $quantity * $price;
                    $subtotal += $itemTotal;

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $price,
                        'total' => $itemTotal,
                        'average_cost' => $price * 0.6,
                    ]);
                }

                $tax = $subtotal * 0.05;
                $total = $subtotal + $tax;

                $sale->update([
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                ]);

                // Create payment
                Payment::create([
                    'sale_id' => $sale->id,
                    'amount' => $total,
                    'payment_method' => 'cash',
                    'status' => 'completed',
                    'payment_time' => $sale->sale_time,
                    'gl_posting_status' => 'pending',
                ]);

                $bar->advance();
            } catch (\Exception $e) {
                $this->warn("\n  Error creating sale: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->info('');
    }

    private function seedPurchases(int $count)
    {
        $this->info("📦 Creating $count purchases...");
        $bar = $this->output->createProgressBar($count);

        $branch = Branch::first();
        $products = Product::limit(5)->get();

        if (!$branch || $products->isEmpty()) {
            $this->warn('  ⚠ Missing branch or products. Skipping purchases.');
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            try {
                $purchase = Purchase::create([
                    'purchase_number' => 'PUR-' . date('YmdHis') . '-' . rand(1000, 9999),
                    'branch_id' => $branch->id,
                    'status' => 'approved',
                    'purchase_date' => now()->subDays(rand(0, 30)),
                    'supplier_name' => 'Supplier ' . ($i + 1),
                    'subtotal' => 0,
                    'tax' => 0,
                    'total' => 0,
                    'gl_posting_status' => 'pending',
                ]);

                // Add items
                $itemCount = rand(1, 3);
                $subtotal = 0;
                for ($j = 0; $j < $itemCount; $j++) {
                    $product = $products->random();
                    $quantity = rand(5, 50);
                    $cost = rand(500, 5000);
                    $itemTotal = $quantity * $cost;
                    $subtotal += $itemTotal;

                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_cost' => $cost,
                        'total' => $itemTotal,
                    ]);
                }

                $tax = $subtotal * 0.05;
                $total = $subtotal + $tax;

                $purchase->update([
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                ]);

                $bar->advance();
            } catch (\Exception $e) {
                $this->warn("\n  Error creating purchase: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->info('');
    }

    private function seedPayments(int $count)
    {
        $this->info("💳 Creating $count additional payments...");
        $bar = $this->output->createProgressBar($count);

        try {
            for ($i = 0; $i < $count; $i++) {
                Payment::create([
                    'sale_id' => null,
                    'amount' => rand(10000, 100000),
                    'payment_method' => ['cash', 'check', 'card'][rand(0, 2)],
                    'status' => 'completed',
                    'payment_time' => now()->subDays(rand(0, 30)),
                    'description' => 'Manual payment ' . ($i + 1),
                    'gl_posting_status' => 'pending',
                ]);

                $bar->advance();
            }
        } catch (\Exception $e) {
            $this->warn("\n  Error creating payment: " . $e->getMessage());
        }

        $bar->finish();
        $this->info('');
    }
}
