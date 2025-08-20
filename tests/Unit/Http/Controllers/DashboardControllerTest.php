<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Response;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Http\Controllers\DashboardController;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;
use Mockery;

/**
 * Unit tests for DashboardController.
 */
class DashboardControllerTest extends TestCase
{
    private DashboardController $controller;
    private Dashboard $mockDashboard;
    private Request $mockRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new DashboardController();
        $this->mockDashboard = Mockery::mock(Dashboard::class);
        $this->mockRequest = Mockery::mock(Request::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_cards_returns_empty_array_when_no_cards_configured(): void
    {
        // Mock dashboard to return empty cards array
        $this->mockDashboard->shouldReceive('cards')->once()->andReturn([]);

        $result = $this->invokeGetCards();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_cards_handles_card_authorization_errors(): void
    {
        // Create a mock card that throws an exception during authorization
        $mockCard = Mockery::mock('JTD\AdminPanel\Cards\Card');
        $mockCard->shouldReceive('authorize')->once()->andThrow(new \Exception('Authorization error'));

        // Mock dashboard to return the problematic card
        $this->mockDashboard->shouldReceive('cards')->once()->andReturn([$mockCard]);

        $result = $this->invokeGetCards();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_cards_method_exists_and_has_correct_signature(): void
    {
        $this->assertTrue(method_exists($this->controller, 'getCards'));

        $reflection = new \ReflectionMethod($this->controller, 'getCards');
        $this->assertTrue($reflection->isProtected());
        $this->assertEquals('array', $reflection->getReturnType()->getName());

        // Verify method parameters
        $parameters = $reflection->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('dashboard', $parameters[0]->getName());
        $this->assertEquals('request', $parameters[1]->getName());
    }

    public function test_index_method_has_correct_signature(): void
    {
        $this->assertTrue(method_exists($this->controller, 'index'));

        $reflection = new \ReflectionMethod($this->controller, 'index');
        $this->assertEquals('Inertia\Response', $reflection->getReturnType()->getName());

        // Verify method parameters
        $parameters = $reflection->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('request', $parameters[0]->getName());
    }

    /**
     * Helper method to invoke the protected getCards method.
     */
    private function invokeGetCards(): array
    {
        $reflection = new \ReflectionMethod($this->controller, 'getCards');
        $reflection->setAccessible(true);

        return $reflection->invoke($this->controller, $this->mockDashboard, $this->mockRequest);
    }
}
