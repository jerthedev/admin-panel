<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * URL Field
 * 
 * A URL input field with validation, clickable display, and protocol handling.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class URL extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'URLField';

    /**
     * Whether the URL should be clickable in display mode.
     */
    public bool $clickable = false;

    /**
     * The target attribute for the link.
     */
    public string $target = '_self';

    /**
     * The text to display for the link.
     */
    public ?string $linkText = null;

    /**
     * The callback to generate link text.
     */
    public $linkTextCallback = null;

    /**
     * Whether to show favicon next to the URL.
     */
    public bool $showFavicon = false;

    /**
     * The default protocol to use.
     */
    public string $protocol = 'https';

    /**
     * Whether to validate the URL format.
     */
    public bool $validateUrl = true;

    /**
     * Whether to normalize the protocol.
     */
    public bool $normalizeProtocol = false;

    /**
     * Whether to show URL preview.
     */
    public bool $showPreview = false;

    /**
     * The maximum length of the URL.
     */
    public ?int $maxLength = null;

    /**
     * Make the URL clickable in display mode.
     */
    public function clickable(bool $clickable = true): static
    {
        $this->clickable = $clickable;

        return $this;
    }

    /**
     * Set the target attribute for the link.
     */
    public function target(string $target): static
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Set the text to display for the link.
     */
    public function linkText(string $text): static
    {
        $this->linkText = $text;

        return $this;
    }

    /**
     * Set a callback to generate link text.
     */
    public function linkTextUsing(callable $callback): static
    {
        $this->linkTextCallback = $callback;

        return $this;
    }

    /**
     * Show favicon next to the URL.
     */
    public function showFavicon(bool $show = true): static
    {
        $this->showFavicon = $show;

        return $this;
    }

    /**
     * Set the default protocol.
     */
    public function protocol(string $protocol): static
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * Enable URL validation.
     */
    public function validateUrl(bool $validate = true): static
    {
        $this->validateUrl = $validate;

        return $this;
    }

    /**
     * Enable protocol normalization.
     */
    public function normalizeProtocol(bool $normalize = true): static
    {
        $this->normalizeProtocol = $normalize;

        return $this;
    }

    /**
     * Show URL preview.
     */
    public function showPreview(bool $show = true): static
    {
        $this->showPreview = $show;

        return $this;
    }

    /**
     * Set the maximum length of the URL.
     */
    public function maxLength(int $length): static
    {
        $this->maxLength = $length;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        // Normalize protocol if enabled and value exists
        if ($this->value && $this->normalizeProtocol) {
            $this->value = $this->normalizeUrl($this->value);
        }
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
            
            if ($value !== null && $value !== '') {
                // Normalize the URL if needed
                if ($this->normalizeProtocol) {
                    $value = $this->normalizeUrl($value);
                }
                
                $model->{$this->attribute} = $value;
            } else {
                $model->{$this->attribute} = null;
            }
        }
    }

    /**
     * Normalize the URL by adding protocol if missing.
     */
    protected function normalizeUrl(string $url): string
    {
        if (!preg_match('/^https?:\/\//', $url)) {
            return $this->protocol . '://' . $url;
        }

        return $url;
    }

    /**
     * Get the link text for display.
     */
    public function getLinkText(?string $url = null): ?string
    {
        $url = $url ?? $this->value;

        if ($this->linkTextCallback) {
            return call_user_func($this->linkTextCallback, $url);
        }

        if ($this->linkText) {
            return $this->linkText;
        }

        // Default to showing the domain
        if ($url) {
            $parsed = parse_url($url);
            return $parsed['host'] ?? $url;
        }

        return null;
    }

    /**
     * Get the favicon URL for the given URL.
     */
    public function getFaviconUrl(?string $url = null): ?string
    {
        $url = $url ?? $this->value;

        if (!$url) {
            return null;
        }

        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
            return null;
        }

        $scheme = $parsed['scheme'] ?? 'https';
        return "{$scheme}://{$parsed['host']}/favicon.ico";
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'clickable' => $this->clickable,
            'target' => $this->target,
            'linkText' => $this->linkText,
            'showFavicon' => $this->showFavicon,
            'protocol' => $this->protocol,
            'validateUrl' => $this->validateUrl,
            'normalizeProtocol' => $this->normalizeProtocol,
            'showPreview' => $this->showPreview,
            'maxLength' => $this->maxLength,
        ]);
    }
}
