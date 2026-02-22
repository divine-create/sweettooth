<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale for the application. This will be used by
    | localization methods to determine the locale when not explicitly set.
    |
    */

    'default' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The locale to use when translations are not available for the current
    | locale. This ensures the application remains functional.
    |
    */

    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | Array of locales that the application supports. Users can select
    | any of these locales for their preferred language/locale.
    |
    */

    'supported_locales' => [
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'ja' => '日本語',
        'zh' => '中文',
    ],

    /*
    |--------------------------------------------------------------------------
    | Date & Time Formatting
    |--------------------------------------------------------------------------
    |
    | Configure how dates and times are formatted for different locales.
    |
    */

    'date_format' => env('DATE_FORMAT', 'Y-m-d'),
    'time_format' => env('TIME_FORMAT', 'H:i:s'),
    'datetime_format' => env('DATETIME_FORMAT', 'Y-m-d H:i:s'),

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    |
    | The application timezone. This is used for date/time operations.
    | It should be one of PHP's supported timezones.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | First Day of Week
    |--------------------------------------------------------------------------
    |
    | Configure which day is considered the first day of the week.
    | 0 = Sunday, 1 = Monday, etc.
    |
    */

    'first_day_of_week' => 0,

    /*
    |--------------------------------------------------------------------------
    | Number Formatting
    |--------------------------------------------------------------------------
    |
    | Configure number formatting options for different locales.
    |
    */

    'decimal_separator' => '.',
    'thousands_separator' => ',',
    'decimal_places' => 2,

    /*
    |--------------------------------------------------------------------------
    | RTL (Right-to-Left) Languages
    |--------------------------------------------------------------------------
    |
    | Languages that use right-to-left text direction.
    | Used for UI layout adjustments.
    |
    */

    'rtl_locales' => [
        'ar', // Arabic
        'he', // Hebrew
        'fa', // Persian
        'ur', // Urdu
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Aliases
    |--------------------------------------------------------------------------
    |
    | Map locale codes to their full ICU locale identifiers.
    | This helps with IntlDateFormatter and NumberFormatter.
    |
    */

    'aliases' => [
        'en' => 'en_US',
        'es' => 'es_ES',
        'fr' => 'fr_FR',
        'de' => 'de_DE',
        'it' => 'it_IT',
        'pt' => 'pt_BR',
        'ja' => 'ja_JP',
        'zh' => 'zh_CN',
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Paths
    |--------------------------------------------------------------------------
    |
    | Paths to translation files. The framework will look in these
    | directories for translation files.
    |
    */

    'translation_paths' => [
        resource_path('lang'),
    ],
];
