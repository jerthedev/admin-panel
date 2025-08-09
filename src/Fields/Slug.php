<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Slug Field.
 *
 * A field for generating URL-friendly slugs with auto-updating from other fields.
 * Supports customizable separators, length limits, and uniqueness validation.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Slug extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'SlugField';

    /**
     * The attribute to generate the slug from.
     */
    public ?string $fromAttribute = null;

    /**
     * The separator to use in the slug.
     */
    public string $separator = '-';

    /**
     * The maximum length of the slug.
     */
    public ?int $maxLength = null;

    /**
     * Whether to convert the slug to lowercase.
     */
    public bool $lowercase = true;

    /**
     * The table name for uniqueness validation.
     */
    public ?string $uniqueTable = null;

    /**
     * The column name for uniqueness validation.
     */
    public ?string $uniqueColumn = null;

    /**
     * Set the attribute to generate the slug from.
     */
    public function from(string $attribute): static
    {
        $this->fromAttribute = $attribute;

        return $this;
    }

    /**
     * Set the separator to use in the slug.
     */
    public function separator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * Set the maximum length of the slug.
     */
    public function maxLength(int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    /**
     * Set whether to convert the slug to lowercase.
     */
    public function lowercase(bool $lowercase = true): static
    {
        $this->lowercase = $lowercase;

        return $this;
    }

    /**
     * Add uniqueness validation for the slug.
     */
    public function unique(string $table, ?string $column = null): static
    {
        $this->uniqueTable = $table;
        $this->uniqueColumn = $column ?? $this->attribute;

        return $this;
    }

    /**
     * Generate a slug from the given text.
     */
    public function generateSlug(string $text): string
    {
        // Convert to slug using Laravel's Str helper
        $slug = Str::slug($text, $this->separator);

        // Convert to lowercase if needed
        if ($this->lowercase) {
            $slug = strtolower($slug);
        }

        // Truncate if max length is set
        if ($this->maxLength !== null && strlen($slug) > $this->maxLength) {
            $slug = substr($slug, 0, $this->maxLength);

            // Remove trailing separator if it exists
            $slug = rtrim($slug, $this->separator);
        }

        return $slug;
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

            // If value is empty and we have a fromAttribute, generate slug
            if (empty($value) && $this->fromAttribute && $request->exists($this->fromAttribute)) {
                $sourceValue = $request->input($this->fromAttribute);
                if (! empty($sourceValue)) {
                    $value = $this->generateSlug($sourceValue);
                }
            }

            // Clean the slug if it's provided
            if (! empty($value)) {
                $value = $this->generateSlug($value);
            }

            $model->{$this->attribute} = $value;
        }
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'fromAttribute' => $this->fromAttribute,
            'separator' => $this->separator,
            'maxLength' => $this->maxLength,
            'lowercase' => $this->lowercase,
            'uniqueTable' => $this->uniqueTable,
            'uniqueColumn' => $this->uniqueColumn,
        ]);
    }
}
