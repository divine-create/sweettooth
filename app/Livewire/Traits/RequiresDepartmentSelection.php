<?php

namespace App\Livewire\Traits;

use App\Models\Department;

trait RequiresDepartmentSelection
{
    public bool $showDepartmentModal = false;
    public $availableDepartments = [];
    public $selectedDepartmentId = null;
    public ?string $pendingDepartmentAction = null;

    protected function initDepartments($branchId): void
    {
        $this->availableDepartments = Department::where(function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->orderBy('name')
            ->get();
        $this->selectedDepartmentId = $this->departmentId
            ?? session('selected_department_id')
            ?? $this->selectedDepartmentId;
    }

    protected function ensureDepartmentSelected(string $action): bool
    {
        if ($this->departmentId) {
            return true;
        }

        $branchId = $this->b_id ?? $this->branchId ?? current_branch_id();
        if (empty($this->availableDepartments)) {
            $this->initDepartments($branchId);
        }

        $this->selectedDepartmentId = $this->selectedDepartmentId ?? session('selected_department_id');
        $this->pendingDepartmentAction = $action;
        $this->showDepartmentModal = true;

        return false;
    }

    public function confirmDepartmentSelection(): void
    {
        if (! $this->selectedDepartmentId) {
            $this->toast()->error('Please select a department')->send();
            return;
        }

        $this->departmentId = $this->selectedDepartmentId;
        session(['selected_department_id' => $this->selectedDepartmentId]);

        $action = $this->pendingDepartmentAction;
        $this->pendingDepartmentAction = null;
        $this->showDepartmentModal = false;

        if ($action === 'preview') {
            $this->generatePreview();
        } elseif ($action === 'generate') {
            $this->generateReport();
        }
    }

    public function updatedSelectedDepartmentId($value): void
    {
        if (! $value) {
            return;
        }

        $this->departmentId = $value;
        session(['selected_department_id' => $value]);
    }
}
