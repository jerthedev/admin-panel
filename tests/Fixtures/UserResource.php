<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Http\Request;
use JTD\AdminPanel\Actions\DeleteAction;
use JTD\AdminPanel\Actions\UpdateStatusAction;
use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Fields\Email;
use JTD\AdminPanel\Fields\Password;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Filters\BooleanFilter;
use JTD\AdminPanel\Filters\TextFilter;
use JTD\AdminPanel\Resources\Resource;

/**
 * Test User Resource
 *
 * User resource for testing admin panel functionality.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class UserResource extends Resource
{
    public static string $model = User::class;

    public static string $title = 'name';

    public static array $search = ['name', 'email'];

    public function fields(Request $request): array
    {
        return [
            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Email::make('Email')
                ->sortable()
                ->rules('required', 'email', 'unique:users,email,{{resourceId}}'),

            Password::make('Password')
                ->onlyOnForms()
                ->rules('required', 'min:8')
                ->creationRules('required')
                ->updateRules('nullable'),

            Boolean::make('Is Admin', 'is_admin')
                ->sortable(),

            Boolean::make('Is Active', 'is_active')
                ->sortable(),
        ];
    }

    public function filters(Request $request): array
    {
        return [
            BooleanFilter::forActiveStatus(),
            TextFilter::make('Search', 'search')
                ->withColumns(['name', 'email']),
        ];
    }

    public function actions(Request $request): array
    {
        return [
            UpdateStatusAction::activate(),
            UpdateStatusAction::deactivate(),
            DeleteAction::make(),
        ];
    }

    public static function searchableColumns(): array
    {
        return ['name', 'email'];
    }
}
