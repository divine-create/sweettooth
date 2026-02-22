<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\TableManagement;

use App\Livewire\BaseComponent;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Table;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\{Layout, Url, Computed};

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    #[Url(keep: true)]
    public ?string $salesDeptSlug = null;

    public ?string $branchId = null;
    public ?int $departmentId = null;
    public string $departmentName = 'Table Management';
    public string $branchName = '';

    // Table form properties
    public bool $showTableModal = false;
    public ?int $editingTableId = null;
    public string $tableNumber = '';
    public string $tableName = '';
    public int $tableCapacity = 4;
    public string $tableStatus = 'available';
    public bool $isActive = true;
    public string $notes = '';

    protected $rules = [
        'tableNumber' => 'required|string|max:10',
        'tableName' => 'nullable|string|max:50',
        'tableCapacity' => 'required|integer|min:1|max:20',
        'tableStatus' => 'required|in:available,occupied,reserved',
        'isActive' => 'boolean',
        'notes' => 'nullable|string|max:500',
    ];

    public function mount(): void
    {
        $this->mountBase();
        $this->loadBranchAndDepartment();
    }

    protected function loadBranchAndDepartment(): void
    {
        $this->branchId = request('b_id');
        if ($this->branchId) {
            $branch = Branch::find($this->branchId);
            $this->branchName = $branch?->name ?? 'Unknown Branch';
        }

        if ($this->salesDeptSlug) {
            $department = Department::where('slug', $this->salesDeptSlug)
                ->where('branch_id', $this->branchId)
                ->first();

            if (!$department) {
                $department = Department::where('slug', $this->salesDeptSlug)
                    ->whereNull('branch_id')
                    ->first();
            }

            if ($department) {
                $this->departmentId = $department->id;
                $this->departmentName = $department->name;
            }
        }
    }

    public function getModelClass(): string
    {
        return Table::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    #[Computed]
    public function tables()
    {
        if (!$this->departmentId || !$this->branchId) {
            return collect([]);
        }

        return Table::where('branch_id', $this->branchId)
            ->where('department_id', $this->departmentId)
            ->orderBy('table_number')
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingTableId', 'tableNumber', 'tableName', 'tableCapacity', 'tableStatus', 'isActive', 'notes']);
        $this->tableCapacity = 4;
        $this->tableStatus = 'available';
        $this->isActive = true;
        $this->showTableModal = true;
    }

    public function openEditModal(int $tableId): void
    {
        $table = Table::find($tableId);
        if (!$table) {
            $this->toast()->error('Table not found.')->send();
            return;
        }

        $this->editingTableId = $table->id;
        $this->tableNumber = $table->table_number;
        $this->tableName = $table->table_name ?? '';
        $this->tableCapacity = $table->capacity;
        $this->tableStatus = $table->status;
        $this->isActive = $table->is_active;
        $this->notes = $table->notes ?? '';
        $this->showTableModal = true;
    }

    public function saveTable(): void
    {
        $this->validate();

        if (!$this->branchId || !$this->departmentId) {
            $this->toast()->error('Branch or department not found.')->send();
            return;
        }

        if ($this->editingTableId) {
            // Update existing table
            $table = Table::find($this->editingTableId);
            if (!$table) {
                $this->toast()->error('Table not found.')->send();
                return;
            }

            $table->update([
                'table_number' => $this->tableNumber,
                'table_name' => $this->tableName ?: 'Table ' . $this->tableNumber,
                'capacity' => $this->tableCapacity,
                'status' => $this->tableStatus,
                'is_active' => $this->isActive,
                'notes' => $this->notes,
            ]);

            $this->toast()->success('Table updated successfully.')->send();
        } else {
            // Create new table
            $exists = Table::where('branch_id', $this->branchId)
                ->where('department_id', $this->departmentId)
                ->where('table_number', $this->tableNumber)
                ->exists();

            if ($exists) {
                $this->toast()->error('Table number already exists in this department.')->send();
                return;
            }

            Table::create([
                'branch_id' => $this->branchId,
                'department_id' => $this->departmentId,
                'table_number' => $this->tableNumber,
                'table_name' => $this->tableName ?: 'Table ' . $this->tableNumber,
                'capacity' => $this->tableCapacity,
                'status' => $this->tableStatus,
                'is_active' => $this->isActive,
                'notes' => $this->notes,
            ]);

            $this->toast()->success('Table created successfully.')->send();
        }

        $this->showTableModal = false;
        unset($this->tables);
    }

    public function deleteTable(int $tableId): void
    {
        $table = Table::find($tableId);
        if (!$table) {
            $this->toast()->error('Table not found.')->send();
            return;
        }

        if ($table->hasActiveSale()) {
            $this->toast()->error('Cannot delete table with active orders.')->send();
            return;
        }

        $table->delete();
        unset($this->tables);
        $this->toast()->success('Table deleted successfully.')->send();
    }

    public function toggleTableStatus(int $tableId): void
    {
        $table = Table::find($tableId);
        if (!$table) {
            $this->toast()->error('Table not found.')->send();
            return;
        }

        $table->update(['is_active' => !$table->is_active]);
        unset($this->tables);
        $this->toast()->success('Table status updated.')->send();
    }

    public function createBulkTables(): void
    {
        if (!$this->branchId || !$this->departmentId) {
            $this->toast()->error('Branch or department not found.')->send();
            return;
        }

        $department = Department::find($this->departmentId);
        if (!$department) {
            $this->toast()->error('Department not found.')->send();
            return;
        }

        DB::transaction(function () use ($department) {
            $existingCount = Table::where('branch_id', $this->branchId)
                ->where('department_id', $this->departmentId)
                ->count();

            $startNumber = $existingCount + 1;

            for ($i = 0; $i < 8; $i++) {
                $tableNumber = (string)($startNumber + $i);
                Table::create([
                    'branch_id' => $this->branchId,
                    'department_id' => $this->departmentId,
                    'table_number' => $tableNumber,
                    'table_name' => 'Table ' . $tableNumber,
                    'status' => 'available',
                    'capacity' => 4,
                    'is_active' => true,
                ]);
            }
        });

        unset($this->tables);
        $this->toast()->success('8 tables created successfully.')->send();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.table-management.index');
    }
}
