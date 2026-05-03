<?php

namespace App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement;

use App\Livewire\BaseComponent;
use App\Models\LeaveType;
use App\Services\LeaveAuditService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class LeaveTypes extends BaseComponent
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 20;

    public ?string $search = null;

    // Form fields
    public $showModal = false;

    public $editMode = false;

    public $leaveTypeId = null;

    public $name = '';

    public $code = '';

    public $description = '';

    public $default_days_per_year = 0;

    public $requires_approval = true;

    public $requires_document = false;

    public $max_consecutive_days = null;

    public $min_notice_days = 0;

    public $is_paid = true;

    public $is_active = true;

    public $color = '#3b82f6';

    // Table headers
    public array $headers = [
        ['index' => 'name', 'label' => 'Leave Type'],
        ['index' => 'code', 'label' => 'Code'],
        ['index' => 'default_days', 'label' => 'Default Days/Year'],
        ['index' => 'paid', 'label' => 'Paid'],
        ['index' => 'requires_approval', 'label' => 'Requires Approval'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'action', 'label' => 'Action'],
    ];

    protected function getModelClass(): string
    {
        return LeaveType::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        return LeaveType::query();
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function getRowsProperty()
    {
        $query = LeaveType::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('name')->paginate((int) $this->quantity);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $leaveType = LeaveType::find($id);

        if (! $leaveType) {
            $this->toast()->error('Leave type not found.')->send();

            return;
        }

        $this->editMode = true;
        $this->leaveTypeId = $leaveType->id;
        $this->name = $leaveType->name;
        $this->code = $leaveType->code;
        $this->description = $leaveType->description;
        $this->default_days_per_year = $leaveType->default_days_per_year;
        $this->requires_approval = $leaveType->requires_approval;
        $this->requires_document = $leaveType->requires_document;
        $this->max_consecutive_days = $leaveType->max_consecutive_days;
        $this->min_notice_days = $leaveType->min_notice_days;
        $this->is_paid = $leaveType->is_paid;
        $this->is_active = $leaveType->is_active;
        $this->color = $leaveType->color;

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->leaveTypeId = null;
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->default_days_per_year = 0;
        $this->requires_approval = true;
        $this->requires_document = false;
        $this->max_consecutive_days = null;
        $this->min_notice_days = 0;
        $this->is_paid = true;
        $this->is_active = true;
        $this->color = '#3b82f6';
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:leave_types,code,'.$this->leaveTypeId,
            'default_days_per_year' => 'required|integer|min:0',
            'max_consecutive_days' => 'nullable|integer|min:1',
            'min_notice_days' => 'required|integer|min:0',
        ]);

        try {
            $data = [
                'name' => $this->name,
                'code' => strtoupper($this->code),
                'description' => $this->description,
                'default_days_per_year' => $this->default_days_per_year,
                'requires_approval' => $this->requires_approval,
                'requires_document' => $this->requires_document,
                'max_consecutive_days' => $this->max_consecutive_days,
                'min_notice_days' => $this->min_notice_days,
                'is_paid' => $this->is_paid,
                'is_active' => $this->is_active,
                'color' => $this->color,
            ];

            $actor = current_actor();

            if ($this->editMode) {
                $leaveType = LeaveType::find($this->leaveTypeId);
                $leaveType->update($data);
                $message = 'Leave type updated successfully!';
            } else {
                $leaveType = LeaveType::create($data);
                LeaveAuditService::logLeaveTypeCreation($leaveType, $actor);
                $message = 'Leave type created successfully!';
            }

            $this->toast()->success($message)->send();
            $this->closeModal();
            $this->resetPage();

        } catch (\Exception $e) {
            $this->toast()->error('Error: '.$e->getMessage())->send();
        }
    }

    public function toggleStatus($id)
    {
        $leaveType = LeaveType::find($id);

        if (! $leaveType) {
            $this->toast()->error('Leave type not found.')->send();

            return;
        }

        $leaveType->is_active = ! $leaveType->is_active;
        $leaveType->save();

        $status = $leaveType->is_active ? 'activated' : 'deactivated';
        $this->toast()->success("Leave type {$status} successfully!")->send();
    }

    public function delete($id)
    {
        try {
            $leaveType = LeaveType::find($id);

            if (! $leaveType) {
                $this->toast()->error('Leave type not found.')->send();

                return;
            }

            // Check if there are any leave applications
            if ($leaveType->leaveApplications()->count() > 0) {
                $this->toast()->error('Cannot delete leave type with existing applications. Deactivate instead.')->send();

                return;
            }

            $leaveType->delete();
            $this->toast()->success('Leave type deleted successfully!')->send();
            $this->resetPage();

        } catch (\Exception $e) {
            $this->toast()->error('Error deleting leave type: '.$e->getMessage())->send();
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.employee-module.leave-management.leave-types', [
            'rows' => $this->rows,
        ]);
    }
}
