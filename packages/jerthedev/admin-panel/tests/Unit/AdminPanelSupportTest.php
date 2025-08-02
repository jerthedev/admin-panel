<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * AdminPanel Support Unit Tests
 *
 * Tests for the AdminPanel support class and installation process.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class AdminPanelSupportTest extends TestCase
{
    public function test_resources_method_signature_allows_static_calls(): void
    {
        $reflection = new \ReflectionMethod(AdminPanel::class, 'resources');

        $this->assertTrue(
            $reflection->isStatic(),
            'AdminPanel::resources() should be a static method to allow static calls'
        );
    }

    public function test_resources_method_accepts_array_parameter(): void
    {
        $testResources = [
            'App\Admin\Resources\UserResource',
            'App\Admin\Resources\PostResource',
        ];

        try {
            AdminPanel::resources($testResources);
            $this->assertTrue(true, 'resources() should accept array parameter');
        } catch (\TypeError $e) {
            $this->fail('resources() method should accept array parameter: ' . $e->getMessage());
        }
    }

    public function test_admin_panel_can_be_resolved_from_container(): void
    {
        $adminPanel = app(AdminPanel::class);

        $this->assertInstanceOf(
            AdminPanel::class,
            $adminPanel,
            'AdminPanel should be resolvable from service container'
        );
    }

    public function test_assets_can_be_published_successfully(): void
    {
        $exitCode = Artisan::call('vendor:publish', [
            '--tag' => 'admin-panel-assets',
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode, 'Asset publishing should succeed without errors');

        $this->assertTrue(
            File::exists(public_path('vendor/admin-panel')),
            'Published assets directory should exist'
        );
    }

    public function test_package_has_prebuilt_assets(): void
    {
        $packagePath = __DIR__ . '/../../public/build';

        $this->assertTrue(
            File::exists($packagePath),
            'Package should include pre-built assets in public/build directory'
        );
    }
}
