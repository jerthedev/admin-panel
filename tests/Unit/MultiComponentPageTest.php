<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Pages\Page;
use PHPUnit\Framework\TestCase;

/**
 * Multi-Component Page Test
 *
 * Tests the multi-component page architecture where pages can define
 * multiple Vue components with shared field state.
 */
class MultiComponentPageTest extends TestCase
{
    public function test_page_can_define_single_component_array(): void
    {
        $page = new class extends Page {
            public static array $components = ['UserDashboard'];
            public static ?string $title = 'Test Page';

            public function fields(Request $request): array
            {
                return [Text::make('Name')];
            }
        };

        $this->assertEquals(['UserDashboard'], $page::$components);
        $this->assertEquals('UserDashboard', $page::component()); // Primary component
    }

    public function test_page_can_define_multiple_components_array(): void
    {
        $page = new class extends Page {
            public static array $components = ['UserDashboard', 'UserSettings', 'UserMetrics'];
            public static ?string $title = 'Test Page';

            public function fields(Request $request): array
            {
                return [Text::make('Name')];
            }
        };

        $this->assertEquals(['UserDashboard', 'UserSettings', 'UserMetrics'], $page::$components);
        $this->assertEquals('UserDashboard', $page::component()); // First is primary
    }

    public function test_page_components_method_returns_all_components(): void
    {
        $page = new class extends Page {
            public static array $components = ['Main', 'Settings', 'Advanced'];
            public static ?string $title = 'Test Page';

            public function fields(Request $request): array
            {
                return [Text::make('Name')];
            }
        };

        $this->assertEquals(['Main', 'Settings', 'Advanced'], $page::components());
    }

    public function test_page_primary_component_method_returns_first_component(): void
    {
        $page = new class extends Page {
            public static array $components = ['Primary', 'Secondary', 'Tertiary'];
            public static ?string $title = 'Test Page';

            public function fields(Request $request): array
            {
                return [Text::make('Name')];
            }
        };

        $this->assertEquals('Primary', $page::primaryComponent());
    }

    public function test_page_secondary_components_method_returns_non_primary_components(): void
    {
        $page = new class extends Page {
            public static array $components = ['Primary', 'Secondary', 'Tertiary'];
            public static ?string $title = 'Test Page';

            public function fields(Request $request): array
            {
                return [Text::make('Name')];
            }
        };

        $this->assertEquals(['Secondary', 'Tertiary'], $page::secondaryComponents());
    }

    public function test_page_has_multiple_components_method(): void
    {
        $singleComponentPage = new class extends Page {
            public static array $components = ['Single'];
            public static ?string $title = 'Test Page';

            public function fields(Request $request): array
            {
                return [Text::make('Name')];
            }
        };

        $multiComponentPage = new class extends Page {
            public static array $components = ['First', 'Second'];
            public static ?string $title = 'Test Page';

            public function fields(Request $request): array
            {
                return [Text::make('Name')];
            }
        };

        $this->assertFalse($singleComponentPage::hasMultipleComponents());
        $this->assertTrue($multiComponentPage::hasMultipleComponents());
    }

    public function test_page_component_count_method(): void
    {
        $page = new class extends Page {
            public static array $components = ['One', 'Two', 'Three'];
            public static ?string $title = 'Test Page';

            public function fields(Request $request): array
            {
                return [Text::make('Name')];
            }
        };

        $this->assertEquals(3, $page::componentCount());
    }

    public function test_page_validates_components_array_not_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must define at least one component');

        $page = new class extends Page {
            public static array $components = [];
            public static ?string $title = 'Test Page';

            public function fields(Request $request): array
            {
                return [Text::make('Name')];
            }
        };

        $page::validateComponents();
    }

    public function test_page_validates_components_array_contains_strings(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All components must be strings');

        $page = new class extends Page {
            public static array $components = ['Valid', 123, 'AlsoValid'];
            public static ?string $title = 'Test Page';

            public function fields(Request $request): array
            {
                return [Text::make('Name')];
            }
        };

        $page::validateComponents();
    }

    public function test_page_validates_components_array_no_duplicates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Component names must be unique');

        $page = new class extends Page {
            public static array $components = ['Dashboard', 'Settings', 'Dashboard'];
            public static ?string $title = 'Test Page';

            public function fields(Request $request): array
            {
                return [Text::make('Name')];
            }
        };

        $page::validateComponents();
    }

    public function test_backward_compatibility_with_single_component_string(): void
    {
        // Test that existing pages with $component string still work
        $page = new class extends Page {
            public static ?string $component = 'LegacyComponent';
            public static ?string $title = 'Test Page';

            public function fields(Request $request): array
            {
                return [Text::make('Name')];
            }
        };

        // Should convert single component to array format
        $this->assertEquals(['LegacyComponent'], $page::components());
        $this->assertEquals('LegacyComponent', $page::component());
        $this->assertEquals('LegacyComponent', $page::primaryComponent());
        $this->assertFalse($page::hasMultipleComponents());
    }
}
