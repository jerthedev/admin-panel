<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Media Library Image Field
 *
 * An image upload field that integrates with Spatie Media Library for
 * professional image management with automatic conversions, responsive images,
 * and advanced image processing features.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
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
     * Get the thumbnail URL for the image.
     */
    public function getThumbnailUrl($media = null, string $conversion = 'thumb'): ?string
    {
        if (!$media && $this->value) {
            $media = $this->value;
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
        if (!$media && $this->value) {
            $media = $this->value;
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
        if (!$media && $this->value) {
            $media = $this->value;
        }

        if (!$media) {
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
            $metadata['dimensions'] = $metadata['width'] . ' × ' . $metadata['height'];
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

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }

    /**
     * Get responsive image URLs with srcset.
     */
    public function getResponsiveImageUrls($media = null): array
    {
        if (!$media && $this->value) {
            $media = $this->value;
        }

        if (!$media || !method_exists($media, 'getUrl')) {
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
            $srcset[] = $url . ' ' . $width . 'w';
        }

        return implode(', ', $srcset);
    }
}
