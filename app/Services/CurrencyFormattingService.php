<?php

namespace App\Services;

use App\Helpers\Settings;

class CurrencyFormattingService
{
    /**
     * Currency symbol mapping
     */
    private const CURRENCY_SYMBOLS = [
        'NGN' => '₦',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'GHS' => '₵',
        'INR' => '₹',
        'JPY' => '¥',
        'CNY' => '¥',
        'AUD' => 'A$',
        'CAD' => 'C$',
        'CHF' => 'CHF',
        'SEK' => 'kr',
        'NZD' => 'NZ$',
    ];

    /**
     * Format amount as currency string
     *
     * @param float $amount
     * @param string|null $currency
     * @param int $decimals
     * @return string
     */
    public function format(float $amount, ?string $currency = null, int $decimals = 2): string
    {
        $currency = $currency ?? Settings::currencyLocalization('primary_currency', 'NGN');
        $symbol = $this->getSymbol($currency);
        
        return $symbol . ' ' . number_format($amount, $decimals, '.', ',');
    }

    /**
     * Format amount without symbol
     *
     * @param float $amount
     * @param int $decimals
     * @return string
     */
    public function formatAmount(float $amount, int $decimals = 2): string
    {
        return number_format($amount, $decimals, '.', ',');
    }

    /**
     * Format amount with currency and locale support
     *
     * @param float $amount
     * @param string|null $currency
     * @param string|null $locale
     * @return string
     */
    public function formatLocalized(float $amount, ?string $currency = null, ?string $locale = null): string
    {
        $currency = $currency ?? Settings::currencyLocalization('primary_currency', 'NGN');
        $locale = $locale ?? Settings::currencyLocalization('default_language', 'en_US');
        
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        
        return $formatter->formatCurrency($amount, $currency);
    }

    /**
     * Get currency symbol
     *
     * @param string $currency
     * @return string
     */
    public function getSymbol(string $currency): string
    {
        return self::CURRENCY_SYMBOLS[$currency] ?? $currency;
    }

    /**
     * Get currency code
     *
     * @return string
     */
    public function getPrimaryCurrency(): string
    {
        return Settings::currencyLocalization('primary_currency', 'NGN');
    }

    /**
     * Check if multi-currency is enabled
     *
     * @return bool
     */
    public function isMultiCurrencyEnabled(): bool
    {
        $setting = Settings::currencyLocalization('multi_currency', 'disabled');
        
        return $setting === 'enabled';
    }

    /**
     * Get list of available currencies
     *
     * @return array
     */
    public function getAvailableCurrencies(): array
    {
        return Settings::currencyLocalization('currency_list', ['NGN', 'USD', 'EUR', 'GBP']);
    }

    /**
     * Format percentage
     *
     * @param float $value
     * @param int $decimals
     * @return string
     */
    public function formatPercentage(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals, '.', ',') . '%';
    }

    /**
     * Convert amount between currencies (requires exchange rate data)
     *
     * @param float $amount
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param float|null $exchangeRate
     * @return float
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency, ?float $exchangeRate = null): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        // If exchange rate not provided, would need to fetch from service
        // For now, return amount as-is if no rate provided
        if ($exchangeRate === null) {
            return $amount;
        }

        return $amount * $exchangeRate;
    }

    /**
     * Get date format from settings
     *
     * @return string
     */
    public function getDateFormat(): string
    {
        return Settings::currencyLocalization('date_format', 'Y-m-d');
    }

    /**
     * Format date according to locale settings
     *
     * @param \DateTime|string $date
     * @param string|null $format
     * @return string
     */
    public function formatDate($date, ?string $format = null): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        $format = $format ?? $this->getDateFormat();

        return $date->format($format);
    }
}
