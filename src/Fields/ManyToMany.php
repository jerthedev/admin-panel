<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * ManyToMany Field.
 *
 * Represents a many-to-many relationship field for managing related models.
 * Provides multi-select interface with pivot data support and attach/detach operations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ManyToMany extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'ManyToManyField';

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
    public ?string $pivotTable = null;

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
     * Whether the relationship should be searchable.
     */
    public bool $searchable = true;

    /**
     * Whether to show the attach button.
     */
    public bool $showAttachButton = true;

    /**
     * Whether to show the detach button.
     */
    public bool $showDetachButton = true;

    /**
     * The pivot fields to display and manage.
     */
    public array $pivotFields = [];

    /**
     * The display callback for the related model.
     */
    public $displayCallback = null;

    /**
     * The query callback for filtering related models.
     */
    public $queryCallback = null;

    /**
     * Create a new ManyToMany field.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->relationshipName = $this->attribute;
        $this->resourceClass = $this->guessResourceClass();

        // ManyToMany fields are typically only shown on detail views
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
     * Set the pivot table name.
     */
    public function pivotTable(string $pivotTable): static
    {
        $this->pivotTable = $pivotTable;

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
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

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
     * Show the detach button for detaching models.
     */
    public function showDetachButton(bool $showDetachButton = true): static
    {
        $this->showDetachButton = $showDetachButton;

        return $this;
    }

    /**
     * Set the pivot fields to display and manage.
     */
    public function pivotFields(array $fields): static
    {
        $this->pivotFields = $fields;

        return $this;
    }

    /**
     * Set a custom display callback for the related model.
     */
    public function display(callable $callback): static
    {
        $this->displayCallback = $callback;

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
            // For ManyToMany, we'll store the count and let the frontend handle the actual data loading
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
        // ManyToMany relationships are typically not filled directly
        // They are managed through separate endpoints for attach/detach operations
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        }
    }

    /**
     * Get the available options for attaching.
     */
    public function getAttachableOptions(Request $request, Model $parentModel): array
    {
        $query = $this->resourceClass::newModel()->newQuery();

        // Exclude already attached models
        $attachedIds = $parentModel->{$this->relationshipName}()->pluck($this->relatedKey ?? 'id');
        $query->whereNotIn('id', $attachedIds);

        // Apply custom query callback if provided
        if ($this->queryCallback) {
            $query = call_user_func($this->queryCallback, $request, $query);
        }

        // Apply resource's relatable query
        $query = $this->resourceClass::relatableQuery($request, $query);

        $models = $query->get();
        $options = [];

        foreach ($models as $model) {
            $resourceInstance = new $this->resourceClass($model);

            if ($this->displayCallback) {
                $label = call_user_func($this->displayCallback, $model);
            } else {
                $label = $resourceInstance->title();
            }

            $options[] = [
                'value' => $model->getKey(),
                'label' => $label,
            ];
        }

        return $options;
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
            'pivotTable' => $this->pivotTable,
            'foreignPivotKey' => $this->foreignPivotKey,
            'relatedPivotKey' => $this->relatedPivotKey,
            'parentKey' => $this->parentKey,
            'relatedKey' => $this->relatedKey,
            'searchable' => $this->searchable,
            'showAttachButton' => $this->showAttachButton,
            'showDetachButton' => $this->showDetachButton,
            'pivotFields' => $this->pivotFields,
        ]);
    }
}
