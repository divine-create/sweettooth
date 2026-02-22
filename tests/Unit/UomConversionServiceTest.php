<?php

use App\Models\Branch;
use App\Models\Item;
use App\Models\UnitOfMeasure;
use App\Services\UomConversionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('uom_conversions');
    Schema::dropIfExists('items');
    Schema::dropIfExists('branches');
    Schema::dropIfExists('units_of_measure');

    Schema::create('units_of_measure', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique();
        $table->string('name');
        $table->string('symbol');
        $table->string('category');
        $table->integer('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('branches', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name')->unique();
        $table->string('code')->unique();
        $table->string('location');
        $table->string('email')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('items', function (Blueprint $table) {
        $table->id();
        $table->uuid('branch_id');
        $table->string('name');
        $table->string('sku')->unique();
        $table->string('category');
        $table->unsignedBigInteger('uom_id')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('uom_conversions', function (Blueprint $table) {
        $table->id();
        $table->uuid('branch_id')->nullable();
        $table->unsignedBigInteger('item_id')->nullable();
        $table->uuid('product_id')->nullable();
        $table->unsignedBigInteger('from_uom_id');
        $table->unsignedBigInteger('to_uom_id');
        $table->decimal('factor', 24, 12);
        $table->boolean('is_active')->default(true);
        $table->boolean('is_system')->default(false);
        $table->text('notes')->nullable();
        $table->uuid('created_by_id')->nullable();
        $table->string('created_by_type')->nullable();
        $table->timestamps();
    });
});

it('converts quantities using direct and inverse conversions', function () {
    $grams = UnitOfMeasure::query()->create([
        'code' => 'g',
        'name' => 'Grams',
        'symbol' => 'g',
        'category' => 'weight',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $kilograms = UnitOfMeasure::query()->create([
        'code' => 'kg',
        'name' => 'Kilograms',
        'symbol' => 'kg',
        'category' => 'weight',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $service = app(UomConversionService::class);
    $service->setConversion($grams->id, $kilograms->id, 0.001);

    expect($service->convert(5000, $grams->id, $kilograms->id))->toEqualWithDelta(5.0, 0.000000000001);
    expect($service->convert(2, $kilograms->id, $grams->id))->toEqualWithDelta(2000.0, 0.000000000001);
});

it('resolves chained conversions when direct conversion does not exist', function () {
    $grams = UnitOfMeasure::query()->create([
        'code' => 'g',
        'name' => 'Grams',
        'symbol' => 'g',
        'category' => 'weight',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $kilograms = UnitOfMeasure::query()->create([
        'code' => 'kg',
        'name' => 'Kilograms',
        'symbol' => 'kg',
        'category' => 'weight',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $pounds = UnitOfMeasure::query()->create([
        'code' => 'lb',
        'name' => 'Pounds',
        'symbol' => 'lb',
        'category' => 'weight',
        'sort_order' => 3,
        'is_active' => true,
    ]);

    $service = app(UomConversionService::class);
    $service->setConversion($grams->id, $kilograms->id, 0.001);
    $service->setConversion($kilograms->id, $pounds->id, 2.20462262185);

    expect($service->convert(500, $grams->id, $pounds->id))->toEqualWithDelta(1.102311310925, 0.0000000001);
});

it('prefers item scoped conversions over global conversions when context matches', function () {
    $grams = UnitOfMeasure::query()->create([
        'code' => 'g',
        'name' => 'Grams',
        'symbol' => 'g',
        'category' => 'weight',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $cup = UnitOfMeasure::query()->create([
        'code' => 'cup',
        'name' => 'Cup',
        'symbol' => 'cup',
        'category' => 'volume',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $branch = Branch::query()->create([
        'id' => (string) Str::uuid(),
        'name' => 'Branch '.Str::upper(Str::random(6)),
        'code' => 'BR'.Str::upper(Str::random(4)),
        'location' => 'Main location',
        'email' => Str::lower(Str::random(8)).'@example.com',
        'is_active' => true,
    ]);

    $item = Item::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Milk Mix',
        'sku' => 'ITM-'.Str::upper(Str::random(8)),
        'category' => 'raw_material',
        'uom_id' => $grams->id,
        'status' => 'active',
    ]);

    $service = app(UomConversionService::class);
    $service->setConversion($grams->id, $cup->id, 0.0042267528, [], true, ['is_system' => true]);
    $service->setConversion(
        $grams->id,
        $cup->id,
        0.01,
        ['branch_id' => $branch->id, 'item_id' => $item->id],
        true,
        ['is_system' => false]
    );

    $global = $service->convert(100, $grams->id, $cup->id);
    $itemScoped = $service->convert(100, $grams->id, $cup->id, [
        'branch_id' => $branch->id,
        'item_id' => $item->id,
    ]);

    expect($global)->toEqualWithDelta(0.42267528, 0.0000000001);
    expect($itemScoped)->toEqualWithDelta(1.0, 0.0000000001);
});

it('returns null from tryConvert when no conversion exists', function () {
    $grams = UnitOfMeasure::query()->create([
        'code' => 'g',
        'name' => 'Grams',
        'symbol' => 'g',
        'category' => 'weight',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $cup = UnitOfMeasure::query()->create([
        'code' => 'cup',
        'name' => 'Cup',
        'symbol' => 'cup',
        'category' => 'volume',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $service = app(UomConversionService::class);

    expect($service->tryConvert(12, $grams->id, $cup->id))->toBeNull();
});

// SCOOP TO GRAM CONVERSION TESTS
it('converts scoops to grams for gelato products', function () {
    $grams = UnitOfMeasure::query()->create([
        'code' => 'g',
        'name' => 'Grams',
        'symbol' => 'g',
        'category' => 'weight',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $scoop = UnitOfMeasure::query()->create([
        'code' => 'scoop',
        'name' => 'Scoops',
        'symbol' => 'scoop',
        'category' => 'serving',
        'sort_order' => 16,
        'is_active' => true,
    ]);

    $service = app(UomConversionService::class);
    $service->setConversion($scoop->id, $grams->id, 100);

    // 1 scoop should equal 100 grams
    expect($service->convert(1, $scoop->id, $grams->id))->toEqualWithDelta(100.0, 0.000000000001);
    // 2 scoops should equal 200 grams
    expect($service->convert(2, $scoop->id, $grams->id))->toEqualWithDelta(200.0, 0.000000000001);
    // 5 scoops should equal 500 grams
    expect($service->convert(5, $scoop->id, $grams->id))->toEqualWithDelta(500.0, 0.000000000001);
});

it('converts grams to scoops using inverse conversion', function () {
    $grams = UnitOfMeasure::query()->create([
        'code' => 'g',
        'name' => 'Grams',
        'symbol' => 'g',
        'category' => 'weight',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $scoop = UnitOfMeasure::query()->create([
        'code' => 'scoop',
        'name' => 'Scoops',
        'symbol' => 'scoop',
        'category' => 'serving',
        'sort_order' => 16,
        'is_active' => true,
    ]);

    $service = app(UomConversionService::class);
    $service->setConversion($scoop->id, $grams->id, 100);

    // 100 grams should equal 1 scoop
    expect($service->convert(100, $grams->id, $scoop->id))->toEqualWithDelta(1.0, 0.000000000001);
    // 200 grams should equal 2 scoops
    expect($service->convert(200, $grams->id, $scoop->id))->toEqualWithDelta(2.0, 0.000000000001);
    // 50 grams should equal 0.5 scoops
    expect($service->convert(50, $grams->id, $scoop->id))->toEqualWithDelta(0.5, 0.000000000001);
});

it('converts cones to grams for ice cream products', function () {
    $grams = UnitOfMeasure::query()->create([
        'code' => 'g',
        'name' => 'Grams',
        'symbol' => 'g',
        'category' => 'weight',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $cone = UnitOfMeasure::query()->create([
        'code' => 'cone',
        'name' => 'Cones',
        'symbol' => 'cone',
        'category' => 'serving',
        'sort_order' => 17,
        'is_active' => true,
    ]);

    $service = app(UomConversionService::class);
    $service->setConversion($cone->id, $grams->id, 150);

    // 1 cone should equal 150 grams
    expect($service->convert(1, $cone->id, $grams->id))->toEqualWithDelta(150.0, 0.000000000001);
    // 2 cones should equal 300 grams
    expect($service->convert(2, $cone->id, $grams->id))->toEqualWithDelta(300.0, 0.000000000001);
});

it('converts cup servings to grams for ice cream products', function () {
    $grams = UnitOfMeasure::query()->create([
        'code' => 'g',
        'name' => 'Grams',
        'symbol' => 'g',
        'category' => 'weight',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $cupServing = UnitOfMeasure::query()->create([
        'code' => 'cup_serving',
        'name' => 'Cups (Serving)',
        'symbol' => 'cup',
        'category' => 'serving',
        'sort_order' => 18,
        'is_active' => true,
    ]);

    $service = app(UomConversionService::class);
    $service->setConversion($cupServing->id, $grams->id, 200);

    // 1 cup should equal 200 grams
    expect($service->convert(1, $cupServing->id, $grams->id))->toEqualWithDelta(200.0, 0.000000000001);
    // 3 cups should equal 600 grams
    expect($service->convert(3, $cupServing->id, $grams->id))->toEqualWithDelta(600.0, 0.000000000001);
});

it('handles product-scoped scoop to gram conversions', function () {
    $grams = UnitOfMeasure::query()->create([
        'code' => 'g',
        'name' => 'Grams',
        'symbol' => 'g',
        'category' => 'weight',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $scoop = UnitOfMeasure::query()->create([
        'code' => 'scoop',
        'name' => 'Scoops',
        'symbol' => 'scoop',
        'category' => 'serving',
        'sort_order' => 16,
        'is_active' => true,
    ]);

    $branch = Branch::query()->create([
        'id' => (string) Str::uuid(),
        'name' => 'Branch '.Str::upper(Str::random(6)),
        'code' => 'BR'.Str::upper(Str::random(4)),
        'location' => 'Main location',
        'email' => Str::lower(Str::random(8)).'@example.com',
        'is_active' => true,
    ]);

    $productId = (string) Str::uuid();

    $service = app(UomConversionService::class);
    
    // Global conversion: 1 scoop = 100g
    $service->setConversion($scoop->id, $grams->id, 100, [], true, ['is_system' => true]);
    
    // Product-specific conversion: Large scoop = 120g
    $service->setConversion(
        $scoop->id,
        $grams->id,
        120,
        ['branch_id' => $branch->id, 'product_id' => $productId],
        true,
        ['is_system' => false]
    );

    // Without product context - uses global conversion
    $globalGrams = $service->convert(1, $scoop->id, $grams->id);
    
    // With product context - uses product-specific conversion
    $productGrams = $service->convert(1, $scoop->id, $grams->id, [
        'branch_id' => $branch->id,
        'product_id' => $productId,
    ]);

    expect($globalGrams)->toEqualWithDelta(100.0, 0.0000000001);
    expect($productGrams)->toEqualWithDelta(120.0, 0.0000000001);
});
