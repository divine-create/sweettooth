<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Shift;
use App\Models\ShiftConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateTestShift extends Command
{
    protected $signature = 'shifts:create-test
        {--type=night : Shift type (morning|afternoon|night|full_time)}
        {--branch= : Branch UUID (defaults to first branch)}
        {--user= : User UUID (defaults to first user)}
        {--shift-date= : Shift date (YYYY-MM-DD, defaults to today)}
        {--clock-in= : Clock-in time (HH:MM, defaults to now)}';

    protected $description = 'Create a test shift record for a user (useful for night shift testing)';

    public function handle(): int
    {
        $type = strtolower((string) $this->option('type') ?: 'night');
        $branchId = $this->option('branch') ?: Branch::query()->value('id');
        $userId = $this->option('user') ?: User::query()->value('id');

        if (! $branchId) {
            $this->error('No branches found. Provide --branch.');
            return 1;
        }
        if (! $userId) {
            $this->error('No users found. Provide --user.');
            return 1;
        }

        $user = User::find($userId);
        $config = ShiftConfiguration::query()
            ->where('branch_id', $branchId)
            ->where('shift_type', $type)
            ->where('is_active', true)
            ->first();

        if (! $config) {
            $this->error("No active shift configuration found for type '{$type}'.");
            return 1;
        }

        $shiftDate = $this->option('shift-date') ?: Carbon::today()->toDateString();
        $clockInTime = $this->option('clock-in');

        $clockIn = $clockInTime
            ? Carbon::parse("{$shiftDate} {$clockInTime}")
            : Carbon::now($config->timezone ?? config('app.timezone'));

        $departmentKey = $user?->department?->slug ?? $user?->department_id ?? 'dept';
        $shiftNumber = Shift::generateShiftNumber(
            (string) $departmentKey,
            (string) $userId,
            $clockIn,
            $config->shift_type
        );

        $startTimeRaw = $config->start_time instanceof Carbon
            ? $config->start_time->format('H:i:s')
            : (string) $config->start_time;
        $endTimeRaw = $config->end_time instanceof Carbon
            ? $config->end_time->format('H:i:s')
            : (string) $config->end_time;

        $shift = Shift::create([
            'branch_id' => $branchId,
            'department_id' => $user?->department_id,
            'employee_id' => $userId,
            'shift_number' => $shiftNumber,
            'shift_date' => $shiftDate,
            'shift_type' => $config->shift_type,
            'clock_in' => $clockIn,
            'clock_out' => null,
            'status' => 'active',
            'notes' => 'Test shift created via CLI',
            'metadata' => [
                'config_id' => $config->id,
                'config_name' => $config->name,
                'original_shift_type' => $config->shift_type,
                'mapped_shift_type' => $config->shift_type,
                'start_time' => $startTimeRaw,
                'end_time' => $endTimeRaw,
                'expected_end' => $endTimeRaw,
                'auto_clock_out_minutes' => $config->auto_clock_out_minutes,
                'max_overtime_hours' => $config->max_overtime_hours,
                'break_duration_minutes' => $config->break_duration_minutes,
            ],
        ]);

        $this->info("Created shift #{$shift->id} ({$shift->shift_type}) for user {$userId} on {$shiftDate}.");

        return 0;
    }
}
