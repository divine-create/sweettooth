<?php

namespace Tests\Feature\Livewire\BranchDashboard\SalesDashboard\Pos;

use App\Livewire\BranchDashboard\SalesDashboard\Pos\Index;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductDispatch;
use App\Models\ProductStock;
use App\Models\ProductType;
use App\Models\Shift;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Services\UomConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PosUomConversionTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;
    protected Department $department;
    protected User $user;
    protected Employee $employee;
    protected UnitOfMeasure $grams;
    protected UnitOfMeasure $scoop;
    protected UnitOfMeasure $cone;
    protected ProductType $productType;

    protected function setUp(): void
    {
        parent::setUp();

        // Create UOM units
        $this->grams = UnitOfMeasure::create([
            'code' => 'g',
            'name' => 'Grams',
            'symbol' => 'g',
            'category' => 'weight',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->scoop = UnitOfMeasure::create([
            'code' => 'scoop',
            'name' => 'Scoops',
            'symbol' => 'scoop',
            'category' => 'serving',
            'sort_order' => 16,
            'is_active' => true,
        ]);

        $this->cone = UnitOfMeasure::create([
            'code' => 'cone',
            'name' => 'Cones',
            'symbol' => 'cone',
            'category' => 'serving',
            'sort_order' => 17,
            'is_active' => true,
        ]);

        // Create UOM conversions
        $service = app(UomConversionService::class);
        $service->setConversion($this->scoop->id, $this->grams->id, 100);
        $service->setConversion($this->cone->id, $this->grams->id, 150);

        // Create branch
        $this->branch = Branch::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Gelato Branch',
            'code' => 'GEL001',
            'location' => 'Test Location',
            'is_active' => true,
        ]);

        // Create department
        $category = \App\Models\DepartmentCategory::create(['name' => 'Sales']);
        $this->department = Department::create([
            'name' => 'Gelato Counter',
            'category_id' => $category->id,
            'branch_id' => $this->branch->id,
        ]);

        // Create user and employee
        $this->user = User::factory()->create();
        $role = Role::create(['name' => 'Sales Staff', 'guard_name' => 'web']);
        $this->user->assignRole($role);

        $this->employee = Employee::create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'employee_code' => 'EMP001',
            'is_active' => true,
        ]);

        // Create product type
        $this->productType = ProductType::create(['name' => 'Ice Cream']);

        // Create active shift for the employee
        Shift::create([
            'employee_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'shift_type' => 'morning',
            'clock_in' => now(),
            'shift_date' => today(),
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_displays_gelato_products_with_scoop_uom_in_pos()
    {
        // Create gelato product with scoop as sales UOM
        $gelatoProduct = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Vanilla Gelato',
            'sku' => 'VAN-GEL-001',
            'product_type_id' => $this->productType->id,
            'uom_id' => $this->grams->id,
            'sales_uom_id' => $this->scoop->id,
            'sales_unit_weight' => 100,
            'sales_department_id' => $this->department->id,
            'price' => 5.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        // Create stock for the product
        ProductDispatch::create([
            'id' => (string) Str::uuid(),
            'product_id' => $gelatoProduct->id,
            'branch_id' => $this->branch->id,
            'dispatch_by' => $this->user->id,
            'production_date' => today(),
            'receive_day' => today(),
            'quantity' => 1000, // 1000g available
            'sales_department_id' => $this->department->id,
            'is_received' => true,
        ]);

        $this->actingAs($this->user);

        // Test that product is available in POS
        $component = Livewire::test(Index::class, [
            'salesDeptSlug' => $this->department->slug,
        ])
        ->set('branchId', $this->branch->id);

        // Verify product can be added to cart
        $component->call('addToCart', $gelatoProduct->id);

        $component->assertSet('cart.' . $gelatoProduct->id . '.name', 'Vanilla Gelato');
        $component->assertSet('cart.' . $gelatoProduct->id . '.qty', 1);
        $component->assertSet('cart.' . $gelatoProduct->id . '.sales_uom', 'scoop');
        $component->assertSet('cart.' . $gelatoProduct->id . '.base_uom', 'g');
        $component->assertSet('cart.' . $gelatoProduct->id . '.has_conversion', true);
    }

    /** @test */
    public function it_converts_scoop_quantity_to_grams_when_adding_to_cart()
    {
        $gelatoProduct = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Chocolate Gelato',
            'sku' => 'CHOC-GEL-001',
            'product_type_id' => $this->productType->id,
            'uom_id' => $this->grams->id,
            'sales_uom_id' => $this->scoop->id,
            'sales_unit_weight' => 100,
            'sales_department_id' => $this->department->id,
            'price' => 6.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        // Create stock (500g available = 5 scoops)
        ProductDispatch::create([
            'id' => (string) Str::uuid(),
            'product_id' => $gelatoProduct->id,
            'branch_id' => $this->branch->id,
            'dispatch_by' => $this->user->id,
            'production_date' => today(),
            'receive_day' => today(),
            'quantity' => 500,
            'sales_department_id' => $this->department->id,
            'is_received' => true,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(Index::class, [
            'salesDeptSlug' => $this->department->slug,
        ])
        ->set('branchId', $this->branch->id);

        // Add 2 scoops to cart
        $component->call('addToCart', $gelatoProduct->id);
        $component->call('addToCart', $gelatoProduct->id);

        // Verify cart has correct quantities
        $component->assertSet('cart.' . $gelatoProduct->id . '.qty', 2); // 2 scoops
        $component->assertSet('cart.' . $gelatoProduct->id . '.base_quantity', 200); // 200g
        $component->assertSet('cart.' . $gelatoProduct->id . '.available_sales_qty', 5); // 5 scoops available
    }

    /** @test */
    public function it_prevents_adding_more_scoops_than_available_stock()
    {
        $gelatoProduct = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Strawberry Gelato',
            'sku' => 'STRAW-GEL-001',
            'product_type_id' => $this->productType->id,
            'uom_id' => $this->grams->id,
            'sales_uom_id' => $this->scoop->id,
            'sales_unit_weight' => 100,
            'sales_department_id' => $this->department->id,
            'price' => 5.50,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        // Create stock (200g available = 2 scoops)
        ProductDispatch::create([
            'id' => (string) Str::uuid(),
            'product_id' => $gelatoProduct->id,
            'branch_id' => $this->branch->id,
            'dispatch_by' => $this->user->id,
            'production_date' => today(),
            'receive_day' => today(),
            'quantity' => 200,
            'sales_department_id' => $this->department->id,
            'is_received' => true,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(Index::class, [
            'salesDeptSlug' => $this->department->slug,
        ])
        ->set('branchId', $this->branch->id);

        // Try to add 3 scoops when only 2 are available
        $component->call('addToCart', $gelatoProduct->id);
        $component->call('addToCart', $gelatoProduct->id);
        
        // This should fail with warning
        $component->call('addToCart', $gelatoProduct->id);

        // Verify only 2 scoops are in cart
        $component->assertSet('cart.' . $gelatoProduct->id . '.qty', 2);
    }

    /** @test */
    public function it_handles_cone_products_with_different_conversion_rate()
    {
        $coneProduct = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Ice Cream Cone',
            'sku' => 'CONE-001',
            'product_type_id' => $this->productType->id,
            'uom_id' => $this->grams->id,
            'sales_uom_id' => $this->cone->id,
            'sales_unit_weight' => 150, // 150g per cone
            'sales_department_id' => $this->department->id,
            'price' => 8.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        // Create stock (450g available = 3 cones)
        ProductDispatch::create([
            'id' => (string) Str::uuid(),
            'product_id' => $coneProduct->id,
            'branch_id' => $this->branch->id,
            'dispatch_by' => $this->user->id,
            'production_date' => today(),
            'receive_day' => today(),
            'quantity' => 450,
            'sales_department_id' => $this->department->id,
            'is_received' => true,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(Index::class, [
            'salesDeptSlug' => $this->department->slug,
        ])
        ->set('branchId', $this->branch->id);

        // Add 2 cones to cart
        $component->call('addToCart', $coneProduct->id);
        $component->call('addToCart', $coneProduct->id);

        // Verify cart has correct quantities
        $component->assertSet('cart.' . $coneProduct->id . '.qty', 2); // 2 cones
        $component->assertSet('cart.' . $coneProduct->id . '.base_quantity', 300); // 300g
        $component->assertSet('cart.' . $coneProduct->id . '.sales_uom', 'cone');
        $component->assertSet('cart.' . $coneProduct->id . '.available_sales_qty', 3); // 3 cones available
    }

    /** @test */
    public function it_saves_both_sales_and_base_quantities_to_sale_items()
    {
        $gelatoProduct = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Mango Sorbet',
            'sku' => 'MANGO-001',
            'product_type_id' => $this->productType->id,
            'uom_id' => $this->grams->id,
            'sales_uom_id' => $this->scoop->id,
            'sales_unit_weight' => 100,
            'sales_department_id' => $this->department->id,
            'price' => 5.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        // Create stock
        ProductDispatch::create([
            'id' => (string) Str::uuid(),
            'product_id' => $gelatoProduct->id,
            'branch_id' => $this->branch->id,
            'dispatch_by' => $this->user->id,
            'production_date' => today(),
            'receive_day' => today(),
            'quantity' => 1000,
            'sales_department_id' => $this->department->id,
            'is_received' => true,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(Index::class, [
            'salesDeptSlug' => $this->department->slug,
        ])
        ->set('branchId', $this->branch->id);

        // Add 3 scoops to cart
        $component->call('addToCart', $gelatoProduct->id);
        $component->call('addToCart', $gelatoProduct->id);
        $component->call('addToCart', $gelatoProduct->id);

        // Set up payment
        $component->set('payments', [
            ['method' => 'cash', 'amount' => 15.00, 'bank_account_id' => null, 'payer_bank' => null]
        ]);

        // Complete the sale
        $component->call('completeSale');

        // Verify sale was created
        $this->assertDatabaseHas('sales', [
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'total' => 15.00,
        ]);

        // Verify sale item has both sales and base quantities
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $gelatoProduct->id,
            'sales_quantity' => 3, // 3 scoops
            'sales_uom_id' => $this->scoop->id,
            'quantity' => 300, // 300g
            'conversion_factor' => 100,
        ]);
    }

    /** @test */
    public function it_deducts_correct_gram_quantity_from_stock_on_sale()
    {
        $gelatoProduct = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Pistachio Gelato',
            'sku' => 'PIST-001',
            'product_type_id' => $this->productType->id,
            'uom_id' => $this->grams->id,
            'sales_uom_id' => $this->scoop->id,
            'sales_unit_weight' => 100,
            'sales_department_id' => $this->department->id,
            'price' => 7.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        // Create stock (500g available)
        $dispatch = ProductDispatch::create([
            'id' => (string) Str::uuid(),
            'product_id' => $gelatoProduct->id,
            'branch_id' => $this->branch->id,
            'dispatch_by' => $this->user->id,
            'production_date' => today(),
            'receive_day' => today(),
            'quantity' => 500,
            'sales_department_id' => $this->department->id,
            'is_received' => true,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(Index::class, [
            'salesDeptSlug' => $this->department->slug,
        ])
        ->set('branchId', $this->branch->id);

        // Add 2 scoops (200g) to cart
        $component->call('addToCart', $gelatoProduct->id);
        $component->call('addToCart', $gelatoProduct->id);

        // Set up payment
        $component->set('payments', [
            ['method' => 'cash', 'amount' => 14.00, 'bank_account_id' => null, 'payer_bank' => null]
        ]);

        // Complete the sale
        $component->call('completeSale');

        // Verify stock was deducted by base quantity (200g), not sales quantity (2 scoops)
        $this->assertDatabaseHas('product_dispatches', [
            'id' => $dispatch->id,
            'quantity_sold' => 200, // Should be 200g, not 2
        ]);
    }

    /** @test */
    public function it_works_with_products_without_sales_uom_conversion()
    {
        $regularProduct = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Cake Slice',
            'sku' => 'CAKE-001',
            'product_type_id' => $this->productType->id,
            'uom_id' => $this->grams->id,
            // No sales_uom_id
            'sales_department_id' => $this->department->id,
            'price' => 12.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        // Create stock
        ProductDispatch::create([
            'id' => (string) Str::uuid(),
            'product_id' => $regularProduct->id,
            'branch_id' => $this->branch->id,
            'dispatch_by' => $this->user->id,
            'production_date' => today(),
            'receive_day' => today(),
            'quantity' => 1000,
            'sales_department_id' => $this->department->id,
            'is_received' => true,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(Index::class, [
            'salesDeptSlug' => $this->department->slug,
        ])
        ->set('branchId', $this->branch->id);

        // Add to cart
        $component->call('addToCart', $regularProduct->id);

        // Verify cart handles product without conversion
        $component->assertSet('cart.' . $regularProduct->id . '.qty', 1);
        $component->assertSet('cart.' . $regularProduct->id . '.has_conversion', false);
        $component->assertSet('cart.' . $regularProduct->id . '.sales_uom', 'g'); // Falls back to base UOM
    }
}
