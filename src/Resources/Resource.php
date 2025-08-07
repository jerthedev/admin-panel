<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JTD\AdminPanel\Menu\MenuItem;

/**
 * Base Resource Class
 *
 * Abstract base class for all admin panel resources. Provides Nova-like
 * API for resource registration, field definition, and CRUD operations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Resources
 */
abstract class Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static string $model;

    /**
     * The single value that should be used to represent the resource when being displayed.
     */
    public static string $title = 'id';

    /**
     * The columns that should be searched.
     */
    public static array $search = [];

    /**
     * The number of resources to show per page via relationships.
     */
    public static int $perPageViaRelationship = 5;

    /**
     * Indicates if the resource should be globally searchable.
     */
    public static bool $globallySearchable = true;

    /**
     * The logical group associated with the resource.
     */
    public static ?string $group = null;

    /**
     * The underlying model resource instance.
     */
    public Model $resource;

    /**
     * Create a new resource instance.
     */
    public function __construct(?Model $resource = null)
    {
        $this->resource = $resource ?? $this->newModel();
    }

    /**
     * Get the fields displayed by the resource.
     */
    abstract public function fields(Request $request): array;

    /**
     * Get the cards available for the request.
     */
    public function cards(Request $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     */
    public function filters(Request $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     */
    public function actions(Request $request): array
    {
        return [];
    }

    /**
     * Get the metrics available for the resource.
     */
    public function metrics(Request $request): array
    {
        return [];
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        $className = class_basename(get_called_class());

        // Remove 'Resource' suffix if present
        if (Str::endsWith($className, 'Resource')) {
            $className = Str::substr($className, 0, -8);
        }

        return Str::plural(Str::title(Str::snake($className, ' ')));
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        $className = class_basename(get_called_class());

        // Remove 'Resource' suffix if present
        if (Str::endsWith($className, 'Resource')) {
            $className = Str::substr($className, 0, -8);
        }

        return Str::title(Str::snake($className, ' '));
    }

    /**
     * Get the URI key for the resource.
     */
    public static function uriKey(): string
    {
        $className = class_basename(get_called_class());

        // Remove 'Resource' suffix if present
        if (Str::endsWith($className, 'Resource')) {
            $className = Str::substr($className, 0, -8);
        }

        return Str::plural(Str::kebab($className));
    }

    /**
     * Create a new instance of the underlying model.
     */
    public static function newModel(): Model
    {
        $model = static::$model;

        return new $model;
    }

    /**
     * Get the underlying model class name.
     */
    public static function model(): string
    {
        return static::$model;
    }

    /**
     * Get the searchable columns for the resource.
     * Combines both $search array and fields marked as searchable().
     */
    public static function searchableColumns(): array
    {
        // Start with explicitly defined search columns
        $searchColumns = static::$search ?? [];

        // Add columns from fields marked as searchable
        $searchableFields = static::getSearchableFieldColumns();

        // Merge and remove duplicates
        $allColumns = array_unique(array_merge($searchColumns, $searchableFields));

        // If no search columns defined anywhere, fall back to title column
        return empty($allColumns) ? [static::$title] : $allColumns;
    }

    /**
     * Get database columns from fields marked as searchable().
     */
    protected static function getSearchableFieldColumns(): array
    {
        $resource = new static(static::newModel());
        $fields = $resource->fields(request());

        $searchableColumns = [];

        foreach ($fields as $field) {
            if (isset($field->searchable) && $field->searchable === true) {
                $searchableColumns[] = $field->attribute;
            }
        }

        return $searchableColumns;
    }

    /**
     * Build an "index" query for the given resource.
     */
    public static function indexQuery(Request $request, $query)
    {
        return $query;
    }

    /**
     * Build a "detail" query for the given resource.
     */
    public static function detailQuery(Request $request, $query)
    {
        return $query;
    }

    /**
     * Build a "relatable" query for the given resource.
     */
    public static function relatableQuery(Request $request, $query)
    {
        return $query;
    }

    /**
     * Get the value that should be displayed to represent the resource.
     */
    public function title(): string
    {
        return (string) $this->resource->{static::$title};
    }

    /**
     * Get the subtitle that should be displayed for the resource.
     */
    public function subtitle(): ?string
    {
        return null;
    }

    /**
     * Determine if this resource is available for navigation.
     */
    public static function availableForNavigation(Request $request): bool
    {
        return true;
    }

    /**
     * Determine if the current user can view the resource.
     */
    public function authorizedToView(Request $request): bool
    {
        return true;
    }

    /**
     * Determine if the current user can create new resources.
     */
    public function authorizedToCreate(Request $request): bool
    {
        return true;
    }

    /**
     * Determine if the current user can update the resource.
     */
    public function authorizedToUpdate(Request $request): bool
    {
        return true;
    }

    /**
     * Determine if the current user can delete the resource.
     */
    public function authorizedToDelete(Request $request): bool
    {
        return true;
    }

    /**
     * Get the fields that are available for the given request.
     */
    public function availableFields(Request $request): Collection
    {
        return collect($this->fields($request))->filter(function ($field) use ($request) {
            return $field->authorize($request);
        });
    }

    /**
     * Get the fields that are available for the index view.
     */
    public function indexFields(Request $request): Collection
    {
        return $this->availableFields($request)->filter(function ($field) {
            return $field->showOnIndex;
        });
    }

    /**
     * Get the fields that are available for the detail view.
     */
    public function detailFields(Request $request): Collection
    {
        return $this->availableFields($request)->filter(function ($field) {
            return $field->showOnDetail;
        });
    }

    /**
     * Get the fields that are available for creation.
     */
    public function creationFields(Request $request): Collection
    {
        return $this->availableFields($request)->filter(function ($field) {
            return $field->showOnCreation;
        });
    }

    /**
     * Get the fields that are available for updating.
     */
    public function updateFields(Request $request): Collection
    {
        return $this->availableFields($request)->filter(function ($field) {
            return $field->showOnUpdate;
        });
    }

    /**
     * Resolve the resource's fields for display.
     */
    public function resolveFields(Request $request): Collection
    {
        return $this->availableFields($request)->each(function ($field) {
            $field->resolve($this->resource);
        });
    }

    /**
     * Fill the resource's fields.
     */
    public function fill(Request $request, Model $model): void
    {
        // Determine which fields to use based on whether this is creation or update
        $fields = $model->exists ? $this->updateFields($request) : $this->creationFields($request);

        $fields->each(function ($field) use ($request, $model) {
            $field->fill($request, $model);
        });
    }

    /**
     * Get the key name of the resource.
     */
    public function getKey()
    {
        return $this->resource->getKey();
    }

    /**
     * Get the route key name for the resource.
     */
    public function getRouteKeyName(): string
    {
        return $this->resource->getRouteKeyName();
    }

    /**
     * Get the fields for the index view.
     */
    public function fieldsForIndex(Request $request): array
    {
        return $this->indexFields($request)->toArray();
    }

    /**
     * Get the fields for the detail view.
     */
    public function fieldsForDetail(Request $request): array
    {
        return $this->detailFields($request)->toArray();
    }

    /**
     * Get the fields for the create form.
     */
    public function fieldsForCreate(Request $request): array
    {
        return $this->creationFields($request)->toArray();
    }

    /**
     * Get the fields for the update form.
     */
    public function fieldsForUpdate(Request $request): array
    {
        return $this->updateFields($request)->toArray();
    }

    /**
     * Get a new query builder for the resource's model.
     */
    public function newQuery()
    {
        return $this->newModel()->newQuery();
    }

    /**
     * Resolve fields for display with a specific model.
     */
    public function resolveFieldsForDisplay(Model $model, Request $request): array
    {
        $resource = new static($model);
        $fields = $resource->resolveFields($request);

        return $fields->map(function ($field) {
            return $field->jsonSerialize();
        })->toArray();
    }

    /**
     * Get the menu that should represent the resource.
     */
    public function menu(Request $request): MenuItem
    {
        return MenuItem::make(static::label(), route('admin-panel.resources.index', static::uriKey()))
            ->withIcon(static::$icon ?? 'DocumentTextIcon');
    }
}
