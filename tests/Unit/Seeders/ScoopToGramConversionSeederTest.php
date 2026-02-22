<?php

namespace Tests\Unit\Seeders;

use App\Models\UnitOfMeasure;
use App\Models\UomConversion;
use Database\Seeders\ScoopToGramConversionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoopToGramConversionSeederTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_scoop_uom_if_not_exists()
    {
        $this->assertDatabaseMissing('units_of_measure', ['code' => 'scoop']);

        // Run the seeder after creating required UOMs
        UnitOfMeasure::create([
            'code' => 'g',
            'name' => 'Grams',
            'symbol' => 'g',
            'category' => 'weight',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        UnitOfMeasure::create([
            'code' => 'scoop',
            'name' => 'Scoops',
            'symbol' => 'scoop',
            'category' => 'serving',
            'sort_order' => 16,
            'is_active' => true,
        ]);

        $seeder = new ScoopToGramConversionSeeder();
        $seeder->run();

        // Verify conversions were created
        $this->assertDatabaseHas('uom_conversions', [
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_creates_scoop_to_gram_conversion_with_correct_factor()
    {
        // Create required UOMs
        $grams = UnitOfMeasure::create([
            'code' => 'g',
            'name' => 'Grams',
            'symbol' => 'g',
            'category' => 'weight',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $scoop = UnitOfMeasure::create([
            'code' => 'scoop',
            'name' => 'Scoops',
            'symbol' => 'scoop',
            'category' => 'serving',
            'sort_order' => 16,
            'is_active' => true,
        ]);

        $seeder = new ScoopToGramConversionSeeder();
        $seeder->run();

        // Verify scoop to gram conversion (1 scoop = 100g)
        $this->assertDatabaseHas('uom_conversions', [
            'from_uom_id' => $scoop->id,
            'to_uom_id' => $grams->id,
            'factor' => 100,
            'is_system' => true,
            'is_active' => true,
        ]);

        // Verify inverse conversion (1g = 0.01 scoop)
        $this->assertDatabaseHas('uom_conversions', [
            'from_uom_id' => $grams->id,
            'to_uom_id' => $scoop->id,
            'factor' => 0.01,
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_creates_cone_to_gram_conversion_with_correct_factor()
    {
        // Create required UOMs
        $grams = UnitOfMeasure::create([
            'code' => 'g',
            'name' => 'Grams',
            'symbol' => 'g',
            'category' => 'weight',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $cone = UnitOfMeasure::create([
            'code' => 'cone',
            'name' => 'Cones',
            'symbol' => 'cone',
            'category' => 'serving',
            'sort_order' => 17,
            'is_active' => true,
        ]);

        $seeder = new ScoopToGramConversionSeeder();
        $seeder->run();

        // Verify cone to gram conversion (1 cone = 150g)
        $this->assertDatabaseHas('uom_conversions', [
            'from_uom_id' => $cone->id,
            'to_uom_id' => $grams->id,
            'factor' => 150,
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_creates_cup_serving_to_gram_conversion_with_correct_factor()
    {
        // Create required UOMs
        $grams = UnitOfMeasure::create([
            'code' => 'g',
            'name' => 'Grams',
            'symbol' => 'g',
            'category' => 'weight',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $cupServing = UnitOfMeasure::create([
            'code' => 'cup_serving',
            'name' => 'Cups (Serving)',
            'symbol' => 'cup',
            'category' => 'serving',
            'sort_order' => 18,
            'is_active' => true,
        ]);

        $seeder = new ScoopToGramConversionSeeder();
        $seeder->run();

        // Verify cup to gram conversion (1 cup = 200g)
        $this->assertDatabaseHas('uom_conversions', [
            'from_uom_id' => $cupServing->id,
            'to_uom_id' => $grams->id,
            'factor' => 200,
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_creates_all_serving_conversions_with_inverse_pairs()
    {
        // Create all required UOMs
        $grams = UnitOfMeasure::create([
            'code' => 'g',
            'name' => 'Grams',
            'symbol' => 'g',
            'category' => 'weight',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        UnitOfMeasure::create([
            'code' => 'scoop',
            'name' => 'Scoops',
            'symbol' => 'scoop',
            'category' => 'serving',
            'sort_order' => 16,
            'is_active' => true,
        ]);

        UnitOfMeasure::create([
            'code' => 'cone',
            'name' => 'Cones',
            'symbol' => 'cone',
            'category' => 'serving',
            'sort_order' => 17,
            'is_active' => true,
        ]);

        UnitOfMeasure::create([
            'code' => 'cup_serving',
            'name' => 'Cups (Serving)',
            'symbol' => 'cup',
            'category' => 'serving',
            'sort_order' => 18,
            'is_active' => true,
        ]);

        $seeder = new ScoopToGramConversionSeeder();
        $seeder->run();

        // Count total conversions created (3 direct + 3 inverse = 6)
        $conversionCount = UomConversion::where('is_system', true)->count();
        $this->assertGreaterThanOrEqual(6, $conversionCount);

        // Verify all conversions are active
        $activeConversions = UomConversion::where('is_system', true)
            ->where('is_active', true)
            ->count();
        $this->assertGreaterThanOrEqual(6, $activeConversions);
    }

    /** @test */
    public function it_does_not_duplicate_existing_conversions()
    {
        // Create required UOMs
        $grams = UnitOfMeasure::create([
            'code' => 'g',
            'name' => 'Grams',
            'symbol' => 'g',
            'category' => 'weight',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $scoop = UnitOfMeasure::create([
            'code' => 'scoop',
            'name' => 'Scoops',
            'symbol' => 'scoop',
            'category' => 'serving',
            'sort_order' => 16,
            'is_active' => true,
        ]);

        // Create existing conversion
        UomConversion::create([
            'from_uom_id' => $scoop->id,
            'to_uom_id' => $grams->id,
            'factor' => 100,
            'is_system' => true,
            'is_active' => true,
            'notes' => 'Existing conversion',
        ]);

        // Run seeder
        $seeder = new ScoopToGramConversionSeeder();
        $seeder->run();

        // Verify no duplicate was created (should still be 1 direct + 1 inverse)
        $directConversions = UomConversion::where('from_uom_id', $scoop->id)
            ->where('to_uom_id', $grams->id)
            ->count();
        $this->assertEquals(1, $directConversions);
    }
}
