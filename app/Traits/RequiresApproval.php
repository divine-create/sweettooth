<?php

namespace App\Traits;

use App\Models\ApprovalRequest;
use App\Models\AuditLog;

trait RequiresApproval
{
    protected static function bootRequiresApproval()
    {
        static::deleting(function ($model) {
            static::handleSensitiveAction($model, 'delete');
        });

        static::updating(function ($model) {
            if (request()->has('requires_approval')) {
                static::handleSensitiveAction($model, 'update');
            }
        });
    }

    protected static function handleSensitiveAction($model, string $action)
    {
        $user = auth()->guard('web')->user() ?? auth()->guard('employees')->user();

        // 1. Superadmin (web guard) → always bypass (no audit log here, handled by calling code)
        if (auth('web')->check() && function_exists('is_super_admin') && is_super_admin()) {
            return true;
        }

        // 2. If user has direct permission → execute
        if ($user?->can("{$action}.direct")) {
            static::logAudit($user, $model, $action, 'completed', null);

            return true;
        }

        // 3. If action requires approval → create request
        if ($user?->cannot("bypass.{$action}.approval")) {
            $request = ApprovalRequest::createPending($user, $action, $model);
            static::logAudit($user, $model, $action, 'pending', $request);

            // throw new ApprovalRequiredException($request);
        }

        static::logAudit($user, $model, $action, 'completed', null);

        return true;
    }

    protected static function logAudit($causer, $subject, string $action, string $status, $approval = null)
    {
        // Extract branch_id from subject model if available
        $branchId = null;
        if ($subject) {
            $branchId = $subject->branch_id ?? $subject->branch ?? null;
            if ($branchId instanceof \Illuminate\Database\Eloquent\Model) {
                $branchId = $branchId->id;
            }
        }

        $reason = request('reason');
        $description = $reason;

        // Provide default description if reason is not provided
        if (! $description) {
            switch ($action) {
                case 'delete':
                    $description = 'Deleted '.($subject->name ?? $subject->title ?? get_class($subject).' record');
                    break;
                case 'update':
                    $description = 'Updated '.($subject->name ?? $subject->title ?? get_class($subject).' record');
                    break;
                default:
                    $description = ucfirst($action).' '.($subject->name ?? $subject->title ?? get_class($subject).' record');
            }
        }

        AuditLog::create([
            'branch_id' => $branchId,
            'causer_type' => $causer ? get_class($causer) : null,
            'causer_id' => $causer?->id,
            'auditable_type' => get_class($subject),
            'auditable_id' => $subject->id,
            'action' => $action,
            'description' => $description,
            'old_values' => $action === 'update' ? $subject->getOriginal() : null,
            'new_values' => $action === 'update' ? $subject->getDirty() : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => $status,
            'approval_request_id' => $approval?->id,
            'logged_at' => now(),
            'details' => [
                'causer' => $causer ? get_class($causer) : null,
                'auditable' => get_class($subject),
                'reason' => $reason,
            ],
        ]);
    }
}
