<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * Handle Inertia Requests for Admin Panel.
 *
 * This middleware handles Inertia.js requests specifically for the admin panel,
 * ensuring proper root view and shared data for admin pages.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class HandleAdminInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'admin-panel::admin';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $guard = config('admin-panel.auth.guard', 'web');
        $user = auth()->guard($guard)->user();
        $adminPanel = app(AdminPanel::class);
        $navigationPages = $adminPanel->getNavigationPages($request);

        // Check if custom main menu is defined
        $customMenu = null;
        if (AdminPanel::hasCustomMainMenu()) {
            $customMenuItems = AdminPanel::resolveMainMenu($request);
            $customMenu = AdminPanel::serializeMainMenu($customMenuItems, $request);
        }

        // Check if custom user menu is defined
        $customUserMenu = null;
        if (AdminPanel::hasCustomUserMenu()) {
            $userMenuInstance = AdminPanel::resolveUserMenu($request);
            if ($userMenuInstance) {
                $customUserMenu = $userMenuInstance->toArray();
            }
        }

        $sharedData = array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin ?? false,
                ] : null,
            ],
            'config' => [
                'app_name' => config('admin-panel.name', 'Admin Panel'),
                'app_url' => config('app.url'),
                'admin_path' => config('admin-panel.path', '/admin'),
                'timezone' => config('app.timezone', 'UTC'),
            ],
            'customMainMenu' => $customMenu,
            'customUserMenu' => $customUserMenu,
            'resources' => $adminPanel->getNavigationResources()->map(function ($resource) use ($request) {
                $menuItem = $resource->menu($request);

                return [
                    'uriKey' => $resource::uriKey(),
                    'label' => $resource::label(),
                    'singularLabel' => $resource::singularLabel(),
                    'icon' => $menuItem->icon ?? $resource::$icon ?? 'DocumentTextIcon',
                    'group' => $resource::$group ?? 'Default',
                    'badge' => $menuItem->resolveBadge($request),
                    'badgeType' => $menuItem->badgeType,
                    'visible' => $menuItem->isVisible($request),
                    'meta' => $menuItem->meta,
                ];
            })->filter(function ($resource) {
                return $resource['visible'];
            })->values(),
            'dashboards' => $adminPanel->getNavigationDashboards($request)->map(function ($dashboard) use ($request) {
                $menuItem = $dashboard->menu($request);

                return [
                    'uriKey' => $dashboard->uriKey(),
                    'name' => $dashboard->name(),
                    'url' => $menuItem->url,
                    'icon' => $menuItem->icon ?? 'chart-bar',
                    'badge' => $menuItem->resolveBadge($request),
                    'badgeType' => $menuItem->badgeType,
                    'visible' => $menuItem->isVisible($request),
                    'showRefreshButton' => $dashboard->shouldShowRefreshButton(),
                    'meta' => $menuItem->meta,
                ];
            })->filter(function ($dashboard) {
                return $dashboard['visible'];
            })->values(),
            'pages' => $navigationPages->map(function ($page) use ($request) {
                $menuItem = $page->menu($request);

                return [
                    'component' => $page::component(),
                    'label' => $page::label(),
                    'icon' => $page::icon(),
                    'group' => $page::group() ?? 'Default',
                    'routeName' => $page::routeName(),
                    'uriPath' => $page::uriPath(),
                    'visible' => $menuItem->isVisible($request),
                ];
            })->filter(function ($page) {
                return $page['visible'];
            })->values(),
            'customPageManifests' => $adminPanel->getAggregatedManifest(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'errors' => function () use ($request) {
                return $request->session()->get('errors')
                    ? $request->session()->get('errors')->getBag('default')->getMessages()
                    : (object) [];
            },
        ]);

        return $sharedData;
    }
}
