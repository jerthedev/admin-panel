<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Code Field
 * 
 * A code editor field with syntax highlighting, language support,
 * and customizable themes.
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
    public string $language = 'text';

    /**
     * The editor theme.
     */
    public string $theme = 'light';

    /**
     * Whether to show line numbers.
     */
    public bool $showLineNumbers = false;

    /**
     * The height of the editor in pixels.
     */
    public int $height = 200;

    /**
     * Whether the editor is read-only.
     */
    public bool $readOnly = false;

    /**
     * Whether to wrap long lines.
     */
    public bool $wrapLines = false;

    /**
     * Whether to auto-detect the language.
     */
    public bool $autoDetectLanguage = false;

    /**
     * Supported programming languages.
     */
    protected array $supportedLanguages = [
        'text', 'php', 'javascript', 'typescript', 'python', 'java', 'c', 'cpp',
        'csharp', 'ruby', 'go', 'rust', 'swift', 'kotlin', 'scala', 'sql',
        'html', 'css', 'scss', 'less', 'json', 'xml', 'yaml', 'markdown',
        'bash', 'shell', 'powershell', 'dockerfile', 'nginx', 'apache'
    ];

    /**
     * Set the programming language.
     */
    public function language(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Set the editor theme.
     */
    public function theme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Show line numbers.
     */
    public function lineNumbers(bool $show = true): static
    {
        $this->showLineNumbers = $show;

        return $this;
    }

    /**
     * Set the editor height.
     */
    public function height(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Make the editor read-only.
     */
    public function readOnly(bool $readOnly = true): static
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    /**
     * Enable line wrapping.
     */
    public function wrapLines(bool $wrap = true): static
    {
        $this->wrapLines = $wrap;

        return $this;
    }

    /**
     * Enable auto language detection.
     */
    public function autoDetectLanguage(bool $autoDetect = true): static
    {
        $this->autoDetectLanguage = $autoDetect;

        return $this;
    }

    /**
     * Get supported languages.
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'language' => $this->language,
            'theme' => $this->theme,
            'showLineNumbers' => $this->showLineNumbers,
            'height' => $this->height,
            'readOnly' => $this->readOnly,
            'wrapLines' => $this->wrapLines,
            'autoDetectLanguage' => $this->autoDetectLanguage,
            'supportedLanguages' => $this->supportedLanguages,
        ]);
    }
}
