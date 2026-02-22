<?php

namespace App\Services;

use App\Enums\ProductionRequestSourceType;
use App\Enums\ProductionRequestStatus;
use App\Models\ProductionRequest;
use Illuminate\Support\Facades\DB;

class ProductionPipelineService
{
    /**
     * Create a production request triggered by sales demand.
     *
     * @param array<string, mixed> $attributes
     */
    public function createFromSalesDemand(array $attributes): ProductionRequest
    {
        return $this->createRequest($attributes, ProductionRequestSourceType::SALES_DEMAND);
    }

    /**
     * Create a production request triggered by restock needs.
     *
     * @param array<string, mixed> $attributes
     */
    public function createFromRestock(array $attributes): ProductionRequest
    {
        return $this->createRequest($attributes, ProductionRequestSourceType::RESTOCK);
    }

    /**
     * Create a manual production request.
     *
     * @param array<string, mixed> $attributes
     */
    public function createManual(array $attributes): ProductionRequest
    {
        return $this->createRequest($attributes, ProductionRequestSourceType::MANUAL);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function createRequest(array $attributes, ProductionRequestSourceType $sourceType): ProductionRequest
    {
        return DB::transaction(function () use ($attributes, $sourceType) {
            $defaults = [
                'status' => ProductionRequestStatus::PENDING->value,
                'source_type' => $sourceType->value,
                'source_id' => $attributes['source_id'] ?? null,
            ];

            return ProductionRequest::create(array_merge($attributes, $defaults));
        });
    }
}
