<?php

namespace App\Helpers;

use App\Models\BranchCurrencyLocalization;
use App\Models\BranchAccountingCash;
use App\Models\BranchBusinessConfiguration;
use App\Models\BranchCustomerSupplierManagement;
use App\Models\BranchEmployeeManagement;
use App\Models\BranchInventoryManagement;
use App\Models\BranchPosConfiguration;
use App\Models\BranchManagement;
use App\Models\BranchNotificationsAlerts;
use App\Models\BranchReportsAnalytics;
use App\Models\BranchSecurityAccess;
use App\Models\GlobalBranchManagement;
use App\Models\GlobalAccountingCash;
use App\Models\GlobalBusinessConfiguration;
use App\Models\GlobalCurrencyLocalization;
use App\Models\GlobalCustomerSupplierManagement;
use App\Models\GlobalEmployeeManagement;
use App\Models\GlobalInventoryManagement;
use App\Models\GlobalNotificationsAlerts;
use App\Models\GlobalPosConfiguration;
use App\Models\GlobalReportsAnalytics;
use App\Models\GlobalSecurityAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

abstract class Settings
{
    // Cache duration in seconds (5 minutes)
    private const CACHE_TTL = 300;

    // Static cache to avoid repeated queries within the same request
    private static array $models = [];

    /**
     * Get currency and localization settings
     */
    public static function currencyLocalization(string $key, $default = null)
    {
        return self::getSetting(
            GlobalCurrencyLocalization::class,
            BranchCurrencyLocalization::class,
            $key,
            $default
        );
    }

    /**
     * Get localization settings (locale, timezone, formats)
     */
    public static function localizationSettings(string $key, $default = null)
    {
        return self::getSetting(
            GlobalCurrencyLocalization::class,
            BranchCurrencyLocalization::class,
            $key,
            $default
        );
    }

    /**
     * Get business configuration settings
     */
    public static function businessConfiguration(string $key, $default = null)
    {
        return self::getSettingGlobalOnly(
            GlobalBusinessConfiguration::class,
            $key,
            $default
        );
    }

    /**
     * Get POS configuration settings
     */
    public static function posConfiguration(string $key, $default = null)
    {
        return self::getSetting(
            GlobalPosConfiguration::class,
            BranchPosConfiguration::class,
            $key,
            $default
        );
    }

    /**
     * Get accounting and cash settings
     */
    public static function accountingCash(string $key, $default = null)
    {
        return self::getSetting(
            GlobalAccountingCash::class,
            BranchAccountingCash::class,
            $key,
            $default
        );
    }

    /**
     * Get inventory management settings
     */
    public static function inventoryManagement(string $key, $default = null)
    {
        return self::getSetting(
            GlobalInventoryManagement::class,
            BranchInventoryManagement::class,
            $key,
            $default
        );
    }

    /**
     * Get customer and supplier management settings
     */
    public static function customerSupplierManagement(string $key, $default = null)
    {
        return self::getSetting(
            GlobalCustomerSupplierManagement::class,
            BranchCustomerSupplierManagement::class,
            $key,
            $default
        );
    }

    /**
     * Get employee management settings
     */
    public static function employeeManagement(string $key, $default = null)
    {
        return self::getSetting(
            GlobalEmployeeManagement::class,
            BranchEmployeeManagement::class,
            $key,
            $default
        );
    }

    /**
     * Get reports and analytics settings
     */
    public static function reportsAnalytics(string $key, $default = null)
    {
        return self::getSetting(
            GlobalReportsAnalytics::class,
            BranchReportsAnalytics::class,
            $key,
            $default
        );
    }

    /**
     * Get security and access settings
     */
    public static function securityAccess(string $key, $default = null)
    {
        return self::getSetting(
            GlobalSecurityAccess::class,
            BranchSecurityAccess::class,
            $key,
            $default
        );
    }

    /**
     * Get notifications and alerts settings
     */
    public static function notificationsAlerts(string $key, $default = null)
    {
        return self::getSetting(
            GlobalNotificationsAlerts::class,
            BranchNotificationsAlerts::class,
            $key,
            $default
        );
    }

    /**
     * Get branch management settings
     */
    public static function branchManagement(string $key, $default = null)
    {
        return self::getSetting(
            GlobalBranchManagement::class,
            BranchManagement::class,
            $key,
            $default
        );
    }

    /**
     * Generic method to get settings with caching
     */
    private static function getSetting(string $globalModelClass, string $branchModelClass, string $key, $default = null)
    {
        $globalModel = self::getModel($globalModelClass);
        $branchModel = self::getModel($branchModelClass);

        return self::getKeyFromModel($branchModel, $globalModel, $key, $default);
    }

    /**
     * Get setting using only the global model (ignores branch overrides)
     */
    private static function getSettingGlobalOnly(string $globalModelClass, string $key, $default = null)
    {
        $globalModel = self::getModel($globalModelClass);

        return self::getKeyFromModel(null, $globalModel, $key, $default);
    }

    /**
     * Get model instance with caching to prevent repeated queries
     */
    private static function getModel(string $modelClass): ?Model
    {
        // Check static cache first (per-request cache)
        if (isset(self::$models[$modelClass])) {
            return self::$models[$modelClass];
        }

        // Check Laravel cache (cross-request cache)
        $cacheKey = 'settings_model_' . $modelClass;

        $model = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($modelClass) {
            return $modelClass::first();
        });

        // Store in static cache for this request
        self::$models[$modelClass] = $model;

        return $model;
    }


    /**
     * Get value from model with priority logic
     * Priority: Branch settings (when available and set) > Global settings (fallback)
     * Special handling for BranchBusinessConfiguration model which stores some settings in nested arrays
     */
    private static function getKeyFromModel(?Model $branchModel, ?Model $globalModel, string $key, $default = null)
    {
        // Special handling for BranchBusinessConfiguration model
        if ($branchModel !== null) {
            if ($branchModel instanceof BranchBusinessConfiguration) {
                // Handle keys that are stored in nested arrays in BranchBusinessConfiguration
                switch ($key) {
                    case 'auto_backup':
                    case 'backup_interval':
                    case 'backup_period':
                        $storageSettings = $branchModel->storage_settings ?? [];
                        $value = $storageSettings[$key] ?? null;
                        if ($value !== null && $value !== '') {
                            return $value;
                        }
                        break;
                    case 'phone':
                    case 'email':
                    case 'vat_number':
                        $contactDetails = $branchModel->contact_details ?? [];
                        $value = $contactDetails[$key] ?? null;
                        if ($value !== null && $value !== '') {
                            return $value;
                        }
                        break;
                    case 'business_name': // Alias for company_name
                        $branchValue = data_get($branchModel, 'company_name');
                        if ($branchValue !== null && $branchValue !== '') {
                            return $branchValue;
                        }
                        break;
                    default:
                        $branchValue = data_get($branchModel, $key);
                        if ($branchValue !== null && $branchValue !== '') {
                            return $branchValue;
                        }
                }
            } else {
                // For other models, use standard data_get
                $branchValue = data_get($branchModel, $key);
                if ($branchValue !== null && $branchValue !== '') {
                    return $branchValue;
                }
            }
        }

        // Fallback to global settings
        if ($globalModel !== null) {
            if ($globalModel instanceof GlobalBusinessConfiguration) {
                // Handle keys that are stored in nested arrays in GlobalBusinessConfiguration
                switch ($key) {
                    case 'phone':
                    case 'email':
                    case 'vat_number':
                        $contactDetails = $globalModel->contact_details ?? [];
                        $value = $contactDetails[$key] ?? null;
                        if ($value !== null && $value !== '') {
                            return $value;
                        }
                        break;
                    case 'auto_backup':
                    case 'backup_interval':
                    case 'backup_period':
                        // These are direct properties in GlobalBusinessConfiguration
                        $globalValue = data_get($globalModel, $key);
                        return $globalValue ?? $default;
                    case 'business_name': // Alias for company_name
                        $globalValue = data_get($globalModel, 'company_name');
                        return $globalValue ?? $default;
                    default:
                        $globalValue = data_get($globalModel, $key);
                        return $globalValue ?? $default;
                }
            } else {
                $globalValue = data_get($globalModel, $key);
                return $globalValue ?? $default;
            }
        }

        return $default;
    }

    /**
     * Clear all cached settings (useful after updating settings)
     */
    public static function clearCache(): void
    {
        self::$models = [];

        $modelClasses = [
            GlobalCurrencyLocalization::class,
            BranchCurrencyLocalization::class,
            GlobalBusinessConfiguration::class,
            BranchBusinessConfiguration::class,
            GlobalPosConfiguration::class,
            BranchPosConfiguration::class,
            GlobalAccountingCash::class,
            BranchAccountingCash::class,
            GlobalInventoryManagement::class,
            BranchInventoryManagement::class,
            GlobalCustomerSupplierManagement::class,
            BranchCustomerSupplierManagement::class,
            GlobalEmployeeManagement::class,
            BranchEmployeeManagement::class,
            GlobalReportsAnalytics::class,
            BranchReportsAnalytics::class,
            GlobalSecurityAccess::class,
            BranchSecurityAccess::class,
            GlobalNotificationsAlerts::class,
            BranchNotificationsAlerts::class,
            GlobalBranchManagement::class,
            BranchManagement::class,
        ];

        foreach ($modelClasses as $modelClass) {
            Cache::forget('settings_model_' . $modelClass);
        }
    }
}
