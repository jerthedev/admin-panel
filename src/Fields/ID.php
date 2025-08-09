<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * ID Field.
 *
 * A field for displaying primary keys and other ID values.
 * Typically readonly on creation forms and sortable by default.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ID extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'IDField';

    /**
     * Create a new ID field instance.
     */
    public function __construct(string $name = 'ID', ?string $attribute = null, ?callable $resolveCallback = null)
    {
        // Default attribute to 'id' if not specified, regardless of name
        $attribute = $attribute ?? 'id';

        parent::__construct($name, $attribute, $resolveCallback);

        // ID fields are sortable by default
        $this->sortable = true;

        // ID fields should not be shown on creation forms by default (readonly on create)
        $this->showOnCreation = false;
    }

    /**
     * Create a new ID field instance.
     */
    public static function make(string $name = 'ID', ?string $attribute = null, ?callable $resolveCallback = null): static
    {
        return new static($name, $attribute, $resolveCallback);
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            // ID-specific meta can be added here if needed
        ]);
    }
}
