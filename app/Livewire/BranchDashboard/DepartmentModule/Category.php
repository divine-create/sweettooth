<?php

namespace App\Livewire\BranchDashboard\DepartmentModule;

use App\Livewire\BaseComponent;
use App\Models\DepartmentCategory;
use App\Livewire\Concerns\CachesDepartmentCategories;
use App\Models\Department;
use App\Services\DepartmentCategoryApprovalService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\{Layout};

#[Layout('components.layouts.app.branch-dashboard')]
class Category extends BaseComponent
{
    use CachesDepartmentCategories;

    public ?int $quantity = 10;
    public ?string $search = null;
    public ?string $advancedSearch = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    //selected category delete modal functionality
    public ?Collection $selectedCategoryDeprtament = null;
    public ?string $selectedCategoryId = null;
    // Modal states
    public bool $showCategoryModal = false;
    public ?string $selectedId = null;
    public bool $isEditing = false;
    
    // Delete reason modal state
    public bool $showDeleteReasonModal = false;
    public string $deleteReason = '';
    public ?string $categoryToDelete = null;

    protected function getModelClass(): string
    {
        return DepartmentCategory::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        return DepartmentCategory::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%"))
            ->orderBy('created_at', 'desc');
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function initiateDelete($id)
    {
        $this->categoryToDelete = $id;

        // Close the Alpine delete modal once the backend knows which item to delete
        $this->dispatch('close-delete-modal');
        
        if (should_bypass_approval()) {
            $this->dialog()
                ->question('Warning!', 'Are you sure you want to delete this category?')
                ->confirm('Confirm', 'confirmedDelete', 'Confirmed Successfully')
                ->cancel('Cancel', 'cancelledDelete', 'Cancelled Successfully')
                ->send();
        } else {
            $this->showDeleteReasonModal = true;
        }
    }

    public function confirmedDelete(string $message): void
    {
        if (!$this->categoryToDelete) {
            return;
        }

        $category = DepartmentCategory::find($this->categoryToDelete);
        
        if (!$category) {
            $this->toast()->error('Category not found.')->send();
            return;
        }

        if (should_bypass_approval()) {
            $category->delete();
            $this->bumpCategoryCacheVersion();
            $this->dialog()->success('Success', 'Category deleted successfully!')->send();
        } else {
            DepartmentCategoryApprovalService::requestDelete($category, $this->deleteReason);
            $this->toast()->success('Delete request submitted for approval')->send();
        }

        $this->categoryToDelete = null;
        $this->deleteReason = '';
        $this->showDeleteReasonModal = false;
    }

    public function cancelledDelete(string $message): void
    {
        $this->categoryToDelete = null;
        $this->deleteReason = '';
        $this->showDeleteReasonModal = false;
        $this->toast()->info('Cancelled')->send();
    }

    public function closeDeleteReasonModal(): void
    {
        $this->showDeleteReasonModal = false;
        $this->deleteReason = '';
        $this->categoryToDelete = null;
    }

    public function proceedWithDeleteReason(): void
    {
        if (strlen($this->deleteReason) < 5) {
            $this->toast()->error('Reason must be at least 5 characters long')->send();
            return;
        }
        
        $this->showDeleteReasonModal = false;
        $this->confirmedDelete('Confirmed Successfully');
    }
    public function resetFilters()
    {
        $this->search = null;
        $this->advancedSearch = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }


    public function exportCSV()
    {
        $rows = $this->getFilteredQuery()->orderBy('created_at', 'desc')->get();

        if ($rows->isEmpty()) {
            $this->toast()->warning('No categories to export.')->send();

            return;
        }

        $filename = 'department-categories-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Category Name', 'Description', 'Created At']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->name,
                    $row->description,
                    optional($row->created_at)->format('Y-m-d H:i'),
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function updated($property, $value)
    {
        if (Str::contains($property, ['search', 'advancedSearch', 'dateFrom', 'dateTo', 'quantity'])) {
            $this->resetPage();
        }
    }

    protected function getCacheKey(): string
    {
        $userId   = auth()->id() ?? 'guest';
        $branchId = auth()->user()?->branch_id ?? 'none';

        return 'dept_categories_v3_' . md5(serialize([
            'user_id'        => $userId,
            'branch_id'      => $branchId,
            'search'         => $this->search,
            'advancedSearch' => $this->advancedSearch,
            'dateFrom'       => $this->dateFrom,
            'dateTo'         => $this->dateTo,
            'quantity'       => $this->quantity ?? 10,
            'page'           => request()->input('page', 1),
            'cache_version'  => $this->getCategoryCacheVersion(), // This makes it auto-invalidate
        ]));
    }

    public function getSelectedData($id)
    {
        $this->selectedCategoryId = $id;
        $departments = Department::with('category')
            ->whereHas('category', function($q) use ($id) {
                $q->where('department_categories.id', $id);
            })
            ->get();

        $this->selectedCategoryDeprtament = $departments;
    }
    



    public function render()
    {
        $cacheKey = $this->getCacheKey();

        $rows = Cache::remember($cacheKey, now()->addMinutes(15), function () {
            return $this->getFilteredQuery()
                ->paginate((int) ($this->quantity ?? 10))
                ->withQueryString();
        });

        $headers = [
            ['index' => 'id', 'label' => '#'],
            ['index' => 'name', 'label' => 'Category Name'],
            ['index' => 'description', 'label' => 'Description'],
            ['index' => 'created_at', 'label' => 'Created At'],
        ];

        if (is_super_admin()) {
            array_push($headers, ['label' => 'Action', 'index' => 'action']);
        }
        return view('livewire.branch-dashboard.department-module.category', [
            'headers' => $headers,
            'rows' => $rows,
        ]);
    }
}
