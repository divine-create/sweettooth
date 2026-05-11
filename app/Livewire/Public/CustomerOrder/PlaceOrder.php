<?php

namespace App\Livewire\Public\CustomerOrder;

use App\Models\Branch;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PlaceOrder extends Component
{
    public string $branchCode = '';
    public ?Branch $branch = null;

    public string $customerName = '';
    public string $customerEmail = '';
    public string $customerPhone = '';
    public string $notes = '';

    /** @var array<string, int> product_id => quantity */
    public array $cart = [];

    public string $search = '';

    public bool $orderPlaced = false;
    public string $confirmedOrderNumber = '';

    public function mount(string $branchCode): void
    {
        $this->branchCode = $branchCode;
        $this->branch = Branch::where('code', $branchCode)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function addToCart(string $productId): void
    {
        $this->cart[$productId] = ($this->cart[$productId] ?? 0) + 1;
    }

    public function removeFromCart(string $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function updateQty(string $productId, int $qty): void
    {
        if ($qty <= 0) {
            $this->removeFromCart($productId);

            return;
        }

        $this->cart[$productId] = $qty;
    }

    public function getCartTotal(): float
    {
        $products = $this->getCartProducts();
        $total = 0.0;

        foreach ($this->cart as $productId => $qty) {
            $product = $products->firstWhere('id', $productId);
            if ($product) {
                $total += (float) $product->price * $qty;
            }
        }

        return $total;
    }

    public function submitOrder(): void
    {
        $this->validate([
            'customerName' => 'required|string|max:255',
            'customerEmail' => 'required|email|max:255',
            'customerPhone' => 'required|string|max:50',
            'cart' => 'required|array|min:1',
        ], [
            'cart.min' => 'Please add at least one item to your order.',
        ]);

        $products = $this->getCartProducts()->keyBy('id');

        DB::transaction(function () use ($products) {
            $total = 0.0;

            $order = CustomerOrder::create([
                'branch_id' => $this->branch->id,
                'customer_name' => $this->customerName,
                'customer_email' => $this->customerEmail,
                'customer_phone' => $this->customerPhone,
                'notes' => $this->notes,
                'total_amount' => 0,
            ]);

            foreach ($this->cart as $productId => $qty) {
                $product = $products->get($productId);
                if (! $product || $qty <= 0) {
                    continue;
                }

                $price = (float) $product->price;
                $subtotal = $price * $qty;
                $total += $subtotal;

                CustomerOrderItem::create([
                    'customer_order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_amount' => $total]);

            $this->confirmedOrderNumber = $order->order_number;
        });

        $this->orderPlaced = true;
        $this->cart = [];
    }

    public function render()
    {
        $query = $this->branch
            ? Product::with('productType')
                ->where('branch_id', $this->branch->id)
                ->where('is_active', true)
                ->whereHas('productType', fn ($q) => $q->where('name', 'like', 'Finished Good%'))
            : null;

        if ($query && ! empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $products = $query ? $query->orderBy('name')->get() : collect();

        return view('livewire.public.customer-order.place-order', [
            'products' => $products,
            'cartProducts' => $this->getCartProducts(),
        ])->layout('components.layouts.public');
    }

    private function getCartProducts()
    {
        if (empty($this->cart)) {
            return collect();
        }

        return Product::whereIn('id', array_keys($this->cart))->get();
    }
}
