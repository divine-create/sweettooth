<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportRichineiAll extends Command
{
    protected $signature = 'import:richinei-all
        {--branch-id= : Branch UUID (defaults to first branch)}
        {--created-by-id= : User UUID to attribute recipes to (defaults to a Super Admin)}
        {--wip-department=HOT KITCHEN : Department to hold WIP intermediates with no derivable department}
        {--dry-run : Preview every step; write nothing}';

    protected $description = 'Run the full Richinei master-data import in the correct order: items → stock → suppliers → products → recipes';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $branch = $this->option('branch-id');

        $steps = [
            ['Items', 'import:richinei-items', ['--branch-id' => $branch]],
            ['Opening stock', 'import:richinei-stock', ['--branch-id' => $branch]],
            ['Suppliers', 'import:richinei-suppliers', ['--branch-id' => $branch]],
            ['Products', 'import:richinei-products', ['--branch-id' => $branch, '--wip-department' => $this->option('wip-department')]],
            ['Recipes', 'import:richinei-recipes', ['--branch-id' => $branch, '--created-by-id' => $this->option('created-by-id')]],
        ];

        $this->info('================ Richinei Import ================');
        if ($dry) {
            $this->warn('DRY RUN — nothing will be written.');
        }

        foreach ($steps as $i => [$label, $command, $args]) {
            $this->newLine();
            $this->info('['.($i + 1).'/'.count($steps)."] {$label}");
            $args = array_filter($args, fn ($v) => $v !== null && $v !== '');
            if ($dry) {
                $args['--dry-run'] = true;
            }
            $this->call($command, $args);
        }

        $this->newLine();
        $this->info('================ Done ================');
        if ($dry) {
            $this->warn('DRY RUN complete — remove --dry-run to write.');
        }

        return self::SUCCESS;
    }
}
