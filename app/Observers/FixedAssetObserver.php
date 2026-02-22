<?php

namespace App\Observers;

use App\Models\FixedAsset;
use App\Services\GlPostingService;
use Exception;

class FixedAssetObserver
{
    public function __construct(private GlPostingService $glPostingService)
    {
    }

    public function created(FixedAsset $asset): void
    {
        if ($asset->gl_posting_status !== 'pending') {
            return;
        }

        try {
            $this->glPostingService->postFixedAssetAcquisition($asset);

            $asset->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);
        } catch (Exception $e) {
            $asset->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post fixed asset acquisition to GL', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
