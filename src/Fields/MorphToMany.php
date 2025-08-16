<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * MorphToMany Field.
 *
 * Represents a many-to-many polymorphic relationship field for managing related models
 * through a polymorphic pivot table. Provides display, attachment, and management
 * capabilities with full Nova v5 compatibility for morphToMany Eloquent relationships.
 *
 * Example: Post morphToMany Tags (where Tags can be attached to Post, Video, etc.)
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MorphToMany extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'MorphToManyField';

    /**
     * The related resource class.
     */
    public string $resourceClass = '';

    /**
     * The relationship method name on the model.
     */
    public string $relationshipName;

    /**
     * The morph type column name.
     */
    public ?string $morphType = null;

    /**
     * The morph id column name.
     */
    public ?string $morphId = null;

    /**
     * The local key attribute.
     */
    public ?string $localKey = null;

    /**
     * The pivot table name.
     */
    public ?string $pivotTable = null;

    /**
     * The pivot fields for the relationship.
     */
    public $pivotFields = [];

    /**
     * The pivot computed fields for the relationship.
     */
    public $pivotComputedFields = [];

    /**
     * The pivot actions for the relationship.
     */
    public $pivotActions = [];

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
     * The number of items to display per page.
     */
    public int $perPage = 15;

    /**
     * A query callback for filtering relatable models.
     */
    public $relatableQueryCallback = null;

    /**
     * Whether to allow duplicate relations.
     */
    public bool $allowDuplicateRelations = false;

    /**
     * Whether to reorder attachables by title.
     */
    public bool $reorderAttachables = true;

    /**
     * Create a new MorphToMany field.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->relationshipName = $this->attribute;
        $this->resourceClass = $this->guessResourceClass();

        // MorphToMany fields are typically only shown on detail views
        $this->onlyOnDetail();
    }

    /**
     * Create a new MorphToMany field with Nova-style syntax.
     */
    public static function make(string $name, ?string $attribute = null, ?callable $resolveCallback = null): static
    {
        return new static($name, $attribute, $resolveCallback);
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
     * Set the morph type column name.
     */
    public function morphType(string $morphType): static
    {
        $this->morphType = $morphType;

        return $this;
    }

    /**
     * Set the morph id column name.
     */
    public function morphId(string $morphId): static
    {
        $this->morphId = $morphId;

        return $this;
    }

    /**
     * Set the local key attribute.
     */
    public function localKey(string $localKey): static
    {
        $this->localKey = $localKey;

        return $this;
    }

    /**
     * Set the pivot table name.
     */
    public function pivotTable(string $pivotTable): static
    {
        $this->pivotTable = $pivotTable;

        return $this;
    }

    /**
     * Set the pivot fields for the relationship.
     */
    public function fields(callable $fieldsCallback): static
    {
        $this->pivotFields = $fieldsCallback;

        return $this;
    }

    /**
     * Set the pivot actions for the relationship.
     */
    public function actions(callable $actionsCallback): static
    {
        $this->pivotActions = $actionsCallback;

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
     * Set a callback for filtering relatable models.
     */
    public function relatableQueryUsing(callable $callback): static
    {
        $this->relatableQueryCallback = $callback;

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
     * Don't reorder attachables by title.
     */
    public function dontReorderAttachables(): static
    {
        $this->reorderAttachables = false;

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
            // For MorphToMany, we'll store the count and let the frontend handle the actual data loading
            $this->value = [
                'count' => $relatedModels->count(),
                'resource_id' => $resource->getKey(),
                'resource_class' => $this->resourceClass,
                'morph_type' => $this->morphType,
                'morph_id' => $this->morphId,
                'pivot_fields' => $this->pivotFields,
                'pivot_computed_fields' => $this->pivotComputedFields,
                'pivot_actions' => $this->pivotActions,
            ];
        } else {
            $this->value = [
                'count' => 0,
                'resource_id' => $resource->getKey(),
                'resource_class' => $this->resourceClass,
                'morph_type' => $this->morphType,
                'morph_id' => $this->morphId,
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
        // MorphToMany relationships are typically not filled directly
        // They are managed through the attach/detach operations
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
            'morphType' => $this->morphType,
            'morphId' => $this->morphId,
            'localKey' => $this->localKey,
            'pivotTable' => $this->pivotTable,
            'searchable' => $this->searchable,
            'withSubtitles' => $this->withSubtitles,
            'collapsable' => $this->collapsable,
            'collapsedByDefault' => $this->collapsedByDefault,
            'showCreateRelationButton' => $this->showCreateRelationButton,
            'modalSize' => $this->modalSize,
            'perPage' => $this->perPage,
            'allowDuplicateRelations' => $this->allowDuplicateRelations,
            'reorderAttachables' => $this->reorderAttachables,
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
                    ->orWhere('content', 'like', "%{$search}%")
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
        // Get the model class from the resource class
        $modelClass = $this->resourceClass::$model ?? null;

        if (! $modelClass) {
            return ['data' => [], 'meta' => ['total' => 0]];
        }

        $query = $modelClass::query();

        // Apply custom query callback if provided
        if ($this->relatableQueryCallback) {
            $query = call_user_func($this->relatableQueryCallback, $request, $query);
        }

        // Apply search if provided
        if ($request->has('search') && $this->searchable) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Exclude already attached models unless duplicates are allowed
        if (! $this->allowDuplicateRelations) {
            $attachedIds = $parentModel->{$this->relationshipName}()->pluck($modelClass::make()->getTable().'.id');
            $query->whereNotIn('id', $attachedIds);
        }

        // Apply ordering
        if ($this->reorderAttachables) {
            $query->orderBy('title')->orderBy('name');
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
     * Guess the resource class based on the field name.
     */
    protected function guessResourceClass(): string
    {
        $className = str_replace('_', '', ucwords($this->attribute, '_'));

        return "App\\AdminPanel\\Resources\\{$className}";
    }
}
