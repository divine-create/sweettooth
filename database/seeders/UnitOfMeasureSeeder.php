<?php

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class UnitOfMeasureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks for truncate
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $units = [
            // Weight
            [
                'code' => 'mg',
                'name' => 'Milligrams',
                'symbol' => 'mg',
                'category' => 'weight',
                'description' => 'Milligram',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'g',
                'name' => 'Grams',
                'symbol' => 'g',
                'category' => 'weight',
                'description' => 'Gram',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'kg',
                'name' => 'Kilograms',
                'symbol' => 'kg',
                'category' => 'weight',
                'description' => 'Kilogram',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'oz',
                'name' => 'Ounces',
                'symbol' => 'oz',
                'category' => 'weight',
                'description' => 'Ounce (avoirdupois)',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'lb',
                'name' => 'Pounds',
                'symbol' => 'lb',
                'category' => 'weight',
                'description' => 'Pound',
                'sort_order' => 5,
                'is_active' => true,
            ],

            // Volume
            [
                'code' => 'ml',
                'name' => 'Milliliters',
                'symbol' => 'ml',
                'category' => 'volume',
                'description' => 'Milliliter',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'code' => 'l',
                'name' => 'Liters',
                'symbol' => 'L',
                'category' => 'volume',
                'description' => 'Liter',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'code' => 'cl',
                'name' => 'Centiliters',
                'symbol' => 'cl',
                'category' => 'volume',
                'description' => 'Centiliter',
                'sort_order' => 8,
                'is_active' => true,
            ],
            [
                'code' => 'fl_oz',
                'name' => 'Fluid Ounces',
                'symbol' => 'fl oz',
                'category' => 'volume',
                'description' => 'Fluid Ounce (US)',
                'sort_order' => 9,
                'is_active' => true,
            ],
            [
                'code' => 'cup',
                'name' => 'Cups',
                'symbol' => 'cup',
                'category' => 'volume',
                'description' => 'Cup (US)',
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'tbsp',
                'name' => 'Tablespoons',
                'symbol' => 'tbsp',
                'category' => 'volume',
                'description' => 'Tablespoon (US)',
                'sort_order' => 11,
                'is_active' => true,
            ],
            [
                'code' => 'tsp',
                'name' => 'Teaspoons',
                'symbol' => 'tsp',
                'category' => 'volume',
                'description' => 'Teaspoon (US)',
                'sort_order' => 12,
                'is_active' => true,
            ],

            // Count
            [
                'code' => 'pcs',
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'category' => 'count',
                'description' => 'Piece',
                'sort_order' => 13,
                'is_active' => true,
            ],
            [
                'code' => 'unit',
                'name' => 'Units',
                'symbol' => 'unit',
                'category' => 'count',
                'description' => 'Unit',
                'sort_order' => 14,
                'is_active' => true,
            ],
            [
                'code' => 'dz',
                'name' => 'Dozens',
                'symbol' => 'dz',
                'category' => 'count',
                'description' => 'Dozen (12 units)',
                'sort_order' => 15,
                'is_active' => true,
            ],

            // Serving
            [
                'code' => 'scoop',
                'name' => 'Scoops',
                'symbol' => 'scoop',
                'category' => 'serving',
                'description' => 'Ice cream scoop (100g)',
                'sort_order' => 16,
                'is_active' => true,
            ],
            [
                'code' => 'cone',
                'name' => 'Cones',
                'symbol' => 'cone',
                'category' => 'serving',
                'description' => 'Ice cream cone',
                'sort_order' => 17,
                'is_active' => true,
            ],
            [
                'code' => 'cup_serving',
                'name' => 'Cups (Serving)',
                'symbol' => 'cup',
                'category' => 'serving',
                'description' => 'Ice cream cup',
                'sort_order' => 18,
                'is_active' => true,
            ],

            // Length
            [
                'code' => 'mm',
                'name' => 'Millimeters',
                'symbol' => 'mm',
                'category' => 'length',
                'description' => 'Millimeter',
                'sort_order' => 16,
                'is_active' => true,
            ],
            [
                'code' => 'cm',
                'name' => 'Centimeters',
                'symbol' => 'cm',
                'category' => 'length',
                'description' => 'Centimeter',
                'sort_order' => 17,
                'is_active' => true,
            ],
            [
                'code' => 'm',
                'name' => 'Meters',
                'symbol' => 'm',
                'category' => 'length',
                'description' => 'Meter',
                'sort_order' => 18,
                'is_active' => true,
            ],
            [
                'code' => 'in',
                'name' => 'Inches',
                'symbol' => 'in',
                'category' => 'length',
                'description' => 'Inch',
                'sort_order' => 19,
                'is_active' => true,
            ],
        ];

        $legacyDispatchMap = [
            'g' => 'grams',
            'kg' => 'kg',
            'l' => 'liters',
            'ml' => 'ml',
            'pcs' => 'pcs',
            'unit' => 'units',
            'bag' => 'bags',
            'carton' => 'cartons',
            'scoop' => 'scoops',
            'cone' => 'cones',
            'cup_serving' => 'cups',
        ];

        $hasLegacyDispatchColumn = Schema::hasColumn('units_of_measure', 'legacy_dispatch_uom');
        foreach ($units as &$unit) {
            if ($hasLegacyDispatchColumn) {
                $unit['legacy_dispatch_uom'] = $legacyDispatchMap[$unit['code']] ?? null;
            } else {
                unset($unit['legacy_dispatch_uom']);
            }
        }
        unset($unit);

        // Clear existing units if they exist
        UnitOfMeasure::truncate();

        // Create each unit
        foreach ($units as $unit) {
            UnitOfMeasure::create($unit);
        }

        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✅ '.count($units).' units of measure created successfully.');
        $this->command->info('   - Weight: 5 units (mg, g, kg, oz, lb)');
        $this->command->info('   - Volume: 7 units (ml, l, cl, fl_oz, cup, tbsp, tsp)');
        $this->command->info('   - Count: 3 units (pcs, unit, dz)');
        $this->command->info('   - Length: 4 units (mm, cm, m, in)');
    }
}
