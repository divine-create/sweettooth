<?php

namespace App\Livewire\BranchDashboard\Production;

use App\Enums\CustomerOrderStatus;
use App\Livewire\BranchDashboard\Production\Concerns\QuickProduceTrait;
use App\Models\CustomerOrder;
use App\Models\Recipe;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class QuickProduceFinishedGood extends Component
{
    use Interactions, QuickProduceTrait;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public function getBranchId(): ?string
    {
        return $this->b_id ?? request()->query('b_id');
    }

    public function getRecipes()
    {
        return Recipe::where('branch_id', $this->getBranchId())
            ->where('department_id', $this->department->id)
            ->where('is_active', true)
            ->where('is_wip', false)
            ->with('productType', 'unitOfMeasure')
            ->orderBy('product_name')
            ->get();
    }

    protected function loadPendingOrders(): void
    {
        $branchId = $this->getBranchId();
        $productId = $this->selectedRecipe?->product_id;

        if (! $branchId) {
            $this->pendingOrders = [];

            return;
        }

        $query = CustomerOrder::with('items.product')
            ->where('branch_id', $branchId)
            ->whereIn('status', [
                CustomerOrderStatus::APPROVED->value,
                CustomerOrderStatus::IN_PRODUCTION->value,
            ]);

        if ($productId) {
            $query->whereHas('items', fn ($q) => $q->where('product_id', $productId));
        }

        $this->pendingOrders = $query->latest()
            ->get()
            ->map(fn (CustomerOrder $order) => [
                'id'            => $order->id,
                'order_number'  => $order->order_number,
                'customer_name' => $order->customer_name,
                'status'        => $order->status->label(),
                'item_qty'      => $productId
                    ? (float) $order->items->where('product_id', $productId)->first()?->quantity
                    : null,
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.quick-produce-finished-good');
    }
}
