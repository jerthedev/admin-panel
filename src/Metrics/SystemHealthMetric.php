<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * System Health Metric.
 *
 * Displays the overall system health as a percentage based on
 * various system checks (database, cache, storage, etc.).
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class SystemHealthMetric extends Metric
{
    /**
     * The metric's display name.
     */
    public string $name = 'System Health';

    /**
     * The metric's icon.
     */
    protected string $icon = 'HeartIcon';

    /**
     * The metric's color scheme.
     */
    protected string $color = 'red';

    /**
     * The metric's number format.
     */
    protected string $format = 'percentage';

    /**
     * Whether to show trend information.
     */
    protected bool $showTrend = false;

    /**
     * Calculate the system health percentage.
     */
    public function calculate(Request $request): float
    {
        $cacheKey = $this->getCacheKey($request);

        return Cache::remember($cacheKey, 300, function () { // 5 minute cache
            $checks = [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
                'memory' => $this->checkMemory(),
            ];

            $totalChecks = count($checks);
            $passedChecks = array_sum($checks);

            return ($passedChecks / $totalChecks) * 100;
        });
    }

    /**
     * Check database connectivity.
     */
    protected function checkDatabase(): int
    {
        try {
            DB::connection()->getPdo();

            return 1;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check cache functionality.
     */
    protected function checkCache(): int
    {
        try {
            $testKey = 'admin_panel_health_check_'.time();
            $testValue = 'test';

            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            return $retrieved === $testValue ? 1 : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check storage accessibility.
     */
    protected function checkStorage(): int
    {
        try {
            $testFile = storage_path('app/admin_panel_health_check.txt');
            $testContent = 'health check';

            file_put_contents($testFile, $testContent);
            $retrieved = file_get_contents($testFile);
            unlink($testFile);

            return $retrieved === $testContent ? 1 : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check memory usage.
     */
    protected function checkMemory(): int
    {
        try {
            $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
            $memoryUsage = memory_get_usage(true);

            if ($memoryLimit === -1) {
                return 1; // No limit set
            }

            $usagePercentage = ($memoryUsage / $memoryLimit) * 100;

            return $usagePercentage < 90 ? 1 : 0; // Pass if under 90% usage
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Parse memory limit string to bytes.
     */
    protected function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return -1;
        }

        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Determine if the user is authorized to view this metric.
     */
    public function authorize(Request $request): bool
    {
        // Only show to admin users
        $user = $request->user();

        if (! $user) {
            return false;
        }

        // Check if user has admin role or is_admin field
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin');
        }

        if (isset($user->is_admin)) {
            return $user->is_admin;
        }

        // Default to true if no specific admin check is available
        return true;
    }

    /**
     * Override color based on health percentage.
     */
    public function color(): string
    {
        // This would need to be calculated dynamically in a real implementation
        // For now, return the base color
        return $this->color;
    }
}
