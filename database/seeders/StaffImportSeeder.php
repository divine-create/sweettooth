<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;

class StaffImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = base_path('real_data/new/SWEETTOOTH STAFFS.xlsx');

        if (!file_exists($filePath)) {
            $this->command->error('❌ Staff Excel file not found: ' . $filePath);
            return;
        }

        $this->command->info('👥 Importing staff from Excel file...');

        // Get branch
        $branch = Branch::where('code', 'PHC-002')->first();
        if (!$branch) {
            $this->command->error('❌ Branch not found. Please run PortHarcourtRealDataSeeder first.');
            return;
        }

        // Get departments
        $departments = $this->getDepartments($branch);
        $this->command->info('✅ Found '.count($departments).' departments.');

        // Parse Excel file
        $staffData = $this->parseExcelFile($filePath);
        $this->command->info('📄 Found '.count($staffData).' staff records.');

        // Create users
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($staffData as $staff) {
            $result = $this->createOrUpdateUser($staff, $branch, $departments);
            if ($result === 'created') {
                $created++;
            } elseif ($result === 'updated') {
                $updated++;
            } else {
                $skipped++;
            }
        }

        $this->command->info('✅ Import completed!');
        $this->command->info("   Created: {$created}");
        $this->command->info("   Updated: {$updated}");
        $this->command->info("   Skipped: {$skipped}");
        $this->command->info('🔑 Default password: password');
        $this->command->warn('⚠️  Please advise users to change their password after first login!');
    }

    /**
     * Get departments keyed by their key
     *
     * @return array<string, Department>
     */
    private function getDepartments(Branch $branch): array
    {
        $departments = [];
        foreach (Department::where('branch_id', $branch->id)->get() as $dept) {
            $key = strtolower(str_replace(' ', '_', $dept->name));
            $departments[$key] = $dept;
        }
        return $departments;
    }

    /**
     * Parse Excel file
     *
     * @return array<int, array{name: string, job_title: string}>
     */
    private function parseExcelFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        $staffData = [];

        // Start from row 3 (row 1 is title, row 2 is header: S/N | NAME | ROLE)
        for ($row = 3; $row <= $highestRow; $row++) {
            $sn = trim((string) $sheet->getCellByColumnAndRow(1, $row)->getValue());
            $name = trim((string) $sheet->getCellByColumnAndRow(2, $row)->getValue());
            $jobTitle = trim((string) $sheet->getCellByColumnAndRow(3, $row)->getValue());

            // Skip empty rows (no S/N or name)
            if ($sn === '' && $name === '') {
                continue;
            }

            // Skip if name is empty
            if ($name === '') {
                continue;
            }

            $staffData[] = [
                'name' => $name,
                'job_title' => $jobTitle,
            ];
        }

        return $staffData;
    }

    /**
     * Create or update user
     */
    private function createOrUpdateUser(array $staff, Branch $branch, array $departments): string
    {
        $name = $staff['name'];
        $jobTitle = $staff['job_title'];

        // Generate email from name
        $email = $this->generateEmail($name);

        // Check if user already exists
        $user = User::where('email', $email)->first();

        // Get department and role from job title
        $department = $this->getDepartmentForJobTitle($jobTitle, $departments);
        $roleName = $this->getRoleForJobTitle($jobTitle);

        if (!$user) {
            // Create new user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'branch_id' => $branch->id,
                'department_id' => $department?->id,
                'employee_number' => $this->generateEmployeeNumber(),
                'employment_status' => 'active',
                'user_type' => 'employee',
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $this->assignRole($user, $roleName);

            return 'created';
        } else {
            // Update existing user
            $user->update([
                'department_id' => $department?->id,
            ]);

            $this->assignRole($user, $roleName);

            return 'updated';
        }
    }

    /**
     * Generate email from name
     */
    private function generateEmail(string $name): string
    {
        // Remove special characters and convert to lowercase
        $cleanName = strtolower(preg_replace('/[^A-Za-z\s]/', '', $name));
        
        // Replace spaces with dots
        $emailName = str_replace(' ', '.', trim($cleanName));
        
        // Remove consecutive dots
        $emailName = preg_replace('/\.+/', '.', $emailName);
        
        return $emailName . '@sweettooth.local';
    }

    /**
     * Generate employee number
     */
    private function generateEmployeeNumber(): string
    {
        $nextNumber = 1;
        
        // Find the highest employee number
        $lastUser = User::where('employee_number', 'LIKE', 'EMP%')
            ->orderByRaw('CAST(SUBSTRING(employee_number, 4) AS UNSIGNED) DESC')
            ->first();
        
        if ($lastUser && $lastUser->employee_number) {
            $lastNumber = (int) str_replace('EMP', '', $lastUser->employee_number);
            $nextNumber = $lastNumber + 1;
        }
        
        return 'EMP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get department for job title
     *
     * @param  array<string, Department>  $departments
     */
    private function getDepartmentForJobTitle(string $jobTitle, array $departments): ?Department
    {
        $jobTitle = strtoupper($jobTitle);

        // Management/Executive
        if (str_contains($jobTitle, 'MANAGER') && str_contains($jobTitle, 'HUMAN RESOURCE')) {
            return $departments['hr'] ?? null;
        }
        if (str_contains($jobTitle, 'MANAGER') && str_contains($jobTitle, 'PRODUCTION')) {
            return $departments['hot_kitchen'] ?? null;
        }
        if (str_contains($jobTitle, 'MANAGER') && str_contains($jobTitle, 'INVENTORY')) {
            return $departments['inventory/store'] ?? null;
        }
        if (str_contains($jobTitle, 'ACCOUNTANT') || str_contains($jobTitle, 'ACCOUNTING')) {
            return $departments['hr'] ?? null; // Use HR as default for finance
        }

        // Production departments
        if (str_contains($jobTitle, 'PASTRY')) {
            return $departments['pastry'] ?? null;
        }
        if (str_contains($jobTitle, 'GELATO')) {
            return $departments['gelato'] ?? null;
        }
        if (str_contains($jobTitle, 'HOT KITCHEN') || str_contains($jobTitle, 'KITCHEN')) {
            return $departments['hot_kitchen'] ?? null;
        }
        if (str_contains($jobTitle, 'COFFEE') || str_contains($jobTitle, 'BARISTA')) {
            return $departments['corner_store'] ?? null;
        }
        if (str_contains($jobTitle, 'PRODUCTION') || str_contains($jobTitle, 'DATA PROCESSOR')) {
            return $departments['hot_kitchen'] ?? null;
        }

        // Sales departments
        if (str_contains($jobTitle, 'TILL') || str_contains($jobTitle, 'CASHIER')) {
            return $departments['till_sales'] ?? $departments['till_concession'] ?? null;
        }
        if (str_contains($jobTitle, 'CORNERSTORE') || str_contains($jobTitle, 'CORNER STORE')) {
            return $departments['corner_store'] ?? null;
        }
        if (str_contains($jobTitle, 'CONSESSION') || str_contains($jobTitle, 'CONCESSION')) {
            return $departments['concession'] ?? null;
        }
        if (str_contains($jobTitle, 'FLOOR MANAGER') || str_contains($jobTitle, 'SHOP FLOOR')) {
            return $departments['till_sales'] ?? $departments['till_concession'] ?? null;
        }
        if (str_contains($jobTitle, 'WAIT STAFF') || str_contains($jobTitle, 'LOBBY HOST')) {
            return $departments['till_sales'] ?? $departments['till_concession'] ?? null;
        }

        // Inventory
        if (str_contains($jobTitle, 'INVENTORY')) {
            return $departments['inventory/store'] ?? null;
        }
        if (str_contains($jobTitle, 'PROCUREMENT')) {
            return $departments['inventory/store'] ?? null;
        }

        // Support
        if (str_contains($jobTitle, 'FACILITY')) {
            return $departments['hr'] ?? null;
        }
        if (str_contains($jobTitle, 'SECURITY')) {
            return $departments['hr'] ?? null;
        }
        if (str_contains($jobTitle, 'CLEANER')) {
            return $departments['hr'] ?? null;
        }
        if (str_contains($jobTitle, 'DRIVER')) {
            return $departments['hr'] ?? null;
        }
        if (str_contains($jobTitle, 'SOCIAL MEDIA')) {
            return $departments['hr'] ?? null;
        }
        if (str_contains($jobTitle, 'KITCHEN ASSISTANT')) {
            return $departments['hot_kitchen'] ?? null;
        }

        // Default to HR
        return $departments['hr'] ?? null;
    }

    /**
     * Get role name for job title
     */
    private function getRoleForJobTitle(string $jobTitle): string
    {
        $jobTitle = strtoupper($jobTitle);

        // Management
        if (str_contains($jobTitle, 'HUMAN RESOURCE MANAGER')) {
            return 'HR Manager';
        }
        if (str_contains($jobTitle, 'PRODUCTION MANAGER')) {
            return 'Production Manager';
        }
        if (str_contains($jobTitle, 'INVENTORY MANAGER')) {
            return 'Inventory Manager';
        }
        if (str_contains($jobTitle, 'ACCOUNTANT') && !str_contains($jobTitle, 'COST')) {
            return 'Accountant';
        }
        if (str_contains($jobTitle, 'COST ACCOUNTANT')) {
            return 'Cost Accountant';
        }

        // Supervisors
        if (str_contains($jobTitle, 'FLOOR MANAGER') || str_contains($jobTitle, 'SHOP FLOOR MANAGER')) {
            return 'Floor Manager';
        }
        if (str_contains($jobTitle, 'ASSISTANT SHOP FLOOR MANAGER')) {
            return 'Assistant Shop Floor Manager';
        }
        if (str_contains($jobTitle, 'HEAD CHEF')) {
            return 'Head Chef';
        }
        if (str_contains($jobTitle, 'GELATO CHEF')) {
            return 'Gelato Chef';
        }
        if (str_contains($jobTitle, 'HOT KITCHEN CHEF') || str_contains($jobTitle, 'KITCHEN CHEF')) {
            return 'Hot Kitchen Chef';
        }
        if (str_contains($jobTitle, 'PASTRY CHEF') || str_contains($jobTitle, 'PASTRY')) {
            return 'Pastry Chef';
        }
        if (str_contains($jobTitle, 'COFFEE BARISTA TRAINER') || str_contains($jobTitle, 'BARISTA TRAINER')) {
            return 'Coffee Barista Trainer';
        }
        if (str_contains($jobTitle, 'TILL SUPERVISOR')) {
            return 'Till Supervisor';
        }
        if (str_contains($jobTitle, 'CORNERSTORE SUPERVISOR') || str_contains($jobTitle, 'CORNER STORE SUPERVISOR')) {
            return 'Cornerstore Supervisor';
        }
        if (str_contains($jobTitle, 'CONSESSION SUPERVISOR') || str_contains($jobTitle, 'CONCESSION SUPERVISOR')) {
            return 'Consession Supervisor';
        }
        if (str_contains($jobTitle, 'LOBBY HOST SUPERVISOR')) {
            return 'Lobby Host Supervisor';
        }
        if (str_contains($jobTitle, 'KITCHEN ASSISTANT SUPERVISOR')) {
            return 'Kitchen Assistant Supervisor';
        }
        if (str_contains($jobTitle, 'CLEANERS SUPERVISOR') || str_contains($jobTitle, 'CLEANER SUPERVISOR')) {
            return 'Cleaners Supervisor';
        }
        if (str_contains($jobTitle, 'CHIEF SECURITY OFFICER')) {
            return 'Chief Security Officer';
        }
        if (str_contains($jobTitle, 'FACILITY OFFICER')) {
            return 'Facility Officer';
        }
        if (str_contains($jobTitle, 'INVENTORY TEAM LEAD')) {
            return 'Inventory Team Lead';
        }
        if (str_contains($jobTitle, 'PROCUREMENT')) {
            return 'Procurement Officer';
        }

        // Staff
        if (str_contains($jobTitle, 'DATA PROCESSOR')) {
            return 'Data Processor';
        }
        if (str_contains($jobTitle, 'COFFEE BARISTA') || str_contains($jobTitle, 'BARISTA')) {
            return 'Coffee Barista';
        }
        if (str_contains($jobTitle, 'CASHIER')) {
            return 'Cashier';
        }
        if (str_contains($jobTitle, 'WAIT STAFF')) {
            return 'Wait Staff';
        }
        if (str_contains($jobTitle, 'LOBBY HOST')) {
            return 'Lobby Host';
        }
        if (str_contains($jobTitle, 'CONSESSION') || str_contains($jobTitle, 'CONCESSION')) {
            return 'Consession Attendant';
        }
        if (str_contains($jobTitle, 'TILL')) {
            return 'Sales Staff';
        }
        if (str_contains($jobTitle, 'KITCHEN ASSISTANT')) {
            return 'Kitchen Assistant';
        }
        if (str_contains($jobTitle, 'HOT KITCHEN')) {
            return 'Production Staff';
        }
        if (str_contains($jobTitle, 'PASTRY')) {
            return 'Production Staff';
        }
        if (str_contains($jobTitle, 'GELATO')) {
            return 'Production Staff';
        }
        if (str_contains($jobTitle, 'SOCIAL MEDIA')) {
            return 'Social Media Manager';
        }
        if (str_contains($jobTitle, 'DRIVER')) {
            return 'Driver';
        }
        if (str_contains($jobTitle, 'SECURITY')) {
            return 'Security Officer';
        }

        // Default
        return 'Sales Staff';
    }

    /**
     * Assign role to user
     */
    private function assignRole(User $user, string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $user->syncRoles([$role]);
        }
    }
}
