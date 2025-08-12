<?php

declare(strict_types=1);

namespace JTD\AdminPanel;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use JTD\AdminPanel\Console\Commands\CheckCommand;
use JTD\AdminPanel\Console\Commands\ClearCacheCommand;
use JTD\AdminPanel\Console\Commands\CreateUserCommand;
use JTD\AdminPanel\Console\Commands\DoctorCommand;
use JTD\AdminPanel\Console\Commands\InstallCommand;
use JTD\AdminPanel\Console\Commands\ListResourcesCommand;
use JTD\AdminPanel\Console\Commands\MakeFieldCommand;
use JTD\AdminPanel\Console\Commands\MakePageCommand;
use JTD\AdminPanel\Console\Commands\MakeResourceCommand;
use JTD\AdminPanel\Console\Commands\RebuildAssetsCommand;
use JTD\AdminPanel\Console\Commands\SetupCustomPagesCommand;
use JTD\AdminPanel\Console\Commands\SetupHybridAssetsCommand;
use JTD\AdminPanel\Console\Commands\UninstallCommand;
use JTD\AdminPanel\Http\Middleware\AdminAuthenticate;
use JTD\AdminPanel\Http\Middleware\AdminAuthorize;
use JTD\AdminPanel\Http\Middleware\HandleAdminInertiaRequests;
use JTD\AdminPanel\Http\Middleware\TestOnlyMiddleware;
use JTD\AdminPanel\Support\AdminPanel;
use Tightenco\Ziggy\ZiggyServiceProvider;

/**
 * AdminPanel Service Provider
 *
 * Handles package registration, configuration publishing, routes,
 * middleware, and Inertia.js integration for the admin panel.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel
 */
class AdminPanelServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/admin-panel.php',
            'admin-panel'
        );

        $this->app->singleton(AdminPanel::class, function ($app) {
            return new AdminPanel();
        });

        $this->app->alias(AdminPanel::class, 'admin-panel');

        // Register the CustomPageManifestRegistry singleton
        $this->app->singleton(\JTD\AdminPanel\Support\CustomPageManifestRegistry::class, function ($app) {
            return new \JTD\AdminPanel\Support\CustomPageManifestRegistry();
        });

        // Ziggy will be auto-discovered if installed
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootPublishing();
        $this->bootRoutes();
        $this->bootMiddleware();
        $this->bootCommands();
        $this->bootInertia();
        $this->bootViews();
        $this->bootPolicies();
        $this->bootMediaLibrary();
    }

    /**
     * Boot publishing of package assets and configuration.
     */
    protected function bootPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/admin-panel.php' => config_path('admin-panel.php'),
            ], 'admin-panel-config');

            // Publish pre-built assets (primary method for self-contained package)
            $this->publishes([
                __DIR__ . '/../public/build' => public_path('vendor/admin-panel'),
            ], 'admin-panel-assets');

            // Publish source assets (optional, for development/customization)
            $this->publishes([
                __DIR__ . '/../resources/js' => resource_path('js/vendor/admin-panel'),
                __DIR__ . '/../resources/css' => resource_path('css/vendor/admin-panel'),
            ], 'admin-panel-source');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/admin-panel'),
            ], 'admin-panel-views');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'admin-panel-migrations');

            // Publish everything
            $this->publishes([
                __DIR__ . '/../config/admin-panel.php' => config_path('admin-panel.php'),
                __DIR__ . '/../resources/js' => resource_path('js/vendor/admin-panel'),
                __DIR__ . '/../resources/css' => resource_path('css/vendor/admin-panel'),
                __DIR__ . '/../resources/views' => resource_path('views/vendor/admin-panel'),
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'admin-panel');
        }
    }

    /**
     * Boot package routes.
     */
    protected function bootRoutes(): void
    {
        // Web routes - only apply web middleware globally, let route groups handle auth
        Route::group([
            'prefix' => config('admin-panel.path', 'admin'),
            'as' => 'admin-panel.',
            'middleware' => ['web'], // Only web middleware globally
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });

        // API routes - only apply api middleware globally
        Route::group([
            'prefix' => config('admin-panel.path', 'admin') . '/api',
            'as' => 'admin-panel.api.',
            'middleware' => ['api'], // Only api middleware globally
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });

        // Test routes - only loaded in testing environments
        $this->bootTestRoutes();

        // Register custom page routes after all service providers have booted
        $this->app->booted(function () {
            $this->registerPageRoutes();
        });
    }

    /**
     * Boot test routes (only in testing environments).
     */
    protected function bootTestRoutes(): void
    {
        // Only load test routes in testing environments
        if ($this->isTestingEnvironment()) {
            Route::group([
                'prefix' => config('admin-panel.path', 'admin') . '/api',
                'as' => 'admin-panel.api.',
                'middleware' => ['api'],
            ], function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/test.php');
            });
        }
    }

    /**
     * Check if we're in a testing environment.
     */
    protected function isTestingEnvironment(): bool
    {
        $environment = app()->environment();

        return in_array($environment, ['testing', 'local']) ||
               config('admin-panel.enable_test_endpoints', false) ||
               env('ADMIN_PANEL_TEST_ENDPOINTS', false);
    }

    /**
     * Register routes for custom pages.
     */
    protected function registerPageRoutes(): void
    {
        Route::group([
            'prefix' => config('admin-panel.path', 'admin'),
            'as' => 'admin-panel.',
            'middleware' => ['web', 'admin.inertia', 'admin.auth'],
        ], function () {
            $adminPanel = app(AdminPanel::class);
            $adminPanel->registerPageRoutes();
        });
    }

    /**
     * Boot middleware registration.
     */
    protected function bootMiddleware(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('admin.auth', AdminAuthenticate::class);
        $router->aliasMiddleware('admin.authorize', AdminAuthorize::class);
        $router->aliasMiddleware('admin.inertia', HandleAdminInertiaRequests::class);
        $router->aliasMiddleware('test-only', TestOnlyMiddleware::class);
    }

    /**
     * Boot artisan commands.
     */
    protected function bootCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckCommand::class,
                ClearCacheCommand::class,
                CreateUserCommand::class,
                DoctorCommand::class,
                InstallCommand::class,
                ListResourcesCommand::class,
                MakeFieldCommand::class,
                MakePageCommand::class,
                MakeResourceCommand::class,
                RebuildAssetsCommand::class,
                SetupCustomPagesCommand::class,
                SetupHybridAssetsCommand::class,
                UninstallCommand::class,
            ]);
        }
    }

    /**
     * Boot policy registration.
     */
    protected function bootPolicies(): void
    {
        // Register default policies if they exist
        $policies = config('admin-panel.policies', []);

        foreach ($policies as $model => $policy) {
            if (class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }

    /**
     * Boot Inertia.js configuration.
     */
    protected function bootInertia(): void
    {
        // Inertia configuration is now handled by HandleAdminInertiaRequests middleware
        // This ensures proper isolation between main app and admin panel Inertia setups
    }

    /**
     * Boot view configuration.
     */
    protected function bootViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'admin-panel');
    }

    /**
     * Boot Media Library configuration.
     */
    protected function bootMediaLibrary(): void
    {
        // Configure default media collections and conversions
        $this->configureDefaultMediaConversions();

        // Set up automatic media cleanup
        $this->configureMediaCleanup();

        // Register media library disk configuration
        $this->configureMediaDisks();
    }

    /**
     * Configure default media conversions for admin panel fields.
     */
    protected function configureDefaultMediaConversions(): void
    {
        // Get default conversions from config
        $defaultConversions = config('admin-panel.media_library.default_conversions', [
            'thumb' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
            'medium' => ['width' => 500, 'height' => 500, 'fit' => 'contain'],
            'large' => ['width' => 1200, 'height' => 1200, 'quality' => 90],
        ]);

        // Avatar-specific conversions
        $avatarConversions = config('admin-panel.media_library.avatar_conversions', [
            'thumb' => ['width' => 64, 'height' => 64, 'fit' => 'crop'],
            'medium' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
            'large' => ['width' => 400, 'height' => 400, 'fit' => 'crop'],
        ]);

        // Store conversions in config for field classes to use
        config([
            'admin-panel.media_library.conversions.default' => $defaultConversions,
            'admin-panel.media_library.conversions.avatar' => $avatarConversions,
        ]);
    }

    /**
     * Configure automatic media cleanup.
     */
    protected function configureMediaCleanup(): void
    {
        // Enable automatic cleanup when models are deleted
        if (config('admin-panel.media_library.auto_cleanup', true)) {
            // This would typically be handled by model observers
            // For now, we'll document this as a feature that needs to be implemented
            // in the model classes that use HasMedia trait
        }
    }

    /**
     * Configure media library disk settings.
     */
    protected function configureMediaDisks(): void
    {
        // Set default disk for media library if not configured
        $defaultDisk = config('admin-panel.media_library.default_disk', 'public');

        // Ensure the disk exists in filesystem config
        if (!config("filesystems.disks.{$defaultDisk}")) {
            // Log warning but don't fail - let the application handle this
            if (app()->hasDebugModeEnabled()) {
                logger()->warning("Admin Panel: Media Library disk '{$defaultDisk}' not found in filesystem configuration");
            }
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            AdminPanel::class,
            'admin-panel',
        ];
    }
}
