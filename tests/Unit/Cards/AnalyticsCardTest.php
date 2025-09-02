<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Cards;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Examples\AnalyticsCard;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Unit tests for the AnalyticsCard class.
 */
class AnalyticsCardTest extends TestCase
{
    private AnalyticsCard $card;

    protected function setUp(): void
    {
        parent::setUp();
        $this->card = new AnalyticsCard;
    }

    public function test_make_creates_new_instance(): void
    {
        $card = AnalyticsCard::make();

        $this->assertInstanceOf(AnalyticsCard::class, $card);
    }

    public function test_constructor_sets_default_properties(): void
    {
        $card = new AnalyticsCard;

        $this->assertEquals('Analytics Card', $card->name());
        $this->assertEquals('analytics-card', $card->uriKey());
        $this->assertEquals('AnalyticsCardCard', $card->component());
    }

    public function test_constructor_sets_default_meta_data(): void
    {
        $card = new AnalyticsCard;
        $meta = $card->meta();

        $this->assertEquals('Analytics Overview', $meta['title']);
        $this->assertEquals('Key performance metrics and analytics data', $meta['description']);
        $this->assertEquals('chart-bar', $meta['icon']);
        $this->assertEquals('blue', $meta['color']);
        $this->assertEquals('Analytics', $meta['group']);
        $this->assertTrue($meta['refreshable']);
        $this->assertEquals(30, $meta['refreshInterval']);
        $this->assertEquals('lg', $meta['size']);
    }

    public function test_admin_only_creates_card_with_admin_authorization(): void
    {
        $card = AnalyticsCard::adminOnly();

        $this->assertInstanceOf(AnalyticsCard::class, $card);
        $this->assertNotNull($card->canSeeCallback);

        // Test with admin user
        $adminRequest = $this->createMockRequest(true);
        $this->assertTrue($card->authorize($adminRequest));

        // Test with non-admin user
        $userRequest = $this->createMockRequest(false);
        $this->assertFalse($card->authorize($userRequest));
    }

    public function test_for_role_creates_card_with_role_authorization(): void
    {
        $card = AnalyticsCard::forRole('manager');

        $this->assertInstanceOf(AnalyticsCard::class, $card);
        $this->assertNotNull($card->canSeeCallback);

        // Test with user having the role
        $managerRequest = $this->createMockRequestWithRole('manager', true);
        $this->assertTrue($card->authorize($managerRequest));

        // Test with user not having the role
        $userRequest = $this->createMockRequestWithRole('manager', false);
        $this->assertFalse($card->authorize($userRequest));
    }

    public function test_with_date_range_sets_date_range_meta(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2024-01-31';
        $card = AnalyticsCard::withDateRange($startDate, $endDate);

        $meta = $card->meta();
        $this->assertArrayHasKey('dateRange', $meta);
        $this->assertEquals($startDate, $meta['dateRange']['start']);
        $this->assertEquals($endDate, $meta['dateRange']['end']);
    }

    public function test_with_metrics_sets_selected_metrics_meta(): void
    {
        $metrics = ['users', 'pageviews', 'revenue'];
        $card = AnalyticsCard::withMetrics($metrics);

        $meta = $card->meta();
        $this->assertArrayHasKey('selectedMetrics', $meta);
        $this->assertEquals($metrics, $meta['selectedMetrics']);
    }

    public function test_data_returns_analytics_data(): void
    {
        $request = $this->createMockRequest();
        $data = $this->card->data($request);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('totalUsers', $data);
        $this->assertArrayHasKey('activeUsers', $data);
        $this->assertArrayHasKey('pageViews', $data);
        $this->assertArrayHasKey('conversionRate', $data);
        $this->assertArrayHasKey('revenue', $data);
        $this->assertArrayHasKey('topPages', $data);
        $this->assertArrayHasKey('userGrowth', $data);
        $this->assertArrayHasKey('deviceBreakdown', $data);
        $this->assertArrayHasKey('lastUpdated', $data);
    }

    public function test_meta_includes_analytics_data(): void
    {
        $meta = $this->card->meta();

        $this->assertArrayHasKey('data', $meta);
        $this->assertArrayHasKey('timestamp', $meta);
        $this->assertArrayHasKey('version', $meta);
        $this->assertArrayHasKey('features', $meta);

        $this->assertEquals('1.0', $meta['version']);
        $this->assertContains('real_time_updates', $meta['features']);
        $this->assertContains('date_range_filtering', $meta['features']);
        $this->assertContains('metric_selection', $meta['features']);
        $this->assertContains('export_capabilities', $meta['features']);
    }

    public function test_get_total_users_returns_integer(): void
    {
        $reflection = new \ReflectionClass($this->card);
        $method = $reflection->getMethod('getTotalUsers');
        $method->setAccessible(true);

        $result = $method->invoke($this->card);

        $this->assertIsInt($result);
        $this->assertEquals(15420, $result);
    }

    public function test_get_active_users_returns_integer(): void
    {
        $reflection = new \ReflectionClass($this->card);
        $method = $reflection->getMethod('getActiveUsers');
        $method->setAccessible(true);

        $result = $method->invoke($this->card);

        $this->assertIsInt($result);
        $this->assertEquals(12350, $result);
    }

    public function test_get_page_views_returns_integer(): void
    {
        $reflection = new \ReflectionClass($this->card);
        $method = $reflection->getMethod('getPageViews');
        $method->setAccessible(true);

        $result = $method->invoke($this->card);

        $this->assertIsInt($result);
        $this->assertEquals(89750, $result);
    }

    public function test_get_conversion_rate_returns_float(): void
    {
        $reflection = new \ReflectionClass($this->card);
        $method = $reflection->getMethod('getConversionRate');
        $method->setAccessible(true);

        $result = $method->invoke($this->card);

        $this->assertIsFloat($result);
        $this->assertEquals(3.2, $result);
    }

    public function test_get_revenue_returns_float(): void
    {
        $reflection = new \ReflectionClass($this->card);
        $method = $reflection->getMethod('getRevenue');
        $method->setAccessible(true);

        $result = $method->invoke($this->card);

        $this->assertIsFloat($result);
        $this->assertEquals(45230.50, $result);
    }

    public function test_get_top_pages_returns_array_with_correct_structure(): void
    {
        $reflection = new \ReflectionClass($this->card);
        $method = $reflection->getMethod('getTopPages');
        $method->setAccessible(true);

        $result = $method->invoke($this->card);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);

        foreach ($result as $page) {
            $this->assertArrayHasKey('path', $page);
            $this->assertArrayHasKey('views', $page);
            $this->assertArrayHasKey('percentage', $page);
            $this->assertIsString($page['path']);
            $this->assertIsInt($page['views']);
            $this->assertIsFloat($page['percentage']);
        }
    }

    public function test_get_user_growth_data_returns_chart_data_structure(): void
    {
        $reflection = new \ReflectionClass($this->card);
        $method = $reflection->getMethod('getUserGrowthData');
        $method->setAccessible(true);

        $result = $method->invoke($this->card);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('datasets', $result);
        $this->assertIsArray($result['labels']);
        $this->assertIsArray($result['datasets']);

        foreach ($result['datasets'] as $dataset) {
            $this->assertArrayHasKey('label', $dataset);
            $this->assertArrayHasKey('data', $dataset);
            $this->assertArrayHasKey('borderColor', $dataset);
            $this->assertArrayHasKey('backgroundColor', $dataset);
        }
    }

    public function test_get_device_breakdown_returns_array_with_correct_structure(): void
    {
        $reflection = new \ReflectionClass($this->card);
        $method = $reflection->getMethod('getDeviceBreakdown');
        $method->setAccessible(true);

        $result = $method->invoke($this->card);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        foreach ($result as $device) {
            $this->assertArrayHasKey('device', $device);
            $this->assertArrayHasKey('users', $device);
            $this->assertArrayHasKey('percentage', $device);
            $this->assertIsString($device['device']);
            $this->assertIsInt($device['users']);
            $this->assertIsFloat($device['percentage']);
        }
    }

    public function test_json_serialize_returns_complete_card_data(): void
    {
        $data = $this->card->jsonSerialize();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('uriKey', $data);
        $this->assertArrayHasKey('component', $data);
        $this->assertArrayHasKey('meta', $data);

        $this->assertEquals('Analytics Card', $data['name']);
        $this->assertEquals('analytics-card', $data['uriKey']);
        $this->assertEquals('AnalyticsCardCard', $data['component']);
    }

    /**
     * Create a mock request with admin status.
     */
    private function createMockRequest(bool $isAdmin = false): Request
    {
        $request = $this->createMock(Request::class);
        $user = (object) ['is_admin' => $isAdmin];

        $request->expects($this->any())
            ->method('user')
            ->willReturn($user);

        return $request;
    }

    /**
     * Create a mock request with role checking.
     */
    private function createMockRequestWithRole(string $role, bool $hasRole): Request
    {
        $request = $this->createMock(Request::class);

        // Create a mock user object with hasRole method
        $user = new class($hasRole)
        {
            private bool $hasRole;

            public function __construct(bool $hasRole)
            {
                $this->hasRole = $hasRole;
            }

            public function hasRole(string $role): bool
            {
                return $this->hasRole;
            }
        };

        $request->expects($this->any())
            ->method('user')
            ->willReturn($user);

        return $request;
    }
}
