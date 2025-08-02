<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Resources\Resource;

/**
 * Resource Registry
 * 
 * Manages resource registration, validation, and provides utilities
 * for working with registered resources.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Support
 */
class ResourceRegistry
{
    /**
     * The cache key for resource metadata.
     */
    protected const METADATA_CACHE_KEY = 'admin_panel_resource_metadata';

    /**
     * Validate that a resource class is properly configured.
     */
    public function validateResource(string $resourceClass): array
    {
        $errors = [];

        if (! class_exists($resourceClass)) {
            $errors[] = "Resource class {$resourceClass} does not exist.";
            return $errors;
        }

        if (! is_subclass_of($resourceClass, Resource::class)) {
            $errors[] = "Resource class {$resourceClass} must extend " . Resource::class;
        }

        // Check if the model property is set
        if (! isset($resourceClass::$model)) {
            $errors[] = "Resource class {$resourceClass} must define a \$model property.";
        } else {
            $modelClass = $resourceClass::$model;
            if (! class_exists($modelClass)) {
                $errors[] = "Model class {$modelClass} does not exist.";
            }
        }

        // Check if required methods are implemented
        $reflection = new \ReflectionClass($resourceClass);
        if ($reflection->isAbstract()) {
            $errors[] = "Resource class {$resourceClass} cannot be abstract.";
        }

        if (! $reflection->hasMethod('fields')) {
            $errors[] = "Resource class {$resourceClass} must implement the fields() method.";
        }

        return $errors;
    }

    /**
     * Get metadata for all resources.
     */
    public function getResourceMetadata(Collection $resources): Collection
    {
        $cacheKey = self::METADATA_CACHE_KEY;
        $cacheTtl = config('admin-panel.performance.cache_ttl', 3600);

        if (config('admin-panel.performance.cache_resources', true)) {
            return Cache::remember($cacheKey, $cacheTtl, function () use ($resources) {
                return $this->buildResourceMetadata($resources);
            });
        }

        return $this->buildResourceMetadata($resources);
    }

    /**
     * Build metadata for resources.
     */
    protected function buildResourceMetadata(Collection $resources): Collection
    {
        return $resources->map(function (Resource $resource) {
            return [
                'class' => get_class($resource),
                'label' => $resource::label(),
                'singularLabel' => $resource::singularLabel(),
                'uriKey' => $resource::uriKey(),
                'model' => $resource::model(),
                'group' => $resource::$group,
                'globallySearchable' => $resource::$globallySearchable,
                'searchableColumns' => $resource::searchableColumns(),
                'perPageViaRelationship' => $resource::$perPageViaRelationship,
                'availableForNavigation' => $resource::availableForNavigation(request()),
            ];
        });
    }

    /**
     * Get resources by their URI keys.
     */
    public function getResourcesByUriKeys(array $uriKeys, Collection $resources): Collection
    {
        return $resources->filter(function (Resource $resource) use ($uriKeys) {
            return in_array($resource::uriKey(), $uriKeys);
        });
    }

    /**
     * Get resources that have a specific model.
     */
    public function getResourcesByModel(string $modelClass, Collection $resources): Collection
    {
        return $resources->filter(function (Resource $resource) use ($modelClass) {
            return $resource::model() === $modelClass;
        });
    }

    /**
     * Check if a resource exists by URI key.
     */
    public function resourceExists(string $uriKey, Collection $resources): bool
    {
        return $resources->contains(function (Resource $resource) use ($uriKey) {
            return $resource::uriKey() === $uriKey;
        });
    }

    /**
     * Get resource statistics.
     */
    public function getResourceStatistics(Collection $resources): array
    {
        $grouped = $resources->groupBy(function (Resource $resource) {
            return $resource::$group ?? 'Default';
        });

        $searchable = $resources->filter(function (Resource $resource) {
            return $resource::$globallySearchable;
        });

        $navigable = $resources->filter(function (Resource $resource) {
            return $resource::availableForNavigation(request());
        });

        return [
            'total' => $resources->count(),
            'groups' => $grouped->keys()->toArray(),
            'groupCounts' => $grouped->map->count()->toArray(),
            'searchable' => $searchable->count(),
            'navigable' => $navigable->count(),
            'models' => $resources->map(function (Resource $resource) {
                return $resource::model();
            })->unique()->values()->toArray(),
        ];
    }

    /**
     * Clear resource metadata cache.
     */
    public function clearMetadataCache(): void
    {
        Cache::forget(self::METADATA_CACHE_KEY);
    }

    /**
     * Validate all resources and return any errors.
     */
    public function validateAllResources(Collection $resources): array
    {
        $allErrors = [];

        foreach ($resources as $resource) {
            $resourceClass = get_class($resource);
            $errors = $this->validateResource($resourceClass);
            
            if (! empty($errors)) {
                $allErrors[$resourceClass] = $errors;
            }
        }

        return $allErrors;
    }
}
