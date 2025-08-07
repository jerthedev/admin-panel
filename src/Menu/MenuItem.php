<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Menu;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Conditionable;
use JsonSerializable;

/**
 * MenuItem Class
 * 
 * Represents a customizable menu item with fluent API for badges, icons,
 * conditional visibility, and other menu properties.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Menu
 */
class MenuItem implements JsonSerializable
{
    use Conditionable;

    /**
     * The menu item label.
     */
    public string $label;

    /**
     * The menu item URL or route.
     */
    public string $url;

    /**
     * The menu item icon.
     */
    public ?string $icon = null;

    /**
     * The badge value or closure.
     */
    public $badge = null;

    /**
     * The badge type.
     */
    public string $badgeType = 'primary';

    /**
     * Whether the menu item is visible.
     */
    public bool $visible = true;

    /**
     * Additional metadata for the menu item.
     */
    public array $meta = [];

    /**
     * Create a new menu item instance.
     */
    public function __construct(string $label, string $url)
    {
        $this->label = $label;
        $this->url = $url;
    }

    /**
     * Create a new menu item instance.
     */
    public static function make(string $label, string $url): static
    {
        return new static($label, $url);
    }

    /**
     * Set the menu item icon.
     */
    public function withIcon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set the menu item badge.
     */
    public function withBadge($badge, string $type = 'primary'): static
    {
        $this->badge = $badge;
        $this->badgeType = $type;

        return $this;
    }

    /**
     * Set the badge type.
     */
    public function badgeType(string $type): static
    {
        $this->badgeType = $type;

        return $this;
    }

    /**
     * Hide the menu item.
     */
    public function hide(): static
    {
        $this->visible = false;

        return $this;
    }

    /**
     * Show the menu item.
     */
    public function show(): static
    {
        $this->visible = true;

        return $this;
    }

    /**
     * Set additional metadata.
     */
    public function withMeta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    /**
     * Set a single metadata value.
     */
    public function meta(string $key, $value): static
    {
        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * Resolve the badge value.
     */
    public function resolveBadge(Request $request = null): mixed
    {
        if ($this->badge instanceof Closure) {
            return call_user_func($this->badge, $request);
        }

        return $this->badge;
    }

    /**
     * Check if the menu item should be visible.
     */
    public function isVisible(Request $request = null): bool
    {
        return $this->visible;
    }

    /**
     * Get the menu item as an array.
     */
    public function toArray(Request $request = null): array
    {
        $badge = $this->resolveBadge($request);

        return [
            'label' => $this->label,
            'url' => $this->url,
            'icon' => $this->icon,
            'badge' => $badge,
            'badgeType' => $this->badgeType,
            'visible' => $this->isVisible($request),
            'meta' => $this->meta,
        ];
    }

    /**
     * Convert the menu item to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the menu item to a string.
     */
    public function __toString(): string
    {
        return $this->label;
    }
}
