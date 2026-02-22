<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentCategory;
use App\Models\SalesShift;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftNumberUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_shift_numbers_are_unique_for_same_day_and_context(): void
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB01',
            'location' => 'Test Location',
            'email' => 'branch@example.com',
        ]);

        $category = DepartmentCategory::create([
            'name' => 'Production',
            'description' => 'Production Department',
        ]);

        $department = Department::create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'name' => 'Production Department',
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'password',
            'branch_id' => $branch->id,
        ]);

        $shiftNumbers = [];
        for ($i = 0; $i < 3; $i++) {
            $shiftNumbers[] = Shift::create([
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'employee_id' => $user->id,
                'shift_number' => Shift::generateShiftNumber(
                    (string) $department->id,
                    (string) $user->id,
                    now(),
                    'morning'
                ),
                'shift_date' => today(),
                'shift_type' => 'morning',
                'status' => 'active',
            ])->shift_number;
        }

        $this->assertCount(3, array_unique($shiftNumbers));
    }

    public function test_sales_shift_numbers_are_unique_for_same_day_and_context(): void
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB01',
            'location' => 'Test Location',
            'email' => 'branch@example.com',
        ]);

        $category = DepartmentCategory::create([
            'name' => 'Sales',
            'description' => 'Sales Department',
        ]);

        $department = Department::create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'name' => 'Sales Department',
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'sales@example.com',
            'password' => 'password',
            'branch_id' => $branch->id,
        ]);

        $shiftNumbers = [];
        for ($i = 0; $i < 3; $i++) {
            $shiftNumbers[] = SalesShift::create([
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'employee_id' => $user->id,
                'shift_number' => SalesShift::generateShiftNumber(
                    (string) $department->id,
                    (string) $user->id,
                    now(),
                    'morning'
                ),
                'shift_date' => today(),
                'shift_type' => 'morning',
                'status' => 'active',
            ])->shift_number;
        }

        $this->assertCount(3, array_unique($shiftNumbers));
    }
}
