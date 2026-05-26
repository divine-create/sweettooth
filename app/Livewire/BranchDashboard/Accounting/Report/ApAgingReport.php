<?php

namespace App\Livewire\BranchDashboard\Accounting\Report;

use App\Models\Purchase;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class ApAgingReport extends Component
{
    public string $asOfDate = '';

    public function mount(): void
    {
        $this->asOfDate = now()->toDateString();
    }

    public function render()
    {
        $asOf = Carbon::parse($this->asOfDate);
        $branchId = session('selected_branch_id');

        $unpaid = Purchase::query()
            ->where('branch_id', $branchId)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->with('supplier')
            ->orderBy('due_date')
            ->get();

        $buckets = [
            'current'  => ['label' => 'Current (not yet due)',  'items' => collect(), 'total' => 0],
            '1_30'     => ['label' => '1–30 days overdue',      'items' => collect(), 'total' => 0],
            '31_60'    => ['label' => '31–60 days overdue',     'items' => collect(), 'total' => 0],
            '61_90'    => ['label' => '61–90 days overdue',     'items' => collect(), 'total' => 0],
            'over_90'  => ['label' => '90+ days overdue',       'items' => collect(), 'total' => 0],
        ];

        foreach ($unpaid as $purchase) {
            $due = Carbon::parse($purchase->due_date);
            $daysOverdue = $due->lt($asOf) ? $due->diffInDays($asOf) : 0;

            $balance = (float) ($purchase->total_cost ?? 0);

            if ($daysOverdue === 0) {
                $key = 'current';
            } elseif ($daysOverdue <= 30) {
                $key = '1_30';
            } elseif ($daysOverdue <= 60) {
                $key = '31_60';
            } elseif ($daysOverdue <= 90) {
                $key = '61_90';
            } else {
                $key = 'over_90';
            }

            $purchase->days_overdue = $daysOverdue;
            $purchase->outstanding_balance = $balance;
            $buckets[$key]['items']->push($purchase);
            $buckets[$key]['total'] += $balance;
        }

        $grandTotal = collect($buckets)->sum('total');

        return view('livewire.branch-dashboard.accounting.report.ap-aging-report', [
            'buckets'    => $buckets,
            'grandTotal' => $grandTotal,
            'asOf'       => $asOf,
        ]);
    }
}
