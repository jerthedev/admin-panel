<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * User Count Metric.
 *
 * Displays the total number of users in the system with trend analysis
 * comparing to the previous period.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class UserCountMetric extends Metric
{
    /**
     * The metric's display name.
     */
    public string $name = 'Total Users';

    /**
     * The metric's icon.
     */
    protected string $icon = 'UsersIcon';

    /**
     * The metric's color scheme.
     */
    protected string $color = 'blue';

    /**
     * The metric's number format.
     */
    protected string $format = 'number';

    /**
     * The user model class.
     */
    protected string $userModel;

    /**
     * Create a new UserCountMetric instance.
     */
    public function __construct()
    {
        $this->userModel = config('auth.providers.users.model', \App\Models\User::class);
    }

    /**
     * Calculate the total number of users.
     */
    public function calculate(Request $request): int
    {
        $cacheKey = $this->getCacheKey($request);

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () {
            if (! class_exists($this->userModel)) {
                return 0;
            }

            return $this->userModel::count();
        });
    }

    /**
     * Calculate the user count for a specific period.
     */
    protected function calculateForPeriod(Carbon $start, Carbon $end, Request $request): int
    {
        $cacheKey = $this->getCacheKey($request, $start->format('Y-m-d').'_'.$end->format('Y-m-d'));

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($end) {
            if (! class_exists($this->userModel)) {
                return 0;
            }

            return $this->userModel::where('created_at', '<=', $end)->count();
        });
    }

    /**
     * Determine if the user is authorized to view this metric.
     */
    public function authorize(Request $request): bool
    {
        // Check if user has permission to view user metrics
        return $request->user()?->can('viewAny', $this->userModel) ?? true;
    }

    /**
     * Set the user model class.
     */
    public function withUserModel(string $model): static
    {
        $this->userModel = $model;

        return $this;
    }
}
