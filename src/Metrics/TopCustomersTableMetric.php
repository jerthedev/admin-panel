<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Top Customers Table Metric.
 *
 * Example Table metric that demonstrates customer data display with tabular format.
 * Shows top customers by revenue with custom columns, actions, and formatting.
 *
 * This metric serves as a reference implementation for Table metrics, showcasing:
 * - Custom column definitions with sorting and formatting
 * - Row actions and links functionality
 * - Icons and custom formatting support
 * - Sorting and pagination capabilities
 * - Caching for performance optimization
 * - Configurable model support
 * - Range selection for time-based filtering
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TopCustomersTableMetric extends Table
{
    /**
     * The metric's display name.
     */
    public string $name = 'Top Customers';

    /**
     * The customer model class to query.
     */
    protected string $customerModel = 'App\Models\Customer';

    /**
     * The order model class to query.
     */
    protected string $orderModel = 'App\Models\Order';

    /**
     * Cache duration in minutes.
     */
    protected int $cacheMinutes = 15;

    /**
     * Default number of records to display.
     */
    protected int $defaultLimit = 10;

    /**
     * Calculate the table data for the metric.
     */
    public function calculate(Request $request): TableResult
    {
        // Use caching for performance
        $cacheKey = $this->getCacheKey($request);

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            return $this->getTopCustomersByRevenue($request);
        });
    }

    /**
     * Get top customers by total revenue.
     */
    public function getTopCustomersByRevenue(Request $request): TableResult
    {
        $limit = $request->get('limit', $this->defaultLimit);

        return $this->fromCustomQuery($request, function ($request, $range, $timezone) use ($limit) {
            // Get customers with their total revenue in the selected range
            $customerTable = (new ($this->customerModel))->getTable();
            $orderTable = (new ($this->orderModel))->getTable();

            $query = $this->buildQuery($this->customerModel)
                ->select([
                    $customerTable.'.id',
                    $customerTable.'.name',
                    $customerTable.'.email',
                    $customerTable.'.created_at',
                ])
                ->selectRaw("COUNT({$orderTable}.id) as order_count")
                ->selectRaw("COALESCE(SUM({$orderTable}.total), 0) as total_revenue")
                ->leftJoin($orderTable, $customerTable.'.id', '=', $orderTable.'.customer_id');

            // Apply date range to orders
            [$start, $end] = $this->calculateDateRange($range, $timezone);
            $query->whereBetween($orderTable.'.created_at', [$start, $end]);

            return $query
                ->groupBy($customerTable.'.id', $customerTable.'.name', $customerTable.'.email', $customerTable.'.created_at')
                ->orderByDesc('total_revenue')
                ->limit($limit)
                ->get();
        })
            ->columns([
                'name' => [
                    'label' => 'Customer Name',
                    'sortable' => true,
                    'width' => '200px',
                ],
                'email' => [
                    'label' => 'Email',
                    'sortable' => true,
                    'formatter' => fn ($value) => $this->truncateText($value, 30),
                ],
                'order_count' => [
                    'label' => 'Orders',
                    'sortable' => true,
                    'align' => 'center',
                    'width' => '100px',
                    'formatter' => fn ($value) => $this->formatNumber($value),
                ],
                'total_revenue' => [
                    'label' => 'Revenue',
                    'sortable' => true,
                    'align' => 'right',
                    'width' => '120px',
                    'formatter' => fn ($value) => $this->formatCurrency($value),
                ],
                'created_at' => [
                    'label' => 'Joined',
                    'sortable' => true,
                    'width' => '120px',
                    'formatter' => fn ($value) => $this->formatDate($value, 'M j, Y'),
                ],
            ])
            ->action('view', 'View Customer', [
                'icon' => 'eye',
                'color' => 'primary',
                'url' => '/admin/customers/{id}',
                'target' => '_self',
            ])
            ->action('orders', 'View Orders', [
                'icon' => 'shopping-bag',
                'color' => 'secondary',
                'url' => '/admin/orders?customer_id={id}',
                'target' => '_self',
            ])
            ->action('contact', 'Send Email', [
                'icon' => 'mail',
                'color' => 'success',
                'url' => 'mailto:{email}',
                'target' => '_blank',
                'condition' => fn ($row) => ! empty($row['email']),
            ])
            ->sortBy('total_revenue', 'desc')
            ->emptyText('No customers found for the selected period');
    }

    /**
     * Get recent customers table.
     */
    public function getRecentCustomers(Request $request): TableResult
    {
        $limit = $request->get('limit', $this->defaultLimit);

        return $this->recentRecords($request, $this->customerModel, $limit, [
            'name' => 'name',
            'email' => 'email',
            'created_at' => 'created_at',
        ])
            ->columns([
                'name' => [
                    'label' => 'Customer Name',
                    'sortable' => true,
                ],
                'email' => [
                    'label' => 'Email',
                    'sortable' => true,
                    'formatter' => fn ($value) => $this->truncateText($value, 30),
                ],
                'created_at' => [
                    'label' => 'Joined',
                    'sortable' => true,
                    'formatter' => fn ($value) => $this->formatDate($value, 'M j, Y H:i'),
                ],
            ])
            ->action('view', 'View Customer', [
                'icon' => 'eye',
                'color' => 'primary',
                'url' => '/admin/customers/{id}',
            ])
            ->sortBy('created_at', 'desc')
            ->emptyText('No recent customers found');
    }

    /**
     * Get customer activity summary table.
     */
    public function getCustomerActivitySummary(Request $request): TableResult
    {
        return $this->fromAggregation($request, $this->orderModel, 'status', 'count')
            ->columns([
                'group' => [
                    'label' => 'Order Status',
                    'sortable' => true,
                    'formatter' => fn ($value) => ucfirst(str_replace('_', ' ', $value)),
                ],
                'value' => [
                    'label' => 'Count',
                    'sortable' => true,
                    'align' => 'right',
                    'formatter' => fn ($value) => $this->formatNumber($value),
                ],
            ])
            ->sortBy('value', 'desc')
            ->emptyText('No order activity found');
    }

    /**
     * Get the ranges available for this metric.
     */
    public function ranges(): array
    {
        return [
            7 => '7 Days',
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            'MTD' => 'Month To Date',
            'QTD' => 'Quarter To Date',
            'YTD' => 'Year To Date',
        ];
    }

    /**
     * Determine the cache duration for this metric.
     */
    public function cacheFor(): int
    {
        return $this->cacheMinutes * 60; // Convert to seconds
    }

    /**
     * Set the customer model to query.
     */
    public function customerModel(string $model): static
    {
        $this->customerModel = $model;

        return $this;
    }

    /**
     * Set the order model to query.
     */
    public function orderModel(string $model): static
    {
        $this->orderModel = $model;

        return $this;
    }

    /**
     * Set the default limit for records.
     */
    public function defaultLimit(int $limit): static
    {
        $this->defaultLimit = $limit;

        return $this;
    }

    /**
     * Set the cache duration in minutes.
     */
    public function cacheForMinutes(int $minutes): static
    {
        $this->cacheMinutes = $minutes;

        return $this;
    }

    /**
     * Generate a cache key for the metric.
     */
    protected function getCacheKey(Request $request, string $suffix = ''): string
    {
        $range = $request->get('range', 30);
        $limit = $request->get('limit', $this->defaultLimit);
        $timezone = $request->get('timezone', config('app.timezone', 'UTC'));

        $key = sprintf(
            'admin_panel_metric_top_customers_%s_%s_%s_%s',
            md5($this->customerModel),
            $range,
            $limit,
            md5($timezone),
        );

        if ($suffix) {
            $key .= '_'.$suffix;
        }

        return $key;
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'top-customers';
    }

    /**
     * Determine if the metric should be displayed.
     */
    public function authorize(Request $request): bool
    {
        // Only show to users who can view customer data
        return $request->user()?->can('viewAny', $this->customerModel) ?? false;
    }

    /**
     * Get help text for the metric.
     */
    public function help(): ?string
    {
        return 'Shows the top customers by revenue with detailed information and actions.';
    }

    /**
     * Get the metric's icon.
     */
    public function icon(): string
    {
        return 'users';
    }

    /**
     * Get additional metadata for the metric.
     */
    public function meta(): array
    {
        return [
            'customer_model' => $this->customerModel,
            'order_model' => $this->orderModel,
            'default_limit' => $this->defaultLimit,
            'cache_minutes' => $this->cacheMinutes,
            'help' => $this->help(),
            'icon' => $this->icon(),
        ];
    }
}
