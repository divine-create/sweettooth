<?php

namespace App\Services;

use App\Helpers\Settings;
use Carbon\Carbon;
use IntlDateFormatter;
use NumberFormatter;

class LocalizationService
{
    protected ?string $locale = null;
    protected ?NumberFormatter $numberFormatter = null;
    protected ?NumberFormatter $currencyFormatter = null;
    protected ?IntlDateFormatter $dateFormatter = null;
    protected ?IntlDateFormatter $timeFormatter = null;
    protected ?IntlDateFormatter $dateTimeFormatter = null;

    public function __construct(?string $locale = null)
    {
        $this->locale = $locale ?? Settings::localizationSettings('preferred_locale', config('app.locale', 'en'));
        $this->initializeFormatters();
    }

    /**
     * Initialize IntlDateFormatter and NumberFormatter instances
     */
    protected function initializeFormatters(): void
    {
        // Number formatter for general numbers
        $this->numberFormatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
        
        // Currency formatter
        $this->currencyFormatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
        
        // Date formatter
        $this->dateFormatter = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::NONE
        );
        
        // Time formatter
        $this->timeFormatter = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::MEDIUM
        );
        
        // DateTime formatter
        $this->dateTimeFormatter = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::MEDIUM
        );
    }

    /**
     * Get current locale
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Set locale
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        $this->initializeFormatters();
        return $this;
    }

    /**
     * Format number according to locale
     */
    public function formatNumber(float $number, int $decimals = 2): string
    {
        if ($this->numberFormatter) {
            $this->numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
            return $this->numberFormatter->format($number);
        }
        
        return number_format($number, $decimals);
    }

    /**
     * Format currency with locale-specific symbol
     */
    public function formatCurrency(float $amount, ?string $currency = null): string
    {
        $currency = $currency ?? Settings::currencyLocalization('primary_currency', 'USD');
        
        if ($this->currencyFormatter) {
            return $this->currencyFormatter->formatCurrency($amount, $currency);
        }
        
        return number_format($amount, 2);
    }

    /**
     * Format percentage
     */
    public function formatPercentage(float $value, int $decimals = 2): string
    {
        if ($this->numberFormatter) {
            $this->numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
            return $this->numberFormatter->format($value / 100) . '%';
        }
        
        return number_format($value, $decimals) . '%';
    }

    /**
     * Format date according to locale
     */
    public function formatDate(\DateTime|Carbon|string $date, ?string $format = null): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        
        if ($format) {
            return $date->format($format);
        }
        
        if ($this->dateFormatter) {
            return $this->dateFormatter->format($date->timestamp);
        }
        
        return $date->format('M d, Y');
    }

    /**
     * Format time according to locale
     */
    public function formatTime(\DateTime|Carbon|string $time, ?string $format = null): string
    {
        if (is_string($time)) {
            $time = Carbon::parse($time);
        }
        
        if ($format) {
            return $time->format($format);
        }
        
        if ($this->timeFormatter) {
            return $this->timeFormatter->format($time->timestamp);
        }
        
        return $time->format('h:i A');
    }

    /**
     * Format datetime according to locale
     */
    public function formatDateTime(\DateTime|Carbon|string $dateTime, ?string $format = null): string
    {
        if (is_string($dateTime)) {
            $dateTime = Carbon::parse($dateTime);
        }
        
        if ($format) {
            return $dateTime->format($format);
        }
        
        if ($this->dateTimeFormatter) {
            return $this->dateTimeFormatter->format($dateTime->timestamp);
        }
        
        return $dateTime->format('M d, Y h:i A');
    }

    /**
     * Format short date (e.g., 12/25/2024)
     */
    public function formatShortDate(\DateTime|Carbon|string $date): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        
        $formatter = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE
        );
        
        return $formatter->format($date->timestamp);
    }

    /**
     * Format long date (e.g., December 25, 2024)
     */
    public function formatLongDate(\DateTime|Carbon|string $date): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        
        $formatter = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE
        );
        
        return $formatter->format($date->timestamp);
    }

    /**
     * Parse localized date string
     */
    public function parseDate(string $dateString): ?Carbon
    {
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get locale display name
     */
    public function getLocaleDisplayName(string $locale = null): string
    {
        $locale = $locale ?? $this->locale;
        return \Locale::getDisplayName($locale, $locale) ?: $locale;
    }

    /**
     * Get list of supported locales
     */
    public function getSupportedLocales(): array
    {
        return Settings::localizationSettings('supported_locales', [
            'en' => 'English',
            'es' => 'Español',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'ru' => 'Русский',
            'ja' => 'Japanese',
            'zh' => '中文',
        ]);
    }

    /**
     * Get timezone offset for locale
     */
    public function getTimezoneForLocale(): string
    {
        return Settings::localizationSettings('timezone', 'UTC');
    }

    /**
     * Format relative time (e.g., "2 hours ago")
     */
    public function formatRelative(\DateTime|Carbon|string $date): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        
        return $date->diffForHumans();
    }

    /**
     * Get day of week name
     */
    public function getDayOfWeekName(\DateTime|Carbon|string $date): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        
        $formatter = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            null,
            null,
            'EEEE' // Full weekday name
        );
        
        return $formatter->format($date->timestamp);
    }

    /**
     * Get month name
     */
    public function getMonthName(\DateTime|Carbon|string $date): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        
        $formatter = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            null,
            null,
            'MMMM' // Full month name
        );
        
        return $formatter->format($date->timestamp);
    }

    /**
     * Get short month name
     */
    public function getShortMonthName(\DateTime|Carbon|string $date): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        
        $formatter = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            null,
            null,
            'MMM' // Short month name
        );
        
        return $formatter->format($date->timestamp);
    }

    /**
     * Check if locale uses 12-hour time format
     */
    public function uses12HourFormat(): bool
    {
        $patterns = \Locale::getKeywordsForLocale($this->locale);
        // This is a simplified check; in production, consult ICU data
        return in_array($this->locale, ['en', 'en_US', 'en_AU', 'en_PH']);
    }

    /**
     * Get first day of week for locale (0=Sunday, 1=Monday)
     */
    public function getFirstDayOfWeek(): int
    {
        return Settings::localizationSettings('first_day_of_week', 0);
    }

    /**
     * Format file size
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
