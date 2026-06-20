<?php

namespace App\Livewire\Components;

use App\Services\Chatbot\ChatbotService;
use Livewire\Component;

class ChatBot extends Component
{
    /** @var array<int,array{role:string,content:string}> */
    public array $messages = [];

    public string $draft = '';

    public bool $open = false;

    public bool $thinking = false;

    public function send(ChatbotService $bot): void
    {
        $text = trim($this->draft);

        if ($text === '') {
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
        }
    }

    public function clearChat(): void
    {
        $this->messages = [];
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
