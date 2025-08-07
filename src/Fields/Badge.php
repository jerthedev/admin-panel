<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Badge Field
 * 
 * A badge field for displaying status indicators with customizable colors,
 * icons, and styles.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class Badge extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'BadgeField';

    /**
     * The color mapping for different values.
     */
    public array $colorMap = [];

    /**
     * The default color when no mapping is found.
     */
    public string $defaultColor = 'secondary';

    /**
     * Whether to show icons in badges.
     */
    public bool $showIcons = false;

    /**
     * The icon mapping for different values.
     */
    public array $iconMap = [];

    /**
     * The badge style (solid, outline, pill).
     */
    public string $style = 'solid';

    /**
     * The badge size (small, medium, large).
     */
    public string $size = 'medium';

    /**
     * Set the color mapping for different values.
     */
    public function map(array $colorMap): static
    {
        $this->colorMap = $colorMap;

        return $this;
    }

    /**
     * Set the default color.
     */
    public function defaultColor(string $color): static
    {
        $this->defaultColor = $color;

        return $this;
    }

    /**
     * Enable icons in badges.
     */
    public function withIcons(bool $showIcons = true): static
    {
        $this->showIcons = $showIcons;

        return $this;
    }

    /**
     * Set the icon mapping for different values.
     */
    public function iconMap(array $iconMap): static
    {
        $this->iconMap = $iconMap;

        return $this;
    }

    /**
     * Set the badge style.
     */
    public function style(string $style): static
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Set the badge size.
     */
    public function size(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Resolve the color for a given value.
     */
    public function resolveColor($value): string
    {
        return $this->colorMap[$value] ?? $this->defaultColor;
    }

    /**
     * Resolve the icon for a given value.
     */
    public function resolveIcon($value): ?string
    {
        return $this->iconMap[$value] ?? null;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'colorMap' => $this->colorMap,
            'defaultColor' => $this->defaultColor,
            'showIcons' => $this->showIcons,
            'iconMap' => $this->iconMap,
            'style' => $this->style,
            'size' => $this->size,
        ]);
    }
}
