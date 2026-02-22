<?php

namespace App\Livewire\BranchDashboard;

use App\Models\Appraisal;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class EmployeeAppraisals extends Component
{
    use WithPagination;

    public $search = '';
    public $departmentFilter = '';
    public $selectedEmployee = null;
    public $showAppraisalModal = false;
    public $selectedEmployeeForAppraisal = null;

    // Appraisal form data
    public $appraisalData = [
        'rating' => null,
        'comments' => '',
        'strengths' => '',
        'areas_for_improvement' => '',
        'goals' => ''
    ];

    protected $rules = [
        'appraisalData.rating' => 'required|integer|min:1|max:5',
        'appraisalData.comments' => 'required|string|max:1000',
        'appraisalData.strengths' => 'nullable|string|max:500',
        'appraisalData.areas_for_improvement' => 'nullable|string|max:500',
        'appraisalData.goals' => 'nullable|string|max:500'
    ];

    public function mount()
    {
        // Initialize component
    }



    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDepartmentFilter()
    {
        $this->resetPage();
    }

    public function openAppraisalModal($employeeId)
    {
        try {
            $branchId = request()->query('b_id')
                      ?? auth()->user()->branch_id
                      ?? auth()->user()->last_accessed_branch_id;

            // Check if appraisal already exists for this employee in active cycle
            $existingAppraisal = Appraisal::where('employee_id', $employeeId)
                ->whereHas('appraisalCycle', function($q) {
                    $q->where('status', 'active');
                })
                ->first();

            if ($existingAppraisal) {
                return redirect()->route('branch-dashboard.appraise-employee', [
                    'employee' => $employeeId,
                    'branch' => $branchId,
                    'appraisal' => $existingAppraisal->id
                ]);
            }

            return redirect()->route('branch-dashboard.appraise-employee', ['employee' => $employeeId, 'branch' => $branchId]);
        } catch (\Exception $e) {
            session()->flash('error', 'Unable to open appraisal form. Please try again.');
        }
    }

    public function startAppraisal()
    {
        if (!$this->selectedEmployeeForAppraisal) {
            session()->flash('error', 'Please select an employee first.');
            return;
        }

        $branchId = request()->query('b_id')
                  ?? auth()->user()->branch_id
                  ?? auth()->user()->last_accessed_branch_id;

        // Check if appraisal already exists for this employee in active cycle
        $existingAppraisal = Appraisal::where('employee_id', $this->selectedEmployeeForAppraisal)
            ->whereHas('appraisalCycle', function($q) {
                $q->where('status', 'active');
            })
            ->first();

        if ($existingAppraisal) {
            return redirect()->route('branch-dashboard.appraise-employee', [
                'employee' => $this->selectedEmployeeForAppraisal,
                'b_id' => $branchId,
                'appraisal' => $existingAppraisal->id
            ]);
        }

        return redirect()->route('branch-dashboard.appraise-employee', [
            'employee' => $this->selectedEmployeeForAppraisal,
            'b_id' => $branchId
        ]);
    }

    public function submitAppraisal()
    {
        $this->validate();

        // Create appraisal
        Appraisal::create([
            'employee_id' => $this->selectedEmployee->id,
            'manager_id' => auth()->id(),
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
        $this->selectedEmployee->update([
            'performance_rating' => $this->appraisalData['rating'],
            'last_performance_review_date' => now()
        ]);

        $this->showAppraisalModal = false;
        $this->resetAppraisalForm();
        session()->flash('message', 'Employee appraisal completed successfully.');
    }



    private function resetAppraisalForm()
    {
        $this->appraisalData = [
            'rating' => null,
            'comments' => '',
            'strengths' => '',
            'areas_for_improvement' => '',
            'goals' => ''
        ];
    }

    public function exportCsv()
    {
        $branchId = request()->query('b_id')
            ?? auth()->user()->branch_id
            ?? auth()->user()->last_accessed_branch_id;

        $employees = User::query()
            ->where('branch_id', $branchId)
            ->where('id', '!=', auth()->id())
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->departmentFilter, function ($query) {
                $query->where('department_id', $this->departmentFilter);
            })
            ->with(['department', 'appraisals.appraisalCycle'])
            ->orderBy('name')
            ->get();

        $csv = "Employee,Email,Department,Appraisal Cycle,Rating,Status,Completed At\n";

        foreach ($employees as $employee) {
            if ($employee->appraisals->isEmpty()) {
                $csv .= "\"{$employee->name}\",\"{$employee->email}\",\"".($employee->department->name ?? 'N/A')."\",\"\",\"\"," . "\"\",\"\"\n";
                continue;
            }

            foreach ($employee->appraisals as $appraisal) {
                $cycle = $appraisal->appraisalCycle?->name ?? 'N/A';
                $rating = $appraisal->final_rating ?? $appraisal->rating ?? '';
                $status = $appraisal->status ?? '';
                $completed = $appraisal->completed_at ? $appraisal->completed_at->format('Y-m-d') : '';

                $csv .= "\"{$employee->name}\",\"{$employee->email}\",\"".($employee->department->name ?? 'N/A')."\",\"{$cycle}\",\"{$rating}\",\"{$status}\",\"{$completed}\"\n";
            }
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'employee-appraisals-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        // Priority: b_id parameter > user's branch_id > user's last_accessed_branch_id
        $branchId = request()->query('b_id') 
                 ?? auth()->user()->branch_id 
                 ?? auth()->user()->last_accessed_branch_id;
        
        $employees = User::query()
            ->where('branch_id', $branchId)
            ->where('id', '!=', auth()->id()) // Don't show current user
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->departmentFilter, function ($query) {
                $query->where('department_id', $this->departmentFilter);
            })
            ->with(['department', 'roles'])
            ->orderBy('name')
            ->paginate(12);

        $departments = \App\Models\Department::where(function($q) use ($branchId) {
            $q->where('branch_id', $branchId)
              ->orWhereNull('branch_id');
        })->get();

        return view('livewire.branch-dashboard.employee-appraisals', [
            'employees' => $employees,
            'departments' => $departments,
            'branchId' => $branchId
        ]);
    }
}
