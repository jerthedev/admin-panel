<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use JTD\AdminPanel\Console\Commands\MakePageCommand;
use PHPUnit\Framework\TestCase;

/**
 * Make Page Command Test
 *
 * Tests the enhanced make-page command that creates both PHP page classes
 * and Vue components with multi-component support and auto-registration.
 */
class MakePageCommandTest extends TestCase
{
    protected Filesystem $files;
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->files = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/admin-panel-test-' . uniqid();
        $this->files->makeDirectory($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if ($this->files->exists($this->tempDir)) {
            $this->files->deleteDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    public function test_command_creates_single_component_page(): void
    {
        $command = new MakePageCommand($this->files);

        // Mock the command arguments
        $pageName = 'UserDashboard';
        $appPath = $this->tempDir . '/app';
        $resourcesPath = $this->tempDir . '/resources';

        $this->files->makeDirectory($appPath . '/Admin/Pages', 0755, true);
        $this->files->makeDirectory($resourcesPath . '/js/admin-pages', 0755, true);

        // Test single component creation
        $result = $command->createPage($pageName, $appPath, $resourcesPath);

        $this->assertTrue($result);

        // Check PHP class was created
        $phpFile = $appPath . '/Admin/Pages/UserDashboardPage.php';
        $this->assertFileExists($phpFile);

        // Check Vue component was created
        $vueFile = $resourcesPath . '/js/admin-pages/UserDashboard.vue';
        $this->assertFileExists($vueFile);

        // Verify PHP class content
        $phpContent = $this->files->get($phpFile);
        $this->assertStringContainsString('class UserDashboardPage extends Page', $phpContent);
        $this->assertStringContainsString('public static array $components = [\'Pages/UserDashboard\']', $phpContent);
        $this->assertStringContainsString('public static ?string $title = \'User Dashboard\'', $phpContent);
    }

    public function test_command_creates_multi_component_page(): void
    {
        $command = new MakePageCommand($this->files);

        $pageName = 'UserManagement';
        $components = ['Dashboard', 'Settings', 'Metrics'];
        $appPath = $this->tempDir . '/app';
        $resourcesPath = $this->tempDir . '/resources';

        $this->files->makeDirectory($appPath . '/Admin/Pages', 0755, true);
        $this->files->makeDirectory($resourcesPath . '/js/admin-pages', 0755, true);

        // Test multi-component creation
        $result = $command->createPage($pageName, $appPath, $resourcesPath, $components);

        $this->assertTrue($result);

        // Check PHP class was created
        $phpFile = $appPath . '/Admin/Pages/UserManagementPage.php';
        $this->assertFileExists($phpFile);

        // Check all Vue components were created
        foreach ($components as $component) {
            $vueFile = $resourcesPath . "/js/admin-pages/UserManagement{$component}.vue";
            $this->assertFileExists($vueFile);
        }

        // Verify PHP class content
        $phpContent = $this->files->get($phpFile);
        $this->assertStringContainsString('class UserManagementPage extends Page', $phpContent);
        $this->assertStringContainsString('public static array $components = [', $phpContent);
        $this->assertStringContainsString('\'Pages/UserManagementDashboard\'', $phpContent);
        $this->assertStringContainsString('\'Pages/UserManagementSettings\'', $phpContent);
        $this->assertStringContainsString('\'Pages/UserManagementMetrics\'', $phpContent);
    }

    public function test_command_generates_proper_vue_component_structure(): void
    {
        $command = new MakePageCommand($this->files);

        $pageName = 'TestPage';
        $appPath = $this->tempDir . '/app';
        $resourcesPath = $this->tempDir . '/resources';

        $this->files->makeDirectory($appPath . '/Admin/Pages', 0755, true);
        $this->files->makeDirectory($resourcesPath . '/js/admin-pages', 0755, true);

        $command->createPage($pageName, $appPath, $resourcesPath);

        $vueFile = $resourcesPath . '/js/admin-pages/TestPage.vue';
        $vueContent = $this->files->get($vueFile);

        // Check Vue component structure
        $this->assertStringContainsString('<template>', $vueContent);
        $this->assertStringContainsString('<script setup>', $vueContent);
        $this->assertStringContainsString('const props = defineProps({', $vueContent);
        $this->assertStringContainsString('page: {', $vueContent);
        $this->assertStringContainsString('fields: {', $vueContent);
        $this->assertStringContainsString('data: {', $vueContent);
    }

    public function test_command_validates_page_name(): void
    {
        $command = new MakePageCommand($this->files);

        // Test invalid page names
        $this->assertFalse($command->isValidPageName(''));
        $this->assertFalse($command->isValidPageName('123Invalid'));
        $this->assertFalse($command->isValidPageName('invalid-name'));
        $this->assertFalse($command->isValidPageName('invalid name'));

        // Test valid page names
        $this->assertTrue($command->isValidPageName('ValidPage'));
        $this->assertTrue($command->isValidPageName('UserDashboard'));
        $this->assertTrue($command->isValidPageName('SystemHealthCheck'));
    }

    public function test_command_validates_component_names(): void
    {
        $command = new MakePageCommand($this->files);

        // Test invalid component names
        $this->assertFalse($command->isValidComponentName(''));
        $this->assertFalse($command->isValidComponentName('123Invalid'));
        $this->assertFalse($command->isValidComponentName('invalid-name'));

        // Test valid component names
        $this->assertTrue($command->isValidComponentName('Dashboard'));
        $this->assertTrue($command->isValidComponentName('Settings'));
        $this->assertTrue($command->isValidComponentName('UserMetrics'));
    }

    public function test_command_prevents_duplicate_pages(): void
    {
        $command = new MakePageCommand($this->files);

        $pageName = 'DuplicatePage';
        $appPath = $this->tempDir . '/app';
        $resourcesPath = $this->tempDir . '/resources';

        $this->files->makeDirectory($appPath . '/Admin/Pages', 0755, true);
        $this->files->makeDirectory($resourcesPath . '/js/admin-pages', 0755, true);

        // Create page first time
        $result1 = $command->createPage($pageName, $appPath, $resourcesPath);
        $this->assertTrue($result1);

        // Try to create same page again
        $result2 = $command->createPage($pageName, $appPath, $resourcesPath, [], false); // force = false
        $this->assertFalse($result2);
    }

    public function test_command_can_force_overwrite_existing_pages(): void
    {
        $command = new MakePageCommand($this->files);

        $pageName = 'OverwritePage';
        $appPath = $this->tempDir . '/app';
        $resourcesPath = $this->tempDir . '/resources';

        $this->files->makeDirectory($appPath . '/Admin/Pages', 0755, true);
        $this->files->makeDirectory($resourcesPath . '/js/admin-pages', 0755, true);

        // Create page first time
        $command->createPage($pageName, $appPath, $resourcesPath);

        // Force overwrite
        $result = $command->createPage($pageName, $appPath, $resourcesPath, [], true); // force = true
        $this->assertTrue($result);
    }

    public function test_command_generates_proper_page_class_structure(): void
    {
        $command = new MakePageCommand($this->files);

        $pageName = 'StructureTest';
        $appPath = $this->tempDir . '/app';
        $resourcesPath = $this->tempDir . '/resources';

        $this->files->makeDirectory($appPath . '/Admin/Pages', 0755, true);
        $this->files->makeDirectory($resourcesPath . '/js/admin-pages', 0755, true);

        $command->createPage($pageName, $appPath, $resourcesPath);

        $phpFile = $appPath . '/Admin/Pages/StructureTestPage.php';
        $phpContent = $this->files->get($phpFile);

        // Check required methods and properties
        $this->assertStringContainsString('public function fields(Request $request): array', $phpContent);
        $this->assertStringContainsString('public function data(Request $request): array', $phpContent);
        $this->assertStringContainsString('public function actions(Request $request): array', $phpContent);
        $this->assertStringContainsString('public static function authorizedToViewAny(Request $request): bool', $phpContent);
        $this->assertStringContainsString('use Illuminate\Http\Request;', $phpContent);
        $this->assertStringContainsString('use JTD\AdminPanel\Pages\Page;', $phpContent);
    }

    public function test_command_can_auto_register_page(): void
    {
        $command = new MakePageCommand($this->files);

        $pageName = 'AutoRegisterTest';
        $appPath = $this->tempDir . '/app';
        $resourcesPath = $this->tempDir . '/resources';

        $this->files->makeDirectory($appPath . '/Admin/Pages', 0755, true);
        $this->files->makeDirectory($resourcesPath . '/js/admin-pages', 0755, true);
        $this->files->makeDirectory($appPath . '/Providers', 0755, true);

        // Create a basic AdminPanelServiceProvider
        $serviceProviderContent = <<<PHP
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use JTD\AdminPanel\Support\AdminPanel;

class AdminPanelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register custom admin pages
        app(AdminPanel::class)->pages([
            // Add your custom page classes here
        ]);
    }
}
PHP;
        $this->files->put($appPath . '/Providers/AdminPanelServiceProvider.php', $serviceProviderContent);

        // Test auto-registration
        $result = $command->autoRegisterPage($pageName, $appPath);
        $this->assertTrue($result);

        // Check that the page was registered
        $updatedContent = $this->files->get($appPath . '/Providers/AdminPanelServiceProvider.php');
        $this->assertStringContainsString('use JTD\AdminPanel\Support\AdminPanel;', $updatedContent);
        $this->assertStringContainsString('app(AdminPanel::class)->pages([', $updatedContent);
        $this->assertStringContainsString('\\App\\Admin\\Pages\\AutoRegisterTestPage::class,', $updatedContent);
    }

    public function test_command_updates_existing_page_registration(): void
    {
        $command = new MakePageCommand($this->files);

        $pageName = 'UpdateTest';
        $appPath = $this->tempDir . '/app';

        $this->files->makeDirectory($appPath . '/Providers', 0755, true);

        // Create AdminPanelServiceProvider with existing page registration
        $serviceProviderContent = <<<PHP
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use JTD\AdminPanel\Support\AdminPanel;

class AdminPanelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        app(AdminPanel::class)->pages([
            \\App\\Admin\\Pages\\ExistingPage::class,
        ]);
    }
}
PHP;
        $this->files->put($appPath . '/Providers/AdminPanelServiceProvider.php', $serviceProviderContent);

        // Test updating existing registration
        $result = $command->autoRegisterPage($pageName, $appPath);
        $this->assertTrue($result);

        // Check that the new page was added
        $updatedContent = $this->files->get($appPath . '/Providers/AdminPanelServiceProvider.php');
        $this->assertStringContainsString('\\App\\Admin\\Pages\\ExistingPage::class,', $updatedContent);
        $this->assertStringContainsString('\\App\\Admin\\Pages\\UpdateTestPage::class,', $updatedContent);
    }
}
