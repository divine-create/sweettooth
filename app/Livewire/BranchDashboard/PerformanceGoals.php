<?php

namespace App\Livewire\BranchDashboard;

use App\Models\PerformanceGoal;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class PerformanceGoals extends Component
{
    use WithPagination;

    public $search = '';
    public $employeeFilter = '';
    public $statusFilter = 'all';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $editingGoal;

    public $goalData = [
        'employee_id' => null,
        'title' => '',
        'description' => '',
        'category' => 'individual', // individual, team, organizational
        'type' => 'operational', // development, operational, strategic
        'priority' => 'medium', // high, medium, low
        'target_value' => 0,
        'current_value' => 0,
        'unit' => 'percentage', // percentage, number, currency
        'start_date' => '',
        'target_date' => '',
        'status' => 'not_started'
    ];

    protected $rules = [
        'goalData.employee_id' => 'required|exists:users,id',
        'goalData.title' => 'required|string|max:255',
        'goalData.description' => 'required|string|max:1000',
        'goalData.category' => 'required|in:individual,team,organizational',
        'goalData.type' => 'required|in:development,operational,strategic',
        'goalData.priority' => 'required|in:high,medium,low',
        'goalData.target_value' => 'required|numeric|min:0',
        'goalData.current_value' => 'numeric|min:0',
        'goalData.unit' => 'required|in:percentage,number,currency',
        'goalData.start_date' => 'required|date',
        'goalData.target_date' => 'required|date|after:goalData.start_date',
        'goalData.status' => 'required|in:not_started,in_progress,completed,cancelled'
    ];

    public function mount()
    {
        $this->goalData['start_date'] = now()->format('Y-m-d');
        $this->goalData['target_date'] = now()->addMonths(3)->format('Y-m-d');
    }

    public function createGoal()
    {
        $this->validate();

        PerformanceGoal::create([
            'employee_id' => $this->goalData['employee_id'],
            'title' => $this->goalData['title'],
            'description' => $this->goalData['description'],
            'category' => $this->goalData['category'],
            'type' => $this->goalData['type'],
            'priority' => $this->goalData['priority'],
            'target_value' => $this->goalData['target_value'],
            'current_value' => $this->goalData['current_value'],
            'unit' => $this->goalData['unit'],
            'start_date' => $this->goalData['start_date'],
            'target_date' => $this->goalData['target_date'],
            'status' => $this->goalData['status'],
            'last_updated_by' => auth()->id()
        ]);

        $this->reset('goalData', 'showCreateModal');
        session()->flash('message', 'Performance goal created successfully.');
    }

    public function editGoal(PerformanceGoal $goal)
    {
        $this->editingGoal = $goal;
        $this->goalData = [
            'employee_id' => $goal->employee_id,
            'title' => $goal->title,
            'description' => $goal->description,
            'category' => $goal->category,
            'type' => $goal->type,
            'priority' => $goal->priority,
            'target_value' => $goal->target_value,
            'current_value' => $goal->current_value,
            'unit' => $goal->unit,
            'start_date' => $goal->start_date->format('Y-m-d'),
            'target_date' => $goal->target_date->format('Y-m-d'),
            'status' => $goal->status
        ];
        $this->showEditModal = true;
    }

    public function updateGoal()
    {
        $this->validate();

        $this->editingGoal->update([
            'employee_id' => $this->goalData['employee_id'],
            'title' => $this->goalData['title'],
            'description' => $this->goalData['description'],
            'category' => $this->goalData['category'],
            'type' => $this->goalData['type'],
            'priority' => $this->goalData['priority'],
            'target_value' => $this->goalData['target_value'],
            'current_value' => $this->goalData['current_value'],
            'unit' => $this->goalData['unit'],
            'start_date' => $this->goalData['start_date'],
            'target_date' => $this->goalData['target_date'],
            'status' => $this->goalData['status'],
            'last_updated_by' => auth()->id()
        ]);

        $this->reset('goalData', 'showEditModal', 'editingGoal');
        session()->flash('message', 'Performance goal updated successfully.');
    }

    public function updateProgress(PerformanceGoal $goal, $progress)
    {
        $goal->update([
            'current_value' => $progress,
            'last_updated_by' => auth()->id()
        ]);

        // Auto-complete if target reached
        if ($progress >= $goal->target_value && $goal->status !== 'completed') {
            $goal->update(['status' => 'completed']);
        }

        session()->flash('message', 'Goal progress updated successfully.');
    }

    public function deleteGoal(PerformanceGoal $goal)
    {
        $goal->delete();
        session()->flash('message', 'Performance goal deleted successfully.');
    }

    public function getGoalsProperty()
    {
        $query = PerformanceGoal::with(['employee', 'lastUpdater']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('employee', function ($employeeQuery) {
                      $employeeQuery->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->employeeFilter) {
            $query->where('employee_id', $this->employeeFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function getEmployeesProperty()
    {
        $branchId = request()->query('b_id') ?? auth()->user()->branch_id ?? auth()->user()->last_accessed_branch_id;

        return User::where('branch_id', $branchId)->get();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.performance-goals');
    }
}