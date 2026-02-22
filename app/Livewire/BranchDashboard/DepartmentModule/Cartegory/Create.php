<?php

namespace App\Livewire\BranchDashboard\DepartmentModule\Cartegory;

use Livewire\Component;
use Livewire\Attributes\{Layout, Title, Url};
use App\Models\DepartmentCategory;
use App\Livewire\Concerns\CachesDepartmentCategories;
use TallStackUi\Traits\Interactions;

/**
 * Department Category Create Livewire Component
 * 
 * Handles creation of new department categories.
 * 
 * Key Features:
 * - Simple form for category name and description
 * - Form validation with rules
 * - Automatic cache invalidation on creation
 * - Redirect back to category list with b_id parameter
 * 
 * Categories are system-wide and not branch-specific.
 * The b_id parameter is maintained for consistent navigation.
 * 
 * @see App\Livewire\Concerns\CachesDepartmentCategories
 * @see App\Models\DepartmentCategory
 */
#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Create New Category')]
class Create extends Component
{
    use CachesDepartmentCategories;
    use Interactions;

    /**
     * Category name - required field
     * Must be unique across all categories
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
     * Validation rules for category creation
     * 
     * - name: Required, max 255 chars, must be unique
     * - description: Required, max 1000 chars
     * 
     * @var array
     */
    protected $rules = [
        'name' => 'required|string|max:255|unique:department_categories,name',
        'description' => 'required|string|max:1000',
    ];

    /**
     * Save new department category
     * 
     * Process:
     * 1. Validates form input
     * 2. Creates new DepartmentCategory in database
     * 3. Invalidates department category cache (all instances will refetch)
     * 4. Shows success message
     * 5. Redirects to category list with b_id parameter
     * 
     * Cache Invalidation:
     * When a new category is created, the cache for all department category
     * lists is invalidated so they immediately reflect the new category.
     * This is done via bumpCategoryCacheVersion() from CachesDepartmentCategories trait.
     * 
     * @return void
     */
    public function saveCategory()
    {
        // Validate form input
        $this->validate();

        // Create the new category
        DepartmentCategory::create([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        // Invalidate cached department categories so all components refetch
        // This updates category dropdowns across the entire application
        $this->bumpCategoryCacheVersion();

        // Show success feedback to user
        $this->toast()->success('Category created successfully!')->send();

        // Redirect to category list with branch context
        $this->redirect(route('branch-dashboard.branch.departments.category', ['b_id' => $this->b_id]), navigate: true);
    }

    /**
     * Render the category creation form
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.branch-dashboard.department-module.cartegory.create');
    }
}