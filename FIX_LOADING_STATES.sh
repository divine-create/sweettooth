#!/bin/bash

# Loading State Fix Script
# Clears caches and resets Alpine/Livewire state

echo "🔧 Fixing Platform-Wide Loading State Issue..."

# Step 1: Clear Laravel caches
echo "1️⃣ Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Step 2: Clear compiled assets
echo "2️⃣ Clearing compiled assets..."
rm -rf public/build
rm -rf node_modules/.vite

# Step 3: Rebuild assets
echo "3️⃣ Rebuilding front-end assets..."
npm run build

# Step 4: Optimize
echo "4️⃣ Running Laravel optimization..."
php artisan optimize

# Step 5: Done
echo ""
echo "✅ All caches cleared and assets rebuilt!"
echo ""
echo "📋 Next Steps:"
echo "1. Hard refresh your browser (Ctrl+Shift+R or Cmd+Shift+R)"
echo "2. Open DevTools Console (F12)"
echo "3. Check for any JavaScript errors"
echo "4. Test a page with tables/selects"
echo ""
echo "🐛 If loaders still show:"
echo "   - Check console for errors: console.log(Alpine, Livewire)"
echo "   - Check Network tab for failed requests"
echo "   - Review this file: PLATFORM_WIDE_LOADING_ISSUE_DIAGNOSIS.md"
