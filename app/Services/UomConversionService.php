<?php

namespace App\Services;

use App\Models\UnitOfMeasure;
use App\Models\UomConversion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class UomConversionService
{
    /**
     * Convert quantity from one UOM to another using scoped conversion rules.
     *
     * @param  int|string|UnitOfMeasure  $fromUom
     * @param  int|string|UnitOfMeasure  $toUom
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null,max_depth?:int|null}  $context
     */
    public function convert(float $quantity, int|string|UnitOfMeasure $fromUom, int|string|UnitOfMeasure $toUom, array $context = []): float
    {
        $fromUomId = $this->resolveUomId($fromUom);
        $toUomId = $this->resolveUomId($toUom);

        if ($fromUomId === $toUomId) {
            return $quantity;
        }

        $factor = $this->resolveConversionRate($fromUomId, $toUomId, $context);
        if ($factor === null) {
            throw new RuntimeException("No UOM conversion path found from {$fromUomId} to {$toUomId}.");
        }

        return $quantity * $factor;
    }

    /**
     * Safe conversion helper that returns null when conversion path does not exist.
     *
     * @param  int|string|UnitOfMeasure  $fromUom
     * @param  int|string|UnitOfMeasure  $toUom
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null,max_depth?:int|null}  $context
     */
    public function tryConvert(float $quantity, int|string|UnitOfMeasure $fromUom, int|string|UnitOfMeasure $toUom, array $context = []): ?float
    {
        try {
            return $this->convert($quantity, $fromUom, $toUom, $context);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve conversion factor between two UOMs.
     *
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null,max_depth?:int|null}  $context
     */
    public function resolveConversionRate(int $fromUomId, int $toUomId, array $context = []): ?float
    {
        if ($fromUomId === $toUomId) {
            return 1.0;
        }

        $context = $this->normalizeContext($context);
        $direct = $this->resolveBestDirectConversion($fromUomId, $toUomId, $context);
        if ($direct) {
            return (float) $direct->factor;
        }

        $maxDepth = max(1, (int) ($context['max_depth'] ?? 4));

        return $this->resolveRateViaPath($fromUomId, $toUomId, $context, $maxDepth);
    }

    /**
     * Create or update a conversion and (optionally) its inverse.
     *
     * @param  int|string|UnitOfMeasure  $fromUom
     * @param  int|string|UnitOfMeasure  $toUom
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null}  $context
     * @param  array{is_active?:bool,is_system?:bool,notes?:string|null,created_by_id?:string|null,created_by_type?:string|null}  $meta
     * @return array{direct:UomConversion,inverse:UomConversion|null}
     */
    public function setConversion(
        int|string|UnitOfMeasure $fromUom,
        int|string|UnitOfMeasure $toUom,
        float $factor,
        array $context = [],
        bool $syncInverse = true,
        array $meta = [],
    ): array {
        if ($factor <= 0) {
            throw new InvalidArgumentException('Conversion factor must be greater than zero.');
        }

        $fromUomId = $this->resolveUomId($fromUom);
        $toUomId = $this->resolveUomId($toUom);
        if ($fromUomId === $toUomId) {
            throw new InvalidArgumentException('Cannot create conversion between the same UOM.');
        }

        $context = $this->normalizeContext($context);
        $meta = $this->normalizeMeta($meta);

        return DB::transaction(function () use (
            $fromUomId,
            $toUomId,
            $factor,
            $context,
            $syncInverse,
            $meta
        ) {
            $direct = $this->upsertScopedConversion(
                $fromUomId,
                $toUomId,
                $factor,
                $context,
                $meta
            );

            $inverse = null;
            if ($syncInverse) {
                $inverse = $this->upsertScopedConversion(
                    $toUomId,
                    $fromUomId,
                    1 / $factor,
                    $context,
                    $meta
                );
            }

            return [
                'direct' => $direct,
                'inverse' => $inverse,
            ];
        });
    }

    /**
     * Get all active conversions relevant to provided context.
     *
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null}  $context
     * @return Collection<int, UomConversion>
     */
    public function getApplicableConversions(array $context = []): Collection
    {
        $normalized = $this->normalizeContext($context);

        return UomConversion::query()
            ->active()
            ->with(['fromUnit:id,name,code,symbol', 'toUnit:id,name,code,symbol'])
            ->get()
            ->filter(fn (UomConversion $conversion) => $this->isApplicableToContext($conversion, $normalized))
            ->values();
    }

    /**
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null,max_depth?:int|null}  $context
     */
    protected function resolveBestDirectConversion(int $fromUomId, int $toUomId, array $context): ?UomConversion
    {
        $candidates = UomConversion::query()
            ->active()
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId)
            ->get()
            ->filter(fn (UomConversion $conversion) => $this->isApplicableToContext($conversion, $context))
            ->sortByDesc(fn (UomConversion $conversion) => $this->specificityScore($conversion))
            ->values();

        return $candidates->first();
    }

    /**
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null,max_depth?:int|null}  $context
     */
    protected function resolveRateViaPath(int $fromUomId, int $toUomId, array $context, int $maxDepth): ?float
    {
        $applicable = $this->getApplicableConversions($context);
        if ($applicable->isEmpty()) {
            return null;
        }

        $bestEdges = [];
        foreach ($applicable as $conversion) {
            $edgeKey = $conversion->from_uom_id . ':' . $conversion->to_uom_id;
            $score = $this->specificityScore($conversion);

            if (! isset($bestEdges[$edgeKey]) || $score > $bestEdges[$edgeKey]['score']) {
                $bestEdges[$edgeKey] = [
                    'score' => $score,
                    'to' => (int) $conversion->to_uom_id,
                    'factor' => (float) $conversion->factor,
                    'from' => (int) $conversion->from_uom_id,
                ];
            }
        }

        $adjacency = [];
        foreach ($bestEdges as $edge) {
            $adjacency[$edge['from']][] = [
                'to' => $edge['to'],
                'factor' => $edge['factor'],
            ];
        }

        $queue = [
            ['uom' => $fromUomId, 'factor' => 1.0, 'depth' => 0],
        ];
        $visited = [$fromUomId => true];

        while (! empty($queue)) {
            $current = array_shift($queue);
            if (! is_array($current)) {
                continue;
            }

            $currentUomId = (int) ($current['uom'] ?? 0);
            $currentFactor = (float) ($current['factor'] ?? 1.0);
            $depth = (int) ($current['depth'] ?? 0);

            if ($depth >= $maxDepth) {
                continue;
            }

            foreach ($adjacency[$currentUomId] ?? [] as $edge) {
                $nextUomId = (int) $edge['to'];
                $nextFactor = $currentFactor * (float) $edge['factor'];

                if ($nextUomId === $toUomId) {
                    return $nextFactor;
                }

                if (isset($visited[$nextUomId])) {
                    continue;
                }

                $visited[$nextUomId] = true;
                $queue[] = [
                    'uom' => $nextUomId,
                    'factor' => $nextFactor,
                    'depth' => $depth + 1,
                ];
            }
        }

        return null;
    }

    /**
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null,max_depth?:int|null}  $context
     */
    protected function upsertScopedConversion(
        int $fromUomId,
        int $toUomId,
        float $factor,
        array $context,
        array $meta
    ): UomConversion {
        $existing = $this->findScopedConversion($fromUomId, $toUomId, $context);
        $scope = $this->scopeContext($context);
        $payload = array_merge($scope, $meta, [
            'from_uom_id' => $fromUomId,
            'to_uom_id' => $toUomId,
            'factor' => $factor,
        ]);
        $payload = $this->filterPayloadForUomConversionsTable($payload);

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return $existing->fresh();
        }

        return UomConversion::query()->create($payload);
    }

    /**
     * Keep only scoping fields that belong to stored conversion rows.
     *
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null,max_depth?:int|null}  $context
     * @return array{branch_id:string|null,item_id:int|null,product_id:string|null}
     */
    protected function scopeContext(array $context): array
    {
        return [
            'branch_id' => isset($context['branch_id']) && $context['branch_id'] !== '' ? (string) $context['branch_id'] : null,
            'item_id' => isset($context['item_id']) && $context['item_id'] !== '' ? (int) $context['item_id'] : null,
            'product_id' => isset($context['product_id']) && $context['product_id'] !== '' ? (string) $context['product_id'] : null,
        ];
    }

    /**
     * Filter payload keys to actual table columns to support mixed schema states.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function filterPayloadForUomConversionsTable(array $payload): array
    {
        static $allowedColumns = null;

        if ($allowedColumns === null) {
            $allowedColumns = Schema::hasTable('uom_conversions')
                ? array_flip(Schema::getColumnListing('uom_conversions'))
                : [];
        }

        if ($allowedColumns === []) {
            return $payload;
        }

        return array_intersect_key($payload, $allowedColumns);
    }

    /**
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null,max_depth?:int|null}  $context
     */
    protected function findScopedConversion(int $fromUomId, int $toUomId, array $context): ?UomConversion
    {
        return UomConversion::query()
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId)
            ->where(function ($query) use ($context) {
                if (! empty($context['branch_id'])) {
                    $query->where('branch_id', $context['branch_id']);
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->where(function ($query) use ($context) {
                if (! empty($context['item_id'])) {
                    $query->where('item_id', $context['item_id']);
                } else {
                    $query->whereNull('item_id');
                }
            })
            ->where(function ($query) use ($context) {
                if (! empty($context['product_id'])) {
                    $query->where('product_id', $context['product_id']);
                } else {
                    $query->whereNull('product_id');
                }
            })
            ->first();
    }

    /**
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null,max_depth?:int|null}  $context
     */
    protected function isApplicableToContext(UomConversion $conversion, array $context): bool
    {
        if ($conversion->branch_id !== null && (string) $conversion->branch_id !== (string) ($context['branch_id'] ?? null)) {
            return false;
        }

        if ($conversion->item_id !== null && (int) $conversion->item_id !== (int) ($context['item_id'] ?? 0)) {
            return false;
        }

        if ($conversion->product_id !== null && (string) $conversion->product_id !== (string) ($context['product_id'] ?? null)) {
            return false;
        }

        return true;
    }

    protected function specificityScore(UomConversion $conversion): int
    {
        $score = 0;
        if (! empty($conversion->product_id)) {
            $score += 100;
        }
        if (! empty($conversion->item_id)) {
            $score += 80;
        }
        if (! empty($conversion->branch_id)) {
            $score += 40;
        }

        return $score;
    }

    /**
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null,max_depth?:int|null}  $context
     * @return array{branch_id:string|null,item_id:int|null,product_id:string|null,max_depth:int|null}
     */
    protected function normalizeContext(array $context): array
    {
        return [
            'branch_id' => isset($context['branch_id']) && $context['branch_id'] !== '' ? (string) $context['branch_id'] : null,
            'item_id' => isset($context['item_id']) && $context['item_id'] !== '' ? (int) $context['item_id'] : null,
            'product_id' => isset($context['product_id']) && $context['product_id'] !== '' ? (string) $context['product_id'] : null,
            'max_depth' => isset($context['max_depth']) ? (int) $context['max_depth'] : null,
        ];
    }

    /**
     * @param  array{is_active?:bool,is_system?:bool,notes?:string|null,created_by_id?:string|null,created_by_type?:string|null}  $meta
     * @return array{is_active:bool,is_system:bool,notes:string|null,created_by_id:string|null,created_by_type:string|null}
     */
    protected function normalizeMeta(array $meta): array
    {
        return [
            'is_active' => (bool) ($meta['is_active'] ?? true),
            'is_system' => (bool) ($meta['is_system'] ?? false),
            'notes' => isset($meta['notes']) && $meta['notes'] !== '' ? (string) $meta['notes'] : null,
            'created_by_id' => isset($meta['created_by_id']) && $meta['created_by_id'] !== '' ? (string) $meta['created_by_id'] : null,
            'created_by_type' => isset($meta['created_by_type']) && $meta['created_by_type'] !== '' ? (string) $meta['created_by_type'] : null,
        ];
    }

    /**
     * @param  int|string|UnitOfMeasure  $uom
     */
    protected function resolveUomId(int|string|UnitOfMeasure $uom): int
    {
        if ($uom instanceof UnitOfMeasure) {
            return (int) $uom->id;
        }

        if (is_int($uom) || ctype_digit((string) $uom)) {
            return (int) $uom;
        }

        $value = trim((string) $uom);
        if ($value === '') {
            throw new InvalidArgumentException('UOM value cannot be empty.');
        }

        $unit = UnitOfMeasure::query()
            ->whereRaw('LOWER(code) = ?', [strtolower($value)])
            ->orWhereRaw('LOWER(symbol) = ?', [strtolower($value)])
            ->orWhereRaw('LOWER(name) = ?', [strtolower($value)])
            ->first();

        if (! $unit) {
            throw new InvalidArgumentException("Unknown UOM value: {$value}");
        }

        return (int) $unit->id;
    }
}
