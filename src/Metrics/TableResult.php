<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use JsonSerializable;

/**
 * Table Result Class.
 *
 * Represents the result of a Table metric calculation, containing tabular data
 * with column definitions and optional actions. This class provides Nova-compatible
 * API for table metric results with support for sorting and pagination.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TableResult implements JsonSerializable
{
    /**
     * The table data rows.
     */
    protected array $data = [];

    /**
     * The table columns configuration.
     */
    protected array $columns = [];

    /**
     * The table actions.
     */
    protected array $actions = [];

    /**
     * The empty text to display when no data.
     */
    protected string $emptyText = 'No data available';

    /**
     * Whether the table supports sorting.
     */
    protected bool $sortable = true;

    /**
     * The default sort column.
     */
    protected ?string $defaultSort = null;

    /**
     * The default sort direction.
     */
    protected string $defaultSortDirection = 'asc';

    /**
     * Create a new table result instance.
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Create a new table result instance.
     */
    public static function make(array $data = []): static
    {
        return new static($data);
    }

    /**
     * Set the table data.
     */
    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Add a column to the table.
     */
    public function column(string $key, string $label, array $options = []): static
    {
        $this->columns[$key] = array_merge([
            'key' => $key,
            'label' => $label,
            'sortable' => true,
            'align' => 'left',
            'width' => null,
            'formatter' => null,
        ], $options);

        return $this;
    }

    /**
     * Set multiple columns at once.
     */
    public function columns(array $columns): static
    {
        foreach ($columns as $key => $config) {
            if (is_string($config)) {
                $this->column($key, $config);
            } else {
                $this->column($key, $config['label'] ?? $key, $config);
            }
        }

        return $this;
    }

    /**
     * Add an action to table rows.
     */
    public function action(string $key, string $label, array $options = []): static
    {
        $this->actions[$key] = array_merge([
            'key' => $key,
            'label' => $label,
            'icon' => null,
            'color' => 'primary',
            'url' => null,
            'target' => '_self',
            'condition' => null,
        ], $options);

        return $this;
    }

    /**
     * Set the empty text.
     */
    public function emptyText(string $text): static
    {
        $this->emptyText = $text;

        return $this;
    }

    /**
     * Disable sorting for the table.
     */
    public function withoutSorting(): static
    {
        $this->sortable = false;

        return $this;
    }

    /**
     * Set the default sort configuration.
     */
    public function sortBy(string $column, string $direction = 'asc'): static
    {
        $this->defaultSort = $column;
        $this->defaultSortDirection = $direction;

        return $this;
    }

    /**
     * Get the table data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the table columns.
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get the table actions.
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Check if the result has no data.
     */
    public function hasNoData(): bool
    {
        return empty($this->data);
    }

    /**
     * Format a cell value based on column configuration.
     */
    protected function formatCellValue(mixed $value, array $column): mixed
    {
        if (isset($column['formatter']) && is_callable($column['formatter'])) {
            return call_user_func($column['formatter'], $value);
        }

        return $value;
    }

    /**
     * Get formatted table data for display.
     */
    public function getFormattedData(): array
    {
        $formatted = [];

        foreach ($this->data as $rowIndex => $row) {
            $formattedRow = [];

            foreach ($this->columns as $columnKey => $column) {
                $value = $row[$columnKey] ?? null;
                $formattedRow[$columnKey] = $this->formatCellValue($value, $column);
            }

            // Add row actions
            $formattedRow['_actions'] = $this->getRowActions($row, $rowIndex);
            $formattedRow['_row_id'] = $rowIndex;

            $formatted[] = $formattedRow;
        }

        return $formatted;
    }

    /**
     * Get actions available for a specific row.
     */
    protected function getRowActions(array $row, int $rowIndex): array
    {
        $availableActions = [];

        foreach ($this->actions as $actionKey => $action) {
            // Check if action should be shown for this row
            if (isset($action['condition']) && is_callable($action['condition'])) {
                if (! call_user_func($action['condition'], $row, $rowIndex)) {
                    continue;
                }
            }

            $availableActions[] = [
                'key' => $actionKey,
                'label' => $action['label'],
                'icon' => $action['icon'],
                'color' => $action['color'],
                'url' => $this->buildActionUrl($action['url'], $row, $rowIndex),
                'target' => $action['target'],
            ];
        }

        return $availableActions;
    }

    /**
     * Build action URL with row data.
     */
    protected function buildActionUrl(?string $urlTemplate, array $row, int $rowIndex): ?string
    {
        if (! $urlTemplate) {
            return null;
        }

        // Replace placeholders in URL template
        $url = $urlTemplate;
        foreach ($row as $key => $value) {
            $url = str_replace('{'.$key.'}', (string) $value, $url);
        }

        return $url;
    }

    /**
     * Convert the result to an array for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->getFormattedData(),
            'columns' => $this->getColumns(),
            'actions' => $this->getActions(),
            'empty_text' => $this->emptyText,
            'sortable' => $this->sortable,
            'default_sort' => $this->defaultSort,
            'default_sort_direction' => $this->defaultSortDirection,
            'has_no_data' => $this->hasNoData(),
            'total_rows' => count($this->data),
        ];
    }
}
