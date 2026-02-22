<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendShiftReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shifts:send-reminders
                          {--test : Send test reminders to specific users}
                          {--user=* : Send reminders only to specific user IDs}';

    protected $description = 'Send shift reminders and notifications to employees';

    protected int $remindersSent = 0;

    public function handle()
    {
        $this->info('🔔 Starting shift reminder process...');

        try {
            if ($this->option('test')) {
                $this->sendTestReminders();
            } else {
                $this->sendRegularReminders();
            }

            $this->info("✅ Sent {$this->remindersSent} shift reminders");

        } catch (\Exception $e) {
            $this->error('❌ Failed to send shift reminders: ' . $e->getMessage());
            \Log::error('SendShiftReminders command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function sendRegularReminders()
    {
        $notificationService = app(\App\Services\ShiftNotificationService::class);
        $results = $notificationService->checkAndSendNotifications();

        $this->remindersSent = $results['warnings'] + $results['auto_clock_warnings'] + $results['reminders'];

        $this->line("📊 Summary:");
        $this->line("   - Shift ending warnings: {$results['warnings']}");
        $this->line("   - Auto clock out warnings: {$results['auto_clock_warnings']}");
        $this->line("   - Shift reminders: {$results['reminders']}");
    }

    protected function sendTestReminders()
    {
        $this->info('🧪 Sending test reminders...');

        $userIds = $this->option('user');
        $users = empty($userIds)
            ? \App\Models\User::limit(3)->get()
            : \App\Models\User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $this->sendTestReminderToUser($user);
        }
    }

    protected function sendTestReminderToUser($user)
    {
        $shiftTypes = ['morning', 'afternoon', 'full_time'];
        $shiftType = $shiftTypes[array_rand($shiftTypes)];
        $scheduledTime = now()->addHours(rand(1, 8));

        try {
            $notificationService = app(\App\Services\ShiftNotificationService::class);
            $notificationService->sendShiftReminder($user, $shiftType, $scheduledTime);

            $this->line("📧 Sent test reminder to {$user->name} ({$user->email}) for {$shiftType} shift");
            $this->remindersSent++;

        } catch (\Exception $e) {
            $this->error("❌ Failed to send test reminder to {$user->name}: " . $e->getMessage());
        }
    }
}
