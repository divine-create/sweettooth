<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only load commands when running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\ImportInventoryData::class,
                \App\Console\Commands\PurgeLegacyRoles::class,
                \App\Console\Commands\GrantAllPermissionsToUser::class,
                \App\Console\Commands\GrantSuperAdminRoleToUser::class,
                \App\Console\Commands\GrantRoleAndPermissionsToUser::class,
                \App\Console\Commands\AssignItemsToBranch::class,
                \App\Console\Commands\AssignRandomItemUnitPrices::class,
                \App\Console\Commands\RebuildItemRequestQuantities::class,
            ]);
        }
    }
}
