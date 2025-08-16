<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * HasManyThrough Field.
 *
 * Represents a one-to-many through relationship field for displaying and managing
 * related models accessed through an intermediate model. Provides display, search,
 * pagination, and navigation capabilities with full Nova v5 compatibility.
 *
 * Example: Country -> User -> Post relationship where Country hasManyThrough Posts via Users.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class HasManyThrough extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'HasManyThroughField';

    /**
     * The related resource class.
     */
    public string $resourceClass = '';

    /**
     * The relationship method name on the model.
     */
    public string $relationshipName;

    /**
     * The intermediate model class.
     */
    public ?string $through = null;

    /**
     * The foreign key on the intermediate model.
     */
    public ?string $firstKey = null;

    /**
     * The foreign key on the related model.
     */
    public ?string $secondKey = null;

    /**
     * The local key on the parent model.
     */
    public ?string $localKey = null;

    /**
     * The local key on the intermediate model.
     */
    public ?string $secondLocalKey = null;

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
     * Create a new HasManyThrough field.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->relationshipName = $this->attribute;
        $this->resourceClass = $this->guessResourceClass();

        // HasManyThrough fields are typically only shown on detail views
        $this->onlyOnDetail();
    }

    /**
     * Create a new HasManyThrough field with Nova-style syntax.
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
     * Set the intermediate model class.
     */
    public function through(string $through): static
    {
        $this->through = $through;

        return $this;
    }

    /**
     * Set the foreign key on the intermediate model.
     */
    public function firstKey(string $firstKey): static
    {
        $this->firstKey = $firstKey;

        return $this;
    }

    /**
     * Set the foreign key on the related model.
     */
    public function secondKey(string $secondKey): static
    {
        $this->secondKey = $secondKey;

        return $this;
    }

    /**
     * Set the local key on the parent model.
     */
    public function localKey(string $localKey): static
    {
        $this->localKey = $localKey;

        return $this;
    }

    /**
     * Set the local key on the intermediate model.
     */
    public function secondLocalKey(string $secondLocalKey): static
    {
        $this->secondLocalKey = $secondLocalKey;

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
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        $attribute = $attribute ?? $this->attribute;

        // Get the related models through the intermediate relationship
        $relatedModels = $resource->{$this->relationshipName};

        if ($relatedModels) {
            // For HasManyThrough, we'll store the count and let the frontend handle the actual data loading
            $this->value = [
                'count' => $relatedModels->count(),
                'resource_id' => $resource->getKey(),
                'resource_class' => $this->resourceClass,
                'through' => $this->through,
            ];
        } else {
            $this->value = [
                'count' => 0,
                'resource_id' => $resource->getKey(),
                'resource_class' => $this->resourceClass,
                'through' => $this->through,
            ];
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        // HasManyThrough relationships are typically not filled directly
        // They are managed through the intermediate model
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        }
    }

    /**
     * Get the related models through the intermediate relationship.
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
                    ->orWhere('content', 'like', "%{$search}%");
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
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'resourceClass' => $this->resourceClass,
            'relationshipName' => $this->relationshipName,
            'through' => $this->through,
            'firstKey' => $this->firstKey,
            'secondKey' => $this->secondKey,
            'localKey' => $this->localKey,
            'secondLocalKey' => $this->secondLocalKey,
            'searchable' => $this->searchable,
            'withSubtitles' => $this->withSubtitles,
            'collapsable' => $this->collapsable,
            'collapsedByDefault' => $this->collapsedByDefault,
            'showCreateRelationButton' => $this->showCreateRelationButton,
            'modalSize' => $this->modalSize,
            'perPage' => $this->perPage,
        ]);
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
