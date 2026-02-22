<?php

namespace App\Livewire\BranchDashboard\Settings;

use App\Models\GlobalCurrencyLocalization;
use Livewire\Component;

class CurrencyLocalization extends Component
{
    public $multiCurrency;
    public $primaryCurrency;
    public $defaultLanguage;
    public $dateFormat;
    public $multiTax;

    protected $rules = [
        'multiCurrency' => 'boolean',
        'primaryCurrency' => 'required|string|max:3',
        'defaultLanguage' => 'required|string|max:5',
        'dateFormat' => 'required|string|max:20',
        'multiTax' => 'boolean',
    ];

    public function mount()
    {
        // If user is super admin, load global settings
        if (is_super_admin()) {
            $settings = \App\Models\GlobalCurrencyLocalization::first();

            if ($settings) {
                $this->multiCurrency = $settings->multi_currency === 'enabled';
                $this->primaryCurrency = $settings->primary_currency;
                $this->defaultLanguage = $settings->default_language;
                $this->dateFormat = $settings->date_format;
                $this->multiTax = $settings->multi_tax === 'enabled';
            } else {
                $this->multiCurrency = true;
                $this->primaryCurrency = 'NGN';
                $this->defaultLanguage = 'en';
                $this->dateFormat = 'MM/DD/YYYY';
                $this->multiTax = true;
            }
        } else {
            // For non-super admins, we could potentially load branch-specific settings
            // But currently, currency localization is only global, so we'll keep the same logic
            $settings = \App\Models\GlobalCurrencyLocalization::first();

            if ($settings) {
                $this->multiCurrency = $settings->multi_currency === 'enabled';
                $this->primaryCurrency = $settings->primary_currency;
                $this->defaultLanguage = $settings->default_language;
                $this->dateFormat = $settings->date_format;
                $this->multiTax = $settings->multi_tax === 'enabled';
            } else {
                $this->multiCurrency = true;
                $this->primaryCurrency = 'NGN';
                $this->defaultLanguage = 'en';
                $this->dateFormat = 'MM/DD/YYYY';
                $this->multiTax = true;
            }
        }
    }

    public function save()
    {
        $this->validate();

        try {
            // If user is super admin, save to global settings
            if (is_super_admin()) {
                $settings = \App\Models\GlobalCurrencyLocalization::first();

                if (!$settings) {
                    $settings = new \App\Models\GlobalCurrencyLocalization();
                }

                $settings->multi_currency = $this->multiCurrency ? 'enabled' : 'disabled';
                $settings->primary_currency = $this->primaryCurrency;
                $settings->default_language = $this->defaultLanguage;
                $settings->date_format = $this->dateFormat;
                $settings->multi_tax = $this->multiTax ? 'enabled' : 'disabled';

                $settings->save();

                // Clear settings cache to ensure changes take effect immediately
                \App\Helpers\Settings::clearCache();

                session()->flash('message', 'Global currency & localization settings saved successfully! These settings will apply to all branches.');
            } else {
                // For non-super admins, we could potentially save to branch-specific settings
                // But currently, currency localization is only global, so we'll keep the same logic
                $settings = \App\Models\GlobalCurrencyLocalization::first();

                if (!$settings) {
                    $settings = new \App\Models\GlobalCurrencyLocalization();
                }

                $settings->multi_currency = $this->multiCurrency ? 'enabled' : 'disabled';
                $settings->primary_currency = $this->primaryCurrency;
                $settings->default_language = $this->defaultLanguage;
                $settings->date_format = $this->dateFormat;
                $settings->multi_tax = $this->multiTax ? 'enabled' : 'disabled';

                $settings->save();

                session()->flash('message', 'Currency & Localization settings saved successfully!');
            }
        } catch (\Exception $e) {
            \Log::error('Failed to save currency localization settings: ' . $e->getMessage());
            session()->flash('error', 'Failed to save currency localization settings. Please try again.');
        }
    }

    public function cancel()
    {
        $this->mount(); // Reload original values
        session()->flash('message', 'Changes cancelled. Values reverted to last saved state.');
    }

    public function render()
    {
        return view('livewire.branch-dashboard.settings.currency-localization');
    }
}