<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use JTD\AdminPanel\Pages\Page;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * Page Controller
 *
 * Handles custom admin panel pages with field rendering,
 * data binding, and authorization.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Http\Controllers
 */
class PageController extends Controller
{
    /**
     * Display a custom page.
     *
     * @param Request $request
     * @param string|null $component Optional component name for multi-component pages
     * @return Response
     */
    public function show(Request $request, string $component = null): Response
    {
        $routeName = $request->route()->getName();
        $adminPanel = app(AdminPanel::class);

        // Find the page by route name
        $page = $this->findPageByRouteName($adminPanel, $routeName);

        if (!$page) {
            abort(404, 'Page not found');
        }

        // Check authorization
        if (!$page::authorizedToViewAny($request)) {
            abort(403, 'Unauthorized');
        }

        // Get page data
        $pageInstance = new $page();

        // Handle multi-component routing
        $targetComponent = $this->resolveTargetComponent($page, $component);
        $isMultiComponent = $page::hasMultipleComponents();

        return Inertia::render($targetComponent, [
            'page' => [
                'title' => $page::label(),
                'icon' => $page::icon(),
                'group' => $page::group(),
                'slug' => $this->getPageSlug($routeName),
                'isMultiComponent' => $isMultiComponent,
                'components' => $isMultiComponent ? $page::components() : [$targetComponent],
                'currentComponent' => $targetComponent,
            ],
            'fields' => $this->resolveFields($pageInstance, $request),
            'actions' => $this->resolveActions($pageInstance, $request),
            'metrics' => $this->resolveMetrics($pageInstance, $request),
            'data' => $pageInstance->data($request),
            'multiComponent' => $isMultiComponent ? [
                'availableComponents' => $page::components(),
                'currentComponent' => $targetComponent,
                'primaryComponent' => $page::primaryComponent(),
                'componentUrls' => $this->generateComponentUrls($page, $routeName),
            ] : null,
        ]);
    }

    /**
     * Find a page class by its route name.
     *
     * @param AdminPanel $adminPanel
     * @param string $routeName
     * @return string|null
     */
    protected function findPageByRouteName(AdminPanel $adminPanel, string $routeName): ?string
    {
        $pages = $adminPanel->getPages();

        return $pages->first(function (string $pageClass) use ($routeName) {
            return $pageClass::routeName() === $routeName;
        });
    }

    /**
     * Resolve fields for the page.
     *
     * @param Page $page
     * @param Request $request
     * @return array
     */
    protected function resolveFields(Page $page, Request $request): array
    {
        $fields = $page->fields($request);

        return collect($fields)->map(function ($field) use ($request) {
            return $field->jsonSerialize();
        })->toArray();
    }

    /**
     * Resolve actions for the page.
     *
     * @param Page $page
     * @param Request $request
     * @return array
     */
    protected function resolveActions(Page $page, Request $request): array
    {
        $actions = $page->actions($request);

        return collect($actions)->map(function ($action) use ($request) {
            return $action->jsonSerialize();
        })->toArray();
    }

    /**
     * Resolve metrics for the page.
     *
     * @param Page $page
     * @param Request $request
     * @return array
     */
    protected function resolveMetrics(Page $page, Request $request): array
    {
        $metrics = $page->metrics($request);

        return collect($metrics)->map(function ($metric) use ($request) {
            if (method_exists($metric, 'authorize') && !$metric->authorize($request)) {
                return null;
            }

            return [
                'name' => $metric->name(),
                'value' => $metric->calculate($request),
                'format' => $metric->format(),
                'icon' => $metric->icon(),
                'color' => $metric->color(),
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Resolve the target component for rendering.
     *
     * @param string $pageClass
     * @param string|null $component
     * @return string
     */
    protected function resolveTargetComponent(string $pageClass, ?string $component): string
    {
        $availableComponents = $pageClass::components();

        if ($component) {
            // Find component by name (case-insensitive)
            $targetComponent = collect($availableComponents)->first(function ($comp) use ($component) {
                return strtolower(basename($comp)) === strtolower($component);
            });

            if (!$targetComponent) {
                abort(404, "Component '{$component}' not found for this page");
            }

            return $targetComponent;
        }

        // Return primary component (first one)
        return $pageClass::primaryComponent();
    }

    /**
     * Get page slug from route name.
     *
     * @param string $routeName
     * @return string
     */
    protected function getPageSlug(string $routeName): string
    {
        return str_replace('admin.pages.', '', $routeName);
    }

    /**
     * Generate component URLs for multi-component pages.
     *
     * @param string $pageClass
     * @param string $routeName
     * @return array
     */
    protected function generateComponentUrls(string $pageClass, string $routeName): array
    {
        $components = $pageClass::components();
        $baseUrl = route($routeName);
        $urls = [];

        foreach ($components as $index => $component) {
            $componentName = basename($component);

            if ($index === 0) {
                // Primary component uses base URL
                $urls[$componentName] = $baseUrl;
            } else {
                // Other components get their own URLs
                $urls[$componentName] = $baseUrl . '/' . strtolower($componentName);
            }
        }

        return $urls;
    }
}
