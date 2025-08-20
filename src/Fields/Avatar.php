<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Avatar Field.
 *
 * The Avatar field extends the Image field and accepts the same options and configuration.
 * If a resource contains an Avatar field, that field will be displayed next to the
 * resource's title when the resource is displayed in search results.
 *
 * 100% compatible with Laravel Nova Avatar field API.
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
     * Create a new avatar field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Set default accepted types for avatars (more restrictive than general images)
        $this->acceptedTypes = 'image/jpeg,image/jpg,image/png,image/webp,.jpg,.jpeg,.png,.webp';

        // Set reasonable defaults for avatars
        $this->width = 400;
        $this->height = 400;
        $this->quality = 85;
    }

    /**
     * Display the image's thumbnail with squared edges.
     *
     * @param bool $squared
     * @return $this
     */
    public function squared(bool $squared = true): static
    {
        parent::squared($squared);
        return $this->withMeta(['squared' => $squared, 'rounded' => !$squared]);
    }

    /**
     * Display the image's thumbnail with fully-rounded edges.
     *
     * @return $this
     */
    public function rounded(bool $rounded = true): static
    {
        return $this->withMeta(['squared' => false, 'rounded' => $rounded]);
    }
}
