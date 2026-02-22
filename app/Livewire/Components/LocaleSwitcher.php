<?php

namespace App\Livewire\Components;

use App\Helpers\LocalizationHelper;
use Livewire\Component;
use Livewire\Attributes\Computed;

class LocaleSwitcher extends Component
{
    public string $currentLocale = '';
    public bool $showDropdown = false;

    public function mount(): void
    {
        $this->currentLocale = LocalizationHelper::getLocale();
    }

    public function switchLocale(string $locale): void
    {
        if (!array_key_exists($locale, $this->supportedLocales())) {
            $this->toast()->error('Invalid locale selected.')->send();
            return;
        }

        // Update user preference (if user is logged in)
        if (auth()->check()) {
            auth()->user()->update(['preferred_locale' => $locale]);
        }

        // Set session locale
        session(['locale' => $locale]);

        // Update app locale
        app()->setLocale($locale);
        LocalizationHelper::setLocale($locale);

        $this->currentLocale = $locale;
        $this->showDropdown = false;

        // Dispatch event to refresh UI
        $this->dispatch('locale-changed', locale: $locale);

        $this->toast()->success('Language changed to ' . $this->supportedLocales()[$locale])->send();
    }

    #[Computed]
    public function supportedLocales(): array
    {
        return LocalizationHelper::getSupportedLocales();
    }

    #[Computed]
    public function currentLocaleName(): string
    {
        return $this->supportedLocales()[$this->currentLocale] ?? $this->currentLocale;
    }

    #[Computed]
    public function isRtl(): bool
    {
        return LocalizationHelper::isRtlLocale();
    }

    public function render()
    {
        return view('livewire.components.locale-switcher');
    }
}
