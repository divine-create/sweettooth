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

        $tools = collect($this->tools)->map(fn (string $class): ChatTool => app($class));

        // Super admins can access everything in the app, so they get every tool.
        if (function_exists('is_super_admin') && is_super_admin()) {
            return $tools->values()->all();
        }

        return $tools
            ->filter(fn (ChatTool $tool) => $tool->permission() === null || $user->can($tool->permission()))
            ->values()
            ->all();
    }
}
