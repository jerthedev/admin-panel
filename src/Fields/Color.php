<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Color Field
 * 
 * A color picker field with support for hex, rgb, hsl formats,
 * color palettes, and alpha channel.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class Color extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'ColorField';

    /**
     * The color format (hex, rgb, hsl).
     */
    public string $format = 'hex';

    /**
     * Whether to support alpha channel.
     */
    public bool $withAlpha = false;

    /**
     * Predefined color palette.
     */
    public array $palette = [];

    /**
     * Whether to show color preview.
     */
    public bool $showPreview = true;

    /**
     * Named color swatches.
     */
    public array $swatches = [];

    /**
     * Set the color format.
     */
    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Enable alpha channel support.
     */
    public function withAlpha(bool $withAlpha = true): static
    {
        $this->withAlpha = $withAlpha;

        return $this;
    }

    /**
     * Set the color palette.
     */
    public function palette(array $palette): static
    {
        $this->palette = $palette;

        return $this;
    }

    /**
     * Show color preview.
     */
    public function showPreview(bool $show = true): static
    {
        $this->showPreview = $show;

        return $this;
    }

    /**
     * Set named color swatches.
     */
    public function swatches(array $swatches): static
    {
        $this->swatches = $swatches;

        return $this;
    }

    /**
     * Validate hex color format.
     */
    public function isValidHexColor(string $color): bool
    {
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color) === 1;
    }

    /**
     * Validate RGB color format.
     */
    public function isValidRgbColor(string $color): bool
    {
        $rgbPattern = '/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*(0|1|0?\.\d+))?\s*\)$/';
        
        if (!preg_match($rgbPattern, $color, $matches)) {
            return false;
        }

        // Check if RGB values are within valid range (0-255)
        for ($i = 1; $i <= 3; $i++) {
            if ((int) $matches[$i] > 255) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert hex color to RGB.
     */
    public function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "rgb($r, $g, $b)";
    }

    /**
     * Convert RGB color to hex.
     */
    public function rgbToHex(string $rgb): string
    {
        preg_match('/rgb\((\d+),\s*(\d+),\s*(\d+)\)/', $rgb, $matches);
        
        if (count($matches) !== 4) {
            return '#000000';
        }
        
        $r = str_pad(dechex((int) $matches[1]), 2, '0', STR_PAD_LEFT);
        $g = str_pad(dechex((int) $matches[2]), 2, '0', STR_PAD_LEFT);
        $b = str_pad(dechex((int) $matches[3]), 2, '0', STR_PAD_LEFT);
        
        return "#$r$g$b";
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'format' => $this->format,
            'withAlpha' => $this->withAlpha,
            'palette' => $this->palette,
            'showPreview' => $this->showPreview,
            'swatches' => $this->swatches,
        ]);
    }
}
