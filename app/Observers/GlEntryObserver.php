<?php

namespace App\Observers;

use App\Models\GlEntry;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

class GlEntryObserver
{
    public function created(GlEntry $entry): void
    {
        $this->audit('gl_entry_created', $entry, [
            'account_number' => $entry->glAccount?->account_number,
            'debit' => $entry->debit,
            'credit' => $entry->credit,
            'entry_type' => $entry->entry_type,
            'status' => $entry->status,
        ]);
    }

    public function updated(GlEntry $entry): void
    {
        $changes = $entry->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $original = array_intersect_key($entry->getOriginal(), $changes);

        $this->audit('gl_entry_updated', $entry, [
            'changed_fields' => array_keys($changes),
            'before' => $original,
            'after' => $changes,
        ]);
    }

    public function deleted(GlEntry $entry): void
    {
        $this->audit('gl_entry_deleted', $entry, [
            'account_number' => $entry->glAccount?->account_number,
            'debit' => $entry->debit,
            'credit' => $entry->credit,
            'status' => $entry->status,
        ]);
    }

    private function audit(string $action, GlEntry $entry, array $metadata): void
    {
        try {
            $causer = Auth::user();
            AuditService::log(
                $causer,
                $action,
                $entry,
                null,
                'completed',
                null,
                $metadata
            );
        } catch (\Throwable $e) {
            \Log::warning('GlEntryObserver audit failed: ' . $e->getMessage(), [
                'entry_id' => $entry->id,
                'action' => $action,
            ]);
        }
    }
}
