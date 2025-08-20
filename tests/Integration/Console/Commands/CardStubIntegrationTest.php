<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Card Stub Integration Tests.
 *
 * Tests the Card stub template integration with Laravel context,
 * including file generation and class instantiation.
 */
class CardStubIntegrationTest extends TestCase
{
    protected Filesystem $files;
    protected string $stubPath;
    protected string $testOutputPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->files = new Filesystem();
        $this->stubPath = __DIR__ . '/../../../../src/Console/stubs/Card.stub';
        $this->testOutputPath = base_path('tests/temp');
        
        // Create temp directory for test files
        if (!$this->files->exists($this->testOutputPath)) {
            $this->files->makeDirectory($this->testOutputPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if ($this->files->exists($this->testOutputPath)) {
            $this->files->deleteDirectory($this->testOutputPath);
        }
        
        parent::tearDown();
    }

    public function test_stub_generates_valid_card_class(): void
    {
        $stubContent = $this->files->get($this->stubPath);
        
        $replacements = [
            '{{ namespace }}' => 'Tests\\Temp',
            '{{ class }}' => 'IntegrationTestCard',
            '{{ icon }}' => 'test-icon',
            '{{ group }}' => 'Test Group',
        ];

        $processedContent = $stubContent;
        foreach ($replacements as $placeholder => $value) {
            $processedContent = str_replace($placeholder, $value, $processedContent);
        }

        $outputFile = $this->testOutputPath . '/IntegrationTestCard.php';
        $this->files->put($outputFile, $processedContent);

        // Verify file was created
        $this->assertTrue($this->files->exists($outputFile));

        // Include the file and test class instantiation
        require_once $outputFile;
        
        $className = 'Tests\\Temp\\IntegrationTestCard';
        $this->assertTrue(class_exists($className));

        // Test that the class can be instantiated
        $card = new $className();
        $this->assertInstanceOf('JTD\\AdminPanel\\Cards\\Card', $card);
    }

    public function test_generated_card_has_correct_meta_data(): void
    {
        $stubContent = $this->files->get($this->stubPath);
        
        $replacements = [
            '{{ namespace }}' => 'Tests\\Temp',
            '{{ class }}' => 'MetaTestCard',
            '{{ icon }}' => 'chart-bar',
            '{{ group }}' => 'Analytics',
        ];

        $processedContent = $stubContent;
        foreach ($replacements as $placeholder => $value) {
            $processedContent = str_replace($placeholder, $value, $processedContent);
        }

        $outputFile = $this->testOutputPath . '/MetaTestCard.php';
        $this->files->put($outputFile, $processedContent);

        require_once $outputFile;
        
        $className = 'Tests\\Temp\\MetaTestCard';
        $card = new $className();

        $meta = $card->meta();
        
        $this->assertEquals('MetaTestCard', $meta['title']);
        $this->assertEquals('chart-bar', $meta['icon']);
        $this->assertEquals('Analytics', $meta['group']);
        $this->assertFalse($meta['refreshable']);
        $this->assertArrayHasKey('data', $meta);
        $this->assertArrayHasKey('timestamp', $meta);
    }

    public function test_generated_card_authorization_works(): void
    {
        $stubContent = $this->files->get($this->stubPath);

        $replacements = [
            '{{ namespace }}' => 'Tests\\Temp',
            '{{ class }}' => 'AuthTestCard',
            '{{ icon }}' => 'shield',
            '{{ group }}' => 'Security',
        ];

        $processedContent = $stubContent;
        foreach ($replacements as $placeholder => $value) {
            $processedContent = str_replace($placeholder, $value, $processedContent);
        }

        $outputFile = $this->testOutputPath . '/AuthTestCard.php';
        $this->files->put($outputFile, $processedContent);

        require_once $outputFile;

        $className = 'Tests\\Temp\\AuthTestCard';
        $card = new $className();

        // Test default authorization (should always return true)
        $request = $this->createRequestWithUser();
        $this->assertTrue($card->authorize($request));

        $request = $this->createRequestWithoutUser();
        $this->assertTrue($card->authorize($request)); // Default authorization allows all

        // Test custom authorization
        $card->canSee(function ($request) {
            return $request->user() !== null;
        });

        $requestWithUser = $this->createRequestWithUser();
        $this->assertTrue($card->authorize($requestWithUser));

        $requestWithoutUser = $this->createRequestWithoutUser();
        $this->assertFalse($card->authorize($requestWithoutUser));
    }

    public function test_generated_card_make_method_works(): void
    {
        $stubContent = $this->files->get($this->stubPath);
        
        $replacements = [
            '{{ namespace }}' => 'Tests\\Temp',
            '{{ class }}' => 'MakeTestCard',
            '{{ icon }}' => 'plus',
            '{{ group }}' => 'Factory',
        ];

        $processedContent = $stubContent;
        foreach ($replacements as $placeholder => $value) {
            $processedContent = str_replace($placeholder, $value, $processedContent);
        }

        $outputFile = $this->testOutputPath . '/MakeTestCard.php';
        $this->files->put($outputFile, $processedContent);

        require_once $outputFile;
        
        $className = 'Tests\\Temp\\MakeTestCard';
        $card = $className::make();

        $this->assertInstanceOf($className, $card);
        $this->assertInstanceOf('JTD\\AdminPanel\\Cards\\Card', $card);
    }

    public function test_generated_card_has_example_data(): void
    {
        $stubContent = $this->files->get($this->stubPath);
        
        $replacements = [
            '{{ namespace }}' => 'Tests\\Temp',
            '{{ class }}' => 'DataTestCard',
            '{{ icon }}' => 'database',
            '{{ group }}' => 'Data',
        ];

        $processedContent = $stubContent;
        foreach ($replacements as $placeholder => $value) {
            $processedContent = str_replace($placeholder, $value, $processedContent);
        }

        $outputFile = $this->testOutputPath . '/DataTestCard.php';
        $this->files->put($outputFile, $processedContent);

        require_once $outputFile;
        
        $className = 'Tests\\Temp\\DataTestCard';
        $card = new $className();

        $meta = $card->meta();
        $this->assertArrayHasKey('data', $meta);
        $this->assertArrayHasKey('example_value', $meta['data']);
        $this->assertStringContainsString('DataTestCard', $meta['data']['example_value']);
    }

    public function test_multiple_cards_can_be_generated(): void
    {
        $stubContent = $this->files->get($this->stubPath);
        
        $cards = [
            ['class' => 'FirstCard', 'icon' => 'one', 'group' => 'First'],
            ['class' => 'SecondCard', 'icon' => 'two', 'group' => 'Second'],
            ['class' => 'ThirdCard', 'icon' => 'three', 'group' => 'Third'],
        ];

        foreach ($cards as $cardConfig) {
            $replacements = [
                '{{ namespace }}' => 'Tests\\Temp',
                '{{ class }}' => $cardConfig['class'],
                '{{ icon }}' => $cardConfig['icon'],
                '{{ group }}' => $cardConfig['group'],
            ];

            $processedContent = $stubContent;
            foreach ($replacements as $placeholder => $value) {
                $processedContent = str_replace($placeholder, $value, $processedContent);
            }

            $outputFile = $this->testOutputPath . '/' . $cardConfig['class'] . '.php';
            $this->files->put($outputFile, $processedContent);

            require_once $outputFile;
            
            $className = 'Tests\\Temp\\' . $cardConfig['class'];
            $this->assertTrue(class_exists($className));
            
            $card = new $className();
            $this->assertInstanceOf('JTD\\AdminPanel\\Cards\\Card', $card);
            
            $meta = $card->meta();
            $this->assertEquals($cardConfig['class'], $meta['title']);
            $this->assertEquals($cardConfig['icon'], $meta['icon']);
            $this->assertEquals($cardConfig['group'], $meta['group']);
        }
    }

    protected function createRequestWithUser()
    {
        $user = new \stdClass();
        $user->id = 1;

        $request = $this->getMockBuilder(\Illuminate\Http\Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['user'])
            ->getMock();
        $request->expects($this->any())->method('user')->willReturn($user);

        return $request;
    }

    protected function createRequestWithoutUser()
    {
        $request = $this->getMockBuilder(\Illuminate\Http\Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['user'])
            ->getMock();
        $request->expects($this->any())->method('user')->willReturn(null);

        return $request;
    }
}
