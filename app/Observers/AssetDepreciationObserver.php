<?php

namespace App\Observers;

use App\Models\AssetDepreciation;
use App\Services\GlPostingService;
use Exception;

class AssetDepreciationObserver
{
    public function __construct(private GlPostingService $glPostingService)
    {
    }

    public function created(AssetDepreciation $depreciation): void
    {
        if ($depreciation->gl_posting_status !== 'pending') {
            return;
        }

        try {
            $this->glPostingService->postAssetDepreciation($depreciation);

            $depreciation->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);

            if ($depreciation->asset) {
                $depreciation->asset->update([
                    'accumulated_depreciation' => (float) $depreciation->asset->accumulated_depreciation + (float) $depreciation->depreciation_amount,
                ]);
            }
        } catch (Exception $e) {
            $depreciation->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post asset depreciation to GL', [
                'depreciation_id' => $depreciation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
