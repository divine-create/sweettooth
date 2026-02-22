<?php

namespace App\Helpers;

class Color
{
    public static function darkenHex(string $hex, int $lightnessDelta = 15): string
    {
        $rgb = self::hexToRgb($hex);
        if ($rgb === null) {
            return $hex;
        }

        [$h, $s, $l] = self::rgbToHsl($rgb[0], $rgb[1], $rgb[2]);
        $l = max(0.0, min(1.0, $l - ($lightnessDelta / 100)));
        [$r, $g, $b] = self::hslToRgb($h, $s, $l);

        return self::rgbToHex($r, $g, $b);
    }

    public static function lightenHex(string $hex, int $lightnessDelta = 15): string
    {
        $rgb = self::hexToRgb($hex);

        if ($rgb === null) {
            return $hex;
        }

        [$h, $s, $l] = self::rgbToHsl($rgb[0], $rgb[1], $rgb[2]);
        $l = max(0.0, min(1.0, $l + ($lightnessDelta / 100)));
        [$r, $g, $b] = self::hslToRgb($h, $s, $l);

        return self::rgbToHex($r, $g, $b);
    }

    public static function isLight(string $hex): bool
    {
        $rgb = self::hexToRgb($hex);
        if ($rgb === null) {
            return false;
        }

        [$h, $s, $l] = self::rgbToHsl($rgb[0], $rgb[1], $rgb[2]);
        return $l > 0.55;
    }

    public static function contrastColor(string $hex): string
    {
        return self::isLight($hex) ? '#000000' : '#ffffff';
    }

    private static function hexToRgb(string $hex): ?array
    {
        $hex = trim($hex);
        if ($hex === '') {
            return null;
        }

        if ($hex[0] === '#') {
            $hex = substr($hex, 1);
        }

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private static function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $h = 0.0;
        $s = 0.0;
        $l = ($max + $min) / 2;

        if ($max !== $min) {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r:
                    $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                    break;
                case $g:
                    $h = ($b - $r) / $d + 2;
                    break;
                case $b:
                    $h = ($r - $g) / $d + 4;
                    break;
            }

            $h /= 6;
        }

        return [$h, $s, $l];
    }

    private static function hslToRgb(float $h, float $s, float $l): array
    {
        if ($s == 0) {
            $v = (int) round($l * 255);
            return [$v, $v, $v];
        }

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        $r = self::hueToRgb($p, $q, $h + 1 / 3);
        $g = self::hueToRgb($p, $q, $h);
        $b = self::hueToRgb($p, $q, $h - 1 / 3);

        return [
            (int) round($r * 255),
            (int) round($g * 255),
            (int) round($b * 255),
        ];
    }

    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }
        return $p;
    }
}
