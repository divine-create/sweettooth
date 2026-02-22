<?php

namespace App\Livewire\BranchDashboard\Organization;

use App\Models\UnitOfMeasure;
use App\Models\UomConversion;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class UomIndex extends Component
{
    public function render()
    {
        $unitsCount = UnitOfMeasure::query()->count();
        $categoriesCount = UnitOfMeasure::query()->distinct()->count('category');
        $activeConversionsCount = UomConversion::query()->where('is_active', true)->count();
        $scopedConversionsCount = UomConversion::query()
            ->where(function ($query): void {
                $query->whereNotNull('branch_id')
                    ->orWhereNotNull('item_id')
                    ->orWhereNotNull('product_id');
            })
            ->count();

        return view('livewire.branch-dashboard.organization.uom.index', [
            'unitsCount' => $unitsCount,
            'categoriesCount' => $categoriesCount,
            'activeConversionsCount' => $activeConversionsCount,
            'scopedConversionsCount' => $scopedConversionsCount,
        ]);
    }
}
