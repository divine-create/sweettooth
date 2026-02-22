<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;

class ImportHrUsersSimple extends Command
{
    protected $signature = 'import:hr-users-simple
        {--branch-id= : Branch UUID to assign users to}
        {--password= : Default password (defaults to password)}
        {--file= : Specific file path (optional)}';

    protected $description = 'Import HR/staff users from Excel files (simplified and faster)';

    public function handle(): int
    {
        $branchId = $this->option('branch-id') ?: Branch::value('id');
        $password = $this->option('password') ?: 'password';
        $specificFile = $this->option('file');

        if (! $branchId) {
            $this->error('No branch found. Provide --branch-id.');
            return 1;
        }

        $this->info("Importing HR users to branch: {$branchId}");
        $this->info("Default password: {$password}");
        $this->newLine();

        // Get departments for assignment
        $departments = Department::where('branch_id', $branchId)->get()->keyBy('slug');

        $files = $specificFile ? [$specificFile] : [
            base_path('real_data/HR.xlsx'),
            base_path('real_data/SWEETTOOTH STAFFS.xlsx'),
        ];

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($files as $filePath) {
            if (! file_exists($filePath)) {
                $this->warn("File not found: {$filePath}");
                continue;
            }

            $this->info("Processing: " . basename($filePath));
            
            try {
                $result = $this->processFile($filePath, $branchId, $password, $departments);
                $created += $result['created'];
                $updated += $result['updated'];
                $skipped += $result['skipped'];
            } catch (\Exception $e) {
                $this->error("Error processing {$filePath}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Import completed:");
        $this->info("  Created: {$created}");
        $this->info("  Updated: {$updated}");
        $this->info("  Skipped: {$skipped}");

        return 0;
    }

    protected function processFile(string $filePath, string $branchId, string $password, $departments): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        // Read headers
        $headers = [];
        for ($col = 1; $col <= $highestColumn; $col++) {
            $headers[$col] = strtolower(trim($sheet->getCellByColumnAndRow($col, 1)->getValue() ?? ''));
        }

        // Find column indices
        $nameCol = $this->findColumn($headers, ['name', 'staff_name', 'full_name', 'employee_name']);
        $jobCol = $this->findColumn($headers, ['job_description', 'job_title', 'designation', 'position']);
        $deptCol = $this->findColumn($headers, ['department', 'dept']);
        $emailCol = $this->findColumn($headers, ['email', 'email_address']);
        $phoneCol = $this->findColumn($headers, ['phone', 'phone_number', 'mobile']);
        $resumptionCol = $this->findColumn($headers, ['resumption_date', 'date_joined', 'hire_date']);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($highestRow - 1);
        $bar->start();

        for ($row = 2; $row <= $highestRow; $row++) {
            $name = trim($sheet->getCellByColumnAndRow($nameCol, $row)->getValue() ?? '');
            
            if (empty($name)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $jobTitle = trim($sheet->getCellByColumnAndRow($jobCol, $row)->getValue() ?? '');
            $deptName = trim($sheet->getCellByColumnAndRow($deptCol, $row)->getValue() ?? '');
            $email = trim($sheet->getCellByColumnAndRow($emailCol, $row)->getValue() ?? '');
            $phone = trim($sheet->getCellByColumnAndRow($phoneCol, $row)->getValue() ?? '');
            $resumptionDate = $sheet->getCellByColumnAndRow($resumptionCol, $row)->getValue() ?? null;

            // Generate email if not provided
            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = $this->generateEmail($name);
            }

            // Find department
            $departmentId = null;
            if (! empty($deptName)) {
                $deptSlug = Str::slug($deptName);
                $department = $departments->get($deptSlug);
                if ($department) {
                    $departmentId = $department->id;
                }
            }

            // Prepare user data
            $userData = [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'branch_id' => $branchId,
                'last_accessed_branch_id' => $branchId,
                'department_id' => $departmentId,
                'phone' => $this->cleanPhone($phone),
                'hire_date' => $this->parseDate($resumptionDate),
                'is_active' => true,
                'email_verified_at' => now(),
                'user_type' => 'employee',
                'employment_status' => 'active',
            ];

            // Find or create user
            $user = User::where('email', $email)->first();
            
            if ($user) {
                $user->update($userData);
                $updated++;
            } else {
                User::create($userData);
                $created++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return compact('created', 'updated', 'skipped');
    }

    protected function findColumn(array $headers, array $candidates): ?int
    {
        foreach ($headers as $col => $header) {
            foreach ($candidates as $candidate) {
                if (strpos($header, $candidate) !== false) {
                    return $col;
                }
            }
        }
        return null;
    }

    protected function generateEmail(string $name): string
    {
        $slug = Str::slug($name, '.');
        $random = Str::random(4);
        return "{$slug}.{$random}@sweettooth.local";
    }

    protected function cleanPhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }
        $digits = preg_replace('/[^0-9+]/', '', $phone);
        return strlen($digits) > 0 ? substr($digits, 0, 20) : null;
    }

    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        // Handle Excel date serial number
        if (is_numeric($value)) {
            $unixDate = ($value - 25569) * 86400;
            return date('Y-m-d H:i:s', (int)$unixDate);
        }
        
        // Try to parse string date
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
