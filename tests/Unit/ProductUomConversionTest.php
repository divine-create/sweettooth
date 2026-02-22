<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\UnitOfMeasure;
use App\Services\UomConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductUomConversionTest extends TestCase
{
    use RefreshDatabase;

    protected UnitOfMeasure $grams;
    protected UnitOfMeasure $scoop;
    protected UnitOfMeasure $cone;
    protected Branch $branch;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base UOM units
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

        // Create branch and department
        $this->branch = Branch::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Branch',
            'code' => 'TB001',
            'location' => 'Test Location',
            'is_active' => true,
        ]);

        $category = \App\Models\DepartmentCategory::create(['name' => 'Production']);
        $this->department = Department::create([
            'name' => 'Gelato Department',
            'category_id' => $category->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    /** @test */
    public function it_detects_when_product_has_sales_uom_conversion()
    {
        $productType = ProductType::create(['name' => 'Ice Cream']);
        
        // Product without sales UOM
        $productWithoutConversion = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Vanilla Ice Cream',
            'sku' => 'VAN-001',
            'product_type_id' => $productType->id,
            'uom_id' => $this->grams->id,
            'sales_department_id' => $this->department->id,
            'price' => 10.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        // Product with sales UOM
        $productWithConversion = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Chocolate Gelato',
            'sku' => 'CHOC-001',
            'product_type_id' => $productType->id,
            'uom_id' => $this->grams->id,
            'sales_uom_id' => $this->scoop->id,
            'sales_unit_weight' => 100,
            'sales_department_id' => $this->department->id,
            'price' => 5.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        $this->assertFalse($productWithoutConversion->hasSalesUomConversion());
        $this->assertTrue($productWithConversion->hasSalesUomConversion());
    }

    /** @test */
    public function it_converts_sales_quantity_to_base_quantity_using_sales_unit_weight()
    {
        $productType = ProductType::create(['name' => 'Ice Cream']);
        
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Strawberry Gelato',
            'sku' => 'STRAW-001',
            'product_type_id' => $productType->id,
            'uom_id' => $this->grams->id,
            'sales_uom_id' => $this->scoop->id,
            'sales_unit_weight' => 100,
            'sales_department_id' => $this->department->id,
            'price' => 6.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        // 1 scoop should equal 100 grams
        $this->assertEquals(100, $product->convertSalesToBaseQuantity(1));
        // 2 scoops should equal 200 grams
        $this->assertEquals(200, $product->convertSalesToBaseQuantity(2));
        // 5 scoops should equal 500 grams
        $this->assertEquals(500, $product->convertSalesToBaseQuantity(5));
    }

    /** @test */
    public function it_converts_base_quantity_to_sales_quantity_using_sales_unit_weight()
    {
        $productType = ProductType::create(['name' => 'Ice Cream']);
        
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Mango Sorbet',
            'sku' => 'MANGO-001',
            'product_type_id' => $productType->id,
            'uom_id' => $this->grams->id,
            'sales_uom_id' => $this->scoop->id,
            'sales_unit_weight' => 100,
            'sales_department_id' => $this->department->id,
            'price' => 5.50,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        // 100 grams should equal 1 scoop
        $this->assertEquals(1, $product->convertBaseToSalesQuantity(100));
        // 200 grams should equal 2 scoops
        $this->assertEquals(2, $product->convertBaseToSalesQuantity(200));
        // 50 grams should equal 0.5 scoops
        $this->assertEquals(0.5, $product->convertBaseToSalesQuantity(50));
    }

    /** @test */
    public function it_converts_sales_quantity_to_base_quantity_using_uom_service()
    {
        $productType = ProductType::create(['name' => 'Ice Cream']);
        
        // Product with sales UOM but no explicit sales_unit_weight
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Cone Ice Cream',
            'sku' => 'CONE-001',
            'product_type_id' => $productType->id,
            'uom_id' => $this->grams->id,
            'sales_uom_id' => $this->cone->id,
            // No sales_unit_weight, should use UOM conversion service (150g per cone)
            'sales_department_id' => $this->department->id,
            'price' => 8.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        // 1 cone should equal 150 grams (from UOM conversion service)
        $this->assertEquals(150, $product->convertSalesToBaseQuantity(1));
        // 2 cones should equal 300 grams
        $this->assertEquals(300, $product->convertSalesToBaseQuantity(2));
    }

    /** @test */
    public function it_returns_same_quantity_when_no_sales_uom_conversion()
    {
        $productType = ProductType::create(['name' => 'Ice Cream']);
        
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Cake Slice',
            'sku' => 'CAKE-001',
            'product_type_id' => $productType->id,
            'uom_id' => $this->grams->id,
            // No sales_uom_id
            'sales_department_id' => $this->department->id,
            'price' => 12.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        // Without sales UOM conversion, quantities should remain unchanged
        $this->assertEquals(100, $product->convertSalesToBaseQuantity(100));
        $this->assertEquals(250, $product->convertSalesToBaseQuantity(250));
        $this->assertEquals(100, $product->convertBaseToSalesQuantity(100));
    }

    /** @test */
    public function it_provides_sales_conversion_display_text()
    {
        $productType = ProductType::create(['name' => 'Ice Cream']);
        
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Pistachio Gelato',
            'sku' => 'PIST-001',
            'product_type_id' => $productType->id,
            'uom_id' => $this->grams->id,
            'sales_uom_id' => $this->scoop->id,
            'sales_unit_weight' => 100,
            'sales_department_id' => $this->department->id,
            'price' => 7.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        $display = $product->getSalesConversionDisplay();
        
        $this->assertStringContainsString('1 scoop', $display);
        $this->assertStringContainsString('100', $display);
        $this->assertStringContainsString('g', $display);
    }

    /** @test */
    public function it_returns_empty_string_for_conversion_display_when_no_conversion()
    {
        $productType = ProductType::create(['name' => 'Ice Cream']);
        
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Plain Product',
            'sku' => 'PLAIN-001',
            'product_type_id' => $productType->id,
            'uom_id' => $this->grams->id,
            'sales_department_id' => $this->department->id,
            'price' => 5.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        $this->assertEquals('', $product->getSalesConversionDisplay());
    }

    /** @test */
    public function it_provides_sales_uom_symbol_accessor()
    {
        $productType = ProductType::create(['name' => 'Ice Cream']);
        
        $productWithSalesUom = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gelato',
            'sku' => 'GEL-001',
            'product_type_id' => $productType->id,
            'uom_id' => $this->grams->id,
            'sales_uom_id' => $this->scoop->id,
            'sales_department_id' => $this->department->id,
            'price' => 5.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        $productWithoutSalesUom = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Cake',
            'sku' => 'CAKE-002',
            'product_type_id' => $productType->id,
            'uom_id' => $this->grams->id,
            'sales_department_id' => $this->department->id,
            'price' => 10.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        $this->assertEquals('scoop', $productWithSalesUom->salesUomSymbol);
        $this->assertEquals('g', $productWithoutSalesUom->salesUomSymbol); // Falls back to base UOM
    }

    /** @test */
    public function it_provides_effective_sales_uom_relationship()
    {
        $productType = ProductType::create(['name' => 'Ice Cream']);
        
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gelato',
            'sku' => 'GEL-002',
            'product_type_id' => $productType->id,
            'uom_id' => $this->grams->id,
            'sales_uom_id' => $this->scoop->id,
            'sales_department_id' => $this->department->id,
            'price' => 5.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        $effectiveUom = $product->effectiveSalesUom;
        
        $this->assertNotNull($effectiveUom);
        $this->assertEquals($this->scoop->id, $effectiveUom->id);
    }

    /** @test */
    public function effective_sales_uom_falls_back_to_base_uom_when_no_sales_uom()
    {
        $productType = ProductType::create(['name' => 'Ice Cream']);
        
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Cake',
            'sku' => 'CAKE-003',
            'product_type_id' => $productType->id,
            'uom_id' => $this->grams->id,
            'sales_department_id' => $this->department->id,
            'price' => 10.00,
            'is_active' => true,
            'is_available' => true,
            'branch_id' => $this->branch->id,
        ]);

        $effectiveUom = $product->effectiveSalesUom;
        
        $this->assertNotNull($effectiveUom);
        $this->assertEquals($this->grams->id, $effectiveUom->id);
    }
}
