<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Cards;

use Illuminate\Http\Request;

/**
 * Base Card Class.
 *
 * Abstract base class for all admin panel cards providing Nova-compatible
 * methods for custom cards, including withMeta(), canSee(), and other
 * essential card functionality.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
abstract class Card
{
    /**
     * The card's component name.
     */
    public string $component;

    /**
     * The card's display name.
     */
    public string $name;

    /**
     * The card's URI key.
     */
    public string $uriKey;

    /**
     * The card's metadata.
     */
    public array $meta = [];

    /**
     * The callback used to determine if the card should be displayed.
     */
    public $canSeeCallback;

    /**
     * Create a new card instance.
     */
    public function __construct()
    {
        $this->name = $this->name ?? $this->generateName();
        $this->uriKey = $this->uriKey ?? $this->generateUriKey();
        $this->component = $this->component ?? $this->generateComponent();
    }

    /**
     * Create a new card instance.
     */
    public static function make(): static
    {
        return new static;
    }

    /**
     * Get the card's display name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Set the card's display name.
     */
    public function withName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the card's component name.
     */
    public function component(): string
    {
        return $this->component;
    }

    /**
     * Set the card's component name.
     */
    public function withComponent(string $component): static
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Get the card's URI key.
     */
    public function uriKey(): string
    {
        return $this->uriKey;
    }

    /**
     * Set additional meta information for the card.
     */
    public function withMeta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $this->validateMeta($meta));

        return $this;
    }

    /**
     * Get additional meta information to merge with the card payload.
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * Set the card's color theme.
     */
    public function withColor(string $color): static
    {
        return $this->withMeta(['color' => $color]);
    }

    /**
     * Set the card's background color.
     */
    public function withBackgroundColor(string $color): static
    {
        return $this->withMeta(['backgroundColor' => $color]);
    }

    /**
     * Set the card's text color.
     */
    public function withTextColor(string $color): static
    {
        return $this->withMeta(['textColor' => $color]);
    }

    /**
     * Set the card's border color.
     */
    public function withBorderColor(string $color): static
    {
        return $this->withMeta(['borderColor' => $color]);
    }

    /**
     * Set the card's icon.
     */
    public function withIcon(string $icon): static
    {
        return $this->withMeta(['icon' => $icon]);
    }

    /**
     * Set the card's title.
     */
    public function withTitle(string $title): static
    {
        return $this->withMeta(['title' => $title]);
    }

    /**
     * Set the card's subtitle.
     */
    public function withSubtitle(string $subtitle): static
    {
        return $this->withMeta(['subtitle' => $subtitle]);
    }

    /**
     * Set the card's description.
     */
    public function withDescription(string $description): static
    {
        return $this->withMeta(['description' => $description]);
    }

    /**
     * Set custom labels for the card.
     */
    public function withLabels(array $labels): static
    {
        return $this->withMeta(['labels' => $this->validateLabels($labels)]);
    }

    /**
     * Set the card as refreshable.
     */
    public function refreshable(bool $refreshable = true): static
    {
        return $this->withMeta(['refreshable' => $refreshable]);
    }

    /**
     * Set the card's refresh interval in seconds.
     */
    public function refreshEvery(int $seconds): static
    {
        return $this->withMeta(['refreshInterval' => $seconds]);
    }

    /**
     * Set custom CSS classes for the card.
     */
    public function withClasses(array $classes): static
    {
        return $this->withMeta(['classes' => $classes]);
    }

    /**
     * Set custom styles for the card.
     */
    public function withStyles(array $styles): static
    {
        return $this->withMeta(['styles' => $this->validateStyles($styles)]);
    }

    /**
     * Set the card's theme variant.
     */
    public function withVariant(string $variant): static
    {
        $validVariants = ['default', 'bordered', 'elevated', 'flat', 'gradient'];

        if (! in_array($variant, $validVariants)) {
            throw new \InvalidArgumentException("Invalid variant '{$variant}'. Must be one of: ".implode(', ', $validVariants));
        }

        return $this->withMeta(['variant' => $variant]);
    }

    /**
     * Set the card's size.
     */
    public function withSize(string $size): static
    {
        $validSizes = ['sm', 'md', 'lg', 'xl', 'full'];

        if (! in_array($size, $validSizes)) {
            throw new \InvalidArgumentException("Invalid size '{$size}'. Must be one of: ".implode(', ', $validSizes));
        }

        return $this->withMeta(['size' => $size]);
    }

    /**
     * Set the callback used to determine if the card should be displayed.
     */
    public function canSee(callable $callback): static
    {
        $this->canSeeCallback = $callback;

        return $this;
    }

    /**
     * Determine if the card should be displayed for the given request.
     */
    public function authorize(Request $request): bool
    {
        if ($this->canSeeCallback) {
            return call_user_func($this->canSeeCallback, $request);
        }

        return true;
    }

    /**
     * Get the card's title.
     */
    public function title(): string
    {
        return $this->meta['title'] ?? $this->name();
    }

    /**
     * Get the card's size.
     */
    public function size(): string
    {
        return $this->meta['size'] ?? 'md';
    }

    /**
     * Get the card's data for rendering.
     *
     * This method should be overridden by concrete card implementations
     * to provide specific data for the card.
     */
    public function data(Request $request): array
    {
        return $this->meta['data'] ?? [];
    }

    /**
     * Generate a display name from the class name.
     */
    protected function generateName(): string
    {
        $className = class_basename(static::class);

        // Convert PascalCase to Title Case
        return preg_replace('/(?<!^)([A-Z])/', ' $1', $className);
    }

    /**
     * Generate a URI key from the class name.
     */
    protected function generateUriKey(): string
    {
        $className = class_basename(static::class);

        // Convert PascalCase to kebab-case
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '-$1', $className));
    }

    /**
     * Generate a component name from the class name.
     */
    protected function generateComponent(): string
    {
        return class_basename(static::class).'Card';
    }

    /**
     * Validate meta data before setting.
     */
    protected function validateMeta(array $meta): array
    {
        $validated = [];

        foreach ($meta as $key => $value) {
            // Validate specific meta keys
            switch ($key) {
                case 'color':
                case 'backgroundColor':
                case 'textColor':
                case 'borderColor':
                    $validated[$key] = $this->validateColor($value);
                    break;
                case 'refreshInterval':
                    $validated[$key] = $this->validateRefreshInterval($value);
                    break;
                case 'variant':
                    $validated[$key] = $this->validateVariant($value);
                    break;
                case 'size':
                    $validated[$key] = $this->validateSize($value);
                    break;
                case 'labels':
                    $validated[$key] = $this->validateLabels($value);
                    break;
                case 'styles':
                    $validated[$key] = $this->validateStyles($value);
                    break;
                default:
                    $validated[$key] = $value;
            }
        }

        return $validated;
    }

    /**
     * Validate color values.
     */
    protected function validateColor(string $color): string
    {
        // Support hex colors, CSS color names, and Tailwind classes
        if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color) ||
            preg_match('/^(red|blue|green|yellow|purple|pink|indigo|gray|black|white)(-\d{2,3})?$/', $color) ||
            in_array($color, ['primary', 'secondary', 'success', 'danger', 'warning', 'info'])) {
            return $color;
        }

        throw new \InvalidArgumentException("Invalid color format: {$color}");
    }

    /**
     * Validate refresh interval.
     */
    protected function validateRefreshInterval(int $interval): int
    {
        if ($interval < 1 || $interval > 3600) {
            throw new \InvalidArgumentException('Refresh interval must be between 1 and 3600 seconds');
        }

        return $interval;
    }

    /**
     * Validate variant.
     */
    protected function validateVariant(string $variant): string
    {
        $validVariants = ['default', 'bordered', 'elevated', 'flat', 'gradient'];

        if (! in_array($variant, $validVariants)) {
            throw new \InvalidArgumentException("Invalid variant '{$variant}'. Must be one of: ".implode(', ', $validVariants));
        }

        return $variant;
    }

    /**
     * Validate size.
     */
    protected function validateSize(string $size): string
    {
        $validSizes = ['sm', 'md', 'lg', 'xl', 'full'];

        if (! in_array($size, $validSizes)) {
            throw new \InvalidArgumentException("Invalid size '{$size}'. Must be one of: ".implode(', ', $validSizes));
        }

        return $size;
    }

    /**
     * Validate labels array.
     */
    protected function validateLabels(array $labels): array
    {
        foreach ($labels as $key => $label) {
            if (! is_string($key) || ! is_string($label)) {
                throw new \InvalidArgumentException('Labels must be an associative array of strings');
            }
        }

        return $labels;
    }

    /**
     * Validate styles array.
     */
    protected function validateStyles(array $styles): array
    {
        $validStyleProperties = [
            'background', 'backgroundColor', 'color', 'border', 'borderColor',
            'borderWidth', 'borderRadius', 'padding', 'margin', 'width', 'height',
            'fontSize', 'fontWeight', 'textAlign', 'boxShadow', 'opacity',
        ];

        foreach ($styles as $property => $value) {
            if (! in_array($property, $validStyleProperties)) {
                throw new \InvalidArgumentException("Invalid CSS property: {$property}");
            }
        }

        return $styles;
    }

    /**
     * Get the card's data for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name(),
            'component' => $this->component(),
            'uriKey' => $this->uriKey(),
            'meta' => $this->meta(),
        ];
    }
}
