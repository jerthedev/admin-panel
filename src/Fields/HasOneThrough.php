<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * HasOneThrough Field.
 *
 * Represents a one-to-one through relationship field for displaying related models
 * accessed through an intermediate model. Provides display and navigation to the
 * related model with full Nova v5 compatibility.
 *
 * Example: Mechanic -> Car -> Owner relationship where Mechanic hasOneThrough Owner via Car.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class HasOneThrough extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'HasOneThroughField';

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
     * Create a new HasOneThrough field.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->relationshipName = $this->attribute;
        $this->resourceClass = $this->guessResourceClass();

        // HasOneThrough fields are typically only shown on detail views
        $this->onlyOnDetail();
    }

    /**
     * Create a new HasOneThrough field with Nova-style syntax.
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
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        $attribute = $attribute ?? $this->attribute;

        // Get the related model through the intermediate relationship
        $relatedModel = $resource->{$this->relationshipName};

        if ($relatedModel) {
            // Create a resource instance to get the title
            $resourceInstance = new $this->resourceClass($relatedModel);

            $this->value = [
                'id' => $relatedModel->getKey(),
                'title' => $resourceInstance->title(),
                'resource_class' => $this->resourceClass,
                'exists' => true,
                'through' => $this->through,
            ];
        } else {
            $this->value = [
                'id' => null,
                'title' => null,
                'resource_class' => $this->resourceClass,
                'exists' => false,
                'through' => $this->through,
            ];
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        // HasOneThrough relationships are typically not filled directly
        // They are managed through the intermediate model
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        }
    }

    /**
     * Get the related model through the intermediate relationship.
     */
    public function getRelatedModel(Model $parentModel): ?Model
    {
        return $parentModel->{$this->relationshipName};
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
