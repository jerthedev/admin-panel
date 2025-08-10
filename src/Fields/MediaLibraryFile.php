<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Media Library File Field
 *
 * A file upload field that integrates with Spatie Media Library for
 * professional file management with collections, conversions, and metadata.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class MediaLibraryFile extends MediaLibraryField
{
    /**
     * The field's component.
     */
    public string $component = 'MediaLibraryFileField';

    /**
     * The default collection for file uploads.
     */
    public string $collection = 'files';

    /**
     * Create a new media library file field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Set default accepted MIME types from configuration
        $this->acceptedMimeTypes = config('admin-panel.media_library.accepted_mime_types.file', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'text/csv',
            'application/zip',
            'application/x-zip-compressed',
        ]);

        // Set default file size limit from configuration
        $this->maxFileSize = config('admin-panel.media_library.file_size_limits.file', 10240);
    }

    /**
     * Get the download URL for the file.
     */
    public function getDownloadUrl($media = null): ?string
    {
        if (!$media && $this->value) {
            $media = $this->value;
        }

        if ($media && method_exists($media, 'getUrl')) {
            return $media->getUrl();
        }

        return null;
    }

    /**
     * Get file metadata for display.
     */
    public function getFileMetadata($media = null): array
    {
        if (!$media && $this->value) {
            $media = $this->value;
        }

        if (!$media) {
            return [];
        }

        return [
            'name' => $media->name ?? $media->file_name ?? 'Unknown',
            'size' => $media->size ?? 0,
            'mime_type' => $media->mime_type ?? 'application/octet-stream',
            'created_at' => $media->created_at ?? null,
            'human_readable_size' => $this->formatFileSize($media->size ?? 0),
        ];
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
}
