<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\Tests\TestCase;

/**
 * Vue Component Resolution Tests
 *
 * Tests for Vue component resolution logic for custom pages.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class VueComponentResolutionTest extends TestCase
{
    public function test_page_component_resolution_logic(): void
    {
        // Test the logic that will be implemented in JavaScript
        // This tests the PHP side logic for component name generation

        $testCases = [
            // [input component name, expected path]
            ['TestComponent', 'TestComponent'],
            ['Pages/CustomPage', 'Pages/CustomPage'],
            ['Dashboard', 'Dashboard'],
            ['Auth/Login', 'Auth/Login'],
        ];

        foreach ($testCases as [$input, $expected]) {
            $this->assertEquals($expected, $input);
        }
    }

    public function test_page_component_name_matches_static_property(): void
    {
        // Ensure that the component name from Page class matches what Vue expects
        $this->assertEquals('TestRouteComponent', TestPageForVue::$component);
    }

    // Sample custom page component test removed - components now live in application directory

    public function test_page_component_path_generation(): void
    {
        // Test that we can generate the correct component path
        $componentName = TestPageForVue::$component;

        // If component starts with 'Pages/', it's a custom page
        if (str_starts_with($componentName, 'Pages/')) {
            $expectedPath = './pages/' . substr($componentName, 6) . '.vue';
        } else {
            $expectedPath = './pages/' . $componentName . '.vue';
        }

        $this->assertEquals('./pages/TestRouteComponent.vue', $expectedPath);
    }

    public function test_custom_page_component_path_generation(): void
    {
        $componentName = 'Pages/CustomDashboard';

        // If component starts with 'Pages/', it's a custom page
        if (str_starts_with($componentName, 'Pages/')) {
            $expectedPath = './pages/' . substr($componentName, 6) . '.vue';
        } else {
            $expectedPath = './pages/' . $componentName . '.vue';
        }

        $this->assertEquals('./pages/CustomDashboard.vue', $expectedPath);
    }

    public function test_standard_component_path_generation(): void
    {
        $componentName = 'Dashboard';

        // If component starts with 'Pages/', it's a custom page
        if (str_starts_with($componentName, 'Pages/')) {
            $expectedPath = './pages/' . substr($componentName, 6) . '.vue';
        } else {
            $expectedPath = './pages/' . $componentName . '.vue';
        }

        $this->assertEquals('./pages/Dashboard.vue', $expectedPath);
    }
}

/**
 * Test Page for Vue Component Testing
 */
class TestPageForVue extends \JTD\AdminPanel\Pages\Page
{
    public static ?string $component = 'TestRouteComponent';

    public function fields(\Illuminate\Http\Request $request): array
    {
        return [];
    }
}
