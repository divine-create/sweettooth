<?php

namespace App\Services\Chatbot\Contracts;

use App\Services\Chatbot\DTO\ChatMessage;
use App\Services\Chatbot\DTO\ChatResult;
use Closure;

/**
 * The stable seam. Every LLM vendor is reached through this contract, so
 * swapping models never changes anything above the adapter.
 */
interface ChatProvider
{
    /**
     * @param  ChatMessage[]  $messages
     * @param  ChatTool[]     $tools
     * @param  ?Closure       $onToken  fn(string $delta): void — streaming sink,
     *                                   ignored by providers that can't stream.
     */
    public function chat(array $messages, array $tools, ?Closure $onToken = null): ChatResult;
}
