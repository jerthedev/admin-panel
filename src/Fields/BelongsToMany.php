<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * BelongsToMany Field.
 *
 * Represents a many-to-many relationship field for managing related models
 * through a pivot table. Provides attach/detach functionality, pivot fields
 * support, and all standard relationship features with full Nova v5 compatibility.
 *
 * Example: User belongsToMany Roles through role_user pivot table.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class BelongsToMany extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'BelongsToManyField';

    /**
     * The related resource class.
     */
    public string $resourceClass = '';

    /**
     * The relationship method name on the model.
     */
    public string $relationshipName;

    /**
     * The pivot table name.
     */
    public ?string $table = null;

    /**
     * The foreign pivot key.
     */
    public ?string $foreignPivotKey = null;

    /**
     * The related pivot key.
     */
    public ?string $relatedPivotKey = null;

    /**
     * The parent key.
     */
    public ?string $parentKey = null;

    /**
     * The related key.
     */
    public ?string $relatedKey = null;

    /**
     * Whether the relationship is searchable.
     */
    public bool $searchable = false;

    /**
     * Whether to show subtitles in search results.
     */
    public bool $withSubtitles = false;

    /**
     * Whether the relationship is collapsable.
     */
    public bool $collapsable = false;

    /**
     * Whether the relationship is collapsed by default.
     */
    public bool $collapsedByDefault = false;

    /**
     * Whether to show the create relation button.
     */
    public bool $showCreateRelationButton = false;

    /**
     * The modal size for inline creation.
     */
    public ?string $modalSize = null;

    /**
     * Whether to reorder attachables.
     */
    public bool $reorderAttachables = true;

    /**
     * Whether to allow duplicate relations.
     */
    public bool $allowDuplicateRelations = false;

    /**
     * The number of items to display per page.
     */
    public int $perPage = 15;

    /**
     * A query callback for filtering relatable models.
     */
    public $relatableQueryCallback = null;

    /**
     * The pivot fields for the relationship.
     */
    public array $pivotFields = [];

    /**
     * The pivot computed fields for the relationship.
     */
    public array $pivotComputedFields = [];

    /**
     * The pivot actions for the relationship.
     */
    public array $pivotActions = [];

    /**
     * Create a new BelongsToMany field.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->relationshipName = $this->attribute;
        $this->resourceClass = $this->guessResourceClass();

        // BelongsToMany fields are typically only shown on detail views
        $this->onlyOnDetail();
    }

    /**
     * Create a new BelongsToMany field with Nova-style syntax.
     */
    public static function make(string $name, ?string $attribute = null, $resourceOrCallback = null): static
    {
        // If third parameter is a string (resource class), use Nova syntax
        if (is_string($resourceOrCallback)) {
            $field = new static($name, $attribute);
            $field->resourceClass = $resourceOrCallback;

            return $field;
        }

        // Otherwise, use parent signature (callable resolveCallback)
        $field = new static($name, $attribute, $resourceOrCallback);

        return $field;
    }

    /**
     * Set the related resource class.
     */
    public function resource(string $resourceClass): static
    {
        $this->resourceClass = $resourceClass;

        return $this;
    }

    /**
     * Set the relationship method name.
     */
    public function relationship(string $relationshipName): static
    {
        $this->relationshipName = $relationshipName;

        return $this;
    }

    /**
     * Set the pivot table name.
     */
    public function table(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Set the foreign pivot key.
     */
    public function foreignPivotKey(string $foreignPivotKey): static
    {
        $this->foreignPivotKey = $foreignPivotKey;

        return $this;
    }

    /**
     * Set the related pivot key.
     */
    public function relatedPivotKey(string $relatedPivotKey): static
    {
        $this->relatedPivotKey = $relatedPivotKey;

        return $this;
    }

    /**
     * Set the parent key.
     */
    public function parentKey(string $parentKey): static
    {
        $this->parentKey = $parentKey;

        return $this;
    }

    /**
     * Set the related key.
     */
    public function relatedKey(string $relatedKey): static
    {
        $this->relatedKey = $relatedKey;

        return $this;
    }

    /**
     * Make the relationship searchable.
     */
    public function searchable(bool|callable $searchable = true): static
    {
        if (is_callable($searchable)) {
            $this->searchable = call_user_func($searchable);
        } else {
            $this->searchable = $searchable;
        }

        return $this;
    }

    /**
     * Show subtitles in search results.
     */
    public function withSubtitles(bool $withSubtitles = true): static
    {
        $this->withSubtitles = $withSubtitles;

        return $this;
    }

    /**
     * Make the relationship collapsable.
     */
    public function collapsable(bool $collapsable = true): static
    {
        $this->collapsable = $collapsable;

        return $this;
    }

    /**
     * Make the relationship collapsed by default.
     */
    public function collapsedByDefault(bool $collapsedByDefault = true): static
    {
        $this->collapsedByDefault = $collapsedByDefault;

        // If collapsed by default, also make it collapsable
        if ($collapsedByDefault) {
            $this->collapsable = true;
        }

        return $this;
    }

    /**
     * Show the create relation button.
     */
    public function showCreateRelationButton(bool|callable $showCreateRelationButton = true): static
    {
        if (is_callable($showCreateRelationButton)) {
            $this->showCreateRelationButton = call_user_func($showCreateRelationButton);
        } else {
            $this->showCreateRelationButton = $showCreateRelationButton;
        }

        return $this;
    }

    /**
     * Hide the create relation button.
     */
    public function hideCreateRelationButton(): static
    {
        $this->showCreateRelationButton = false;

        return $this;
    }

    /**
     * Set the modal size for inline creation.
     */
    public function modalSize(string $modalSize): static
    {
        $this->modalSize = $modalSize;

        return $this;
    }

    /**
     * Don't reorder attachables.
     */
    public function dontReorderAttachables(): static
    {
        $this->reorderAttachables = false;

        return $this;
    }

    /**
     * Allow duplicate relations.
     */
    public function allowDuplicateRelations(bool $allowDuplicateRelations = true): static
    {
        $this->allowDuplicateRelations = $allowDuplicateRelations;

        return $this;
    }

    /**
     * Set a callback for filtering relatable models.
     */
    public function relatableQueryUsing(callable $callback): static
    {
        $this->relatableQueryCallback = $callback;

        return $this;
    }

    /**
     * Set the pivot fields for the relationship.
     */
    public function fields(array $fields): static
    {
        $this->pivotFields = $fields;

        return $this;
    }

    /**
     * Set the pivot computed fields for the relationship.
     */
    public function computedFields(array $computedFields): static
    {
        $this->pivotComputedFields = $computedFields;

        return $this;
    }

    /**
     * Set the pivot actions for the relationship.
     */
    public function actions(array $actions): static
    {
        $this->pivotActions = $actions;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        $attribute = $attribute ?? $this->attribute;

        // Get the related models
        $relatedModels = $resource->{$this->relationshipName};

        if ($relatedModels) {
            // For BelongsToMany, we'll store the count and let the frontend handle the actual data loading
            $this->value = [
                'count' => $relatedModels->count(),
                'resource_id' => $resource->getKey(),
                'resource_class' => $this->resourceClass,
                'pivot_fields' => $this->pivotFields,
                'pivot_computed_fields' => $this->pivotComputedFields,
                'pivot_actions' => $this->pivotActions,
            ];
        } else {
            $this->value = [
                'count' => 0,
                'resource_id' => $resource->getKey(),
                'resource_class' => $this->resourceClass,
                'pivot_fields' => $this->pivotFields,
                'pivot_computed_fields' => $this->pivotComputedFields,
                'pivot_actions' => $this->pivotActions,
            ];
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        // BelongsToMany relationships are typically managed through attach/detach operations
        // rather than direct filling
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        }
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'resourceClass' => $this->resourceClass,
            'relationshipName' => $this->relationshipName,
            'table' => $this->table,
            'foreignPivotKey' => $this->foreignPivotKey,
            'relatedPivotKey' => $this->relatedPivotKey,
            'parentKey' => $this->parentKey,
            'relatedKey' => $this->relatedKey,
            'searchable' => $this->searchable,
            'withSubtitles' => $this->withSubtitles,
            'collapsable' => $this->collapsable,
            'collapsedByDefault' => $this->collapsedByDefault,
            'showCreateRelationButton' => $this->showCreateRelationButton,
            'modalSize' => $this->modalSize,
            'reorderAttachables' => $this->reorderAttachables,
            'allowDuplicateRelations' => $this->allowDuplicateRelations,
            'perPage' => $this->perPage,
            'pivotFields' => $this->pivotFields,
            'pivotComputedFields' => $this->pivotComputedFields,
            'pivotActions' => $this->pivotActions,
        ]);
    }

    /**
     * Get the related models for the relationship.
     */
    public function getRelatedModels(Request $request, Model $parentModel): array
    {
        $query = $parentModel->{$this->relationshipName}();

        // Apply custom query callback if provided
        if ($this->relatableQueryCallback) {
            $query = call_user_func($this->relatableQueryCallback, $request, $query);
        }

        // Apply search if provided
        if ($request->has('search') && $this->searchable) {
            $search = $request->get('search');
            // Simple search implementation for testing
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Apply pagination
        $perPage = $request->get('perPage', $this->perPage);
        $page = $request->get('page', 1);

        $results = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ];
    }

    /**
     * Get the attachable models for the relationship.
     */
    public function getAttachableModels(Request $request, Model $parentModel): array
    {
        // Get the related model class
        $relatedModel = $parentModel->{$this->relationshipName}()->getRelated();
        $query = $relatedModel->newQuery();

        // Apply custom query callback if provided
        if ($this->relatableQueryCallback) {
            $query = call_user_func($this->relatableQueryCallback, $request, $query);
        }

        // Exclude already attached models unless duplicates are allowed
        if (! $this->allowDuplicateRelations) {
            $attachedIds = $parentModel->{$this->relationshipName}()->pluck($relatedModel->getTable().'.'.$relatedModel->getKeyName());
            $query->whereNotIn($relatedModel->getTable().'.'.$relatedModel->getKeyName(), $attachedIds);
        }

        // Apply search if provided
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Apply pagination
        $perPage = $request->get('perPage', $this->perPage);
        $page = $request->get('page', 1);

        $results = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ];
    }

    /**
     * Attach models to the relationship.
     */
    public function attachModels(Request $request, Model $parentModel, array $modelIds, array $pivotData = []): void
    {
        $relationship = $parentModel->{$this->relationshipName}();

        foreach ($modelIds as $modelId) {
            $relationship->attach($modelId, $pivotData);
        }
    }

    /**
     * Detach models from the relationship.
     */
    public function detachModels(Request $request, Model $parentModel, array $modelIds): void
    {
        $relationship = $parentModel->{$this->relationshipName}();
        $relationship->detach($modelIds);
    }

    /**
     * Update pivot data for the relationship.
     */
    public function updatePivot(Request $request, Model $parentModel, int $relatedId, array $pivotData): void
    {
        $relationship = $parentModel->{$this->relationshipName}();
        $relationship->updateExistingPivot($relatedId, $pivotData);
    }

    /**
     * Guess the resource class based on the field name.
     */
    protected function guessResourceClass(): string
    {
        $className = str_replace('_', '', ucwords($this->attribute, '_'));

        return "App\\AdminPanel\\Resources\\{$className}";
    }
}
