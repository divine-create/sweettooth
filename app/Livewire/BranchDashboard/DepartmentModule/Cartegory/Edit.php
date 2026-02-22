<?php

namespace App\Livewire\BranchDashboard\DepartmentModule\Cartegory;

use Livewire\Component;
use Livewire\Attributes\{Layout, Title, Url};
use App\Models\DepartmentCategory;
use App\Livewire\Concerns\CachesDepartmentCategories;
use TallStackUi\Traits\Interactions;

/**
 * Department Category Edit Livewire Component
 * 
 * Handles editing of existing department categories.
 * 
 * Key Features:
 * - Load existing category data on mount
 * - Form validation with rules (excluding current category from uniqueness check)
 * - Automatic cache invalidation on update
 * - Redirect back to category list with b_id parameter
 * 
 * Categories are system-wide and not branch-specific.
 * The b_id parameter is maintained for consistent navigation.
 * 
 * @see App\Livewire\Concerns\CachesDepartmentCategories
 * @see App\Models\DepartmentCategory
 */
#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Edit Category')]
class Edit extends Component
{
    use CachesDepartmentCategories, Interactions;

    /**
     * ID of the category being edited
     * @var int
     */
    public $categoryId;

    /**
     * Category name - required field
     * Must be unique across all categories (excluding current category)
     * @var string
     */
    public string $name = '';

    /**
     * Category description - required field
     * Provides context for what the category contains
     * @var string
     */
    public string $description = '';
    
    /**
     * Current branch context from URL parameter
     * Used for navigation consistency
     * @var string|null
     */
    #[Url(keep: true)]
    public ?string $b_id = null;

    /**
     * Mount the component and load category data
     * 
     * Called when component is initialized.
     * Loads the category to be edited from database or throws 404.
     * 
     * @param int $id The ID of the category to edit
     * @return void
     */
    public function mount($id)
    {
        // Load category or fail with 404
        $category = DepartmentCategory::findOrFail($id);
        
        // Populate form fields from category data
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description;
    }

    /**
     * Get validation rules for category edit
     * 
     * Dynamically excludes current category from uniqueness check
     * so the existing name is allowed.
     * 
     * @return array
     */
    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:department_categories,name,' . $this->categoryId,
            'description' => 'required|string|max:1000',
        ];
    }

    /**
     * Update department category
     * 
     * Process:
     * 1. Validates form input
     * 2. Updates category in database
     * 3. Invalidates department category cache (all instances will refetch)
     * 4. Shows success message
     * 5. Redirects to category list with b_id parameter
     * 
     * Cache Invalidation:
     * When a category is updated, the cache for all department category
     * lists is invalidated so they immediately reflect the changes.
     * This is done via bumpCategoryCacheVersion() from CachesDepartmentCategories trait.
     * 
     * @return void
     */
    public function editCategory()
    {
        // Validate form input
        $this->validate();

        // Update the category in database
        DepartmentCategory::find($this->categoryId)->update([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        // Invalidate cached department categories so all components refetch
        // This updates category dropdowns across the entire application
        $this->bumpCategoryCacheVersion();

        // Show success feedback to user
        $this->toast()->success('Category updated successfully!')->send();
        
        // Redirect to category list with branch context
        $this->redirect(route('branch-dashboard.branch.departments.category', ['b_id' => $this->b_id]), navigate: true);
    }

    /**
     * Render the category edit form
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.branch-dashboard.department-module.cartegory.edit');
    }
}