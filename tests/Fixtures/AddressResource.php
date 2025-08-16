<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\BelongsTo;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Resources\Resource;

/**
 * Address Resource Test Fixture
 *
 * Test resource for Address model used in integration tests.
 */
class AddressResource extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static string $model = Address::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     */
    public static string $title = 'street';

    /**
     * The columns that should be searched.
     */
    public static array $search = [
        'street',
        'city',
        'state',
        'zip',
    ];

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(Request $request): array
    {
        return [
            Text::make('Street')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('City')
                ->sortable()
                ->rules('required', 'max:100'),

            Text::make('State')
                ->sortable()
                ->rules('nullable', 'max:50'),

            Text::make('ZIP Code', 'zip')
                ->sortable()
                ->rules('nullable', 'max:20'),

            Text::make('Country')
                ->sortable()
                ->rules('nullable', 'max:100')
                ->default('USA'),

            BelongsTo::make('User')
                ->sortable()
                ->rules('required', 'exists:users,id'),
        ];
    }

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
     * Get the lenses available for the resource.
     */
    public function lenses(Request $request): array
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
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Addresses';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Address';
    }

    /**
     * Get the URI key for the resource.
     */
    public static function uriKey(): string
    {
        return 'addresses';
    }

    /**
     * Get the title for the resource.
     */
    public function title(): string
    {
        $address = $this->resource;

        if ($address->street && $address->city) {
            return "{$address->street}, {$address->city}";
        }

        if ($address->street) {
            return $address->street;
        }

        return "Address #{$address->id}";
    }

    /**
     * Get the subtitle for the resource.
     */
    public function subtitle(): ?string
    {
        $address = $this->resource;

        $parts = array_filter([
            $address->state,
            $address->zip,
            $address->country,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Build a relatable query for the given resource.
     */
    public static function relatableQuery(Request $request, $query)
    {
        return $query;
    }

    /**
     * Get the logical group associated with the resource.
     */
    public static function group(): string
    {
        return 'User Management';
    }
}
