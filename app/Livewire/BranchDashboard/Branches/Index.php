<?php

namespace App\Livewire\BranchDashboard\Branches;

use App\Livewire\BaseComponent;
use Livewire\Component;
use App\Models\Branch;
use App\Models\User;
use App\Traits\Exportable;

class Index extends BaseComponent
{
    use Exportable;
    public ?int $quantity = 10;
    public ?string $search = null;
    public ?string $advancedSearch = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    // Filter fields
    public ?string $filterStatus = null;
    public ?string $filterCountry = null;
    public ?string $filterCity = null;
    public ?string $filterManager = null;

    // Modal states
    public bool $showBranchModal = false;
    public bool $showDeleteModal = false;
    public ?string $selectedBranchId = null;
    public bool $isEditing = false;

    // Branch form fields
    public string $name = '';
    public string $code = '';
    public string $location = '';
    public string $phone = '';
    public string $email = '';
    public string $description = '';
    public ?string $manager_user_id = null;
    public string $country = '';
    public string $state = '';
    public string $city = '';
    public string $postal_code = '';
    public string $timezone = 'UTC';
    public bool $is_active = true;

    protected array $bulkActions = [
        'delete' => ['label' => 'Delete Selected', 'method' => 'bulkDelete'],
        'export' => ['label' => 'Export Selected', 'method' => 'exportSelected'],
    ];

    public function mount()
    {
        // Check if user is super admin
        if (!is_super_admin()) {
            abort(403, 'Only Super Admins can manage branches');
        }
    }

    protected function getModelClass(): string
    {
        return Branch::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        return Branch::query()->latest()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->when($this->advancedSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->advancedSearch . '%')
                      ->orWhere('code', 'like', '%' . $this->advancedSearch . '%')
                      ->orWhere('location', 'like', '%' . $this->advancedSearch . '%')
                      ->orWhere('city', 'like', '%' . $this->advancedSearch . '%');
                });
            })
            ->when($this->filterStatus !== null && $this->filterStatus !== '', function ($query) {
                $query->where('is_active', $this->filterStatus === 'active');
            })
            ->when($this->filterCountry, function ($query) {
                $query->where('country', 'like', '%' . $this->filterCountry . '%');
            })
            ->when($this->filterCity, function ($query) {
                $query->where('city', 'like', '%' . $this->filterCity . '%');
            })
            ->when($this->filterManager, function ($query) {
                $query->where('manager_user_id', $this->filterManager);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            });
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = null;
        $this->advancedSearch = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->filterStatus = null;
        $this->filterCountry = null;
        $this->filterCity = null;
        $this->filterManager = null;
        $this->resetPage();
    }

    // Export methods


    public function exportCSV()
    {
        $branches = $this->getFilteredQuery()->get();

        $csv = "ID,Name,Code,Location,Phone,Email,City,Country,Active,Created At\n";
        foreach ($branches as $branch) {
            $csv .= "{$branch->id},{$branch->name},{$branch->code},{$branch->location},{$branch->phone},{$branch->email},{$branch->city},{$branch->country}," . ($branch->is_active ? 'Yes' : 'No') . ",{$branch->created_at}\n";
        }

        return response()->streamDownload(function() use ($csv) {
            echo $csv;
        }, 'branches-' . date('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    // Modal methods
    public function openBranchModal()
    {
        $this->isEditing = false;
        $this->resetBranchForm();
        $this->showBranchModal = true;
    }

    public function editBranch($branchId)
    {
        $branch = Branch::findOrFail($branchId);
        $this->isEditing = true;
        $this->selectedBranchId = $branchId;
        $this->name = $branch->name;
        $this->code = $branch->code;
        $this->location = $branch->location ?? '';
        $this->phone = $branch->phone ?? '';
        $this->email = $branch->email ?? '';
        $this->description = $branch->description ?? '';
        $this->manager_user_id = $branch->manager_user_id;
        $this->country = $branch->country ?? '';
        $this->state = $branch->state ?? '';
        $this->city = $branch->city ?? '';
        $this->postal_code = $branch->postal_code ?? '';
        $this->timezone = $branch->timezone ?? 'UTC';
        $this->is_active = $branch->is_active ?? true;
        $this->showBranchModal = true;
    }

    public function closeBranchModal()
    {
        $this->showBranchModal = false;
        $this->resetBranchForm();
    }

    public function resetBranchForm()
    {
        $this->name = '';
        $this->code = '';
        $this->location = '';
        $this->phone = '';
        $this->email = '';
        $this->description = '';
        $this->manager_user_id = null;
        $this->country = '';
        $this->state = '';
        $this->city = '';
        $this->postal_code = '';
        $this->timezone = 'UTC';
        $this->is_active = true;
        $this->selectedBranchId = null;
        $this->isEditing = false;
    }

    public function saveBranch()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code,' . $this->selectedBranchId,
            'location' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'manager_user_id' => 'nullable|exists:users,id',
            'country' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'timezone' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        $data = [
            'name' => $this->name,
            'code' => $this->code,
            'location' => $this->location,
            'phone' => $this->phone,
            'email' => $this->email,
            'description' => $this->description,
            'manager_user_id' => $this->manager_user_id,
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing && $this->selectedBranchId) {
            Branch::findOrFail($this->selectedBranchId)->update($data);
            $message = 'Branch updated successfully!';
        } else {
            Branch::create($data);
            $message = 'Branch created successfully!';
        }

        $this->toast()->success($message)->send();
        $this->closeBranchModal();
    }

    // Delete methods with TallStackUI Dialog
    public function confirmDelete($branchId): void
    {
        $this->selectedBranchId = $branchId;

        $this->dialog()
            ->question('Warning!', 'Are you sure you want to delete this branch?')
            ->confirm('Confirm Delete', 'deleteBranch', 'Branch deleted successfully!')
            ->cancel('Cancel', 'cancelledDelete', 'Delete cancelled')
            ->send();
    }

    public function deleteBranch(string $message): void
    {
        if ($this->selectedBranchId) {
            Branch::findOrFail($this->selectedBranchId)->delete();
            $this->dialog()->success('Success', $message)->send();
            $this->selectedBranchId = null;
        }
    }

    public function cancelledDelete(string $message): void
    {
        $this->selectedBranchId = null;
        $this->dialog()->info('Cancelled', $message)->send();
    }

    // Bulk Delete with TallStackUI Dialog
    public function confirmBulkDelete(): void
    {
        $this->dialog()
            ->question('Warning!', 'Are you sure you want to delete ' . count($this->selectedIds) . ' branch(es)?')
            ->confirm('Confirm Delete', 'bulkDelete', count($this->selectedIds) . ' branch(es) deleted successfully!')
            ->cancel('Cancel', 'cancelledBulkDelete', 'Bulk delete cancelled')
            ->send();
    }

    public function bulkDelete(string $message): void
    {
        Branch::whereIn('id', $this->selectedIds)->delete();
        $this->dialog()->success('Success', $message)->send();
        $this->selectedIds = [];
    }

    public function cancelledBulkDelete(string $message): void
    {
        $this->dialog()->info('Cancelled', $message)->send();
    }

    protected function exportSelected(): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('info', 'No branches selected for export.');
            return;
        }

        $branches = Branch::whereIn('id', $this->selectedIds)
            ->with('manager')
            ->get();

        $this->export(
            'branches_' . date('Y-m-d'),
            $branches,
            'exports.branches'
        );

        session()->flash('success', count($this->selectedIds) . ' branches exported successfully.');
        $this->resetBulkSelection();
    }

    public function render()
    {
        $rows = $this->getFilteredQuery()->paginate($this->quantity ?? 10);

        // Only load users when modal is open to avoid loading on every render
        $users = $this->showBranchModal ? User::select('id', 'name')->get() : collect();

        return view('livewire.branch-dashboard.branches.index', [
            'headers' => [
                ['index' => 'id', 'label' => '#'],
                ['index' => 'name', 'label' => 'Branch Name'],
                ['index' => 'code', 'label' => 'Code'],
                ['index' => 'location', 'label' => 'Location'],
                ['index' => 'city', 'label' => 'City'],
                ['index' => 'is_active', 'label' => 'Status'],
                ['index' => 'created_at', 'label' => 'Created At'],
                ['index' => 'action', 'label' => 'Actions', 'display' => true],
            ],
            'rows' => $rows,
            'users' => $users,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
