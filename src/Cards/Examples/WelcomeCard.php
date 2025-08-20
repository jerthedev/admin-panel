<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Cards\Examples;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;

/**
 * Welcome Card Example.
 *
 * Example implementation of the Card base class demonstrating
 * Nova-compatible card functionality with custom meta data
 * and authorization logic.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class WelcomeCard extends Card
{
    /**
     * Create a new Welcome card instance.
     */
    public function __construct()
    {
        parent::__construct();

        // Set custom meta data for the card
        $this->withMeta([
            'title' => 'Welcome to Admin Panel',
            'description' => 'Get started with your dashboard',
            'icon' => 'hand-wave',
            'color' => 'blue',
            'refreshable' => false,
        ]);
    }

    /**
     * Create a new Welcome card with admin-only access.
     */
    public static function adminOnly(): static
    {
        return static::make()->canSee(function (Request $request) {
            // Example: Only show to admin users
            return $request->user()?->is_admin ?? false;
        });
    }

    /**
     * Create a new Welcome card with custom greeting.
     */
    public static function withGreeting(string $greeting): static
    {
        return static::make()->withMeta([
            'greeting' => $greeting,
        ]);
    }
}
