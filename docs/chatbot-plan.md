# Model-Agnostic AI Chatbot — Implementation Plan

A chatbot for Sweettooth that answers three kinds of questions:

- **(A) How-to** — "How do I close a shift?" → RAG over help docs
- **(B) Business data** — "What were sales last week?" → tool use over existing Services
- **(C) General** — open-ended questions → plain model call

**Design principle:** *Your code owns the stable contract; the LLM vendor is a swappable detail.* A `ChatProvider` interface is the seam. Prism is the default adapter behind it. Swapping models (Claude → GPT → Gemini → Ollama) is an env-var change — no code change above the adapter.

---

## 1. Dependencies & config

```bash
composer require prism-php/prism
php artisan vendor:publish --tag=prism-config   # creates config/prism.php
```

Prism is Laravel-native and abstracts Anthropic / OpenAI / Gemini / Ollama / Mistral
behind one tool-calling + streaming API. (Verify the exact fluent API against the
Prism docs as you wire the adapter — the `ChatProvider` interface insulates you if
anything differs.)

**`config/chatbot.php`** (new):

```php
return [
    // The only line you change to swap models:
    'provider' => env('CHATBOT_PROVIDER', 'anthropic'),
    'model'    => env('CHATBOT_MODEL', 'claude-opus-4-8'),

    'max_tokens'  => 8192,
    'max_steps'   => 6,        // tool-call loop ceiling
    'history_ttl' => 1800,     // seconds to keep a conversation in cache

    // Per-provider knobs the adapter opts into when present
    'features' => [
        'thinking'     => env('CHATBOT_THINKING', true),  // Claude adaptive thinking
        'prompt_cache' => true,
    ],
];
```

Keys live in `.env` (`ANTHROPIC_API_KEY`, etc.) and `config/prism.php`. Swapping to
GPT/Gemini/Ollama is two env vars + an API key — no code change.

---

## 2. File layout

```
app/
├── Services/Chatbot/
│   ├── ChatbotService.php            # orchestration loop (provider-neutral)
│   ├── Contracts/
│   │   ├── ChatProvider.php          # THE stable seam
│   │   └── ChatTool.php              # tool contract
│   ├── Providers/
│   │   ├── PrismChatProvider.php     # default adapter (multi-model)
│   │   └── (later) GatewayChatProvider.php
│   ├── DTO/
│   │   ├── ChatMessage.php
│   │   └── ChatResult.php
│   ├── ToolRegistry.php
│   └── Tools/
│       ├── GetSalesSummaryTool.php   # first real tool
│       ├── GetLowStockItemsTool.php
│       └── SearchHelpTool.php
├── Livewire/Components/
│   └── ChatBot.php                   # the widget
resources/views/livewire/components/chat-bot.blade.php
config/chatbot.php
```

---

## 3. The stable seam — `ChatProvider`

This is *your* contract, fully specified. Every model talks through it.

```php
namespace App\Services\Chatbot\Contracts;

use App\Services\Chatbot\DTO\ChatResult;
use Closure;

interface ChatProvider
{
    /**
     * @param  ChatMessage[]  $messages
     * @param  ChatTool[]     $tools
     * @param  ?Closure       $onToken  fn(string $delta) for streaming to the UI
     */
    public function chat(array $messages, array $tools, ?Closure $onToken = null): ChatResult;
}
```

`ChatResult` carries the final assistant text + token usage; the tool-call loop is
handled inside the adapter (Prism runs it) or inside `ChatbotService` (if you ever use
a provider without an auto-runner). Either way, callers above never see vendor shapes.

---

## 4. Tool contract + registry

```php
namespace App\Services\Chatbot\Contracts;

interface ChatTool
{
    public function name(): string;          // 'get_sales_summary'
    public function description(): string;   // when to call it
    public function schema(): array;         // JSON Schema for inputs
    public function permission(): ?string;   // Spatie permission, or null
    public function handle(array $input): array;  // returns data; YOU scope it
}
```

```php
namespace App\Services\Chatbot;

class ToolRegistry
{
    /** @return ChatTool[] */
    public function for(\App\Models\User $user): array
    {
        return collect([
            app(Tools\GetSalesSummaryTool::class),
            app(Tools\GetLowStockItemsTool::class),
            app(Tools\SearchHelpTool::class),
            // …add tools here
        ])->filter(fn ($t) => $t->permission() === null || $user->can($t->permission()))
          ->values()->all();
    }
}
```

Permission filtering happens **before** tools are even offered to the model — a user
who can't see accounting never gets a GL tool in the list.

---

## 5. First branch-scoped tool (the security-critical pattern)

```php
namespace App\Services\Chatbot\Tools;

use App\Services\Chatbot\Contracts\ChatTool;
use App\Services\SalesWorkflowService;

class GetSalesSummaryTool implements ChatTool
{
    public function __construct(private SalesWorkflowService $sales) {}

    public function name(): string { return 'get_sales_summary'; }

    public function description(): string
    {
        return 'Get total sales, transaction count, and top items for the '
             . 'CURRENT branch over a date range. Use for questions about '
             . 'revenue, takings, or sales performance.';
    }

    public function permission(): ?string { return 'view sales reports'; }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'from' => ['type' => 'string', 'format' => 'date'],
                'to'   => ['type' => 'string', 'format' => 'date'],
            ],
            'required' => ['from', 'to'],
            'additionalProperties' => false,
        ];
    }

    public function handle(array $input): array
    {
        // NON-NEGOTIABLE: branch comes from the session, NOT the model.
        $branchId = current_branch_id();

        // Defensive re-check (the model never sees other branches' data even
        // if the prompt is adversarial).
        abort_unless(auth()->user()->can($this->permission()), 403);

        return $this->sales->summaryForBranch(
            branchId: $branchId,
            from: $input['from'],
            to: $input['to'],
        ); // -> ['total' => ..., 'count' => ..., 'top_items' => [...]]
    }
}
```

The three guarantees that make this safe: **branch injected server-side**,
**permission re-checked at execution**, **read-only**. The model proposes; your code
disposes.

---

## 6. Orchestration — `ChatbotService`

```php
namespace App\Services\Chatbot;

use App\Services\Chatbot\Contracts\ChatProvider;
use App\Services\Chatbot\DTO\ChatMessage;
use Closure;

class ChatbotService
{
    public function __construct(
        private ChatProvider $provider,
        private ToolRegistry $registry,
    ) {}

    public function ask(array $history, string $userText, ?Closure $onToken = null): array
    {
        $messages = [
            ChatMessage::system($this->systemPrompt()),
            ...$history,
            ChatMessage::user($userText),
        ];

        $tools  = $this->registry->for(auth()->user());
        $result = $this->provider->chat($messages, $tools, $onToken);

        return [
            'reply'   => $result->text,
            'history' => [...$history,
                ChatMessage::user($userText),
                ChatMessage::assistant($result->text),
            ],
        ];
    }

    private function systemPrompt(): string
    {
        // Stable + cacheable. Inject NO per-request volatility (no now()/IDs).
        $branch = current_branch_name();
        return <<<TXT
        You are the assistant for Sweettooth, a multi-branch food ERP.
        Answer questions about how to use the software, the user's business
        data (via tools), or general questions. The user is working in branch:
        {$branch}. All data tools are scoped to this branch automatically.
        Never claim data you didn't get from a tool. If a tool isn't available,
        say the user may lack permission.
        TXT;
    }
}
```

Bind the interface in a service provider (`AppServiceProvider`):

```php
$this->app->bind(ChatProvider::class, fn () => match (config('chatbot.provider')) {
    'anthropic', 'openai', 'gemini', 'ollama' => app(PrismChatProvider::class),
    // 'gateway' => app(GatewayChatProvider::class),
});
```

---

## 7. Prism adapter (default — multi-model)

```php
namespace App\Services\Chatbot\Providers;

use App\Services\Chatbot\Contracts\ChatProvider;
use App\Services\Chatbot\DTO\ChatResult;
use Closure;
// Illustrative — confirm Prism's exact facade/enum names against its docs.
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Tool;

class PrismChatProvider implements ChatProvider
{
    public function chat(array $messages, array $tools, ?Closure $onToken = null): ChatResult
    {
        $prismTools = array_map(fn ($t) => Tool::as($t->name())
            ->for($t->description())
            ->withParameters($t->schema())          // JSON schema in
            ->using(fn (array $input) => json_encode($t->handle($input))),
            $tools);

        $pending = Prism::text()
            ->using(Provider::from(config('chatbot.provider')), config('chatbot.model'))
            ->withMaxTokens(config('chatbot.max_tokens'))
            ->withMaxSteps(config('chatbot.max_steps'))   // tool loop
            ->withMessages($this->mapMessages($messages))
            ->withTools($prismTools);

        // Streaming path → push deltas to the Livewire component
        if ($onToken) {
            $text = '';
            foreach ($pending->asStream() as $chunk) {
                $text .= $chunk->text;
                $onToken($chunk->text);
            }
            return new ChatResult($text);
        }

        $response = $pending->asText();
        return new ChatResult($response->text, $response->usage ?? null);
    }
}
```

This one file is the *only* place a vendor name appears. The "optimized edges" (Claude
adaptive thinking, prompt caching) are added here behind `config('chatbot.features.*')`
checks — present them when the active model supports them, skip otherwise.

---

## 8. The widget — Livewire `ChatBot`

```php
namespace App\Livewire\Components;

use App\Services\Chatbot\ChatbotService;
use Livewire\Component;

class ChatBot extends Component
{
    public array $messages = [];   // [['role'=>..., 'text'=>...], ...]
    public string $draft = '';
    public bool $open = false;

    public function send(ChatbotService $bot)
    {
        $text = trim($this->draft);
        if ($text === '') return;

        $this->messages[] = ['role' => 'user', 'text' => $text];
        $this->draft = '';

        // v1: simple (non-streamed) call. Add stream() + wire:stream in v2.
        $result = $bot->ask($this->toHistory(), $text);
        $this->messages[] = ['role' => 'assistant', 'text' => $result['reply']];
    }

    public function render() { return view('livewire.components.chat-bot'); }
}
```

Blade is a floating Flux panel. Mount it once in `resources/views/dashboard.blade.php`
(and/or the branch layout) so it's available everywhere a logged-in user is:

```blade
@auth
    <livewire:components.chat-bot />
@endauth
```

---

## 9. Security checklist (enforce in code, not the prompt)

- ✅ Branch ID always from `current_branch_id()`, never from model input.
- ✅ Permission filtered at registry build **and** re-checked in `handle()`.
- ✅ All v1 tools read-only.
- ✅ System prompt frozen (cacheable; no `now()`/IDs).
- ✅ Treat tool output as untrusted text (injection surface).
- ✅ Log every tool call via the existing `AuditService` (`user`, `branch`, `tool`, `input`).

---

## 10. Milestones

1. **M1 — Plumbing:** config, interface, Prism adapter, `ChatbotService`, widget.
   General chat (job C) works end to end. Swappable model proven (flip env to a
   different provider).
2. **M2 — Tools (job B):** registry + 4–6 read-only branch-scoped tools (sales, low
   stock, cash position, trial balance). The differentiator.
3. **M3 — Help (job A):** `SearchHelpTool` over module docs.
4. **M4 — Polish:** streaming (`wire:stream`), prompt caching + adaptive thinking
   behind feature flags, conversation persistence, per-role tuning.
