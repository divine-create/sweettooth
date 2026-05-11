<?php

namespace App\Services;

use App\Enums\CustomerOrderStatus;
use App\Enums\ProductionRequestSourceType;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\ProductDispatch;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerOrderService
{
    public function __construct(
        private readonly ProductionPipelineService $pipeline
    ) {}

    public function approveOrder(CustomerOrder $order, User $approver): void
    {
        DB::transaction(function () use ($order, $approver) {
            $order->update([
                'status' => CustomerOrderStatus::APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            foreach ($order->items()->with('product')->get() as $item) {
                $this->createProductionRequestForItem($order, $item);
            }
        });
    }

    public function rejectOrder(CustomerOrder $order, User $rejector, string $reason): void
    {
        DB::transaction(function () use ($order, $rejector, $reason) {
            $order->update([
                'status' => CustomerOrderStatus::REJECTED,
                'rejected_by' => $rejector->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
        });
    }

    public function recordDispatch(CustomerOrder $order, ProductDispatch $dispatch): void
    {
        DB::transaction(function () use ($order) {
            $allFulfilled = true;
            $order->loadMissing('items');

            foreach ($order->items as $item) {
                $totalDispatched = (float) ProductDispatch::where('customer_order_id', $order->id)
                    ->where('product_id', $item->product_id)
                    ->sum('quantity');

                if ($totalDispatched < (float) $item->quantity) {
                    $allFulfilled = false;
                    break;
                }
            }

            // Step through IN_PRODUCTION first (required by status machine)
            if ($order->status === CustomerOrderStatus::APPROVED) {
                $order->update(['status' => CustomerOrderStatus::IN_PRODUCTION]);
                $order->refresh();
            }

            if ($allFulfilled && $order->status === CustomerOrderStatus::IN_PRODUCTION) {
                $order->update(['status' => CustomerOrderStatus::COMPLETED]);
            }
        });
    }

    private function createProductionRequestForItem(CustomerOrder $order, CustomerOrderItem $item): void
    {
        $recipe = Recipe::where('product_id', $item->product_id)
            ->where('is_active', true)
            ->first();

        if (! $recipe) {
            return;
        }

        $this->pipeline->createFromCustomerOrder([
            'recipe_id' => $recipe->id,
            'planned_production_quantity' => (float) $item->quantity,
            'source_id' => (string) $order->id,
            'notes' => "Customer Order #{$order->order_number} — {$order->customer_name}",
            'production_department_id' => $recipe->department_id ?? null,
        ]);
    }
}
