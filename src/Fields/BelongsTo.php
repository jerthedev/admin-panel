<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * BelongsTo Field.
 *
 * Represents a many-to-one relationship field for selecting related models.
 * Provides dropdown selection with search capabilities and custom display options.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class BelongsTo extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'BelongsToField';

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
     * The owner key attribute.
     */
    public ?string $ownerKey = null;

    /**
     * Whether the relationship should be searchable.
     */
    public bool $searchable = true;

    /**
     * Whether to show the create button.
     */
    public bool $showCreateButton = false;

    /**
     * The display callback for the related model.
     */
    public $displayCallback = null;

    /**
     * The query callback for filtering related models.
     */
    public $queryCallback = null;

    /**
     * Create a new BelongsTo field.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->relationshipName = $this->attribute;
        $this->resourceClass = $this->guessResourceClass();
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
     * Set the owner key attribute.
     */
    public function ownerKey(string $ownerKey): static
    {
        $this->ownerKey = $ownerKey;

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

        // Get the related model
        $relatedModel = $resource->{$this->relationshipName};

        if ($relatedModel) {
            // Use custom display callback if provided
            if ($this->displayCallback) {
                $this->value = call_user_func($this->displayCallback, $relatedModel);
            } else {
                // Use the resource's title method or fall back to the model's title
                $resourceInstance = new $this->resourceClass($relatedModel);
                $this->value = $resourceInstance->title();
            }
        } else {
            $this->value = null;
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        } elseif ($request->exists($this->attribute)) {
            $value = $request->input($this->attribute);

            // Set the foreign key value
            $foreignKey = $this->foreignKey ?? $this->relationshipName.'_id';
            $model->{$foreignKey} = $value;
        }
    }

    /**
     * Get the available options for the relationship.
     */
    public function getOptions(Request $request): array
    {
        $query = $this->resourceClass::newModel()->newQuery();

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
            'foreignKey' => $this->foreignKey,
            'ownerKey' => $this->ownerKey,
            'searchable' => $this->searchable,
            'showCreateButton' => $this->showCreateButton,
        ]);
    }
}
