<?php

namespace App\Livewire\BranchDashboard;

use App\Models\Appraisal;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class EmployeeAppraisalHistory extends Component
{
    public $employee;
    public $b_id;

    public function mount($employee)
    {
        $this->b_id = request()->query('b_id') ?? current_branch_id();
        $this->employee = User::findOrFail($employee);
    }

    public function getAppraisalsProperty()
    {
        return Appraisal::where('employee_id', $this->employee->id)
            ->with(['appraisalCycle', 'manager'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.employee-appraisal-history', [
            'appraisals' => $this->appraisals,
            'currentRating' => $this->employee->performance_rating,
            'lastReviewDate' => $this->employee->last_performance_review_date
        ]);
    }
}