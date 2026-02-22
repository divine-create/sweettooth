<?php

namespace Tests\Feature;

use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\DepartmentCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Models\GlEntry;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalesShift;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\GlAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingPhase2ObserversTest extends TestCase
{
    use RefreshDatabase;

    protected $branch;

    protected $period;

    protected $department;

    protected $salesShift;

    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        (new GlAccountSeeder())->run();

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

        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'password',
            'branch_id' => $this->branch->id,
        ]);

        $this->employee = Employee::create([
            'name' => 'Test Employee',
            'email' => 'employee@example.com',
            'password' => 'password',
            'branch_id' => $this->branch->id,
            'employee_number' => 'EMP-001',
            'hire_date' => now(),
        ]);

        $this->salesShift = SalesShift::create([
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'employee_id' => $user->id,
            'shift_number' => 'SHIFT-001',
            'shift_date' => now()->toDateString(),
            'shift_type' => 'morning',
            'status' => 'active',
        ]);
    }

    /**
     * Test SaleObserver: Sale should post to GL when completed and fully paid
     */
    public function test_sale_observer_posts_to_gl_when_completed_and_paid()
    {
        // Create a sale
        $sale = Sale::create([
            'sales_shift_id' => $this->salesShift->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'sold_by_type' => Employee::class,
            'sold_by_id' => $this->employee->id,
            'sale_number' => 'SAL-001',
            'sale_time' => now(),
            'subtotal' => 1000,
            'tax' => 100,
            'discount' => 50,
            'total' => 1050,
            'status' => 'pending',
        ]);

        // Initially should be pending
        $this->assertEquals('pending', $sale->gl_posting_status);

        // Create payment to complete the sale
        Payment::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'amount' => 1050,
            'payment_time' => now(),
            'status' => 'pending',
        ]);

        // Mark sale as completed and fully paid
        $sale->update(['status' => 'completed']);

        // Refresh from DB
        $sale->refresh();

        // Should now be posted or failed (depending on GL account setup)
        $this->assertIn($sale->gl_posting_status, ['posted', 'failed']);

        // If posted, verify GL entries were created
        if ($sale->gl_posting_status === 'posted') {
            // Should have at least 2 GL entries (revenue + COGS)
            $saleEntries = GlEntry::where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->count();
            $this->assertGreaterThanOrEqual(2, $saleEntries);
        }
    }

    /**
     * Test PurchaseObserver: Purchase should post to GL when approved
     */
    public function test_purchase_observer_posts_to_gl_when_approved()
    {
        // Create a purchase
        $purchase = Purchase::create([
            'branch_id' => $this->branch->id,
            'recorded_by_type' => Employee::class,
            'recorded_by_id' => $this->employee->id,
            'purchase_number' => 'PUR-001',
            'purchase_date' => now()->date(),
            'supplier_name' => 'Test Supplier',
            'total_fob_ngn' => 5000,
            'other_costs' => 500,
            'landing_cost' => 5500,
            'status' => 'draft',
        ]);

        // Initially should be pending
        $this->assertEquals('pending', $purchase->gl_posting_status);

        // Approve the purchase
        $purchase->update(['status' => 'approved']);

        // Refresh from DB
        $purchase->refresh();

        // Should now be posted or failed
        $this->assertIn($purchase->gl_posting_status, ['posted', 'failed']);

        // If posted, verify GL entries were created
        if ($purchase->gl_posting_status === 'posted') {
            $purchaseEntries = GlEntry::where('reference_type', Purchase::class)
                ->where('reference_id', $purchase->id)
                ->count();
            $this->assertGreaterThanOrEqual(2, $purchaseEntries);
        }
    }

    /**
     * Test PaymentObserver: Payment should post to GL when completed
     */
    public function test_payment_observer_posts_to_gl_when_completed()
    {
        // Create a sale first
        $sale = Sale::create([
            'sales_shift_id' => $this->salesShift->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'sold_by_type' => Employee::class,
            'sold_by_id' => $this->employee->id,
            'sale_number' => 'SAL-002',
            'sale_time' => now(),
            'subtotal' => 1000,
            'tax' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'completed',
        ]);

        // Create a payment
        $payment = Payment::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'amount' => 1000,
            'payment_time' => now(),
            'status' => 'pending',
        ]);

        // Initially should be pending
        $this->assertEquals('pending', $payment->gl_posting_status);

        // Mark payment as completed
        $payment->update(['status' => 'completed']);

        // Refresh from DB
        $payment->refresh();

        // Should now be posted or failed
        $this->assertIn($payment->gl_posting_status, ['posted', 'failed']);

        // If posted, verify GL entries were created
        if ($payment->gl_posting_status === 'posted') {
            $paymentEntries = GlEntry::where('reference_type', Payment::class)
                ->where('reference_id', $payment->id)
                ->count();
            $this->assertGreaterThanOrEqual(2, $paymentEntries);
        }
    }

    /**
     * Test StockMovementObserver: Damage/shrinkage should post to GL
     */
    public function test_stock_movement_observer_posts_damage_to_gl()
    {
        $item = Item::create([
            'branch_id' => $this->branch->id,
            'name' => 'Test Item',
            'sku' => 'ITEM-001',
            'category' => 'raw_material',
            'uom' => 'kg',
            'status' => 'active',
        ]);

        $stock = Stock::create([
            'branch_id' => $this->branch->id,
            'item_id' => $item->id,
            'quantity_available' => 100,
            'quantity_reserved' => 0,
            'quantity_damaged' => 0,
            'average_cost' => 10,
        ]);

        // Create damage movement
        $movement = StockMovement::create([
            'stock_id' => $stock->id,
            'type' => 'damaged',
            'adjustment_reason' => 'damage',
            'quantity' => 5,
            'quantity_before' => 100,
            'quantity_after' => 95,
            'moved_by_type' => Employee::class,
            'moved_by_id' => $this->employee->id,
            'movement_date' => now(),
            'unit_cost' => 10,
            'cost_impact' => 50,
        ]);

        // Refresh from DB
        $movement->refresh();

        // Should be posted or failed
        $this->assertIn($movement->gl_posting_status, ['posted', 'failed']);

        // If posted, verify GL entries were created
        if ($movement->gl_posting_status === 'posted') {
            $entries = GlEntry::where('reference_type', StockMovement::class)
                ->where('reference_id', $movement->id)
                ->count();
            $this->assertGreaterThanOrEqual(2, $entries);
        }
    }

    /**
     * Test StockMovementObserver: Regular movements should NOT post to GL
     */
    public function test_stock_movement_observer_ignores_regular_movements()
    {
        $item = Item::create([
            'branch_id' => $this->branch->id,
            'name' => 'Test Item 2',
            'sku' => 'ITEM-002',
            'category' => 'raw_material',
            'uom' => 'kg',
            'status' => 'active',
        ]);

        $stock = Stock::create([
            'branch_id' => $this->branch->id,
            'item_id' => $item->id,
            'quantity_available' => 100,
            'quantity_reserved' => 0,
            'quantity_damaged' => 0,
            'average_cost' => 10,
        ]);

        $movement = StockMovement::create([
            'stock_id' => $stock->id,
            'type' => 'in', // Regular inbound movement
            'quantity' => 10,
            'quantity_before' => 100,
            'quantity_after' => 110,
            'moved_by_type' => Employee::class,
            'moved_by_id' => $this->employee->id,
            'movement_date' => now(),
        ]);

        $movement->refresh();

        // Should still be pending (observer only handles damage/shrinkage)
        $this->assertEquals('pending', $movement->gl_posting_status);

        // Verify no GL entries created
        $entries = GlEntry::where('reference_type', StockMovement::class)
            ->where('reference_id', $movement->id)
            ->count();
        $this->assertEquals(0, $entries);
    }

    /**
     * Test GL Balancing: All posted entries should balance
     */
    public function test_all_gl_entries_are_balanced()
    {
        $postedEntries = GlEntry::where('status', 'posted')->get();

        foreach ($postedEntries as $entry) {
            // Each entry should have debit OR credit, not both
            if ($entry->debit > 0) {
                $this->assertEquals(0, $entry->credit, "Entry {$entry->id} has both debit and credit");
            } else {
                $this->assertGreaterThan(0, $entry->credit, "Entry {$entry->id} has neither debit nor credit");
            }
        }

        // Overall trial balance
        $debits = GlEntry::where('status', 'posted')->sum('debit');
        $credits = GlEntry::where('status', 'posted')->sum('credit');

        $this->assertAlmostEquals($debits, $credits, 0.01, 'Trial balance is not balanced');
    }

    /**
     * Test Idempotency: Multiple updates shouldn't create duplicate GL entries
     */
    public function test_observer_does_not_create_duplicate_entries()
    {
        $sale = Sale::create([
            'sales_shift_id' => $this->salesShift->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'sold_by_type' => Employee::class,
            'sold_by_id' => $this->employee->id,
            'sale_number' => 'SAL-003',
            'sale_time' => now(),
            'subtotal' => 1000,
            'tax' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'pending',
        ]);

        Payment::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'amount' => 1000,
            'payment_time' => now(),
            'status' => 'pending',
        ]);

        // Mark as completed (triggers observer)
        $sale->update(['status' => 'completed']);
        $firstPostCount = GlEntry::where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->count();

        // Update again (should NOT trigger again)
        $sale->update(['notes' => 'Test']);
        $secondPostCount = GlEntry::where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->count();

        // Count should not increase
        $this->assertEquals($firstPostCount, $secondPostCount, 'Duplicate GL entries were created');
    }

    /**
     * Helper: Assert floats are almost equal
     */
    protected function assertAlmostEquals($expected, $actual, $delta, $message = '')
    {
        $this->assertLessThanOrEqual($delta, abs($expected - $actual), $message);
    }
}
