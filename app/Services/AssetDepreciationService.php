<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\AssetDepreciation;
use App\Models\FixedAsset;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AssetDepreciationService
{
    /**
     * Generate depreciation entries for a period end date.
     */
    public function generateForPeriod(AccountingPeriod $period): Collection
    {
        $asOf = Carbon::parse($period->period_end);

        $assets = FixedAsset::where('is_active', true)
            ->where('acquisition_date', '<=', $asOf)
            ->get();

        $entries = collect();

        foreach ($assets as $asset) {
            $amount = $asset->monthlyDepreciationAmount();
            if ($amount <= 0) {
                continue;
            }

            $alreadyExists = AssetDepreciation::where('fixed_asset_id', $asset->id)
                ->whereDate('depreciation_date', $asOf->toDateString())
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            $entries->push(AssetDepreciation::create([
                'fixed_asset_id' => $asset->id,
                'accounting_period_id' => $period->id,
                'depreciation_date' => $asOf->toDateString(),
                'depreciation_amount' => $amount,
                'gl_posting_status' => 'pending',
            ]));
        }

        return $entries;
    }
}
