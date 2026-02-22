<?php

use App\Models\Branch;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shift_selection_route_accessible_without_active_shift()
    {
        $employee = User::create([
            'name' => 'Test Employee',
            'email' => 'employee@test.com',
            'password' => bcrypt('password'),
        ]);
        $employee->assignRole('employee');
        $branch = Branch::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Test Branch',
            'code' => 'TEST',
            'location' => 'Test Location',
            'is_active' => true,
        ]);

        $response = $this->actingAs($employee)
            ->get(route('branch-dashboard.select_shift', ['b_id' => $branch->id]));

        $response->assertSuccessful();
    }

    public function test_dashboard_requires_active_shift()
    {
        $employee = User::create([
            'name' => 'Test Employee 2',
            'email' => 'employee2@test.com',
            'password' => bcrypt('password'),
        ]);
        $employee->assignRole('employee');
        $branch = Branch::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Test Branch 2',
            'code' => 'TEST2',
            'location' => 'Test Location 2',
            'is_active' => true,
        ]);

        $response = $this->actingAs($employee)
            ->get(route('branch-dashboard.index', ['b_id' => $branch->id]));

        $response->assertRedirect(route('branch-dashboard.select_shift', ['b_id' => $branch->id]));
    }

    public function test_super_admin_bypasses_shift_requirement()
    {
        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('super-admin');
        $branch = Branch::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Test Branch 3',
            'code' => 'TEST3',
            'location' => 'Test Location 3',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('branch-dashboard.index', ['b_id' => $branch->id]));

        $response->assertSuccessful();
    }

    public function test_employee_with_active_shift_can_access_dashboard()
    {
        $employee = User::create([
            'name' => 'Test Employee 4',
            'email' => 'employee4@test.com',
            'password' => bcrypt('password'),
        ]);
        $employee->assignRole('employee');
        $branch = Branch::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Test Branch 4',
            'code' => 'TEST4',
            'location' => 'Test Location 4',
            'is_active' => true,
        ]);

        // Create active shift
        Shift::create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'shift_date' => Carbon::today(),
            'shift_type' => 'morning',
            'clock_in' => Carbon::now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($employee)
            ->get(route('branch-dashboard.index', ['b_id' => $branch->id]));

        $response->assertSuccessful();
    }
}
