<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Dashboards;

use Illuminate\Http\Request;

/**
 * Main Dashboard.
 *
 * The default dashboard for the admin panel. This dashboard loads
 * cards from the configuration and provides the primary overview
 * of the application.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Main extends Dashboard
{
    /**
     * Get the cards that should be displayed on the dashboard.
     *
     * @return array<int, \JTD\AdminPanel\Cards\Card>
     */
    public function cards(): array
    {
        return [
            // Add your default cards here
            // Example:
            // new \App\Admin\Cards\UserCountCard,
            // new \App\Admin\Cards\RevenueCard,
        ];
    }

    /**
     * Get the displayable name of the dashboard.
     *
     * Nova v5 compatible return type.
     */
    public function name(): \Stringable|string
    {
        return 'Main';
    }

    /**
     * Get the URI key of the dashboard.
     */
    public function uriKey(): string
    {
        return 'main';
    }

    /**
     * Get the description of the dashboard.
     */
    public function description(): ?string
    {
        return 'Main application dashboard with overview metrics and quick actions';
    }

    /**
     * Get the icon of the dashboard.
     */
    public function icon(): ?string
    {
        return 'HomeIcon';
    }

    /**
     * Get the category of the dashboard.
     */
    public function category(): ?string
    {
        return 'Overview';
    }

    /**
     * Determine if the dashboard should be available for the given request.
     */
    public function authorizedToSee(Request $request): bool
    {
        // Main dashboard is always visible to authenticated admin users
        return true;
    }
}
