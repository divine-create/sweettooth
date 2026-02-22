<?php

namespace App\Helpers;

use App\Services\LocalizationService;
use Carbon\Carbon;

class LocalizationHelper
{
    protected static ?LocalizationService $service = null;

    /**
     * Get the localization service instance
     */
    public static function service(): LocalizationService
    {
        if (self::$service === null) {
            self::$service = new LocalizationService();
        }
        
        return self::$service;
    }

    /**
     * Format a number according to the current locale
     */
    public static function formatNumber(float $number, int $decimals = 2): string
    {
        return self::service()->formatNumber($number, $decimals);
    }

    /**
     * Format currency according to the current locale
     */
    public static function formatCurrency(float $amount, ?string $currency = null): string
    {
        return self::service()->formatCurrency($amount, $currency);
    }

    /**
     * Format percentage
     */
    public static function formatPercentage(float $value, int $decimals = 2): string
    {
        return self::service()->formatPercentage($value, $decimals);
    }

    /**
     * Format date
     */
    public static function formatDate(Carbon|string $date, ?string $format = null): string
    {
        return self::service()->formatDate($date, $format);
    }

    /**
     * Format time
     */
    public static function formatTime(Carbon|string $time, ?string $format = null): string
    {
        return self::service()->formatTime($time, $format);
    }

    /**
     * Format datetime
     */
    public static function formatDateTime(Carbon|string $dateTime, ?string $format = null): string
    {
        return self::service()->formatDateTime($dateTime, $format);
    }

    /**
     * Format short date
     */
    public static function formatShortDate(Carbon|string $date): string
    {
        return self::service()->formatShortDate($date);
    }

    /**
     * Format long date
     */
    public static function formatLongDate(Carbon|string $date): string
    {
        return self::service()->formatLongDate($date);
    }

    /**
     * Format relative time (e.g., "2 hours ago")
     */
    public static function formatRelative(Carbon|string $date): string
    {
        return self::service()->formatRelative($date);
    }

    /**
     * Get day of week name
     */
    public static function getDayOfWeekName(Carbon|string $date): string
    {
        return self::service()->getDayOfWeekName($date);
    }

    /**
     * Get month name
     */
    public static function getMonthName(Carbon|string $date): string
    {
        return self::service()->getMonthName($date);
    }

    /**
     * Get current locale
     */
    public static function getLocale(): string
    {
        return self::service()->getLocale();
    }

    /**
     * Set current locale
     */
    public static function setLocale(string $locale): void
    {
        self::$service = null; // Reset service
        self::service()->setLocale($locale);
    }

    /**
     * Format bytes as readable file size
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        return self::service()->formatBytes($bytes, $precision);
    }

    /**
     * Check if locale uses 12-hour time format
     */
    public static function uses12HourFormat(): bool
    {
        return self::service()->uses12HourFormat();
    }

    /**
     * Check if locale uses RTL text direction
     */
    public static function isRtlLocale(): bool
    {
        $locale = self::getLocale();
        $rtlLocales = config('localization.rtl_locales', []);
        
        return in_array(substr($locale, 0, 2), $rtlLocales);
    }

    /**
     * Get supported locales
     */
    public static function getSupportedLocales(): array
    {
        return config('localization.supported_locales', []);
    }
}
