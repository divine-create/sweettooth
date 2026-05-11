<?php

namespace Tests\Feature;

use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentCategory;
use App\Models\Employee;
use App\Models\GlEntry;
use App\Models\GlAccount;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesShift;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\TrialBalanceService;
use Database\Seeders\GlAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;
    protected Department $department;
    protected AccountingPeriod $period;
    protected User $user;
    protected TrialBalanceService $trialBalanceService;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed GL Accounts
        (new GlAccountSeeder())->run();

        // 2. Setup environment
        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB01',
            'location' => 'Test Location',
            'email' => 'branch@example.com',
        ]);

        $category = DepartmentCategory::create([
            'name' => 'Sales',
            'description' => 'Sales Department',
        ]);

        $this->department = Department::create([
            'branch_id' => $this->branch->id,
            'category_id' => $category->id,
            'name' => 'Sales Department',
        ]);

        $this->period = AccountingPeriod::create([
            'year' => now()->year,
            'month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'open',
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'password',
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user);
        
        $this->trialBalanceService = app(TrialBalanceService::class);
    }

    /**
     * Test Case 1: Comprehensive Sale Flow
     */
    public function test_sales_cycle_integrity(): void
    {
        // Setup Product
        $productType = ProductType::create(['department_id' => $this->department->id, 'name' => 'Items', 'code' => 'ITM']);
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 1000,
            'cost' => 600,
            'product_type_id' => $productType->id,
            'sales_department_id' => $this->department->id,
            'branch_id' => $this->branch->id,
        ]);

        // Create Sale
        $sale = Sale::create([
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'sale_number' => 'SAL-001',
            'sale_time' => now(),
            'subtotal' => 1000,
            'total' => 1000,
            'status' => 'pending',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'unit_cost' => 600,
            'line_cost' => 600,
            'total' => 1000,
        ]);

        // Complete Sale -> Should trigger GL
        $sale->update(['status' => 'completed']);
        $sale->refresh();

        $this->assertEquals('posted', $sale->gl_posting_status);
        
        // Verify Trial Balance is balanced after Sale
        $tb = $this->trialBalanceService->getTrialBalance($this->period->id);
        $this->assertTrue($tb['balanced'], "Trial Balance unbalanced after Sale: " . ($tb['difference'] ?? 0));

        // Create Payment
        $payment = Payment::create([
            'sale_id' => $sale->id,
            'amount' => 1000,
            'payment_method' => 'cash',
            'payment_time' => now(),
            'status' => 'pending',
        ]);

        $payment->update(['status' => 'completed']);
        $payment->refresh();

        $this->assertEquals('posted', $payment->gl_posting_status);

        // Verify Trial Balance is balanced after Payment
        $tb = $this->trialBalanceService->getTrialBalanceFresh($this->period->id);
        $this->assertTrue($tb['balanced'], "Trial Balance unbalanced after Payment: " . ($tb['difference'] ?? 0));
    }

    /**
     * Test Case 2: Purchase Cycle Integrity
     */
    public function test_purchase_cycle_integrity(): void
    {
        $purchase = Purchase::create([
            'branch_id' => $this->branch->id,
            'reference_number' => 'PUR-001',
            'total_fob_ngn' => 5000,
            'status' => 'pending',
        ]);

        // Complete Purchase -> Should trigger GL
        $purchase->update(['status' => 'approved']);
        $purchase->refresh();

        // Note: Check if PurchaseObserver handles 'approved' status
        $this->assertEquals('posted', $purchase->gl_posting_status);

        $tb = $this->trialBalanceService->getTrialBalanceFresh($this->period->id);
        $this->assertTrue($tb['balanced'], "Trial Balance unbalanced after Purchase: " . ($tb['difference'] ?? 0));
    }

    /**
     * Test Case 3: Inventory Adjustment (Damage)
     */
    public function test_inventory_adjustment_integrity(): void
    {
        $productType = ProductType::create(['department_id' => $this->department->id, 'name' => 'Items', 'code' => 'ITM']);
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 1000,
            'cost' => 600,
            'product_type_id' => $productType->id,
            'sales_department_id' => $this->department->id,
            'branch_id' => $this->branch->id,
        ]);

        $movement = StockMovement::create([
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'type' => 'damaged',
            'adjustment_reason' => 'damage',
            'unit_cost' => 600,
            'cost_impact' => 3000,
            'movement_date' => now(),
        ]);

        // Observer should trigger posting
        $movement->refresh();
        $this->assertEquals('posted', $movement->gl_posting_status);

        $tb = $this->trialBalanceService->getTrialBalanceFresh($this->period->id);
        $this->assertTrue($tb['balanced'], "Trial Balance unbalanced after Adjustment: " . ($tb['difference'] ?? 0));
    }

    /**
     * Test Case 4: Idempotency Protection
     */
    public function test_posting_is_idempotent(): void
    {
        $sale = Sale::create([
            'branch_id' => $this->branch->id,
            'sale_number' => 'SAL-IDEM-001',
            'total' => 1000,
            'status' => 'completed',
        ]);

        $initialEntriesCount = GlEntry::where('reference_type', Sale::class)->where('reference_id', $sale->id)->count();
        
        // Attempt to post again manually via service
        $service = app(\App\Services\GlPostingService::class);
        $service->postSaleTransaction($sale);

        $finalEntriesCount = GlEntry::where('reference_type', Sale::class)->where('reference_id', $sale->id)->count();
        $this->assertEquals($initialEntriesCount, $finalEntriesCount, "Duplicate GL entries created!");
    }
}
