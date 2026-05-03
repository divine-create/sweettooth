<?php

namespace App\Livewire\BranchDashboard\Inventory\ItemCategories;

use App\Livewire\BaseComponent;
use App\Models\ItemCategory;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 20;

    public ?string $search = null;

    public ?string $filterStatus = null;

    public ?string $categoryId = null;

    public string $name = '';

    public string $description = '';

    public string $status = 'active';

    public bool $showModal = false;

    public bool $isEditing = false;

    protected string $paginationTheme = 'tailwind';

    protected function getModelClass(): string
    {
        return ItemCategory::class;
    }

    protected function getAllSelectableIds(): array
    {
        return ItemCategory::where('branch_id', $this->b_id)
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->pluck('id')->toArray();
    }

    public function mount()
    {
        $this->b_id = current_branch_id();
    }

    public function getCategoriesProperty()
    {
        $query = ItemCategory::query()
            ->where('branch_id', $this->b_id)
            ->orderBy('name');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        return $query->paginate((int) $this->quantity);
    }

    public function getTotalCategoriesProperty()
    {
        return ItemCategory::where('branch_id', $this->b_id)->count();
    }

    public function getActiveCategoriesProperty()
    {
        return ItemCategory::where('branch_id', $this->b_id)->where('status', 'active')->count();
    }

    public function openModal(?string $categoryId = null)
    {
        $this->resetValidation();

        if ($categoryId) {
            $category = ItemCategory::find($categoryId);
            if ($category) {
                $this->categoryId = $category->id;
                $this->name = $category->name;
                $this->description = $category->description ?? '';
                $this->status = $category->status;
                $this->isEditing = true;
            }
        } else {
            $this->reset(['categoryId', 'name', 'description', 'status']);
            $this->status = 'active';
            $this->isEditing = false;
        }

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['categoryId', 'name', 'description', 'status', 'isEditing']);
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:item_categories,name,'.$this->categoryId.',id,branch_id,'.$this->b_id,
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            if ($this->isEditing) {
                $category = ItemCategory::find($this->categoryId);
                if ($category) {
                    $category->update([
                        'name' => $this->name,
                        'description' => $this->description,
                        'status' => $this->status,
                    ]);
                    $this->toast()->success('Category updated successfully!')->send();
                }
            } else {
                ItemCategory::create([
                    'name' => $this->name,
                    'description' => $this->description,
                    'status' => $this->status,
                    'branch_id' => $this->b_id,
                ]);
                $this->toast()->success('Category created successfully!')->send();
            }

            $this->closeModal();
            $this->dispatch('$refresh');

        } catch (\Exception $e) {
            $this->toast()->error('Error saving category: '.$e->getMessage())->send();
        }
    }

    public function delete(?string $categoryId = null)
    {
        if (! $categoryId) {
            $this->toast()->error('Category ID is required!')->send();

            return;
        }

        $category = ItemCategory::where('id', $categoryId)
            ->where('branch_id', $this->b_id)
            ->first();

        if (! $category) {
            $this->toast()->error('Category not found!')->send();

            return;
        }

        if ($category->items()->count() > 0) {
            $this->toast()->error('Cannot delete category with existing items. Please reassign items first.')->send();

            return;
        }

        try {
            $category->delete();
            $this->toast()->success('Category deleted successfully!')->send();
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            $this->toast()->error('Error deleting category: '.$e->getMessage())->send();
        }
    }

    public function toggleStatus(string $categoryId)
    {
        $category = ItemCategory::find($categoryId);

        if (! $category) {
            $this->toast()->error('Category not found!')->send();

            return;
        }

        try {
            $newStatus = $category->status === 'active' ? 'inactive' : 'active';
            $category->update(['status' => $newStatus]);

            $message = $newStatus === 'active' ? 'Category activated!' : 'Category deactivated!';
            $this->toast()->success($message)->send();
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            $this->toast()->error('Error updating category: '.$e->getMessage())->send();
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.inventory.item-categories.index', [
            'categories' => $this->categories,
            'totalCategories' => $this->totalCategories,
            'activeCategories' => $this->activeCategories,
        ]);
    }
}
