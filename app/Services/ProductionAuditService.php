<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DailyProduce;
use App\Models\ProductionRecord;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Model;

/**
 * ProductionAuditService
 *
 * Specialized audit service for Production module operations.
 * Extends the generic AuditService with production-specific logging methods.
 *
 * ACTOR PATTERN:
 * All methods accept a $causer parameter (User or Employee model).
 * The actor's ID and class are extracted using polymorphic pattern:
 *   - $actor_id = $actor->id
 *   - $actor_class = get_class($actor)
 *
 * These are used for:
 * - Audit log causer_id and causer_type
 * - Polymorphic *_by_id and *_by_type columns on related models
 *
 * Usage:
 *   $actor = current_actor();  // Returns User or Employee
 *   ProductionAuditService::logBatchProduced($actor, $batch, $notes);
 *
 * Handles audit logging for:
 * - Production batch records (DailyProduce)
 * - Production quality tracking
 * - Recipe CRUD operations
 * - Waste and rejection tracking
 * - Shift closing operations
 * - Raw material tracking
 * - Callback approvals
 *
 * @see AuditService for generic logging methods
 * @see current_actor() helper function in BranchHelper
 */
class ProductionAuditService
{
    /**
     * Log production batch recording.
     *
     * Records when a batch of product is produced, including:
     * - Quantity produced
     * - Quality status (good/rejected)
     * - Producer information
     * - Batch number
     *
     * @param Model $causer The employee/user recording production
     * @param ProductionRecord $batch The production batch
     * @param string|null $notes Additional notes about the batch
     *
     * @return AuditLog
     */
    public static function logBatchProduced(
        Model $causer,
        ProductionRecord $batch,
        ?string $notes = null
    ): AuditLog {
        return AuditService::log(
            $causer,
            'record_production_batch',
            $batch,
            $notes ?? "Recorded batch #{$batch->batch_number}: {$batch->quantity_produced} units, Status: {$batch->quality_status}",
            'completed',
            null,
            [
                'batch_number' => $batch->batch_number,
                'quantity_produced' => $batch->quantity_produced,
                'quality_status' => $batch->quality_status,
                'production_time' => $batch->production_time,
            ]
        );
    }

    /**
     * Log quality adjustment on a production batch.
     *
     * Tracks when quantities approved/rejected are adjusted.
     *
     * @param Model $causer
     * @param ProductionRecord $batch
     * @param int|float $previousApproved
     * @param int|float $newApproved
     * @param int|float $previousRejected
     * @param int|float $newRejected
     * @param string|null $reason
     *
     * @return AuditLog
     */
    public static function logQualityAdjustment(
        Model $causer,
        ProductionRecord $batch,
        $previousApproved,
        $newApproved,
        $previousRejected,
        $newRejected,
        ?string $reason = null
    ): AuditLog {
        return AuditService::log(
            $causer,
            'adjust_batch_quality',
            $batch,
            $reason ?? "Adjusted batch quality quantities",
            'completed',
            null,
            [
                'batch_number' => $batch->batch_number,
                'approved_before' => $previousApproved,
                'approved_after' => $newApproved,
                'rejected_before' => $previousRejected,
                'rejected_after' => $newRejected,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log dispatch of production quantities.
     *
     * Tracks when produced quantities are dispatched to sales departments.
     *
     * @param Model $causer
     * @param ProductionRecord $batch
     * @param int|float $sentOut
     * @param int|float $forOrder
     * @param string|null $notes
     *
     * @return AuditLog
     */
    public static function logDispatch(
        Model $causer,
        ProductionRecord $batch,
        $sentOut,
        $forOrder,
        ?string $notes = null
    ): AuditLog {
        return AuditService::log(
            $causer,
            'dispatch_batch_quantity',
            $batch,
            $notes ?? "Dispatched batch: {$sentOut} sent out, {$forOrder} for order",
            'completed',
            null,
            [
                'batch_number' => $batch->batch_number,
                'sent_out' => $sentOut,
                'for_order' => $forOrder,
                'dispatch_time' => now(),
            ]
        );
    }

    /**
     * Log daily produce opening quantity recording.
     *
     * Records when opening quantities are set for daily production tracking.
     *
     * @param Model $causer
     * @param DailyProduce $dailyProduce
     * @param int|float $openingQty
     * @param string|null $reason
     *
     * @return AuditLog
     */
    public static function logOpeningQuantity(
        Model $causer,
        DailyProduce $dailyProduce,
        $openingQty,
        ?string $reason = null
    ): AuditLog {
        return AuditService::log(
            $causer,
            'record_opening_quantity',
            $dailyProduce,
            $reason ?? "Recorded opening quantity: {$openingQty}",
            'completed',
            null,
            [
                'opening_quantity' => $openingQty,
                'recipe_id' => $dailyProduce->recipe_id,
                'shift_id' => $dailyProduce->shift_id,
            ]
        );
    }

    /**
     * Log waste/rejection tracking.
     *
     * Tracks when production waste or rejected quantities are recorded.
     *
     * @param Model $causer
     * @param DailyProduce $dailyProduce
     * @param int|float $wasteQty
     * @param string $reason Mandatory reason for waste
     *
     * @return AuditLog
     */
    public static function logWaste(
        Model $causer,
        DailyProduce $dailyProduce,
        $wasteQty,
        string $reason
    ): AuditLog {
        return AuditService::log(
            $causer,
            'record_production_waste',
            $dailyProduce,
            "Recorded waste: {$wasteQty}. Reason: {$reason}",
            'completed',
            null,
            [
                'waste_quantity' => $wasteQty,
                'waste_reason' => $reason,
                'recipe_id' => $dailyProduce->recipe_id,
            ]
        );
    }

    /**
     * Log recipe creation.
     *
     * Tracks when new recipes are created with full ingredient details.
     *
     * @param Model $causer
     * @param Recipe $recipe
     * @param array|null $ingredients Ingredient details
     *
     * @return AuditLog
     */
    public static function logRecipeCreated(
        Model $causer,
        Recipe $recipe,
        ?array $ingredients = null
    ): AuditLog {
        return AuditService::log(
            $causer,
            'create_recipe',
            $recipe,
            "Created recipe: {$recipe->product_name}",
            'completed',
            null,
            [
                'recipe_name' => $recipe->product_name,
                'yield' => $recipe->yield_quantity,
                'unit_cost' => $recipe->cost_per_unit,
                'ingredients_count' => count($ingredients ?? []),
                'ingredients' => $ingredients,
            ]
        );
    }

    /**
     * Log recipe modification.
     *
     * Tracks when recipe details, ingredients, or costs are changed.
     *
     * @param Model $causer
     * @param Recipe $recipe
     * @param array $changes What was changed
     * @param string|null $reason
     *
     * @return AuditLog
     */
    public static function logRecipeUpdated(
        Model $causer,
        Recipe $recipe,
        array $changes = [],
        ?string $reason = null
    ): AuditLog {
        return AuditService::log(
            $causer,
            'update_recipe',
            $recipe,
            $reason ?? "Updated recipe: {$recipe->product_name}",
            'completed',
            null,
            [
                'recipe_name' => $recipe->product_name,
                'changes' => $changes,
                'updated_fields' => array_keys($changes),
            ]
        );
    }

    /**
     * Log recipe deletion.
     *
     * Tracks when recipes are deleted with reason.
     *
     * @param Model $causer
     * @param Recipe $recipe
     * @param string $reason Mandatory deletion reason
     *
     * @return AuditLog
     */
    public static function logRecipeDeleted(
        Model $causer,
        Recipe $recipe,
        string $reason
    ): AuditLog {
        return AuditService::log(
            $causer,
            'delete_recipe',
            $recipe,
            "Deleted recipe: {$recipe->product_name}. Reason: {$reason}",
            'completed',
            null,
            [
                'recipe_name' => $recipe->product_name,
                'deletion_reason' => $reason,
            ]
        );
    }

    /**
     * Log shift closing operation.
     *
     * Tracks comprehensive shift closing with financial adjustments.
     *
     * @param Model $causer
     * @param Model $shift
     * @param array $closingData Summary of closing operation
     *
     * @return AuditLog
     */
    public static function logShiftClosing(
        Model $causer,
        Model $shift,
        array $closingData = []
    ): AuditLog {
        return AuditService::log(
            $causer,
            'close_shift',
            $shift,
            "Shift closing completed at " . now()->format('M d, h:i A'),
            'completed',
            null,
            array_merge($closingData, [
                'shift_type' => $shift->shift_type ?? 'Unknown',
                'department_id' => $shift->department_id,
                'closed_by' => $causer->name ?? 'System',
                'closed_at' => now(),
            ])
        );
    }

    /**
     * Log raw material usage/adjustment.
     *
     * Tracks raw material consumption and adjustments in production.
     *
     * @param Model $causer
     * @param Model $materialRecord
     * @param int|float $quantityUsed
     * @param string|null $reason
     *
     * @return AuditLog
     */
    public static function logMaterialUsage(
        Model $causer,
        Model $materialRecord,
        $quantityUsed,
        ?string $reason = null
    ): AuditLog {
        return AuditService::log(
            $causer,
            'use_raw_material',
            $materialRecord,
            $reason ?? "Used {$quantityUsed} units of raw material",
            'completed',
            null,
            [
                'quantity_used' => $quantityUsed,
                'usage_reason' => $reason,
                'usage_time' => now(),
            ]
        );
    }

    /**
     * Log callback approval action.
     *
     * Tracks approval/rejection of callbacks (quality issues, returns, etc).
     *
     * @param Model $causer The approver
     * @param Model $callback The callback record
     * @param string $action 'approve' or 'reject'
     * @param string|null $notes Approval notes
     *
     * @return AuditLog
     */
    public static function logCallbackApproval(
        Model $causer,
        Model $callback,
        string $action,
        ?string $notes = null
    ): AuditLog {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        return AuditService::log(
            $causer,
            "{$action}_callback",
            $callback,
            $notes ?? "Callback {$status}",
            'completed',
            null,
            [
                'callback_action' => $action,
                'status' => $status,
                'approval_notes' => $notes,
                'approved_by' => $causer->name ?? 'System',
                'approved_at' => now(),
            ]
        );
    }

    /**
     * Log production variance or discrepancy.
     *
     * Tracks unexpected differences between expected and actual production.
     *
     * @param Model $causer
     * @param DailyProduce $dailyProduce
     * @param int|float $expected
     * @param int|float $actual
     * @param string|null $reason
     *
     * @return AuditLog
     */
    public static function logVariance(
        Model $causer,
        DailyProduce $dailyProduce,
        $expected,
        $actual,
        ?string $reason = null
    ): AuditLog {
        $variance = $actual - $expected;
        
        return AuditService::log(
            $causer,
            'record_production_variance',
            $dailyProduce,
            $reason ?? "Production variance recorded: {$variance}",
            'completed',
            null,
            [
                'expected_quantity' => $expected,
                'actual_quantity' => $actual,
                'variance' => $variance,
                'variance_percent' => $expected > 0 ? round(($variance / $expected) * 100, 2) : 0,
                'variance_reason' => $reason,
            ]
        );
    }
}
