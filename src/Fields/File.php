<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * File Field
 * 
 * A file upload field with disk configuration, type restrictions,
 * and download functionality.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
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
     * Set the callback used to handle file downloads.
     */
    public function download(callable $callback): static
    {
        $this->downloadCallback = $callback;

        return $this;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        } elseif ($request->hasFile($this->attribute)) {
            $file = $request->file($this->attribute);
            
            if ($file instanceof UploadedFile && $file->isValid()) {
                $path = $this->storeFile($file);
                $model->{$this->attribute} = $path;
            }
        } elseif ($request->exists($this->attribute) && $request->input($this->attribute) === null) {
            // Handle explicit null values (file removal)
            // Don't change the model value to preserve existing files
        }
    }

    /**
     * Store the uploaded file.
     */
    protected function storeFile(UploadedFile $file): string
    {
        $filename = $this->generateFilename($file);
        
        return $file->storeAs(
            $this->path,
            $filename,
            $this->disk
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
        ]);
    }
}
