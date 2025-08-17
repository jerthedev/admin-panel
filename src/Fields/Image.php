<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Image Field.
 *
 * The Image field extends the File field and accepts the same options and configurations.
 * The Image field, unlike the File field, will display a thumbnail preview of the
 * underlying image when viewing the resource.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Image extends File
{
    /**
     * The field's component.
     */
    public string $component = 'ImageField';

    /**
     * Whether the image should be displayed as squared.
     */
    public bool $squared = false;

    /**
     * Whether the image should be displayed with rounded edges.
     */
    public bool $rounded = false;

    /**
     * Set the image to be displayed as squared.
     */
    public function squared(bool $squared = true): static
    {
        $this->squared = $squared;

        return $this;
    }

    /**
     * Set the image to be displayed with rounded edges.
     */
    public function rounded(bool $rounded = true): static
    {
        $this->rounded = $rounded;

        return $this;
    }

    /**
     * Disable downloads for this field.
     */
    public function disableDownload(): static
    {
        $this->downloadCallback = false;

        return $this;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'squared' => $this->squared,
            'rounded' => $this->rounded,
        ]);
    }
}
