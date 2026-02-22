<?php

namespace App\Livewire\BranchDashboard\Inventory\ShiftClosing;

use App\Livewire\BaseComponent;
use App\Models\Branch;
use App\Models\Shift;
use App\Models\Stock;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?string $branchId = null;

    public string $branchName = '';

    public ?string $currentShiftId = null;

    public $shiftDate;

    public $shiftType = 'morning';

    // Closing data
    public array $closingStocks = [];

    public bool $isVerified = false;

    public $notes = '';

    protected function getModelClass(): string
    {
        return Shift::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    public function mount()
    {
        $this->mountBase();
        $this->branchId = request('b_id');
        if ($this->branchId) {
            $branch = Branch::find($this->branchId);
            $this->branchName = $branch?->name ?? 'Unknown Branch';
        }

        $this->shiftDate = Carbon::today()->format('Y-m-d');
        $this->loadCurrentShift();
        $this->loadClosingStockData();
    }

    protected function loadCurrentShift()
    {
        $employee = auth()->user();

        // Get active shift for today (Inventory department)
        $activeShift = Shift::where('employee_id', $employee->id)
            ->where('shift_date', Carbon::today())
            ->where('status', 'active')
            ->whereHas('department', function ($q) {
                $q->where('name', 'Inventory'); // Inventory is not department-based
            })
            ->first();

        if ($activeShift) {
            $this->currentShiftId = $activeShift->id;
            $this->shiftType = $activeShift->shift_type ?? 'morning';
        }
    }

    /**
     * Load stock closing data
     *
     * TODO: Enhance this method to:
     * - Load all raw material stocks from the Stock table
     * - Calculate variance between system count and physical count
     * - Show opening quantity, items used/dispatched during shift, expected closing
     * - Allow manual entry of actual closing count
     * - Track reasons for variances
     */
    public function loadClosingStockData()
    {
        if (! $this->currentShiftId) {
            $this->closingStocks = [];

            return;
        }

        // BASIC IMPLEMENTATION: Get all stocks
        $stocks = Stock::with('item')
            ->where('branch_id', $this->branchId)
            ->get();

        $closingStocks = [];
        foreach ($stocks as $stock) {
            $closingStocks[] = [
                'item_id' => $stock->item_id,
                'item_name' => $stock->item->name ?? 'N/A',
                'opening_quantity' => $stock->quantity ?? 0,
                'expected_closing' => $stock->quantity ?? 0, // TODO: Calculate based on movements
                'actual_closing' => $stock->quantity ?? 0,
                'variance' => 0,
                'notes' => '',
            ];
        }

        $this->closingStocks = $closingStocks;
    }

    /**
     * Save shift closing
     *
     * TODO: Implement full closing logic:
     * - Update Stock quantities with actual closing counts
     * - Create StockMovement records for variances
     * - Close the active shift (update status to 'closed')
     * - Generate shift closure report
     * - Send notifications to supervisor
     * - Lock data to prevent further modifications
     */
     public function saveShiftClosing()
    {
        if (!$this->currentShiftId) {
            $this->toast()->error('No active shift found.')->send();
            return;
        }

        DB::beginTransaction();
        try {
            $shift = Shift::find($this->currentShiftId);
            if (!$shift) {
                throw new \Exception('Shift not found');
            }

            // 1. Update all Stock records with actual_closing values
            $totalVariance = $this->updateStockRecords();

            // 2. Create StockMovement records for each variance
            $this->createStockMovements($shift);

            // 3. Calculate total variance value and update shift
            $shiftSummary = $this->generateShiftClosureSummary($totalVariance);

            // 4. Update shift status and store summary
            $shift->status = 'closed';
            $shift->clock_out = now();
            $shift->variance = $totalVariance;
            $shift->notes = json_encode(array_merge(
                ['user_notes' => $this->notes],
                $shiftSummary
            ));

            // Update metadata
            $metadata = $shift->metadata ?? [];
            $metadata['shift_closing_completed'] = true;
            $metadata['shift_closed_at'] = now()->toIso8601String();
            $metadata['closed_by'] = auth()->id() ?? null;
            $shift->metadata = $metadata;

            $shift->save();

            // 5. Send notification to inventory manager
            $this->notifyInventoryManager($shift, $totalVariance);

            DB::commit();
            $this->toast()->success('Shift closed successfully! Total variance: ' . number_format($totalVariance, 2))->send();
            $this->isVerified = true;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->toast()->error('Error closing shift: '.$e->getMessage())->send();
        }
    }

    /**
     * Update all stock records with actual closing values and calculate variances
     */
    protected function updateStockRecords(): float
    {
        $totalVariance = 0.0;

        foreach ($this->closingStocks as $stockData) {
            $stock = Stock::where('branch_id', $this->branchId)
                ->where('item_id', $stockData['item_id'])
                ->first();

            if ($stock) {
                $expectedClosing = $stockData['expected_closing'] ?? $stock->quantity;
                $actualClosing = $stockData['actual_closing'];
                $variance = $actualClosing - $expectedClosing;

                // Update stock quantity
                $stock->quantity = $actualClosing;
                $stock->last_updated = now();
                $stock->save();

                $totalVariance += $variance;
            }
        }

        return $totalVariance;
    }

    /**
     * Create StockMovement records for each variance
     */
    protected function createStockMovements(Shift $shift): void
    {
        foreach ($this->closingStocks as $stockData) {
            $variance = ($stockData['actual_closing'] ?? 0) - ($stockData['expected_closing'] ?? 0);

            // Only create movement if there's a variance
            if (abs($variance) > 0.01) {
                StockMovement::create([
                    'branch_id' => $this->branchId,
                    'stock_id' => $stockData['stock_id'] ?? null,
                    'item_id' => $stockData['item_id'],
                    'type' => $variance < 0 ? 'adjustment_negative' : 'adjustment_positive',
                    'quantity' => abs($variance),
                    'reference_type' => Shift::class,
                    'reference_id' => $shift->id,
                    'mover_id' => auth()->id() ?? null,
                    'notes' => 'Shift closing variance - ' . $this->notes,
                    'created_at' => now(),
                ]);
            }
        }
    }

    /**
     * Generate shift closure summary data
     */
    protected function generateShiftClosureSummary(float $totalVariance): array
    {
        $totalItems = count($this->closingStocks);
        $itemsWithVariance = collect($this->closingStocks)->filter(function ($item) {
            $variance = ($item['actual_closing'] ?? 0) - ($item['expected_closing'] ?? 0);
            return abs($variance) > 0.01;
        })->count();

        return [
            'shift_closure_summary' => [
                'total_items_counted' => $totalItems,
                'items_with_variance' => $itemsWithVariance,
                'total_variance' => $totalVariance,
                'variance_percentage' => $totalItems > 0 ? ($itemsWithVariance / $totalItems) * 100 : 0,
                'closed_at' => now()->toIso8601String(),
                'closed_by' => auth()->id(),
            ],
            'stock_details' => $this->closingStocks,
        ];
    }

    /**
     * Send notification to inventory manager about shift closing
     */
    protected function notifyInventoryManager(Shift $shift, float $totalVariance): void
    {
        // Find inventory managers for this branch
        $inventoryManagers = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('name', 'inventory-manager');
        })->where('branch_id', $this->branchId)->get();

        if ($inventoryManagers->isNotEmpty()) {
            // Create a simple notification - in production you'd create a proper notification class
            $message = "Shift {$shift->shift_number} has been closed with total variance: " . number_format($totalVariance, 2);

            // For now, we'll just log it. In production, send actual notifications
            \Log::info('Shift closing notification', [
                'shift_id' => $shift->id,
                'variance' => $totalVariance,
                'managers_notified' => $inventoryManagers->pluck('id')->toArray(),
                'message' => $message,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.inventory.shift-closing.index');
    }
}
