<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Cache;

trait CachesDepartments
{
    protected function bumpDepartmentCacheVersion(): void
    {
        // Increment a tiny counter — extremely fast, even on file cache
        $version = Cache::get('dept_cache_version', 0) + 1;
        Cache::forever('dept_cache_version', $version);
    }

    protected function getCategoryCacheVersion(): int
    {
        return Cache::get('dept_cache_version', 0);
    }
}