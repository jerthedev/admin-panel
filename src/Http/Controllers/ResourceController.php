<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * Resource Controller.
 *
 * Handles CRUD operations for admin panel resources with support for
 * search, filtering, sorting, and pagination.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $resource): Response
    {
        $adminPanel = app(AdminPanel::class);
        $resourceInstance = $adminPanel->findResource($resource);

        if (! $resourceInstance) {
            abort(404, "Resource [{$resource}] not found.");
        }

        // Check authorization
        if (! $resourceInstance->authorizedToView($request)) {
            abort(403, 'Unauthorized to view this resource.');
        }

        // Get the model query
        $query = $resourceInstance->newModel()->newQuery();

        // Apply resource-specific query modifications
        $query = $resourceInstance::indexQuery($request, $query);

        // Apply search
        if ($search = $request->get('search')) {
            $this->applySearch($query, $resourceInstance, $search);
        }

        // Apply filters
        if ($filters = $request->get('filters')) {
            $this->applyFilters($query, $resourceInstance, $filters, $request);
        }

        // Apply sorting
        $this->applySorting($query, $request);

        // Get pagination settings
        $perPage = min(
            (int) $request->get('per_page', config('admin-panel.resources.per_page', 25)),
            config('admin-panel.resources.max_per_page', 100),
        );

        // Paginate results
        $resources = $query->paginate($perPage)->withQueryString();

        // Transform resources for display
        $transformedResources = $resources->through(function ($model) use ($resourceInstance, $request) {
            $resource = new $resourceInstance($model);

            return $this->transformResourceForIndex($resource, $request);
        });

        return Inertia::render('Resources/Index', [
            'resource' => $this->getResourceMetadata($resourceInstance),
            'data' => $transformedResources, // Changed from 'resources' to 'data' to avoid conflict
            'fields' => $resourceInstance->indexFields($request)->values(),
            'cards' => $this->resolveCards($resourceInstance, $request),
            'filters' => $resourceInstance->filters($request),
            'actions' => $resourceInstance->actions($request),
            'search' => $search,
            'appliedFilters' => $filters ?: [],
            'sort' => [
                'field' => $request->get('sort_field'),
                'direction' => $request->get('sort_direction', 'asc'),
            ],
            'perPage' => $perPage,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, string $resource): Response
    {
        $adminPanel = app(AdminPanel::class);
        $resourceInstance = $adminPanel->findResource($resource);

        if (! $resourceInstance) {
            abort(404, "Resource [{$resource}] not found.");
        }

        // Check authorization
        if (! $resourceInstance->authorizedToCreate($request)) {
            abort(403, 'Unauthorized to create this resource.');
        }

        return Inertia::render('Resources/Create', [
            'resource' => $this->getResourceMetadata($resourceInstance),
            'fields' => $resourceInstance->creationFields($request)->values(),
            'cards' => $this->resolveCards($resourceInstance, $request),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $resource)
    {
        $adminPanel = app(AdminPanel::class);
        $resourceInstance = $adminPanel->findResource($resource);

        if (! $resourceInstance) {
            abort(404, "Resource [{$resource}] not found.");
        }

        // Check authorization
        if (! $resourceInstance->authorizedToCreate($request)) {
            abort(403, 'Unauthorized to create this resource.');
        }

        // Validate the request
        $this->validateResource($request, $resourceInstance, 'creation');

        // Create the model
        $model = $resourceInstance->newModel();

        DB::transaction(function () use ($request, $resourceInstance, $model) {
            // Fill the model with field data
            $resourceInstance->fill($request, $model);

            // Save the model
            $model->save();
        });

        return redirect()
            ->route('admin-panel.resources.show', [$resource, $model->getKey()])
            ->with('success', "{$resourceInstance::singularLabel()} created successfully.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $resource, string $id): Response
    {
        $adminPanel = app(AdminPanel::class);
        $resourceInstance = $adminPanel->findResource($resource);

        if (! $resourceInstance) {
            abort(404, "Resource [{$resource}] not found.");
        }

        // Find the model
        $query = $resourceInstance->newModel()->newQuery();
        $query = $resourceInstance::detailQuery($request, $query);
        $model = $query->findOrFail($id);

        $resourceWithModel = new $resourceInstance($model);

        // Check authorization
        if (! $resourceWithModel->authorizedToView($request)) {
            abort(403, 'Unauthorized to view this resource.');
        }

        return Inertia::render('Resources/Show', [
            'resource' => $this->getResourceMetadata($resourceInstance),
            'resourceData' => $this->transformResourceForDetail($resourceWithModel, $request),
            'fields' => $resourceWithModel->detailFields($request)->values(),
            'cards' => $this->resolveCards($resourceWithModel, $request),
            'actions' => $resourceWithModel->actions($request),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $resource, string $id): Response
    {
        $adminPanel = app(AdminPanel::class);
        $resourceInstance = $adminPanel->findResource($resource);

        if (! $resourceInstance) {
            abort(404, "Resource [{$resource}] not found.");
        }

        // Find the model
        $query = $resourceInstance->newModel()->newQuery();
        $query = $resourceInstance::detailQuery($request, $query);
        $model = $query->findOrFail($id);

        $resourceWithModel = new $resourceInstance($model);

        // Check authorization
        if (! $resourceWithModel->authorizedToUpdate($request)) {
            abort(403, 'Unauthorized to update this resource.');
        }

        return Inertia::render('Resources/Edit', [
            'resource' => $this->getResourceMetadata($resourceInstance),
            'resourceData' => $this->transformResourceForDetail($resourceWithModel, $request),
            'fields' => $resourceWithModel->updateFields($request)->values(),
            'cards' => $this->resolveCards($resourceWithModel, $request),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $resource, string $id)
    {
        $adminPanel = app(AdminPanel::class);
        $resourceInstance = $adminPanel->findResource($resource);

        if (! $resourceInstance) {
            abort(404, "Resource [{$resource}] not found.");
        }

        // Find the model
        $model = $resourceInstance->newModel()->findOrFail($id);
        $resourceWithModel = new $resourceInstance($model);

        // Check authorization
        if (! $resourceWithModel->authorizedToUpdate($request)) {
            abort(403, 'Unauthorized to update this resource.');
        }

        // Validate the request
        $this->validateResource($request, $resourceWithModel, 'update');

        DB::transaction(function () use ($request, $resourceWithModel, $model) {
            // Fill the model with field data
            $resourceWithModel->fill($request, $model);

            // Save the model
            $model->save();
        });

        return redirect()
            ->route('admin-panel.resources.show', [$resource, $model->getKey()])
            ->with('success', "{$resourceInstance::singularLabel()} updated successfully.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $resource, string $id)
    {
        $adminPanel = app(AdminPanel::class);
        $resourceInstance = $adminPanel->findResource($resource);

        if (! $resourceInstance) {
            abort(404, "Resource [{$resource}] not found.");
        }

        // Find the model
        $model = $resourceInstance->newModel()->findOrFail($id);
        $resourceWithModel = new $resourceInstance($model);

        // Check authorization
        if (! $resourceWithModel->authorizedToDelete($request)) {
            abort(403, 'Unauthorized to delete this resource.');
        }

        DB::transaction(function () use ($model) {
            $model->delete();
        });

        return redirect()
            ->route('admin-panel.resources.index', $resource)
            ->with('success', "{$resourceInstance::singularLabel()} deleted successfully.");
    }

    /**
     * Apply search to the query.
     */
    protected function applySearch($query, $resource, string $search): void
    {
        $searchableColumns = $resource::searchableColumns();

        if (empty($searchableColumns)) {
            return;
        }

        $query->where(function ($q) use ($searchableColumns, $search) {
            foreach ($searchableColumns as $column) {
                $q->orWhere($column, 'LIKE', "%{$search}%");
            }
        });
    }

    /**
     * Apply filters to the query.
     */
    protected function applyFilters($query, $resource, array $filters, Request $request): void
    {
        $availableFilters = $resource->filters($request);

        foreach ($filters as $filterKey => $filterValue) {
            $filter = $availableFilters->firstWhere('key', $filterKey);

            if ($filter && $filterValue !== null && $filterValue !== '') {
                $filter->apply($query, $filterValue);
            }
        }
    }

    /**
     * Apply sorting to the query.
     */
    protected function applySorting($query, Request $request): void
    {
        $sortField = $request->get('sort_field');
        $sortDirection = $request->get('sort_direction', 'asc');

        if ($sortField && in_array($sortDirection, ['asc', 'desc'])) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            // Default sorting
            $query->latest();
        }
    }

    /**
     * Validate the resource request.
     */
    protected function validateResource(Request $request, $resource, string $context): void
    {
        $rules = [];
        $fields = $context === 'creation'
            ? $resource->creationFields($request)
            : $resource->updateFields($request);

        foreach ($fields as $field) {
            $baseRules = $field->rules;
            $contextRules = $context === 'creation' ? $field->creationRules : $field->updateRules;

            // For creation: merge base rules with creation rules
            // For update: use update rules if they exist, otherwise use base rules
            if ($context === 'creation') {
                $fieldRules = ! empty($contextRules) ? array_merge($baseRules, $contextRules) : $baseRules;
            } else {
                $fieldRules = ! empty($contextRules) ? $contextRules : $baseRules;
            }

            if (! empty($fieldRules)) {
                $rules[$field->attribute] = $fieldRules;
            }
        }

        $request->validate($rules);
    }

    /**
     * Transform resource for index display.
     */
    protected function transformResourceForIndex($resource, Request $request): array
    {
        $fields = $resource->indexFields($request);
        $data = ['id' => $resource->getKey()];

        foreach ($fields as $field) {
            $data[$field->attribute] = $field->resolveValue($resource->resource);
        }

        return $data;
    }

    /**
     * Transform resource for detail display.
     */
    protected function transformResourceForDetail($resource, Request $request): array
    {
        $fields = $resource->detailFields($request);
        $data = ['id' => $resource->getKey()];

        foreach ($fields as $field) {
            $data[$field->attribute] = $field->resolveValue($resource->resource);
        }

        return $data;
    }

    /**
     * Resolve cards for the resource.
     */
    protected function resolveCards($resource, Request $request): array
    {
        $cards = $resource->cards($request);

        return collect($cards)->map(function ($card) use ($request) {
            if (method_exists($card, 'authorize') && ! $card->authorize($request)) {
                return null;
            }

            return $card->jsonSerialize();
        })->filter()->values()->toArray();
    }

    /**
     * Get resource metadata.
     */
    protected function getResourceMetadata($resource): array
    {
        return [
            'label' => $resource::label(),
            'singularLabel' => $resource::singularLabel(),
            'uriKey' => $resource::uriKey(),
            'authorizedToCreate' => $resource->authorizedToCreate(request()),
        ];
    }
}
