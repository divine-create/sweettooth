<?php

namespace App\Livewire\Components;

use App\Services\Chatbot\ChatbotService;
use Illuminate\Support\Str;
use Livewire\Component;

class ChatBot extends Component
{
    /** @var array<int,array{role:string,content:string}> */
    public array $messages = [];

    public string $draft = '';

    public bool $open = false;

    public bool $thinking = false;

    /** Starter prompts shown on the empty state. */
    public array $suggestions = [
        'What were our sales this month?',
        'Which items need restocking?',
        'How do I close a sales shift?',
        'Are our books balanced?',
    ];

    public function send(ChatbotService $bot): void
    {
        $this->ask($this->draft, $bot);
    }

    public function suggest(int $index, ChatbotService $bot): void
    {
        $this->ask($this->suggestions[$index] ?? '', $bot);
    }

    protected function ask(string $text, ChatbotService $bot): void
    {
        $text = trim($text);

        if ($text === '' || $this->thinking) {
            return;
        }

        $this->messages[] = ['role' => 'user', 'content' => $text];
        $this->draft = '';
        $this->thinking = true;

        try {
            $result = $bot->ask($this->history(), $text);
            $this->messages[] = ['role' => 'assistant', 'content' => $result['reply']];
        } catch (\Throwable $e) {
            report($e);
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Sorry — I could not complete that request. Please try again.',
            ];
        } finally {
            $this->thinking = false;
            $this->dispatch('chat-updated');
        }
    }

    public function clearChat(): void
    {
        $this->messages = [];
    }

    /** Render assistant markdown to sanitized HTML (raw HTML in the model output is stripped). */
    public function md(string $text): string
    {
        return Str::markdown($text, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * History excluding the just-pushed user turn (the service appends it).
     *
     * @return array<int,array{role:string,content:string}>
     */
    protected function history(): array
    {
        return array_slice($this->messages, 0, -1);
    }

    public function render()
    {
        return view('livewire.components.chat-bot');
    }
}
