<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Markdown Field.
 *
 * A rich markdown editor field using BlockNote with Notion-style editing,
 * traditional WYSIWYG toolbar, and excellent copy-paste support.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Markdown extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'MarkdownField';

    /**
     * Whether to show the formatting toolbar.
     */
    public bool $showToolbar = true;

    /**
     * Whether to enable slash commands.
     */
    public bool $enableSlashCommands = true;

    /**
     * The height of the editor in pixels.
     */
    public ?int $height = null;

    /**
     * Whether the editor should auto-resize.
     */
    public bool $autoResize = true;



    /**
     * Enable the formatting toolbar.
     */
    public function withToolbar(bool $show = true): static
    {
        $this->showToolbar = $show;

        return $this;
    }

    /**
     * Disable the formatting toolbar.
     */
    public function withoutToolbar(): static
    {
        $this->showToolbar = false;

        return $this;
    }

    /**
     * Enable slash commands.
     */
    public function withSlashCommands(bool $enable = true): static
    {
        $this->enableSlashCommands = $enable;

        return $this;
    }

    /**
     * Disable slash commands.
     */
    public function withoutSlashCommands(): static
    {
        $this->enableSlashCommands = false;

        return $this;
    }

    /**
     * Set the height of the editor.
     */
    public function height(int $height): static
    {
        $this->height = $height;
        $this->autoResize = false;

        return $this;
    }

    /**
     * Enable or disable auto-resize.
     */
    public function autoResize(bool $autoResize = true): static
    {
        $this->autoResize = $autoResize;

        return $this;
    }



    /**
     * Set the maximum length for the content.
     */
    public function maxlength(int $maxlength): static
    {
        return $this->rules("max:$maxlength");
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

            // Normalize line endings for markdown content
            if (is_string($value)) {
                $value = str_replace(["\r\n", "\r"], "\n", $value);
                // Trim only leading/trailing whitespace, preserve internal formatting
                $value = trim($value);
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
            'showToolbar' => $this->showToolbar,
            'enableSlashCommands' => $this->enableSlashCommands,
            'height' => $this->height,
            'autoResize' => $this->autoResize,
        ]);
    }
}
