<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Base Media Library Field
 *
 * Abstract base class for all Media Library fields. Provides common
 * functionality for media collections, conversions, and file handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
abstract class MediaLibraryField extends Field
{
    /**
     * The media collection name.
     */
    public string $collection = 'default';

    /**
     * The disk where media should be stored.
     */
    public string $disk;

    /**
     * The accepted MIME types.
     */
    public array $acceptedMimeTypes = [];

    /**
     * The maximum file size in KB.
     */
    public ?int $maxFileSize = null;

    /**
     * Whether to allow multiple file uploads.
     */
    public bool $multiple = false;

    /**
     * Whether this is a single file field (opposite of multiple).
     */
    public bool $singleFile = true;

    /**
     * Media conversions configuration.
     */
    public array $conversions = [];

    /**
     * Whether to enable responsive images.
     */
    public bool $responsiveImages = false;

    /**
     * Whether to enable cropping interface.
     */
    public bool $enableCropping = false;

    /**
     * Maximum number of files allowed.
     */
    public ?int $limit = null;

    /**
     * Whether to show image dimensions.
     */
    public bool $showImageDimensions = false;

    /**
     * Crop aspect ratio for images.
     */
    public ?string $cropAspectRatio = null;

    /**
     * Fallback URL for missing media.
     */
    public ?string $fallbackUrl = null;

    /**
     * Fallback file path for missing media.
     */
    public ?string $fallbackPath = null;

    /**
     * Create a new media library field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Initialize default values from configuration
        $this->disk = config('admin-panel.media_library.default_disk', 'public');
    }

    /**
     * Set the media collection name.
     */
    public function collection(string $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Set the storage disk.
     */
    public function disk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Set the accepted MIME types.
     */
    public function acceptsMimeTypes(array $mimeTypes): static
    {
        $this->acceptedMimeTypes = $mimeTypes;

        return $this;
    }

    /**
     * Set the maximum file size in KB.
     */
    public function maxFileSize(int $sizeInKb): static
    {
        $this->maxFileSize = $sizeInKb;

        return $this;
    }

    /**
     * Allow multiple file uploads.
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        $this->singleFile = !$multiple;

        return $this;
    }

    /**
     * Allow only single file upload.
     */
    public function singleFile(bool $singleFile = true): static
    {
        $this->singleFile = $singleFile;
        $this->multiple = !$singleFile;

        return $this;
    }

    /**
     * Set media conversions.
     */
    public function conversions(array $conversions): static
    {
        $this->conversions = $conversions;

        return $this;
    }

    /**
     * Enable responsive images.
     */
    public function responsiveImages(bool $enabled = true): static
    {
        $this->responsiveImages = $enabled;

        return $this;
    }

    /**
     * Enable cropping interface.
     */
    public function enableCropping(bool $enabled = true): static
    {
        $this->enableCropping = $enabled;

        return $this;
    }

    /**
     * Set the maximum number of files.
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Show image dimensions.
     */
    public function showImageDimensions(bool $show = true): static
    {
        $this->showImageDimensions = $show;

        return $this;
    }

    /**
     * Set crop aspect ratio.
     */
    public function cropAspectRatio(string $ratio): static
    {
        $this->cropAspectRatio = $ratio;

        return $this;
    }

    /**
     * Set fallback URL.
     */
    public function fallbackUrl(string $url): static
    {
        $this->fallbackUrl = $url;

        return $this;
    }

    /**
     * Set fallback file path.
     */
    public function fallbackPath(string $path): static
    {
        $this->fallbackPath = $path;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        // If the resource implements HasMedia, get media from the collection
        if ($resource instanceof HasMedia) {
            $media = $resource->getMedia($this->collection);

            if ($this->singleFile) {
                $this->value = $media->first();
            } else {
                $this->value = $media;
            }
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
            return;
        }

        // Handle media library file uploads
        if ($request->hasFile($this->attribute) && $model instanceof HasMedia) {
            $files = $request->file($this->attribute);

            if (!is_array($files)) {
                $files = [$files];
            }

            // Clear existing media if single file
            if ($this->singleFile) {
                $model->clearMediaCollection($this->collection);
            }

            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $mediaAdder = $model->addMediaFromRequest($this->attribute)
                        ->toMediaCollection($this->collection, $this->disk);
                }
            }
        }
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'collection' => $this->collection,
            'disk' => $this->disk,
            'acceptedMimeTypes' => $this->acceptedMimeTypes,
            'maxFileSize' => $this->maxFileSize,
            'multiple' => $this->multiple,
            'singleFile' => $this->singleFile,
            'conversions' => $this->conversions,
            'responsiveImages' => $this->responsiveImages,
            'enableCropping' => $this->enableCropping,
            'limit' => $this->limit,
            'showImageDimensions' => $this->showImageDimensions,
            'cropAspectRatio' => $this->cropAspectRatio,
            'fallbackUrl' => $this->fallbackUrl,
            'fallbackPath' => $this->fallbackPath,
        ]);
    }
}
