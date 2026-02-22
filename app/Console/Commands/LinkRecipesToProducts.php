<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkRecipesToProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipes:link-products {--dry-run : Run without updating database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link recipes to products by matching product_name';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 Running in DRY RUN mode - no changes will be made');
        }

        // Get all recipes without product_id
        $recipes = DB::table('recipes')
            ->whereNull('product_id')
            ->get();

        $totalRecipes = $recipes->count();
        $this->info("Found {$totalRecipes} recipes without product_id");

        $matched = 0;
        $notMatched = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($totalRecipes);
        $bar->start();

        foreach ($recipes as $recipe) {
            try {
                // Extract clean product name (remove code in parentheses)
                $recipeName = $recipe->product_name;
                $cleanName = preg_replace('/\s*\(\d+\)\s*$/', '', $recipeName);
                $cleanName = trim($cleanName);

                // Try exact match first (case-insensitive)
                $product = DB::table('products')
                    ->where('branch_id', $recipe->branch_id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($cleanName)])
                    ->first();

                // If no exact match, try partial match
                if (!$product) {
                    $product = DB::table('products')
                        ->where('branch_id', $recipe->branch_id)
                        ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($cleanName) . '%'])
                        ->first();
                }

                // If still no match, try without branch filter
                if (!$product) {
                    $product = DB::table('products')
                        ->whereRaw('LOWER(name) = ?', [strtolower($cleanName)])
                        ->first();
                }

                if ($product) {
                    if (!$dryRun) {
                        DB::table('recipes')
                            ->where('id', $recipe->id)
                            ->update(['product_id' => $product->id]);
                    }
                    $matched++;
                } else {
                    $notMatched++;
                    $this->warn("\nNo match found for: {$recipeName} (SKU: {$recipe->sku})");
                }

                $bar->advance();
            } catch (\Exception $e) {
                $errors++;
                $this->error("\nError processing recipe {$recipe->id}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Status', 'Count'],
            [
                ['✅ Matched', $matched],
                ['❌ Not Matched', $notMatched],
                ['⚠️ Errors', $errors],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN completed. Run without --dry-run to apply changes.');
        } else {
            $this->info("Successfully linked {$matched} recipes to products!");
            
            if ($notMatched > 0) {
                $this->warn("{$notMatched} recipes could not be matched. You may need to link them manually.");
            }
        }

        return Command::SUCCESS;
    }
}
