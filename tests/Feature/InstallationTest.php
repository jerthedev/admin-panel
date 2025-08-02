<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Installation Feature Tests
 *
 * Tests for admin panel installation process including
 * asset publishing, service provider registration, and Laravel 12 compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class InstallationTest extends TestCase
{
    public function test_assets_can_be_published_successfully(): void
    {
        // Test that asset publishing works without path errors
        $exitCode = Artisan::call('vendor:publish', [
            '--tag' => 'admin-panel-assets',
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode, 'Asset publishing should succeed without errors');

        // Verify published assets exist
        $this->assertTrue(
            File::exists(public_path('vendor/admin-panel')),
            'Published assets directory should exist'
        );
    }

    public function test_package_has_prebuilt_assets(): void
    {
        // Test that the package includes pre-built assets
        // In dev environment, check the actual package location
        $packagePath = __DIR__ . '/../../public/build';

        $this->assertTrue(
            File::exists($packagePath),
            'Package should include pre-built assets in public/build directory'
        );

        // Verify assets actually exist
        $this->assertTrue(
            count(File::files($packagePath . '/assets')) > 0,
            'Pre-built assets should exist in public/build/assets directory'
        );
    }

    public function test_admin_panel_resources_method_exists_and_callable(): void
    {
        // Test that AdminPanel::resources() can be called without static method errors
        $adminPanel = app(\JTD\AdminPanel\Support\AdminPanel::class);

        // This should not throw a "non-static method called statically" error
        $this->assertTrue(
            method_exists($adminPanel, 'resources'),
            'AdminPanel should have resources method'
        );

        // Test calling the method doesn't throw errors
        try {
            $adminPanel->resources([]);
            $this->assertTrue(true, 'resources() method should be callable');
        } catch (\Error $e) {
            $this->fail('resources() method should not throw static method call errors: ' . $e->getMessage());
        }
    }

    public function test_service_provider_can_be_registered_without_errors(): void
    {
        // Test that AdminServiceProvider can be registered without causing artisan failures
        // Skip this test since AdminServiceProvider doesn't exist in package dev environment
        $this->markTestSkipped('AdminServiceProvider only exists in consuming applications');
    }

    /**
     * COMPREHENSIVE TEST: All three critical installation issues resolved
     *
     * This test verifies that all reported installation issues are fixed:
     * - Issue #1: Pre-built assets are included and publishable
     * - Issue #2: Laravel 12 documentation is correct (tested via reflection)
     * - Issue #3: AdminPanel::resources() can be called statically
     */
    public function test_all_critical_installation_issues_are_resolved(): void
    {
        // Issue #1: Verify pre-built assets exist and can be published
        $packageAssetsPath = __DIR__ . '/../../public/build';
        $this->assertTrue(
            File::exists($packageAssetsPath),
            'Issue #1: Package should include pre-built assets directory'
        );

        $this->assertTrue(
            count(File::files($packageAssetsPath . '/assets')) > 0,
            'Issue #1: Pre-built assets should exist in assets directory'
        );

        // Test asset publishing works
        $exitCode = Artisan::call('vendor:publish', [
            '--tag' => 'admin-panel-assets',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'Issue #1: Asset publishing should succeed');

        // Issue #3: Verify AdminPanel::resources() is static and callable
        $reflection = new \ReflectionMethod(\JTD\AdminPanel\Support\AdminPanel::class, 'resources');
        $this->assertTrue(
            $reflection->isStatic(),
            'Issue #3: AdminPanel::resources() should be static method'
        );

        // Test static call works without errors
        try {
            \JTD\AdminPanel\Support\AdminPanel::resources([]);
            $this->assertTrue(true, 'Issue #3: Static call to resources() should work');
        } catch (\Error $e) {
            $this->fail('Issue #3: Static call failed: ' . $e->getMessage());
        }

        // Issue #2: Verify install command shows correct Laravel 12 syntax
        // This is tested by checking the InstallCommand source contains bootstrap/app.php
        $installCommandPath = __DIR__ . '/../../src/Console/Commands/InstallCommand.php';
        $installCommandContent = File::get($installCommandPath);

        $this->assertStringContains(
            'bootstrap/app.php',
            $installCommandContent,
            'Issue #2: Install command should reference bootstrap/app.php for Laravel 12'
        );

        $this->assertStringContains(
            '->withProviders([',
            $installCommandContent,
            'Issue #2: Install command should show Laravel 12 provider syntax'
        );

        // Verify old config/app.php reference is removed
        $this->assertStringNotContainsString(
            'config/app.php',
            $installCommandContent,
            'Issue #2: Install command should not reference old config/app.php'
        );
    }
}
