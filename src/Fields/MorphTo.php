<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * MorphTo Field.
 *
 * Represents a polymorphic inverse relationship field for selecting and managing
 * the parent model in a polymorphic relationship. Provides selection and navigation
 * capabilities with full Nova v5 compatibility for morphTo Eloquent relationships.
 *
 * Example: Comment morphTo Post|Video (where Comment can belong to Post, Video, etc.)
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MorphTo extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'MorphToField';

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
     * The allowed resource types for this polymorphic relationship.
     */
    public array $types = [];

    /**
     * Whether the relationship is nullable.
     */
    public bool $nullable = false;

    /**
     * Whether peeking is enabled.
     */
    public bool $peekable = true;

    /**
     * The default value for the relationship.
     */
    public $defaultValue = null;

    /**
     * The default resource class for the relationship.
     */
    public ?string $defaultResourceClass = null;

    /**
     * Whether the relationship is searchable.
     */
    public bool $searchable = false;

    /**
     * Whether to show subtitles in search results.
     */
    public bool $withSubtitles = false;

    /**
     * Whether to show the create relation button.
     */
    public bool $showCreateRelationButton = false;

    /**
     * The modal size for inline creation.
     */
    public ?string $modalSize = null;

    /**
     * A query callback for filtering relatable models.
     */
    public $relatableQueryCallback = null;

    /**
     * Create a new MorphTo field.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->relationshipName = $this->attribute;
    }

    /**
     * Create a new MorphTo field with Nova-style syntax.
     */
    public static function make(string $name, ?string $attribute = null, ?callable $resolveCallback = null): static
    {
        return new static($name, $attribute, $resolveCallback);
    }

    /**
     * Set the allowed resource types for this polymorphic relationship.
     */
    public function types(array $types): static
    {
        $this->types = $types;

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
     * Make the relationship nullable.
     */
    public function nullable(bool $nullable = true): static
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * Disable peeking for this relationship.
     */
    public function noPeeking(): static
    {
        $this->peekable = false;

        return $this;
    }

    /**
     * Set whether peeking is enabled.
     */
    public function peekable(bool|callable $peekable = true): static
    {
        if (is_callable($peekable)) {
            $this->peekable = call_user_func($peekable);
        } else {
            $this->peekable = $peekable;
        }

        return $this;
    }

    /**
     * Set the default value for the relationship.
     */
    public function default($value): static
    {
        $this->defaultValue = $value;

        return $this;
    }

    /**
     * Set the default resource class for the relationship.
     */
    public function defaultResource(string $resourceClass): static
    {
        $this->defaultResourceClass = $resourceClass;

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

        // Get the related model
        $relatedModel = $resource->{$this->relationshipName};

        if ($relatedModel) {
            // Find the resource class for this model type
            $resourceClass = $this->getResourceClassForModel($relatedModel);

            if ($resourceClass) {
                // Create a resource instance to get the title
                $resourceInstance = new $resourceClass($relatedModel);

                $this->value = [
                    'id' => $relatedModel->getKey(),
                    'title' => $resourceInstance->title(),
                    'resource_class' => $resourceClass,
                    'morph_type' => get_class($relatedModel),
                    'exists' => true,
                ];
            } else {
                $this->value = [
                    'id' => $relatedModel->getKey(),
                    'title' => "#{$relatedModel->getKey()}",
                    'resource_class' => null,
                    'morph_type' => get_class($relatedModel),
                    'exists' => true,
                ];
            }
        } else {
            $this->value = [
                'id' => $this->defaultValue,
                'title' => null,
                'resource_class' => $this->defaultResourceClass,
                'morph_type' => null,
                'exists' => false,
            ];
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        // MorphTo relationships are typically filled through the form data
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
            'relationshipName' => $this->relationshipName,
            'morphType' => $this->morphType,
            'morphId' => $this->morphId,
            'types' => $this->types,
            'nullable' => $this->nullable,
            'peekable' => $this->peekable,
            'defaultValue' => $this->defaultValue,
            'defaultResourceClass' => $this->defaultResourceClass,
            'searchable' => $this->searchable,
            'withSubtitles' => $this->withSubtitles,
            'showCreateRelationButton' => $this->showCreateRelationButton,
            'modalSize' => $this->modalSize,
        ]);
    }

    /**
     * Get the resource class for a given model.
     */
    protected function getResourceClassForModel($model): ?string
    {
        $modelClass = get_class($model);

        foreach ($this->types as $resourceClass) {
            if (class_exists($resourceClass) && property_exists($resourceClass, 'model')) {
                if ($resourceClass::$model === $modelClass) {
                    return $resourceClass;
                }
            }
        }

        return null;
    }
}
