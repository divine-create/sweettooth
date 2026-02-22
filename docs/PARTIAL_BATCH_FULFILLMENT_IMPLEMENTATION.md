# Partial Batch Fulfillment Implementation

## Business Requirement

When a production request is made for **N batches**:
- If inventory has enough for only **M batches** (where M < N):
  - Produce M batches immediately
  - Mark (N - M) batches as **pending**
  - Track pending batches for future fulfillment when inventory arrives
- Daily Produce records actual batches produced per shift
- Request page shows: requested, produced, pending quantities

---

## Current System Analysis

### Existing Models
| Model | Purpose | Key Fields |
|-------|---------|------------|
| `ProductionRequest` | Request production | `planned_production_quantity`, `recipe_id`, `shift_id` |
| `ItemRequest` | Request ingredients | `status`, `branch_id`, `department_id` |
| `ItemRequestDetail` | Per-ingredient tracking | `quantity_requested`, `quantity_approved`, `quantity_dispatched` |
| `DailyProduce` | Shift production tracking | `produced_quantity`, `requested_quantity`, `shift_id` |
| `ProductionRecord` | Individual batch | `quantity_produced`, `quantity_approved`, `batch_number` |
| `ProductDispatch` | Dispatch to sales | `quantity`, `sales_department_id`, `production_record_id` |

### Existing Logic
- `DailyProduce::calculateProducableQuantity()` - calculates max producable based on dispatched ingredients
- `ProductionBatchProgressCalculator` - tracks batch progress (produced → approved → dispatched)

---

## Implementation Plan

### Phase 1: Database Migrations

```php
// database/migrations/2026_02_16_100000_add_batch_fulfillment_columns.php

Schema::table('production_requests', function (Blueprint $table) {
    $table->integer('batches_requested')->nullable()->after('planned_production_quantity');
    $table->integer('batches_produced')->default(0)->after('batches_requested');
    $table->integer('batches_pending')->default(0)->after('batches_produced');
    $table->boolean('partial_fulfillment_allowed')->default(true)->after('batches_pending');
    $table->enum('fulfillment_status', ['pending', 'partial', 'completed', 'exceeded'])
          ->default('pending')->after('partial_fulfillment_allowed');
});

Schema::table('daily_produce', function (Blueprint $table) {
    $table->integer('batches_produced_this_shift')->default(0)->after('produced_quantity');
    $table->integer('batches_remaining')->nullable()->after('batches_produced_this_shift');
    $table->enum('fulfillment_status', ['pending', 'partial', 'completed', 'exceeded'])
          ->default('pending')->after('batches_remaining');
});
```

---

### Phase 2: ProductionRequest Model Updates

```php
// app/Models/ProductionRequest.php

class ProductionRequest extends Model
{
    // Add to $fillable
    protected $fillable = [
        // ... existing
        'batches_requested',
        'batches_produced',
        'batches_pending',
        'partial_fulfillment_allowed',
        'fulfillment_status',
    ];

    /**
     * Boot method to auto-calculate batches
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($request) {
            if ($request->recipe && !$request->batches_requested) {
                $request->batches_requested = self::calculateBatchesFromQuantity(
                    $request->planned_production_quantity,
                    $request->recipe->yield_quantity
                );
            }
            $request->updateBatchCounts();
        });
    }

    /**
     * Calculate how many batches a quantity represents
     */
    public static function calculateBatchesFromQuantity(float $quantity, float $yieldPerBatch): int
    {
        return (int) ceil($quantity / $yieldPerBatch);
    }

    /**
     * Update batch counts based on production records
     */
    public function updateBatchCounts(): void
    {
        $producedBatches = $this->productionRecords()
            ->where('quantity_approved', '>', 0)
            ->count();
        
        $this->batches_produced = $producedBatches;
        $this->batches_pending = max(0, $this->batches_requested - $producedBatches);
        
        // Update fulfillment status
        if ($producedBatches >= $this->batches_requested) {
            $this->fulfillment_status = 'completed';
        } elseif ($producedBatches > 0) {
            $this->fulfillment_status = 'partial';
        } else {
            $this->fulfillment_status = 'pending';
        }
        
        $this->save();
    }

    /**
     * Get pending batches that can be produced when inventory arrives
     */
    public function getPendingBatches(): int
    {
        return $this->batches_pending;
    }

    /**
     * Check if more batches can be produced
     */
    public function canProduceMoreBatches(int $count = 1): bool
    {
        return $this->batches_pending >= $count && 
               $this->fulfillment_status !== 'completed';
    }

    /**
     * Relationship to production records (batches)
     */
    public function productionRecords(): HasMany
    {
        return $this->hasMany(ProductionRecord::class, 'production_request_id');
    }

    /**
     * Relationship via DailyProduce
     */
    public function dailyProduces(): HasMany
    {
        return $this->hasMany(DailyProduce::class, 'production_request_id');
    }
}
```

---

### Phase 3: DailyProduce Model Updates

```php
// app/Models/DailyProduce.php

class DailyProduce extends Model
{
    // Add to $fillable
    protected $fillable = [
        // ... existing
        'batches_produced_this_shift',
        'batches_remaining',
        'fulfillment_status',
    ];

    /**
     * Record production for this shift
     */
    public function recordProduction(int $batchesProduced, float $quantityProduced): void
    {
        DB::transaction(function () use ($batchesProduced, $quantityProduced) {
            $this->batches_produced_this_shift = $batchesProduced;
            $this->produced_quantity = $quantityProduced;
            
            // Update batches remaining from production request
            if ($this->productionRequest) {
                $this->batches_remaining = max(
                    0, 
                    $this->productionRequest->batches_requested - 
                    $this->productionRequest->batches_produced - 
                    $batchesProduced
                );
                
                // Update fulfillment status
                $totalProduced = $this->productionRequest->batches_produced + $batchesProduced;
                if ($totalProduced >= $this->productionRequest->batches_requested) {
                    $this->fulfillment_status = 'completed';
                } elseif ($batchesProduced > 0) {
                    $this->fulfillment_status = 'partial';
                }
            }
            
            $this->save();
            
            // Update parent ProductionRequest
            if ($this->productionRequest) {
                $this->productionRequest->updateBatchCounts();
            }
        });
    }

    /**
     * Override calculateProducableQuantity to return batches instead of units
     */
    public function calculateProducableBatches(): array
    {
        $result = $this->calculateProducableQuantity();
        
        $recipeYield = $this->recipe?->yield_quantity ?? 1;
        $producableBatches = (int) floor($result['producable_quantity'] / $recipeYield);
        $requestedBatches = (int) ceil($result['requested_quantity'] / $recipeYield);
        
        return [
            'producable_batches' => $producableBatches,
            'requested_batches' => $requestedBatches,
            'pending_batches' => max(0, $requestedBatches - $producableBatches),
            'producable_quantity' => $result['producable_quantity'],
            'shortage' => $result['shortage'],
            'limiting_ingredient' => $result['limiting_ingredient'],
            'ingredient_analysis' => $result['ingredient_analysis'],
            'can_produce_full_request' => $producableBatches >= $requestedBatches,
        ];
    }
}
```

---

### Phase 4: Production Service for Partial Fulfillment

```php
// app/Services/ProductionFulfillmentService.php

namespace App\Services;

use App\Models\ProductionRequest;
use App\Models\DailyProduce;
use App\Models\ProductionRecord;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

class ProductionFulfillmentService
{
    /**
     * Check if partial fulfillment should be triggered
     */
    public function checkAndTriggerPartialFulfillment(ProductionRequest $request): array
    {
        $dailyProduce = DailyProduce::where('production_request_id', $request->id)
            ->where('status', '!=', 'completed')
            ->first();
            
        if (!$dailyProduce) {
            return ['action' => 'no_daily_produce_found', 'can_produce' => false];
        }
        
        $producableBatches = $dailyProduce->calculateProducableBatches();
        
        // If we can produce at least 1 batch but not all
        if ($producableBatches['producable_batches'] > 0 && 
            !$producableBatches['can_produce_full_request']) {
            
            return [
                'action' => 'partial_fulfillment_available',
                'producable_batches' => $producableBatches['producable_batches'],
                'requested_batches' => $producableBatches['requested_batches'],
                'pending_batches' => $producableBatches['pending_batches'],
                'limiting_ingredient' => $producableBatches['limiting_ingredient'],
                'can_produce' => true,
            ];
        }
        
        // If we can produce all requested batches
        if ($producableBatches['can_produce_full_request']) {
            return [
                'action' => 'full_fulfillment_available',
                'producable_batches' => $producableBatches['producable_batches'],
                'requested_batches' => $producableBatches['requested_batches'],
                'pending_batches' => 0,
                'can_produce' => true,
            ];
        }
        
        return [
            'action' => 'insufficient_inventory',
            'producable_batches' => 0,
            'can_produce' => false,
        ];
    }

    /**
     * Start production for available batches
     */
    public function startPartialProduction(
        ProductionRequest $request,
        int $batchesToProduce,
        array $productionData
    ): ProductionRecord {
        return DB::transaction(function () use ($request, $batchesToProduce, $productionData) {
            // Validate we can produce this many batches
            $fulfillmentStatus = $this->checkAndTriggerPartialFulfillment($request);
            
            if (!$fulfillmentStatus['can_produce'] || 
                $batchesToProduce > $fulfillmentStatus['producable_batches']) {
                throw new \Exception('Insufficient inventory for requested batches');
            }
            
            // Create production record (batch)
            $recipe = $request->recipe;
            $quantityToProduce = $batchesToProduce * $recipe->yield_quantity;
            
            $productionRecord = ProductionRecord::create([
                'production_request_id' => $request->id,
                'recipe_id' => $recipe->id,
                'batch_number' => $this->generateBatchNumber($request),
                'quantity_produced' => $quantityToProduce,
                'quantity_approved' => 0, // Pending quality check
                'quantity_rejected' => 0,
                'quantity_sent_out' => 0,
                'quantity_for_order' => 0,
                'quantity_remaining' => 0,
                'production_time' => $productionData['production_time'] ?? now(),
                'quality_status' => 'pending',
                'dispatch_status' => 'pending',
                'produced_by_id' => $productionData['produced_by_id'] ?? auth()->id(),
                'produced_by_type' => $productionData['produced_by_type'] ?? get_class(auth()->user()),
                'notes' => $productionData['notes'] ?? null,
            ]);
            
            // Update DailyProduce
            $dailyProduce = DailyProduce::where('production_request_id', $request->id)->first();
            if ($dailyProduce) {
                $dailyProduce->recordProduction($batchesToProduce, $quantityToProduce);
            }
            
            // Update ProductionRequest
            $request->updateBatchCounts();
            
            return $productionRecord;
        });
    }

    /**
     * Get pending production requests that can now be fulfilled
     * (Called when new inventory arrives)
     */
    public function getFulfillablePendingRequests(int $branchId, int $departmentId): array
    {
        $pendingRequests = ProductionRequest::where('fulfillment_status', 'partial')
            ->where('batches_pending', '>', 0)
            ->whereHas('recipe', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->with(['recipe', 'itemRequest.requestDetails'])
            ->get();
            
        $fulfillable = [];
        
        foreach ($pendingRequests as $request) {
            $status = $this->checkAndTriggerPartialFulfillment($request);
            
            if ($status['can_produce'] && $status['producable_batches'] > 0) {
                $fulfillable[] = [
                    'request' => $request,
                    'additional_batches_possible' => $status['producable_batches'],
                    'remaining_pending' => $request->batches_pending,
                ];
            }
        }
        
        return $fulfillable;
    }

    /**
     * Generate unique batch number
     */
    private function generateBatchNumber(ProductionRequest $request): string
    {
        $prefix = 'BATCH-' . date('Ymd');
        $lastBatch = ProductionRecord::whereDate('created_at', today())
            ->orderBy('batch_number', 'desc')
            ->first();
            
        if ($lastBatch && str_starts_with($lastBatch->batch_number, $prefix)) {
            $lastNumber = (int) substr($lastBatch->batch_number, -4);
            return $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }
        
        return $prefix . '-0001';
    }
}
```

---

### Phase 5: Livewire Component Updates

```php
// app/Livewire/BranchDashboard/Production/DailyProduce/Index.php

class Index extends Component
{
    /**
     * Show partial fulfillment modal
     */
    public function showPartialFulfillmentModal($dailyProduceId)
    {
        $dailyProduce = DailyProduce::find($dailyProduceId);
        
        if (!$dailyProduce) {
            $this->dispatch('notification', type: 'error', message: 'Daily produce not found');
            return;
        }
        
        $fulfillmentStatus = app(\App\Services\ProductionFulfillmentService::class)
            ->checkAndTriggerPartialFulfillment($dailyProduce->productionRequest);
        
        $this->dispatch('openPartialFulfillmentModal', [
            'daily_produce_id' => $dailyProduceId,
            'producable_batches' => $fulfillmentStatus['producable_batches'],
            'requested_batches' => $fulfillmentStatus['requested_batches'],
            'pending_batches' => $fulfillmentStatus['pending_batches'],
            'limiting_ingredient' => $fulfillmentStatus['limiting_ingredient'] ?? null,
        ]);
    }

    /**
     * Start production for partial batches
     */
    public function startPartialProduction(
        $dailyProduceId,
        $batchesToProduce,
        $productionTime = null,
        $notes = null
    ) {
        $dailyProduce = DailyProduce::find($dailyProduceId);
        
        if (!$dailyProduce || !$dailyProduce->productionRequest) {
            $this->dispatch('notification', type: 'error', message: 'Invalid request');
            return;
        }
        
        try {
            $service = app(\App\Services\ProductionFulfillmentService::class);
            
            $productionRecord = $service->startPartialProduction(
                $dailyProduce->productionRequest,
                $batchesToProduce,
                [
                    'production_time' => $productionTime ?? now(),
                    'produced_by_id' => auth()->id(),
                    'produced_by_type' => get_class(auth()->user()),
                    'notes' => $notes,
                ]
            );
            
            $this->dispatch('notification', 
                type: 'success', 
                message: "Started production of {$batchesToProduce} batch(es). " .
                         "{$dailyProduce->productionRequest->batches_pending} batch(es) remain pending."
            );
            
            $this->dispatch('productionStarted', $productionRecord->id);
            $this->loadDailyProduces(); // Refresh the list
            
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: $e->getMessage());
        }
    }
}
```

---

### Phase 6: Blade View Updates

```blade
{{-- resources/views/livewire/branch-dashboard/production/daily-produce/index.blade.php --}}

{{-- Add to each daily produce row --}}
@foreach($dailyProduces as $produce)
    <tr>
        {{-- Existing columns --}}
        <td class="px-4 py-3">
            {{-- Requested Batches --}}
            <span class="text-sm font-medium">
                {{ $produce['producability']['requested_batches'] ?? 0 }} batches
            </span>
        </td>
        <td class="px-4 py-3">
            {{-- Produced Batches --}}
            <span class="text-sm font-medium text-green-700">
                {{ $produce['producability']['producable_batches'] ?? 0 }} batches
            </span>
        </td>
        <td class="px-4 py-3">
            {{-- Pending Batches --}}
            @if(($produce['producability']['pending_batches'] ?? 0) > 0)
                <span class="text-sm font-medium text-yellow-700">
                    {{ $produce['producability']['pending_batches'] }} pending
                </span>
                @if($produce['producability']['producable_batches'] > 0)
                    <button 
                        wire:click="showPartialFulfillmentModal({{ $produce['id'] }})"
                        class="ml-2 px-2 py-1 text-xs bg-teal-500 text-white rounded hover:bg-teal-600"
                    >
                        Produce Available
                    </button>
                @endif
            @else
                <span class="text-sm text-gray-500">None</span>
            @endif
        </td>
        {{-- Limiting Ingredient --}}
        <td class="px-4 py-3 text-sm">
            {{ $produce['producability']['limiting_ingredient'] ?? '-' }}
        </td>
    </tr>
@endforeach

{{-- Partial Fulfillment Modal --}}
@if($showPartialFulfillmentModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md">
            <h3 class="text-lg font-bold mb-4">Start Partial Production</h3>
            
            <p class="mb-4">
                <strong>Requested:</strong> {{ $modalData['requested_batches'] }} batches<br>
                <strong>Available to produce:</strong> {{ $modalData['producable_batches'] }} batches<br>
                <strong>Will remain pending:</strong> {{ $modalData['pending_batches'] }} batches<br>
                <strong class="text-red-600">Limiting ingredient:</strong> {{ $modalData['limiting_ingredient'] }}
            </p>
            
            <div class="mb-4">
                <label class="block text-sm font-medium">Batches to produce now:</label>
                <input 
                    type="number" 
                    wire:model="partialBatchesToProduce"
                    max="{{ $modalData['producable_batches'] }}"
                    min="1"
                    class="w-full border rounded px-3 py-2"
                />
            </div>
            
            <div class="flex justify-end gap-2">
                <button 
                    wire:click="$set('showPartialFulfillmentModal', false)"
                    class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded"
                >
                    Cancel
                </button>
                <button 
                    wire:click="confirmPartialProduction"
                    class="px-4 py-2 bg-teal-500 text-white rounded hover:bg-teal-600"
                >
                    Start Production
                </button>
            </div>
        </div>
    </div>
@endif
```

---

### Phase 7: Request Page Summary

```blade
{{-- Production Request List View --}}

<table>
    <thead>
        <tr>
            <th>Request #</th>
            <th>Product</th>
            <th>Batches Requested</th>
            <th>Batches Produced</th>
            <th>Batches Pending</th>
            <th>Fulfillment Status</th>
            <th>Limiting Factor</th>
        </tr>
    </thead>
    <tbody>
        @foreach($productionRequests as $request)
            <tr>
                <td>{{ $request->id }}</td>
                <td>{{ $request->recipe->product_name ?? 'N/A' }}</td>
                <td>{{ $request->batches_requested }}</td>
                <td>{{ $request->batches_produced }}</td>
                <td>
                    @if($request->batches_pending > 0)
                        <span class="text-yellow-600">{{ $request->batches_pending }}</span>
                    @else
                        0
                    @endif
                </td>
                <td>
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'partial' => 'bg-blue-100 text-blue-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'exceeded' => 'bg-purple-100 text-purple-800',
                        ];
                    @endphp
                    <span class="px-2 py-1 rounded text-sm {{ $statusColors[$request->fulfillment_status] ?? '' }}">
                        {{ ucfirst($request->fulfillment_status) }}
                    </span>
                </td>
                <td class="text-sm">
                    @if($request->fulfillment_status === 'partial')
                        {{ $request->dailyProduces->first()?->calculateProducableQuantity()['limiting_ingredient'] ?? '-' }}
                    @else
                        -
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
```

---

## Workflow Summary

### Scenario: Request 6 batches, inventory for 4

1. **User creates ProductionRequest** for 6 batches (600 units @ 100 units/batch)
2. **ItemRequest created** for ingredients needed for 6 batches
3. **Store approves/dispatches** ingredients for only 4 batches (partial dispatch)
4. **DailyProduce::calculateProducableBatches()** returns:
   - `producable_batches`: 4
   - `requested_batches`: 6
   - `pending_batches`: 2
   - `limiting_ingredient`: "Sugar" (example)
5. **User clicks "Produce Available"** → produces 4 batches
6. **ProductionRequest updated**:
   - `batches_produced`: 4
   - `batches_pending`: 2
   - `fulfillment_status`: "partial"
7. **Later, inventory arrives** for remaining 2 batches
8. **System notifies** or user checks pending requests → produces remaining 2 batches
9. **ProductionRequest updated**:
   - `batches_produced`: 6
   - `batches_pending`: 0
   - `fulfillment_status`: "completed"

---

## Key Benefits

1. **No production delays** - produce what you can, when you can
2. **Clear visibility** - track pending batches separately
3. **Inventory flexibility** - handle partial ingredient deliveries
4. **Audit trail** - each batch tracked in `ProductionRecord`
5. **Daily produce accuracy** - record actual production per shift
