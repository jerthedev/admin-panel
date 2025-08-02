<?php

declare(strict_types=1);

namespace JTD\AdminPanel;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use JTD\AdminPanel\Console\Commands\CheckCommand;
use JTD\AdminPanel\Console\Commands\ClearCacheCommand;
use JTD\AdminPanel\Console\Commands\CreateUserCommand;
use JTD\AdminPanel\Console\Commands\DoctorCommand;
use JTD\AdminPanel\Console\Commands\InstallCommand;
use JTD\AdminPanel\Console\Commands\ListResourcesCommand;
use JTD\AdminPanel\Console\Commands\MakeFieldCommand;
use JTD\AdminPanel\Console\Commands\MakeResourceCommand;
use JTD\AdminPanel\Console\Commands\RebuildAssetsCommand;
use JTD\AdminPanel\Console\Commands\UninstallCommand;
use JTD\AdminPanel\Http\Middleware\AdminAuthenticate;
use JTD\AdminPanel\Http\Middleware\HandleAdminInertiaRequests;
use JTD\AdminPanel\Http\Middleware\AdminAuthorize;
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
                MakeResourceCommand::class,
                RebuildAssetsCommand::class,
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
