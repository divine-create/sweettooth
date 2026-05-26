<?php

namespace App\Livewire\Hooks;

use App\Services\UserActivityService;
use Livewire\ComponentHook;

class ActivityTrackingHook extends ComponentHook
{
    // Livewire lifecycle and infrastructure methods — never meaningful user actions
    protected static array $skipMethods = [
        'render', 'boot', 'mount', 'hydrate', 'dehydrate',
        'hydrating', 'dehydrating', 'booting', 'booted',
        'exception', 'placeholder',
        // Pagination infrastructure
        'gotoPage', 'previousPage', 'nextPage', 'resetPage',
        'setPage', 'paginationView', 'paginators',
        // BaseComponent bulk infrastructure
        'updatedSelectAll', 'toggleBulkMode',
        // Auth/session
        'logout',
    ];

    // Method name prefixes that represent property updates, not intentional actions
    protected static array $skipPrefixes = [
        'updating', 'updated',
        'hydrate', 'dehydrate',
    ];

    public function call($method, $params, $returnEarly): void
    {
        if ($this->shouldSkip($method)) {
            return;
        }

        $user = auth()->user();

        if (!$user) {
            return;
        }

        $component = $this->component;

        defer(fn () => UserActivityService::trackComponentAction(
            $user,
            get_class($component),
            $method,
            $params ?? []
        ));
    }

    protected function shouldSkip(string $method): bool
    {
        if (in_array($method, self::$skipMethods)) {
            return true;
        }

        foreach (self::$skipPrefixes as $prefix) {
            if (str_starts_with($method, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
