<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Resources\Concerns;

use JTD\AdminPanel\Observers\ResourceObserver;

/**
 * HasObservers Trait.
 *
 * Provides functionality for registering and managing model observers for resources.
 * Enables automatic observer registration and lifecycle event handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
trait HasObservers
{
    /**
     * The observers for the resource.
     */
    protected static array $resourceObservers = [];

    /**
     * Whether observers have been registered.
     */
    protected static bool $observersRegistered = false;

    /**
     * Register observers for the resource.
     */
    public static function registerObservers(): void
    {
        if (static::$observersRegistered) {
            return;
        }

        $model = static::newModel();

        foreach (static::getObservers() as $observer) {
            $model::observe($observer);
        }

        static::$observersRegistered = true;
    }

    /**
     * Get the observers for the resource.
     */
    public static function getObservers(): array
    {
        // Check if the class has a static $observers property
        $observers = property_exists(static::class, 'observers') ? static::$observers : static::$resourceObservers;

        // Auto-detect observer based on resource name
        $autoObserver = static::guessObserverClass();
        if (class_exists($autoObserver)) {
            $observers[] = $autoObserver;
        }

        return array_unique($observers);
    }

    /**
     * Add an observer to the resource.
     */
    public static function addObserver(string $observerClass): void
    {
        $observers = property_exists(static::class, 'observers') ? static::$observers : static::$resourceObservers;

        if (! in_array($observerClass, $observers)) {
            if (property_exists(static::class, 'observers')) {
                static::$observers[] = $observerClass;
            } else {
                static::$resourceObservers[] = $observerClass;
            }
        }
    }

    /**
     * Remove an observer from the resource.
     */
    public static function removeObserver(string $observerClass): void
    {
        if (property_exists(static::class, 'observers')) {
            static::$observers = array_filter(static::$observers, function ($observer) use ($observerClass) {
                return $observer !== $observerClass;
            });
        } else {
            static::$resourceObservers = array_filter(static::$resourceObservers, function ($observer) use ($observerClass) {
                return $observer !== $observerClass;
            });
        }
    }

    /**
     * Guess the observer class name based on the resource class.
     */
    protected static function guessObserverClass(): string
    {
        $resourceClass = class_basename(static::class);

        // Remove 'Resource' suffix and any test-related suffixes
        $observerClass = preg_replace('/Resource$/', '', $resourceClass);
        $observerClass = preg_replace('/WithObservers$/', '', $observerClass);
        $observerClass = preg_replace('/WithoutObservers$/', '', $observerClass);

        return "App\\Observers\\{$observerClass}Observer";
    }

    /**
     * Boot the observers for the resource.
     */
    public static function bootHasObservers(): void
    {
        static::registerObservers();
    }

    /**
     * Create a new observer instance for the resource.
     */
    public static function createObserver(?string $observerClass = null): ResourceObserver
    {
        $observerClass = $observerClass ?? static::guessObserverClass();

        if (! class_exists($observerClass)) {
            throw new \InvalidArgumentException("Observer class {$observerClass} does not exist.");
        }

        return new $observerClass;
    }

    /**
     * Check if the resource has observers.
     */
    public static function hasObservers(): bool
    {
        return ! empty(static::getObservers());
    }

    /**
     * Get observer events that are handled.
     */
    public static function getObserverEvents(): array
    {
        return [
            'creating',
            'created',
            'updating',
            'updated',
            'saving',
            'saved',
            'deleting',
            'deleted',
            'restoring',
            'restored',
            'forceDeleting',
            'forceDeleted',
            'replicating',
        ];
    }

    /**
     * Manually trigger an observer event.
     */
    public static function triggerObserverEvent(string $event, $model): void
    {
        $observers = static::getObservers();

        foreach ($observers as $observerClass) {
            $observer = new $observerClass;

            if (method_exists($observer, $event)) {
                $observer->{$event}($model);
            }
        }
    }

    /**
     * Disable observers for the resource.
     */
    public static function disableObservers(): void
    {
        $model = static::newModel();

        foreach (static::getObservers() as $observer) {
            $model::unsetEventDispatcher();
        }
    }

    /**
     * Enable observers for the resource.
     */
    public static function enableObservers(): void
    {
        static::$observersRegistered = false;
        static::registerObservers();
    }

    /**
     * Execute a callback without observers.
     */
    public static function withoutObservers(callable $callback)
    {
        $model = static::newModel();

        return $model::withoutEvents($callback);
    }

    /**
     * Get observer statistics.
     */
    public static function getObserverStats(): array
    {
        $observers = static::getObservers();
        $events = static::getObserverEvents();

        $stats = [
            'total_observers' => count($observers),
            'registered' => static::$observersRegistered,
            'events_handled' => [],
        ];

        foreach ($observers as $observerClass) {
            if (class_exists($observerClass)) {
                $observer = new $observerClass;
                $handledEvents = [];

                foreach ($events as $event) {
                    if (method_exists($observer, $event)) {
                        $handledEvents[] = $event;
                    }
                }

                $stats['events_handled'][$observerClass] = $handledEvents;
            }
        }

        return $stats;
    }
}
