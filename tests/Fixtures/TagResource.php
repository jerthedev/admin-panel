<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use JTD\AdminPanel\Resources\Resource;

/**
 * Tag Resource Fixture for Testing.
 *
 * Used for testing MorphToMany relationships.
 */
class TagResource extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static string $model = Tag::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     */
    public static string $title = 'name';

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Tags';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Tag';
    }

    /**
     * Get the value that should be displayed to represent the resource.
     */
    public function title(): string
    {
        return $this->resource->name ?? "Tag #{$this->resource->id}";
    }

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(\Illuminate\Http\Request $request): array
    {
        return [
            // Fields would be defined here in a real implementation
        ];
    }
}
