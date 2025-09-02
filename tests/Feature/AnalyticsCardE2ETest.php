<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Examples\AnalyticsCard;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Analytics Card End-to-End Tests.
 *
 * Complete end-to-end testing of the AnalyticsCard functionality
 * including API endpoints, data serialization, and real-world scenarios.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class AnalyticsCardE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_analytics_card_complete_workflow(): void
    {
        // Create admin user
        $admin = $this->createAdminUser();

        // Test 1: Card instantiation and basic properties
        $card = new AnalyticsCard;
        $this->assertInstanceOf(AnalyticsCard::class, $card);
        $this->assertEquals('Analytics Card', $card->name());
        $this->assertEquals('analytics-card', $card->uriKey());

        // Test 2: Card authorization with admin user
        $request = Request::create('/admin/cards/analytics', 'GET');
        $request->setUserResolver(fn () => $admin);
        $this->assertTrue($card->authorize($request));

        // Test 3: Data generation and structure
        $data = $card->data($request);
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

        // Test 4: JSON serialization for API responses
        $json = $card->jsonSerialize();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('uriKey', $json);
        $this->assertArrayHasKey('component', $json);
        $this->assertArrayHasKey('meta', $json);

        // Test 5: Meta data includes analytics data
        $meta = $json['meta'];
        $this->assertArrayHasKey('data', $meta);
        $this->assertArrayHasKey('timestamp', $meta);
        $this->assertArrayHasKey('version', $meta);
        $this->assertArrayHasKey('features', $meta);

        // Test 6: Verify analytics data integrity
        $analyticsData = $meta['data'];
        $this->assertEquals(15420, $analyticsData['totalUsers']);
        $this->assertEquals(12350, $analyticsData['activeUsers']);
        $this->assertEquals(89750, $analyticsData['pageViews']);
        $this->assertEquals(3.2, $analyticsData['conversionRate']);
        $this->assertEquals(45230.50, $analyticsData['revenue']);
    }

    public function test_analytics_card_admin_only_workflow(): void
    {
        // Create regular user and admin user
        $regularUser = $this->createUser();
        $adminUser = $this->createAdminUser();

        // Test admin-only card
        $adminOnlyCard = AnalyticsCard::adminOnly();

        // Test with regular user - should be denied
        $regularRequest = Request::create('/admin/cards/analytics', 'GET');
        $regularRequest->setUserResolver(fn () => $regularUser);
        $this->assertFalse($adminOnlyCard->authorize($regularRequest));

        // Test with admin user - should be allowed
        $adminRequest = Request::create('/admin/cards/analytics', 'GET');
        $adminRequest->setUserResolver(fn () => $adminUser);
        $this->assertTrue($adminOnlyCard->authorize($adminRequest));

        // Verify data is accessible for admin
        $data = $adminOnlyCard->data($adminRequest);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('totalUsers', $data);
    }

    public function test_analytics_card_with_date_range_workflow(): void
    {
        $admin = $this->createAdminUser();
        $request = Request::create('/admin/cards/analytics', 'GET');
        $request->setUserResolver(fn () => $admin);

        // Test card with date range
        $startDate = '2024-01-01';
        $endDate = '2024-01-31';
        $cardWithDateRange = AnalyticsCard::withDateRange($startDate, $endDate);

        // Verify date range is set in meta
        $meta = $cardWithDateRange->meta();
        $this->assertArrayHasKey('dateRange', $meta);
        $this->assertEquals($startDate, $meta['dateRange']['start']);
        $this->assertEquals($endDate, $meta['dateRange']['end']);

        // Verify card still functions normally
        $data = $cardWithDateRange->data($request);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('totalUsers', $data);
    }

    public function test_analytics_card_with_metrics_workflow(): void
    {
        $admin = $this->createAdminUser();
        $request = Request::create('/admin/cards/analytics', 'GET');
        $request->setUserResolver(fn () => $admin);

        // Test card with specific metrics
        $metrics = ['users', 'pageviews', 'revenue'];
        $cardWithMetrics = AnalyticsCard::withMetrics($metrics);

        // Verify metrics are set in meta
        $meta = $cardWithMetrics->meta();
        $this->assertArrayHasKey('selectedMetrics', $meta);
        $this->assertEquals($metrics, $meta['selectedMetrics']);

        // Verify card still functions normally
        $data = $cardWithMetrics->data($request);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('totalUsers', $data);
    }

    public function test_analytics_card_top_pages_data_structure(): void
    {
        $admin = $this->createAdminUser();
        $request = Request::create('/admin/cards/analytics', 'GET');
        $request->setUserResolver(fn () => $admin);

        $card = new AnalyticsCard;
        $data = $card->data($request);

        // Verify top pages structure
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

        // Verify specific page data
        $dashboardPage = $topPages[0];
        $this->assertEquals('/dashboard', $dashboardPage['path']);
        $this->assertEquals(12500, $dashboardPage['views']);
        $this->assertEquals(35.2, $dashboardPage['percentage']);
    }

    public function test_analytics_card_user_growth_chart_data(): void
    {
        $admin = $this->createAdminUser();
        $request = Request::create('/admin/cards/analytics', 'GET');
        $request->setUserResolver(fn () => $admin);

        $card = new AnalyticsCard;
        $data = $card->data($request);

        // Verify user growth chart structure
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
            $this->assertCount(6, $dataset['data']);
        }
    }

    public function test_analytics_card_device_breakdown_data(): void
    {
        $admin = $this->createAdminUser();
        $request = Request::create('/admin/cards/analytics', 'GET');
        $request->setUserResolver(fn () => $admin);

        $card = new AnalyticsCard;
        $data = $card->data($request);

        // Verify device breakdown structure
        $deviceBreakdown = $data['deviceBreakdown'];
        $this->assertIsArray($deviceBreakdown);
        $this->assertCount(3, $deviceBreakdown);

        $totalUsers = 0;
        $totalPercentage = 0;

        foreach ($deviceBreakdown as $device) {
            $this->assertArrayHasKey('device', $device);
            $this->assertArrayHasKey('users', $device);
            $this->assertArrayHasKey('percentage', $device);
            $this->assertIsString($device['device']);
            $this->assertIsInt($device['users']);
            $this->assertIsFloat($device['percentage']);

            $totalUsers += $device['users'];
            $totalPercentage += $device['percentage'];
        }

        // Verify device types
        $deviceTypes = array_column($deviceBreakdown, 'device');
        $this->assertContains('Desktop', $deviceTypes);
        $this->assertContains('Mobile', $deviceTypes);
        $this->assertContains('Tablet', $deviceTypes);

        // Verify totals make sense
        $this->assertEquals(15420, $totalUsers);
        $this->assertEqualsWithDelta(100.0, $totalPercentage, 0.01);
    }

    public function test_analytics_card_timestamp_and_version_info(): void
    {
        $card = new AnalyticsCard;
        $meta = $card->meta();

        // Verify timestamp format
        $this->assertArrayHasKey('timestamp', $meta);
        $timestamp = $meta['timestamp'];
        $this->assertIsString($timestamp);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,6})?Z$/',
            $timestamp,
        );

        // Verify version information
        $this->assertArrayHasKey('version', $meta);
        $this->assertEquals('1.0', $meta['version']);

        // Verify features list
        $this->assertArrayHasKey('features', $meta);
        $features = $meta['features'];
        $this->assertIsArray($features);
        $this->assertContains('real_time_updates', $features);
        $this->assertContains('date_range_filtering', $features);
        $this->assertContains('metric_selection', $features);
        $this->assertContains('export_capabilities', $features);
    }

    public function test_analytics_card_complete_json_response(): void
    {
        $admin = $this->createAdminUser();
        $card = new AnalyticsCard;

        // Simulate complete API response
        $jsonResponse = $card->jsonSerialize();

        // Verify complete structure
        $this->assertArrayHasKey('name', $jsonResponse);
        $this->assertArrayHasKey('uriKey', $jsonResponse);
        $this->assertArrayHasKey('component', $jsonResponse);
        $this->assertArrayHasKey('meta', $jsonResponse);

        $meta = $jsonResponse['meta'];

        // Verify all meta fields are present
        $expectedMetaKeys = [
            'title', 'description', 'icon', 'color', 'group',
            'refreshable', 'refreshInterval', 'size', 'data',
            'timestamp', 'version', 'features',
        ];

        foreach ($expectedMetaKeys as $key) {
            $this->assertArrayHasKey($key, $meta, "Missing meta key: {$key}");
        }

        // Verify data completeness
        $data = $meta['data'];
        $expectedDataKeys = [
            'totalUsers', 'activeUsers', 'pageViews', 'conversionRate',
            'revenue', 'topPages', 'userGrowth', 'deviceBreakdown', 'lastUpdated',
        ];

        foreach ($expectedDataKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Missing data key: {$key}");
        }

        // Verify JSON can be encoded/decoded
        $jsonString = json_encode($jsonResponse);
        $this->assertIsString($jsonString);
        $this->assertNotFalse($jsonString);

        $decodedJson = json_decode($jsonString, true);
        $this->assertIsArray($decodedJson);
        $this->assertEquals($jsonResponse, $decodedJson);
    }
}
