<?php

namespace App\Services\Chatbot;

use App\Services\Chatbot\Contracts\ChatProvider;
use App\Services\Chatbot\DTO\ChatMessage;
use Closure;

/**
 * Orchestrates a chat turn. Provider-neutral: builds messages, gathers the
 * user's allowed tools, and delegates the actual model call to the adapter.
 */
class ChatbotService
{
    public function __construct(
        private ChatProvider $provider,
        private ToolRegistry $registry,
    ) {}

    /**
     * @param  array<int,array{role:string,content:string}>  $history  prior turns
     * @return array{reply:string,history:array<int,array{role:string,content:string}>}
     */
    public function ask(array $history, string $userText, ?Closure $onToken = null): array
    {
        $historyMessages = array_map([ChatMessage::class, 'fromArray'], $history);

        $messages = [
            ChatMessage::system($this->systemPrompt()),
            ...$historyMessages,
            ChatMessage::user($userText),
        ];

        $tools  = $this->registry->for(auth()->user());
        $result = $this->provider->chat($messages, $tools, $onToken);

        $newHistory = [
            ...$history,
            ChatMessage::user($userText)->toArray(),
            ChatMessage::assistant($result->text)->toArray(),
        ];

        return ['reply' => $result->text, 'history' => $newHistory];
    }

    private function systemPrompt(): string
    {
        $branch = current_branch()?->name ?? 'an unspecified branch';
        $today  = now()->toDateString();

        return <<<TXT
        You are the assistant for Sweettooth, a multi-branch food ERP system.
        You help users with three kinds of questions:
        1. How to use the software (workflows, where to find features).
        2. The user's business data — only via the tools provided to you.
        3. General questions.

        Today's date is {$today}. Use it for any relative dates the user mentions
        (e.g. "today", "this week", "last month") — do NOT rely on your own idea
        of the current date.

        The user is currently working in branch: {$branch}. All data tools are
        scoped to this branch automatically — never ask the user for a branch id.

        Rules:
        - Never state business figures you did not get from a tool result.
        - If you have a tool that fits the question, you MUST call it rather than
          saying you cannot help.
        - If no tool fits a data question, do not say you "lack the ability";
          instead explain the user may not have permission to view that data in
          their role and could ask an administrator.
        - Be concise and practical. Use the user's own terminology.
        TXT;
    }
}
