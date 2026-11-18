<?php

namespace App\Livewire\BranchDashboard\Production\StockOpening;

use App\Models\Branch;
use App\Models\DailyProduce;
use App\Models\Department;
use App\Models\Item;
use App\Models\Product;
use App\Models\ProductionStore;
use App\Models\ProductionStoreDailyStock;
use App\Models\Recipe;
use App\Models\Shift;
use App\Services\ProductionStockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $deptSlug = null;

    public ?int $departmentId = null;
    public ?Department $department = null;
    public ?ProductionStore $store = null;
    public ?Shift $currentShift = null;

    public string $activeTab = 'finished'; // finished, wip, raw
    public string $search = '';

    public array $stockItems = [];
    public array $openingCounts = [];
    public array $notes = [];

    public bool $isVerified = false;
    public ?string $verifiedAt = null;

    public function mount($deptSlug = null)
    {
        $this->deptSlug = $deptSlug ?? request()->route('deptSlug') ?? $this->deptSlug;
        $this->b_id = request()->query('b_id', $this->b_id) ?? session('selected_branch_id');

        $this->loadDepartmentAndStore();
        $this->loadActiveShift();
        $this->loadItems();
    }

    protected function loadDepartmentAndStore(): void
    {
        $branchId = $this->b_id ?? current_branch_id();

        $query = Department::query();
        if ($this->deptSlug) {
            $query->where('slug', $this->deptSlug);
        } else {
            $query->whereHas('category', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['production']));
        }

        $this->department = $query->first();

        if (!$this->department) {
            abort(404, 'Production Department not found.');
        }

        $this->departmentId = $this->department->id;
        $this->deptSlug = $this->department->slug;
        $this->store = ProductionStore::getOrCreateForDepartment($this->departmentId);
    }

    protected function loadActiveShift(): void
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $this->currentShift = Shift::where('department_id', $this->departmentId)
            ->where('shift_date', $today)
            ->where('status', 'active')
            ->whereNull('clock_out')
            ->latest('clock_in')
            ->first();

        // If no active shift specifically for this department, check user's any active shift today
        if (!$this->currentShift && $user) {
            $this->currentShift = Shift::where('employee_id', $user->id)
                ->where('shift_date', $today)
                ->where('status', 'active')
                ->whereNull('clock_out')
                ->latest('clock_in')
                ->first();
        }

        // For Super Admins, Branch Managers, or Production Managers/Staff, if no shift is clocked in yet,
        // initialize a departmental shift context so they can verify opening stock for the kitchen
        if (!$this->currentShift && $user && (
            is_super_admin()
            || can_access_all_branches()
            || (int) $user->department_id === (int) $this->departmentId
            || $user->hasRole(['Admin', 'Super Admin', 'Branch Manager', 'Production Manager', 'Operations Manager', 'Production Staff'])
        )) {
            $shiftType = now()->hour >= 14 ? 'afternoon' : 'morning';
            $shiftNumber = Shift::generateShiftNumber(
                $this->department->slug ?? 'prod',
                (string) $user->id,
                Carbon::now(),
                $shiftType
            );
            $this->currentShift = Shift::create([
                'branch_id' => $this->department->branch_id ?? current_branch_id(),
                'department_id' => $this->departmentId,
                'employee_id' => $user->id,
                'shift_number' => $shiftNumber,
                'shift_date' => $today,
                'shift_type' => $shiftType,
                'clock_in' => Carbon::now(),
                'status' => 'active',
                'workflow_state' => 'stock_opening',
                'notes' => 'Department shift initialized by ' . $user->name,
            ]);
        }

        if ($this->currentShift && $this->currentShift->stock_verified_at) {
            $this->isVerified = true;
            $this->verifiedAt = $this->currentShift->stock_verified_at->format('M d, Y h:i A');
        }
    }

    public function loadItems(): void
    {
        if (!$this->store || !$this->department) {
            return;
        }

        $stockService = app(ProductionStockService::class);

        // Ensure all items are seeded in production_store_daily_stocks
        if ($this->currentShift) {
            $stockService->initializeShiftDailyStock($this->store, $this->department, $this->currentShift);
        } else {
            $stockService->ensureBaselineDailyStock($this->store, $this->department);
        }

        $categories = $stockService->getTrackableItemsForStore($this->store, $this->department);

        $today = Carbon::today()->toDateString();
        $shiftId = $this->currentShift?->id;

        $dailyRecords = ProductionStoreDailyStock::where('store_id', $this->store->id)
            ->where('stock_date', $today)
            ->when($shiftId, fn ($q) => $q->where(fn ($sub) => $sub->where('shift_id', $shiftId)->orWhereNull('shift_id')))
            ->get()
            ->keyBy('item_id');

        $this->stockItems = [
            'finished' => [],
            'wip' => [],
            'raw' => [],
        ];

        foreach (['finished', 'wip', 'raw'] as $cat) {
            foreach ($categories[$cat] as $entity) {
                $itemId = (string) $entity->id;
                $daily = $dailyRecords->get($itemId);

                $expected = $daily ? (float) $daily->opening_quantity : 0.0;
                $actual = ($daily && $daily->workflow_step !== 'opening_pending')
                    ? (float) $daily->opening_quantity
                    : $expected;

                $this->stockItems[$cat][$itemId] = [
                    'id' => $itemId,
                    'daily_id' => $daily?->id,
                    'name' => $entity->name,
                    'sku' => $entity->sku ?? 'N/A',
                    'uom' => $entity->unitOfMeasure?->symbol ?? ($cat === 'raw' ? ($entity->uom ?? 'units') : 'pcs'),
                    'expected' => $expected,
                ];

                if (!isset($this->openingCounts[$itemId])) {
                    $this->openingCounts[$itemId] = $actual;
                }
                if (!isset($this->notes[$itemId])) {
                    $this->notes[$itemId] = $daily?->notes ?? '';
                }
            }
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function matchAllExpected(): void
    {
        $currentItems = $this->stockItems[$this->activeTab] ?? [];
        foreach ($currentItems as $itemId => $item) {
            $this->openingCounts[$itemId] = $item['expected'];
        }
        $this->toast()->info('Populated all items in this tab with expected counts.')->send();
    }

    public function saveOpeningStock(): void
    {
        if (!$this->currentShift) {
            $this->loadActiveShift();
        }

        if (!$this->currentShift) {
            $this->toast()->error('No active shift found. Please clock in first.')->send();
            return;
        }

        // Normalize blank/null counts to 0.0 so staff are not blocked by empty inputs
        foreach ($this->openingCounts as $itemId => $count) {
            if ($count === '' || $count === null) {
                $this->openingCounts[$itemId] = 0.0;
            }
        }

        $this->validate([
            'openingCounts.*' => 'required|numeric|min:0',
            'notes.*' => 'nullable|string|max:500',
        ], [
            'openingCounts.*.required' => 'Please enter a count for each item.',
            'openingCounts.*.numeric' => 'Count must be a valid number.',
            'openingCounts.*.min' => 'Count cannot be negative.',
        ]);

        DB::beginTransaction();
        try {
            $today = Carbon::today()->toDateString();
            $now = Carbon::now();
            $userId = Auth::id();

            // Preload recipes for syncing finished goods
            $recipes = Recipe::where('department_id', $this->departmentId)
                ->orWhereNull('department_id')
                ->get();

            foreach ($this->openingCounts as $itemId => $count) {
                $actualCount = (float) $count;
                $itemNote = $this->notes[$itemId] ?? null;

                // Match existing daily stock record (including those seeded with shift_id = null)
                $dailyStock = ProductionStoreDailyStock::where('store_id', $this->store->id)
                    ->where('item_id', (string) $itemId)
                    ->whereDate('stock_date', $today)
                    ->where(function ($q) {
                        $q->where('shift_id', $this->currentShift->id)
                          ->orWhereNull('shift_id');
                    })
                    ->first();

                if (!$dailyStock) {
                    $dailyStock = new ProductionStoreDailyStock([
                        'store_id' => $this->store->id,
                        'item_id' => (string) $itemId,
                        'stock_date' => $today,
                    ]);
                }

                $expected = (float) ($dailyStock->opening_quantity ?? 0.0);
                $dailyStock->shift_id = $this->currentShift->id;
                $dailyStock->opening_quantity = $actualCount;
                $dailyStock->variance = $actualCount - $expected;
                $dailyStock->workflow_step = 'opening_verified';
                $dailyStock->is_workflow_verified = true;
                $dailyStock->verified_at = $now;
                $dailyStock->verified_by = $userId;
                $dailyStock->notes = $itemNote;
                $dailyStock->save();

                // If this is a Product (Finished Good or WIP), sync to DailyProduce
                $recipe = $recipes->firstWhere('product_id', $itemId);
                if (!$recipe) {
                    $product = Product::find($itemId);
                    if ($product) {
                        $recipe = $recipes->firstWhere('product_name', $product->name);
                    }
                }

                if ($recipe) {
                    $dp = DailyProduce::where('recipe_id', $recipe->id)
                        ->whereDate('produce_date', $today)
                        ->where(function ($q) {
                            $q->where('shift_id', $this->currentShift->id)
                              ->orWhereNull('shift_id');
                        })
                        ->first();

                    if ($dp) {
                        $dp->shift_id = $this->currentShift->id;
                        $dp->shift_type = $this->currentShift->shift_type ?? $dp->shift_type ?? 'morning';
                        $dp->opening_quantity = $actualCount;
                        $dp->updateCalculations();
                        $dp->save();
                    } else {
                        DailyProduce::create([
                            'shift_id' => $this->currentShift->id,
                            'recipe_id' => $recipe->id,
                            'produce_date' => $today,
                            'shift_type' => $this->currentShift->shift_type ?? 'morning',
                            'opening_quantity' => $actualCount,
                            'requested_quantity' => 0,
                            'produced_quantity' => 0,
                            'sent_out_quantity' => 0,
                            'order_quantity' => 0,
                            'closing_quantity' => $actualCount,
                            'status' => 'in_progress',
                            'fulfillment_status' => 'pending',
                        ]);
                    }
                }
            }

            // Unlock the shift using valid ENUM ('pos' for active operational state)
            $this->currentShift->update([
                'stock_verified_at' => $now,
                'workflow_state' => 'pos',
            ]);

            DB::commit();

            $this->isVerified = true;
            $this->verifiedAt = $now->format('M d, Y h:i A');

            $this->toast()->success('Shift Opening Stock verified! Production operations are unlocked.')->send();

            // Refresh records
            $this->loadItems();

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error saving production opening stock: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->toast()->error('Error saving opening stock: '.$e->getMessage())->send();
        }
    }

    public function render()
    {
        $filteredItems = [];
        $search = trim($this->search);

        $currentTabItems = $this->stockItems[$this->activeTab] ?? [];

        foreach ($currentTabItems as $itemId => $item) {
            if ($search === '' || stripos($item['name'], $search) !== false || stripos($item['sku'], $search) !== false) {
                $filteredItems[$itemId] = $item;
            }
        }

        return view('livewire.branch-dashboard.production.stock-opening.index', [
            'filteredItems' => $filteredItems,
            'finishedCount' => count($this->stockItems['finished'] ?? []),
            'wipCount' => count($this->stockItems['wip'] ?? []),
            'rawCount' => count($this->stockItems['raw'] ?? []),
        ]);
    }
}
