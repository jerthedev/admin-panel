<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Media Library File Field.
 *
 * A file upload field that integrates with Spatie Media Library for
 * professional file management with collections, conversions, and metadata.
 *
 * This field is 100% compatible with Nova File Field API while providing
 * additional Media Library functionality.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
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
     * The callback used to handle file downloads.
     */
    public $downloadCallback;

    /**
     * The callback used to handle file storage.
     */
    public $storeCallback;

    /**
     * The callback used to handle file deletion.
     */
    public $deleteCallback;

    /**
     * The callback used to handle file previews.
     */
    public $previewCallback;

    /**
     * The callback used to handle file thumbnails.
     */
    public $thumbnailCallback;

    /**
     * The callback used to generate custom filenames.
     */
    public $storeAsCallback;

    /**
     * The column to store the original filename.
     */
    public ?string $originalNameColumn = null;

    /**
     * The column to store the file size.
     */
    public ?string $sizeColumn = null;

    /**
     * Whether the file can be deleted.
     */
    public bool $deletable = true;

    /**
     * Whether the file should be pruned when the model is deleted.
     */
    public bool $prunable = false;

    /**
     * Whether downloads are disabled.
     */
    public bool $downloadsDisabled = false;

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
     * Nova File Field Compatibility Methods.
     */

    /**
     * Set the accepted file types (Nova compatibility alias).
     */
    public function acceptedTypes(string $types): static
    {
        // Convert string format to MIME types array
        $extensions = explode(',', str_replace(' ', '', $types));
        $mimeTypes = [];

        foreach ($extensions as $ext) {
            $ext = ltrim($ext, '.');
            $mimeTypes = array_merge($mimeTypes, $this->getMimeTypesForExtension($ext));
        }

        return $this->acceptsMimeTypes(array_unique($mimeTypes));
    }

    /**
     * Set the maximum file size in KB (Nova compatibility alias).
     */
    public function maxSize(int $sizeInKb): static
    {
        return $this->maxFileSize($sizeInKb);
    }

    /**
     * Disable file downloads.
     */
    public function disableDownload(): static
    {
        $this->downloadsDisabled = true;

        return $this;
    }

    /**
     * Set the callback used to handle file downloads.
     */
    public function download(callable $callback): static
    {
        $this->downloadCallback = $callback;

        return $this;
    }

    /**
     * Set the callback used to handle file storage.
     */
    public function store(callable $callback): static
    {
        $this->storeCallback = $callback;

        return $this;
    }

    /**
     * Set the callback used to generate custom filenames.
     */
    public function storeAs(callable $callback): static
    {
        $this->storeAsCallback = $callback;

        return $this;
    }

    /**
     * Store the original filename in the specified column.
     */
    public function storeOriginalName(string $column): static
    {
        $this->originalNameColumn = $column;

        return $this;
    }

    /**
     * Store the file size in the specified column.
     */
    public function storeSize(string $column): static
    {
        $this->sizeColumn = $column;

        return $this;
    }

    /**
     * Set whether the file can be deleted.
     */
    public function deletable(bool $deletable = true): static
    {
        $this->deletable = $deletable;

        return $this;
    }

    /**
     * Set whether the file should be pruned when the model is deleted.
     */
    public function prunable(bool $prunable = true): static
    {
        $this->prunable = $prunable;

        return $this;
    }

    /**
     * Set the callback used to handle file deletion.
     */
    public function delete(callable $callback): static
    {
        $this->deleteCallback = $callback;

        return $this;
    }

    /**
     * Set the callback used to handle file previews.
     */
    public function preview(callable $callback): static
    {
        $this->previewCallback = $callback;

        return $this;
    }

    /**
     * Set the callback used to handle file thumbnails.
     */
    public function thumbnail(callable $callback): static
    {
        $this->thumbnailCallback = $callback;

        return $this;
    }

    /**
     * Nova File Field URL Methods.
     */

    /**
     * Get the URL for the file (Nova compatibility).
     */
    public function getUrl($media = null): ?string
    {
        return $this->getDownloadUrl($media);
    }

    /**
     * Get the preview URL for the file.
     */
    public function getPreviewUrl($media = null): ?string
    {
        if ($this->previewCallback) {
            $mediaObject = $media ?? $this->value;

            return call_user_func($this->previewCallback, $mediaObject, $this->disk);
        }

        return $this->getDownloadUrl($media);
    }

    /**
     * Get the thumbnail URL for the file.
     */
    public function getThumbnailUrl($media = null): ?string
    {
        if ($this->thumbnailCallback) {
            $mediaObject = $media ?? $this->value;

            return call_user_func($this->thumbnailCallback, $mediaObject, $this->disk);
        }

        return $this->getDownloadUrl($media);
    }

    /**
     * Get the download response for the file (Nova compatibility).
     */
    public function getDownloadResponse(Request $request, $model): mixed
    {
        if ($this->downloadsDisabled) {
            return null;
        }

        if ($this->downloadCallback) {
            return call_user_func($this->downloadCallback, $request, $model, $this->disk, $this->value);
        }

        // Default Media Library download behavior
        if ($this->value && method_exists($this->value, 'getUrl')) {
            $filename = $this->originalNameColumn && isset($model->{$this->originalNameColumn})
                ? $model->{$this->originalNameColumn}
                : ($this->value->name ?? $this->value->file_name ?? 'download');

            return response()->download($this->value->getPath(), $filename);
        }

        return null;
    }

    /**
     * Get the download URL for the file.
     */
    public function getDownloadUrl($media = null): ?string
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
     * Get file metadata for display.
     */
    public function getFileMetadata($media = null): array
    {
        if (! $media && $this->value) {
            $media = $this->value;
        }

        if (! $media) {
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

        return round($bytes / (1024 ** $power), 2).' '.$units[$power];
    }

    /**
     * Get MIME types for a file extension.
     */
    protected function getMimeTypesForExtension(string $extension): array
    {
        $mimeMap = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'txt' => ['text/plain'],
            'csv' => ['text/csv'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'svg' => ['image/svg+xml'],
            'mp4' => ['video/mp4'],
            'avi' => ['video/x-msvideo'],
            'mov' => ['video/quicktime'],
            'mp3' => ['audio/mpeg'],
            'wav' => ['audio/wav'],
            'ogg' => ['audio/ogg'],
        ];

        return $mimeMap[strtolower($extension)] ?? ['application/octet-stream'];
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            // Nova File Field compatibility
            'deletable' => $this->deletable,
            'prunable' => $this->prunable,
            'downloadsDisabled' => $this->downloadsDisabled,
            'originalNameColumn' => $this->originalNameColumn,
            'sizeColumn' => $this->sizeColumn,
            'previewUrl' => $this->getPreviewUrl(),
            'thumbnailUrl' => $this->getThumbnailUrl(),
            'downloadUrl' => $this->getDownloadUrl(),
        ]);
    }
}
