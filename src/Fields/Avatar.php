<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Avatar Field.
 *
 * A user avatar field that extends Image with special display features.
 * Optimized for user profile pictures with squared/rounded display options.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Avatar extends Image
{
    /**
     * The field's component.
     */
    public string $component = 'AvatarField';

    /**
     * The default path for avatar uploads.
     */
    public string $path = 'avatars';

    /**
     * Whether the avatar should be displayed as rounded.
     */
    public bool $rounded = false;

    /**
     * The size of the avatar display.
     */
    public int $size = 80;

    /**
     * Whether to show the avatar in index views.
     */
    public bool $showInIndex = false;

    /**
     * Create a new avatar field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Avatars are squared by default
        $this->squared = true;

        // Set default accepted types for avatars (more restrictive than general images)
        $this->acceptedTypes = 'image/jpeg,image/jpg,image/png,image/webp,.jpg,.jpeg,.png,.webp';

        // Set reasonable defaults for avatars
        $this->width = 400;
        $this->height = 400;
        $this->quality = 85;
    }

    /**
     * Set the avatar to be displayed as rounded.
     */
    public function rounded(bool $rounded = true): static
    {
        $this->rounded = $rounded;

        // If rounded, disable squared
        if ($rounded) {
            $this->squared = false;
        }

        return $this;
    }

    /**
     * Set the avatar to be displayed as squared.
     */
    public function squared(bool $squared = true): static
    {
        parent::squared($squared);

        // If squared, disable rounded
        if ($squared) {
            $this->rounded = false;
        }

        return $this;
    }

    /**
     * Set the size of the avatar display.
     */
    public function size(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Set whether to show the avatar in index views.
     */
    public function showInIndex(bool $showInIndex = true): static
    {
        $this->showInIndex = $showInIndex;

        return $this;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'rounded' => $this->rounded,
            'size' => $this->size,
            'showInIndex' => $this->showInIndex,
        ]);
    }
}
