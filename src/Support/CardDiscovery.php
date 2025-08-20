<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Cards\Card;
use ReflectionClass;

/**
 * Card Discovery Service.
 *
 * Automatically discovers and registers admin panel cards from the
 * configured discovery path with caching for performance optimization.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class CardDiscovery
{
    /**
     * The cache key for discovered cards.
     */
    protected const CACHE_KEY = 'admin_panel_discovered_cards';

    /**
     * Discover all cards in the configured path.
     */
    public function discover(): Collection
    {
        if (! config('admin-panel.cards.auto_discovery', true)) {
            return collect();
        }

        $cacheKey = $this->getCacheKey();
        $cacheTtl = config('admin-panel.performance.cache_ttl', 3600);

        if (config('admin-panel.performance.cache_cards', true)) {
            try {
                return Cache::remember($cacheKey, $cacheTtl, function () {
                    return $this->performDiscovery();
                });
            } catch (\Exception $e) {
                // If caching fails (e.g., cache table doesn't exist during migration),
                // fall back to performing discovery without caching
                return $this->performDiscovery();
            }
        }

        return $this->performDiscovery();
    }

    /**
     * Discover cards in a specific path.
     */
    public function discoverIn(string $path): Collection
    {
        if (! File::exists($path)) {
            return collect();
        }

        $cards = collect();
        $files = File::allFiles($path);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getClassNameFromFile($file, $path);

            if ($this->isValidCardClass($className)) {
                $cards->push($className);
            }
        }

        return $cards;
    }

    /**
     * Perform the actual card discovery.
     */
    protected function performDiscovery(): Collection
    {
        $discoveryPath = $this->getDiscoveryPath();

        if (! File::exists($discoveryPath)) {
            return collect();
        }

        return $this->discoverIn($discoveryPath);
    }

    /**
     * Get the discovery path for cards.
     */
    protected function getDiscoveryPath(): string
    {
        $path = config('admin-panel.cards.discovery_path', 'app/Admin/Cards');

        return base_path($path);
    }

    /**
     * Get the class name from a file path.
     */
    protected function getClassNameFromFile($file, ?string $basePath = null): string
    {
        $basePath = $basePath ?? base_path();
        $relativePath = str_replace($basePath.'/', '', $file->getPathname());
        $relativePath = str_replace('.php', '', $relativePath);
        $relativePath = str_replace('/', '\\', $relativePath);

        // Convert app/Admin/Cards/StatsCard to App\Admin\Cards\StatsCard
        $className = str_replace('app\\', 'App\\', $relativePath);

        return $className;
    }

    /**
     * Check if a class is a valid card class.
     */
    protected function isValidCardClass(string $className): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($className);

            // Must be a subclass of Card and not abstract
            return $reflection->isSubclassOf(Card::class) && ! $reflection->isAbstract();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the cache key for discovered cards.
     */
    protected function getCacheKey(): string
    {
        $discoveryPath = $this->getDiscoveryPath();
        $pathHash = md5($discoveryPath);

        return self::CACHE_KEY.'_'.$pathHash;
    }

    /**
     * Clear the card discovery cache.
     */
    public function clearCache(): void
    {
        try {
            $cacheKey = $this->getCacheKey();
            Cache::forget($cacheKey);
        } catch (\Exception $e) {
            // If cache clearing fails (e.g., cache table doesn't exist),
            // silently continue as there's nothing to clear anyway
        }
    }

    /**
     * Get all discovered card instances.
     */
    public function getCardInstances(): Collection
    {
        return $this->discover()->map(function (string $cardClass) {
            return new $cardClass;
        });
    }

    /**
     * Get cards grouped by their group.
     */
    public function getGroupedCards(): Collection
    {
        return $this->getCardInstances()
            ->groupBy(function (Card $card) {
                return $card->meta()['group'] ?? 'Default';
            })
            ->map(function (Collection $groupCards) {
                // Sort cards alphabetically within each group
                return $groupCards->sortBy(function (Card $card) {
                    return $card->name();
                })->values();
            });
    }

    /**
     * Find a card by its URI key.
     */
    public function findByUriKey(string $uriKey): ?Card
    {
        $cards = $this->getCardInstances();

        return $cards->first(function (Card $card) use ($uriKey) {
            return $card->uriKey() === $uriKey;
        });
    }

    /**
     * Get cards available for the given request.
     */
    public function getAuthorizedCards(\Illuminate\Http\Request $request): Collection
    {
        return $this->getCardInstances()->filter(function (Card $card) use ($request) {
            return $card->authorize($request);
        });
    }

    /**
     * Get cards grouped by their group for the given request.
     */
    public function getAuthorizedGroupedCards(\Illuminate\Http\Request $request): Collection
    {
        return $this->getAuthorizedCards($request)
            ->groupBy(function (Card $card) {
                return $card->meta()['group'] ?? 'Default';
            })
            ->map(function (Collection $groupCards) {
                // Sort cards alphabetically within each group
                return $groupCards->sortBy(function (Card $card) {
                    return $card->name();
                })->values();
            });
    }
}
