<?php

namespace App\Console\Commands;

use App\Models\Item;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignRandomItemUnitPrices extends Command
{
    protected $signature = 'inventory:assign-random-unit-prices
        {--min=1 : Minimum unit price}
        {--max=100 : Maximum unit price}
        {--decimals=2 : Number of decimal places}
        {--dry-run : Show sample without updating}';

    protected $description = 'Assign a random unit price to each item';

    public function handle(): int
    {
        $min = (float) $this->option('min');
        $max = (float) $this->option('max');
        $decimals = (int) $this->option('decimals');
        $dryRun = (bool) $this->option('dry-run');

        if ($min < 0 || $max < 0 || $max < $min) {
            $this->error('Invalid min/max values.');
            return Command::FAILURE;
        }

        $count = Item::query()->count();
        $this->info("Items: {$count}");
        $this->info("Range: {$min} - {$max} (decimals: {$decimals})");

        if ($dryRun) {
            $sample = Item::query()->limit(5)->get();
            foreach ($sample as $item) {
                $price = $this->randomPrice($min, $max, $decimals);
                $this->line("{$item->id} {$item->sku} => {$price}");
            }
            $this->comment('Dry run only. No changes applied.');
            return Command::SUCCESS;
        }

        DB::transaction(function () use ($min, $max, $decimals) {
            Item::query()->chunkById(500, function ($items) use ($min, $max, $decimals) {
                foreach ($items as $item) {
                    $price = $this->randomPrice($min, $max, $decimals);
                    $item->unit_price = $price;
                    $item->last_unit_price = $price;
                    $item->save();
                }
            });
        });

        $this->info('Random unit prices assigned.');

        return Command::SUCCESS;
    }

    private function randomPrice(float $min, float $max, int $decimals): float
    {
        $factor = 10 ** $decimals;
        $minInt = (int) round($min * $factor);
        $maxInt = (int) round($max * $factor);
        $value = random_int($minInt, $maxInt);

        return $value / $factor;
    }
}
