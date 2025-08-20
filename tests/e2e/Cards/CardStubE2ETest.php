<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E\Cards;

use Illuminate\Filesystem\Filesystem;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Card Stub End-to-End Tests.
 *
 * Tests the complete Card stub workflow from template processing
 * to actual card usage in dashboard context.
 */
class CardStubE2ETest extends TestCase
{
    protected Filesystem $files;
    protected string $stubPath;
    protected string $testCardsPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->files = new Filesystem();
        $this->stubPath = __DIR__ . '/../../../src/Console/stubs/Card.stub';
        $this->testCardsPath = base_path('tests/temp/Cards');
        
        // Create temp directory for test cards
        if (!$this->files->exists($this->testCardsPath)) {
            $this->files->makeDirectory($this->testCardsPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if ($this->files->exists(dirname($this->testCardsPath))) {
            $this->files->deleteDirectory(dirname($this->testCardsPath));
        }
        
        parent::tearDown();
    }

    public function test_complete_card_lifecycle_from_stub(): void
    {
        // Step 1: Generate card from stub
        $card = $this->generateCardFromStub('DashboardStatsCard', 'chart-bar', 'Analytics');
        
        // Step 2: Test card instantiation
        $this->assertInstanceOf('JTD\\AdminPanel\\Cards\\Card', $card);
        
        // Step 3: Test card meta data
        $meta = $card->meta();
        $this->assertEquals('DashboardStatsCard', $meta['title']);
        $this->assertEquals('chart-bar', $meta['icon']);
        $this->assertEquals('Analytics', $meta['group']);
        
        // Step 4: Test card authorization
        $request = $this->createAuthenticatedRequest();
        $this->assertTrue($card->authorize($request));
        
        // Step 5: Test card JSON serialization
        $json = $card->jsonSerialize();
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('component', $json);
        $this->assertArrayHasKey('uriKey', $json);
        $this->assertArrayHasKey('meta', $json);
    }

    public function test_multiple_cards_with_different_configurations(): void
    {
        $cardConfigs = [
            ['name' => 'UserStatsCard', 'icon' => 'users', 'group' => 'Users'],
            ['name' => 'OrderStatsCard', 'icon' => 'shopping-cart', 'group' => 'Orders'],
            ['name' => 'RevenueCard', 'icon' => 'currency-dollar', 'group' => 'Finance'],
        ];

        $cards = [];
        foreach ($cardConfigs as $config) {
            $cards[] = $this->generateCardFromStub($config['name'], $config['icon'], $config['group']);
        }

        // Test that all cards are properly instantiated
        $this->assertCount(3, $cards);
        
        foreach ($cards as $index => $card) {
            $config = $cardConfigs[$index];
            $meta = $card->meta();
            
            $this->assertEquals($config['name'], $meta['title']);
            $this->assertEquals($config['icon'], $meta['icon']);
            $this->assertEquals($config['group'], $meta['group']);
        }
    }

    public function test_card_with_custom_meta_data(): void
    {
        $card = $this->generateCardFromStub('CustomMetaCard', 'cog', 'Settings');
        
        // Test that the card can be extended with custom meta
        $card->withMeta([
            'customProperty' => 'customValue',
            'refreshInterval' => 30,
            'apiEndpoint' => '/api/custom-data',
        ]);

        $meta = $card->meta();
        $this->assertEquals('customValue', $meta['customProperty']);
        $this->assertEquals(30, $meta['refreshInterval']);
        $this->assertEquals('/api/custom-data', $meta['apiEndpoint']);
    }

    public function test_card_authorization_with_different_users(): void
    {
        $card = $this->generateCardFromStub('AuthTestCard', 'shield', 'Security');

        // Test default authorization (should always return true)
        $authenticatedRequest = $this->createAuthenticatedRequest();
        $this->assertTrue($card->authorize($authenticatedRequest));

        $unauthenticatedRequest = $this->createUnauthenticatedRequest();
        $this->assertTrue($card->authorize($unauthenticatedRequest)); // Default authorization allows all
    }

    public function test_card_with_custom_authorization(): void
    {
        $card = $this->generateCardFromStub('AdminOnlyCard', 'key', 'Admin');

        // Add custom authorization logic that always returns false for testing
        $card->canSee(function ($request) {
            return false; // Always deny for this test
        });

        // Test with any request (should fail because callback returns false)
        $userRequest = $this->createAuthenticatedRequest();
        $this->assertFalse($card->authorize($userRequest), 'Custom authorization should deny access');

        // Test with admin request (should still fail because callback returns false)
        $adminRequest = $this->createAdminRequest();
        $this->assertFalse($card->authorize($adminRequest), 'Custom authorization should deny even admin access');

        // Now test with a callback that allows access
        $card->canSee(function ($request) {
            return true; // Always allow for this test
        });

        $this->assertTrue($card->authorize($userRequest), 'Custom authorization should allow access when callback returns true');
    }

    public function test_card_data_method_returns_expected_structure(): void
    {
        $card = $this->generateCardFromStub('DataCard', 'database', 'Data');
        
        $meta = $card->meta();
        $this->assertArrayHasKey('data', $meta);
        $this->assertArrayHasKey('example_value', $meta['data']);
        $this->assertStringContainsString('DataCard', $meta['data']['example_value']);
        $this->assertArrayHasKey('timestamp', $meta);
    }

    public function test_card_factory_method_works(): void
    {
        $card = $this->generateCardFromStub('FactoryCard', 'factory', 'Manufacturing');
        
        $className = get_class($card);
        $factoryCard = $className::make();
        
        $this->assertInstanceOf($className, $factoryCard);
        $this->assertInstanceOf('JTD\\AdminPanel\\Cards\\Card', $factoryCard);
        
        // Test that factory method creates new instance
        $this->assertNotSame($card, $factoryCard);
    }

    protected function generateCardFromStub(string $cardName, string $icon, string $group): object
    {
        $stubContent = $this->files->get($this->stubPath);
        
        $replacements = [
            '{{ namespace }}' => 'Tests\\Temp\\Cards',
            '{{ class }}' => $cardName,
            '{{ icon }}' => $icon,
            '{{ group }}' => $group,
        ];

        $processedContent = $stubContent;
        foreach ($replacements as $placeholder => $value) {
            $processedContent = str_replace($placeholder, $value, $processedContent);
        }

        $outputFile = $this->testCardsPath . '/' . $cardName . '.php';
        $this->files->put($outputFile, $processedContent);

        require_once $outputFile;
        
        $className = 'Tests\\Temp\\Cards\\' . $cardName;
        return new $className();
    }

    protected function createAuthenticatedRequest()
    {
        $user = new \stdClass();
        $user->id = 1;
        $user->name = 'Test User';
        $user->is_admin = false; // Regular user, not admin

        $request = $this->getMockBuilder(\Illuminate\Http\Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['user'])
            ->getMock();
        $request->expects($this->any())->method('user')->willReturn($user);

        return $request;
    }

    protected function createUnauthenticatedRequest()
    {
        $request = $this->getMockBuilder(\Illuminate\Http\Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['user'])
            ->getMock();
        $request->expects($this->any())->method('user')->willReturn(null);
        
        return $request;
    }

    protected function createAdminRequest()
    {
        $user = new \stdClass();
        $user->id = 1;
        $user->name = 'Admin User';
        $user->is_admin = true;
        
        $request = $this->getMockBuilder(\Illuminate\Http\Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['user'])
            ->getMock();
        $request->expects($this->any())->method('user')->willReturn($user);
        
        return $request;
    }
}
