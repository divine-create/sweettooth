<?php

namespace App\Livewire\BranchDashboard\Production;

use App\Models\Recipe;
use App\Models\Department;
use Livewire\Attributes\{Layout, On, Url};
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class RecipeDetail extends Component
{
    #[Url(keep: true)]
    public ?string $b_id = null;

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->loadRecipe();
    }

    public $recipeId;
    public ?Recipe $recipe = null;
    public $batchSize = 1;


    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;

    public function mount($id, $deptSlug)
    {
        $this->recipeId = $id;
        $this->dept_slug = $deptSlug;
        $this->department = Department::where('slug', $deptSlug)->first();

        if (!$this->department) {
            abort(404, 'Department not found');
        }

        $this->loadRecipe();
    }

    public function loadRecipe()
    {
        $this->recipe = Recipe::with(['ingredients', 'department', 'createdBy', 'productType'])
            ->where('department_id', $this->department->id)
            ->where('id', $this->recipeId)
            ->first();

        if (!$this->recipe) {
            abort(404, 'Recipe not found or does not belong to this department');
        }
    }

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    public function updatedBatchSize()
    {
        // Just ensure it's a valid number, but don't force immediate validation
        if ($this->batchSize !== null && $this->batchSize !== '' && is_numeric($this->batchSize)) {
            $this->batchSize = (int) max(1, $this->batchSize);
        }
    }

    public function getCalculatedIngredientsProperty()
    {
        $effectiveBatchSize = $this->batchSize > 0 ? $this->batchSize : 1;
        return $this->recipe->calculateIngredientsForBatch($effectiveBatchSize);
    }

    public function getTotalCostProperty()
    {
        $effectiveBatchSize = $this->batchSize > 0 ? $this->batchSize : 1;
        return $this->recipe->calculateTotalCostForBatch($effectiveBatchSize);
    }

    public function getTotalYieldProperty()
    {
        $effectiveBatchSize = $this->batchSize > 0 ? $this->batchSize : 1;
        return $this->recipe->calculateYieldForBatch($effectiveBatchSize);
    }

    public function getCostPerUnitProperty()
    {
        return $this->recipe->calculateCostPerUnit();
    }

    public function calculateBatch()
    {
        // Validate batch size to ensure it's at least 1
        if (empty($this->batchSize) || $this->batchSize < 1) {
            $this->batchSize = 1;
        } else {
            $this->batchSize = (int) $this->batchSize;
        }
        
        // The getters will automatically recalculate with the new batch size
    }

    public function getInstructionsProperty()
    {
        return $this->recipe->getInstructionsArray();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.recipe-detail');
    }
}
