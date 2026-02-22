<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DatabaseBackupService;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup
                            {--force : Force backup even if auto-backup is disabled}
                            {--list : List all available backups}
                            {--restore= : Restore database from backup file}
                            {--delete= : Delete a specific backup file}
                            {--info : Show backup configuration info}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup, restore, and manage database backups based on settings';

    /**
     * Database backup service
     */
    protected DatabaseBackupService $backupService;

    /**
     * Create a new command instance.
     */
    public function __construct(DatabaseBackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Handle --list option
        if ($this->option('list')) {
            return $this->listBackups();
        }

        // Handle --restore option
        if ($this->option('restore')) {
            return $this->restoreBackup($this->option('restore'));
        }

        // Handle --delete option
        if ($this->option('delete')) {
            return $this->deleteBackup($this->option('delete'));
        }

        // Handle --info option
        if ($this->option('info')) {
            return $this->showInfo();
        }

        // Perform backup
        return $this->performBackup();
    }

    /**
     * Perform database backup
     */
    protected function performBackup(): int
    {
        // Check if auto backup is enabled
        if (!$this->backupService->isAutoBackupEnabled() && !$this->option('force')) {
            $this->warn('Auto backup is disabled in settings. Use --force to backup anyway.');
            return Command::FAILURE;
        }

        $this->info('Starting database backup...');

        $result = $this->backupService->backup();

        if ($result['success']) {
            $this->info('✓ ' . $result['message']);
            $this->line('  Filename: ' . $result['filename']);
            $this->line('  Size: ' . $result['size']);
            $this->line('  Path: ' . $result['path']);

            return Command::SUCCESS;
        }

        $this->error('✗ Backup failed: ' . $result['message']);
        return Command::FAILURE;
    }

    /**
     * List all available backups
     */
    protected function listBackups(): int
    {
        $backups = $this->backupService->listBackups();

        if (empty($backups)) {
            $this->info('No backups found.');
            return Command::SUCCESS;
        }

        $this->info('Available backups:');
        $this->newLine();

        $headers = ['Filename', 'Size', 'Date', 'Age'];
        $rows = [];

        foreach ($backups as $backup) {
            $rows[] = [
                $backup['filename'],
                $backup['size'],
                $backup['date'],
                $backup['age'],
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('Total backups: ' . count($backups));

        return Command::SUCCESS;
    }

    /**
     * Restore database from backup
     */
    protected function restoreBackup(string $filename): int
    {
        if (!$this->confirm("Are you sure you want to restore from '{$filename}'? This will overwrite your current database.")) {
            $this->info('Restore cancelled.');
            return Command::SUCCESS;
        }

        $this->warn('Restoring database from backup...');

        $result = $this->backupService->restore($filename);

        if ($result['success']) {
            $this->info('✓ ' . $result['message']);

            if (isset($result['current_backup'])) {
                $this->line('  Current database backed up as: ' . $result['current_backup']);
            }

            return Command::SUCCESS;
        }

        $this->error('✗ Restore failed: ' . $result['message']);
        return Command::FAILURE;
    }

    /**
     * Delete a specific backup
     */
    protected function deleteBackup(string $filename): int
    {
        if (!$this->confirm("Are you sure you want to delete '{$filename}'?")) {
            $this->info('Deletion cancelled.');
            return Command::SUCCESS;
        }

        if ($this->backupService->deleteBackup($filename)) {
            $this->info("✓ Backup '{$filename}' deleted successfully.");
            return Command::SUCCESS;
        }

        $this->error("✗ Failed to delete backup '{$filename}'.");
        return Command::FAILURE;
    }

    /**
     * Show backup configuration info
     */
    protected function showInfo(): int
    {
        $this->info('Database Backup Configuration:');
        $this->newLine();

        $autoBackup = $this->backupService->isAutoBackupEnabled();
        $interval = $this->backupService->getBackupInterval();
        $period = $this->backupService->getBackupPeriod();
        $nextBackup = $this->backupService->getNextBackupTime();
        $lastBackup = $this->backupService->getLastBackupTime();
        $isBackupDue = $this->backupService->isBackupDue();

        $this->line('  Auto Backup: ' . ($autoBackup ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>'));
        $this->line('  Backup Interval: Every ' . $interval . ' ' . $period);

        if ($lastBackup) {
            $this->line('  Last Backup: ' . $lastBackup->format('Y-m-d H:i:s') . ' (' . $lastBackup->diffForHumans() . ')');
        } else {
            $this->line('  Last Backup: <fg=yellow>Never</>');
        }

        if ($nextBackup) {
            $this->line('  Next Backup: ' . $nextBackup->format('Y-m-d H:i:s') . ' (' . $nextBackup->diffForHumans() . ')');
            $this->line('  Backup Due: ' . ($isBackupDue ? '<fg=yellow>Yes</>' : '<fg=green>No</>'));
        }

        $this->line('  Database: ' . config('database.default'));
        $this->line('  Backup Directory: ' . storage_path('app/backups'));

        $backups = $this->backupService->listBackups();
        $this->line('  Total Backups: ' . count($backups));

        return Command::SUCCESS;
    }
}
