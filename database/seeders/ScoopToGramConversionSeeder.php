<?php

namespace Database\Seeders;

use App\Services\UomConversionService;
use Illuminate\Database\Seeder;

class ScoopToGramConversionSeeder extends Seeder
{
    /**
     * Seed scoop-to-gram and other gelato serving conversions.
     * 1 scoop = 100g (standard ice cream scoop)
     */
    public function run(): void
    {
        $conversionService = app(UomConversionService::class);

        // Standard conversions for gelato/ice cream
        $conversions = [
            // Scoop conversions (1 scoop = 100g)
            ['from' => 'scoop', 'to' => 'g', 'factor' => 100.00],
            
            // Cone conversions (1 cone = 150g - includes ice cream in cone)
            ['from' => 'cone', 'to' => 'g', 'factor' => 150.00],
            
            // Cup serving conversions (1 cup = 200g)
            ['from' => 'cup_serving', 'to' => 'g', 'factor' => 200.00],
        ];

        $applied = 0;

        foreach ($conversions as $conversion) {
            try {
                $result = $conversionService->setConversion(
                    $conversion['from'],
                    $conversion['to'],
                    $conversion['factor'],
                    [], // No specific context (global conversion)
                    true, // Create inverse conversion automatically
                    [
                        'is_system' => true,
                        'notes' => "Standard gelato serving conversion: 1 {$conversion['from']} = {$conversion['factor']}g",
                    ]
                );

                if ($result['direct']) {
                    $applied++;
                    $this->command?->info(
                        "✓ {$conversion['from']} → {$conversion['to']}: {$conversion['factor']} (inverse: " . 
                        (1 / $conversion['factor']) . ")"
                    );
                }
            } catch (\Throwable $e) {
                $this->command?->warn("Failed to set conversion {$conversion['from']} → {$conversion['to']}: " . $e->getMessage());
            }
        }

        $this->command?->info("Seeded {$applied} gelato serving UOM conversions with inverse pairs.");
    }
}
