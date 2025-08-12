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
     * Authorization callback.
     */
    protected $canSeeCallback = null;

    /**
     * Authorization cache TTL in seconds.
     */
    protected ?int $authCacheTtl = null;

    /**
     * Cached authorization result.
     */
    protected ?bool $cachedAuthResult = null;

    /**
     * Badge cache TTL in seconds.
     */
    protected ?int $badgeCacheTtl = null;

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
     * Create a link menu item.
     */
    public static function link(string $name, string $path): static
    {
        return static::make($name, $path);
    }

    /**
     * Create a resource menu item.
     */
    public static function resource(string $resource): static
    {
        $baseName = class_basename($resource);
        // Remove 'Resource' suffix if present
        if (str_ends_with($baseName, 'Resource')) {
            $baseName = substr($baseName, 0, -8);
        }

        $label = \Illuminate\Support\Str::plural($baseName);
        $uriKey = \Illuminate\Support\Str::kebab(\Illuminate\Support\Str::plural($baseName));
        $url = "/admin/resources/{$uriKey}";

        return static::make($label, $url);
    }

    /**
     * Create a dashboard menu item.
     */
    public static function dashboard(string $dashboard): static
    {
        $label = class_basename($dashboard);
        $url = "/admin/dashboards/{$dashboard}";

        return static::make($label, $url);
    }

    /**
     * Create an external link menu item.
     */
    public static function externalLink(string $name, string $url): static
    {
        return static::make($name, $url)
            ->meta('external', true);
    }

    /**
     * Create a lens menu item.
     */
    public static function lens(string $resource, string $lens): static
    {
        $resourceBaseName = class_basename($resource);
        if (str_ends_with($resourceBaseName, 'Resource')) {
            $resourceBaseName = substr($resourceBaseName, 0, -8);
        }

        $lensBaseName = class_basename($lens);
        $label = \Illuminate\Support\Str::title(\Illuminate\Support\Str::snake($lensBaseName, ' '));
        $resourceUriKey = \Illuminate\Support\Str::kebab(\Illuminate\Support\Str::plural($resourceBaseName));
        $lensUriKey = \Illuminate\Support\Str::kebab(\Illuminate\Support\Str::snake($lensBaseName, '-'));
        $url = "/admin/resources/{$resourceUriKey}/lens/{$lensUriKey}";

        return static::make($label, $url)
            ->meta('type', 'lens')
            ->meta('resource', $resource)
            ->meta('lens', $lens);
    }

    /**
     * Create a filtered resource menu item.
     */
    public static function filter(string $name, string $resource): static
    {
        $resourceBaseName = class_basename($resource);
        if (str_ends_with($resourceBaseName, 'Resource')) {
            $resourceBaseName = substr($resourceBaseName, 0, -8);
        }

        $resourceUriKey = \Illuminate\Support\Str::kebab(\Illuminate\Support\Str::plural($resourceBaseName));
        $url = "/admin/resources/{$resourceUriKey}";

        return static::make($name, $url)
            ->meta('type', 'filter')
            ->meta('resource', $resource)
            ->meta('filters', []);
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

        // If badge is a Badge instance, use its type
        if ($badge instanceof Badge) {
            $this->badgeType = $badge->type;
        } else {
            $this->badgeType = $type;
        }

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
     * Set the badge conditionally.
     */
    public function withBadgeIf($badge, string $type, callable $condition): static
    {
        // Store the condition to be evaluated later with request context
        $this->meta['badgeIfCondition'] = $condition;
        $this->meta['badgeIfValue'] = $badge;
        $this->meta['badgeIfType'] = $type;

        return $this;
    }

    /**
     * Resolve conditional badge if applicable.
     */
    public function resolveBadgeIf(Request $request = null): static
    {
        if (isset($this->meta['badgeIfCondition'])) {
            $condition = $this->meta['badgeIfCondition'];

            if (call_user_func($condition, $request)) {
                $this->withBadge($this->meta['badgeIfValue'], $this->meta['badgeIfType']);
            }

            // Clean up the conditional metadata
            unset($this->meta['badgeIfCondition'], $this->meta['badgeIfValue'], $this->meta['badgeIfType']);
        }

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
     * Set the menu item to open in a new tab.
     */
    public function openInNewTab(bool $openInNewTab = true): static
    {
        $this->meta['openInNewTab'] = $openInNewTab;

        return $this;
    }

    /**
     * Set the HTTP method for external links.
     */
    public function method(string $method, array $data = [], array $headers = []): static
    {
        $this->meta['method'] = $method;

        if (!empty($data)) {
            $this->meta['data'] = $data;
        }

        if (!empty($headers)) {
            $this->meta['headers'] = $headers;
        }

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
        $this->cachedAuthResult = null;

        if ($this->authCacheTtl !== null) {
            // Clear both with and without request cache keys
            \Illuminate\Support\Facades\Cache::forget($this->getAuthCacheKey());
            \Illuminate\Support\Facades\Cache::forget($this->getAuthCacheKey(new Request()));
        }

        return $this;
    }

    /**
     * Get the authorization cache key.
     */
    public function getAuthCacheKey(Request $request = null): string
    {
        // Create a unique identifier without serializing closures
        $callbackId = $this->canSeeCallback ? spl_object_hash($this->canSeeCallback) : 'none';
        $requestSuffix = $request ? 'with_request' : 'without_request';
        $identifier = md5($this->label . ':' . $this->url . ':' . $callbackId . ':' . $requestSuffix);

        return "menu_auth_{$identifier}";
    }

    /**
     * Apply a filter to a filtered resource menu item.
     */
    public function applies(string $filter, $value, array $parameters = []): static
    {
        if (!isset($this->meta['filters'])) {
            $this->meta['filters'] = [];
        }

        $filterData = [
            'filter' => $filter,
            'value' => $value,
        ];

        if (!empty($parameters)) {
            $filterData['parameters'] = $parameters;
        }

        $this->meta['filters'][] = $filterData;

        // Regenerate URL with filter parameters
        $this->updateFilteredUrl();

        return $this;
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
        $identifier = md5($this->label . ':' . $this->url);
        $requestSuffix = $request ? 'with_request' : 'without_request';

        return "menu_badge_{$identifier}_{$requestSuffix}";
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
     * Update the URL with filter parameters for filtered resource menu items.
     */
    protected function updateFilteredUrl(): void
    {
        if (($this->meta['type'] ?? null) !== 'filter' || empty($this->meta['filters'])) {
            return;
        }

        // Get base URL without query parameters
        $baseUrl = strtok($this->url, '?');
        $queryParams = [];

        // Convert filters to query parameters
        foreach ($this->meta['filters'] as $filterData) {
            $filterKey = $this->generateFilterKey($filterData['filter'], $filterData['parameters'] ?? []);
            $queryParams["filters[{$filterKey}]"] = $filterData['value'];
        }

        // Update URL with query parameters
        if (!empty($queryParams)) {
            $this->url = $baseUrl . '?' . http_build_query($queryParams);
        }
    }

    /**
     * Generate a filter key for query parameters.
     */
    protected function generateFilterKey(string $filter, array $parameters = []): string
    {
        // Convert filter class name to snake_case key
        $key = \Illuminate\Support\Str::snake(class_basename($filter));

        // Remove 'filter' suffix if present
        if (str_ends_with($key, '_filter')) {
            $key = substr($key, 0, -7);
        }

        // Add parameter-based suffix if needed
        if (!empty($parameters)) {
            $paramValues = array_values($parameters);
            $paramKey = implode('_', array_map(function ($value) {
                return \Illuminate\Support\Str::snake(strtolower((string) $value));
            }, $paramValues));
            $key .= '_' . $paramKey;
        }

        return $key;
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
     * Check if the menu item should be visible.
     */
    public function isVisible(Request $request = null): bool
    {
        if ($this->canSeeCallback) {
            // Use caching if enabled
            if ($this->authCacheTtl !== null) {
                $cacheKey = $this->getAuthCacheKey($request);

                return \Illuminate\Support\Facades\Cache::remember($cacheKey, $this->authCacheTtl, function () use ($request) {
                    return call_user_func($this->canSeeCallback, $request);
                });
            }

            return call_user_func($this->canSeeCallback, $request);
        }

        return $this->visible;
    }

    /**
     * Get the menu item as an array.
     */
    public function toArray(Request $request = null): array
    {
        // Resolve conditional badge first
        $this->resolveBadgeIf($request);

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
