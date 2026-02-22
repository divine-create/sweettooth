<?php

/**
 * Server-side Loader State Diagnostic
 * 
 * Run from command line:
 * php check-loader-issues.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║        LOADER STATE - SERVER-SIDE DIAGNOSTICS             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// 1. Check Laravel Version
echo "1️⃣  LARAVEL SETUP\n";
echo "─────────────────────────────────────────────────────────────\n";

if (file_exists('artisan')) {
    echo "   ✓ Laravel installation found\n";
    
    // Get Laravel version from composer.json
    $composer = json_decode(file_get_contents('composer.json'), true);
    $laravelVersion = $composer['require']['laravel/framework'] ?? 'unknown';
    echo "   ✓ Laravel version: $laravelVersion\n";
} else {
    echo "   ✗ Laravel installation not found\n";
}

// 2. Check Dependencies
echo "\n2️⃣  DEPENDENCIES\n";
echo "─────────────────────────────────────────────────────────────\n";

$composer = json_decode(file_get_contents('composer.json'), true);
$dependencies = [
    'livewire/flux' => 'Livewire Flux',
    'livewire/volt' => 'Livewire Volt',
    'tallstackui/tallstackui' => 'TallStackUI',
];

foreach ($dependencies as $package => $name) {
    if (isset($composer['require'][$package])) {
        $version = $composer['require'][$package];
        echo "   ✓ $name: $version\n";
    } else {
        echo "   ✗ $name: NOT FOUND\n";
    }
}

// 3. Check View Files
echo "\n3️⃣  VIEW FILES\n";
echo "─────────────────────────────────────────────────────────────\n";

$filesToCheck = [
    'resources/views/components/layouts/app/branch-dashboard.blade.php' => 'Main Layout',
    'resources/views/partials/head.blade.php' => 'Head Partial',
    'resources/views/livewire/branch-dashboard/inventory/partials/stocks-edit-modal.blade.php' => 'Stocks Modal (FIXED)',
];

foreach ($filesToCheck as $file => $name) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for specific patterns
        if ($name === 'Stocks Modal (FIXED)') {
            if (strpos($content, 'isLoading: false') !== false) {
                echo "   ✓ $name: FIXED (isLoading: false)\n";
            } elseif (strpos($content, 'isLoading: true') !== false) {
                echo "   ✗ $name: NOT FIXED (isLoading: true)\n";
            }
        } else {
            echo "   ✓ $name: exists\n";
        }
    } else {
        echo "   ✗ $name: NOT FOUND\n";
    }
}

// 4. Check Asset Files
echo "\n4️⃣  ASSET CONFIGURATION\n";
echo "─────────────────────────────────────────────────────────────\n";

$assets = [
    'resources/css/app.css' => 'CSS',
    'resources/js/app.js' => 'JavaScript',
    'vite.config.js' => 'Vite Config',
    'package.json' => 'npm Package',
    'composer.json' => 'Composer',
];

foreach ($assets as $file => $name) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "   ✓ $name: $size bytes\n";
    } else {
        echo "   ✗ $name: NOT FOUND\n";
    }
}

// 5. Check Build Status
echo "\n5️⃣  BUILD STATUS\n";
echo "─────────────────────────────────────────────────────────────\n";

if (is_dir('public/build')) {
    $files = count(array_diff(scandir('public/build'), ['.', '..']));
    echo "   ✓ Build directory exists ($files files)\n";
} else {
    echo "   ⚠ Build directory not found - run: npm run build\n";
}

if (file_exists('public/hot')) {
    echo "   ℹ Development mode (Vite HMR active)\n";
}

// 6. Check Views Cache
echo "\n6️⃣  CACHE STATUS\n";
echo "─────────────────────────────────────────────────────────────\n";

$cacheDirs = [
    'storage/framework/views' => 'Blade Cache',
    'bootstrap/cache' => 'Bootstrap Cache',
];

foreach ($cacheDirs as $dir => $name) {
    if (is_dir($dir)) {
        $files = count(array_diff(scandir($dir), ['.', '..']));
        echo "   ℹ $name: $files files\n";
    } else {
        echo "   ✗ $name: NOT FOUND\n";
    }
}

// 7. Recommendations
echo "\n7️⃣  RECOMMENDATIONS\n";
echo "─────────────────────────────────────────────────────────────\n";

$recommendations = [
    'Run: php artisan view:clear' => 'Clear cached views',
    'Run: php artisan config:clear' => 'Clear config cache',
    'Run: npm run build' => 'Rebuild assets',
    'Run: php artisan optimize' => 'Optimize application',
    'Hard refresh browser' => 'Ctrl+Shift+R or Cmd+Shift+R',
];

foreach ($recommendations as $action => $description) {
    echo "   ▸ $action - $description\n";
}

echo "\n8️⃣  QUICK FIX SCRIPT\n";
echo "─────────────────────────────────────────────────────────────\n";

echo "Run this to fix cache issues:\n\n";
echo "php artisan config:clear && \\\n";
echo "php artisan cache:clear && \\\n";
echo "php artisan view:clear && \\\n";
echo "php artisan route:clear && \\\n";
echo "npm run build && \\\n";
echo "php artisan optimize\n\n";

echo "Or use the included script:\n";
echo "chmod +x ./FIX_LOADING_STATES.sh\n";
echo "./FIX_LOADING_STATES.sh\n\n";

echo "9️⃣  TESTING\n";
echo "─────────────────────────────────────────────────────────────\n";

echo "After running the fix:\n";
echo "1. Open browser DevTools (F12)\n";
echo "2. Go to Console tab\n";
echo "3. Copy and run this:\n\n";

echo "console.log({\n";
echo "    alpine: typeof Alpine !== 'undefined',\n";
echo "    livewire: typeof Livewire !== 'undefined',\n";
echo "    spinners: document.querySelectorAll('.animate-spin').length,\n";
echo "});\n\n";

echo "All should be true except spinners should be 0 or very few.\n\n";

echo "🎯 NEXT STEPS\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "1. Run cache clearing commands above\n";
echo "2. Hard refresh your browser\n";
echo "3. Test pages with tables/selects\n";
echo "4. Check browser console for errors (F12)\n";
echo "5. If still broken, use: ADVANCED_LOADER_DIAGNOSTIC.html\n";
echo "   (Open in browser, run diagnostics)\n\n";

echo "✓ Diagnostic complete!\n\n";
