<?php

namespace App\Http\Controllers;

use App\Helpers\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    public function getSwtcPng()
    {
        // Get the uploaded logo from settings
        $logoPath = Settings::businessConfiguration('logo_upload');

        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            // Get the logo image
            $logo = Storage::disk('public')->get($logoPath);

            // Return the image with appropriate content type
            $mimeType = Storage::mimeType($logoPath);
            return response($logo)
                ->header('Content-Type', $mimeType)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        // Fallback to default swtc.png or create a simple placeholder
        $defaultSwtc = public_path('swtc.png');
        if (file_exists($defaultSwtc)) {
            return response()->file($defaultSwtc)
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        // If no default exists, return a simple SVG as PNG (base64 encoded)
        // This is a simple green square as a placeholder
        $svgPlaceholder = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
    <rect width="200" height="200" fill="#10b981"/>
    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="24" fill="white">ST</text>
</svg>';

        // Convert SVG to response
        return response($svgPlaceholder)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function getManifest()
    {
        // Get business configuration
        $businessName = \App\Helpers\Settings::businessConfiguration('business_name', config('app.name', 'SweetTooth POS'));
        $logoPath = \App\Helpers\Settings::businessConfiguration('logo_upload');
        
        // Determine theme color from appearance settings
        $appearanceSettings = \App\Helpers\Settings::businessConfiguration('appearance_settings', []);
        $primaryColor = $appearanceSettings['primary_color'] ?? '#10b981';

        // Prepare manifest data
        $manifest = [
            'name' => $businessName,
            'short_name' => substr($businessName, 0, 12), // Short name limited to 12 chars
            'description' => 'Sweet Tooth Point of Sale and Management System',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => $primaryColor,
            'orientation' => 'portrait-primary',
            'icons' => [
                [
                    'src' => $logoPath ? asset(Storage::disk('public')->url($logoPath)) : '/swtc.png',
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ],
                [
                    'src' => $logoPath ? asset(Storage::disk('public')->url($logoPath)) : '/swtc.png',
                    'sizes' => '512x512',
                    'type' => 'image/png'
                ]
            ]
        ];

        return response()->json($manifest)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
