<?php

namespace JTD\AdminPanel\Tests\Fixtures;

use JTD\AdminPanel\Dashboards\Dashboard;

/**
 * Main Dashboard fixture for testing.
 */
class MainDashboard extends Dashboard
{
    /**
     * Get the dashboard's unique identifier.
     */
    public function uriKey(): string
    {
        return 'main';
    }

    /**
     * Get the dashboard's display name.
     */
    public function name(): string
    {
        return 'Main Dashboard';
    }

    /**
     * Get the dashboard's description.
     */
    public function description(): string
    {
        return 'The main dashboard for the admin panel.';
    }

    /**
     * Get the cards for this dashboard.
     */
    public function cards(): array
    {
        return [];
    }
}
