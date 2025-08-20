<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Pages\Page;
use PHPUnit\Framework\TestCase;

/**
 * Multi-Component Integration Test
 *
 * Tests the integration of multi-component pages with the admin panel system.
 */
class MultiComponentIntegrationTest extends TestCase
{
    public function test_multi_component_page_integration(): void
    {
        // Create a realistic multi-component page
        $page = new class extends Page {
            public static array $components = [
                'UserDashboard',    // Primary component
                'UserSettings',     // Settings tab
                'UserMetrics',      // Metrics tab
            ];

            public static ?string $title = 'User Management';
            public static ?string $group = 'Users';
            public static ?string $icon = 'user';

            public function fields(Request $request): array
            {
                return [
                    Text::make('Name')->required(),
                    Text::make('Email')->required(),
                    Boolean::make('Active')->default(true),
                ];
            }
        };

        // Test component methods
        $this->assertEquals('UserDashboard', $page::component()); // Primary
        $this->assertEquals('UserDashboard', $page::primaryComponent());
        $this->assertEquals(['UserSettings', 'UserMetrics'], $page::secondaryComponents());
        $this->assertTrue($page::hasMultipleComponents());
        $this->assertEquals(3, $page::componentCount());

        // Test that all components are available
        $allComponents = $page::components();
        $this->assertContains('UserDashboard', $allComponents);
        $this->assertContains('UserSettings', $allComponents);
        $this->assertContains('UserMetrics', $allComponents);

        // Test validation passes
        $page::validateComponents();
        $this->assertTrue(true); // If we get here, validation passed
    }

    public function test_single_component_page_backward_compatibility(): void
    {
        // Test that legacy single component pages still work
        $page = new class extends Page {
            public static ?string $component = 'LegacyUserPage';
            public static ?string $title = 'Legacy User Page';

            public function fields(Request $request): array
            {
                return [Text::make('Name')];
            }
        };

        // Should work with new multi-component methods
        $this->assertEquals('LegacyUserPage', $page::component());
        $this->assertEquals('LegacyUserPage', $page::primaryComponent());
        $this->assertEquals(['LegacyUserPage'], $page::components());
        $this->assertEquals([], $page::secondaryComponents());
        $this->assertFalse($page::hasMultipleComponents());
        $this->assertEquals(1, $page::componentCount());

        // Test validation passes
        $page::validateComponents();
        $this->assertTrue(true); // If we get here, validation passed
    }

    public function test_page_with_mixed_component_types(): void
    {
        // Test a page that might have different types of components
        $page = new class extends Page {
            public static array $components = [
                'Main',           // Main dashboard
                'Settings',       // Settings form
                'Analytics',      // Analytics charts
                'Logs',          // Activity logs
            ];

            public static ?string $title = 'Admin Dashboard';
            public static ?string $group = 'System';

            public function fields(Request $request): array
            {
                return [
                    Text::make('System Name'),
                    Boolean::make('Maintenance Mode'),
                ];
            }
        };

        // Test component access patterns
        $components = $page::components();
        $this->assertEquals(4, count($components));
        $this->assertEquals('Main', $components[0]); // Primary

        // Test secondary components
        $secondary = $page::secondaryComponents();
        $this->assertEquals(['Settings', 'Analytics', 'Logs'], $secondary);

        // Test component queries
        $this->assertTrue($page::hasMultipleComponents());
        $this->assertEquals(4, $page::componentCount());
    }

    public function test_component_validation_in_realistic_scenarios(): void
    {
        // Test validation with realistic component names
        $page = new class extends Page {
            public static array $components = [
                'Dashboard/Overview',
                'Dashboard/Settings',
                'Dashboard/Reports',
            ];

            public static ?string $title = 'Dashboard';

            public function fields(Request $request): array
            {
                return [Text::make('Title')];
            }
        };

        // Should validate successfully with path-like component names
        $page::validateComponents();
        $this->assertTrue(true);

        // Test component access
        $this->assertEquals('Dashboard/Overview', $page::primaryComponent());
        $this->assertContains('Dashboard/Settings', $page::secondaryComponents());
    }
}
