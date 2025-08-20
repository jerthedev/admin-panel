<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Cards;

use Illuminate\Http\Request;

/**
 * Base Card Class.
 *
 * Abstract base class for all admin panel cards providing Nova-compatible
 * methods for custom cards, including withMeta(), canSee(), and other
 * essential card functionality.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
abstract class Card
{
    /**
     * The card's component name.
     */
    public string $component;

    /**
     * The card's display name.
     */
    public string $name;

    /**
     * The card's URI key.
     */
    public string $uriKey;

    /**
     * The card's metadata.
     */
    public array $meta = [];

    /**
     * The callback used to determine if the card should be displayed.
     */
    public $canSeeCallback;

    /**
     * Create a new card instance.
     */
    public function __construct()
    {
        $this->name = $this->name ?? $this->generateName();
        $this->uriKey = $this->uriKey ?? $this->generateUriKey();
        $this->component = $this->component ?? $this->generateComponent();
    }

    /**
     * Create a new card instance.
     */
    public static function make(): static
    {
        return new static;
    }

    /**
     * Get the card's display name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Set the card's display name.
     */
    public function withName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the card's component name.
     */
    public function component(): string
    {
        return $this->component;
    }

    /**
     * Set the card's component name.
     */
    public function withComponent(string $component): static
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Get the card's URI key.
     */
    public function uriKey(): string
    {
        return $this->uriKey;
    }

    /**
     * Set additional meta information for the card.
     */
    public function withMeta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    /**
     * Get additional meta information to merge with the card payload.
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * Set the callback used to determine if the card should be displayed.
     */
    public function canSee(callable $callback): static
    {
        $this->canSeeCallback = $callback;

        return $this;
    }

    /**
     * Determine if the card should be displayed for the given request.
     */
    public function authorize(Request $request): bool
    {
        if ($this->canSeeCallback) {
            return call_user_func($this->canSeeCallback, $request);
        }

        return true;
    }

    /**
     * Generate a display name from the class name.
     */
    protected function generateName(): string
    {
        $className = class_basename(static::class);

        // Convert PascalCase to Title Case
        return preg_replace('/(?<!^)([A-Z])/', ' $1', $className);
    }

    /**
     * Generate a URI key from the class name.
     */
    protected function generateUriKey(): string
    {
        $className = class_basename(static::class);

        // Convert PascalCase to kebab-case
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '-$1', $className));
    }

    /**
     * Generate a component name from the class name.
     */
    protected function generateComponent(): string
    {
        return class_basename(static::class).'Card';
    }

    /**
     * Get the card's data for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name(),
            'component' => $this->component(),
            'uriKey' => $this->uriKey(),
            'meta' => $this->meta(),
        ];
    }
}
