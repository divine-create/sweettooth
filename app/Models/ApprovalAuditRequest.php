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
        static::updated(function (ApprovalAuditRequest $req) {
            if ($req->wasChanged('status') && in_array($req->status, ['approved', 'denied'])) {
                $requester = $req->requester;
                if ($requester) {
                    $requester->notify(new ApprovalStatusChangedNotification($req));
                }
            }
        });
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