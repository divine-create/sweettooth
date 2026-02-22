<?php

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use App\Services\UomConversionService;
use Illuminate\Database\Seeder;

class UomConversionSeeder extends Seeder
{
    /**
     * Seed common system-level UOM conversions.
     */
    public function run(): void
    {
        $uomIdsByCode = UnitOfMeasure::query()->pluck('id', 'code');
        if ($uomIdsByCode->isEmpty()) {
            $this->command?->warn('Skipped UOM conversions seeding: units_of_measure is empty.');

            return;
        }

        $definitions = [
            // Weight
            ['mg', 'g', 0.001, '1 mg = 0.001 g'],
            ['g', 'kg', 0.001, '1000 g = 1 kg'],
            ['oz', 'g', 28.349523125, '1 oz = 28.349523125 g'],
            ['lb', 'kg', 0.45359237, '1 lb = 0.45359237 kg'],

            // Volume
            ['ml', 'l', 0.001, '1000 ml = 1 l'],
            ['ml', 'cl', 0.1, '10 ml = 1 cl'],
            ['tsp', 'tbsp', 0.333333333333, '3 tsp = 1 tbsp'],
            ['tbsp', 'cup', 0.0625, '16 tbsp = 1 cup'],
            ['fl_oz', 'ml', 29.5735295625, '1 fl oz = 29.5735295625 ml'],
            ['cup', 'ml', 236.5882365, '1 cup = 236.5882365 ml'],

            // Count
            ['pcs', 'unit', 1, '1 piece = 1 unit'],
            ['dz', 'unit', 12, '1 dozen = 12 units'],

            // Length
            ['mm', 'cm', 0.1, '10 mm = 1 cm'],
            ['cm', 'm', 0.01, '100 cm = 1 m'],
            ['in', 'cm', 2.54, '1 in = 2.54 cm'],
        ];

        $conversionService = app(UomConversionService::class);
        $applied = 0;

        foreach ($definitions as [$fromCode, $toCode, $factor, $notes]) {
            $fromId = $uomIdsByCode[$fromCode] ?? null;
            $toId = $uomIdsByCode[$toCode] ?? null;

            if (! $fromId || ! $toId) {
                continue;
            }

            $conversionService->setConversion(
                (int) $fromId,
                (int) $toId,
                (float) $factor,
                [],
                true,
                [
                    'is_active' => true,
                    'is_system' => true,
                    'notes' => $notes,
                ],
            );

            $applied++;
        }

        $this->command?->info("Seeded {$applied} common UOM conversions (with inverse pairs).");
    }
}
