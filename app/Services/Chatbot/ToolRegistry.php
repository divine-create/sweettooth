<?php

namespace App\Services\Chatbot;

use App\Models\User;
use App\Services\Chatbot\Contracts\ChatTool;

/**
 * Builds the list of tools a given user is allowed to use. Permission filtering
 * happens HERE — tools the user can't access are never offered to the model.
 */
class ToolRegistry
{
    /**
     * Tool classes available in the system. (M2 adds the real tools here.)
     *
     * @var array<class-string<ChatTool>>
     */
    protected array $tools = [
        \App\Services\Chatbot\Tools\GetSalesSummaryTool::class,
        \App\Services\Chatbot\Tools\GetLowStockItemsTool::class,
        \App\Services\Chatbot\Tools\GetTrialBalanceTool::class,
        \App\Services\Chatbot\Tools\GetIncomeStatementTool::class,
        \App\Services\Chatbot\Tools\GetBalanceSheetTool::class,
        \App\Services\Chatbot\Tools\SearchHelpTool::class,
    ];

    /** @return ChatTool[] */
    public function for(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return collect($this->tools)
            ->map(fn (string $class): ChatTool => app($class))
            ->filter(fn (ChatTool $tool) => $tool->permission() === null || $user->can($tool->permission()))
            ->values()
            ->all();
    }
}
