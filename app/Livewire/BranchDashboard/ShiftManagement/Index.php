<?php

namespace App\Livewire\BranchDashboard\ShiftManagement;

use App\Livewire\BaseComponent;
use App\Models\Branch;
use App\Models\Shift;
use App\Models\ShiftConfiguration;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public function mount()
    {
        $this->b_id = current_branch_id();
    }

    protected function getModelClass(): string
    {
        return Shift::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    /**
     * Get statistics for shift management
     */
    public function getShiftStats()
    {
        $branchId = $this->b_id ?: current_branch_id();

        // Get today's shifts
        $todayShifts = Shift::where('branch_id', $branchId)
            ->whereDate('shift_date', today())
            ->get();

        // Get shift configurations for this branch
        $shiftConfigs = ShiftConfiguration::where('branch_id', $branchId)->get();

        // Get upcoming shifts (next 7 days)
        $upcomingShifts = Shift::where('branch_id', $branchId)
            ->whereBetween('shift_date', [today(), today()->addDays(7)])
            ->get();

        return [
            'total_configs' => $shiftConfigs->count(),
            'today_active' => $todayShifts->where('status', 'active')->count(),
            'today_total' => $todayShifts->count(),
            'upcoming_shifts' => $upcomingShifts->count(),
        ];
    }

    /**
     * Get recent shift configurations
     */
    public function getRecentConfigs()
    {
        $branchId = $this->b_id ?: current_branch_id();

        return ShiftConfiguration::where('branch_id', $branchId)
            ->latest()
            ->take(5)
            ->get();
    }

    /**
     * Get today's shifts
     */
    public function getTodaysShifts()
    {
        $branchId = $this->b_id ?: current_branch_id();

        return Shift::where('branch_id', $branchId)
            ->whereDate('shift_date', today())
            ->with(['employee', 'department'])
            ->latest('clock_in')
            ->take(10)
            ->get();
    }

    public function render()
    {
        $branch = current_branch();
        $stats = $this->getShiftStats();
        $recentConfigs = $this->getRecentConfigs();
        $todaysShifts = $this->getTodaysShifts();

        return view('livewire.branch-dashboard.shift-management.index', [
            'branch' => $branch,
            'stats' => $stats,
            'recentConfigs' => $recentConfigs,
            'todaysShifts' => $todaysShifts,
        ]);
    }
}