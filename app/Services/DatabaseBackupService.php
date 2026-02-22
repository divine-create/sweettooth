<?php

namespace App\Services;

use App\Helpers\Settings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseBackupService
{
    /**
     * Check if auto backup is enabled
     */
    public function isAutoBackupEnabled(): bool
    {
        return Settings::businessConfiguration('auto_backup', false) === true;
    }

    /**
     * Get backup interval from settings
     */
    public function getBackupInterval(): int
    {
        return Settings::businessConfiguration('backup_interval', 2);
    }

    /**
     * Get backup period from settings (days, weeks, months)
     */
    public function getBackupPeriod(): string
    {
        return Settings::businessConfiguration('backup_period', 'months');
    }

    /**
     * Perform database backup
     */
    public function backup(): array
    {
        try {
            // Get database statistics before backup
            $metadata = $this->getDatabaseMetadata();

            $dbConnection = config('database.default');
            $dbDriver = config("database.connections.{$dbConnection}.driver");

            if ($dbDriver === 'sqlite') {
                $result = $this->backupSqlite();
            } elseif ($dbDriver === 'mysql') {
                $result = $this->backupMysql();
            } else {
                return [
                    'success' => false,
                    'message' => "Unsupported database driver: {$dbDriver}",
                ];
            }

            // Save metadata if backup was successful
            if ($result['success']) {
                $this->saveBackupMetadata($result['filename'], $metadata);
                $result['metadata'] = $metadata;
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Database backup failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get database metadata (table counts, sizes, etc.)
     */
    protected function getDatabaseMetadata(): array
    {
        try {
            $dbConnection = config('database.default');
            $dbDriver = config("database.connections.{$dbConnection}.driver");

            $metadata = [
                'timestamp' => Carbon::now()->toDateTimeString(),
                'database_driver' => $dbDriver,
                'tables' => [],
                'total_records' => 0,
            ];

            // Get all tables
            if ($dbDriver === 'sqlite') {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            } elseif ($dbDriver === 'mysql') {
                $tables = DB::select('SHOW TABLES');
            } else {
                return $metadata;
            }

            // Count records in each table
            foreach ($tables as $table) {
                $tableName = $dbDriver === 'sqlite' ? $table->name : array_values((array) $table)[0];

                try {
                    $count = DB::table($tableName)->count();
                    $metadata['tables'][$tableName] = $count;
                    $metadata['total_records'] += $count;
                } catch (\Exception $e) {
                    $metadata['tables'][$tableName] = 'Error: '.$e->getMessage();
                }
            }

            $metadata['total_tables'] = count($metadata['tables']);

            return $metadata;
        } catch (\Exception $e) {
            Log::error('Failed to get database metadata: '.$e->getMessage());

            return [
                'timestamp' => Carbon::now()->toDateTimeString(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Save backup metadata to JSON file
     */
    protected function saveBackupMetadata(string $backupFilename, array $metadata): void
    {
        try {
            $metadataFilename = str_replace(['.sqlite', '.sql'], '.json', $backupFilename);
            $metadataPath = storage_path("app/backups/{$metadataFilename}");

            file_put_contents($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            Log::error('Failed to save backup metadata: '.$e->getMessage());
        }
    }

    /**
     * Load backup metadata from JSON file
     */
    protected function loadBackupMetadata(string $backupFilename): ?array
    {
        try {
            $metadataFilename = str_replace(['.sqlite', '.sql'], '.json', $backupFilename);
            $metadataPath = storage_path("app/backups/{$metadataFilename}");

            if (file_exists($metadataPath)) {
                $content = file_get_contents($metadataPath);

                return json_decode($content, true);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to load backup metadata: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Backup SQLite database
     */
    protected function backupSqlite(): array
    {
        $dbPath = config('database.connections.sqlite.database');

        if (! file_exists($dbPath)) {
            return [
                'success' => false,
                'message' => 'Database file not found',
            ];
        }

        $backupFileName = $this->generateBackupFileName('sqlite');
        $backupPath = storage_path("app/backups/{$backupFileName}");

        // Copy SQLite database file
        if (copy($dbPath, $backupPath)) {
            // Clean up old backups
            $this->cleanupOldBackups();

            return [
                'success' => true,
                'message' => 'SQLite database backed up successfully',
                'filename' => $backupFileName,
                'path' => $backupPath,
                'size' => $this->formatBytes(filesize($backupPath)),
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to copy database file',
        ];
    }

    /**
     * Backup MySQL database
     */
    protected function backupMysql(): array
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $backupFileName = $this->generateBackupFileName('sql');
        $backupPath = storage_path("app/backups/{$backupFileName}");

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($backupPath)
        );

        // Execute backup
        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($backupPath)) {
            // Clean up old backups
            $this->cleanupOldBackups();

            return [
                'success' => true,
                'message' => 'MySQL database backed up successfully',
                'filename' => $backupFileName,
                'path' => $backupPath,
                'size' => $this->formatBytes(filesize($backupPath)),
            ];
        }

        return [
            'success' => false,
            'message' => 'MySQL backup failed: '.implode("\n", $output),
        ];
    }

    /**
     * Generate backup filename with timestamp
     */
    protected function generateBackupFileName(string $extension): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $dbName = config('database.connections.'.config('database.default').'.database');
        $dbName = basename($dbName, '.sqlite'); // Remove extension if SQLite

        return "backup_{$dbName}_{$timestamp}.{$extension}";
    }

    /**
     * Clean up old backups based on settings
     */
    protected function cleanupOldBackups(): void
    {
        try {
            $backupDir = storage_path('app/backups');
            $files = glob($backupDir.'/backup_*');

            if (empty($files)) {
                return;
            }

            // Sort files by modification time (oldest first)
            usort($files, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            // Keep only the last 10 backups
            $maxBackups = 10;
            if (count($files) > $maxBackups) {
                $filesToDelete = array_slice($files, 0, count($files) - $maxBackups);

                foreach ($filesToDelete as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                        Log::info('Deleted old backup: '.basename($file));
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old backups: '.$e->getMessage());
        }
    }

    /**
     * Get list of all backups with metadata
     */
    public function listBackups(): array
    {
        $backupDir = storage_path('app/backups');
        $files = glob($backupDir.'/backup_*.{sqlite,sql}', GLOB_BRACE);

        if (empty($files)) {
            return [];
        }

        // Sort by modification time (newest first)
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $backups = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $metadata = $this->loadBackupMetadata($filename);

            $backups[] = [
                'filename' => $filename,
                'path' => $file,
                'size' => $this->formatBytes(filesize($file)),
                'size_bytes' => filesize($file),
                'date' => Carbon::createFromTimestamp(filemtime($file))->format('Y-m-d H:i:s'),
                'age' => Carbon::createFromTimestamp(filemtime($file))->diffForHumans(),
                'timestamp' => filemtime($file),
                'metadata' => $metadata,
            ];
        }

        return $backups;
    }

    /**
     * Compare two backups and show differences
     */
    public function compareBackups(string $filename1, string $filename2): array
    {
        $metadata1 = $this->loadBackupMetadata($filename1);
        $metadata2 = $this->loadBackupMetadata($filename2);

        if (! $metadata1 || ! $metadata2) {
            return [
                'success' => false,
                'message' => 'Metadata not available for one or both backups',
            ];
        }

        $comparison = [
            'backup1' => [
                'filename' => $filename1,
                'timestamp' => $metadata1['timestamp'] ?? 'Unknown',
                'total_records' => $metadata1['total_records'] ?? 0,
                'total_tables' => $metadata1['total_tables'] ?? 0,
            ],
            'backup2' => [
                'filename' => $filename2,
                'timestamp' => $metadata2['timestamp'] ?? 'Unknown',
                'total_records' => $metadata2['total_records'] ?? 0,
                'total_tables' => $metadata2['total_tables'] ?? 0,
            ],
            'differences' => [],
            'record_change' => 0,
        ];

        // Calculate total record change
        $comparison['record_change'] = $comparison['backup2']['total_records'] - $comparison['backup1']['total_records'];

        // Compare table by table
        $allTables = array_unique(array_merge(
            array_keys($metadata1['tables'] ?? []),
            array_keys($metadata2['tables'] ?? [])
        ));

        foreach ($allTables as $table) {
            $count1 = $metadata1['tables'][$table] ?? 0;
            $count2 = $metadata2['tables'][$table] ?? 0;
            $change = $count2 - $count1;

            if ($change != 0) {
                $comparison['differences'][$table] = [
                    'before' => $count1,
                    'after' => $count2,
                    'change' => $change,
                    'change_type' => $change > 0 ? 'added' : 'removed',
                ];
            }
        }

        return [
            'success' => true,
            'comparison' => $comparison,
        ];
    }

    /**
     * Delete a specific backup
     */
    public function deleteBackup(string $filename): bool
    {
        $backupPath = storage_path("app/backups/{$filename}");

        if (file_exists($backupPath)) {
            return unlink($backupPath);
        }

        return false;
    }

    /**
     * Restore database from backup
     */
    public function restore(string $filename): array
    {
        try {
            $backupPath = storage_path("app/backups/{$filename}");

            if (! file_exists($backupPath)) {
                return [
                    'success' => false,
                    'message' => 'Backup file not found',
                ];
            }

            $dbConnection = config('database.default');
            $dbDriver = config("database.connections.{$dbConnection}.driver");

            if ($dbDriver === 'sqlite') {
                return $this->restoreSqlite($backupPath);
            } elseif ($dbDriver === 'mysql') {
                return $this->restoreMysql($backupPath);
            }

            return [
                'success' => false,
                'message' => "Unsupported database driver: {$dbDriver}",
            ];
        } catch (\Exception $e) {
            Log::error('Database restore failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restore SQLite database
     */
    protected function restoreSqlite(string $backupPath): array
    {
        $dbPath = config('database.connections.sqlite.database');

        // Create backup of current database before restoring
        $currentBackup = $dbPath.'.before_restore_'.Carbon::now()->format('YmdHis');
        copy($dbPath, $currentBackup);

        // Restore from backup
        if (copy($backupPath, $dbPath)) {
            return [
                'success' => true,
                'message' => 'SQLite database restored successfully',
                'current_backup' => basename($currentBackup),
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to restore database',
        ];
    }

    /**
     * Restore MySQL database
     */
    protected function restoreMysql(string $backupPath): array
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        // Build mysql restore command
        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($backupPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            return [
                'success' => true,
                'message' => 'MySQL database restored successfully',
            ];
        }

        return [
            'success' => false,
            'message' => 'MySQL restore failed: '.implode("\n", $output),
        ];
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    /**
     * Get next scheduled backup time based on settings
     */
    public function getNextBackupTime(): ?Carbon
    {
        if (! $this->isAutoBackupEnabled()) {
            return null;
        }

        $interval = $this->getBackupInterval();
        $period = $this->getBackupPeriod();

        $lastBackup = $this->getLastBackupTime();

        if (! $lastBackup) {
            return Carbon::now();
        }

        switch ($period) {
            case 'days':
                return $lastBackup->copy()->addDays($interval);
            case 'weeks':
                return $lastBackup->copy()->addWeeks($interval);
            case 'months':
                return $lastBackup->copy()->addMonths($interval);
            default:
                return null;
        }
    }

    /**
     * Get last backup time
     */
    public function getLastBackupTime(): ?Carbon
    {
        $backups = $this->listBackups();

        if (empty($backups)) {
            return null;
        }

        return Carbon::parse($backups[0]['date']);
    }

    /**
     * Check if backup is due
     */
    public function isBackupDue(): bool
    {
        if (! $this->isAutoBackupEnabled()) {
            return false;
        }

        $nextBackup = $this->getNextBackupTime();

        if (! $nextBackup) {
            return true;
        }

        return Carbon::now()->greaterThanOrEqualTo($nextBackup);
    }
}
