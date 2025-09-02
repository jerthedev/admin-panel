<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Feature;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Examples\AnalyticsCard;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Analytics Card Integration Tests.
 *
 * Test the AnalyticsCard integration with the admin panel system
 * including card discovery, registration, and API endpoints.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class AnalyticsCardIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear any cached cards
        app(AdminPanel::class)->clearCardCache();
    }

    public function test_analytics_card_can_be_instantiated(): void
    {
        $card = new AnalyticsCard;

        // Verify the card is properly instantiated
        $this->assertInstanceOf(AnalyticsCard::class, $card);
        $this->assertEquals('Analytics Card', $card->name());
        $this->assertEquals('analytics-card', $card->uriKey());
        $this->assertEquals('AnalyticsCardCard', $card->component());
    }

    public function test_analytics_card_returns_proper_json_structure(): void
    {
        $card = new AnalyticsCard;
        $json = $card->jsonSerialize();

        // Verify basic card structure
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('uriKey', $json);
        $this->assertArrayHasKey('component', $json);
        $this->assertArrayHasKey('meta', $json);

        // Verify card identification
        $this->assertEquals('Analytics Card', $json['name']);
        $this->assertEquals('analytics-card', $json['uriKey']);
        $this->assertEquals('AnalyticsCardCard', $json['component']);

        // Verify meta structure
        $meta = $json['meta'];
        $this->assertArrayHasKey('title', $meta);
        $this->assertArrayHasKey('description', $meta);
        $this->assertArrayHasKey('data', $meta);
        $this->assertArrayHasKey('timestamp', $meta);
        $this->assertArrayHasKey('version', $meta);
        $this->assertArrayHasKey('features', $meta);

        // Verify analytics data structure
        $data = $meta['data'];
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

    public function test_analytics_card_data_method_returns_complete_analytics(): void
    {
        $card = new AnalyticsCard;
        $request = new Request;
        $data = $card->data($request);

        // Verify all required analytics data is present
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

        // Verify data types
        $this->assertIsInt($data['totalUsers']);
        $this->assertIsInt($data['activeUsers']);
        $this->assertIsInt($data['pageViews']);
        $this->assertIsFloat($data['conversionRate']);
        $this->assertIsFloat($data['revenue']);
        $this->assertIsArray($data['topPages']);
        $this->assertIsArray($data['userGrowth']);
        $this->assertIsArray($data['deviceBreakdown']);
        $this->assertIsString($data['lastUpdated']);

        // Verify expected mock values
        $this->assertEquals(15420, $data['totalUsers']);
        $this->assertEquals(12350, $data['activeUsers']);
        $this->assertEquals(89750, $data['pageViews']);
        $this->assertEquals(3.2, $data['conversionRate']);
        $this->assertEquals(45230.50, $data['revenue']);
    }

    public function test_analytics_card_top_pages_structure(): void
    {
        $card = new AnalyticsCard;
        $request = new Request;
        $data = $card->data($request);

        $topPages = $data['topPages'];
        $this->assertIsArray($topPages);
        $this->assertCount(5, $topPages);

        foreach ($topPages as $page) {
            $this->assertArrayHasKey('path', $page);
            $this->assertArrayHasKey('views', $page);
            $this->assertArrayHasKey('percentage', $page);
            $this->assertIsString($page['path']);
            $this->assertIsInt($page['views']);
            $this->assertIsFloat($page['percentage']);
        }

        // Verify first page data
        $firstPage = $topPages[0];
        $this->assertEquals('/dashboard', $firstPage['path']);
        $this->assertEquals(12500, $firstPage['views']);
        $this->assertEquals(35.2, $firstPage['percentage']);
    }

    public function test_analytics_card_user_growth_chart_structure(): void
    {
        $card = new AnalyticsCard;
        $request = new Request;
        $data = $card->data($request);

        $userGrowth = $data['userGrowth'];
        $this->assertIsArray($userGrowth);
        $this->assertArrayHasKey('labels', $userGrowth);
        $this->assertArrayHasKey('datasets', $userGrowth);

        // Verify labels
        $labels = $userGrowth['labels'];
        $this->assertIsArray($labels);
        $this->assertCount(6, $labels);
        $this->assertEquals(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], $labels);

        // Verify datasets
        $datasets = $userGrowth['datasets'];
        $this->assertIsArray($datasets);
        $this->assertCount(2, $datasets);

        foreach ($datasets as $dataset) {
            $this->assertArrayHasKey('label', $dataset);
            $this->assertArrayHasKey('data', $dataset);
            $this->assertArrayHasKey('borderColor', $dataset);
            $this->assertArrayHasKey('backgroundColor', $dataset);
            $this->assertIsString($dataset['label']);
            $this->assertIsArray($dataset['data']);
            $this->assertIsString($dataset['borderColor']);
            $this->assertIsString($dataset['backgroundColor']);
        }
    }

    public function test_analytics_card_device_breakdown_structure(): void
    {
        $card = new AnalyticsCard;
        $request = new Request;
        $data = $card->data($request);

        $deviceBreakdown = $data['deviceBreakdown'];
        $this->assertIsArray($deviceBreakdown);
        $this->assertCount(3, $deviceBreakdown);

        foreach ($deviceBreakdown as $device) {
            $this->assertArrayHasKey('device', $device);
            $this->assertArrayHasKey('users', $device);
            $this->assertArrayHasKey('percentage', $device);
            $this->assertIsString($device['device']);
            $this->assertIsInt($device['users']);
            $this->assertIsFloat($device['percentage']);
        }

        // Verify device types
        $deviceTypes = array_column($deviceBreakdown, 'device');
        $this->assertContains('Desktop', $deviceTypes);
        $this->assertContains('Mobile', $deviceTypes);
        $this->assertContains('Tablet', $deviceTypes);
    }

    public function test_analytics_card_admin_only_authorization(): void
    {
        $card = AnalyticsCard::adminOnly();

        // Test with admin user
        $adminRequest = $this->createMockRequest(true);
        $this->assertTrue($card->authorize($adminRequest));

        // Test with non-admin user
        $userRequest = $this->createMockRequest(false);
        $this->assertFalse($card->authorize($userRequest));
    }

    public function test_analytics_card_role_based_authorization(): void
    {
        $card = AnalyticsCard::forRole('manager');

        // Test with user having the role
        $managerRequest = $this->createMockRequestWithRole('manager', true);
        $this->assertTrue($card->authorize($managerRequest));

        // Test with user not having the role
        $userRequest = $this->createMockRequestWithRole('manager', false);
        $this->assertFalse($card->authorize($userRequest));
    }

    public function test_analytics_card_with_date_range_meta(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2024-01-31';
        $card = AnalyticsCard::withDateRange($startDate, $endDate);

        $meta = $card->meta();
        $this->assertArrayHasKey('dateRange', $meta);
        $this->assertEquals($startDate, $meta['dateRange']['start']);
        $this->assertEquals($endDate, $meta['dateRange']['end']);
    }

    public function test_analytics_card_with_metrics_meta(): void
    {
        $metrics = ['users', 'pageviews', 'revenue'];
        $card = AnalyticsCard::withMetrics($metrics);

        $meta = $card->meta();
        $this->assertArrayHasKey('selectedMetrics', $meta);
        $this->assertEquals($metrics, $meta['selectedMetrics']);
    }

    public function test_analytics_card_features_list(): void
    {
        $card = new AnalyticsCard;
        $meta = $card->meta();

        $this->assertArrayHasKey('features', $meta);
        $features = $meta['features'];
        $this->assertIsArray($features);
        $this->assertContains('real_time_updates', $features);
        $this->assertContains('date_range_filtering', $features);
        $this->assertContains('metric_selection', $features);
        $this->assertContains('export_capabilities', $features);
    }

    public function test_analytics_card_timestamp_format(): void
    {
        $card = new AnalyticsCard;
        $meta = $card->meta();

        $this->assertArrayHasKey('timestamp', $meta);
        $timestamp = $meta['timestamp'];
        $this->assertIsString($timestamp);

        // Verify ISO 8601 format (with optional microseconds)
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,6})?Z$/',
            $timestamp,
        );
    }

    public function test_analytics_card_version_information(): void
    {
        $card = new AnalyticsCard;
        $meta = $card->meta();

        $this->assertArrayHasKey('version', $meta);
        $this->assertEquals('1.0', $meta['version']);
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
