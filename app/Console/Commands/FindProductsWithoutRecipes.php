<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class FindProductsWithoutRecipes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:find-products-without-recipes {--create : Create basic recipes for products without recipes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find products that do not have recipes and optionally create basic recipes for them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $productsWithoutRecipes = Product::whereDoesntHave('recipes')->active()->get();

        if ($productsWithoutRecipes->isEmpty()) {
            $this->info('All products have recipes!');
            return;
        }

        $this->info("Found {$productsWithoutRecipes->count()} products without recipes:");
        $this->table(
            ['ID', 'Name', 'SKU', 'Product Type', 'Department'],
            $productsWithoutRecipes->map(function ($product) {
                return [
                    $product->id,
                    $product->name,
                    $product->sku,
                    $product->productType?->name ?? 'N/A',
                    $product->departments->pluck('name')->join(', ') ?: 'None',
                ];
            })
        );

        if ($this->option('create')) {
            $this->createBasicRecipes($productsWithoutRecipes);
        }
    }

    private function createBasicRecipes($products)
    {
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            // Find appropriate department based on product type
            $departmentId = $product->departments->first()?->id;

            if (!$departmentId) {
                // Try to find department based on product type
                if (str_contains(strtolower($product->productType?->name ?? ''), 'gelato')) {
                    $department = \App\Models\Department::where('name', 'Gelato Production')->first();
                    $departmentId = $department?->id;
                } elseif (str_contains(strtolower($product->productType?->name ?? ''), 'candy') || str_contains(strtolower($product->productType?->name ?? ''), 'confection')) {
                    $department = \App\Models\Department::where('name', 'Confectionaries Production')->first();
                    $departmentId = $department?->id;
                }
            }

            if (!$departmentId) {
                // Fallback to first production department
                $department = \App\Models\Department::whereHas('category', fn($q) => $q->where('name', 'Production'))->first();
                $departmentId = $department?->id;
            }

            // Create a basic recipe
            $recipe = $product->recipes()->create([
                'product_name' => $product->name,
                'sku' => $product->sku,
                'product_type_id' => $product->product_type_id,
                'uom_id' => $product->uom_id,
                'yield_quantity' => $product->recipe_yield ?: 1,
                'preparation_time' => 30, // Default 30 minutes
                'instructions' => json_encode([
                    'Basic preparation instructions for ' . $product->name,
                    'Follow standard procedures for this product type'
                ]),
                'status' => 'active',
                'created_by_id' => 1, // Assuming admin user
                'created_by_type' => 'App\\Models\\User',
                'branch_id' => $product->branch_id,
                'department_id' => $departmentId,
            ]);

            // Add basic ingredients based on product type
            $this->addBasicIngredients($recipe, $product);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Basic recipes created. Please review and update ingredients manually.');
    }

    private function addBasicIngredients($recipe, $product)
    {
        $productType = strtolower($product->productType?->name ?? '');

        if (str_contains($productType, 'gelato base')) {
            // Basic vanilla gelato base ingredients
            $ingredients = [
                ['name' => 'Milk - Fresh Whole', 'quantity' => 4, 'uom_code' => 'l'],
                ['name' => 'Cream - Heavy Whipping', 'quantity' => 1, 'uom_code' => 'l'],
                ['name' => 'Sugar - White Granulated', 'quantity' => 500, 'uom_code' => 'g'],
                ['name' => 'Vanilla Extract', 'quantity' => 20, 'uom_code' => 'ml'],
            ];
        } elseif (str_contains($productType, 'candy') || str_contains($productType, 'gummy')) {
            // Basic fruit gummies ingredients
            $ingredients = [
                ['name' => 'Gelatin Powder', 'quantity' => 20, 'uom_code' => 'g'],
                ['name' => 'Sugar - White Granulated', 'quantity' => 400, 'uom_code' => 'g'],
                ['name' => 'Fruit Puree', 'quantity' => 300, 'uom_code' => 'g'],
                ['name' => 'Citric Acid', 'quantity' => 5, 'uom_code' => 'g'],
            ];
        } else {
            // Generic placeholder
            $ingredients = [
                ['name' => 'Basic Ingredient', 'quantity' => 1, 'uom_code' => 'kg'],
            ];
        }

        foreach ($ingredients as $ing) {
            $item = \App\Models\Item::where('name', $ing['name'])->first();
            $uom = \App\Models\UnitOfMeasure::where('code', $ing['uom_code'])->first();

            if ($item && $uom) {
                $recipe->ingredients()->create([
                    'item_id' => $item->id,
                    'quantity' => $ing['quantity'],
                    'uom_id' => $uom->id,
                    'cost_per_unit' => (float) ($item->unit_price ?: ($item->purchaseItems()->avg('cost_per_unit') ?: 0)),
                    'waste_percentage' => 0,
                    'notes' => 'Auto-generated ingredient - please verify quantities and costs',
                ]);
            }
        }
    }
}
