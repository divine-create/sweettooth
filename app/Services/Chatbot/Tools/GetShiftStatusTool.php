<?php

namespace App\Services\Chatbot\Tools;

use App\Models\Shift;
use App\Services\Chatbot\Contracts\ChatTool;
use Carbon\Carbon;

/**
 * Read-only status of the current user's active shift today. In this unified
 * system a Shift's employee_id is the authenticated user's id.
 */
class GetShiftStatusTool implements ChatTool
{
    public function name(): string
    {
        return 'get_shift_status';
    }

    public function description(): string
    {
        return "Get the status of the current user's own shift for today: whether a "
            . 'shift is active, its type, clock-in time and workflow state. Use for '
            . '"is my shift open?", "have I clocked in?", or "what shift am I on?".';
    }

    public function permission(): ?string
    {
        // A user can always see their own shift.
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
        $userId = current_actor()?->id ?? auth()->id();

        abort_unless($userId !== null, 401);

        $shift = Shift::where('employee_id', $userId)
            ->where('shift_date', Carbon::today())
            ->where('status', 'active')
            ->whereNull('clock_out')
            ->first();

        if (! $shift) {
            return ['active' => false, 'message' => 'You have no active shift today.'];
        }

        return [
            'active'         => true,
            'branch'         => current_branch()?->name,
            'shift_number'   => $shift->shift_number,
            'shift_type'     => $shift->shift_type,
            'clock_in'       => optional($shift->clock_in)->toDateTimeString(),
            'status'         => $shift->status,
            'workflow_state' => $shift->workflow_state,
        ];
    }
}
