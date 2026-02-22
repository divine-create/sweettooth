<?php

namespace App\Observers;

use App\Models\Payroll;
use App\Services\GlPostingService;
use Exception;

class PayrollObserver
{
    public function __construct(private GlPostingService $glPostingService)
    {
    }

    public function created(Payroll $payroll): void
    {
        $this->handleStatus($payroll, true);
    }

    public function updated(Payroll $payroll): void
    {
        $this->handleStatus($payroll, false);
    }

    private function handleStatus(Payroll $payroll, bool $isCreate): void
    {
        try {
            if ($payroll->status === 'approved' && $payroll->gl_posting_status === 'pending') {
                $this->glPostingService->postPayrollAccrual($payroll);
                $payroll->update([
                    'gl_posting_status' => 'posted',
                    'gl_posted_at' => now(),
                    'gl_posting_error' => null,
                ]);
            }

            if ($payroll->status === 'paid' && $payroll->gl_payment_posting_status === 'pending') {
                if ($payroll->gl_posting_status !== 'posted') {
                    $this->glPostingService->postPayrollAccrual($payroll);
                    $payroll->update([
                        'gl_posting_status' => 'posted',
                        'gl_posted_at' => now(),
                        'gl_posting_error' => null,
                    ]);
                }

                $this->glPostingService->postPayrollPayment($payroll);
                $payroll->update([
                    'gl_payment_posting_status' => 'posted',
                    'gl_payment_posted_at' => now(),
                    'gl_payment_posting_error' => null,
                ]);
            }
        } catch (Exception $e) {
            if ($payroll->status === 'approved') {
                $payroll->update([
                    'gl_posting_status' => 'failed',
                    'gl_posting_error' => $e->getMessage(),
                ]);
            } elseif ($payroll->status === 'paid') {
                $payroll->update([
                    'gl_payment_posting_status' => 'failed',
                    'gl_payment_posting_error' => $e->getMessage(),
                ]);
            }

            \Log::error('Payroll GL posting failed', [
                'payroll_id' => $payroll->id,
                'status' => $payroll->status,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
