<?php

namespace App\Livewire\BranchDashboard\Production\FinishedGoods;

use App\Models\Department;
use App\Models\Product;
use App\Models\ProductionStore;
use App\Models\ProductionStoreMovement;
use App\Models\Recipe;
use App\Services\ProductionStoreService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Daily Finished-Goods Sheet — the digital version of the kitchen's paper
 * closing sheet for a production department.
 *
 * Opening / Produced / Sent out / Closing are DERIVED from production-store
 * movements (a tamper-evident ledger), so the figures trace to real produce
 * and dispatch events rather than being typed in. Opening carries from the
 * prior day's closing automatically (it's a continuous balance).
 */
#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    #[Url]
    public ?string $date = null;

    public string $search = '';

    public ?Department $department = null;

    public function mount($deptSlug = null)
    {
        $this->dept_slug = $deptSlug ?? $this->dept_slug;
        $this->b_id = request()->query('b_id', $this->b_id);
        $this->date = $this->date ?: now()->toDateString();

        $this->department = Department::where('slug', $this->dept_slug)->first();

        if (! $this->department) {
            abort(404, 'Department not found');
        }
    }

    protected function getBranchId(): ?string
    {
        return $this->b_id ?: request()->query('b_id');
    }

    protected function getStore(): ?ProductionStore
    {
        return ProductionStore::forBranch($this->getBranchId())
            ->forDepartment($this->department->id)
            ->active()
            ->first();
    }

    /**
     * Build the daily sheet rows (one per finished-goods product) derived from
     * the production-store movement ledger for the selected day.
     *
     * @return array{rows: array<int,array<string,mixed>>, totals: array<string,float>}
     */
    protected function buildSheet(?ProductionStore $store = null): array
    {
        $store ??= $this->getStore();

        $empty = ['rows' => [], 'totals' => [
            'opening' => 0.0, 'produced' => 0.0, 'sent_out' => 0.0, 'adjust' => 0.0, 'closing' => 0.0,
        ]];

        if (! $store) {
            return $empty;
        }

        $dayStart = Carbon::parse($this->date)->startOfDay();
        $dayEnd = Carbon::parse($this->date)->endOfDay();

        $wipProductIds = Recipe::where('is_wip', true)
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->toArray();

        // Finished goods = product (UUID) item_id that is not a WIP output.
        $movements = ProductionStoreMovement::where('store_id', $store->id)
            ->where('movement_date', '<=', $dayEnd)
            ->whereRaw("NOT (item_id REGEXP '^[0-9]+$')")
            ->when(! empty($wipProductIds), fn ($q) => $q->whereNotIn('item_id', $wipProductIds))
            ->get(['item_id', 'type', 'quantity', 'movement_date']);

        if ($movements->isEmpty()) {
            return $empty;
        }

        $grouped = $movements->groupBy('item_id');
        $products = Product::whereIn('id', $grouped->keys()->all())
            ->with('unitOfMeasure')
            ->get()
            ->keyBy('id');

        $rows = [];
        foreach ($grouped as $productId => $ms) {
            $opening = (float) $ms->filter(fn ($m) => $m->movement_date->lt($dayStart))->sum('quantity');
            $today = $ms->filter(fn ($m) => $m->movement_date->between($dayStart, $dayEnd));
            $produced = (float) $today->where('type', 'in')->sum('quantity');
            $sentOut = (float) abs($today->whereIn('type', ['out', 'transfer'])->sum('quantity'));
            $adjust = (float) $today->whereIn('type', ['adjustment', 'return', 'damaged'])->sum('quantity');
            $closing = (float) $ms->sum('quantity');

            if (abs($opening) < 0.001 && abs($produced) < 0.001 && abs($sentOut) < 0.001
                && abs($adjust) < 0.001 && abs($closing) < 0.001) {
                continue;
            }

            $product = $products->get($productId);
            $name = $product?->name ?? 'Unknown product';

            if ($this->search && stripos($name, $this->search) === false) {
                continue;
            }

            $rows[] = [
                'product_id' => $productId,
                'name' => $name,
                'uom' => $product?->unitOfMeasure?->symbol ?? '',
                'opening' => $opening,
                'produced' => $produced,
                'sent_out' => $sentOut,
                'adjust' => $adjust,
                'closing' => $closing,
            ];
        }

        usort($rows, fn ($a, $b) => strcmp($a['name'], $b['name']));

        $totals = [
            'opening' => array_sum(array_column($rows, 'opening')),
            'produced' => array_sum(array_column($rows, 'produced')),
            'sent_out' => array_sum(array_column($rows, 'sent_out')),
            'adjust' => array_sum(array_column($rows, 'adjust')),
            'closing' => array_sum(array_column($rows, 'closing')),
        ];

        return ['rows' => $rows, 'totals' => $totals];
    }

    public function exportCsv()
    {
        $sheet = $this->buildSheet();
        $date = $this->date;
        $deptName = $this->department->name;

        return response()->streamDownload(function () use ($sheet, $date, $deptName) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ["Finished Goods — {$deptName} — {$date}"]);
            fputcsv($out, ['Product', 'Opening', 'Produced', 'Sent out', 'Adjustments', 'Closing', 'UOM']);
            foreach ($sheet['rows'] as $r) {
                fputcsv($out, [
                    $r['name'], $r['opening'], $r['produced'], $r['sent_out'], $r['adjust'], $r['closing'], $r['uom'],
                ]);
            }
            $t = $sheet['totals'];
            fputcsv($out, ['TOTAL', $t['opening'], $t['produced'], $t['sent_out'], $t['adjust'], $t['closing'], '']);
            fclose($out);
        }, 'finished-goods-' . $this->dept_slug . '-' . $date . '.csv');
    }

    public function render()
    {
        $store = $this->getStore();
        $sheet = $this->buildSheet($store);

        // Reconciliation: kitchen-sent vs shop-received for the day, scoped to
        // the products on this sheet.
        $variances = [];
        if ($store && ! empty($sheet['rows'])) {
            $productIds = array_column($sheet['rows'], 'product_id');
            foreach (app(ProductionStoreService::class)->getDispatchVariances($this->getBranchId(), $this->date, $productIds) as $v) {
                $variances[$v['product_id']][] = $v;
            }
        }

        return view('livewire.branch-dashboard.production.finished-goods.index', [
            'store' => $store,
            'rows' => $sheet['rows'],
            'totals' => $sheet['totals'],
            'variances' => $variances,
        ]);
    }
}
