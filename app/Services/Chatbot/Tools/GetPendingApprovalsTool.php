<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ApprovalRequest;
use App\Services\Chatbot\Contracts\ChatTool;

/**
 * Read-only list of pending approval requests. Super admins see all pending
 * requests; everyone else sees only the ones they themselves submitted.
 * (ApprovalRequest has no branch column, so we scope by requester for safety.)
 */
class GetPendingApprovalsTool implements ChatTool
{
    public function name(): string
    {
        return 'get_pending_approvals';
    }

    public function description(): string
    {
        return 'List pending approval requests. For most users this is the requests '
            . 'they submitted that are still awaiting approval; administrators see all '
            . 'pending requests. Use for "what am I waiting on?" or "what needs approval?".';
    }

    public function permission(): ?string
    {
        // Scoped inside handle(): admins see all, others see only their own.
        return null;
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
            'additionalProperties' => false,
        ];
    }

    public function handle(array $input): array
    {
        $user = auth()->user();
        abort_unless($user !== null, 401);

        $isAdmin = is_super_admin();

        $query = ApprovalRequest::where('status', 'pending');

        if (! $isAdmin) {
            $query->where('requested_by_id', $user->getKey());
        }

        $rows = $query->latest()->limit(20)->get();

        return [
            'scope' => $isAdmin ? 'all_pending' : 'requested_by_me',
            'count' => $rows->count(),
            'approvals' => $rows->map(fn (ApprovalRequest $r) => [
                'action'       => $r->action,
                'target_type'  => class_basename((string) $r->auditable_type),
                'reason'       => $r->reason,
                'requested_at' => optional($r->created_at)->toDateTimeString(),
                'age_days'     => $r->created_at ? (int) $r->created_at->diffInDays(now()) : null,
            ])->all(),
        ];
    }
}
