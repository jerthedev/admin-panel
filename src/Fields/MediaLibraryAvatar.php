<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Media Library Avatar Field.
 *
 * A specialized image upload field for user avatars that integrates with
 * Spatie Media Library. Provides single file upload with automatic conversions,
 * aspect ratio enforcement, and fallback support.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MediaLibraryAvatar extends MediaLibraryField
{
    /**
     * The field's component.
     */
    public string $component = 'MediaLibraryAvatarField';

    /**
     * The default collection for avatar uploads.
     */
    public string $collection = 'avatars';

    /**
     * Create a new media library avatar field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Avatars are always single file
        $this->singleFile = true;
        $this->multiple = false;

        // Set default accepted MIME types from configuration (more restrictive for avatars)
        $this->acceptedMimeTypes = config('admin-panel.media_library.accepted_mime_types.avatar', [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
        ]);

        // Set default file size limit from configuration
        $this->maxFileSize = config('admin-panel.media_library.file_size_limits.avatar', 2048);

        // Set default conversions optimized for avatars from configuration
        $this->conversions = config('admin-panel.media_library.avatar_conversions', [
            'thumb' => ['width' => 64, 'height' => 64, 'fit' => 'crop'],
            'medium' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
            'large' => ['width' => 400, 'height' => 400, 'fit' => 'crop'],
        ]);

        // Enable cropping with 1:1 aspect ratio by default
        $this->enableCropping = true;
        $this->cropAspectRatio = '1:1';

        // Set default fallback URL
        $this->fallbackUrl = '/images/default-avatar.png';
    }

    /**
     * Get the avatar URL with fallback support.
     */
    public function getAvatarUrl($media = null, string $conversion = 'medium'): string
    {
        if (! $media && $this->value) {
            $media = $this->value;
        }

        if ($media && method_exists($media, 'getUrl')) {
            return $media->getUrl($conversion);
        }

        return $this->fallbackUrl ?? '/images/default-avatar.png';
    }

    /**
     * Get the thumbnail avatar URL.
     */
    public function getThumbnailUrl($media = null): string
    {
        return $this->getAvatarUrl($media, 'thumb');
    }

    /**
     * Get the large avatar URL.
     */
    public function getLargeUrl($media = null): string
    {
        return $this->getAvatarUrl($media, 'large');
    }

    /**
     * Get avatar metadata for display.
     */
    public function getAvatarMetadata($media = null): array
    {
        if (! $media && $this->value) {
            $media = $this->value;
        }

        if (! $media) {
            return [
                'has_avatar' => false,
                'fallback_url' => $this->fallbackUrl,
                'urls' => [
                    'thumb' => $this->fallbackUrl,
                    'medium' => $this->fallbackUrl,
                    'large' => $this->fallbackUrl,
                ],
            ];
        }

        $metadata = [
            'has_avatar' => true,
            'name' => $media->name ?? $media->file_name ?? 'Avatar',
            'size' => $media->size ?? 0,
            'mime_type' => $media->mime_type ?? 'image/jpeg',
            'created_at' => $media->created_at ?? null,
            'human_readable_size' => $this->formatFileSize($media->size ?? 0),
            'urls' => [
                'thumb' => $this->getThumbnailUrl($media),
                'medium' => $this->getAvatarUrl($media, 'medium'),
                'large' => $this->getLargeUrl($media),
            ],
        ];

        // Add image dimensions if available
        if (isset($media->custom_properties['width'])) {
            $metadata['width'] = $media->custom_properties['width'];
        }
        if (isset($media->custom_properties['height'])) {
            $metadata['height'] = $media->custom_properties['height'];
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
     * Check if the avatar exists (not using fallback).
     */
    public function hasAvatar($media = null): bool
    {
        if (! $media && $this->value) {
            $media = $this->value;
        }

        return $media !== null && method_exists($media, 'getUrl');
    }

    /**
     * Get all available avatar sizes.
     */
    public function getAvatarSizes($media = null): array
    {
        $sizes = [];

        foreach ($this->conversions as $name => $config) {
            $sizes[$name] = [
                'width' => $config['width'] ?? null,
                'height' => $config['height'] ?? null,
                'url' => $this->getAvatarUrl($media, $name),
            ];
        }

        return $sizes;
    }

    /**
     * Display the avatar with squared edges (Nova Avatar field compatibility).
     */
    public function squared(bool $squared = true): static
    {
        return $this->withMeta(['squared' => $squared, 'rounded' => ! $squared]);
    }

    /**
     * Display the avatar with fully-rounded edges (Nova Avatar field compatibility).
     */
    public function rounded(): static
    {
        return $this->withMeta(['squared' => false, 'rounded' => true]);
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'avatarMetadata' => $this->getAvatarMetadata(),
            'avatarSizes' => $this->getAvatarSizes(),
            'hasAvatar' => $this->hasAvatar(),
        ]);
    }
}
