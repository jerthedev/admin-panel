<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * File Field.
 *
 * A file upload field with disk configuration, type restrictions,
 * and download functionality.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class File extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'FileField';

    /**
     * The disk where files should be stored.
     */
    public string $disk = 'public';

    /**
     * The path where files should be stored.
     */
    public string $path = 'files';

    /**
     * The accepted file types.
     */
    public ?string $acceptedTypes = null;

    /**
     * The maximum file size in KB.
     */
    public ?int $maxSize = null;

    /**
     * Whether to allow multiple file uploads.
     */
    public bool $multiple = false;

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
     * Set the disk where files should be stored.
     */
    public function disk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Set the path where files should be stored.
     */
    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Set the accepted file types.
     */
    public function acceptedTypes(string $types): static
    {
        $this->acceptedTypes = $types;

        return $this;
    }

    /**
     * Set the maximum file size in KB.
     */
    public function maxSize(int $sizeInKb): static
    {
        $this->maxSize = $sizeInKb;

        return $this;
    }

    /**
     * Allow multiple file uploads.
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
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
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        } elseif ($this->storeCallback) {
            $result = call_user_func($this->storeCallback, $request, $model, $this->attribute, $this->attribute, $this->disk, $this->path);

            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    $model->{$key} = $value;
                }
            } elseif (is_callable($result)) {
                $result();
            }
        } elseif ($request->hasFile($this->attribute)) {
            $file = $request->file($this->attribute);

            if ($file instanceof UploadedFile && $file->isValid()) {
                $path = $this->storeFile($file);
                $model->{$this->attribute} = $path;

                // Store original filename if configured
                if ($this->originalNameColumn) {
                    $model->{$this->originalNameColumn} = $file->getClientOriginalName();
                }

                // Store file size if configured
                if ($this->sizeColumn) {
                    $model->{$this->sizeColumn} = $file->getSize();
                }
            }
        } elseif ($request->exists($this->attribute) && $request->input($this->attribute) === null) {
            // Handle explicit null values (file removal)
            if ($this->deletable) {
                $this->handleFileDeletion($request, $model);
            }
        }
    }

    /**
     * Store the uploaded file.
     */
    protected function storeFile(UploadedFile $file): string
    {
        if ($this->storeAsCallback) {
            $filename = call_user_func($this->storeAsCallback, request(), null, $this->attribute, $this->attribute);

            return $file->storeAs(
                $this->path,
                $filename,
                $this->disk,
            );
        }

        $filename = $this->generateFilename($file);

        return $file->storeAs(
            $this->path,
            $filename,
            $this->disk,
        );
    }

    /**
     * Generate a unique filename for the uploaded file.
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Sanitize the basename
        $basename = Str::slug($basename);

        // Add timestamp to ensure uniqueness
        $timestamp = now()->format('Y-m-d_H-i-s');

        return "{$basename}_{$timestamp}.{$extension}";
    }

    /**
     * Handle file deletion.
     */
    protected function handleFileDeletion(Request $request, $model): void
    {
        if ($this->deleteCallback) {
            $result = call_user_func($this->deleteCallback, $request, $model, $this->disk, $model->{$this->attribute});

            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    $model->{$key} = $value;
                }
            }
        } else {
            // Default deletion behavior
            $path = $model->{$this->attribute};

            if ($path && Storage::disk($this->disk)->exists($path)) {
                Storage::disk($this->disk)->delete($path);
            }

            $model->{$this->attribute} = null;

            // Clear metadata columns if configured
            if ($this->originalNameColumn) {
                $model->{$this->originalNameColumn} = null;
            }

            if ($this->sizeColumn) {
                $model->{$this->sizeColumn} = null;
            }
        }
    }

    /**
     * Get the URL for the file.
     */
    public function getUrl(?string $path = null): ?string
    {
        $filePath = $path ?? $this->value;

        if (! $filePath) {
            return null;
        }

        return Storage::disk($this->disk)->url($filePath);
    }

    /**
     * Get the preview URL for the file.
     */
    public function getPreviewUrl(?string $path = null): ?string
    {
        if ($this->previewCallback) {
            return call_user_func($this->previewCallback, $path ?? $this->value, $this->disk);
        }

        return $this->getUrl($path);
    }

    /**
     * Get the thumbnail URL for the file.
     */
    public function getThumbnailUrl(?string $path = null): ?string
    {
        if ($this->thumbnailCallback) {
            return call_user_func($this->thumbnailCallback, $path ?? $this->value, $this->disk);
        }

        return $this->getUrl($path);
    }

    /**
     * Get the download response for the file.
     */
    public function getDownloadResponse(Request $request, $model): mixed
    {
        if ($this->downloadsDisabled) {
            return null;
        }

        if ($this->downloadCallback) {
            return call_user_func($this->downloadCallback, $request, $model, $this->disk, $model->{$this->attribute});
        }

        $path = $model->{$this->attribute};

        if (! $path || ! Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        $filename = $this->originalNameColumn && $model->{$this->originalNameColumn}
            ? $model->{$this->originalNameColumn}
            : basename($path);

        return Storage::disk($this->disk)->download($path, $filename);
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'disk' => $this->disk,
            'path' => $this->path,
            'acceptedTypes' => $this->acceptedTypes,
            'maxSize' => $this->maxSize,
            'multiple' => $this->multiple,
            'deletable' => $this->deletable,
            'prunable' => $this->prunable,
            'downloadsDisabled' => $this->downloadsDisabled,
            'originalNameColumn' => $this->originalNameColumn,
            'sizeColumn' => $this->sizeColumn,
            'previewUrl' => $this->getPreviewUrl(),
            'thumbnailUrl' => $this->getThumbnailUrl(),
        ]);
    }
}
