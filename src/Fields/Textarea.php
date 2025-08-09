<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Textarea Field.
 *
 * A textarea input field with support for character limits and auto-resize.
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
    public ?int $maxLength = null;

    /**
     * Whether the textarea should auto-resize.
     */
    public bool $autoResize = false;

    /**
     * Whether to show the character count.
     */
    public bool $showCharacterCount = false;

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
    public function maxLength(int $maxLength): static
    {
        $this->maxLength = $maxLength;
        $this->showCharacterCount = true;

        return $this;
    }

    /**
     * Enable auto-resize for the textarea.
     */
    public function autoResize(bool $autoResize = true): static
    {
        $this->autoResize = $autoResize;

        return $this;
    }

    /**
     * Show or hide the character count.
     */
    public function showCharacterCount(bool $show = true): static
    {
        $this->showCharacterCount = $show;

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
            'maxLength' => $this->maxLength,
            'autoResize' => $this->autoResize,
            'showCharacterCount' => $this->showCharacterCount,
            'alwaysShow' => $this->alwaysShow,
        ]);
    }
}
