<?php

namespace App\Services\Chatbot\DTO;

/**
 * Provider-neutral chat message. Adapters translate this into vendor shapes;
 * nothing above the adapter ever sees a Prism/OpenAI/Vertex message object.
 */
class ChatMessage
{
    public function __construct(
        public string $role,    // 'system' | 'user' | 'assistant'
        public string $content,
    ) {}

    public static function system(string $content): self
    {
        return new self('system', $content);
    }

    public static function user(string $content): self
    {
        return new self('user', $content);
    }

    public static function assistant(string $content): self
    {
        return new self('assistant', $content);
    }

    /** @return array{role:string,content:string} */
    public function toArray(): array
    {
        return ['role' => $this->role, 'content' => $this->content];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['role'], $data['content']);
    }
}
