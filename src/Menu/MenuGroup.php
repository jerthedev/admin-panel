<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Menu;

use Illuminate\Http\Request;
use Illuminate\Support\Traits\Conditionable;
use JsonSerializable;

/**
 * MenuGroup Class
 *
 * Represents a logical grouping of menu items within sections
 * with collapsible functionality and authorization.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Menu
 */
class MenuGroup implements JsonSerializable
{
    use Conditionable;

    /**
     * The group name.
     */
    public string $name;

    /**
     * The group items.
     */
    public array $items = [];

    /**
     * Whether the group is collapsible.
     */
    public bool $collapsible = false;

    /**
     * Authorization callback.
     */
    protected $canSeeCallback = null;

    /**
     * Authorization cache TTL in seconds.
     */
    protected ?int $authCacheTtl = null;

    /**
     * Whether the group is currently collapsed.
     */
    public bool $collapsed = false;

    /**
     * Unique identifier for state persistence.
     */
    protected ?string $stateId = null;

    /**
     * Create a new menu group instance.
     */
    public function __construct(string $name, array $items = [])
    {
        $this->name = $name;
        $this->items = $items;
    }

    /**
     * Create a new menu group instance.
     */
    public static function make(string $name, array $items = []): static
    {
        return new static($name, $items);
    }

    /**
     * Make the group collapsible.
     */
    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;

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
        $identifier = md5($this->name . ':' . $callbackId);

        return "menu_group_auth_{$identifier}";
    }

    /**
     * Check if the group should be visible.
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

        // Generate a state ID based on the group name
        return 'menu_group_' . \Illuminate\Support\Str::slug($this->name, '_');
    }

    /**
     * Get the group as an array.
     */
    public function toArray(Request $request = null): array
    {
        return [
            'name' => $this->name,
            'collapsible' => $this->collapsible,
            'collapsed' => $this->collapsed,
            'stateId' => $this->getStateId(),
            'items' => array_map(function ($item) use ($request) {
                return $item->toArray($request);
            }, $this->items),
        ];
    }

    /**
     * Convert the group to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
