<?php

namespace App\Services;

use App\Models\ProductStock;
use App\Models\Product;
use App\Models\Department;
use App\Models\Shift;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SalesStockVerificationService
{
    /**
     * Check if stock is verified for a department/shift combination
     */
    public function isStockVerified(string|int $departmentId, string|int $shiftId, string|int|null $employeeId = null): bool
    {
        $shift = Shift::find($shiftId);
        if (!$shift) {
            return false;
        }

        // Get all products for this department
        $productIds = $this->getDepartmentProductIds($departmentId, $shift->branch_id);
        if ($productIds->isEmpty()) {
            return true; // No products to verify
        }

        // Check if stock records exist for today's shift
        $verifiedCount = ProductStock::whereDate('stock_date', $shift->shift_date)
            ->where('shift_type', $shift->shift_type)
            ->whereIn('product_id', $productIds)
            ->whereNotNull('opening_quantity') // Must have opening quantity set
            ->count();

        return $verifiedCount >= $productIds->count();
    }

    /**
     * Get verification status details
     */
    public function getVerificationStatus(string|int $departmentId, string|int $shiftId): array
    {
        $shift = Shift::find($shiftId);
        if (!$shift) {
            return ['verified' => false, 'message' => 'Invalid shift'];
        }

        $productIds = $this->getDepartmentProductIds($departmentId, $shift->branch_id);
        $totalProducts = $productIds->count();

        if ($totalProducts === 0) {
            return ['verified' => true, 'message' => 'No products to verify'];
        }

        $verifiedCount = ProductStock::whereDate('stock_date', $shift->shift_date)
            ->where('shift_type', $shift->shift_type)
            ->whereIn('product_id', $productIds)
            ->whereNotNull('opening_quantity')
            ->count();

        return [
            'verified' => $verifiedCount >= $totalProducts,
            'total_products' => $totalProducts,
            'verified_products' => $verifiedCount,
            'remaining_products' => $totalProducts - $verifiedCount,
            'percentage_complete' => $totalProducts > 0 ? round(($verifiedCount / $totalProducts) * 100, 2) : 100
        ];
    }

    /**
     * Mark stock verification as completed for an employee
     */
    public function completeStockVerification(string|int $employeeId, string|int $shiftId, string|int $departmentId): bool
    {
        // Update shift record to track who completed verification
        $shift = Shift::find($shiftId);
        if (!$shift) {
            return false;
        }

        // Store verification completion in shift metadata
        $metadata = $shift->metadata ?? [];
        $metadata['stock_verification'] = [
            'completed_by' => $employeeId,
            'completed_at' => now()->toIso8601String(),
            'department_id' => $departmentId
        ];

        $shift->metadata = $metadata;

        // Also set stock_verified_at if column exists
        if (in_array('stock_verified_at', $shift->getFillable())) {
            $shift->stock_verified_at = now();
        }

        return $shift->save();
    }

    /**
     * Check if employee can access POS (stock verification completed)
     */
    public function canAccessPos(string|int $employeeId, string|int $shiftId): bool
    {
        $shift = Shift::find($shiftId);
        if (!$shift) {
            return false;
        }

        $employee = Employee::find($employeeId);
        if (!$employee || !$employee->department_id) {
            return false;
        }

        return $this->isStockVerified($employee->department_id, $shiftId);
    }

    /**
     * Check stock verification using department and shift info directly
     * (without requiring employee context)
     */
    public function checkStockVerificationForShift(string|int $departmentId, string $shiftDate, string $shiftType, string|int|null $branchId = null): bool
    {
        $productIds = $this->getDepartmentProductIds($departmentId, $branchId);

        if ($productIds->isEmpty()) {
            return true; // No products to verify
        }

        $verifiedCount = ProductStock::whereDate('stock_date', $shiftDate)
            ->where('shift_type', $shiftType)
            ->whereIn('product_id', $productIds)
            ->whereNotNull('opening_quantity')
            ->count();

        return $verifiedCount > 0;
    }

    /**
     * Get department product IDs
     */
    protected function getDepartmentProductIds(string|int $departmentId, string|int|null $branchId = null): Collection
    {
        $branchId = $branchId ?? current_branch_id();

        return Product::query()
            ->where('sales_department_id', $departmentId)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')
                  ->orWhere('branch_id', $branchId);
            })
            ->active()
            ->pluck('id');
    }

    /**
     * Get products that still need verification
     */
    public function getUnverifiedProducts(string|int $departmentId, string|int $shiftId): Collection
    {
        $shift = Shift::find($shiftId);
        if (!$shift) {
            return collect();
        }

        $productIds = $this->getDepartmentProductIds($departmentId, $shift->branch_id);

        $verifiedProductIds = ProductStock::whereDate('stock_date', $shift->shift_date)
            ->where('shift_type', $shift->shift_type)
            ->whereIn('product_id', $productIds)
            ->whereNotNull('opening_quantity')
            ->pluck('product_id');

        return Product::whereIn('id', $productIds)
            ->whereNotIn('id', $verifiedProductIds)
            ->get();
    }
}
