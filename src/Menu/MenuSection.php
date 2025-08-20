<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Menu;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use JsonSerializable;

/**
 * MenuSection Class
 *
 * Represents a top-level navigation section with icons, badges,
 * collapsible support, and authorization.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Menu
 */
class MenuSection implements JsonSerializable
{
    use Conditionable;

    /**
     * The section name.
     */
    public string $name;

    /**
     * The section items.
     */
    public array $items = [];

    /**
     * The section icon.
     */
    public ?string $icon = null;

    /**
     * The section badge.
     */
    public $badge = null;

    /**
     * The badge type.
     */
    public string $badgeType = 'primary';

    /**
     * Whether the section is collapsible.
     */
    public bool $collapsible = false;

    /**
     * The section path (for direct links).
     */
    public ?string $path = null;

    /**
     * Authorization callback.
     */
    protected $canSeeCallback = null;

    /**
     * Authorization cache TTL in seconds.
     */
    protected ?int $authCacheTtl = null;

    /**
     * Badge cache TTL in seconds.
     */
    protected ?int $badgeCacheTtl = null;

    /**
     * Whether the section is currently collapsed.
     */
    public bool $collapsed = false;

    /**
     * Unique identifier for state persistence.
     */
    protected ?string $stateId = null;

    /**
     * Additional metadata for the section.
     */
    protected array $meta = [];

    /**
     * Create a new menu section instance.
     */
    public function __construct(string $name, array $items = [])
    {
        $this->name = $name;
        $this->items = $items;
    }

    /**
     * Create a new menu section instance.
     */
    public static function make(string $name, array $items = []): static
    {
        return new static($name, $items);
    }

    /**
     * Create a dashboard menu section.
     *
     * @param string|\JTD\AdminPanel\Dashboards\Dashboard $dashboard Dashboard class name or instance
     */
    public static function dashboard(string|\JTD\AdminPanel\Dashboards\Dashboard $dashboard): static
    {
        if (is_string($dashboard)) {
            $dashboardInstance = app($dashboard);
        } else {
            $dashboardInstance = $dashboard;
        }

        $request = request();
        $url = $dashboardInstance->uriKey() === 'main'
            ? route('admin-panel.dashboard')
            : route('admin-panel.dashboards.show', ['uriKey' => $dashboardInstance->uriKey()]);

        return static::make($dashboardInstance->name())
            ->path($url)
            ->icon($dashboardInstance->icon() ?? 'chart-bar')
            ->meta('dashboard', true)
            ->meta('dashboard_uri_key', $dashboardInstance->uriKey())
            ->canSee(fn($req) => $dashboardInstance->authorizedToSee($req));
    }

    /**
     * Create a resource section.
     */
    public static function resource(string $resource): static
    {
        $baseName = class_basename($resource);
        // Remove 'Resource' suffix if present
        if (Str::endsWith($baseName, 'Resource')) {
            $baseName = Str::substr($baseName, 0, -8);
        }

        $name = Str::plural($baseName);
        $uriKey = Str::kebab(Str::plural(Str::lower($baseName)));
        $path = '/admin/resources/' . $uriKey;

        return static::make($name)
            ->path($path);
    }

    /**
     * Set the section icon.
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set the section badge.
     */
    public function withBadge($badge, string $type = 'primary'): static
    {
        $this->badge = $badge;
        $this->badgeType = $type;

        return $this;
    }

    /**
     * Set the badge conditionally.
     */
    public function withBadgeIf($badge, string $type, callable $condition): static
    {
        if (call_user_func($condition)) {
            return $this->withBadge($badge, $type);
        }

        return $this;
    }

    /**
     * Make the section collapsible.
     */
    public function collapsible(bool $collapsible = true): static
    {
        if ($collapsible && $this->path !== null) {
            throw new \InvalidArgumentException('Sections with a path cannot be collapsible');
        }

        $this->collapsible = $collapsible;

        return $this;
    }

    /**
     * Set the section path.
     */
    public function path(string $path): static
    {
        if ($this->collapsible) {
            throw new \InvalidArgumentException('Collapsible sections cannot have a direct path');
        }

        $this->path = $path;

        return $this;
    }

    /**
     * Set the authorization callback.
     */
    public function canSee(callable $callback): static
    {
        $this->canSeeCallback = $callback;

        return $this;
    }

    /**
     * Enable authorization caching with TTL.
     */
    public function cacheAuth(int $ttl): static
    {
        $this->authCacheTtl = $ttl;

        return $this;
    }

    /**
     * Clear the authorization cache.
     */
    public function clearAuthCache(): static
    {
        if ($this->authCacheTtl !== null) {
            $cacheKey = $this->getAuthCacheKey();
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        }

        return $this;
    }

    /**
     * Get the authorization cache key.
     */
    public function getAuthCacheKey(): string
    {
        // Create a unique identifier without serializing closures
        $callbackId = $this->canSeeCallback ? spl_object_hash($this->canSeeCallback) : 'none';
        $identifier = md5($this->name . ':' . ($this->path ?? '') . ':' . $callbackId);

        return "menu_section_auth_{$identifier}";
    }

    /**
     * Enable badge caching with TTL.
     */
    public function cacheBadge(int $ttl): static
    {
        $this->badgeCacheTtl = $ttl;

        return $this;
    }

    /**
     * Get the badge cache key.
     */
    public function getBadgeCacheKey(Request $request = null): string
    {
        $identifier = md5($this->name . ':' . ($this->path ?? 'section'));
        $requestSuffix = $request ? 'with_request' : 'without_request';

        return "menu_section_badge_{$identifier}_{$requestSuffix}";
    }

    /**
     * Clear the badge cache.
     */
    public function clearBadgeCache(): static
    {
        if ($this->badgeCacheTtl !== null) {
            \Illuminate\Support\Facades\Cache::forget($this->getBadgeCacheKey());
            \Illuminate\Support\Facades\Cache::forget($this->getBadgeCacheKey(new Request()));
        }

        return $this;
    }

    /**
     * Set the collapsed state.
     */
    public function collapsed(bool $collapsed = true): static
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    /**
     * Set a unique state ID for persistence.
     */
    public function stateId(string $id): static
    {
        $this->stateId = $id;

        return $this;
    }

    /**
     * Get the state ID for persistence.
     */
    public function getStateId(): string
    {
        if ($this->stateId !== null) {
            return $this->stateId;
        }

        // Generate a state ID based on the section name
        return 'menu_section_' . \Illuminate\Support\Str::slug($this->name, '_');
    }

    /**
     * Resolve the badge value.
     */
    public function resolveBadge(Request $request = null): mixed
    {
        if ($this->badge instanceof Badge) {
            return $this->badge->resolve();
        }

        if ($this->badge instanceof Closure) {
            // Use caching if enabled
            if ($this->badgeCacheTtl !== null) {
                $cacheKey = $this->getBadgeCacheKey($request);

                return \Illuminate\Support\Facades\Cache::remember($cacheKey, $this->badgeCacheTtl, function () use ($request) {
                    return call_user_func($this->badge, $request);
                });
            }

            return call_user_func($this->badge, $request);
        }

        return $this->badge;
    }

    /**
     * Check if the section should be visible.
     */
    public function isVisible(Request $request = null): bool
    {
        if ($this->canSeeCallback) {
            // Use caching if enabled
            if ($this->authCacheTtl !== null) {
                $cacheKey = $this->getAuthCacheKey();

                return \Illuminate\Support\Facades\Cache::remember($cacheKey, $this->authCacheTtl, function () use ($request) {
                    return call_user_func($this->canSeeCallback, $request);
                });
            }

            return call_user_func($this->canSeeCallback, $request);
        }

        return true;
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
     * Get the section as an array.
     */
    public function toArray(Request $request = null): array
    {
        $badge = $this->resolveBadge($request);
        $badgeType = $this->badgeType;

        // If badge is a Badge instance, use its type
        if ($this->badge instanceof Badge) {
            $badgeType = $this->badge->type;
        }

        return [
            'name' => $this->name,
            'icon' => $this->icon,
            'badge' => $badge,
            'badgeType' => $badgeType,
            'collapsible' => $this->collapsible,
            'collapsed' => $this->collapsed,
            'stateId' => $this->getStateId(),
            'path' => $this->path,
            'items' => array_map(function ($item) use ($request) {
                return $item->toArray($request);
            }, $this->items),
        ];
    }

    /**
     * Convert the section to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
