<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Audio Field.
 *
 * The Audio field extends the File field and accepts the same options and configuration.
 * The Audio field, unlike the File field, will display a thumbnail preview of the 
 * underlying audio when viewing the resource.
 *
 * 100% compatible with Laravel Nova Audio field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Audio extends File
{
    /**
     * The field's component.
     */
    public string $component = 'AudioField';

    /**
     * The default path for audio uploads.
     */
    public string $path = 'audio';

    /**
     * Preload constants for HTML5 audio element.
     */
    public const PRELOAD_NONE = 'none';
    public const PRELOAD_METADATA = 'metadata';
    public const PRELOAD_AUTO = 'auto';

    /**
     * Whether downloads are disabled for this audio field.
     */
    protected bool $downloadsDisabled = false;

    /**
     * The preload attribute for the HTML5 audio element.
     */
    protected string $preloadAttribute = self::PRELOAD_METADATA;

    /**
     * Create a new audio field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Set default accepted types for audio files
        $this->acceptedTypes = 'audio/mpeg,audio/wav,audio/ogg,audio/mp4,audio/aac,audio/flac,.mp3,.wav,.ogg,.m4a,.aac,.flac';
    }

    /**
     * Disable downloads for this audio field.
     *
     * @return $this
     */
    public function disableDownload(): static
    {
        $this->downloadsDisabled = true;

        return $this;
    }

    /**
     * Set the preload attribute for the HTML5 audio element.
     *
     * @param string $preload The preload value ('none', 'metadata', or 'auto')
     * @return $this
     */
    public function preload(string $preload): static
    {
        $this->preloadAttribute = $preload;

        return $this;
    }

    /**
     * Determine if downloads are disabled.
     */
    public function downloadsAreDisabled(): bool
    {
        return $this->downloadsDisabled;
    }

    /**
     * Get the preload attribute value.
     */
    public function getPreloadAttribute(): string
    {
        return $this->preloadAttribute;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'downloadsDisabled' => $this->downloadsDisabled,
            'preload' => $this->preloadAttribute,
        ]);
    }
}
