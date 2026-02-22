<?php

namespace App\Livewire\BranchDashboard;

use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\User;
use function is_super_admin;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class AppraiseEmployee extends Component
{
    public $employee;
    public $branch;
    public $appraisal;
    public $currentStage = 'manager_review'; // draft, self_review, manager_review, hr_review, completed

    public $selectedCycle;
    public $appraisalData = [
        'rating' => null,
        'comments' => '',
        'strengths' => '',
        'areas_for_improvement' => '',
        'goals' => ''
    ];

    // Self-assessment data
    public $selfAssessment = [
        'accomplishments' => '',
        'challenges' => '',
        'goals_achieved' => '',
        'development_needs' => '',
        'self_rating' => null
    ];

    // Manager assessment data
    public $managerAssessment = [
        'rating' => null,
        'comments' => '',
        'strengths' => '',
        'areas_for_improvement' => '',
        'goals' => '',
        'development_plan' => ''
    ];

    // HR assessment data
    public $hrAssessment = [
        'rating' => null,
        'comments' => '',
        'calibration_notes' => '',
        'final_decision' => ''
    ];



    public function mount($employee)
    {
        $this->employee = User::findOrFail($employee);
        $this->branch = request()->query('b_id')
            ?? current_branch_id()
            ?? auth()->user()->branch_id
            ?? auth()->user()->last_accessed_branch_id
            ?? $this->employee->branch_id;

        // Verify the employee belongs to this branch
        if ($this->branch && $this->employee->branch_id !== $this->branch) {
            abort(403, 'Unauthorized access to this employee.');
        }

        // Set default cycle
        $defaultCycle = AppraisalCycle::active()->first();
        $this->selectedCycle = $defaultCycle ? $defaultCycle->id : null;


    }

    public function submitAppraisal()
    {
        $this->validate([
            'selectedCycle' => 'required',
            'appraisalData.rating' => 'required|integer|min:1|max:5',
            'appraisalData.comments' => 'required|string|min:10',
            'appraisalData.strengths' => 'required|string|min:10',
            'appraisalData.areas_for_improvement' => 'required|string|min:10',
            'appraisalData.goals' => 'required|string|min:10',
        ]);

        // Create appraisal
        Appraisal::create([
            'employee_id' => $this->employee->id,
            'manager_id' => auth()->id(),
            'appraisal_cycle_id' => $this->selectedCycle,
            'status' => 'completed',
            'final_rating' => $this->appraisalData['rating'],
            'final_comments' => $this->appraisalData['comments'],
            'development_plan' => [
                'strengths' => $this->appraisalData['strengths'],
                'areas_for_improvement' => $this->appraisalData['areas_for_improvement'],
                'goals' => $this->appraisalData['goals']
            ],
            'completed_at' => now()
        ]);

        // Update employee's performance rating
        $this->employee->update([
            'performance_rating' => $this->appraisalData['rating'],
            'last_performance_review_date' => now()
        ]);

        session()->flash('message', 'Employee appraisal completed successfully.');
        return redirect()->route('branch-dashboard.employee-appraisals', ['b_id' => $this->branch]);
    }

    public function render()
    {
        $query = AppraisalCycle::active();

        // HR selects any active cycle (no department scoping)

        $cycles = $query->get();

        return view('livewire.branch-dashboard.appraise-employee', [
            'employee' => $this->employee,
            'cycles' => $cycles
        ]);
    }
}
