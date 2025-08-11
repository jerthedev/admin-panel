<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Fields\Hidden;
use JTD\AdminPanel\Fields\Markdown;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Resources\Resource;

/**
 * Test Post Resource.
 *
 * Post resource for testing admin panel functionality with Markdown field.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class PostResource extends Resource
{
    public static string $model = Post::class;

    public static string $title = 'title';

    public static array $search = ['title', 'content'];

    public function fields(Request $request): array
    {
        return [
            Text::make('Title')
                ->sortable()
                ->rules('required', 'max:255'),

            Markdown::make('Content')
                ->withToolbar()
                ->withSlashCommands()
                ->placeholder('Write your post content here...')
                ->rules('required'),

            Boolean::make('Published', 'is_published')
                ->sortable(),

            Boolean::make('Featured', 'is_featured')
                ->sortable(),

            Hidden::make('User ID', 'user_id')
                ->default(fn ($request) => $request->user()?->id),
        ];
    }
}
