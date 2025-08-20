<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Dashboards;

use Illuminate\Http\Request;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Tests\TestCase;
use Mockery;

/**
 * Dashboard Unit Tests.
 *
 * Tests the abstract Dashboard base class functionality including
 * authorization, menu generation, and Nova API compatibility.
 */
class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dashboard_can_be_instantiated(): void
    {
        $dashboard = $this->createConcreteDashboard();

        $this->assertInstanceOf(Dashboard::class, $dashboard);
    }

    public function test_dashboard_has_default_name(): void
    {
        $dashboard = $this->createConcreteDashboard();

        $this->assertEquals('Test Dashboard', $dashboard->name());
    }

    public function test_dashboard_has_default_uri_key(): void
    {
        $dashboard = $this->createConcreteDashboard();

        $this->assertEquals('test-dashboard', $dashboard->uriKey());
    }

    public function test_dashboard_is_authorized_by_default(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $this->assertTrue($dashboard->authorizedToSee($request));
    }

    public function test_dashboard_can_set_authorization_callback(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $dashboard->canSee(function (Request $req) {
            return false;
        });

        $this->assertFalse($dashboard->authorizedToSee($request));
    }

    public function test_dashboard_can_see_when_uses_gate(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        // Mock user with can method
        $user = Mockery::mock();
        $user->shouldReceive('can')
            ->with('view-dashboard')
            ->andReturn(true);

        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $dashboard->canSeeWhen('view-dashboard');

        $this->assertTrue($dashboard->authorizedToSee($request));
    }

    public function test_dashboard_can_see_when_fails_without_user(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $request->setUserResolver(function () {
            return null;
        });

        $dashboard->canSeeWhen('view-dashboard');

        $this->assertFalse($dashboard->authorizedToSee($request));
    }

    public function test_dashboard_menu_returns_menu_item(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $menuItem = $dashboard->menu($request);

        $this->assertInstanceOf(MenuItem::class, $menuItem);
        $this->assertEquals('Test Dashboard', $menuItem->label);
        $this->assertEquals(route('admin-panel.dashboards.show', ['uriKey' => 'test-dashboard']), $menuItem->url);
    }

    public function test_dashboard_show_refresh_button(): void
    {
        $dashboard = $this->createConcreteDashboard();

        $this->assertFalse($dashboard->shouldShowRefreshButton());

        $result = $dashboard->showRefreshButton();

        $this->assertSame($dashboard, $result); // Test fluent interface
        $this->assertTrue($dashboard->shouldShowRefreshButton());
    }

    public function test_dashboard_make_creates_instance(): void
    {
        $dashboard = TestDashboard::make();

        $this->assertInstanceOf(TestDashboard::class, $dashboard);
    }

    public function test_dashboard_json_serialization(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $dashboard->showRefreshButton();

        $expected = [
            'name' => 'Test Dashboard',
            'uriKey' => 'test-dashboard',
            'showRefreshButton' => true,
        ];

        $this->assertEquals($expected, $dashboard->jsonSerialize());
        $this->assertEquals($expected, $dashboard->toArray());
    }

    public function test_dashboard_cards_method_is_abstract(): void
    {
        $reflection = new \ReflectionClass(Dashboard::class);
        $method = $reflection->getMethod('cards');

        $this->assertTrue($method->isAbstract());
    }

    public function test_dashboard_authorization_callback_receives_request(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/test');
        $callbackRequest = null;

        $dashboard->canSee(function (Request $req) use (&$callbackRequest) {
            $callbackRequest = $req;

            return true;
        });

        $dashboard->authorizedToSee($request);

        $this->assertSame($request, $callbackRequest);
    }

    public function test_dashboard_can_see_when_with_arguments(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $user = Mockery::mock();
        $user->shouldReceive('can')
            ->with('view-dashboard', 'arg1', 'arg2')
            ->andReturn(true);

        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $dashboard->canSeeWhen('view-dashboard', ['arg1', 'arg2']);

        $this->assertTrue($dashboard->authorizedToSee($request));
    }

    /**
     * Create a concrete dashboard instance for testing.
     */
    protected function createConcreteDashboard(): TestDashboard
    {
        return new TestDashboard;
    }
}

/**
 * Concrete dashboard class for testing.
 */
class TestDashboard extends Dashboard
{
    public function cards(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'Test Dashboard';
    }

    public function uriKey(): string
    {
        return 'test-dashboard';
    }
}
