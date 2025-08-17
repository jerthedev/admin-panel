<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Heading Field.
 *
 * A field that displays a banner across forms and can function as a separator
 * for long lists of fields. Does not correspond to any database column.
 * 100% compatible with Nova v5 Heading field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Heading extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'HeadingField';

    /**
     * Create a new heading field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Heading fields are automatically hidden from the resource index page
        $this->showOnIndex = false;

        // Heading fields should be shown on detail, creation, and update views
        $this->showOnDetail = true;
        $this->showOnCreation = true;
        $this->showOnUpdate = true;

        // Heading fields don't correspond to database columns
        $this->nullable = true;
        $this->readonly = true;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     * Heading fields don't store data, so this is a no-op.
     */
    public function fill(Request $request, $model): void
    {
        // Heading fields don't store data, so we don't fill anything
    }

    /**
     * Resolve the field's value for display.
     * For heading fields, the value is the name/text to display.
     */
    public function resolveForDisplay($resource, ?string $attribute = null): void
    {
        // For heading fields, the display value is the field name
        $this->value = $this->name;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'asHtml' => $this->asHtml,
            'isHeading' => true,
        ]);
    }
}
