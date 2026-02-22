<?php

namespace App\Livewire\BranchDashboard\HR;

use App\Models\AppraisalCycle;
use App\Models\Department;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use function is_super_admin;

#[Layout('components.layouts.app.branch-dashboard')]
class AppraisalCycles extends Component
{
    use WithPagination;

    public $b_id;
    public $search = '';
    public $statusFilter = 'all';

    public $showCreateModal = false;
    public $showEditModal = false;

    public $editingCycle;
    public $newCycle = [
        'name' => '',
        'start_date' => '',
        'end_date' => '',
        'due_date' => '',
        'status' => 'planned',
        'department_id' => null
    ];

    protected $rules = [
        'newCycle.name' => 'required|string|max:255',
        'newCycle.start_date' => 'required|date',
        'newCycle.end_date' => 'required|date|after_or_equal:newCycle.start_date',
        'newCycle.due_date' => 'required|date|after:newCycle.start_date|before_or_equal:newCycle.end_date',
        'newCycle.status' => 'required|in:planned,active,completed',
        'newCycle.department_id' => 'nullable|exists:departments,id'
    ];

    public function mount()
    {
        $this->b_id = request()->query('b_id') ?? current_branch_id();
        $this->resetNewCycleForm();
    }

    public function resetNewCycleForm()
    {
        $this->newCycle = [
            'name' => '',
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->endOfMonth()->format('Y-m-d'),
            'due_date' => now()->endOfMonth()->subDays(3)->format('Y-m-d'),
            'status' => 'planned',
            'department_id' => null
        ];
    }

    public function createCycle()
    {
        $this->validate();

        AppraisalCycle::create($this->newCycle);

        $this->resetNewCycleForm();
        $this->showCreateModal = false;
        session()->flash('message', 'Appraisal cycle created successfully.');
    }

    public function editCycle(AppraisalCycle $cycle)
    {
        $this->editingCycle = $cycle;
        $this->newCycle = [
            'name' => $cycle->name,
            'start_date' => $cycle->start_date->format('Y-m-d'),
            'end_date' => $cycle->end_date->format('Y-m-d'),
            'due_date' => $cycle->due_date->format('Y-m-d'),
            'status' => $cycle->status,
            'department_id' => $cycle->department_id
        ];
        $this->showEditModal = true;
    }

    public function updateCycle()
    {
        $this->validate();

        $this->editingCycle->update($this->newCycle);

        $this->resetNewCycleForm();
        $this->showEditModal = false;
        $this->editingCycle = null;
        session()->flash('message', 'Appraisal cycle updated successfully.');
    }

    public function activateCycle(AppraisalCycle $cycle)
    {
        AppraisalCycle::where('status', 'active')
            ->update(['status' => 'planned']);

        $cycle->update(['status' => 'active']);
        session()->flash('message', 'Appraisal cycle activated successfully.');
    }

    public function completeCycle(AppraisalCycle $cycle)
    {
        $cycle->update(['status' => 'completed']);
        session()->flash('message', 'Appraisal cycle completed successfully.');
    }

    public function deleteCycle(AppraisalCycle $cycle)
    {
        $cycle->delete();
        session()->flash('message', 'Appraisal cycle deleted successfully.');
    }

    public function getCyclesProperty()
    {
        $query = AppraisalCycle::with('department');

        if (is_super_admin() && !$this->b_id) {
            // Super admin with no branch selected sees all cycles
        } else {
            $query->where(function($q) {
                $q->whereNull('department_id') // Global cycles
                  ->orWhereHas('department', function($subQ) {
                      $subQ->where('branch_id', $this->b_id);
                  });
            });
        }

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function getDepartmentsProperty()
    {
        if (is_super_admin()) {
            // Super admin can see departments from all branches or current selected branch
            if ($this->b_id) {
                return Department::where('branch_id', $this->b_id)->get();
            } else {
                return Department::all();
            }
        } else {
            return Department::where('branch_id', $this->b_id)->get();
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.h-r.appraisals.cycles.index', [
            'departments' => $this->departments
        ]);
    }
}
