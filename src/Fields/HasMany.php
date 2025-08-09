<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * HasMany Field.
 *
 * Represents a one-to-many relationship field for displaying and managing related models.
 * Provides table display with pagination, search, and CRUD operations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class HasMany extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'HasManyField';

    /**
     * The related resource class.
     */
    public string $resourceClass = '';

    /**
     * The relationship method name on the model.
     */
    public string $relationshipName;

    /**
     * The foreign key attribute.
     */
    public ?string $foreignKey = null;

    /**
     * The local key attribute.
     */
    public ?string $localKey = null;

    /**
     * Whether the relationship should be searchable.
     */
    public bool $searchable = true;

    /**
     * Whether to show the create button.
     */
    public bool $showCreateButton = true;

    /**
     * Whether to show the attach button.
     */
    public bool $showAttachButton = false;

    /**
     * The number of items to display per page.
     */
    public int $perPage = 10;

    /**
     * The fields to display in the table.
     */
    public array $displayFields = [];

    /**
     * The query callback for filtering related models.
     */
    public $queryCallback = null;

    /**
     * Create a new HasMany field.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->relationshipName = $this->attribute;
        $this->resourceClass = $this->guessResourceClass();

        // HasMany fields are typically only shown on detail views
        $this->onlyOnDetail();
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
     * Set the foreign key attribute.
     */
    public function foreignKey(string $foreignKey): static
    {
        $this->foreignKey = $foreignKey;

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
     * Make the relationship searchable.
     */
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Show the create button for creating new related models.
     */
    public function showCreateButton(bool $showCreateButton = true): static
    {
        $this->showCreateButton = $showCreateButton;

        return $this;
    }

    /**
     * Show the attach button for attaching existing models.
     */
    public function showAttachButton(bool $showAttachButton = true): static
    {
        $this->showAttachButton = $showAttachButton;

        return $this;
    }

    /**
     * Set the number of items to display per page.
     */
    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Set the fields to display in the table.
     */
    public function displayFields(array $fields): static
    {
        $this->displayFields = $fields;

        return $this;
    }

    /**
     * Set a query callback for filtering related models.
     */
    public function query(callable $callback): static
    {
        $this->queryCallback = $callback;

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
            // For HasMany, we'll store the count and let the frontend handle the actual data loading
            $this->value = [
                'count' => $relatedModels->count(),
                'resource_id' => $resource->getKey(),
            ];
        } else {
            $this->value = [
                'count' => 0,
                'resource_id' => $resource->getKey(),
            ];
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        // HasMany relationships are typically not filled directly
        // They are managed through separate endpoints
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        }
    }

    /**
     * Get the related models with pagination.
     */
    public function getRelatedModels(Request $request, Model $parentModel): array
    {
        $query = $parentModel->{$this->relationshipName}();

        // Apply custom query callback if provided
        if ($this->queryCallback) {
            $query = call_user_func($this->queryCallback, $request, $query);
        }

        // Apply search if provided
        if ($request->has('search') && $this->searchable) {
            $search = $request->get('search');
            $query = $this->resourceClass::applySearch($query, $search);
        }

        // Apply ordering
        if ($request->has('orderBy')) {
            $direction = $request->get('orderByDirection', 'asc');
            $query->orderBy($request->get('orderBy'), $direction);
        }

        // Paginate
        $page = $request->get('page', 1);
        $perPage = $request->get('perPage', $this->perPage);

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
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

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'resourceClass' => $this->resourceClass,
            'relationshipName' => $this->relationshipName,
            'foreignKey' => $this->foreignKey,
            'localKey' => $this->localKey,
            'searchable' => $this->searchable,
            'showCreateButton' => $this->showCreateButton,
            'showAttachButton' => $this->showAttachButton,
            'perPage' => $this->perPage,
            'displayFields' => $this->displayFields,
        ]);
    }
}
