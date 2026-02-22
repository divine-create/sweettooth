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

class ImportHrUsers extends Command
{
    protected $signature = 'import:hr-users
        {path? : Path to XLSX file or directory (defaults to real_data)}
        {--branch-id= : Branch UUID to assign users to}
        {--password= : Default password (defaults to password)}
        {--assign-roles : Assign roles based on job title}
        {--dry-run : Parse only, do not write to database}';

    protected $description = 'Import HR/staff users from Excel files (HR.xlsx, SWEETTOOTH STAFFS.xlsx)';

    public function handle(): int
    {
        $path = $this->argument('path') ?: base_path('real_data');
        $branchId = $this->resolveBranchId($this->option('branch-id'));
        $password = $this->option('password') ?: 'password';
        $assignRoles = (bool) $this->option('assign-roles');
        $dryRun = (bool) $this->option('dry-run');

        if (! $branchId) {
            $this->error('No branch found. Provide --branch-id.');
            return 1;
        }

        $files = $this->resolveExcelFiles($path);
        if ($files === []) {
            $this->warn('No HR/staff XLSX files found.');
            return 0;
        }

        $departments = Department::query()
            ->where('branch_id', $branchId)
            ->get();

        $deptByKey = [];
        foreach ($departments as $dept) {
            $slug = Str::slug($dept->name);
            $deptByKey[$slug] = $dept;
            $deptByKey[str_replace('-', '_', $slug)] = $dept;
            $deptByKey[$dept->slug ?? $slug] = $dept;
        }

        $departmentMap = [
            'inventory-store' => $deptByKey['inventory-store'] ?? $deptByKey['inventory_store'] ?? null,
            'hr' => $deptByKey['hr'] ?? null,
            'gelato-production' => $deptByKey['gelato-production'] ?? $deptByKey['gelato'] ?? null,
            'pastry-production' => $deptByKey['pastry-production'] ?? $deptByKey['pastry'] ?? null,
            'corner-store' => $deptByKey['corner-store'] ?? $deptByKey['corner_store'] ?? null,
            'concession' => $deptByKey['concession'] ?? null,
            'till-sales' => $deptByKey['till-sales'] ?? $deptByKey['till_sales'] ?? null,
            'hot-kitchen-production' => $deptByKey['hot-kitchen-production'] ?? $deptByKey['hot_kitchen'] ?? $deptByKey['hot_kitchen_production'] ?? null,
        ];

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($files as $filePath) {
            $rows = $this->parseWorkbook($filePath);
            foreach ($rows as $row) {
                $name = trim((string) Arr::get($row, 'name', ''));
                if ($name === '') {
                    $skipped++;
                    continue;
                }

                $job = (string) Arr::get($row, 'job_description', '');
                $departmentName = (string) Arr::get($row, 'department', '');
                $department = $this->resolveDepartmentFromJob($job, $departmentMap, $departmentName);
                $email = $this->sanitizeEmail((string) Arr::get($row, 'email', ''), $name);

                $payload = [
                    'name' => $name,
                    'password' => Hash::make($password),
                    'branch_id' => $branchId,
                    'last_accessed_branch_id' => $branchId,
                    'is_active' => true,
                    'department_id' => $department?->id,
                    'phone' => $this->extractPhone((string) Arr::get($row, 'phone', '')),
                    'hire_date' => $this->normalizeDate((string) Arr::get($row, 'resumption_date', '')),
                    'date_of_birth' => $this->normalizeDate((string) Arr::get($row, 'dob', ''), 'Y-m-d'),
                    'address' => (string) Arr::get($row, 'address', ''),
                    'emergency_contact_name' => Str::limit((string) Arr::get($row, 'emergency_contact', ''), 250, ''),
                    'employment_status' => 'active',
                    'user_type' => 'employee',
                    'email_verified_at' => now(),
                ];

                if ($dryRun) {
                    $created++;
                    continue;
                }

                $user = User::query()->where('email', $email)->first();
                if ($user) {
                    $user->update($payload);
                    $updated++;
                } else {
                    $user = User::create(array_merge($payload, ['email' => $email]));
                    $created++;
                }

                if ($assignRoles) {
                    $roleName = $this->resolveRoleFromJob($job);
                    if ($roleName) {
                        $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                        if ($role) {
                            $user->syncRoles([$role]);
                        }
                    }
                }
            }
        }

        $this->info("Users created: {$created}");
        $this->info("Users updated: {$updated}");
        $this->info("Users skipped: {$skipped}");

        return 0;
    }

    private function resolveBranchId(?string $branchIdOption): ?string
    {
        if ($branchIdOption) {
            return $branchIdOption;
        }

        return Branch::query()->value('id');
    }

    private function resolveExcelFiles(string $path): array
    {
        $files = [];
        if (is_dir($path)) {
            $root = rtrim($path, DIRECTORY_SEPARATOR);
            $files = array_merge(
                glob($root . DIRECTORY_SEPARATOR . '*.xlsx') ?: [],
                glob($root . DIRECTORY_SEPARATOR . 'new' . DIRECTORY_SEPARATOR . '*.xlsx') ?: [],
                glob($root . DIRECTORY_SEPARATOR . '**' . DIRECTORY_SEPARATOR . '*.xlsx') ?: []
            );
        } elseif (is_file($path)) {
            $files = [$path];
        }

        $files = array_values(array_unique(array_filter($files, 'is_file')));
        sort($files);

        $filtered = [];
        foreach ($files as $file) {
            $name = Str::lower(basename($file));
            if (Str::contains($name, ['hr', 'staff'])) {
                $filtered[] = $file;
                continue;
            }

            if ($this->looksLikeStaffWorkbook($file)) {
                $filtered[] = $file;
            }
        }

        return array_values(array_unique($filtered));
    }

    private function looksLikeStaffWorkbook(string $filePath): bool
    {
        try {
            $sheet = IOFactory::load($filePath)->getSheet(0);
            $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            $headers = [];
            for ($column = 1; $column <= $highestColumn; $column++) {
                $headers[] = $this->normalizeHeaderName(
                    (string) $sheet->getCellByColumnAndRow($column, 1)->getFormattedValue()
                );
            }
        } catch (\Throwable) {
            return false;
        }

        $needle = ['name', 'staff_name', 'employee_name', 'full_name', 'job_description', 'designation'];
        foreach ($headers as $header) {
            if (in_array($header, $needle, true)) {
                return true;
            }
        }

        return false;
    }

    private function parseWorkbook(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return [];
        }

        $sheet = IOFactory::load($filePath)->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $headerRow = [];
        for ($column = 1; $column <= $highestColumn; $column++) {
            $headerRow[$column] = $this->normalizeHeaderName((string) $sheet->getCellByColumnAndRow($column, 1)->getFormattedValue());
        }

        $nameColumn = $this->findColumn($headerRow, ['name', 'staff_name', 'employee_name', 'full_name']);
        $firstNameColumn = $this->findColumn($headerRow, ['first_name', 'firstname', 'first name']);
        $lastNameColumn = $this->findColumn($headerRow, ['last_name', 'lastname', 'surname', 'last name']);
        $resumptionColumn = $this->findColumn($headerRow, [
            'resumption_date', 'resumption', 'date_of_resumption', 'date_joined', 'employment_date', 'hire_date',
        ]);
        $jobColumn = $this->findColumn($headerRow, ['job_description', 'job_title', 'designation', 'position', 'role']);
        $dobColumn = $this->findColumn($headerRow, ['dob', 'date_of_birth', 'birth_date']);
        $emailColumn = $this->findColumn($headerRow, ['email', 'email_address']);
        $addressColumn = $this->findColumn($headerRow, ['home_address', 'address', 'residential_address']);
        $phoneColumn = $this->findColumn($headerRow, ['phone_number', 'phone', 'mobile', 'mobile_number']);
        $emergencyColumn = $this->findColumn($headerRow, [
            'contact_in_case_of_emergency', 'emergency_contact', 'next_of_kin', 'next_of_kin_phone',
        ]);
        $departmentColumn = $this->findColumn($headerRow, ['department', 'dept']);

        $records = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $name = '';
            if ($nameColumn !== null) {
                $name = trim((string) $sheet->getCellByColumnAndRow($nameColumn, $row)->getFormattedValue());
            }
            if ($name === '' && ($firstNameColumn !== null || $lastNameColumn !== null)) {
                $firstName = $firstNameColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($firstNameColumn, $row)->getFormattedValue())
                    : '';
                $lastName = $lastNameColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($lastNameColumn, $row)->getFormattedValue())
                    : '';
                $name = trim($firstName . ' ' . $lastName);
            }
            if ($name === '') {
                continue;
            }

            $records[] = [
                'name' => $name,
                'department' => $departmentColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($departmentColumn, $row)->getFormattedValue())
                    : '',
                'job_description' => $jobColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($jobColumn, $row)->getFormattedValue())
                    : '',
                'email' => $emailColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($emailColumn, $row)->getFormattedValue())
                    : '',
                'phone' => $phoneColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($phoneColumn, $row)->getFormattedValue())
                    : '',
                'address' => $addressColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($addressColumn, $row)->getFormattedValue())
                    : '',
                'dob' => $dobColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($dobColumn, $row)->getFormattedValue())
                    : '',
                'resumption_date' => $resumptionColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($resumptionColumn, $row)->getFormattedValue())
                    : '',
                'emergency_contact' => $emergencyColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($emergencyColumn, $row)->getFormattedValue())
                    : '',
            ];
        }

        return $records;
    }

    private function normalizeHeaderName(string $value): string
    {
        return Str::snake(Str::of($value)->trim()->replaceMatches('/\s+/', ' ')->toString());
    }

    private function findColumn(array $headers, array $candidates): ?int
    {
        foreach ($headers as $column => $header) {
            if (in_array($header, $candidates, true)) {
                return $column;
            }
        }

        return null;
    }

    private function sanitizeEmail(string $rawEmail, string $name): string
    {
        $email = Str::lower(trim($rawEmail));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        if ($email !== '' && ! Str::contains($email, '@')) {
            $candidate = $email.'@sweettooth.local';
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        $slug = Str::slug($name, '.');

        return $slug.'.'.Str::random(4).'@sweettooth.local';
    }

    private function extractPhone(string $rawPhone): ?string
    {
        $digits = preg_replace('/[^0-9+]/', '', $rawPhone);

        return $digits !== '' ? Str::limit($digits, 25, '') : null;
    }

    private function normalizeDate(string $rawDate, string $format = 'Y-m-d H:i:s'): ?string
    {
        $rawDate = trim($rawDate);
        if ($rawDate === '') {
            return null;
        }

        $timestamp = strtotime($rawDate);
        if ($timestamp === false) {
            return null;
        }

        return date($format, $timestamp);
    }

    private function resolveDepartmentFromJob(string $jobDescription, array $departments, ?string $departmentName = null): ?Department
    {
        $departmentName = $departmentName !== null ? trim($departmentName) : '';
        if ($departmentName !== '') {
            $department = $this->resolveDepartmentFromName($departmentName, $departments);
            if ($department !== null) {
                return $department;
            }
        }

        $job = Str::lower($jobDescription);

        if (Str::contains($job, ['inventory', 'store'])) {
            return $departments['inventory-store'] ?? null;
        }

        if (Str::contains($job, ['hr', 'human resource'])) {
            return $departments['hr'] ?? null;
        }

        if (Str::contains($job, ['gelato', 'ice cream'])) {
            return $departments['gelato-production'] ?? null;
        }

        if (Str::contains($job, ['pastry', 'baker', 'cake'])) {
            return $departments['pastry-production'] ?? null;
        }

        if (Str::contains($job, ['corner', 'barista', 'coffee'])) {
            return $departments['corner-store'] ?? null;
        }

        if (Str::contains($job, ['concession'])) {
            return $departments['concession'] ?? $departments['till-sales'] ?? null;
        }

        if (Str::contains($job, ['chef', 'kitchen', 'cook', 'production'])) {
            return $departments['hot-kitchen-production'] ?? null;
        }

        return $departments['till-sales'] ?? null;
    }

    private function resolveDepartmentFromName(string $departmentName, array $departments): ?Department
    {
        $name = Str::lower(trim($departmentName));
        if ($name === '') {
            return null;
        }

        if (Str::contains($name, ['inventory', 'store'])) {
            return $departments['inventory-store'] ?? null;
        }

        if (Str::contains($name, ['hr', 'human'])) {
            return $departments['hr'] ?? null;
        }

        if (Str::contains($name, ['gelato', 'ice cream'])) {
            return $departments['gelato-production'] ?? null;
        }

        if (Str::contains($name, ['pastry', 'baker', 'cake'])) {
            return $departments['pastry-production'] ?? null;
        }

        if (Str::contains($name, ['corner', 'barista', 'coffee'])) {
            return $departments['corner-store'] ?? null;
        }

        if (Str::contains($name, ['concession'])) {
            return $departments['concession'] ?? $departments['till-sales'] ?? null;
        }

        if (Str::contains($name, ['till'])) {
            return $departments['till-sales'] ?? null;
        }

        if (Str::contains($name, ['hot kitchen', 'kitchen', 'production'])) {
            return $departments['hot-kitchen-production'] ?? null;
        }

        return null;
    }

    private function resolveRoleFromJob(string $jobDescription): ?string
    {
        $job = Str::lower($jobDescription);

        if (Str::contains($job, ['managing director', 'md'])) {
            return 'Managing Director';
        }

        if (Str::contains($job, ['admin'])) {
            return 'Admin';
        }

        if (Str::contains($job, ['cost accountant'])) {
            return 'Cost Accountant';
        }

        if (Str::contains($job, ['accountant'])) {
            return 'Accountant';
        }

        if (Str::contains($job, ['hr manager', 'human resource manager'])) {
            return 'HR Manager';
        }

        if (Str::contains($job, ['hr', 'human resource'])) {
            return 'HR Officer';
        }

        if (Str::contains($job, ['floor manager'])) {
            return 'Floor Manager';
        }

        if (Str::contains($job, ['assistant shop floor manager'])) {
            return 'Assistant Shop Floor Manager';
        }

        if (Str::contains($job, ['production manager'])) {
            return 'Production Manager';
        }

        if (Str::contains($job, ['sales manager'])) {
            return 'Sales Manager';
        }

        if (Str::contains($job, ['inventory manager'])) {
            return 'Inventory Manager';
        }

        if (Str::contains($job, ['inventory team lead'])) {
            return 'Inventory Team Lead';
        }

        if (Str::contains($job, ['procurement'])) {
            return 'Procurement Officer';
        }

        if (Str::contains($job, ['store keeper', 'storekeeper'])) {
            return 'Store Keeper';
        }

        if (Str::contains($job, ['head chef'])) {
            return 'Head Chef';
        }

        if (Str::contains($job, ['hot kitchen'])) {
            return 'Hot Kitchen Chef';
        }

        if (Str::contains($job, ['pastry'])) {
            return 'Pastry Chef';
        }

        if (Str::contains($job, ['gelato'])) {
            return 'Gelato Chef';
        }

        if (Str::contains($job, ['kitchen assistant supervisor'])) {
            return 'Kitchen Assistant Supervisor';
        }

        if (Str::contains($job, ['kitchen assistant'])) {
            return 'Kitchen Assistant';
        }

        if (Str::contains($job, ['data processor'])) {
            return 'Data Processor';
        }

        if (Str::contains($job, ['till supervisor'])) {
            return 'Till Supervisor';
        }

        if (Str::contains($job, ['cornerstore supervisor', 'corner store supervisor'])) {
            return 'Cornerstore Supervisor';
        }

        if (Str::contains($job, ['consession supervisor', 'concession supervisor'])) {
            return 'Consession Supervisor';
        }

        if (Str::contains($job, ['barista trainer', 'trainer'])) {
            return 'Coffee Barista Trainer';
        }

        if (Str::contains($job, ['barista'])) {
            return 'Coffee Barista';
        }

        if (Str::contains($job, ['cashier'])) {
            return 'Cashier';
        }

        if (Str::contains($job, ['wait staff', 'waiter', 'waitress'])) {
            return 'Wait Staff';
        }

        if (Str::contains($job, ['lobby host supervisor'])) {
            return 'Lobby Host Supervisor';
        }

        if (Str::contains($job, ['lobby host'])) {
            return 'Lobby Host';
        }

        if (Str::contains($job, ['consession', 'concession'])) {
            return 'Consession Attendant';
        }

        if (Str::contains($job, ['facility officer'])) {
            return 'Facility Officer';
        }

        if (Str::contains($job, ['cleaner', 'cleaners supervisor'])) {
            return 'Cleaners Supervisor';
        }

        if (Str::contains($job, ['chief security officer'])) {
            return 'Chief Security Officer';
        }

        if (Str::contains($job, ['security'])) {
            return 'Security Officer';
        }

        if (Str::contains($job, ['social media'])) {
            return 'Social Media Manager';
        }

        if (Str::contains($job, ['driver'])) {
            return 'Driver';
        }

        if (Str::contains($job, ['inventory', 'store'])) {
            return 'Inventory Staff';
        }

        if (Str::contains($job, ['chef', 'kitchen', 'pastry', 'gelato', 'production'])) {
            return 'Production Staff';
        }

        if (Str::contains($job, ['sales', 'floor'])) {
            return 'Sales Staff';
        }

        return 'Sales Staff';
    }
}
