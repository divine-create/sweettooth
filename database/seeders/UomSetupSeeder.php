<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UomSetupSeeder extends Seeder
{
    /**
     * Seed UOM master data and standard conversions.
     */
    public function run(): void
    {
        $this->call([
            UnitOfMeasureSeeder::class,
            UomConversionSeeder::class,
            ScoopToGramConversionSeeder::class,
            CupToGramConversionsSeeder::class,
        ]);
    }
}
