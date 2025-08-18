<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Textarea Field.
 *
 * A textarea input field compatible with Nova's Textarea field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Textarea extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'TextareaField';

    /**
     * The number of rows for the textarea.
     */
    public int $rows = 4;

    /**
     * The maximum number of characters allowed.
     */
    public ?int $maxlength = null;

    /**
     * Whether to enforce the maximum length client-side.
     */
    public bool $enforceMaxlength = false;

    /**
     * Whether the textarea should always be shown (not collapsed).
     */
    public bool $alwaysShow = false;

    /**
     * Set the number of rows for the textarea.
     */
    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Set the maximum number of characters allowed.
     */
    public function maxlength(int $maxlength): static
    {
        $this->maxlength = $maxlength;

        return $this;
    }

    /**
     * Enforce the maximum length client-side.
     */
    public function enforceMaxlength(bool $enforceMaxlength = true): static
    {
        $this->enforceMaxlength = $enforceMaxlength;

        return $this;
    }

    /**
     * Set whether the textarea should always be shown (not collapsed).
     */
    public function alwaysShow(bool $alwaysShow = true): static
    {
        $this->alwaysShow = $alwaysShow;

        return $this;
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

            // Trim whitespace and normalize line endings
            if (is_string($value)) {
                $value = trim($value);
                $value = str_replace(["\r\n", "\r"], "\n", $value);
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
            'rows' => $this->rows,
            'maxlength' => $this->maxlength,
            'enforceMaxlength' => $this->enforceMaxlength,
            'alwaysShow' => $this->alwaysShow,
        ]);
    }
}
