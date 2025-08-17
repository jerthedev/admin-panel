<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Code Field
 *
 * A beautiful code editor field with syntax highlighting for various programming languages.
 * 100% compatible with Nova's Code field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class Code extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'CodeField';

    /**
     * The programming language for syntax highlighting.
     */
    public string $language = 'htmlmixed';

    /**
     * Whether this field is for JSON editing.
     */
    public bool $isJson = false;

    /**
     * Nova-supported programming languages for syntax highlighting.
     */
    protected array $supportedLanguages = [
        'dockerfile',
        'htmlmixed',
        'javascript',
        'markdown',
        'nginx',
        'php',
        'ruby',
        'sass',
        'shell',
        'sql',
        'twig',
        'vim',
        'vue',
        'xml',
        'yaml-frontmatter',
        'yaml',
    ];

    /**
     * Set the programming language for syntax highlighting.
     *
     * @param string $language The programming language
     */
    public function language(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Indicate that this field is for JSON editing.
     * This will set the language to 'javascript' and enable JSON formatting.
     */
    public function json(): static
    {
        $this->isJson = true;
        $this->language = 'javascript'; // JSON is highlighted as JavaScript

        return $this;
    }

    /**
     * Get the supported programming languages.
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    /**
     * Determine if this field is configured for JSON editing.
     */
    public function isJsonField(): bool
    {
        return $this->isJson;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'language' => $this->language,
            'isJson' => $this->isJson,
            'supportedLanguages' => $this->supportedLanguages,
        ]);
    }

    /**
     * Prepare the field for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'language' => $this->language,
            'isJson' => $this->isJson,
            'supportedLanguages' => $this->supportedLanguages,
        ]);
    }
}
