<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class AlertManager
{
    /**
     * Trigger a system alert
     */
    public function triggerAlert(string $alertType, array $data = []): void
    {
        $config = config("shift-monitoring.alerts.{$alertType}");

        if (!$config) {
            Log::warning("Unknown alert type: {$alertType}");
            return;
        }

        $severity = $this->determineSeverity($alertType, $data, $config);

        if (!$severity) {
            return; // No alert needed
        }

        $alertData = array_merge($config, [
            'alert_type' => $alertType,
            'severity' => $severity,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment()
        ]);

        $this->sendAlerts($severity, $alertData);
        $this->logAlert($alertData);
    }

    /**
     * Determine alert severity based on thresholds
     */
    private function determineSeverity(string $alertType, array $data, array $config): ?string
    {
        $value = $data['value'] ?? null;

        if ($value >= ($config['critical'] ?? PHP_INT_MAX)) {
            return 'critical';
        } elseif ($value >= ($config['warning'] ?? PHP_INT_MAX)) {
            return 'warning';
        }

        return null;
    }

    /**
     * Send alerts through configured channels
     */
    private function sendAlerts(string $severity, array $alertData): void
    {
        $channels = config("shift-monitoring.escalation.{$severity}", ['email']);

        foreach ($channels as $channel) {
            try {
                match($channel) {
                    'slack' => $this->sendSlackAlert($alertData),
                    'email' => $this->sendEmailAlert($alertData),
                    'sms' => $this->sendSmsAlert($alertData),
                    default => Log::warning("Unknown alert channel: {$channel}")
                };
            } catch (\Exception $e) {
                Log::error("Failed to send alert via {$channel}", [
                    'error' => $e->getMessage(),
                    'alert_type' => $alertData['alert_type']
                ]);
            }
        }
    }

    /**
     * Send Slack alert
     */
    private function sendSlackAlert(array $alertData): void
    {
        $webhook = config('shift-monitoring.notification_channels.slack');

        if (!$webhook) {
            return;
        }

        $emoji = match($alertData['severity']) {
            'critical' => '🚨',
            'warning' => '⚠️',
            default => 'ℹ️'
        };

        $payload = [
            'text' => "{$emoji} Shift System Alert: {$alertData['message']}",
            'attachments' => [
                [
                    'color' => match($alertData['severity']) {
                        'critical' => 'danger',
                        'warning' => 'warning',
                        default => 'good'
                    },
                    'fields' => [
                        [
                            'title' => 'Alert Type',
                            'value' => $alertData['alert_type'],
                            'short' => true
                        ],
                        [
                            'title' => 'Environment',
                            'value' => $alertData['environment'],
                            'short' => true
                        ],
                        [
                            'title' => 'Timestamp',
                            'value' => $alertData['timestamp'],
                            'short' => true
                        ]
                    ]
                ]
            ]
        ];

        // Send to Slack using HTTP client
        \Http::post($webhook, $payload);
    }

    /**
     * Send email alert
     */
    private function sendEmailAlert(array $alertData): void
    {
        $recipients = config('shift-monitoring.notification_channels.email');

        if (!$recipients) {
            return;
        }

        // For now, just log the alert. In production, you'd create a proper mail class
        Log::warning('Email alert triggered', $alertData);
    }

    /**
     * Send SMS alert
     */
    private function sendSmsAlert(array $alertData): void
    {
        if (!config('shift-monitoring.notification_channels.sms')) {
            return;
        }

        // Implementation would depend on SMS provider (Twilio, AWS SNS, etc.)
        Log::warning('SMS alert triggered', $alertData);
    }

    /**
     * Log alert to database and logs
     */
    private function logAlert(array $alertData): void
    {
        Log::log(
            match($alertData['severity']) {
                'critical' => 'critical',
                'warning' => 'warning',
                default => 'info'
            },
            'Shift system alert triggered',
            $alertData
        );

        // Store in database for historical tracking
        try {
            DB::table('system_alerts')->insert([
                'alert_type' => $alertData['alert_type'],
                'severity' => $alertData['severity'],
                'message' => $alertData['message'] ?? 'System alert',
                'data' => json_encode($alertData['data']),
                'environment' => $alertData['environment'],
                'created_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store alert in database', [
                'alert' => $alertData,
                'error' => $e->getMessage()
            ]);
        }
    }
}