<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Media Library Audio Field.
 *
 * An audio upload field that integrates with Spatie Media Library for
 * professional audio management with collections, conversions, and metadata.
 * 100% compatible with Laravel Nova Audio field API with additional Media Library features.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MediaLibraryAudioField extends MediaLibraryField
{
    /**
     * The field's component.
     */
    public string $component = 'MediaLibraryAudioField';

    /**
     * The default collection for audio uploads.
     */
    public string $collection = 'audio';

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
     * Create a new media library audio field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Set default accepted MIME types for audio files
        $this->acceptedMimeTypes = config('admin-panel.media_library.accepted_mime_types.audio', [
            'audio/mpeg',
            'audio/wav',
            'audio/ogg',
            'audio/mp4',
            'audio/aac',
            'audio/flac',
            'audio/x-wav',
            'audio/x-m4a',
        ]);

        // Set default file size limit for audio files
        $this->maxFileSize = config('admin-panel.media_library.file_size_limits.audio', 51200); // 50MB

        // Audio files are typically single files by default
        $this->singleFile = true;
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
     *
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
     * Get the audio URL for playback.
     */
    public function getAudioUrl($media = null): ?string
    {
        if (! $media && $this->value) {
            $media = $this->value;
        }

        if ($media && method_exists($media, 'getUrl')) {
            return $media->getUrl();
        }

        return null;
    }

    /**
     * Get audio metadata for display.
     */
    public function getAudioMetadata($media = null): array
    {
        if (! $media && $this->value) {
            $media = $this->value;
        }

        if (! $media) {
            return [];
        }

        $metadata = [
            'name' => $media->name ?? $media->file_name ?? 'Unknown',
            'size' => $media->size ?? 0,
            'mime_type' => $media->mime_type ?? 'audio/mpeg',
            'created_at' => $media->created_at ?? null,
            'human_readable_size' => $this->formatFileSize($media->size ?? 0),
        ];

        // Add audio-specific metadata if available
        if (isset($media->custom_properties['duration'])) {
            $metadata['duration'] = $media->custom_properties['duration'];
            $metadata['formatted_duration'] = $this->formatDuration($media->custom_properties['duration']);
        }

        if (isset($media->custom_properties['bitrate'])) {
            $metadata['bitrate'] = $media->custom_properties['bitrate'];
        }

        if (isset($media->custom_properties['sample_rate'])) {
            $metadata['sample_rate'] = $media->custom_properties['sample_rate'];
        }

        return $metadata;
    }

    /**
     * Format file size in human readable format.
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2).' '.$units[$power];
    }

    /**
     * Format duration in human readable format.
     */
    protected function formatDuration(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'downloadsDisabled' => $this->downloadsDisabled,
            'preload' => $this->preloadAttribute,
            'audioUrl' => $this->getAudioUrl(),
            'audioMetadata' => $this->getAudioMetadata(),
        ]);
    }
}
