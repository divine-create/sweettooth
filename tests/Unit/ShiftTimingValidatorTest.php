<?php

use App\Models\Branch;
use App\Services\ShiftTimingValidator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftTimingValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_morning_shift_strict_6am_to_12pm_validation()
    {
        $validator = new ShiftTimingValidator;
        $branch = Branch::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Test Branch',
            'code' => 'TEST',
            'location' => 'Test Location',
            'is_active' => true,
        ]);

        // BEFORE 6 AM: Should fail
        $tooEarly = Carbon::today()->setTime(5, 59);
        $result = $validator->validateStrictTimeWindows('morning', $branch->id, $tooEarly);
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Too early', $result->getMessage());

        // AT 6 AM: Should pass
        $exactlyOnTime = Carbon::today()->setTime(6, 0);
        $result = $validator->validateStrictTimeWindows('morning', $branch->id, $exactlyOnTime);
        $this->assertTrue($result->isValid());

        // DURING WINDOW: Should pass
        $duringWindow = Carbon::today()->setTime(9, 30);
        $result = $validator->validateStrictTimeWindows('morning', $branch->id, $duringWindow);
        $this->assertTrue($result->isValid());

        // AT 12 PM: Should fail (end of window)
        $tooLate = Carbon::today()->setTime(12, 0);
        $result = $validator->validateStrictTimeWindows('morning', $branch->id, $tooLate);
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Too late', $result->getMessage());
    }

    public function test_afternoon_shift_strict_12pm_to_8pm_validation()
    {
        $validator = new ShiftTimingValidator;
        $branch = Branch::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Test Branch 2',
            'code' => 'TEST2',
            'location' => 'Test Location 2',
            'is_active' => true,
        ]);

        // BEFORE 12 PM: Should fail
        $tooEarly = Carbon::today()->setTime(11, 59);
        $result = $validator->validateStrictTimeWindows('afternoon', $branch->id, $tooEarly);
        $this->assertFalse($result->isValid());

        // AT 12 PM: Should pass
        $exactlyOnTime = Carbon::today()->setTime(12, 0);
        $result = $validator->validateStrictTimeWindows('afternoon', $branch->id, $exactlyOnTime);
        $this->assertTrue($result->isValid());

        // DURING WINDOW: Should pass
        $duringWindow = Carbon::today()->setTime(15, 30);
        $result = $validator->validateStrictTimeWindows('afternoon', $branch->id, $duringWindow);
        $this->assertTrue($result->isValid());

        // AFTER 8 PM: Should fail
        $tooLate = Carbon::today()->setTime(20, 1);
        $result = $validator->validateStrictTimeWindows('afternoon', $branch->id, $tooLate);
        $this->assertFalse($result->isValid());
    }

    public function test_full_time_shift_no_restrictions()
    {
        $validator = new ShiftTimingValidator;
        $branch = Branch::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Test Branch 3',
            'code' => 'TEST3',
            'location' => 'Test Location 3',
            'is_active' => true,
        ]);

        // Any time should pass for full_time
        $times = [
            Carbon::today()->setTime(2, 0),   // 2 AM
            Carbon::today()->setTime(14, 30), // 2:30 PM
            Carbon::today()->setTime(23, 59), // 11:59 PM
        ];

        foreach ($times as $time) {
            $result = $validator->validateStrictTimeWindows('full_time', $branch->id, $time);
            $this->assertTrue($result->isValid(), 'Full time should allow clock-in at '.$time->format('H:i'));
        }
    }
}
