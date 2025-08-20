<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * Resource Count Metric.
 *
 * Displays the total number of registered resources in the admin panel.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ResourceCountMetric extends Metric
{
    /**
     * The metric's display name.
     */
    public string $name = 'Admin Resources';

    /**
     * The metric's icon.
     */
    protected string $icon = 'CubeIcon';

    /**
     * The metric's color scheme.
     */
    protected string $color = 'green';

    /**
     * The metric's number format.
     */
    protected string $format = 'number';

    /**
     * Whether to show trend information.
     */
    protected bool $showTrend = false;

    /**
     * Calculate the total number of registered resources.
     */
    public function calculate(Request $request): int
    {
        $cacheKey = $this->getCacheKey($request);

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () {
            $adminPanel = app(AdminPanel::class);

            return $adminPanel->getResources()->count();
        });
    }

    /**
     * Determine if the user is authorized to view this metric.
     */
    public function authorize(Request $request): bool
    {
        // Only show to users who can access the admin panel
        return true;
    }
}
