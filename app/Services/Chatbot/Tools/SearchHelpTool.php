<?php

namespace App\Services\Chatbot\Tools;

use App\Services\Chatbot\Contracts\ChatTool;
use App\Services\Chatbot\HelpIndexService;

/**
 * Retrieves passages from the app's help content so the model can answer
 * "how do I…" / "where is…" questions grounded in real documentation.
 */
class SearchHelpTool implements ChatTool
{
    public function __construct(private HelpIndexService $help) {}

    public function name(): string
    {
        return 'search_help';
    }

    public function description(): string
    {
        return 'Search the Sweettooth help documentation for how to use the software '
            . '(workflows, where features are, step-by-step guidance). Use this for '
            . 'any "how do I…", "where is…", or "how does … work" question about the '
            . 'application itself. Returns relevant help passages to base your answer on.';
    }

    public function permission(): ?string
    {
        // Help content is non-sensitive; available to any authenticated user.
        return null;
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'What the user wants to do or know about, in keywords.'],
            ],
            'required' => ['query'],
            'additionalProperties' => false,
        ];
    }

    public function handle(array $input): array
    {
        $query = trim((string) ($input['query'] ?? ''));

        if ($query === '') {
            return ['results' => [], 'note' => 'Empty query.'];
        }

        $results = $this->help->search($query, 4);

        return [
            'results' => $results,
            'note' => $results === []
                ? 'No matching help content found. Answer from general knowledge of the system, or say you are unsure.'
                : 'Base your answer on these passages and name the relevant module.',
        ];
    }
}
