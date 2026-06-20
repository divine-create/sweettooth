<?php

namespace App\Services\Chatbot\Providers;

use App\Services\Chatbot\Contracts\ChatProvider;
use App\Services\Chatbot\Contracts\ChatTool;
use App\Services\Chatbot\DTO\ChatMessage;
use App\Services\Chatbot\DTO\ChatResult;
use Closure;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Tool;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

/**
 * Default multi-model adapter. This is the ONLY class that names a vendor.
 * Provider key + model come from config/chatbot.php (e.g. 'vertex-gemini').
 */
class PrismChatProvider implements ChatProvider
{
    public function chat(array $messages, array $tools, ?Closure $onToken = null): ChatResult
    {
        // Providers expect the system prompt via withSystemPrompt(), not inside
        // the messages array. Split it out.
        $system = implode("\n\n", array_map(
            fn (ChatMessage $m) => $m->content,
            array_filter($messages, fn (ChatMessage $m) => $m->role === 'system'),
        ));
        $conversation = array_values(array_filter($messages, fn (ChatMessage $m) => $m->role !== 'system'));

        $provider = (string) config('chatbot.provider');

        $request = Prism::text()
            ->using($provider, config('chatbot.model'))
            ->withMaxTokens(config('chatbot.max_tokens'))
            ->withMaxSteps(config('chatbot.max_steps'))
            ->withMessages($this->mapMessages($conversation));

        // Disable/limit Gemini "thinking" — it's the main cause of intermittent
        // MALFORMED_FUNCTION_CALL on tool use. Only meaningful for Gemini family.
        $budget = config('chatbot.thinking_budget');
        if ($budget !== null && (str_contains($provider, 'gemini') || str_contains($provider, 'vertex'))) {
            $request->withProviderOptions(['thinkingBudget' => (int) $budget]);
        }

        if ($system !== '') {
            $request->withSystemPrompt($system);
        }

        if ($tools !== []) {
            $request->withTools(array_map([$this, 'toPrismTool'], $tools));
        }

        $response = $this->executeWithRetry($request);

        // Vertex has no streaming yet; emit the whole reply once so the UI seam
        // (onToken) still works and stays compatible when streaming lands.
        if ($onToken && $response->text !== '') {
            $onToken($response->text);
        }

        return new ChatResult(
            text: $response->text,
            usage: isset($response->usage) ? [
                'prompt'     => $response->usage->promptTokens ?? null,
                'completion' => $response->usage->completionTokens ?? null,
            ] : null,
        );
    }

    /**
     * gemini-2.5 thinking mode intermittently returns MALFORMED_FUNCTION_CALL,
     * which rmh/vertex surfaces as "unhandled finish reason". It's transient, so
     * retry once before giving up.
     */
    protected function executeWithRetry(\Prism\Prism\Text\PendingRequest $request)
    {
        $maxAttempts = 3;

        for ($attempt = 1; ; $attempt++) {
            try {
                return $request->asText();
            } catch (\Prism\Prism\Exceptions\PrismException $e) {
                $transient = str_contains($e->getMessage(), 'unhandled finish reason');

                if (! $transient || $attempt >= $maxAttempts) {
                    throw $e;
                }
                // else: retry the transient Gemini finish-reason glitch
            }
        }
    }

    /** @param ChatMessage[] $messages */
    protected function mapMessages(array $messages): array
    {
        return array_map(fn (ChatMessage $m) => match ($m->role) {
            'system'    => new SystemMessage($m->content),
            'assistant' => new AssistantMessage($m->content),
            default     => new UserMessage($m->content),
        }, $messages);
    }

    protected function toPrismTool(ChatTool $chatTool): Tool
    {
        $tool = (new Tool)->as($chatTool->name())->for($chatTool->description());

        $schema     = $chatTool->schema();
        $properties = $schema['properties'] ?? [];
        $required   = $schema['required'] ?? [];

        foreach ($properties as $name => $def) {
            $desc = $def['description'] ?? $name;
            $req  = in_array($name, $required, true);

            match ($def['type'] ?? 'string') {
                'number', 'integer' => $tool->withNumberParameter($name, $desc, $req),
                'boolean'           => $tool->withBooleanParameter($name, $desc, $req),
                default             => $tool->withStringParameter($name, $desc, $req),
            };
        }

        // Prism spreads named args; the variadic collects them as an assoc array.
        $tool->using(fn (...$input): string => json_encode($chatTool->handle($input)));

        return $tool;
    }
}
