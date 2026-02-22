<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
@php
    use App\Helpers\Color;
    use App\Helpers\Settings;

    $appearanceSettings = Settings::businessConfiguration('appearance_settings', []);
    $primaryColor = $appearanceSettings['primary_color'] ?? '#10b981';
    $themeMode = $appearanceSettings['theme_mode'] ?? 'system';
    $accentColor = Color::darkenHex($primaryColor, 15);
    $primaryMuted = Color::lightenHex($primaryColor, 40);
    $pageBackground = Color::lightenHex($primaryColor, 70);
    $primaryContrast = Color::contrastColor($primaryColor);
@endphp

<meta name="theme-color" content="{{ $primaryColor }}">

<title>{{ $title ?? \App\Helpers\Settings::businessConfiguration('business_name', config('app.name')) }}</title>
<meta name="description" content="Sweet Tooth Point of Sale and Management System">

<!-- PWA Manifest -->
<link rel="manifest" href="/manifest.json">

<!-- Favicons -->
@php
    use Illuminate\Support\Facades\Storage;

    $logoPath = \App\Helpers\Settings::businessConfiguration('logo_upload');
    $hasLogo = $logoPath && Storage::disk('public')->exists($logoPath);
    $cacheBuster = $hasLogo ? Storage::disk('public')->lastModified($logoPath) : 'default';
    $faviconUrl = $hasLogo ? Storage::disk('public')->url($logoPath) : '/favicon.ico';
@endphp
<link rel="icon" href="{{ $faviconUrl }}?v={{ $cacheBuster }}" sizes="any">
<link rel="icon" href="{{ $faviconUrl }}?v={{ $cacheBuster }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}?v={{ $cacheBuster }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
<tallstackui:script />
@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
<style>
    :root {
        --color-primary: {{ $primaryColor }};
        --color-accent: {{ $accentColor }};
        --color-accent-content: {{ $accentColor }};
        --color-accent-foreground: #ffffff;
        --color-primary-muted: {{ $primaryMuted }};
        --color-primary-foreground: {{ $primaryContrast }};
        --color-background: {{ $pageBackground }};
    }

    :root.dark {
        --color-primary: {{ $primaryColor }};
        --color-accent: {{ $accentColor }};
        --color-accent-content: {{ $accentColor }};
        --color-accent-foreground: #ffffff;
        --color-primary-muted: {{ $primaryMuted }};
        --color-primary-foreground: {{ $primaryContrast }};
        --color-background: {{ $pageBackground }};
    }
</style>
<script>
    // Apply theme immediately to prevent flash of wrong theme
    (function() {
        var themeMode = <?php echo json_encode($themeMode); ?>;
        var applyDark = function() { document.documentElement.classList.add('dark'); }
        var applyLight = function() { document.documentElement.classList.remove('dark'); }
        
        if (themeMode === 'system') {
            var media = window.matchMedia('(prefers-color-scheme: dark)');
            media.matches ? applyDark() : applyLight();
        } else if (themeMode === 'dark') {
            applyDark();
        } else {
            applyLight();
        }
    })();
</script>
<script>
    if (window.Flux && typeof window.Flux.applyAppearance === 'function') {
        window.Flux.applyAppearance(<?php echo json_encode($themeMode); ?>);
    }
</script>
