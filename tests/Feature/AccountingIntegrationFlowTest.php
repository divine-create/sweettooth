<?php

namespace Tests\Feature;

use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentCategory;
use App\Models\Employee;
use App\Models\GlEntry;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesShift;
use App\Models\User;
use Database\Seeders\GlAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingIntegrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;
    protected Department $department;
    protected AccountingPeriod $period;
    protected SalesShift $salesShift;
    protected Employee $employee;
    protected Product $product;
    protected User $user;

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

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'password',
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user);

        $this->employee = Employee::create([
            'name' => 'Test Employee',
            'email' => 'employee@example.com',
            'password' => 'password',
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'employee_number' => 'EMP-001',
            'hire_date' => now(),
        ]);

        $this->salesShift = SalesShift::create([
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'employee_id' => $this->user->id,
            'shift_number' => 'SHIFT-001',
            'shift_date' => now()->toDateString(),
            'shift_type' => 'morning',
            'status' => 'active',
        ]);

        $productType = ProductType::create([
            'department_id' => $this->department->id,
            'name' => 'Beverages',
            'code' => 'BEV',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'name' => 'Test Product',
            'sku' => 'SKU-001',
            'product_type_id' => $productType->id,
            'sales_department_id' => $this->department->id,
            'price' => 50,
            'cost' => 30,
            'branch_id' => $this->branch->id,
            'is_active' => true,
            'is_available' => true,
        ]);
    }

    public function test_invoice_to_payment_flow_posts_gl_entries(): void
    {
        $sale = Sale::create([
            'sales_shift_id' => $this->salesShift->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'sold_by_type' => Employee::class,
            'sold_by_id' => $this->employee->id,
            'sale_number' => 'SAL-INT-001',
            'sale_time' => now(),
            'subtotal' => 0,
            'tax' => 0,
            'discount' => 0,
            'total' => 0,
            'status' => 'pending',
        ]);

        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'department_id' => $this->department->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 50,
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
        ]);

        $saleItem->forceFill([
            'unit_cost' => 30,
            'line_cost' => 60,
        ])->save();

        $sale->refresh();
        $sale->update(['status' => 'completed']);

        $sale->refresh();
        $this->assertEquals('posted', $sale->gl_posting_status);

        $saleEntries = GlEntry::where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->where('status', 'posted')
            ->get();

        $this->assertGreaterThanOrEqual(4, $saleEntries->count());
        $this->assertEquals(
            $saleEntries->sum('debit'),
            $saleEntries->sum('credit')
        );

        $payment = Payment::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'amount' => 100,
            'payment_time' => now(),
            'status' => 'pending',
        ]);

        $payment->update(['status' => 'completed']);
        $payment->refresh();

        $this->assertEquals('posted', $payment->gl_posting_status);

        $paymentEntries = GlEntry::where('reference_type', Payment::class)
            ->where('reference_id', $payment->id)
            ->where('status', 'posted')
            ->get();

        $this->assertGreaterThanOrEqual(2, $paymentEntries->count());
        $this->assertEquals(
            $paymentEntries->sum('debit'),
            $paymentEntries->sum('credit')
        );
    }
}
