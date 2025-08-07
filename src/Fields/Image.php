<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\UploadedFile;

/**
 * Image Field
 *
 * An image upload field that extends File field with image-specific
 * functionality like thumbnails, dimensions, and quality settings.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class Image extends File
{
    /**
     * The field's component.
     */
    public string $component = 'ImageField';

    /**
     * The default path for image uploads.
     */
    public string $path = 'images';

    /**
     * Whether the image should be displayed as squared.
     */
    public bool $squared = false;

    /**
     * The callback used to generate thumbnail URLs.
     */
    public $thumbnailCallback;

    /**
     * The callback used to generate preview URLs.
     */
    public $previewCallback;

    /**
     * The desired width for image processing.
     */
    public ?int $width = null;

    /**
     * The desired height for image processing.
     */
    public ?int $height = null;

    /**
     * The image quality (0-100).
     */
    public int $quality = 90;

    /**
     * Create a new image field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Set default accepted types for images
        $this->acceptedTypes = 'image/*,.jpg,.jpeg,.png,.gif,.webp';
    }

    /**
     * Set the image to be displayed as squared.
     */
    public function squared(bool $squared = true): static
    {
        $this->squared = $squared;

        return $this;
    }

    /**
     * Set the callback used to generate thumbnail URLs.
     */
    public function thumbnail(callable $callback): static
    {
        $this->thumbnailCallback = $callback;

        return $this;
    }

    /**
     * Set the callback used to generate preview URLs.
     */
    public function preview(callable $callback): static
    {
        $this->previewCallback = $callback;

        return $this;
    }

    /**
     * Set the desired width for image processing.
     */
    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Set the desired height for image processing.
     */
    public function height(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Set the image quality (0-100).
     */
    public function quality(int $quality): static
    {
        $this->quality = max(0, min(100, $quality));

        return $this;
    }

    /**
     * Generate a unique filename for the uploaded image.
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Sanitize the basename
        $basename = \Illuminate\Support\Str::slug($basename);

        // Add timestamp to ensure uniqueness
        $timestamp = now()->format('Y-m-d_H-i-s');

        return "{$basename}_{$timestamp}.{$extension}";
    }

    /**
     * Get the thumbnail URL for the image.
     */
    public function getThumbnailUrl(?string $path = null): ?string
    {
        if ($this->thumbnailCallback) {
            return call_user_func($this->thumbnailCallback);
        }

        // Default thumbnail logic - return the main image URL
        return $this->getUrl($path);
    }

    /**
     * Get the preview URL for the image.
     */
    public function getPreviewUrl(?string $path = null): ?string
    {
        if ($this->previewCallback) {
            return call_user_func($this->previewCallback);
        }

        // Default preview logic - return the main image URL
        return $this->getUrl($path);
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'squared' => $this->squared,
            'width' => $this->width,
            'height' => $this->height,
            'quality' => $this->quality,
        ]);
    }
}
