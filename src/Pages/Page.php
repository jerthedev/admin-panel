<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Pages;

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\MenuItem;

/**
 * Base Page Class.
 *
 * Abstract base class for all admin panel custom pages. Provides common
 * functionality for page registration, field definition, and authorization.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
abstract class Page
{
    /**
     * The Vue component reference for this page (legacy single component).
     * Optional - use $components array for multi-component pages.
     */
    public static ?string $component = null;

    /**
     * The Vue components for this page (multi-component support).
     * First component is primary, others are available via routing.
     */
    public static array $components = [];

    /**
     * The menu group this page belongs to.
     */
    public static ?string $group = null;

    /**
     * The display title for this page.
     */
    public static ?string $title = null;

    /**
     * The icon for this page (Heroicon name).
     */
    public static ?string $icon = null;

    /**
     * Get the fields available for the page.
     */
    abstract public function fields(Request $request): array;

    /**
     * Get the cards available for the page.
     */
    public function cards(Request $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the page.
     */
    public function actions(Request $request): array
    {
        return [];
    }

    /**
     * Get the metrics available for the page.
     */
    public function metrics(Request $request): array
    {
        return [];
    }

    /**
     * Get the custom data for the page.
     */
    public function data(Request $request): array
    {
        return [];
    }

    /**
     * Determine if the current user can view any instances of this page.
     */
    public static function authorizedToViewAny(Request $request): bool
    {
        return true;
    }

    /**
     * Get the displayable label of the page.
     */
    public static function label(): string
    {
        if (static::$title) {
            return static::$title;
        }

        $className = class_basename(get_called_class());

        // Remove 'Page' suffix if present
        if (str_ends_with($className, 'Page')) {
            $className = substr($className, 0, -4);
        }

        return str_replace('_', ' ', $className);
    }

    /**
     * Get the menu group for the page.
     */
    public static function group(): ?string
    {
        return static::$group;
    }

    /**
     * Get the icon for the page.
     */
    public static function icon(): ?string
    {
        return static::$icon;
    }

    /**
     * Get the primary Vue component name for the page.
     * Supports both single component (legacy) and multi-component formats.
     */
    public static function component(): string
    {
        // Multi-component format takes precedence
        if (! empty(static::$components)) {
            return static::$components[0];
        }

        // Fallback to legacy single component format
        if (static::$component !== null) {
            return static::$component;
        }

        throw new \InvalidArgumentException('Page must define either $component or $components property');
    }

    /**
     * Get all Vue components for the page.
     * Returns array format for both single and multi-component pages.
     */
    public static function components(): array
    {
        // Multi-component format
        if (! empty(static::$components)) {
            return static::$components;
        }

        // Convert legacy single component to array format
        if (static::$component !== null) {
            return [static::$component];
        }

        throw new \InvalidArgumentException('Page must define either $component or $components property');
    }

    /**
     * Get the primary (first) component.
     */
    public static function primaryComponent(): string
    {
        return static::components()[0];
    }

    /**
     * Get secondary (non-primary) components.
     */
    public static function secondaryComponents(): array
    {
        $components = static::components();

        return array_slice($components, 1);
    }

    /**
     * Check if the page has multiple components.
     */
    public static function hasMultipleComponents(): bool
    {
        return count(static::components()) > 1;
    }

    /**
     * Get the total number of components.
     */
    public static function componentCount(): int
    {
        return count(static::components());
    }

    /**
     * Validate the components configuration.
     *
     * @throws \InvalidArgumentException
     */
    public static function validateComponents(): void
    {
        // Check for explicitly empty components array
        if (! empty(static::$components) || static::$component !== null) {
            // We have components, validate them
            $components = static::components();

            if (empty($components)) {
                throw new \InvalidArgumentException('Page must define at least one component');
            }

            // All components must be strings
            foreach ($components as $component) {
                if (! is_string($component)) {
                    throw new \InvalidArgumentException('All components must be strings');
                }
            }

            // Component names must be unique
            if (count($components) !== count(array_unique($components))) {
                throw new \InvalidArgumentException('Component names must be unique');
            }
        } else {
            // No components defined at all
            throw new \InvalidArgumentException('Page must define at least one component');
        }
    }

    /**
     * Get the route name for the page.
     */
    public static function routeName(): string
    {
        $className = class_basename(static::class);

        // Remove 'Page' suffix if present
        if (str_ends_with($className, 'Page')) {
            $className = substr($className, 0, -4);
        }

        return 'admin-panel.pages.'.strtolower($className);
    }

    /**
     * Get the URI path for the page.
     */
    public static function uriPath(): string
    {
        $className = class_basename(static::class);

        // Remove 'Page' suffix if present
        if (str_ends_with($className, 'Page')) {
            $className = substr($className, 0, -4);
        }

        return 'pages/'.strtolower($className);
    }

    /**
     * Get the menu item that should represent the page.
     */
    public function menu(Request $request): MenuItem
    {
        $menuItem = MenuItem::make(static::label(), route(static::routeName()));

        if (static::$icon) {
            $menuItem->withIcon(static::$icon);
        }

        return $menuItem;
    }

    /**
     * Determine if this page is available for navigation.
     */
    public static function availableForNavigation(Request $request): bool
    {
        return static::authorizedToViewAny($request);
    }

    /**
     * Validate the page configuration.
     *
     * @throws \InvalidArgumentException
     */
    public static function validate(): void
    {
        // Use the new multi-component validation
        static::validateComponents();
    }
}
