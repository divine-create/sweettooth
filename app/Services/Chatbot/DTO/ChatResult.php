<?php

namespace App\Services\Chatbot\DTO;

/**
 * Provider-neutral result of a chat turn.
 */
class ChatResult
{
    public function __construct(
        public string $text,
        public ?array $usage = null, // ['prompt' => int, 'completion' => int] when available
    ) {}
}
