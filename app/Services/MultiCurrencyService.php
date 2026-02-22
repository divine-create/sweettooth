<?php

namespace App\Services;

use App\Helpers\Settings;
use App\Models\GlEntry;
use Carbon\Carbon;
use Exchanger\ExchangeRate;
use Exchanger\Service\EuropeanCentralBank;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MultiCurrencyService
{
    private string $baseCurrency;
    private EuropeanCentralBank $exchangeService;

    public function __construct()
    {
        $this->baseCurrency = Settings::currencyLocalization('primary_currency', 'NGN');

        // Initialize exchange rate service with ECB provider
        $httpClient = new GuzzleClient();
        $this->exchangeService = new EuropeanCentralBank($httpClient);
    }

    /**
     * Convert amount from one currency to another
     */
    public function convert(
        float $amount,
        string $fromCurrency,
        string $toCurrency,
        Carbon $date = null
    ): float {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $date = $date ?? now();
        $exchangeRate = $this->getExchangeRate($fromCurrency, $toCurrency, $date);

        return floatval($amount * $exchangeRate);
    }

    /**
     * Get exchange rate for date
     */
    public function getExchangeRate(
        string $fromCurrency,
        string $toCurrency,
        Carbon $date = null
    ): float {
        // If currencies are the same, return 1
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        // Use current date if none provided
        $date = $date ?? now();

        // Create cache key
        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}_{$date->format('Y-m-d')}";

        // Try to get from cache first (cache for 1 hour)
        return Cache::remember($cacheKey, 3600, function () use ($fromCurrency, $toCurrency, $date) {
            try {
                // Get exchange rate from ECB
                $exchangeRate = $this->exchangeService->getExchangeRate(
                    new \Exchanger\ExchangeRateQuery($fromCurrency, $toCurrency, $date)
                );

                return $exchangeRate->getValue();
            } catch (\Exception $e) {
                // Log error and fall back to stored rates or approximation
                \Log::warning('Exchange rate retrieval failed', [
                    'from' => $fromCurrency,
                    'to' => $toCurrency,
                    'date' => $date->toDateString(),
                    'error' => $e->getMessage()
                ]);

                // Fallback to stored historical rates or approximation
                return $this->getFallbackExchangeRate($fromCurrency, $toCurrency);
            }
        });
    }

    /**
     * Get fallback exchange rate when API fails
     */
    private function getFallbackExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        // Store some recent known rates as fallback
        $fallbackRates = [
            'USD-EUR' => 0.92,
            'EUR-USD' => 1.09,
            'USD-GBP' => 0.79,
            'GBP-USD' => 1.27,
            'USD-JPY' => 110.50,
            'JPY-USD' => 0.0091,
            'EUR-GBP' => 0.86,
            'GBP-EUR' => 1.16,
        ];

        $key = "{$fromCurrency}-{$toCurrency}";
        return $fallbackRates[$key] ?? 1.0;
    }

    /**
     * Record multi-currency transaction
     */
    public function recordMultiCurrencyTransaction(
        GlEntry $entry,
        string $transactionCurrency,
        string $accountingCurrency = null
    ): array {
        $accountingCurrency = $accountingCurrency ?? $this->baseCurrency;

        if ($transactionCurrency === $accountingCurrency) {
            return [
                'original_amount' => floatval($entry->debit) ?: floatval($entry->credit),
                'original_currency' => $transactionCurrency,
                'converted_amount' => floatval($entry->debit) ?: floatval($entry->credit),
                'converted_currency' => $accountingCurrency,
                'exchange_rate' => 1.0,
                'exchange_gain_loss' => 0,
            ];
        }

        $originalAmount = floatval($entry->debit) ?: floatval($entry->credit);
        $exchangeRate = $this->getExchangeRate($transactionCurrency, $accountingCurrency);
        $convertedAmount = floatval($originalAmount * $exchangeRate);

        return [
            'original_amount' => $originalAmount,
            'original_currency' => $transactionCurrency,
            'converted_amount' => $convertedAmount,
            'converted_currency' => $accountingCurrency,
            'exchange_rate' => $exchangeRate,
            'exchange_gain_loss' => 0, // Calculated at period end
        ];
    }

    /**
     * Calculate unrealized exchange gain/loss at period end
     */
    public function calculateUnrealizedExchangeGainLoss(
        string $currency,
        float $balance,
        string $accountingCurrency = null
    ): float {
        $accountingCurrency = $accountingCurrency ?? $this->baseCurrency;

        if ($currency === $accountingCurrency) {
            return 0;
        }

        // Get current exchange rate
        $currentRate = $this->getExchangeRate($currency, $accountingCurrency);

        // Get historical rate (would need to be stored)
        $historicalRate = 1.0; // Placeholder

        // Calculate gain/loss
        $convertedAtCurrent = floatval($balance * $currentRate);
        $convertedAtHistorical = floatval($balance * $historicalRate);

        return floatval($convertedAtCurrent - $convertedAtHistorical);
    }

    /**
     * Generate multi-currency financial report
     */
    public function generateMultiCurrencyReport(
        array $currencies,
        $period = null,
        string $reportingCurrency = null
    ): array {
        $reportingCurrency = $reportingCurrency ?? $this->baseCurrency;

        $report = [];

        foreach ($currencies as $currency) {
            // Get entries in this currency
            $entries = $this->getEntriesByCurrency($currency, $period);

            // Convert to reporting currency
            $convertedEntries = $entries->map(function ($entry) use ($currency, $reportingCurrency) {
                $debit = floatval($entry['debit']);
                $credit = floatval($entry['credit']);

                $convertedDebit = $this->convert($debit, $currency, $reportingCurrency);
                $convertedCredit = $this->convert($credit, $currency, $reportingCurrency);

                return [
                    'original_debit' => $debit,
                    'original_credit' => $credit,
                    'converted_debit' => $convertedDebit,
                    'converted_credit' => $convertedCredit,
                ];
            });

            $report[$currency] = [
                'currency' => $currency,
                'exchange_rate_to_reporting' => $this->getExchangeRate($currency, $reportingCurrency),
                'entries_count' => $entries->count(),
                'total_debits' => $convertedEntries->sum('converted_debit'),
                'total_credits' => $convertedEntries->sum('converted_credit'),
            ];
        }

        return [
            'reporting_currency' => $reportingCurrency,
            'currencies' => $report,
            'consolidation_total_debits' => collect($report)->sum('total_debits'),
            'consolidation_total_credits' => collect($report)->sum('total_credits'),
        ];
    }

    /**
     * Get entries by currency
     */
    private function getEntriesByCurrency(string $currency, $period = null): Collection
    {
        // Query GL entries that have this currency
        $query = GlEntry::where('currency', $currency);

        if ($period) {
            // Apply period filtering based on your period structure
            $query->whereBetween('created_at', [$period->getStartDate(), $period->getEndDate()]);
        }

        return $query->get();
    }

    /**
     * Store historical exchange rate for revaluation purposes
     */
    public function storeHistoricalExchangeRate(string $fromCurrency, string $toCurrency, float $rate, Carbon $date): void
    {
        // This would store rates in a dedicated table for historical tracking
        // For now, we'll use cache with a longer TTL
        $cacheKey = "historical_exchange_rate_{$fromCurrency}_{$toCurrency}_{$date->format('Y-m-d')}";
        Cache::put($cacheKey, $rate, now()->addDays(365)); // Keep for 1 year
    }

    /**
     * Get historical exchange rate
     */
    public function getHistoricalExchangeRate(string $fromCurrency, string $toCurrency, Carbon $date): ?float
    {
        $cacheKey = "historical_exchange_rate_{$fromCurrency}_{$toCurrency}_{$date->format('Y-m-d')}";
        return Cache::get($cacheKey);
    }

    /**
     * Set base currency
     */
    public function setBaseCurrency(string $currency): void
    {
        $this->baseCurrency = $currency;
    }

    /**
     * Get base currency
     */
    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): array
    {
        return [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'JPY' => 'Japanese Yen',
            'CAD' => 'Canadian Dollar',
            'AUD' => 'Australian Dollar',
            'CHF' => 'Swiss Franc',
            'CNY' => 'Chinese Yuan',
            'INR' => 'Indian Rupee',
            'MXN' => 'Mexican Peso',
        ];
    }

    /**
     * Perform currency revaluation at period end
     */
    public function performCurrencyRevaluation($period): array
    {
        $revaluations = [];
        $totalGainLoss = 0;

        try {
            // Get all foreign currency balances that need revaluation
            $foreignBalances = $this->getForeignCurrencyBalances($period);

            foreach ($foreignBalances as $balance) {
                $currentRate = $this->getExchangeRate($balance['currency'], $this->baseCurrency);
                $historicalRate = $balance['historical_rate'];

                // Calculate gain/loss
                $currentValue = $balance['balance'] * $currentRate;
                $historicalValue = $balance['balance'] * $historicalRate;
                $gainLoss = $currentValue - $historicalValue;

                if (abs($gainLoss) > 0.01) { // Only record significant differences
                    $revaluations[] = [
                        'currency' => $balance['currency'],
                        'balance' => $balance['balance'],
                        'historical_rate' => $historicalRate,
                        'current_rate' => $currentRate,
                        'historical_value' => $historicalValue,
                        'current_value' => $currentValue,
                        'gain_loss' => $gainLoss,
                        'account_id' => $balance['account_id'],
                    ];

                    $totalGainLoss += $gainLoss;
                }
            }

            return [
                'period' => $period->getDisplayName(),
                'revaluations' => $revaluations,
                'total_gain_loss' => $totalGainLoss,
                'status' => 'completed',
                'processed_at' => now()->toIso8601String(),
            ];

        } catch (\Exception $e) {
            \Log::error('Currency revaluation failed', [
                'period' => $period->getDisplayName(),
                'error' => $e->getMessage()
            ]);

            return [
                'period' => $period->getDisplayName(),
                'revaluations' => [],
                'total_gain_loss' => 0,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get foreign currency balances for revaluation
     */
    private function getForeignCurrencyBalances($period): array
    {
        // This would query your GL entries or balance table
        // For now, return empty array as placeholder
        // TODO: Implement actual balance retrieval logic
        return [];
    }
}
