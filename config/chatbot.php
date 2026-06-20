<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Provider & model
    |--------------------------------------------------------------------------
    | The provider is a Prism provider key. For Google Vertex AI use one of the
    | rmh/vertex keys (e.g. 'vertex-gemini', 'vertex-anthropic'). To swap to a
    | native Prism provider, set 'anthropic' | 'openai' | 'gemini' | 'ollama'.
    | This is the ONLY place that changes when switching models.
    */
    'provider' => env('CHATBOT_PROVIDER', 'vertex-gemini'),
    'model'    => env('CHATBOT_MODEL', 'gemini-2.5-flash'),

    'max_tokens' => (int) env('CHATBOT_MAX_TOKENS', 8192),
    'max_steps'  => (int) env('CHATBOT_MAX_STEPS', 6), // tool-call loop ceiling

    /*
    | Gemini "thinking" budget (tokens). Gemini 2.5-flash with thinking enabled
    | intermittently emits MALFORMED_FUNCTION_CALL on tool use, so we disable it
    | by default (0) for reliable, faster tool calls. Use -1 for dynamic/auto.
    | NOTE: gemini-2.5-pro cannot be 0 — use -1 or >=128 there. Ignored by
    | non-Gemini providers.
    */
    'thinking_budget' => (int) env('CHATBOT_THINKING_BUDGET', 0),

    /*
    | Per-provider capabilities. The adapter only opts into a feature when the
    | active provider actually supports it (e.g. Vertex has no streaming yet).
    */
    'features' => [
        'streaming'    => env('CHATBOT_STREAMING', false),
        'prompt_cache' => env('CHATBOT_PROMPT_CACHE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Help sources (how-to RAG)
    |--------------------------------------------------------------------------
    | Files indexed by the search_help tool, keyed by a human module label.
    | Paths are relative to the project root. Markdown, Blade and HTML supported.
    | Explicit named sources below; folder-based guides are auto-discovered via
    | help_source_globs further down.
    */
    'help_sources' => [
        'Roles'           => 'docs/roles-and-responsibilities.md',
        'Getting started' => 'docs/go-live-setup-by-role.md',
        'Sales'           => 'resources/views/livewire/branch-dashboard/sales-dashboard/helper.blade.php',
        'Organization'    => 'resources/views/livewire/branch-dashboard/organization/helper.blade.php',
    ],

    /*
    | Glob patterns auto-discovered at index time. Drop a new guide folder under
    | sweettooth-userguide/ (with an index.html) and it is indexed automatically —
    | no config change needed. The module label comes from the folder name.
    */
    'help_source_globs' => [
        'sweettooth-userguide/*/index.html',
    ],

    'help_cache_ttl' => (int) env('CHATBOT_HELP_CACHE_TTL', 3600),
];
