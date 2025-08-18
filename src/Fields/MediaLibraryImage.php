<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Media Library Image Field.
 *
 * An image upload field that integrates with Spatie Media Library for
 * professional image management with automatic conversions, responsive images,
 * and advanced image processing features.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MediaLibraryImage extends MediaLibraryField
{
    /**
     * The field's component.
     */
    public string $component = 'MediaLibraryImageField';

    /**
     * The default collection for image uploads.
     */
    public string $collection = 'images';

    /**
     * Whether downloads are disabled.
     */
    public bool $downloadDisabled = false;

    /**
     * The maximum width for image display.
     */
    public ?int $maxWidth = null;

    /**
     * The width for index view display.
     */
    public ?int $indexWidth = null;

    /**
     * The width for detail view display.
     */
    public ?int $detailWidth = null;

    /**
     * Whether to display with squared edges.
     */
    public bool $squared = false;

    /**
     * Whether to display with rounded edges.
     */
    public bool $rounded = false;

    /**
     * Custom preview URL callback.
     */
    public $previewCallback = null;

    /**
     * Custom thumbnail URL callback.
     */
    public $thumbnailCallback = null;

    /**
     * Custom download callback.
     */
    public $downloadCallback = null;

    /**
     * Create a new media library image field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Set default accepted MIME types from configuration
        $this->acceptedMimeTypes = config('admin-panel.media_library.accepted_mime_types.image', [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/svg+xml',
        ]);

        // Set default file size limit from configuration
        $this->maxFileSize = config('admin-panel.media_library.file_size_limits.image', 5120);

        // Set default conversions from configuration
        $this->conversions = config('admin-panel.media_library.default_conversions', [
            'thumb' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
            'medium' => ['width' => 500, 'height' => 500, 'fit' => 'contain'],
            'large' => ['width' => 1200, 'height' => 1200, 'quality' => 90],
        ]);

        // Enable responsive images from configuration
        $this->responsiveImages = config('admin-panel.media_library.responsive_images.enabled', true);

        // Enable cropping by default
        $this->enableCropping = true;

        // Show image dimensions by default
        $this->showImageDimensions = true;
    }

    /**
     * Disable file downloads.
     */
    public function disableDownload(bool $disabled = true): static
    {
        $this->downloadDisabled = $disabled;

        return $this;
    }

    /**
     * Set the maximum width for image display.
     */
    public function maxWidth(int $width): static
    {
        $this->maxWidth = $width;

        return $this;
    }

    /**
     * Set the width for index view display.
     */
    public function indexWidth(int $width): static
    {
        $this->indexWidth = $width;

        return $this;
    }

    /**
     * Set the width for detail view display.
     */
    public function detailWidth(int $width): static
    {
        $this->detailWidth = $width;

        return $this;
    }

    /**
     * Display image with squared edges.
     */
    public function squared(bool $squared = true): static
    {
        $this->squared = $squared;
        $this->rounded = false; // Squared and rounded are mutually exclusive

        return $this;
    }

    /**
     * Display image with rounded edges.
     */
    public function rounded(bool $rounded = true): static
    {
        $this->rounded = $rounded;
        $this->squared = false; // Squared and rounded are mutually exclusive

        return $this;
    }

    /**
     * Set custom preview URL callback.
     */
    public function preview(callable $callback): static
    {
        $this->previewCallback = $callback;

        return $this;
    }

    /**
     * Set custom thumbnail URL callback.
     */
    public function thumbnail(callable $callback): static
    {
        $this->thumbnailCallback = $callback;

        return $this;
    }

    /**
     * Set custom download callback.
     */
    public function download(callable $callback): static
    {
        $this->downloadCallback = $callback;

        return $this;
    }

    /**
     * Set accepted file types (Nova compatibility method).
     */
    public function acceptedTypes(string $types): static
    {
        // Convert Nova's acceptedTypes format to MIME types
        $typeArray = array_map('trim', explode(',', $types));
        $mimeTypes = [];

        foreach ($typeArray as $type) {
            if (str_starts_with($type, '.')) {
                // File extension - convert to MIME type
                $mimeTypes = array_merge($mimeTypes, $this->extensionToMimeTypes($type));
            } else {
                // Already a MIME type or pattern
                $mimeTypes[] = $type;
            }
        }

        $this->acceptedMimeTypes = array_unique($mimeTypes);

        return $this;
    }

    /**
     * Convert file extension to MIME types.
     */
    protected function extensionToMimeTypes(string $extension): array
    {
        $extension = ltrim($extension, '.');

        $mimeMap = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml'],
            'bmp' => ['image/bmp'],
            'tiff' => ['image/tiff'],
            'ico' => ['image/x-icon'],
        ];

        return $mimeMap[strtolower($extension)] ?? [];
    }

    /**
     * Get the thumbnail URL for the image.
     */
    public function getThumbnailUrl($media = null, string $conversion = 'thumb'): ?string
    {
        if (! $media && $this->value) {
            $media = $this->value;
        }

        // Use custom thumbnail callback if provided
        if ($this->thumbnailCallback && $media) {
            return call_user_func($this->thumbnailCallback, $media->getPath(), $this->disk);
        }

        if ($media && method_exists($media, 'getUrl')) {
            return $media->getUrl($conversion);
        }

        return null;
    }

    /**
     * Get the preview URL for the image.
     */
    public function getPreviewUrl($media = null, string $conversion = 'medium'): ?string
    {
        if (! $media && $this->value) {
            $media = $this->value;
        }

        // Use custom preview callback if provided
        if ($this->previewCallback && $media) {
            return call_user_func($this->previewCallback, $media->getPath(), $this->disk);
        }

        if ($media && method_exists($media, 'getUrl')) {
            return $media->getUrl($conversion);
        }

        return null;
    }

    /**
     * Get image metadata for display.
     */
    public function getImageMetadata($media = null): array
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
            'mime_type' => $media->mime_type ?? 'image/jpeg',
            'created_at' => $media->created_at ?? null,
            'human_readable_size' => $this->formatFileSize($media->size ?? 0),
        ];

        // Add image-specific metadata if available
        if (isset($media->custom_properties['width'])) {
            $metadata['width'] = $media->custom_properties['width'];
        }
        if (isset($media->custom_properties['height'])) {
            $metadata['height'] = $media->custom_properties['height'];
        }
        if (isset($metadata['width']) && isset($metadata['height'])) {
            $metadata['dimensions'] = $metadata['width'].' × '.$metadata['height'];
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
     * Get responsive image URLs with srcset.
     */
    public function getResponsiveImageUrls($media = null): array
    {
        if (! $media && $this->value) {
            $media = $this->value;
        }

        if (! $media || ! method_exists($media, 'getUrl')) {
            return [];
        }

        $urls = [];

        foreach ($this->conversions as $name => $config) {
            if (isset($config['width'])) {
                $urls[$config['width']] = $media->getUrl($name);
            }
        }

        return $urls;
    }

    /**
     * Generate srcset attribute for responsive images.
     */
    public function getSrcSet($media = null): string
    {
        $urls = $this->getResponsiveImageUrls($media);

        if (empty($urls)) {
            return '';
        }

        $srcset = [];
        foreach ($urls as $width => $url) {
            $srcset[] = $url.' '.$width.'w';
        }

        return implode(', ', $srcset);
    }

    /**
     * Handle file download with custom callback support.
     */
    public function handleDownload($request, $model, $disk, $path)
    {
        if ($this->downloadDisabled) {
            abort(403, 'Downloads are disabled for this field.');
        }

        if ($this->downloadCallback) {
            return call_user_func($this->downloadCallback, $request, $model, $disk, $path);
        }

        // Default download behavior using Laravel Storage
        return response()->download(
            storage_path('app/'.$disk.'/'.$path),
        );
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'downloadDisabled' => $this->downloadDisabled,
            'maxWidth' => $this->maxWidth,
            'indexWidth' => $this->indexWidth,
            'detailWidth' => $this->detailWidth,
            'squared' => $this->squared,
            'rounded' => $this->rounded,
            'hasPreviewCallback' => $this->previewCallback !== null,
            'hasThumbnailCallback' => $this->thumbnailCallback !== null,
            'hasDownloadCallback' => $this->downloadCallback !== null,
        ]);
    }
}
