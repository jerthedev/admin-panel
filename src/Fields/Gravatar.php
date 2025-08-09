<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Gravatar Field.
 *
 * A field for displaying Gravatar avatars based on email addresses.
 * Supports various Gravatar options like size, default fallback, and rating.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Gravatar extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'GravatarField';

    /**
     * The email attribute to use for Gravatar generation.
     */
    public ?string $emailAttribute = null;

    /**
     * The size of the Gravatar image.
     */
    public int $size = 80;

    /**
     * The default fallback for Gravatar.
     */
    public string $defaultFallback = 'mp';

    /**
     * The rating for Gravatar images.
     */
    public string $rating = 'g';

    /**
     * Whether the Gravatar should be displayed as squared.
     */
    public bool $squared = false;

    /**
     * Whether the Gravatar should be displayed as rounded.
     */
    public bool $rounded = true;

    /**
     * Create a new Gravatar field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Gravatars are rounded by default
        $this->rounded = true;
    }

    /**
     * Set the email attribute to use for Gravatar generation.
     */
    public function fromEmail(string $emailAttribute): static
    {
        $this->emailAttribute = $emailAttribute;

        return $this;
    }

    /**
     * Set the size of the Gravatar image.
     */
    public function size(int $size): static
    {
        $this->size = max(1, min(2048, $size)); // Gravatar supports 1-2048px

        return $this;
    }

    /**
     * Set the default fallback for Gravatar.
     *
     * Options: 404, mp, identicon, monsterid, wavatar, retro, robohash, blank
     */
    public function defaultImage(string $defaultFallback): static
    {
        $this->defaultFallback = $defaultFallback;

        return $this;
    }

    /**
     * Set the rating for Gravatar images.
     *
     * Options: g, pg, r, x
     */
    public function rating(string $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * Set the Gravatar to be displayed as squared.
     */
    public function squared(bool $squared = true): static
    {
        $this->squared = $squared;

        // If squared, disable rounded
        if ($squared) {
            $this->rounded = false;
        }

        return $this;
    }

    /**
     * Set the Gravatar to be displayed as rounded.
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
     * Generate a Gravatar URL for the given email.
     */
    public function generateGravatarUrl(string $email): string
    {
        $hash = md5(strtolower(trim($email)));

        $params = [
            's' => $this->size,
            'd' => $this->defaultFallback,
            'r' => $this->rating,
        ];

        $queryString = http_build_query($params);

        return "https://www.gravatar.com/avatar/{$hash}?{$queryString}";
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        // If we have an email attribute, generate the Gravatar URL
        if ($this->emailAttribute && isset($resource->{$this->emailAttribute})) {
            $email = $resource->{$this->emailAttribute};
            if ($email) {
                $this->value = $this->generateGravatarUrl($email);
            }
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        // Gravatar fields don't fill the model directly
        // They are computed from the email field
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        }
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'emailAttribute' => $this->emailAttribute,
            'size' => $this->size,
            'defaultFallback' => $this->defaultFallback,
            'rating' => $this->rating,
            'squared' => $this->squared,
            'rounded' => $this->rounded,
        ]);
    }
}
