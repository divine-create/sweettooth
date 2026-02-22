<?php

namespace App\Models;

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

        // Notify all superadmins + users with "approve.{action}" permission
        $superadmins = User::role('superadmin')->get();
        $approvers   = Employee::permission("approve.{$action}")->get();

        // Notification::send($superadmins->merge($approvers), new ApprovalRequiredNotification($req));

        return $req;
    }
}