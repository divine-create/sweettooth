<?php

namespace App\Livewire\BranchDashboard\Settings;

use App\Models\Branch;
use App\Models\Item;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\UomConversion;
use App\Services\UomConversionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UomConversions extends Component
{
    use WithPagination;

    public int $perPage = 15;
    public string $search = '';
    public string $scopeFilter = 'all';
    public string $statusFilter = 'active';

    public bool $showForm = false;
    public bool $isEditing = false;
    public ?int $editingId = null;

    public string $scopeType = 'global';
    public ?string $branchId = null;
    public ?int $itemId = null;
    public ?string $productId = null;
    public ?int $fromUomId = null;
    public ?int $toUomId = null;
    public string $factor = '';
    public bool $isActive = true;
    public bool $isSystem = false;
    public bool $syncInverse = true;
    public ?string $notes = null;

    public string $itemSearch = '';
    public string $productSearch = '';

    public function mount(): void
    {
        if (! is_super_admin()) {
            abort(403, 'Only Super Admins can manage UOM conversions.');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedScopeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedScopeType(string $scope): void
    {
        if ($scope === 'global') {
            $this->branchId = null;
            $this->itemId = null;
            $this->productId = null;
        }

        if ($scope === 'branch') {
            $this->itemId = null;
            $this->productId = null;
        }

        if ($scope === 'item') {
            $this->productId = null;
        }

        if ($scope === 'product') {
            $this->itemId = null;
        }
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editConversion(int $id): void
    {
        $conversion = UomConversion::query()
            ->with(['item:id,name,sku', 'product:id,name,sku'])
            ->findOrFail($id);

        $this->isEditing = true;
        $this->editingId = (int) $conversion->id;
        $this->showForm = true;

        $this->scopeType = $this->detectScopeType($conversion);
        $this->branchId = $conversion->branch_id;
        $this->itemId = $conversion->item_id ? (int) $conversion->item_id : null;
        $this->productId = $conversion->product_id;
        $this->fromUomId = (int) $conversion->from_uom_id;
        $this->toUomId = (int) $conversion->to_uom_id;
        $this->factor = (string) $conversion->factor;
        $this->isActive = (bool) $conversion->is_active;
        $this->isSystem = (bool) $conversion->is_system;
        $this->notes = $conversion->notes;
        $this->syncInverse = true;

        $this->itemSearch = $conversion->item
            ? trim($conversion->item->name.' '.$conversion->item->sku)
            : '';

        $this->productSearch = $conversion->product
            ? trim($conversion->product->name.' '.$conversion->product->sku)
            : '';
    }

    public function saveConversion(): void
    {
        $this->validate($this->rules(), $this->messages());

        if ((int) $this->fromUomId === (int) $this->toUomId) {
            $this->addError('toUomId', 'From unit and To unit must be different.');

            return;
        }

        $context = $this->buildContext();
        $factor = (float) $this->factor;

        try {
            app(UomConversionService::class)->setConversion(
                (int) $this->fromUomId,
                (int) $this->toUomId,
                $factor,
                $context,
                (bool) $this->syncInverse,
                [
                    'is_active' => (bool) $this->isActive,
                    'is_system' => (bool) $this->isSystem,
                    'notes' => $this->notes,
                    'created_by_id' => Auth::id(),
                    'created_by_type' => Auth::user() ? get_class(Auth::user()) : null,
                ],
            );

            $message = $this->isEditing
                ? 'UOM conversion updated successfully.'
                : 'UOM conversion created successfully.';

            session()->flash('message', $message);
            $this->cancelForm();
            $this->resetPage();
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash('error', 'Unable to save conversion: '.$exception->getMessage());
        }
    }

    public function deleteConversion(int $id): void
    {
        $conversion = UomConversion::query()->findOrFail($id);

        DB::transaction(function () use ($conversion): void {
            $inverse = UomConversion::query()
                ->where('from_uom_id', $conversion->to_uom_id)
                ->where('to_uom_id', $conversion->from_uom_id)
                ->where(function (Builder $query) use ($conversion): void {
                    $this->matchNullableScope($query, 'branch_id', $conversion->branch_id);
                })
                ->where(function (Builder $query) use ($conversion): void {
                    $this->matchNullableScope($query, 'item_id', $conversion->item_id);
                })
                ->where(function (Builder $query) use ($conversion): void {
                    $this->matchNullableScope($query, 'product_id', $conversion->product_id);
                })
                ->first();

            $conversion->delete();

            if ($inverse && (int) $inverse->id !== (int) $conversion->id) {
                $inverse->delete();
            }
        });

        session()->flash('message', 'UOM conversion deleted successfully.');
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $conversion = UomConversion::query()->findOrFail($id);
        $conversion->is_active = ! $conversion->is_active;
        $conversion->save();

        session()->flash('message', 'Conversion status updated.');
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function formatFactor(float|string $factor): string
    {
        $normalized = number_format((float) $factor, 12, '.', '');
        $trimmed = rtrim(rtrim($normalized, '0'), '.');

        return $trimmed === '' ? '0' : $trimmed;
    }

    protected function rules(): array
    {
        return [
            'scopeType' => ['required', Rule::in(['global', 'branch', 'item', 'product'])],
            'branchId' => [
                Rule::requiredIf(fn () => $this->scopeType === 'branch'),
                'nullable',
                'string',
                'exists:branches,id',
            ],
            'itemId' => [
                Rule::requiredIf(fn () => $this->scopeType === 'item'),
                'nullable',
                'integer',
                'exists:items,id',
            ],
            'productId' => [
                Rule::requiredIf(fn () => $this->scopeType === 'product'),
                'nullable',
                'string',
                'exists:products,id',
            ],
            'fromUomId' => ['required', 'integer', 'exists:units_of_measure,id', 'different:toUomId'],
            'toUomId' => ['required', 'integer', 'exists:units_of_measure,id'],
            'factor' => ['required', 'numeric', 'gt:0'],
            'isActive' => ['boolean'],
            'isSystem' => ['boolean'],
            'syncInverse' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'branchId.required' => 'Branch is required when scope is set to Branch.',
            'itemId.required' => 'Item is required when scope is set to Item.',
            'productId.required' => 'Product is required when scope is set to Product.',
            'fromUomId.different' => 'From unit and To unit must be different.',
        ];
    }

    /**
     * @return array{branch_id:string|null,item_id:int|null,product_id:string|null}
     */
    protected function buildContext(): array
    {
        $branchId = $this->branchId;
        $itemId = $this->scopeType === 'item' ? $this->itemId : null;
        $productId = $this->scopeType === 'product' ? $this->productId : null;

        if ($this->scopeType === 'global') {
            $branchId = null;
            $itemId = null;
            $productId = null;
        }

        if ($this->scopeType === 'branch') {
            $itemId = null;
            $productId = null;
        }

        if ($this->scopeType === 'item' && $itemId && ! $branchId) {
            $branchId = Item::query()->whereKey($itemId)->value('branch_id');
        }

        if ($this->scopeType === 'product' && $productId && ! $branchId) {
            $branchId = Product::query()->whereKey($productId)->value('branch_id');
        }

        return [
            'branch_id' => $branchId ?: null,
            'item_id' => $itemId ?: null,
            'product_id' => $productId ?: null,
        ];
    }

    protected function detectScopeType(UomConversion $conversion): string
    {
        if (! empty($conversion->product_id)) {
            return 'product';
        }

        if (! empty($conversion->item_id)) {
            return 'item';
        }

        if (! empty($conversion->branch_id)) {
            return 'branch';
        }

        return 'global';
    }

    protected function matchNullableScope(Builder $query, string $column, mixed $value): void
    {
        if ($value === null || $value === '') {
            $query->whereNull($column);

            return;
        }

        $query->where($column, $value);
    }

    protected function resetForm(): void
    {
        $this->isEditing = false;
        $this->editingId = null;
        $this->scopeType = 'global';
        $this->branchId = null;
        $this->itemId = null;
        $this->productId = null;
        $this->fromUomId = null;
        $this->toUomId = null;
        $this->factor = '';
        $this->isActive = true;
        $this->isSystem = false;
        $this->syncInverse = true;
        $this->notes = null;
        $this->itemSearch = '';
        $this->productSearch = '';
    }

    protected function itemOptions()
    {
        $query = Item::query()
            ->select(['id', 'name', 'sku', 'branch_id'])
            ->with('branch:id,name');

        if ($this->itemSearch !== '') {
            $term = '%'.$this->itemSearch.'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term);
            });
        }

        $items = $query->orderBy('name')->limit(20)->get();
        if ($this->itemId && ! $items->contains('id', $this->itemId)) {
            $selected = Item::query()
                ->select(['id', 'name', 'sku', 'branch_id'])
                ->with('branch:id,name')
                ->find($this->itemId);

            if ($selected) {
                $items->prepend($selected);
            }
        }

        return $items;
    }

    protected function productOptions()
    {
        $query = Product::query()->select(['id', 'name', 'sku', 'branch_id']);

        if ($this->productSearch !== '') {
            $term = '%'.$this->productSearch.'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term);
            });
        }

        $products = $query->orderBy('name')->limit(20)->get();
        if ($this->productId && ! $products->contains('id', $this->productId)) {
            $selected = Product::query()
                ->select(['id', 'name', 'sku', 'branch_id'])
                ->find($this->productId);

            if ($selected) {
                $products->prepend($selected);
            }
        }

        return $products;
    }

    public function render()
    {
        $query = UomConversion::query()
            ->with([
                'fromUnit:id,name,code,symbol',
                'toUnit:id,name,code,symbol',
                'branch:id,name,code',
                'item:id,name,sku',
                'product:id,name,sku',
            ]);

        if ($this->scopeFilter !== 'all') {
            $query->where(function (Builder $builder): void {
                if ($this->scopeFilter === 'global') {
                    $builder->whereNull('branch_id')
                        ->whereNull('item_id')
                        ->whereNull('product_id');

                    return;
                }

                if ($this->scopeFilter === 'branch') {
                    $builder->whereNotNull('branch_id')
                        ->whereNull('item_id')
                        ->whereNull('product_id');

                    return;
                }

                if ($this->scopeFilter === 'item') {
                    $builder->whereNotNull('item_id');

                    return;
                }

                if ($this->scopeFilter === 'product') {
                    $builder->whereNotNull('product_id');
                }
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('notes', 'like', $term)
                    ->orWhereHas('fromUnit', function (Builder $unitQuery) use ($term): void {
                        $unitQuery->where('name', 'like', $term)
                            ->orWhere('symbol', 'like', $term)
                            ->orWhere('code', 'like', $term);
                    })
                    ->orWhereHas('toUnit', function (Builder $unitQuery) use ($term): void {
                        $unitQuery->where('name', 'like', $term)
                            ->orWhere('symbol', 'like', $term)
                            ->orWhere('code', 'like', $term);
                    })
                    ->orWhereHas('branch', fn (Builder $branchQuery) => $branchQuery->where('name', 'like', $term))
                    ->orWhereHas('item', function (Builder $itemQuery) use ($term): void {
                        $itemQuery->where('name', 'like', $term)
                            ->orWhere('sku', 'like', $term);
                    })
                    ->orWhereHas('product', function (Builder $productQuery) use ($term): void {
                        $productQuery->where('name', 'like', $term)
                            ->orWhere('sku', 'like', $term);
                    });
            });
        }

        $conversions = $query->latest()->paginate($this->perPage);

        return view('livewire.branch-dashboard.settings.uom-conversions', [
            'conversions' => $conversions,
            'units' => UnitOfMeasure::active(),
            'branches' => Branch::query()->select(['id', 'name', 'code'])->orderBy('name')->get(),
            'itemOptions' => $this->itemOptions(),
            'productOptions' => $this->productOptions(),
        ]);
    }
}
