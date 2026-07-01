<?php

namespace App\Support;

use App\Models\Department;

/**
 * Resolves "combined sales points" — sales departments whose products are sold
 * and counted from a single point of sale. Configured in config/sales.php,
 * keyed by department slug so it is stable across environments.
 *
 * @see config/sales.php
 */
class CombinedSalesPoints
{
    /**
     * @return array<string, array<int, string>>
     */
    protected static function map(): array
    {
        return (array) config('sales.combined_points', []);
    }

    /**
     * The primary (owning) slug for a given slug. Returns the slug itself when
     * it is already a primary or is not part of any combined point.
     */
    public static function primarySlug(string $slug): string
    {
        foreach (self::map() as $primary => $members) {
            if ($slug === $primary || in_array($slug, (array) $members, true)) {
                return (string) $primary;
            }
        }

        return $slug;
    }

    /**
     * True when the slug is a non-primary member of a combined point (i.e. it
     * should be hidden as a standalone sales point in the sidebar).
     */
    public static function isMember(string $slug): bool
    {
        foreach (self::map() as $primary => $members) {
            if ($slug !== $primary && in_array($slug, (array) $members, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * All slugs combined into the same point as $slug (primary first). Returns
     * just [$slug] when it is not part of any combined point.
     *
     * @return array<int, string>
     */
    public static function groupSlugs(string $slug): array
    {
        $primary = self::primarySlug($slug);
        $members = (array) (self::map()[$primary] ?? []);

        return array_values(array_unique(array_merge([$primary], $members)));
    }

    /**
     * Department ids for every department combined into the same point as
     * $slug, scoped to the given branch (plus global departments). Returns an
     * empty array when $slug is not part of any combined point, so callers can
     * safely merge the result into their existing scope.
     *
     * @param  string|int|null  $branchId  Branch id (UUID string in this app).
     * @return array<int, int>
     */
    public static function departmentIds($branchId, string $slug): array
    {
        $slugs = self::groupSlugs($slug);
        if (count($slugs) <= 1) {
            return [];
        }

        return Department::query()
            ->whereIn('slug', $slugs)
            ->where(function ($query) use ($branchId) {
                if ($branchId !== null && $branchId !== '') {
                    $query->where('branch_id', $branchId);
                }
                $query->orWhereNull('branch_id');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
