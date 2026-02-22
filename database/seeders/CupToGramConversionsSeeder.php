<?php

namespace Database\Seeders;

use App\Models\UomConversion;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class CupToGramConversionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the cup and gram units
        $cupUnit = UnitOfMeasure::where('code', 'cup')->first();
        $gramUnit = UnitOfMeasure::where('code', 'g')->first();
        
        if (!$cupUnit || !$gramUnit) {
            $this->command->error('Required units (cup or g) not found in the database.');
            return;
        }

        // Add conversions for different cup measurements to grams
        // Note: These are approximate averages since cup-to-gram varies by ingredient
        
        // 1/4 Cup to Grams (approximate average: 36.5g from 33-40g range)
        UomConversion::updateOrCreate(
            [
                'from_uom_id' => $cupUnit->id,
                'to_uom_id' => $gramUnit->id,
                'factor' => 36.50,
                'notes' => '1/4 Cup ≈ 33-40g (varies by ingredient)',
            ],
            [
                'factor' => 36.50,
                'is_active' => true,
                'is_system' => true,
                'notes' => '1/4 Cup ≈ 33-40g (varies by ingredient)',
            ]
        );

        // 1/2 Cup to Grams (approximate average: 75g from 65-85g range)
        UomConversion::updateOrCreate(
            [
                'from_uom_id' => $cupUnit->id,
                'to_uom_id' => $gramUnit->id,
                'factor' => 75.00,
                'notes' => '1/2 Cup ≈ 65-85g (varies by ingredient)',
            ],
            [
                'factor' => 75.00,
                'is_active' => true,
                'is_system' => true,
                'notes' => '1/2 Cup ≈ 65-85g (varies by ingredient)',
            ]
        );

        // 1 Cup to Grams (approximate average: 150g from 130-170g range)
        UomConversion::updateOrCreate(
            [
                'from_uom_id' => $cupUnit->id,
                'to_uom_id' => $gramUnit->id,
                'factor' => 150.00,
                'notes' => '1 Cup ≈ 130-170g (varies by ingredient)',
            ],
            [
                'factor' => 150.00,
                'is_active' => true,
                'is_system' => true,
                'notes' => '1 Cup ≈ 130-170g (varies by ingredient)',
            ]
        );

        // 2 Cups (1 Pint) to Grams (approximate average: 300g from 260-340g range)
        UomConversion::updateOrCreate(
            [
                'from_uom_id' => $cupUnit->id,
                'to_uom_id' => $gramUnit->id,
                'factor' => 300.00,
                'notes' => '2 Cups (1 Pint) ≈ 260-340g (varies by ingredient)',
            ],
            [
                'factor' => 300.00,
                'is_active' => true,
                'is_system' => true,
                'notes' => '2 Cups (1 Pint) ≈ 260-340g (varies by ingredient)',
            ]
        );

        // Add the inverse conversions (grams to cups)
        // 1/4 Cup from Grams (approximate average: 0.0274 from 1/36.5)
        UomConversion::updateOrCreate(
            [
                'from_uom_id' => $gramUnit->id,
                'to_uom_id' => $cupUnit->id,
                'factor' => 0.0274, // 1/36.5
                'notes' => 'Inverse: ~36.5g = 1/4 Cup',
            ],
            [
                'factor' => 0.0274,
                'is_active' => true,
                'is_system' => true,
                'notes' => 'Inverse: ~36.5g = 1/4 Cup',
            ]
        );

        // 1/2 Cup from Grams (approximate average: 0.0133 from 1/75)
        UomConversion::updateOrCreate(
            [
                'from_uom_id' => $gramUnit->id,
                'to_uom_id' => $cupUnit->id,
                'factor' => 0.0133, // 1/75
                'notes' => 'Inverse: ~75g = 1/2 Cup',
            ],
            [
                'factor' => 0.0133,
                'is_active' => true,
                'is_system' => true,
                'notes' => 'Inverse: ~75g = 1/2 Cup',
            ]
        );

        // 1 Cup from Grams (approximate average: 0.0067 from 1/150)
        UomConversion::updateOrCreate(
            [
                'from_uom_id' => $gramUnit->id,
                'to_uom_id' => $cupUnit->id,
                'factor' => 0.0067, // 1/150
                'notes' => 'Inverse: ~150g = 1 Cup',
            ],
            [
                'factor' => 0.0067,
                'is_active' => true,
                'is_system' => true,
                'notes' => 'Inverse: ~150g = 1 Cup',
            ]
        );

        // 2 Cups from Grams (approximate average: 0.0033 from 1/300)
        UomConversion::updateOrCreate(
            [
                'from_uom_id' => $gramUnit->id,
                'to_uom_id' => $cupUnit->id,
                'factor' => 0.0033, // 1/300
                'notes' => 'Inverse: ~300g = 2 Cups (1 Pint)',
            ],
            [
                'factor' => 0.0033,
                'is_active' => true,
                'is_system' => true,
                'notes' => 'Inverse: ~300g = 2 Cups (1 Pint)',
            ]
        );

        $this->command->info('Cup to Gram conversions have been added successfully!');
    }
}