<?php

namespace App\Console\Commands;

use App\Services\Chatbot\HelpIndexService;
use Illuminate\Console\Command;

class ChatbotReindexHelp extends Command
{
    protected $signature = 'chatbot:reindex-help';

    protected $description = 'Rebuild the chatbot help index from configured/auto-discovered sources';

    public function handle(HelpIndexService $help): int
    {
        $help->flush();
        $chunks = $help->index();

        $byModule = [];
        foreach ($chunks as $chunk) {
            $byModule[$chunk['module']] = ($byModule[$chunk['module']] ?? 0) + 1;
        }

        $this->info('Help index rebuilt: ' . count($chunks) . ' chunks from ' . count($byModule) . ' sources.');
        foreach ($byModule as $module => $count) {
            $this->line("  • {$module}: {$count}");
        }

        return self::SUCCESS;
    }
}
