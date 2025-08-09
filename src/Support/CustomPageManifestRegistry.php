<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Custom Page Manifest Registry
 *
 * Manages registration and resolution of custom page component manifests
 * from multiple sources (main app and packages) with priority-based resolution.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Support
 */
class CustomPageManifestRegistry
{
    /**
     * Registered manifests with their configurations.
     */
    protected array $manifests = [];

    /**
     * Cached aggregated manifest for performance.
     */
    protected ?array $aggregatedManifest = null;

    /**
     * Register a custom page manifest.
     *
     * @param array $config
     * @return void
     */
    public function register(array $config): void
    {
        $config = $this->validateConfig($config);

        $this->manifests[$config['package']] = $config;

        // Clear cached aggregated manifest
        $this->aggregatedManifest = null;

        Log::debug("Registered custom page manifest for package: {$config['package']}");
    }

    /**
     * Get all registered manifests sorted by priority.
     *
     * @return array
     */
    public function getManifests(): array
    {
        // Sort by priority (lower number = higher priority)
        $manifests = $this->manifests;
        uasort($manifests, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return $manifests;
    }

    /**
     * Get aggregated manifest with all components from all sources.
     *
     * @return array
     */
    public function getAggregatedManifest(): array
    {
        if ($this->aggregatedManifest !== null) {
            return $this->aggregatedManifest;
        }

        $aggregated = [];
        $manifests = $this->getManifests();

        foreach ($manifests as $config) {
            $manifestData = $this->loadManifestData($config);

            // Extract components from manifest data
            $components = [];
            if ($manifestData) {
                // Handle both formats: direct components or nested under 'Pages'
                $components = $manifestData['Pages'] ?? $manifestData;
            }

            // Always include the package, even if manifest data is empty (graceful degradation)
            $aggregated[$config['package']] = [
                'base_url' => $config['base_url'],
                'priority' => $config['priority'],
                'components' => $components,
            ];
        }

        $this->aggregatedManifest = $aggregated;
        return $aggregated;
    }

    /**
     * Resolve a component from registered manifests.
     *
     * @param string $componentName
     * @return array|null
     */
    public function resolveComponent(string $componentName): ?array
    {
        $manifests = $this->getManifests();

        foreach ($manifests as $config) {
            $manifestData = $this->loadManifestData($config);

            if ($manifestData && isset($manifestData[$componentName])) {
                return [
                    'type' => 'manifest',
                    'component_name' => $componentName,
                    'asset_path' => $manifestData[$componentName],
                    'base_url' => $config['base_url'],
                    'source' => $config['package'],
                    'priority' => $config['priority'],
                ];
            }
        }

        return null;
    }

    /**
     * Get available components from all manifests.
     *
     * @return array
     */
    public function getAvailableComponents(): array
    {
        $components = [];
        $manifests = $this->getManifests();

        foreach ($manifests as $config) {
            $manifestData = $this->loadManifestData($config);

            if ($manifestData) {
                foreach (array_keys($manifestData) as $componentName) {
                    $components[] = [
                        'name' => $componentName,
                        'source' => $config['package'],
                        'priority' => $config['priority'],
                    ];
                }
            }
        }

        return $components;
    }

    /**
     * Check if a package has registered a manifest.
     *
     * @param string $package
     * @return bool
     */
    public function hasPackage(string $package): bool
    {
        return isset($this->manifests[$package]);
    }

    /**
     * Get manifests for a specific package.
     *
     * @param string $package
     * @return array|null
     */
    public function getByPackage(string $package): ?array
    {
        return $this->manifests[$package] ?? null;
    }

    /**
     * Remove a manifest by package name.
     *
     * @param string $package
     * @return bool
     */
    public function unregister(string $package): bool
    {
        if (isset($this->manifests[$package])) {
            unset($this->manifests[$package]);
            $this->aggregatedManifest = null;
            return true;
        }
        return false;
    }

    /**
     * Get count of registered manifests.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->manifests);
    }

    /**
     * Get all registered manifests as a collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function all(): \Illuminate\Support\Collection
    {
        return collect($this->getManifests());
    }

    /**
     * Clear all registered manifests.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->manifests = [];
        $this->aggregatedManifest = null;
    }

    /**
     * Check if a manifest is registered for a package.
     *
     * @param string $package
     * @return bool
     */
    public function hasManifest(string $package): bool
    {
        return isset($this->manifests[$package]);
    }

    /**
     * Get statistics about registered manifests.
     *
     * @return array
     */
    public function getStats(): array
    {
        $stats = [
            'total_manifests' => count($this->manifests),
            'total_components' => 0,
            'manifests' => [],
        ];

        foreach ($this->manifests as $config) {
            $manifestData = $this->loadManifestData($config);
            $componentCount = $manifestData ? count($manifestData) : 0;

            $stats['total_components'] += $componentCount;
            $stats['manifests'][] = [
                'package' => $config['package'],
                'priority' => $config['priority'],
                'components' => $componentCount,
                'manifest_exists' => $manifestData !== null,
            ];
        }

        return $stats;
    }

    /**
     * Validate manifest configuration.
     *
     * @param array $config
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function validateConfig(array $config): array
    {
        // Support both manifest_path (legacy) and manifest_url (new API)
        $required = ['package'];

        if (!isset($config['manifest_path']) && !isset($config['manifest_url'])) {
            throw new \InvalidArgumentException("Missing required field: manifest_path or manifest_url");
        }

        foreach ($required as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Normalize manifest_url from manifest_path for backward compatibility
        if (isset($config['manifest_path']) && !isset($config['manifest_url'])) {
            $config['manifest_url'] = $config['manifest_path'];
        }

        // Set defaults
        $config['priority'] = $config['priority'] ?? 100;
        $config['base_url'] = $config['base_url'] ?? '';

        // Validate priority
        if (!is_int($config['priority']) || $config['priority'] < 0) {
            throw new \InvalidArgumentException("Priority must be a non-negative integer");
        }

        return $config;
    }

    /**
     * Load manifest data from file or URL.
     *
     * @param array $config
     * @return array|null
     */
    protected function loadManifestData(array $config): ?array
    {
        // Use manifest_url if available, fallback to manifest_path for backward compatibility
        $manifestPath = $config['manifest_url'] ?? $config['manifest_path'];

        try {
            // Handle different manifest path types
            if (str_starts_with($manifestPath, 'http')) {
                // URL-based manifest (for future use)
                return $this->loadManifestFromUrl($manifestPath);
            } elseif (str_starts_with($manifestPath, '/')) {
                // Absolute path from public root
                $fullPath = public_path(ltrim($manifestPath, '/'));
            } else {
                // Relative path from base
                $fullPath = base_path($manifestPath);
            }

            if (!File::exists($fullPath)) {
                Log::debug("Manifest file not found: {$fullPath}");
                return null;
            }

            $content = File::get($fullPath);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Invalid JSON in manifest file: {$fullPath}");
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            Log::warning("Failed to load manifest from {$manifestPath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Load manifest from URL (placeholder for future implementation).
     *
     * @param string $url
     * @return array|null
     */
    protected function loadManifestFromUrl(string $url): ?array
    {
        // Placeholder for future URL-based manifest loading
        Log::debug("URL-based manifest loading not yet implemented: {$url}");
        return null;
    }
}
