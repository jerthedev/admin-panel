<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Stack Field.
 *
 * A field for displaying multiple fields in a stacked/vertical layout.
 * Supports Text, BelongsTo, and Line fields with additional formatting features.
 * 100% compatible with Nova v5 Stack field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Stack extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'StackField';

    /**
     * The fields to display in the stack.
     */
    public array $fields = [];

    /**
     * Create a new stack field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Stack fields are display-only and don't correspond to database columns
        $this->readonly = true;
        $this->nullable = true;
    }

    /**
     * Set the fields to display in the stack.
     *
     * @param array $fields Array of Field instances
     */
    public function fields(array $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Add a field to the stack.
     */
    public function addField(Field $field): static
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * Create a Line field and add it to the stack.
     */
    public function line(string $content, $attributeOrCallback = null): static
    {
        if (is_string($attributeOrCallback)) {
            // If second parameter is a string, treat it as an attribute
            $line = new Line($content, $attributeOrCallback);
        } elseif (is_callable($attributeOrCallback)) {
            // If second parameter is callable, treat it as a resolve callback
            $line = new Line($content, null, $attributeOrCallback);
        } else {
            // No second parameter, just use the content as name
            $line = new Line($content);
        }

        $this->fields[] = $line;

        return $this;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     * Stack fields don't store data, so this is a no-op.
     */
    public function fill(Request $request, $model): void
    {
        // Stack fields don't store data, so we don't fill anything
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolveForDisplay($resource, ?string $attribute = null): void
    {
        // Resolve all fields in the stack
        foreach ($this->fields as $field) {
            // Use resolveForDisplay if available (for Line fields), otherwise use resolve
            if (method_exists($field, 'resolveForDisplay')) {
                $field->resolveForDisplay($resource);
            } else {
                $field->resolve($resource);
            }
        }

        // Stack field itself doesn't have a value
        $this->value = null;
    }

    /**
     * Resolve the field's value.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        // Resolve all fields in the stack
        foreach ($this->fields as $field) {
            $field->resolve($resource);
        }

        // Stack field itself doesn't have a value
        $this->value = null;
    }

    /**
     * Get the fields in the stack.
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'fields' => array_map(function ($field) {
                return $field->jsonSerialize();
            }, $this->fields),
            'isStack' => true,
        ]);
    }

    /**
     * Prepare the field for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'fields' => array_map(function ($field) {
                return $field->jsonSerialize();
            }, $this->fields),
        ]);
    }
}
