<?php

namespace App\Livewire\BranchDashboard\SalesDashboard;

use App\Services\CheckExpiredProducts;
use Livewire\Component;
use Livewire\Attributes\{Layout, On, Url};
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app.branch-dashboard')]
class ExpiryAlerts extends Component
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->loadExpiryData();
    }

    public $salesShiftId;
    public $expiredProducts = [];
    public $expiringProducts = [];
    public $showModal = false;

    // For callback action
    public $selectedProductStockId;
    public $callbackQuantity;
    public $callbackNotes;

    protected $rules = [
        'callbackQuantity' => 'required|numeric|min:0',
        'callbackNotes' => 'nullable|string|max:500',
    ];

    public function mount($salesShiftId = null)
    {
        if ($salesShiftId) {
            $this->salesShiftId = $salesShiftId;
            $this->loadExpiryData();

            // Show modal if there are expired products
            if (count($this->expiredProducts) > 0) {
                $this->showModal = true;
            }
        }
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function loadExpiryData()
    {
        $employee = Auth::guard('web')->user();
        $service = new CheckExpiredProducts();

        // Get expired products
        $this->expiredProducts = $service->getExpiredProductsForShift(
            $this->salesShiftId,
            $this->getBranchId(),
            $employee->department_id
        )->toArray();

        // Get expiring soon products
        $this->expiringProducts = $service->getExpiringProductsForShift(
            $this->salesShiftId,
            $this->getBranchId(),
            $employee->department_id
        )->toArray();
    }

    public function confirmStillGood($productStockId, $productName)
    {
        try {
            $employee = Auth::guard('web')->user();
            $service = new CheckExpiredProducts();

            $service->confirmStillGood(
                $productStockId,
                $this->salesShiftId,
                $employee->id,
                'Confirmed by ' . $employee->name . ' during clock-in'
            );

            // Remove from expired products list
            $this->expiredProducts = array_filter($this->expiredProducts, function ($product) use ($productStockId) {
                return $product['product_stock_id'] != $productStockId;
            });

            $this->toast()->success("'{$productName}' confirmed as still good")->send();

            // Close modal if no more expired products
            if (count($this->expiredProducts) === 0) {
                $this->showModal = false;
                $this->redirectToDashboard();
            }

        } catch (\Exception $e) {
            $this->toast()->error('Error confirming product: ' . $e->getMessage())->send();
        }
    }

    public function openCallbackModal($productStockId, $quantity)
    {
        $this->selectedProductStockId = $productStockId;
        $this->callbackQuantity = $quantity;
        $this->callbackNotes = '';
    }

    public function markAsCallback($productStockId, $productName, $quantity)
    {
        try {
            $this->validate([
                'callbackQuantity' => 'required|numeric|min:0|max:' . $quantity,
            ]);

            $employee = Auth::guard('web')->user();
            $service = new CheckExpiredProducts();

            $service->markAsCallback(
                $productStockId,
                $this->salesShiftId,
                $employee->id,
                $this->callbackNotes ?: 'Marked as callback: Expired product',
                $this->callbackQuantity
            );

            // Remove from expired products list
            $this->expiredProducts = array_filter($this->expiredProducts, function ($product) use ($productStockId) {
                return $product['product_stock_id'] != $productStockId;
            });

            $this->toast()->success("'{$productName}' marked as callback")->send();

            // Reset callback form
            $this->selectedProductStockId = null;
            $this->callbackQuantity = null;
            $this->callbackNotes = '';

            // Close modal if no more expired products
            if (count($this->expiredProducts) === 0) {
                $this->showModal = false;
                $this->redirectToDashboard();
            }

        } catch (\Exception $e) {
            $this->toast()->error('Error marking callback: ' . $e->getMessage())->send();
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->redirectToDashboard();
    }

    private function redirectToDashboard()
    {
        return $this->redirect(route('branch-dashboard.index', ['b_id' => $this->getBranchId()]), navigate: true);
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.expiry-alerts');
    }
}
