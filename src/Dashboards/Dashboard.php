<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Dashboards;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JsonSerializable;
use JTD\AdminPanel\Menu\MenuItem;

/**
 * Dashboard Base Class.
 *
 * Abstract base class for creating Nova-compatible dashboards.
 * Provides the foundation for dashboard functionality including
 * card management, authorization, and menu customization.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
abstract class Dashboard implements JsonSerializable
{
    /**
     * Authorization callback.
     */
    protected ?Closure $seeCallback = null;

    /**
     * Whether to show the refresh button.
     */
    protected bool $showRefreshButton = false;

    /**
     * Authorization cache TTL in seconds.
     */
    protected ?int $authCacheTtl = null;

    /**
     * Cached authorization result.
     */
    protected ?bool $cachedAuthResult = null;

    /**
     * Cache key for authorization.
     */
    protected ?string $authCacheKey = null;

    /**
     * Get the cards that should be displayed on the dashboard.
     *
     * @return array<int, \JTD\AdminPanel\Cards\Card>
     */
    abstract public function cards(): array;

    /**
     * Get the displayable name of the dashboard.
     *
     * Nova v5 compatible return type supporting both string and Stringable.
     */
    public function name(): \Stringable|string
    {
        return Str::title(Str::snake(class_basename($this), ' '));
    }

    /**
     * Get the URI key of the dashboard.
     */
    public function uriKey(): string
    {
        return Str::slug($this->name());
    }

    /**
     * Get the description of the dashboard.
     */
    public function description(): ?string
    {
        return null;
    }

    /**
     * Get the icon of the dashboard.
     */
    public function icon(): ?string
    {
        return null;
    }

    /**
     * Get the category of the dashboard.
     */
    public function category(): ?string
    {
        return null;
    }

    /**
     * Determine if the dashboard should be available for the given request.
     */
    public function authorizedToSee(Request $request): bool
    {
        // Check cache first if enabled
        if ($this->authCacheTtl && $this->cachedAuthResult !== null) {
            return $this->cachedAuthResult;
        }

        $result = $this->seeCallback ? call_user_func($this->seeCallback, $request) : true;

        // Cache the result if caching is enabled
        if ($this->authCacheTtl) {
            $this->cachedAuthResult = $result;

            // Set up cache expiration (simplified for this implementation)
            if ($this->authCacheKey) {
                cache()->put($this->authCacheKey, $result, $this->authCacheTtl);
            }
        }

        return $result;
    }

    /**
     * Set the callback used to authorize viewing the dashboard.
     */
    public function canSee(Closure $callback): static
    {
        $this->seeCallback = $callback;

        return $this;
    }

    /**
     * Set the callback used to authorize viewing the dashboard using a gate.
     *
     * Nova v5 compatible method supporting both simple abilities and model classes.
     *
     * @param string $ability The ability to check
     * @param mixed $arguments Arguments to pass to the gate (can be model class, instance, or array)
     */
    public function canSeeWhen(string $ability, mixed $arguments = []): static
    {
        return $this->canSee(function (Request $request) use ($ability, $arguments) {
            $user = $request->user();

            if (! $user) {
                return false;
            }

            // Handle model class authorization (Nova v5 pattern)
            if (is_string($arguments) && class_exists($arguments)) {
                return $user->can($ability, $arguments);
            }

            // Handle array of arguments or single argument
            if (is_array($arguments)) {
                return $user->can($ability, ...$arguments);
            }

            return $user->can($ability, $arguments);
        });
    }

    /**
     * Set authorization using a policy method.
     *
     * @param string $policy The policy class name
     * @param string $method The policy method to call
     * @param mixed $arguments Additional arguments to pass to the policy method
     */
    public function canSeeWhenPolicy(string $policy, string $method, mixed $arguments = []): static
    {
        return $this->canSee(function (Request $request) use ($policy, $method, $arguments) {
            $user = $request->user();

            if (! $user) {
                return false;
            }

            if (! class_exists($policy)) {
                return false;
            }

            $policyInstance = app($policy);

            if (! method_exists($policyInstance, $method)) {
                return false;
            }

            $args = is_array($arguments) ? $arguments : [$arguments];

            return $policyInstance->{$method}($user, ...$args);
        });
    }

    /**
     * Enable authorization caching with TTL.
     *
     * @param int|null $ttl Cache time-to-live in seconds (uses config default if null)
     */
    public function cacheAuth(?int $ttl = null): static
    {
        // Use configuration defaults if not specified
        if (! config('admin-panel.dashboard.dashboard_authorization.enable_caching', true)) {
            return $this; // Caching disabled in config
        }

        $this->authCacheTtl = $ttl ?? config('admin-panel.dashboard.dashboard_authorization.cache_ttl', 300);
        $cachePrefix = config('admin-panel.dashboard.dashboard_authorization.cache_key_prefix', 'dashboard_auth');
        $this->authCacheKey = $cachePrefix.'_'.$this->uriKey().'_'.(auth()->id() ?? 'guest');

        // Try to load from cache
        $cached = cache()->get($this->authCacheKey);
        if ($cached !== null) {
            $this->cachedAuthResult = $cached;
        }

        return $this;
    }

    /**
     * Clear authorization cache.
     */
    public function clearAuthCache(): static
    {
        if ($this->authCacheKey) {
            cache()->forget($this->authCacheKey);
        }

        $this->cachedAuthResult = null;

        return $this;
    }

    /**
     * Get the menu that should represent the dashboard.
     */
    public function menu(Request $request): MenuItem
    {
        $url = $this->uriKey() === 'main'
            ? route('admin-panel.dashboard')
            : route('admin-panel.dashboards.show', ['uriKey' => $this->uriKey()]);

        $menuItem = MenuItem::make($this->name(), $url)
            ->withIcon($this->icon() ?? 'chart-bar')
            ->meta('dashboard', true)
            ->meta('dashboard_uri_key', $this->uriKey())
            ->meta('dashboard_name', $this->name())
            ->meta('dashboard_description', $this->description())
            ->meta('dashboard_category', $this->category())
            ->canSee(fn($req) => $this->authorizedToSee($req));

        // Add badge if dashboard has one
        if (method_exists($this, 'badge') && $badge = $this->badge($request)) {
            $menuItem->withBadge($badge['value'] ?? $badge, $badge['type'] ?? 'primary');
        }

        return $menuItem;
    }

    /**
     * Enable the refresh button for this dashboard.
     */
    public function showRefreshButton(): static
    {
        $this->showRefreshButton = true;

        return $this;
    }

    /**
     * Determine if the refresh button should be shown.
     */
    public function shouldShowRefreshButton(): bool
    {
        return $this->showRefreshButton;
    }

    /**
     * Create a new dashboard instance.
     */
    public static function make(): static
    {
        return new static;
    }

    /**
     * Get the dashboard's JSON representation.
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name(),
            'uriKey' => $this->uriKey(),
            'description' => $this->description(),
            'icon' => $this->icon(),
            'category' => $this->category(),
            'showRefreshButton' => $this->shouldShowRefreshButton(),
        ];
    }

    /**
     * Get the dashboard as an array.
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
