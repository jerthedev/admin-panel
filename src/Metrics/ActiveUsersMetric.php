<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Active Users Metric.
 *
 * Displays the number of users who have been active within a specified
 * time period (default: last 30 days).
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ActiveUsersMetric extends Metric
{
    /**
     * The metric's display name.
     */
    public string $name = 'Active Users';

    /**
     * The metric's icon.
     */
    protected string $icon = 'UserGroupIcon';

    /**
     * The metric's color scheme.
     */
    protected string $color = 'purple';

    /**
     * The metric's number format.
     */
    protected string $format = 'number';

    /**
     * The activity period in days.
     */
    protected int $activityPeriod = 30;

    /**
     * The user model class.
     */
    protected string $userModel;

    /**
     * The column to check for activity.
     */
    protected string $activityColumn = 'last_login_at';

    /**
     * Create a new ActiveUsersMetric instance.
     */
    public function __construct()
    {
        $this->userModel = config('auth.providers.users.model', \App\Models\User::class);
    }

    /**
     * Calculate the number of active users.
     */
    public function calculate(Request $request): int
    {
        $cacheKey = $this->getCacheKey($request, (string) $this->activityPeriod);

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () {
            if (! class_exists($this->userModel)) {
                return 0;
            }

            $cutoffDate = Carbon::now()->subDays($this->activityPeriod);

            // Try to use the activity column if it exists
            $model = new $this->userModel;
            if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $this->activityColumn)) {
                return $this->userModel::where($this->activityColumn, '>=', $cutoffDate)->count();
            }

            // Fallback to updated_at if activity column doesn't exist
            return $this->userModel::where('updated_at', '>=', $cutoffDate)->count();
        });
    }

    /**
     * Calculate the active users for a specific period.
     */
    protected function calculateForPeriod(Carbon $start, Carbon $end, Request $request): int
    {
        $cacheKey = $this->getCacheKey($request, $start->format('Y-m-d').'_'.$end->format('Y-m-d').'_period');

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($end) {
            if (! class_exists($this->userModel)) {
                return 0;
            }

            $cutoffDate = $end->copy()->subDays($this->activityPeriod);

            // Try to use the activity column if it exists
            $model = new $this->userModel;
            if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $this->activityColumn)) {
                return $this->userModel::where($this->activityColumn, '>=', $cutoffDate)
                    ->where($this->activityColumn, '<=', $end)
                    ->count();
            }

            // Fallback to updated_at if activity column doesn't exist
            return $this->userModel::where('updated_at', '>=', $cutoffDate)
                ->where('updated_at', '<=', $end)
                ->count();
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
     * Set the activity period in days.
     */
    public function withActivityPeriod(int $days): static
    {
        $this->activityPeriod = $days;

        return $this;
    }

    /**
     * Set the user model class.
     */
    public function withUserModel(string $model): static
    {
        $this->userModel = $model;

        return $this;
    }

    /**
     * Set the activity column to check.
     */
    public function withActivityColumn(string $column): static
    {
        $this->activityColumn = $column;

        return $this;
    }
}
