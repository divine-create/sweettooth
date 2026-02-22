<?php

namespace App\Services;

use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use App\Models\ProductionRecord;
use App\Models\DailyProduce;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionAccountingService
{
    /**
     * Record production start - transfer raw materials to WIP
     */
    public function recordProductionStart(ProductionRecord $production): bool
    {
        try {
            DB::beginTransaction();

            $period = $this->getCurrentPeriod();
            if (!$period || $period->status !== 'open') {
                throw new Exception('No open accounting period found');
            }

            $recipe = $production->recipe;
            if (!$recipe) {
                throw new Exception('Recipe not found for production record');
            }

            $rmAccount = GlAccount::where('account_number', '1310')->firstOrFail();
            $wipAccount = GlAccount::where('account_number', '1320')->firstOrFail();

            // Calculate raw materials cost from recipe
            $rmCost = $this->calculateRawMaterialsCost($recipe, $production->quantity_produced);

            // Entry: Debit WIP, Credit Raw Materials
            $this->createEntry([
                'gl_account_id' => $wipAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'adjustment',
                'reference_type' => ProductionRecord::class,
                'reference_id' => $production->id,
                'reference_number' => $production->batch_number,
                'description' => "Raw materials to WIP - Batch {$production->batch_number}",
                'debit' => $rmCost,
                'credit' => 0,
                'entry_date' => $production->production_time,
            ]);

            $this->createEntry([
                'gl_account_id' => $rmAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'adjustment',
                'reference_type' => ProductionRecord::class,
                'reference_id' => $production->id,
                'reference_number' => $production->batch_number,
                'description' => "Raw materials reduction - Batch {$production->batch_number}",
                'debit' => 0,
                'credit' => $rmCost,
                'entry_date' => $production->production_time,
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Production start GL posting failed: ' . $e->getMessage(), [
                'production_id' => $production->id,
                'error' => $e,
            ]);
            return false;
        }
    }

    /**
     * Record production completion - transfer WIP to Finished Goods
     * Includes direct labor and overhead allocation
     */
    public function recordProductionCompletion(ProductionRecord $production): bool
    {
        try {
            DB::beginTransaction();

            $period = $this->getCurrentPeriod();
            if (!$period || $period->status !== 'open') {
                throw new Exception('No open accounting period found');
            }

            $wipAccount = GlAccount::where('account_number', '1320')->firstOrFail();
            $fgAccount = GlAccount::where('account_number', '1330')->firstOrFail();
            $laborAccount = GlAccount::where('account_number', '6120')->firstOrFail();
            $overheadAccount = GlAccount::where('account_number', '6130')->firstOrFail();

            // Calculate costs
            $laborCost = $this->calculateDirectLaborCost($production);
            $overheadCost = $this->calculateOverheadAllocation($production);
            $totalProductionCost = $laborCost + $overheadCost;

            // Entry 1: Add labor to WIP
            if ($laborCost > 0) {
                $this->createEntry([
                    'gl_account_id' => $wipAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => ProductionRecord::class,
                    'reference_id' => $production->id,
                    'reference_number' => $production->batch_number,
                    'description' => "Direct labor allocation - Batch {$production->batch_number}",
                    'debit' => $laborCost,
                    'credit' => 0,
                    'entry_date' => $production->production_time,
                ]);

                $this->createEntry([
                    'gl_account_id' => $laborAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => ProductionRecord::class,
                    'reference_id' => $production->id,
                    'reference_number' => $production->batch_number,
                    'description' => "Labor cost allocation - Batch {$production->batch_number}",
                    'debit' => 0,
                    'credit' => $laborCost,
                    'entry_date' => $production->production_time,
                ]);
            }

            // Entry 2: Add overhead to WIP
            if ($overheadCost > 0) {
                $this->createEntry([
                    'gl_account_id' => $wipAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => ProductionRecord::class,
                    'reference_id' => $production->id,
                    'reference_number' => $production->batch_number,
                    'description' => "Manufacturing overhead allocation - Batch {$production->batch_number}",
                    'debit' => $overheadCost,
                    'credit' => 0,
                    'entry_date' => $production->production_time,
                ]);

                $this->createEntry([
                    'gl_account_id' => $overheadAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => ProductionRecord::class,
                    'reference_id' => $production->id,
                    'reference_number' => $production->batch_number,
                    'description' => "Overhead allocation - Batch {$production->batch_number}",
                    'debit' => 0,
                    'credit' => $overheadCost,
                    'entry_date' => $production->production_time,
                ]);
            }

            // Entry 3: Transfer completed WIP to FG
            // Total WIP cost = Raw Materials + Labor + Overhead
            $recipe = $production->recipe;
            $rmCost = $this->calculateRawMaterialsCost($recipe, $production->quantity_produced);
            $totalWipCost = $rmCost + $laborCost + $overheadCost;

            $this->createEntry([
                'gl_account_id' => $fgAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'adjustment',
                'reference_type' => ProductionRecord::class,
                'reference_id' => $production->id,
                'reference_number' => $production->batch_number,
                'description' => "WIP to Finished Goods - Batch {$production->batch_number}",
                'debit' => $totalWipCost,
                'credit' => 0,
                'entry_date' => $production->production_time,
            ]);

            $this->createEntry([
                'gl_account_id' => $wipAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'adjustment',
                'reference_type' => ProductionRecord::class,
                'reference_id' => $production->id,
                'reference_number' => $production->batch_number,
                'description' => "WIP reduction - Batch {$production->batch_number}",
                'debit' => 0,
                'credit' => $totalWipCost,
                'entry_date' => $production->production_time,
            ]);

            // Calculate and store unit cost
            $unitCost = $production->quantity_approved > 0 
                ? $totalWipCost / $production->quantity_approved 
                : 0;

            $production->update([
                'unit_cost' => $unitCost,
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Production completion GL posting failed: ' . $e->getMessage(), [
                'production_id' => $production->id,
                'error' => $e,
            ]);
            return false;
        }
    }

    /**
     * Calculate raw materials cost based on recipe
     */
    private function calculateRawMaterialsCost(Recipe $recipe, float $quantity): float
    {
        $ingredients = RecipeIngredient::where('recipe_id', $recipe->id)->get();

        if ($ingredients->isEmpty()) {
            return 0;
        }

        $totalCost = 0;
        foreach ($ingredients as $ingredient) {
            $ingredientQuantity = $ingredient->quantity * $quantity;
            $ingredientCost = $ingredientQuantity * $ingredient->unit_cost;
            $totalCost += $ingredientCost;
        }

        return $totalCost;
    }

    /**
     * Calculate direct labor cost
     * Can be based on production time, number of workers, or fixed amount
     */
    private function calculateDirectLaborCost(ProductionRecord $production): float
    {
        // This can be customized based on your business logic
        // For now, return 0 - you would implement based on your payroll system
        // Example: $hourlyRate = 100; $hours = 2; return $hourlyRate * $hours;
        return 0;
    }

    /**
     * Calculate overhead allocation
     * Can be based on: labor hours, machine hours, direct labor cost, or units produced
     */
    private function calculateOverheadAllocation(ProductionRecord $production): float
    {
        // Overhead allocation typically based on a predetermined rate
        // Example: 50% of direct labor cost
        $laborCost = $this->calculateDirectLaborCost($production);
        return $laborCost * 0.50;
    }

    /**
     * Record rejected/defective production
     */
    public function recordProductionRejection(ProductionRecord $production): bool
    {
        try {
            DB::beginTransaction();

            $period = $this->getCurrentPeriod();
            if (!$period || $period->status !== 'open') {
                throw new Exception('No open accounting period found');
            }

            if ($production->quantity_rejected <= 0) {
                return true; // Nothing to reject
            }

            $recipe = $production->recipe;
            $rmCost = $this->calculateRawMaterialsCost($recipe, $production->quantity_rejected);
            $laborCost = $this->calculateDirectLaborCost($production) * 
                ($production->quantity_rejected / $production->quantity_produced);
            $overheadCost = $this->calculateOverheadAllocation($production) * 
                ($production->quantity_rejected / $production->quantity_produced);

            $totalRejectionCost = $rmCost + $laborCost + $overheadCost;

            $wipAccount = GlAccount::where('account_number', '1320')->firstOrFail();
            $writeoffAccount = GlAccount::where('account_number', '5210')->firstOrFail();

            // Entry: Debit Writeoff, Credit WIP
            $this->createEntry([
                'gl_account_id' => $writeoffAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'adjustment',
                'reference_type' => ProductionRecord::class,
                'reference_id' => $production->id,
                'reference_number' => $production->batch_number,
                'description' => "Production rejection - Batch {$production->batch_number}: {$production->rejection_reason}",
                'debit' => $totalRejectionCost,
                'credit' => 0,
                'entry_date' => $production->production_time,
            ]);

            $this->createEntry([
                'gl_account_id' => $wipAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'adjustment',
                'reference_type' => ProductionRecord::class,
                'reference_id' => $production->id,
                'reference_number' => $production->batch_number,
                'description' => "WIP reduction for rejection - Batch {$production->batch_number}",
                'debit' => 0,
                'credit' => $totalRejectionCost,
                'entry_date' => $production->production_time,
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Production rejection GL posting failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current open accounting period
     */
    private function getCurrentPeriod(): ?AccountingPeriod
    {
        return AccountingPeriod::where('status', 'open')
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->first();
    }

    /**
     * Create and post a GL entry
     */
    private function createEntry(array $data): GlEntry
    {
        $entry = GlEntry::create([
            ...$data,
            'status' => 'draft',
            'entered_by_id' => auth()->id() ?? null,
            'entered_by_type' => auth()->user() ? get_class(auth()->user()) : null,
        ]);

        // Auto-post
        $entry->post(auth()->id() ?? 1);

        return $entry;
    }
}
