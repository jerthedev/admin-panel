<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use JTD\AdminPanel\Resources\Resource;

/**
 * Comment Resource Fixture for Testing.
 *
 * Used for testing MorphMany relationships.
 */
class CommentResource extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static string $model = Comment::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     */
    public static string $title = 'content';

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Comments';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Comment';
    }

    /**
     * Get the value that should be displayed to represent the resource.
     */
    public function title(): string
    {
        $content = $this->resource->content ?? '';
        $truncated = strlen($content) > 50 ? substr($content, 0, 50).'...' : $content;

        return $truncated ?: "Comment #{$this->resource->id}";
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
