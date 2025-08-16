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
     * Conditional searchable callback.
     */
    public $searchableCallback = null;

    /**
     * Whether to show subtitles in search results.
     */
    public bool $withSubtitles = false;

    /**
     * Whether to show the create relation button.
     */
    public bool $showCreateRelationButton = false;

    /**
     * Conditional create relation button callback.
     */
    public $showCreateRelationButtonCallback = null;

    /**
     * Whether to hide the create relation button.
     */
    public bool $hideCreateRelationButton = false;

    /**
     * The modal size for inline creation.
     */
    public string $modalSize = 'md';

    /**
     * Whether peeking is enabled.
     */
    public bool $peekable = true;

    /**
     * Conditional peeking callback.
     */
    public $peekableCallback = null;

    /**
     * Whether the relationship is nullable.
     */
    public bool $nullable = false;

    /**
     * Whether to reorder associatables by title.
     */
    public bool $reorderAssociatables = true;

    /**
     * Whether to include trashed models.
     */
    public bool $withTrashed = true;

    /**
     * The display callback for the related model.
     */
    public $displayCallback = null;

    /**
     * The relatable query callback for filtering related models.
     */
    public $relatableQueryCallback = null;

    /**
     * Dependent field configurations.
     */
    public array $dependentFields = [];

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
     * Create a new BelongsTo field with Nova-style syntax.
     *
     * Supports both parent signature and Nova's BelongsTo signature:
     * - BelongsTo::make('User')
     * - BelongsTo::make('Author', 'user', UserResource::class)
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
    public function searchable($searchable = true): static
    {
        if (is_callable($searchable)) {
            $this->searchableCallback = $searchable;
            $this->searchable = true;
        } else {
            $this->searchable = (bool) $searchable;
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
     * Disable peeking at the relationship.
     */
    public function noPeeking(): static
    {
        $this->peekable = false;

        return $this;
    }

    /**
     * Set whether peeking is allowed.
     */
    public function peekable($peekable = true): static
    {
        if (is_callable($peekable)) {
            $this->peekableCallback = $peekable;
            $this->peekable = true;
        } else {
            $this->peekable = (bool) $peekable;
        }

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
     * Disable reordering associatables by title.
     */
    public function dontReorderAssociatables(): static
    {
        $this->reorderAssociatables = false;

        return $this;
    }

    /**
     * Filter out trashed models.
     */
    public function withoutTrashed(): static
    {
        $this->withTrashed = false;

        return $this;
    }

    /**
     * Show the create relation button for creating new related models (Nova-style).
     */
    public function showCreateRelationButton($showCreateRelationButton = true): static
    {
        if (is_callable($showCreateRelationButton)) {
            $this->showCreateRelationButtonCallback = $showCreateRelationButton;
            $this->showCreateRelationButton = true;
        } else {
            $this->showCreateRelationButton = (bool) $showCreateRelationButton;
        }

        return $this;
    }

    /**
     * Hide the create relation button.
     */
    public function hideCreateRelationButton(): static
    {
        $this->hideCreateRelationButton = true;
        $this->showCreateRelationButton = false;

        return $this;
    }

    /**
     * Set the modal size for inline creation.
     */
    public function modalSize(string $size): static
    {
        $this->modalSize = $size;

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
     * Set a relatable query callback for filtering related models (Nova-style).
     */
    public function relatableQueryUsing(callable $callback): static
    {
        $this->relatableQueryCallback = $callback;

        return $this;
    }

    /**
     * Make this field dependent on other fields.
     */
    public function dependsOn($fields, callable $callback): static
    {
        if (! is_array($fields)) {
            $fields = [$fields];
        }

        $this->dependentFields[] = [
            'fields' => $fields,
            'callback' => $callback,
        ];

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

        // Filter trashed models if specified
        if (! $this->withTrashed) {
            $query->whereNull('deleted_at');
        }

        // Apply custom relatable query callback if provided
        if ($this->relatableQueryCallback) {
            $query = call_user_func($this->relatableQueryCallback, $request, $query);
        }

        // Apply resource's relatable query
        $query = $this->resourceClass::relatableQuery($request, $query);

        // Order by title if reordering is enabled
        if ($this->reorderAssociatables) {
            $titleColumn = $this->resourceClass::$title ?? 'name';
            $query->orderBy($titleColumn);
        }

        $models = $query->get();
        $options = [];

        foreach ($models as $model) {
            $resourceInstance = new $this->resourceClass($model);

            if ($this->displayCallback) {
                $label = call_user_func($this->displayCallback, $model);
            } else {
                $label = $resourceInstance->title();
            }

            $option = [
                'value' => $model->getKey(),
                'label' => $label,
            ];

            // Add subtitle if enabled
            if ($this->withSubtitles && method_exists($resourceInstance, 'subtitle')) {
                $option['subtitle'] = $resourceInstance->subtitle();
            }

            $options[] = $option;
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
            'searchable' => $this->resolveSearchable(),
            'withSubtitles' => $this->withSubtitles,
            'showCreateRelationButton' => $this->resolveShowCreateRelationButton(),
            'hideCreateRelationButton' => $this->hideCreateRelationButton,
            'modalSize' => $this->modalSize,
            'peekable' => $this->resolvePeekable(),
            'nullable' => $this->nullable,
            'reorderAssociatables' => $this->reorderAssociatables,
            'withTrashed' => $this->withTrashed,
            'dependentFields' => $this->dependentFields,
        ]);
    }

    /**
     * Resolve the searchable value.
     */
    protected function resolveSearchable(): bool
    {
        if ($this->searchableCallback) {
            return call_user_func($this->searchableCallback, request());
        }

        return $this->searchable;
    }

    /**
     * Resolve the show create relation button value.
     */
    protected function resolveShowCreateRelationButton(): bool
    {
        if ($this->hideCreateRelationButton) {
            return false;
        }

        if ($this->showCreateRelationButtonCallback) {
            return call_user_func($this->showCreateRelationButtonCallback, request());
        }

        return $this->showCreateRelationButton;
    }

    /**
     * Resolve the peekable value.
     */
    protected function resolvePeekable(): bool
    {
        if ($this->peekableCallback) {
            return call_user_func($this->peekableCallback, request());
        }

        return $this->peekable;
    }
}
