<?php

namespace App\Jobs;

use App\Enums\CallbackStatus;
use App\Models\ProductDispatchCallback;
use App\Models\User;
use App\Services\SidebarVisibilityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Job to handle stuck callbacks
 *
 * This job runs periodically (via scheduler) to:
 * 1. Find callbacks that have been stuck in pending/approved states too long
 * 2. Log warnings about stuck callbacks
 * 3. Optionally notify managers about stuck callbacks
 *
 * Recommended schedule: Run every hour
 */
class HandleStuckCallbacksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Hours before a pending callback is considered stuck
     */
    protected int $pendingTimeoutHours;

    /**
     * Hours before an approved callback is considered stuck
     */
    protected int $approvedTimeoutHours;

    /**
     * Whether to send notifications
     */
    protected bool $sendNotifications;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $pendingTimeoutHours = 24,
        int $approvedTimeoutHours = 48,
        bool $sendNotifications = true
    ) {
        $this->pendingTimeoutHours = $pendingTimeoutHours;
        $this->approvedTimeoutHours = $approvedTimeoutHours;
        $this->sendNotifications = $sendNotifications;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('HandleStuckCallbacksJob: Starting stuck callback detection');

        // Find all stuck callbacks
        $stuckCallbacks = ProductDispatchCallback::stuck(
            $this->pendingTimeoutHours,
            $this->approvedTimeoutHours
        )->with(['product', 'salesShift.branch', 'productDispatch.salesShift.branch'])
         ->get();

        if ($stuckCallbacks->isEmpty()) {
            Log::info('HandleStuckCallbacksJob: No stuck callbacks found');
            return;
        }

        Log::warning('HandleStuckCallbacksJob: Found ' . $stuckCallbacks->count() . ' stuck callbacks');

        // Group stuck callbacks by branch
        $callbacksByBranch = $stuckCallbacks->groupBy(function ($callback) {
            return $callback->salesShift?->branch_id
                ?? $callback->productDispatch?->salesShift?->branch_id
                ?? 'unknown';
        });

        foreach ($callbacksByBranch as $branchId => $callbacks) {
            $this->handleBranchCallbacks($branchId, $callbacks);
        }

        Log::info('HandleStuckCallbacksJob: Completed processing');
    }

    /**
     * Handle stuck callbacks for a specific branch
     */
    protected function handleBranchCallbacks($branchId, $callbacks): void
    {
        // Log details about stuck callbacks
        foreach ($callbacks as $callback) {
            $hoursStuck = $callback->getHoursStuck();
            Log::warning('Stuck callback detected', [
                'callback_id' => $callback->id,
                'product' => $callback->product?->name,
                'status' => $callback->formatted_status ?? ($callback->status instanceof CallbackStatus ? $callback->status->value : $callback->status),
                'hours_stuck' => $hoursStuck,
                'branch_id' => $branchId,
                'callback_time' => $callback->callback_time,
            ]);

            // Add escalation note to callback
            if ($hoursStuck > ($this->pendingTimeoutHours * 2)) {
                $this->addEscalationNote($callback, $hoursStuck);
            }
        }

        // Notify managers if enabled
        if ($this->sendNotifications && $branchId !== 'unknown') {
            $this->notifyBranchManagers($branchId, $callbacks);
        }
    }

    /**
     * Add escalation note to callback
     */
    protected function addEscalationNote(ProductDispatchCallback $callback, int $hoursStuck): void
    {
        $statusLabel = $callback->formatted_status ?? ($callback->status instanceof CallbackStatus ? $callback->status->value : $callback->status);
        $note = "\n[ESCALATED " . now()->format('Y-m-d H:i') . "]: Callback has been stuck in '{$statusLabel}' status for {$hoursStuck} hours.";

        $callback->update([
            'notes' => ($callback->notes ?? '') . $note,
        ]);
    }

    /**
     * Notify branch managers about stuck callbacks
     */
    protected function notifyBranchManagers($branchId, $callbacks): void
    {
        // Find managers for this branch
        $managers = User::where('branch_id', $branchId)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Manager', 'Admin', 'Super Admin']);
            })
            ->get();

        if ($managers->isEmpty()) {
            Log::warning('No managers found for branch', ['branch_id' => $branchId]);
            return;
        }

        // Group callbacks by status
        $pendingCount = $callbacks->where('status', CallbackStatus::PENDING)->count();
        $approvedCount = $callbacks->where('status', CallbackStatus::APPROVED_BY_PRODUCTION)->count();

        $message = "Stuck Callbacks Alert:\n";
        $message .= "- {$pendingCount} callbacks pending for over {$this->pendingTimeoutHours} hours\n";
        $message .= "- {$approvedCount} callbacks approved but not received for over {$this->approvedTimeoutHours} hours\n";
        $message .= "Please review and process these callbacks.";

        Log::info('Notification would be sent to managers', [
            'branch_id' => $branchId,
            'manager_count' => $managers->count(),
            'message' => $message,
        ]);

        // TODO: Uncomment when notification class is created
        // Notification::send($managers, new StuckCallbacksNotification($callbacks, $message));
    }

    /**
     * Get a summary of stuck callbacks
     */
    public static function getStuckCallbacksSummary(): array
    {
        $stuckCallbacks = ProductDispatchCallback::stuck()
            ->with(['salesShift.branch'])
            ->get();

        $summary = [
            'total' => $stuckCallbacks->count(),
            'by_status' => [
                'pending' => $stuckCallbacks->where('status', 'pending')->count(),
                'approved_by_production' => $stuckCallbacks->where('status', 'approved_by_production')->count(),
            ],
            'by_branch' => $stuckCallbacks->groupBy(function ($callback) {
                return $callback->salesShift?->branch?->name ?? 'Unknown';
            })->map->count()->toArray(),
        ];

        return $summary;
    }
}
