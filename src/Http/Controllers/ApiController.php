<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * API Controller
 * 
 * Handles AJAX requests for the admin panel including search,
 * field suggestions, and dynamic data loading.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Http\Controllers
 */
class ApiController extends Controller
{
    /**
     * Global search across all resources.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = min((int) $request->get('limit', 10), 50);

        if (strlen($query) < 2) {
            return response()->json([
                'results' => [],
                'total' => 0,
            ]);
        }

        $adminPanel = app(AdminPanel::class);
        $searchableResources = $adminPanel->getSearchableResources();
        $results = [];
        $total = 0;

        foreach ($searchableResources as $resource) {
            if (! $resource->authorizedToView($request)) {
                continue;
            }

            $resourceResults = $this->searchResource($resource, $query, $limit);
            $results = array_merge($results, $resourceResults);
            $total += count($resourceResults);

            if ($total >= $limit) {
                break;
            }
        }

        return response()->json([
            'results' => array_slice($results, 0, $limit),
            'total' => $total,
        ]);
    }

    /**
     * Get field suggestions for autocomplete.
     */
    public function fieldSuggestions(Request $request, string $resource, string $field): JsonResponse
    {
        $adminPanel = app(AdminPanel::class);
        $resourceInstance = $adminPanel->findResource($resource);

        if (! $resourceInstance) {
            return response()->json(['suggestions' => []], 404);
        }

        if (! $resourceInstance->authorizedToView($request)) {
            return response()->json(['suggestions' => []], 403);
        }

        $query = $request->get('q', '');
        $limit = min((int) $request->get('limit', 10), 50);

        // Get distinct values from the field
        $suggestions = $resourceInstance->newModel()
            ->select($field)
            ->distinct()
            ->where($field, 'LIKE', "%{$query}%")
            ->whereNotNull($field)
            ->limit($limit)
            ->pluck($field)
            ->filter()
            ->values();

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Get resource data for relationships.
     */
    public function resourceData(Request $request, string $resource): JsonResponse
    {
        $adminPanel = app(AdminPanel::class);
        $resourceInstance = $adminPanel->findResource($resource);

        if (! $resourceInstance) {
            return response()->json(['data' => []], 404);
        }

        if (! $resourceInstance->authorizedToView($request)) {
            return response()->json(['data' => []], 403);
        }

        $query = $resourceInstance->newModel()->newQuery();
        $query = $resourceInstance::relatableQuery($request, $query);

        // Apply search if provided
        if ($search = $request->get('search')) {
            $searchableColumns = $resourceInstance::searchableColumns();
            
            if (! empty($searchableColumns)) {
                $query->where(function ($q) use ($searchableColumns, $search) {
                    foreach ($searchableColumns as $column) {
                        $q->orWhere($column, 'LIKE', "%{$search}%");
                    }
                });
            }
        }

        $limit = min((int) $request->get('limit', 25), 100);
        $models = $query->limit($limit)->get();

        $data = $models->map(function ($model) use ($resourceInstance) {
            $resource = new $resourceInstance($model);
            return [
                'value' => $resource->getKey(),
                'label' => $resource->title(),
                'subtitle' => $resource->subtitle(),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Execute resource actions.
     */
    public function executeAction(Request $request, string $resource, string $action): JsonResponse
    {
        $adminPanel = app(AdminPanel::class);
        $resourceInstance = $adminPanel->findResource($resource);

        if (! $resourceInstance) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $availableActions = $resourceInstance->actions($request);
        $actionInstance = $availableActions->firstWhere('uriKey', $action);

        if (! $actionInstance) {
            return response()->json(['message' => 'Action not found'], 404);
        }

        if (! $actionInstance->authorize($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $selectedIds = $request->get('resources', []);
        $models = $resourceInstance->newModel()->whereIn('id', $selectedIds)->get();

        try {
            $result = $actionInstance->handle($models, $request);

            return response()->json([
                'message' => $result['message'] ?? 'Action executed successfully',
                'type' => $result['type'] ?? 'success',
                'redirect' => $result['redirect'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'type' => 'error',
            ], 500);
        }
    }

    /**
     * Get dashboard metrics data.
     */
    public function metrics(Request $request): JsonResponse
    {
        $adminPanel = app(AdminPanel::class);
        $metrics = [];

        foreach ($adminPanel->getMetrics() as $metric) {
            $metricInstance = new $metric();
            
            if ($metricInstance->authorize($request)) {
                $metrics[] = [
                    'name' => $metricInstance->name(),
                    'value' => $metricInstance->calculate($request),
                    'format' => $metricInstance->format(),
                    'trend' => $metricInstance->trend($request),
                    'updated_at' => now()->toISOString(),
                ];
            }
        }

        return response()->json([
            'metrics' => $metrics,
        ]);
    }

    /**
     * Clear application cache.
     */
    public function clearCache(Request $request): JsonResponse
    {
        if (! $request->user() || ! $this->userCanClearCache($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('view:clear');
            \Artisan::call('admin-panel:clear-cache');

            return response()->json([
                'message' => 'Cache cleared successfully',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear cache: ' . $e->getMessage(),
                'type' => 'error',
            ], 500);
        }
    }

    /**
     * Search within a specific resource.
     */
    protected function searchResource($resource, string $query, int $limit): array
    {
        $searchableColumns = $resource::searchableColumns();
        
        if (empty($searchableColumns)) {
            return [];
        }

        $models = $resource->newModel()
            ->where(function ($q) use ($searchableColumns, $query) {
                foreach ($searchableColumns as $column) {
                    $q->orWhere($column, 'LIKE', "%{$query}%");
                }
            })
            ->limit($limit)
            ->get();

        return $models->map(function ($model) use ($resource) {
            $resourceInstance = new $resource($model);
            
            return [
                'type' => 'resource',
                'resource' => $resource::uriKey(),
                'id' => $resourceInstance->getKey(),
                'title' => $resourceInstance->title(),
                'subtitle' => $resourceInstance->subtitle(),
                'url' => route('admin-panel.resources.show', [
                    $resource::uriKey(),
                    $resourceInstance->getKey()
                ]),
            ];
        })->toArray();
    }

    /**
     * Check if user can clear cache.
     */
    protected function userCanClearCache($user): bool
    {
        // Implement your authorization logic here
        // For example, check if user has admin role
        return method_exists($user, 'hasRole') ? $user->hasRole('admin') : true;
    }
}
