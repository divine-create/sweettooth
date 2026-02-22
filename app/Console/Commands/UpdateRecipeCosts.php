<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use Illuminate\Console\Command;

class UpdateRecipeCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipe:update-costs {--recipe=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update recipe ingredient costs from item purchase history';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $recipeId = $this->option('recipe');
        
        if ($recipeId) {
            // Update specific recipe
            $recipe = Recipe::with('ingredients.item.purchaseItems')->find($recipeId);
            
            if (!$recipe) {
                $this->error("Recipe with ID {$recipeId} not found.");
                return 1;
            }
            
            $this->info("Updating costs for recipe: {$recipe->product_name}");
            $recipe->updateIngredientCostsFromItems();
            $this->info("Successfully updated costs for recipe: {$recipe->product_name}");
        } else {
            // Update all recipes
            $recipes = Recipe::with('ingredients.item.purchaseItems')->get();
            
            $totalRecipes = $recipes->count();
            $updatedCount = 0;
            
            $this->info("Found {$totalRecipes} recipes to update...");
            
            $bar = $this->output->createProgressBar($totalRecipes);
            $bar->start();
            
            foreach ($recipes as $recipe) {
                $recipe->updateIngredientCostsFromItems();
                $updatedCount++;
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("Successfully updated costs for {$updatedCount} recipes.");
        }
        
        return 0;
    }
}
