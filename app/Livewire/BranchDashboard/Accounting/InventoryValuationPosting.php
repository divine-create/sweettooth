<?php

namespace App\Livewire\BranchDashboard\Accounting;

use App\Helpers\Settings;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\Item;
use App\Models\Stock;
use App\Services\CurrencyFormattingService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class InventoryValuationPosting extends Component
{
    public ?string $branchId = null;

    public ?int $selectedBranchId = null;

    public string $postingType = 'opening_balance'; // opening_balance, revaluation, adjustment

    public string $description = '';

    public bool $showConfirmModal = false;

    public array $selectedCategories = [];

    public array $categoryTotals = [];

    public array $postingHistory = [];

    // GL Account Mappings
    public array $glAccountMappings = [
        'raw_materials' => '1310',
        'wip' => '1320',
        'finished_goods' => '1330',
        'supplies' => '1340',
        'other' => '1300',
    ];

    public function mount()
    {
        $this->branchId = Request::query('b_id');
        $this->selectedBranchId = $this->branchId ? (int) $this->branchId : null;
        $this->description = 'Inventory Opening Balance - '.now()->format('M Y');
    }

    public function render()
    {
        $inventoryValuation = $this->getInventoryValuation();
        $currentPeriod = AccountingPeriod::current()->first();
        $branches = Branch::orderBy('name')->get();

        // Get posting history for this branch/period
        $this->postingHistory = $this->getPostingHistory($currentPeriod);

        // Check if already posted for this period and branch
        $alreadyPosted = $this->checkAlreadyPosted($currentPeriod);

        return view('livewire.branch-dashboard.accounting.inventory-valuation-posting', [
            'inventoryValuation' => $inventoryValuation,
            'currentPeriod' => $currentPeriod,
            'branches' => $branches,
            'alreadyPosted' => $alreadyPosted,
            'postingHistory' => $this->postingHistory,
            'glAccounts' => GlAccount::where('is_active', true)
                ->whereBetween('account_number', ['1300', '1399'])
                ->orderBy('account_number')
                ->get(),
            'equityAccounts' => GlAccount::where('is_active', true)
                ->where(function ($q) {
                    $q->where('account_type', 'equity')
                        ->orWhere('account_number', 'like', '3%');
                })
                ->orderBy('account_number')
                ->get(),
        ]);
    }

    public function getPostingHistory(?AccountingPeriod $period): array
    {
        if (! $period) {
            return [];
        }

        $query = GlEntry::where('entry_type', 'inventory_valuation')
            ->where('accounting_period_id', $period->id)
            ->where('debit', '>', 0);

        if ($this->selectedBranchId) {
            $query->where('branch_id', $this->selectedBranchId);
        }

        return $query->with('glAccount')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'date' => $entry->created_at->format('M d, Y H:i'),
                    'account' => $entry->glAccount->account_number ?? 'N/A',
                    'account_name' => $entry->glAccount->account_name ?? 'N/A',
                    'amount' => $entry->debit,
                    'description' => $entry->description,
                    'reference' => $entry->reference_number,
                ];
            })
            ->toArray();
    }

    public function checkAlreadyPosted(?AccountingPeriod $period): bool
    {
        if (! $period) {
            return false;
        }

        $query = GlEntry::where('accounting_period_id', $period->id)
            ->where('entry_type', 'inventory_valuation');

        if ($this->selectedBranchId) {
            $query->where('branch_id', $this->selectedBranchId);
        }

        return $query->exists();
    }

    public function getInventoryValuation(): array
    {
        $query = Stock::with(['item', 'branch'])
            ->where('quantity_available', '>', 0);

        if ($this->selectedBranchId) {
            $query->where('branch_id', $this->selectedBranchId);
        }

        $stocks = $query->get();

        // Group by category and calculate totals
        $categories = [
            'raw_materials' => ['name' => 'Raw Materials', 'items' => [], 'total_qty' => 0, 'total_value' => 0, 'gl_account' => '1310'],
            'wip' => ['name' => 'Work in Progress', 'items' => [], 'total_qty' => 0, 'total_value' => 0, 'gl_account' => '1320'],
            'finished_goods' => ['name' => 'Finished Goods', 'items' => [], 'total_qty' => 0, 'total_value' => 0, 'gl_account' => '1330'],
            'supplies' => ['name' => 'Supplies & Consumables', 'items' => [], 'total_qty' => 0, 'total_value' => 0, 'gl_account' => '1340'],
            'other' => ['name' => 'Other Inventory', 'items' => [], 'total_qty' => 0, 'total_value' => 0, 'gl_account' => '1300'],
        ];

        $grandTotal = 0;

        foreach ($stocks as $stock) {
            $category = $this->categorizeItem($stock->item);
            $value = (float) $stock->quantity_available * (float) $stock->average_cost;
            $grandTotal += $value;

            $categories[$category]['items'][] = [
                'id' => $stock->id,
                'item_name' => $stock->item->name ?? 'Unknown',
                'item_sku' => $stock->item->sku ?? 'N/A',
                'branch_name' => $stock->branch->name ?? 'N/A',
                'quantity' => (float) $stock->quantity_available,
                'average_cost' => (float) $stock->average_cost,
                'value' => $value,
            ];

            $categories[$category]['total_qty'] += (float) $stock->quantity_available;
            $categories[$category]['total_value'] += $value;
        }

        // Store totals for posting - only categories with value > 0
        $this->categoryTotals = [];
        foreach ($categories as $key => $cat) {
            if ($cat['total_value'] > 0) {
                $this->categoryTotals[$key] = $cat['total_value'];
            }
        }

        return [
            'categories' => $categories,
            'grand_total' => $grandTotal,
            'stock_count' => $stocks->count(),
        ];
    }

    private function categorizeItem(?Item $item): string
    {
        if (! $item) {
            return 'other';
        }

        $category = strtolower($item->category ?? '');

        if (str_contains($category, 'raw') || str_contains($category, 'material') || str_contains($category, 'ingredient')) {
            return 'raw_materials';
        }
        if (str_contains($category, 'wip') || str_contains($category, 'work in progress') || str_contains($category, 'semi')) {
            return 'wip';
        }
        if (str_contains($category, 'finished') || str_contains($category, 'product') || str_contains($category, 'goods')) {
            return 'finished_goods';
        }
        if (str_contains($category, 'supply') || str_contains($category, 'consumable') || str_contains($category, 'packaging')) {
            return 'supplies';
        }

        return 'other';
    }

    public function toggleCategory(string $category)
    {
        if (in_array($category, $this->selectedCategories)) {
            $this->selectedCategories = array_values(array_diff($this->selectedCategories, [$category]));
        } else {
            $this->selectedCategories[] = $category;
        }
    }

    public function selectAllCategories()
    {
        // Refresh category totals first
        $this->getInventoryValuation();
        $this->selectedCategories = array_keys($this->categoryTotals);
    }

    public function deselectAllCategories()
    {
        $this->selectedCategories = [];
    }

    public function confirmPosting()
    {
        if (empty($this->selectedCategories)) {
            session()->flash('error', 'Please select at least one inventory category to post.');

            return;
        }

        $currentPeriod = AccountingPeriod::current()->first();
        if (! $currentPeriod || $currentPeriod->status !== 'open') {
            session()->flash('error', 'No open accounting period found. Please create one first.');

            return;
        }

        $this->showConfirmModal = true;
    }

    public function postToGL()
    {
        try {
            DB::beginTransaction();

            $currentPeriod = AccountingPeriod::current()->first();
            if (! $currentPeriod || $currentPeriod->status !== 'open') {
                throw new Exception('No open accounting period found');
            }

            // Get opening balance equity account - try multiple options
            $equityAccount = GlAccount::where('account_number', '3110')->first()  // Retained Earnings
                ?? GlAccount::where('account_number', '3100')->first()  // Owner's Equity
                ?? GlAccount::where('account_number', '3101')->first()  // Capital Stock
                ?? GlAccount::where('account_type', 'equity')->first();

            if (! $equityAccount) {
                // Create opening balance equity account if none exists
                $equityAccount = GlAccount::create([
                    'account_number' => '3010',
                    'account_name' => 'Opening Balance Equity',
                    'account_type' => 'equity',
                    'account_category' => 'equity',
                    'description' => 'Opening balance equity for initial postings',
                    'normal_balance' => 'credit',
                    'is_header' => false,
                    'is_active' => true,
                    'allow_manual_entry' => true,
                ]);
            }

            $totalPosted = 0;
            $entriesCreated = 0;
            $referenceNumber = 'INV-VAL-'.now()->format('Ymd-His');

            foreach ($this->selectedCategories as $category) {
                if (! isset($this->categoryTotals[$category]) || $this->categoryTotals[$category] <= 0) {
                    continue;
                }

                $amount = $this->categoryTotals[$category];
                $glAccountNumber = $this->glAccountMappings[$category] ?? '1300';
                $glAccount = GlAccount::where('account_number', $glAccountNumber)->first();

                if (! $glAccount) {
                    // Create the inventory account if it doesn't exist
                    $categoryName = match ($category) {
                        'raw_materials' => 'Raw Materials Inventory',
                        'wip' => 'Work in Progress Inventory',
                        'finished_goods' => 'Finished Goods Inventory',
                        'supplies' => 'Supplies Inventory',
                        default => 'Other Inventory',
                    };

                    $glAccount = GlAccount::create([
                        'account_number' => $glAccountNumber,
                        'account_name' => $categoryName,
                        'account_type' => 'asset',
                        'account_category' => 'current_asset',
                        'description' => $categoryName,
                        'normal_balance' => 'debit',
                        'is_header' => false,
                        'is_active' => true,
                        'allow_manual_entry' => true,
                    ]);
                }

                $categoryDisplayName = match ($category) {
                    'raw_materials' => 'Raw Materials',
                    'wip' => 'Work in Progress',
                    'finished_goods' => 'Finished Goods',
                    'supplies' => 'Supplies',
                    default => 'Other Inventory',
                };

                // Debit: Inventory Asset
                $debitEntry = GlEntry::create([
                    'gl_account_id' => $glAccount->id,
                    'accounting_period_id' => $currentPeriod->id,
                    'entry_type' => 'inventory_valuation',
                    'reference_type' => 'App\\Models\\Stock',
                    'reference_id' => null,
                    'reference_number' => $referenceNumber,
                    'description' => "{$this->description} - {$categoryDisplayName}",
                    'debit' => $amount,
                    'credit' => 0,
                    'entry_date' => now(),
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                    'entered_by_type' => get_class(auth()->user()),
                    'branch_id' => $this->selectedBranchId,
                ]);
                $debitEntry->post(auth()->id() ?? 1);
                $entriesCreated++;

                // Credit: Opening Balance Equity
                $creditEntry = GlEntry::create([
                    'gl_account_id' => $equityAccount->id,
                    'accounting_period_id' => $currentPeriod->id,
                    'entry_type' => 'inventory_valuation',
                    'reference_type' => 'App\\Models\\Stock',
                    'reference_id' => null,
                    'reference_number' => $referenceNumber,
                    'description' => "{$this->description} - {$categoryDisplayName}",
                    'debit' => 0,
                    'credit' => $amount,
                    'entry_date' => now(),
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                    'entered_by_type' => get_class(auth()->user()),
                    'branch_id' => $this->selectedBranchId,
                ]);
                $creditEntry->post(auth()->id() ?? 1);
                $entriesCreated++;

                $totalPosted += $amount;
            }

            DB::commit();

            $this->showConfirmModal = false;
            $this->selectedCategories = [];

            $service = new CurrencyFormattingService;
            session()->flash('message', 'Successfully posted inventory valuation of '.$service->format($totalPosted)." to GL ({$entriesCreated} entries created).");

            $this->dispatch('posting-complete');

        } catch (Exception $e) {
            DB::rollBack();
            $this->showConfirmModal = false;
            session()->flash('error', 'Failed to post inventory valuation: '.$e->getMessage());
        }
    }

    public function reversePosting(int $entryId)
    {
        try {
            $entry = GlEntry::findOrFail($entryId);

            if ($entry->status !== 'posted') {
                session()->flash('error', 'Only posted entries can be reversed.');

                return;
            }

            $entry->reverse(auth()->id() ?? 1);
            session()->flash('message', 'Entry reversed successfully.');
        } catch (Exception $e) {
            session()->flash('error', 'Failed to reverse entry: '.$e->getMessage());
        }
    }

    public function cancelPosting()
    {
        $this->showConfirmModal = false;
    }

    public function changeBranch($branchId)
    {
        $this->selectedBranchId = $branchId ? (int) $branchId : null;
        $this->selectedCategories = [];
        $this->categoryTotals = [];
    }

    /**
     * Format currency value for inventory valuation display
     */
    protected function formatCurrency(float $amount): string
    {
        $service = new CurrencyFormattingService;

        return $service->format($amount);
    }

    /**
     * Get currency symbol for valuation display
     */
    protected function getCurrencySymbol(?string $currency = null): string
    {
        $service = new CurrencyFormattingService;
        $currency = $currency ?? Settings::currencyLocalization('primary_currency', 'NGN');

        return $service->getSymbol($currency);
    }
}
