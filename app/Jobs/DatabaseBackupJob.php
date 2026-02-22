<?php

namespace App\Jobs;

use App\Services\DatabaseBackupService;
use App\Notifications\BackupCompletedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\User;

class DatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The user who initiated the backup
     */
    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct($user = null)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(DatabaseBackupService $backupService): void
    {
        try {
            Log::info('Database backup job started');

            $result = $backupService->backup();

            if ($result['success']) {
                Log::info('Database backup completed successfully', [
                    'filename' => $result['filename'],
                    'size' => $result['size'],
                ]);

                // Send notification to user if provided
                if ($this->user) {
                    $this->user->notify(new BackupCompletedNotification($result, true));
                }
            } else {
                Log::error('Database backup failed', ['message' => $result['message']]);

                // Send failure notification to user if provided
                if ($this->user) {
                    $this->user->notify(new BackupCompletedNotification($result, false));
                }
            }
        } catch (\Exception $e) {
            Log::error('Database backup job exception: ' . $e->getMessage());

            // Send failure notification to user if provided
            if ($this->user) {
                $this->user->notify(new BackupCompletedNotification([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], false));
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Database backup job failed', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Send failure notification to user if provided
        if ($this->user) {
            $this->user->notify(new BackupCompletedNotification([
                'success' => false,
                'message' => $exception->getMessage(),
            ], false));
        }
    }
}
