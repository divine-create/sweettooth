<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\BankAccount;
use App\Models\FixedAsset;
use App\Services\GlPostingService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class FixedAssets extends Component
{
    use Interactions, WithPagination, ExportsCsv;

    public string $asset_name = '';
    public ?string $asset_tag = null;
    public float $asset_cost = 0;
    public float $salvage_value = 0;
    public int $useful_life_months = 36;
    public string $depreciation_method = 'straight_line';
    public string $acquisition_date = '';
    public string $funding_source = 'cash';
    public ?string $bank_account_id = null;

    public int $perPage = 20;

    public ?int $disposingId = null;

    public function mount(): void
    {
        $this->acquisition_date = Carbon::now()->format('Y-m-d');
    }

    public function save(): void
    {
        $this->validate([
            'asset_name'           => 'required|string|max:255',
            'asset_tag'            => 'nullable|string|max:100',
            'asset_cost'           => 'required|numeric|min:0.01',
            'salvage_value'        => 'nullable|numeric|min:0',
            'useful_life_months'   => 'required|integer|min:1',
            'depreciation_method'  => 'required|in:straight_line,reducing_balance',
            'acquisition_date'     => 'required|date',
            'funding_source'       => 'required|in:cash,ap',
            'bank_account_id'      => 'nullable|exists:bank_accounts,id',
        ]);

        FixedAsset::create([
            'branch_id'           => current_branch_id(),
            'asset_name'          => $this->asset_name,
            'asset_tag'           => $this->asset_tag,
            'asset_cost'          => $this->asset_cost,
            'salvage_value'       => $this->salvage_value,
            'useful_life_months'  => $this->useful_life_months,
            'depreciation_method' => $this->depreciation_method,
            'acquisition_date'    => $this->acquisition_date,
            'funding_source'      => $this->funding_source,
            'bank_account_id'     => $this->bank_account_id,
            'created_by_id'       => auth()->id(),
        ]);

        $this->reset([
            'asset_name', 'asset_tag', 'asset_cost', 'salvage_value',
            'useful_life_months', 'depreciation_method', 'funding_source', 'bank_account_id',
        ]);

        $this->useful_life_months = 36;
        $this->depreciation_method = 'straight_line';
        $this->funding_source = 'cash';
        $this->toast()->success('Asset recorded.')->send();
    }

    public function confirmDispose(int $id): void
    {
        $this->disposingId = $id;

        $this->dialog()
            ->question('Dispose Asset?', 'This will write off the remaining book value and post a disposal entry to the GL. This action cannot be undone.')
            ->confirm('Dispose', 'executeDispose', 'Confirmed')
            ->cancel('Cancel', 'cancelDispose', 'Cancelled')
            ->send();
    }

    public function executeDispose(string $message): void
    {
        if (! $this->disposingId) {
            return;
        }

        $asset = FixedAsset::where('branch_id', current_branch_id())
            ->where('is_active', true)
            ->findOrFail($this->disposingId);

        try {
            app(GlPostingService::class)->postAssetDisposal($asset);
            $asset->update([
                'is_active'         => false,
                'gl_posting_status' => 'posted',
                'gl_posted_at'      => now(),
            ]);
            $this->toast()->success("Asset '{$asset->asset_name}' disposed and GL entry posted.")->send();
        } catch (\Exception $e) {
            $asset->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error'  => $e->getMessage(),
            ]);
            $this->toast()->error('GL posting failed: ' . $e->getMessage())->send();
        }

        $this->disposingId = null;
    }

    public function cancelDispose(string $message): void
    {
        $this->disposingId = null;
    }

    public function exportToCsv()
    {
        $rows = FixedAsset::where('branch_id', current_branch_id())
            ->orderByDesc('id')
            ->get()
            ->map(fn ($a) => [
                $a->asset_tag ?? '',
                $a->asset_name,
                number_format((float) $a->asset_cost, 2, '.', ''),
                number_format((float) $a->salvage_value, 2, '.', ''),
                $a->useful_life_months,
                $a->depreciation_method,
                $a->acquisition_date ? \Illuminate\Support\Carbon::parse($a->acquisition_date)->format('Y-m-d') : '',
                number_format((float) $a->accumulated_depreciation, 2, '.', ''),
                $a->is_active ? 'Yes' : 'No',
            ]);

        return $this->streamCsv(
            $this->csvFilename('fixed_assets'),
            ['Asset Tag', 'Asset Name', 'Cost', 'Salvage Value', 'Useful Life (months)', 'Depreciation Method', 'Acquisition Date', 'Accumulated Depreciation', 'Active'],
            $rows
        );
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.simple.fixed-assets', [
            'bankAccounts' => BankAccount::orderBy('bank_name')->get(['id', 'bank_name', 'account_number']),
            'rows'         => FixedAsset::where('branch_id', current_branch_id())
                ->orderByDesc('id')
                ->paginate($this->perPage),
        ]);
    }
}
