<?php

namespace App\Services\Chatbot\Contracts;

/**
 * A read-only capability the model may call. Implementations enforce branch
 * scope and permissions inside handle() — the model only proposes the call.
 */
interface ChatTool
{
    /** Machine name the model uses, e.g. 'get_sales_summary'. */
    public function name(): string;

    /** When/why to call this tool (shown to the model). */
    public function description(): string;

    /**
     * JSON Schema for the inputs (vendor-neutral). Example:
     * ['type' => 'object', 'properties' => [...], 'required' => [...]]
     *
     * @return array<string,mixed>
     */
    public function schema(): array;

    /** Spatie permission required to use this tool, or null if unrestricted. */
    public function permission(): ?string;

    /**
     * Execute the tool. MUST scope to current_branch_id() and re-check
     * permission internally. Returns data to hand back to the model.
     *
     * @param  array<string,mixed>  $input
     * @return array<string,mixed>
     */
    public function handle(array $input): array;
}
