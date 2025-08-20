<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Dashboards;

use Illuminate\Http\Request;
use JTD\AdminPanel\Dashboards\Main;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Main Dashboard Unit Tests.
 *
 * Tests the Main dashboard functionality with pure Nova compatibility.
 */
class MainTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_main_dashboard_can_be_instantiated(): void
    {
        $dashboard = new Main;

        $this->assertInstanceOf(Main::class, $dashboard);
    }

    public function test_main_dashboard_has_correct_name(): void
    {
        $dashboard = new Main;

        $this->assertEquals('Main', $dashboard->name());
    }

    public function test_main_dashboard_has_correct_uri_key(): void
    {
        $dashboard = new Main;

        $this->assertEquals('main', $dashboard->uriKey());
    }

    public function test_main_dashboard_is_always_authorized(): void
    {
        $dashboard = new Main;
        $request = Request::create('/');

        $this->assertTrue($dashboard->authorizedToSee($request));
    }

    public function test_main_dashboard_returns_empty_cards_by_default(): void
    {
        $dashboard = new Main;
        $cards = $dashboard->cards();

        $this->assertIsArray($cards);
        $this->assertCount(0, $cards);
    }

    public function test_main_dashboard_make_creates_instance(): void
    {
        $dashboard = Main::make();

        $this->assertInstanceOf(Main::class, $dashboard);
    }

    public function test_main_dashboard_inherits_dashboard_functionality(): void
    {
        $dashboard = new Main;
        $request = Request::create('/');

        // Test inherited methods
        $this->assertInstanceOf(\JTD\AdminPanel\Menu\MenuItem::class, $dashboard->menu($request));
        $this->assertFalse($dashboard->shouldShowRefreshButton());

        $dashboard->showRefreshButton();
        $this->assertTrue($dashboard->shouldShowRefreshButton());
    }

    public function test_main_dashboard_json_serialization(): void
    {
        $dashboard = new Main;

        $expected = [
            'name' => 'Main',
            'uriKey' => 'main',
            'showRefreshButton' => false,
        ];

        $this->assertEquals($expected, $dashboard->jsonSerialize());
        $this->assertEquals($expected, $dashboard->toArray());
    }
}
