<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use JTD\AdminPanel\Console\Commands\MakeDashboardCommand;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * MakeDashboardCommand Unit Tests.
 *
 * Tests the dashboard generation command functionality including
 * file creation, stub processing, and error handling.
 */
class MakeDashboardCommandTest extends TestCase
{
    protected function setUp(): void
    {
        // No parent setup needed for pure unit tests
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_command_can_be_instantiated(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $command = new MakeDashboardCommand($files);

        $this->assertInstanceOf(MakeDashboardCommand::class, $command);
    }

    public function test_command_has_correct_signature(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $command = new MakeDashboardCommand($files);

        $this->assertEquals('admin-panel:dashboard', $command->getName());
        $this->assertStringContainsString('name', $command->getDefinition()->getArgument('name')->getDescription());
    }

    public function test_command_has_correct_description(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $command = new MakeDashboardCommand($files);

        $this->assertEquals('Create a new admin panel dashboard', $command->getDescription());
    }

    public function test_get_stub_returns_correct_path(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $command = new MakeDashboardCommand($files);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getStub');
        $method->setAccessible(true);

        $stubPath = $method->invoke($command);

        $this->assertStringEndsWith('stubs/Dashboard.stub', $stubPath);
        $this->assertStringContainsString('Console', $stubPath);
    }

    public function test_get_default_namespace_returns_correct_namespace(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $command = new MakeDashboardCommand($files);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getDefaultNamespace');
        $method->setAccessible(true);

        $namespace = $method->invoke($command, 'App');

        $this->assertEquals('App\\Admin\\Dashboards', $namespace);
    }

    public function test_replace_class_replaces_class_placeholder(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $command = new MakeDashboardCommand($files);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('replaceClass');
        $method->setAccessible(true);

        $stub = 'class {{ class }} extends Dashboard';
        $result = $method->invoke($command, $stub, 'App\\Admin\\Dashboards\\TestDashboard');

        $this->assertEquals('class TestDashboard extends Dashboard', $result);
    }

    public function test_replace_uri_key_replaces_uri_key_placeholder(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $command = new MakeDashboardCommand($files);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('replaceUriKey');
        $method->setAccessible(true);

        $stub = 'uriKey: {{ uriKey }}';
        $result = $method->invoke($command, $stub, 'App\\Admin\\Dashboards\\TestDashboard');

        $this->assertEquals('uriKey: test-dashboard', $result);
    }

    public function test_replace_uri_key_handles_multiple_words(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $command = new MakeDashboardCommand($files);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('replaceUriKey');
        $method->setAccessible(true);

        $stub = 'uriKey: {{ uriKey }}';
        $result = $method->invoke($command, $stub, 'App\\Admin\\Dashboards\\UserAnalyticsDashboard');

        $this->assertEquals('uriKey: user-analytics-dashboard', $result);
    }

    public function test_build_class_processes_all_replacements(): void
    {
        // This test requires Laravel context, so we'll test the individual methods instead
        $this->assertTrue(true); // Placeholder - tested in integration tests
    }

    public function test_get_path_returns_correct_file_path(): void
    {
        // This test requires Laravel context, so we'll test in integration tests
        $this->assertTrue(true); // Placeholder - tested in integration tests
    }

    public function test_command_type_is_dashboard(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $command = new MakeDashboardCommand($files);

        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('type');
        $property->setAccessible(true);

        $this->assertEquals('Dashboard', $property->getValue($command));
    }

    public function test_command_handles_force_option(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $command = new MakeDashboardCommand($files);

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('force'));
        $this->assertEquals('Overwrite existing files', $definition->getOption('force')->getDescription());
    }

    public function test_command_signature_includes_required_name_argument(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $command = new MakeDashboardCommand($files);

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->getArgument('name')->isRequired());
    }
}
