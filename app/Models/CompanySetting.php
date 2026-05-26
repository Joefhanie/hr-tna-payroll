<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    private const DEFAULT_BRAND_PRIMARY = '#4F46E5';
    private const DEFAULT_BRAND_SECONDARY = '#2563EB';
    private const DEFAULT_BRAND_ACCENT = '#818CF8';

    protected $fillable = [
        'company_name',
        'tagline',
        'address',
        'city',
        'country',
        'phone',
        'email',
        'website',
        'tin',
        'industry',
        'logo_path',
        'logo_dark_path',
        'brand_primary_color',
        'brand_secondary_color',
        'brand_accent_color',
    ];

    /**
     * Get the singleton company settings row, creating it if it doesn't exist.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    public static function defaultBrandColors(): array
    {
        return [
            'brand_primary_color' => self::DEFAULT_BRAND_PRIMARY,
            'brand_secondary_color' => self::DEFAULT_BRAND_SECONDARY,
            'brand_accent_color' => self::DEFAULT_BRAND_ACCENT,
        ];
    }

    public static function resetBrandColorsPayload(): array
    {
        return [
            'brand_primary_color' => null,
            'brand_secondary_color' => null,
            'brand_accent_color' => null,
        ];
    }

    public function getBrandPaletteAttribute(): array
    {
        $primary = $this->normalizeHexColor($this->brand_primary_color, self::DEFAULT_BRAND_PRIMARY);
        $secondary = $this->normalizeHexColor($this->brand_secondary_color, self::DEFAULT_BRAND_SECONDARY);
        $accent = $this->normalizeHexColor($this->brand_accent_color, self::DEFAULT_BRAND_ACCENT);

        return [
            'primary' => $primary,
            'primary_hover' => $this->adjustHexColor($primary, -0.12),
            'primary_soft' => $this->hexToRgba($primary, 0.12),
            'primary_ring' => $this->hexToRgba($primary, 0.22),
            'secondary' => $secondary,
            'secondary_hover' => $this->adjustHexColor($secondary, -0.12),
            'secondary_soft' => $this->hexToRgba($secondary, 0.12),
            'secondary_ring' => $this->hexToRgba($secondary, 0.22),
            'accent' => $accent,
            'accent_hover' => $this->adjustHexColor($accent, -0.12),
            'accent_soft' => $this->hexToRgba($accent, 0.14),
            'surface_tint' => $this->hexToRgba($primary, 0.08),
            'text_on_primary' => $this->preferredTextColor($primary),
            'text_on_secondary' => $this->preferredTextColor($secondary),
            'text_on_accent' => $this->preferredTextColor($accent),
        ];
    }

    private function normalizeHexColor(?string $color, string $fallback): string
    {
        $color = strtoupper(trim((string) $color));

        if (preg_match('/^#([A-F0-9]{6})$/', $color) === 1) {
            return $color;
        }

        return $fallback;
    }

    private function adjustHexColor(string $hex, float $amount): string
    {
        [$red, $green, $blue] = $this->hexToRgb($hex);

        $adjust = function (int $channel) use ($amount): int {
            $target = $amount >= 0 ? 255 : 0;
            $delta = (int) round(($target - $channel) * abs($amount));

            return max(0, min(255, $channel + ($amount >= 0 ? $delta : -$delta)));
        };

        return sprintf('#%02X%02X%02X', $adjust($red), $adjust($green), $adjust($blue));
    }

    private function hexToRgba(string $hex, float $alpha): string
    {
        [$red, $green, $blue] = $this->hexToRgb($hex);

        return sprintf('rgba(%d, %d, %d, %.2f)', $red, $green, $blue, $alpha);
    }

    private function preferredTextColor(string $hex): string
    {
        [$red, $green, $blue] = $this->hexToRgb($hex);
        $brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

        return $brightness > 160 ? '#0F172A' : '#FFFFFF';
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
