<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Menu;

use Closure;
use JsonSerializable;

/**
 * Badge Class
 *
 * Represents a visual badge for menu items with support for dynamic values
 * and various badge types (colors).
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Menu
 */
class Badge implements JsonSerializable
{
    /**
     * The badge value or closure.
     */
    public $value;

    /**
     * The badge type (color).
     */
    public string $type;

    /**
     * Badge cache TTL in seconds.
     */
    protected ?int $cacheTtl = null;

    /**
     * Create a new badge instance.
     */
    public function __construct($value, string $type = 'primary')
    {
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * Create a new badge instance.
     */
    public static function make($value, string $type = 'primary'): static
    {
        return new static($value, $type);
    }

    /**
     * Set the badge type.
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Enable badge caching with TTL.
     */
    public function cache(int $ttl): static
    {
        $this->cacheTtl = $ttl;

        return $this;
    }

    /**
     * Get the badge cache key.
     */
    public function getCacheKey(): string
    {
        // Create a unique identifier without serializing closures
        $valueId = $this->value instanceof Closure
            ? 'closure_' . spl_object_hash($this->value)
            : md5((string) $this->value);

        $identifier = md5($valueId . ':' . $this->type);

        return "badge_{$identifier}";
    }

    /**
     * Clear the badge cache.
     */
    public function clearCache(): static
    {
        if ($this->cacheTtl !== null) {
            \Illuminate\Support\Facades\Cache::forget($this->getCacheKey());
        }

        return $this;
    }

    /**
     * Resolve the badge value.
     */
    public function resolve(): mixed
    {
        if ($this->value instanceof Closure) {
            // Use caching if enabled
            if ($this->cacheTtl !== null) {
                $cacheKey = $this->getCacheKey();

                return \Illuminate\Support\Facades\Cache::remember($cacheKey, $this->cacheTtl, function () {
                    return call_user_func($this->value);
                });
            }

            return call_user_func($this->value);
        }

        return $this->value;
    }

    /**
     * Get the badge as an array.
     */
    public function toArray(): array
    {
        return [
            'value' => $this->resolve(),
            'type' => $this->type,
        ];
    }

    /**
     * Convert the badge to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the badge to a string.
     */
    public function __toString(): string
    {
        return (string) $this->resolve();
    }
}
