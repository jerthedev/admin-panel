<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Dashboards\Concerns;

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\MenuItem;

/**
 * Has Menu Integration Trait
 * 
 * Provides enhanced menu integration capabilities for dashboards
 * including badges, categories, and custom menu behavior.
 */
trait HasMenuIntegration
{
    /**
     * The dashboard menu badge.
     */
    protected $menuBadge = null;

    /**
     * The dashboard menu badge type.
     */
    protected string $menuBadgeType = 'primary';

    /**
     * Whether this dashboard should appear in quick access.
     */
    protected bool $quickAccess = false;

    /**
     * Whether this dashboard can be favorited.
     */
    protected bool $canBeFavorited = true;

    /**
     * Custom menu icon for this dashboard.
     */
    protected ?string $menuIcon = null;

    /**
     * Custom menu label for this dashboard.
     */
    protected ?string $menuLabel = null;

    /**
     * Menu visibility callback.
     */
    protected $menuVisibilityCallback = null;

    /**
     * Get the menu that should represent the dashboard.
     */
    public function menu(Request $request): MenuItem
    {
        $url = $this->uriKey() === 'main'
            ? route('admin-panel.dashboard')
            : route('admin-panel.dashboards.show', ['uriKey' => $this->uriKey()]);

        $menuItem = MenuItem::make($this->getMenuLabel(), $url)
            ->withIcon($this->getMenuIcon())
            ->meta('dashboard', true)
            ->meta('dashboard_uri_key', $this->uriKey())
            ->meta('dashboard_name', $this->name())
            ->meta('dashboard_description', $this->description())
            ->meta('dashboard_category', $this->category())
            ->meta('quick_access', $this->isQuickAccess())
            ->meta('can_be_favorited', $this->isFavoritable())
            ->canSee(fn($req) => $this->isMenuVisible($req));

        // Add badge if configured
        if ($badge = $this->getMenuBadge($request)) {
            $menuItem->withBadge($badge['value'] ?? $badge, $badge['type'] ?? $this->menuBadgeType);
        }

        return $menuItem;
    }

    /**
     * Set the menu badge for this dashboard.
     */
    public function withMenuBadge($badge, string $type = 'primary'): static
    {
        $this->menuBadge = $badge;
        $this->menuBadgeType = $type;

        return $this;
    }

    /**
     * Set this dashboard as quick access.
     */
    public function quickAccess(bool $enabled = true): static
    {
        $this->quickAccess = $enabled;

        return $this;
    }

    /**
     * Set whether this dashboard can be favorited.
     */
    public function canBeFavorited(bool $enabled = true): static
    {
        $this->canBeFavorited = $enabled;

        return $this;
    }

    /**
     * Set a custom menu icon.
     */
    public function withMenuIcon(string $icon): static
    {
        $this->menuIcon = $icon;

        return $this;
    }

    /**
     * Set a custom menu label.
     */
    public function withMenuLabel(string $label): static
    {
        $this->menuLabel = $label;

        return $this;
    }

    /**
     * Set menu visibility callback.
     */
    public function menuVisibleWhen(callable $callback): static
    {
        $this->menuVisibilityCallback = $callback;

        return $this;
    }

    /**
     * Get the menu label.
     */
    protected function getMenuLabel(): string
    {
        return $this->menuLabel ?? $this->name();
    }

    /**
     * Get the menu icon.
     */
    protected function getMenuIcon(): string
    {
        return $this->menuIcon ?? $this->icon() ?? 'chart-bar';
    }

    /**
     * Get the menu badge.
     */
    protected function getMenuBadge(Request $request)
    {
        if (is_callable($this->menuBadge)) {
            return call_user_func($this->menuBadge, $request);
        }

        return $this->menuBadge;
    }

    /**
     * Check if this dashboard is quick access.
     */
    protected function isQuickAccess(): bool
    {
        return $this->quickAccess;
    }

    /**
     * Check if this dashboard can be favorited.
     */
    protected function isFavoritable(): bool
    {
        return $this->canBeFavorited;
    }

    /**
     * Check if the menu item should be visible.
     */
    protected function isMenuVisible(Request $request): bool
    {
        // First check dashboard authorization
        if (!$this->authorizedToSee($request)) {
            return false;
        }

        // Then check custom menu visibility
        if ($this->menuVisibilityCallback) {
            return call_user_func($this->menuVisibilityCallback, $request);
        }

        return true;
    }

    /**
     * Get dashboard menu metadata.
     */
    public function getMenuMetadata(): array
    {
        return [
            'dashboard' => true,
            'dashboard_uri_key' => $this->uriKey(),
            'dashboard_name' => $this->name(),
            'dashboard_description' => $this->description(),
            'dashboard_category' => $this->category(),
            'quick_access' => $this->isQuickAccess(),
            'can_be_favorited' => $this->isFavoritable(),
            'menu_icon' => $this->getMenuIcon(),
            'menu_label' => $this->getMenuLabel(),
        ];
    }

    /**
     * Create a menu section for this dashboard.
     */
    public function asMenuSection(Request $request): \JTD\AdminPanel\Menu\MenuSection
    {
        $url = $this->uriKey() === 'main'
            ? route('admin-panel.dashboard')
            : route('admin-panel.dashboards.show', ['uriKey' => $this->uriKey()]);

        return \JTD\AdminPanel\Menu\MenuSection::make($this->getMenuLabel())
            ->path($url)
            ->icon($this->getMenuIcon())
            ->meta('dashboard', true)
            ->meta('dashboard_uri_key', $this->uriKey())
            ->canSee(fn($req) => $this->isMenuVisible($req));
    }

    /**
     * Create a menu item for this dashboard.
     */
    public function asMenuItem(Request $request): MenuItem
    {
        return $this->menu($request);
    }

    /**
     * Check if this dashboard should be grouped with others.
     */
    public function shouldGroupInMenu(): bool
    {
        return !empty($this->category());
    }

    /**
     * Get the menu group name for this dashboard.
     */
    public function getMenuGroup(): ?string
    {
        return $this->category();
    }

    /**
     * Get menu sorting priority (lower numbers appear first).
     */
    public function getMenuPriority(): int
    {
        return $this->menuPriority ?? 100;
    }

    /**
     * Set menu sorting priority.
     */
    public function withMenuPriority(int $priority): static
    {
        $this->menuPriority = $priority;

        return $this;
    }

    /**
     * Check if this dashboard should appear in the main menu.
     */
    public function shouldAppearInMainMenu(): bool
    {
        return $this->appearInMainMenu ?? true;
    }

    /**
     * Set whether this dashboard should appear in the main menu.
     */
    public function appearInMainMenu(bool $enabled = true): static
    {
        $this->appearInMainMenu = $enabled;

        return $this;
    }

    /**
     * Get dashboard menu configuration.
     */
    public function getMenuConfig(): array
    {
        return [
            'label' => $this->getMenuLabel(),
            'icon' => $this->getMenuIcon(),
            'badge' => $this->menuBadge,
            'badge_type' => $this->menuBadgeType,
            'quick_access' => $this->isQuickAccess(),
            'can_be_favorited' => $this->isFavoritable(),
            'priority' => $this->getMenuPriority(),
            'appear_in_main_menu' => $this->shouldAppearInMainMenu(),
            'group' => $this->getMenuGroup(),
        ];
    }
}
