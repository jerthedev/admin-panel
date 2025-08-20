<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use JTD\AdminPanel\Console\Commands\MakeCardCommand;
use PHPUnit\Framework\TestCase;

/**
 * MakeCardCommand Unit Tests.
 *
 * Tests the MakeCardCommand functionality including validation,
 * file generation, and command options processing.
 */
class MakeCardCommandTest extends TestCase
{
    protected Filesystem $files;
    protected MakeCardCommand $command;
    protected string $tempPath;

    protected function setUp(): void
    {
        $this->files = $this->createMock(Filesystem::class);
        $this->command = new MakeCardCommand($this->files);
        $this->tempPath = sys_get_temp_dir() . '/admin-panel-test-' . uniqid();
    }

    public function test_command_has_correct_signature(): void
    {
        $this->assertEquals('admin-panel:make-card', $this->command->getName());
        $this->assertStringContainsString('Create a new admin panel card', $this->command->getDescription());
    }

    public function test_command_validates_card_name(): void
    {
        // Test invalid card names
        $this->assertFalse($this->command->isValidCardName(''));
        $this->assertFalse($this->command->isValidCardName('123Invalid'));
        $this->assertFalse($this->command->isValidCardName('invalid-name'));
        $this->assertFalse($this->command->isValidCardName('invalid name'));
        $this->assertFalse($this->command->isValidCardName('invalidName')); // Should start with capital

        // Test valid card names
        $this->assertTrue($this->command->isValidCardName('ValidCard'));
        $this->assertTrue($this->command->isValidCardName('UserStats'));
        $this->assertTrue($this->command->isValidCardName('SystemHealthCheck'));
        $this->assertTrue($this->command->isValidCardName('A')); // Single letter is valid
    }

    public function test_create_card_creates_directories(): void
    {
        $appPath = '/app';
        $resourcesPath = '/resources';
        
        // Mock file and directory existence checks
        $this->files->method('exists')
            ->willReturnCallback(function ($path) {
                // Return false for all paths to trigger directory creation
                return false;
            });

        // Mock directory creation
        $this->files->expects($this->exactly(2))
            ->method('makeDirectory');

        // Mock file operations
        $this->files->expects($this->exactly(2))
            ->method('put')
            ->willReturn(true);

        // Mock stub file reading
        $this->files->expects($this->once())
            ->method('get')
            ->willReturn('<?php stub content {{ namespace }} {{ class }} {{ group }} {{ icon }}');

        $result = $this->command->createCard('TestCard', $appPath, $resourcesPath, false, 'Analytics', 'chart-bar');
        $this->assertTrue($result);
    }

    public function test_create_card_respects_force_option(): void
    {
        $appPath = '/app';
        $resourcesPath = '/resources';
        
        // Mock file existence check
        $this->files->expects($this->once())
            ->method('exists')
            ->with('/app/Admin/Cards/TestCardCard.php')
            ->willReturn(true);

        // Without force, should return false
        $result = $this->command->createCard('TestCard', $appPath, $resourcesPath, false);
        $this->assertFalse($result);
    }

    public function test_create_card_with_force_overwrites_existing(): void
    {
        $appPath = '/app';
        $resourcesPath = '/resources';
        
        // Mock file existence checks
        $this->files->expects($this->exactly(3))
            ->method('exists')
            ->willReturnMap([
                ['/app/Admin/Cards/TestCardCard.php', true], // File exists
                ['/app/Admin/Cards', true], // Directory exists
                ['/resources/js/admin-cards', true], // Directory exists
            ]);

        // Mock file operations
        $this->files->expects($this->exactly(2))
            ->method('put')
            ->willReturn(true);

        // Mock stub file reading
        $this->files->expects($this->once())
            ->method('get')
            ->willReturn('<?php stub content {{ namespace }} {{ class }} {{ group }} {{ icon }}');

        // With force, should return true even if file exists
        $result = $this->command->createCard('TestCard', $appPath, $resourcesPath, true, 'Analytics', 'chart-bar');
        $this->assertTrue($result);
    }

    public function test_generate_php_class_uses_stub_template(): void
    {
        $stubContent = '<?php namespace {{ namespace }}; class {{ class }} { group: {{ group }}, icon: {{ icon }} }';
        
        $this->files->expects($this->once())
            ->method('get')
            ->willReturn($stubContent);

        $result = $this->command->generatePhpClass('TestCard', 'Analytics', 'chart-bar');
        
        $this->assertStringContainsString('namespace App\\Admin\\Cards;', $result);
        $this->assertStringContainsString('class TestCardCard', $result);
        $this->assertStringContainsString('group: Analytics', $result);
        $this->assertStringContainsString('icon: chart-bar', $result);
    }

    public function test_generate_php_class_handles_null_options(): void
    {
        $stubContent = '<?php namespace {{ namespace }}; class {{ class }} { group: {{ group }}, icon: {{ icon }} }';
        
        $this->files->expects($this->once())
            ->method('get')
            ->willReturn($stubContent);

        $result = $this->command->generatePhpClass('TestCard', null, null);
        
        $this->assertStringContainsString('group: Default', $result);
        $this->assertStringContainsString('icon: square-3-stack-3d', $result);
    }

    public function test_generate_vue_component_creates_valid_template(): void
    {
        $result = $this->command->generateVueComponent('TestCard');
        
        // Check Vue template structure
        $this->assertStringContainsString('<template>', $result);
        $this->assertStringContainsString('</template>', $result);
        $this->assertStringContainsString('<script setup>', $result);
        $this->assertStringContainsString('</script>', $result);
        
        // Check component-specific content
        $this->assertStringContainsString('TestCard', $result);
        $this->assertStringContainsString('TestCardCard', $result);
        $this->assertStringContainsString('resources/js/admin-cards/TestCard.vue', $result);
        
        // Check Vue 3 Composition API usage
        $this->assertStringContainsString('defineProps', $result);
        $this->assertStringContainsString('defineEmits', $result);
        $this->assertStringContainsString('ref', $result);
    }

    public function test_auto_register_card_handles_missing_service_provider(): void
    {
        $appPath = '/app';
        
        $this->files->expects($this->once())
            ->method('exists')
            ->with('/app/Providers/AdminPanelServiceProvider.php')
            ->willReturn(false);

        $result = $this->command->autoRegisterCard('TestCard', $appPath);
        $this->assertFalse($result);
    }

    public function test_auto_register_card_skips_if_already_registered(): void
    {
        $appPath = '/app';
        $serviceProviderContent = 'app(AdminPanel::class)->cards([\\App\\Admin\\Cards\\TestCardCard::class]);';
        
        $this->files->expects($this->once())
            ->method('exists')
            ->with('/app/Providers/AdminPanelServiceProvider.php')
            ->willReturn(true);

        $this->files->expects($this->once())
            ->method('get')
            ->willReturn($serviceProviderContent);

        $result = $this->command->autoRegisterCard('TestCard', $appPath);
        $this->assertTrue($result); // Already registered
    }

    public function test_auto_register_card_adds_to_existing_registration(): void
    {
        $appPath = '/app';
        $serviceProviderContent = 'app(AdminPanel::class)->cards([
            \\App\\Admin\\Cards\\ExistingCard::class,
        ]);';
        
        $this->files->expects($this->once())
            ->method('exists')
            ->with('/app/Providers/AdminPanelServiceProvider.php')
            ->willReturn(true);

        $this->files->expects($this->once())
            ->method('get')
            ->willReturn($serviceProviderContent);

        $this->files->expects($this->once())
            ->method('put')
            ->with(
                '/app/Providers/AdminPanelServiceProvider.php',
                $this->stringContains('\\App\\Admin\\Cards\\TestCardCard::class')
            );

        $result = $this->command->autoRegisterCard('TestCard', $appPath);
        $this->assertTrue($result);
    }

    public function test_auto_register_card_creates_new_registration(): void
    {
        $appPath = '/app';
        $serviceProviderContent = 'public function boot(): void
        {
            // Some existing code
        }';
        
        $this->files->expects($this->once())
            ->method('exists')
            ->with('/app/Providers/AdminPanelServiceProvider.php')
            ->willReturn(true);

        $this->files->expects($this->once())
            ->method('get')
            ->willReturn($serviceProviderContent);

        $this->files->expects($this->once())
            ->method('put')
            ->with(
                '/app/Providers/AdminPanelServiceProvider.php',
                $this->logicalAnd(
                    $this->stringContains('Register custom admin cards'),
                    $this->stringContains('\\App\\Admin\\Cards\\TestCardCard::class')
                )
            );

        $result = $this->command->autoRegisterCard('TestCard', $appPath);
        $this->assertTrue($result);
    }

    public function test_command_signature_includes_all_options(): void
    {
        $definition = $this->command->getDefinition();
        
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasOption('group'));
        $this->assertTrue($definition->hasOption('icon'));
        $this->assertTrue($definition->hasOption('force'));
    }

    public function test_card_name_validation_edge_cases(): void
    {
        // Test numeric start
        $this->assertFalse($this->command->isValidCardName('1Card'));
        $this->assertFalse($this->command->isValidCardName('9TestCard'));
        
        // Test special characters
        $this->assertFalse($this->command->isValidCardName('Card@Test'));
        $this->assertFalse($this->command->isValidCardName('Card#Test'));
        $this->assertFalse($this->command->isValidCardName('Card$Test'));
        
        // Test underscores and hyphens
        $this->assertFalse($this->command->isValidCardName('Card_Test'));
        $this->assertFalse($this->command->isValidCardName('Card-Test'));
        
        // Test valid alphanumeric
        $this->assertTrue($this->command->isValidCardName('Card123'));
        $this->assertTrue($this->command->isValidCardName('TestCard2'));
        $this->assertTrue($this->command->isValidCardName('MyCard3Test'));
    }
}
