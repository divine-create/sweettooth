<?php

namespace App\Console\Commands;

use App\Models\GlobalCurrencyLocalization;
use Illuminate\Console\Command;

class UpdateCurrencySettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-currency-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update currency settings to ensure NGN (Nigerian Naira) is the primary currency';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating currency settings to use NGN (Nigerian Naira) as primary currency...');

        // Check if GlobalCurrencyLocalization record exists
        $currencySettings = GlobalCurrencyLocalization::first();

        if (!$currencySettings) {
            // Create the record if it doesn't exist
            $currencySettings = GlobalCurrencyLocalization::create([
                'multi_currency' => 'enabled',
                'primary_currency' => 'NGN',
                'primary_currency_exchange_rate' => null,
                'currency_list' => ['USD', 'EUR', 'GBP', 'INR', 'NGN'],
                'multi_tax' => 'enabled',
                'default_language' => 'en',
                'language_options' => ['en', 'es', 'fr', 'ar'],
                'date_format' => 'MM/DD/YYYY',
                'units_of_measure' => ['piece', 'kg', 'liter'],
            ]);

            $this->info('Created new Global Currency Localization with NGN as primary currency.');
        } else {
            // Update existing record to ensure primary currency is NGN
            $originalCurrency = $currencySettings->primary_currency;
            $currencySettings->update([
                'primary_currency' => 'NGN',
                'currency_list' => ['USD', 'EUR', 'GBP', 'INR', 'NGN'],
            ]);

            if ($originalCurrency !== 'NGN') {
                $this->info("Updated primary currency from {$originalCurrency} to NGN.");
            } else {
                $this->info('Primary currency is already set to NGN.');
            }
        }

        // Clear settings cache to ensure fresh settings are loaded
        \App\Helpers\Settings::clearCache();

        $this->info('Currency settings updated successfully!');
        $this->info('POS system will now display prices in ₦ (Naira) instead of other currencies.');
    }
}
