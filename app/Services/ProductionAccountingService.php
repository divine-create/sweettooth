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

            $rmAccount = GlAccount::where('account_number', '1200')->firstOrFail();
            $wipAccount = GlAccount::where('account_number', '1200')->firstOrFail();

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
     * Record a completed production batch in the general ledger.
     *
     * QuickProduce is a single-step flow (there is no separate "start" posting),
     * so this method records the full movement in one balanced transaction:
     *   DR Finished Goods / WIP   (approved output, capitalised at cost)
     *   DR Write-off Loss         (rejected output, expensed)
     *   CR Raw Materials          (materials consumed)
     *   CR Direct Labor / Overhead (when those costs are tracked)
     *
     * Debits always equal credits because the good + rejected cost split sums
     * back to the total input cost.
     */
    public function recordProductionCompletion(ProductionRecord $production): bool
    {
        try {
            DB::beginTransaction();

            // Idempotency: never post the same batch twice.
            $alreadyPosted = GlEntry::where('reference_type', ProductionRecord::class)
                ->where('reference_id', $production->id)
                ->exists();
            if ($alreadyPosted) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (!$period || $period->status !== 'open') {
                throw new Exception('No open accounting period found');
            }

            $recipe = $production->recipe;
            if (!$recipe) {
                throw new Exception('Recipe not found for production record');
            }

            $branchId = $recipe->branch_id;

            // Account numbers match the seeded chart of accounts.
            $rawMaterials  = GlAccount::where('account_number', '1200')->firstOrFail(); // Raw Materials Inventory
            $wipAccount    = GlAccount::where('account_number', '1200')->firstOrFail(); // Work in Progress
            $fgAccount     = GlAccount::where('account_number', '1200')->firstOrFail(); // Finished Goods Inventory
            $laborAccount  = GlAccount::where('account_number', '6020')->firstOrFail(); // Direct Labor
            $overheadAcct  = GlAccount::where('account_number', '6030')->firstOrFail(); // Manufacturing Overhead
            $writeOffAcct  = GlAccount::where('account_number', '5040')->firstOrFail(); // Write-off Loss

            $producedQty = (float) $production->quantity_produced;
            if ($producedQty <= 0) {
                DB::commit();
                return true; // nothing produced
            }
            $approvedQty = (float) $production->quantity_approved;
            $rejectedQty = (float) $production->quantity_rejected;

            // Input costs. Labor/overhead currently resolve to 0 until a costing
            // rule is configured, in which case they will be capitalised too.
            $rmCost       = $this->calculateRawMaterialsCost($recipe, $producedQty);
            $laborCost    = $this->calculateDirectLaborCost($production);
            $overheadCost = $this->calculateOverheadAllocation($production);
            $totalCost    = $rmCost + $laborCost + $overheadCost;

            if ($totalCost <= 0) {
                // No costed inputs yet (recipe ingredients have no cost) - record
                // a zero unit cost and skip posting rather than write empty entries.
                $production->update(['unit_cost' => 0]);
                DB::commit();
                return true;
            }

            // Split the input cost between good output (capitalised to inventory)
            // and rejected output (expensed as waste).
            $approvedCost = $totalCost * ($approvedQty / $producedQty);
            $rejectedCost = $totalCost - $approvedCost;

            // WIP recipes stock as Work in Progress; everything else as Finished Goods.
            $goodInventory = $recipe->is_wip ? $wipAccount : $fgAccount;
            $outputLabel   = $recipe->is_wip ? 'WIP produced' : 'Finished goods produced';

            // ---- Debits ----
            if ($approvedCost > 0) {
                $this->createEntry([
                    'branch_id' => $branchId,
                    'gl_account_id' => $goodInventory->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => ProductionRecord::class,
                    'reference_id' => $production->id,
                    'reference_number' => $production->batch_number,
                    'description' => "{$outputLabel} - Batch {$production->batch_number}",
                    'debit' => $approvedCost,
                    'credit' => 0,
                    'entry_date' => $production->production_time,
                ]);
            }

            if ($rejectedCost > 0) {
                $reason = $production->rejection_reason ? ": {$production->rejection_reason}" : '';
                $this->createEntry([
                    'branch_id' => $branchId,
                    'gl_account_id' => $writeOffAcct->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => ProductionRecord::class,
                    'reference_id' => $production->id,
                    'reference_number' => $production->batch_number,
                    'description' => "Production rejection write-off - Batch {$production->batch_number}{$reason}",
                    'debit' => $rejectedCost,
                    'credit' => 0,
                    'entry_date' => $production->production_time,
                ]);
            }

            // ---- Credits (inputs consumed) ----
            if ($rmCost > 0) {
                $this->createEntry([
                    'branch_id' => $branchId,
                    'gl_account_id' => $rawMaterials->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => ProductionRecord::class,
                    'reference_id' => $production->id,
                    'reference_number' => $production->batch_number,
                    'description' => "Raw materials consumed - Batch {$production->batch_number}",
                    'debit' => 0,
                    'credit' => $rmCost,
                    'entry_date' => $production->production_time,
                ]);
            }

            if ($laborCost > 0) {
                $this->createEntry([
                    'branch_id' => $branchId,
                    'gl_account_id' => $laborAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => ProductionRecord::class,
                    'reference_id' => $production->id,
                    'reference_number' => $production->batch_number,
                    'description' => "Direct labor applied - Batch {$production->batch_number}",
                    'debit' => 0,
                    'credit' => $laborCost,
                    'entry_date' => $production->production_time,
                ]);
            }

            if ($overheadCost > 0) {
                $this->createEntry([
                    'branch_id' => $branchId,
                    'gl_account_id' => $overheadAcct->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => ProductionRecord::class,
                    'reference_id' => $production->id,
                    'reference_number' => $production->batch_number,
                    'description' => "Manufacturing overhead applied - Batch {$production->batch_number}",
                    'debit' => 0,
                    'credit' => $overheadCost,
                    'entry_date' => $production->production_time,
                ]);
            }

            // Unit cost is the cost of one good unit.
            $unitCost = $approvedQty > 0 ? ($approvedCost / $approvedQty) : 0;
            $production->update(['unit_cost' => $unitCost]);

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

        $totalCost = 0.0;
        foreach ($ingredients as $ingredient) {
            // The real column is cost_per_unit (the previous code read a
            // non-existent unit_cost, so material cost always came out as 0).
            // getCostForBatchFactor applies waste % and scales per unit produced.
            $totalCost += $ingredient->getCostForBatchFactor((float) $quantity);
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

            $wipAccount = GlAccount::where('account_number', '1200')->firstOrFail();
            $writeoffAccount = GlAccount::where('account_number', '5040')->firstOrFail();

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
