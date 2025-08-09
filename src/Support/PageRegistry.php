<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Support\Collection;
use JTD\AdminPanel\Pages\Page;
use ReflectionClass;

/**
 * Page Registry
 *
 * Manages registration and validation of admin panel custom pages.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Support
 */
class PageRegistry
{
    /**
     * The registered pages.
     *
     * @var array
     */
    protected array $pages = [];

    /**
     * Register multiple pages.
     *
     * @param array $pages
     * @return static
     * @throws \InvalidArgumentException
     */
    public function register(array $pages): static
    {
        foreach ($pages as $page) {
            $this->validatePageClass($page);
            $this->pages[] = $page;
        }

        return $this;
    }

    /**
     * Register a single page.
     *
     * @param string $page
     * @return static
     * @throws \InvalidArgumentException
     */
    public function page(string $page): static
    {
        $this->validatePageClass($page);
        $this->pages[] = $page;

        return $this;
    }

    /**
     * Get all registered pages.
     *
     * @return Collection
     */
    public function getPages(): Collection
    {
        return collect($this->pages);
    }

    /**
     * Get page instances.
     *
     * @return Collection
     */
    public function getPageInstances(): Collection
    {
        return $this->getPages()->map(function (string $pageClass) {
            return new $pageClass();
        });
    }

    /**
     * Get pages grouped by their menu group.
     *
     * @return Collection
     */
    public function getGroupedPages(): Collection
    {
        return $this->getPageInstances()
            ->groupBy(function (Page $page) {
                return $page::group() ?? 'Default';
            })
            ->map(function (Collection $groupPages) {
                // Sort pages alphabetically within each group
                return $groupPages->sortBy(function (Page $page) {
                    return $page::label();
                })->values();
            });
    }

    /**
     * Find a page by its route name.
     *
     * @param string $routeName
     * @return Page|null
     */
    public function findByRouteName(string $routeName): ?Page
    {
        $pageInstance = $this->getPageInstances()->first(function (Page $page) use ($routeName) {
            return $page::routeName() === $routeName;
        });

        return $pageInstance;
    }

    /**
     * Clear all registered pages.
     *
     * @return static
     */
    public function clear(): static
    {
        $this->pages = [];

        return $this;
    }

    /**
     * Validate that a class is a valid page class.
     *
     * @param string $pageClass
     * @throws \InvalidArgumentException
     */
    protected function validatePageClass(string $pageClass): void
    {
        if (!class_exists($pageClass)) {
            throw new \InvalidArgumentException(
                "Page class {$pageClass} does not exist."
            );
        }

        try {
            $reflection = new ReflectionClass($pageClass);

            if ($reflection->isAbstract()) {
                throw new \InvalidArgumentException(
                    "Page class {$pageClass} cannot be abstract."
                );
            }

            if (!$reflection->isSubclassOf(Page::class)) {
                throw new \InvalidArgumentException(
                    "Page class {$pageClass} must extend JTD\\AdminPanel\\Pages\\Page."
                );
            }

            // Validate that the page has required properties
            $pageClass::validate();
        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException(
                "Invalid page class {$pageClass}: " . $e->getMessage()
            );
        }
    }
}
