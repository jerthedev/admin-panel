<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * MorphOne Field.
 *
 * Represents a one-to-one polymorphic relationship field for displaying and managing
 * related models. Provides display and navigation to the related model with full
 * Nova v5 compatibility for morphOne Eloquent relationships.
 *
 * Example: Post morphOne Image (where Image can belong to Post, Video, etc.)
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MorphOne extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'MorphOneField';

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
     * Whether this is a "morph one of many" relationship.
     */
    public bool $isOfMany = false;

    /**
     * The "of many" relationship method name.
     */
    public ?string $ofManyRelationship = null;

    /**
     * The "of many" resource class.
     */
    public ?string $ofManyResourceClass = null;

    /**
     * Create a new MorphOne field.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->relationshipName = $this->attribute;
        $this->resourceClass = $this->guessResourceClass();

        // MorphOne fields are typically only shown on detail views
        $this->onlyOnDetail();
    }

    /**
     * Create a new MorphOne field with Nova-style syntax.
     */
    public static function make(string $name, ?string $attribute = null, ?callable $resolveCallback = null): static
    {
        return new static($name, $attribute, $resolveCallback);
    }

    /**
     * Create a "morph one of many" relationship field.
     */
    public static function ofMany(string $name, string $ofManyRelationship, string $resourceClass): static
    {
        $field = new static($name);
        $field->isOfMany = true;
        $field->ofManyRelationship = $ofManyRelationship;
        $field->ofManyResourceClass = $resourceClass;
        $field->resourceClass = $resourceClass;

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
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        $attribute = $attribute ?? $this->attribute;

        if ($this->isOfMany) {
            // For "of many" relationships, use the specific relationship method
            $relatedModel = $resource->{$this->ofManyRelationship};
        } else {
            // Get the related model
            $relatedModel = $resource->{$this->relationshipName};
        }

        if ($relatedModel) {
            // Create a resource instance to get the title
            $resourceInstance = new $this->resourceClass($relatedModel);

            $this->value = [
                'id' => $relatedModel->getKey(),
                'title' => $resourceInstance->title(),
                'resource_class' => $this->resourceClass,
                'exists' => true,
                'morph_type' => $this->morphType,
                'morph_id' => $this->morphId,
                'is_of_many' => $this->isOfMany,
                'of_many_relationship' => $this->ofManyRelationship,
            ];
        } else {
            $this->value = [
                'id' => null,
                'title' => null,
                'resource_class' => $this->resourceClass,
                'exists' => false,
                'morph_type' => $this->morphType,
                'morph_id' => $this->morphId,
                'is_of_many' => $this->isOfMany,
                'of_many_relationship' => $this->ofManyRelationship,
            ];
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        // MorphOne relationships are typically not filled directly
        // They are managed through the related model
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
            'isOfMany' => $this->isOfMany,
            'ofManyRelationship' => $this->ofManyRelationship,
            'ofManyResourceClass' => $this->ofManyResourceClass,
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
