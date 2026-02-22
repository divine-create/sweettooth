<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ShiftConfiguration;
use Illuminate\Database\Seeder;

class ShiftConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding shift configurations...');

        $branch = Branch::where('code', 'PHC-002')->first();
        if (! $branch) {
            $this->command->warn('Branch not found. Skipping shift configuration seeding.');
            return;
        }

        $created = 0;
        $skipped = 0;

        // Define shift configurations for the branch
        $configs = [
            'morning' => ['name' => 'Morning Shift', 'start' => '08:00', 'end' => '16:00'],
            'afternoon' => ['name' => 'Afternoon Shift', 'start' => '16:00', 'end' => '00:00'],
        ];

        foreach ($configs as $shiftType => $config) {
            $existing = ShiftConfiguration::query()
                ->where('branch_id', $branch->id)
                ->where('shift_type', $shiftType)
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            ShiftConfiguration::create([
                'branch_id' => $branch->id,
                'shift_type' => $shiftType,
                'name' => $config['name'],
                'start_time' => $config['start'],
                'end_time' => $config['end'],
                'clock_in_start' => date('H:i', strtotime($config['start'] . ' - 30 minutes')),
                'clock_in_end' => date('H:i', strtotime($config['start'] . ' + 30 minutes')),
                'auto_clock_out_minutes' => 30,
                'is_active' => true,
                'max_overtime_hours' => 2,
                'break_duration_minutes' => 60,
                'timezone' => 'Africa/Lagos',
            ]);
            $created++;
        }

        $this->command->info("Shift configurations created: {$created}");
        $this->command->info("Shift configurations skipped: {$skipped}");
    }
}
