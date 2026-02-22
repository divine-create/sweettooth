<?php

namespace App\Http\Controllers;

use App\Helpers\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FaviconController extends Controller
{
    public function getFavicon()
    {
        // Get the uploaded logo from settings
        $logoPath = Settings::businessConfiguration('logo_upload');

        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            // Get the logo image
            $logo = Storage::disk('public')->get($logoPath);

            // Return the image with appropriate content type
            // For favicon, we'll serve the image as PNG since most browsers support it
            // and it's easier than converting to ICO
            $mimeType = Storage::mimeType($logoPath);

            // Ensure we're sending a PNG for favicon (most browsers support this)
            // If it's not PNG/JPG, we'll serve as-is but with proper headers
            return response($logo)
                ->header('Content-Type', $mimeType)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        // Fallback to default favicon
        $defaultFavicon = public_path('favicon.ico');
        if (file_exists($defaultFavicon)) {
            return response()->file($defaultFavicon)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        // If no default exists, create a simple fallback
        return response('', 204); // No content
    }

    public function getSvgFavicon()
    {
        // Get the uploaded logo from settings
        $logoPath = Settings::businessConfiguration('logo_upload');

        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            // Get the logo image
            $logo = Storage::disk('public')->get($logoPath);

            // Convert to base64 and embed in SVG
            $dataUri = 'data:' . Storage::mimeType($logoPath) . ';base64,' . base64_encode($logo);

            $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
    <image href="' . $dataUri . '" width="32" height="32"/>
</svg>';

            return response($svg)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        // Fallback to default SVG favicon
        $defaultSvgFavicon = public_path('favicon.svg');
        if (file_exists($defaultSvgFavicon)) {
            return response()->file($defaultSvgFavicon)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        // If no default exists, return a minimal SVG
        $fallbackSvg = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
    <rect width="32" height="32" fill="#10b981"/>
</svg>';

        return response($fallbackSvg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function getAppleTouchIcon()
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

        // Fallback to default apple touch icon
        $defaultTouchIcon = public_path('apple-touch-icon.png');
        if (file_exists($defaultTouchIcon)) {
            return response()->file($defaultTouchIcon)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        // If no default exists, return a simple fallback
        $fallbackImage = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=');
        return response($fallbackImage)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}