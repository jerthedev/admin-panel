<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Menu;

use Countable;
use Iterator;
use JsonSerializable;

/**
 * Menu Class
 *
 * Container for menu items with append/prepend functionality.
 * Supports iteration and provides a fluent API for menu management.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Menu
 */
class Menu implements JsonSerializable, Countable, Iterator
{
    /**
     * The menu items.
     */
    protected array $items = [];

    /**
     * Current position for iterator.
     */
    protected int $position = 0;

    /**
     * Create a new menu instance.
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Append an item to the end of the menu.
     */
    public function append($item): static
    {
        $this->validateMenuItem($item);
        $this->items[] = $item;

        return $this;
    }

    /**
     * Prepend an item to the beginning of the menu.
     */
    public function prepend($item): static
    {
        $this->validateMenuItem($item);
        array_unshift($this->items, $item);

        return $this;
    }

    /**
     * Add multiple items to the menu.
     */
    public function add(array $items): static
    {
        foreach ($items as $item) {
            $this->append($item);
        }

        return $this;
    }

    /**
     * Get all menu items.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Check if the menu is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Get the menu as an array.
     */
    public function toArray(): array
    {
        return array_map(function ($item) {
            return $item->toArray();
        }, $this->items);
    }

    /**
     * Convert the menu to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Count the number of items in the menu.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Iterator: Rewind to the first item.
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Iterator: Get the current item.
     */
    public function current()
    {
        return $this->items[$this->position];
    }

    /**
     * Iterator: Get the current key.
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Iterator: Move to the next item.
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Iterator: Check if the current position is valid.
     */
    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    /**
     * Validate that the item is a MenuItem (not MenuSection or MenuGroup).
     */
    protected function validateMenuItem($item): void
    {
        if (!($item instanceof MenuItem)) {
            $type = is_object($item) ? get_class($item) : gettype($item);
            throw new \InvalidArgumentException(
                "User menu only supports MenuItem objects. Got: {$type}. " .
                "MenuSection and MenuGroup objects are not allowed in user menus."
            );
        }
    }
}
