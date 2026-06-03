<?php

namespace App\Models;

use App\Notifications\ApprovalRequestNotification;
use App\Notifications\ApprovalStatusChangedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class ApprovalAuditRequest extends Model
{
    protected $fillable = [
        'branch_id',
        'requester_id',
        'requester_type',
        'approver_id',
        'approver_type',
        'action',
        'description',
        'payload',
        'status',
        'comment',
        'approved_at',
        'denied_at',
        'rejection_comment',
    ];
    protected $casts = ['payload' => 'array'];

    protected static function booted(): void
    {
        // When the global approval workflow is disabled by a super admin, a newly
        // created pending request is executed immediately instead of waiting for
        // an approver. The action still runs through the exact same execution path
        // (and audit logging) as a manual approval — it simply self-approves.
        static::created(function (ApprovalAuditRequest $req) {
            if ($req->status !== 'pending') {
                return;
            }

            if (function_exists('approvals_required') && approvals_required()) {
                return; // Approval workflow is on — leave the request pending.
            }

            $req->autoExecute();
        });

        static::updated(function (ApprovalAuditRequest $req) {
            if ($req->wasChanged('status') && in_array($req->status, ['approved', 'denied'])) {
                $requester = $req->requester;
                if ($requester) {
                    $requester->notify(new ApprovalStatusChangedNotification($req));
                }
            }
        });
    }

    /**
     * Execute this request immediately and mark it approved.
     *
     * Used when the approval workflow is globally disabled. The acting user
     * (the requester) effectively self-approves; the action is fully audited.
     * On failure the request is left pending so it can still be approved manually.
     */
    public function autoExecute(): void
    {
        $approver = function_exists('current_actor') ? current_actor() : null;
        $approver = $approver ?? $this->requester;

        try {
            $auditable = \App\Services\ApprovalExecutionService::execute($this, $approver);

            $this->update([
                'status' => 'approved',
                'approver_id' => $approver?->id,
                'approver_type' => $approver ? get_class($approver) : null,
                'approved_at' => now(),
                'comment' => 'Auto-approved (approval workflow disabled)',
            ]);

            if ($approver) {
                $baseAction = explode(':', $this->action)[0];
                \App\Services\AuditService::log(
                    $approver,
                    "approve_{$baseAction}",
                    is_object($auditable) ? $auditable : null,
                    'Auto-approved (approval workflow disabled)',
                    'completed'
                );
            }
        } catch (\Throwable $e) {
            \Log::error('❌ [AUTO APPROVAL] Failed to auto-execute request', [
                'request_id' => $this->id,
                'action' => $this->action,
                'error_message' => $e->getMessage(),
            ]);
            // Leave the request pending so an approver can resolve it manually.
        }
    }

    // Polymorphic requester
    public function requester()
    {
        return $this->morphTo('requester');
    }

    public function approver()
    {
        return $this->morphTo('approver');
    }

    public static function createPending($requester, string $action, $model)
    {
        $req = static::create([
            'requester_type' => get_class($requester),
            'requester_id'   => $requester->id,
            'action'         => $action . ':' . $model->getKey(),
            'description'    => request('reason'),
            'payload'        => $model->toArray(),
            'status'         => 'pending',
        ]);

        $approvers = Employee::permission("approve.{$action}")->get();
        if ($approvers->isNotEmpty()) {
            Notification::send($approvers, new ApprovalRequestNotification($req));
        }

        return $req;
    }
}