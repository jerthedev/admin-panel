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
        $packagePath = base_path('vendor/jerthedev/admin-panel/public/build');
        
        $this->assertTrue(
            File::exists($packagePath),
            'Package should include pre-built assets in public/build directory'
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
        $provider = new \App\Providers\AdminServiceProvider(app());
        
        try {
            $provider->register();
            $provider->boot();
            $this->assertTrue(true, 'AdminServiceProvider should register without errors');
        } catch (\Error $e) {
            $this->fail('AdminServiceProvider registration should not fail: ' . $e->getMessage());
        }
    }
}