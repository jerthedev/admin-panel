<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Resources\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * HasExport Trait.
 *
 * Provides comprehensive export functionality with multiple formats and customization
 * options for admin panel resources. Enables data export with filtering and formatting.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
trait HasExport
{
    /**
     * Whether export functionality is enabled for this resource.
     */
    public static bool $exportEnabled = true;

    /**
     * Available export formats.
     */
    public static array $exportFormats = [
        'csv' => 'CSV',
        'xlsx' => 'Excel',
        'json' => 'JSON',
        'xml' => 'XML',
        'pdf' => 'PDF',
    ];

    /**
     * Default export format.
     */
    public static string $defaultExportFormat = 'csv';

    /**
     * Maximum number of records to export at once.
     */
    public static int $maxExportRecords = 10000;

    /**
     * Whether to include timestamps in exports.
     */
    public static bool $includeTimestamps = true;

    /**
     * Whether to include soft deleted records in exports.
     */
    public static bool $includeTrashed = false;

    /**
     * Fields to exclude from exports.
     */
    public static array $excludeFromExport = ['password', 'remember_token'];

    /**
     * Custom field transformations for export.
     */
    public static array $exportTransformations = [];

    /**
     * Export resources with the specified parameters.
     */
    public static function exportResources(
        Request $request,
        ?string $format = null,
        array $options = [],
    ): array {
        if (! static::$exportEnabled) {
            return [
                'success' => false,
                'message' => 'Export functionality is disabled for this resource.',
                'data' => null,
            ];
        }

        $format = $format ?? static::$defaultExportFormat;

        if (! array_key_exists($format, static::$exportFormats)) {
            return [
                'success' => false,
                'message' => "Unsupported export format: {$format}",
                'data' => null,
            ];
        }

        try {
            $resources = static::getExportData($request, $options);
            $exportData = static::formatExportData($resources, $format, $options);
            $filename = static::generateExportFilename($format, $options);

            return [
                'success' => true,
                'message' => 'Export completed successfully.',
                'data' => $exportData,
                'filename' => $filename,
                'format' => $format,
                'count' => $resources->count(),
                'generated_at' => now()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Export failed: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Get the data to be exported.
     */
    protected static function getExportData(Request $request, array $options): Collection
    {
        $query = static::newModel()->newQuery();

        // Apply filters from request
        if ($search = $request->get('search')) {
            $query = static::applySearchToQuery($query, $search);
        }

        if ($filters = $request->get('filters')) {
            $query = static::applyFiltersToQuery($query, $filters);
        }

        // Apply soft delete handling
        if (static::$includeTrashed && method_exists($query->getModel(), 'trashed')) {
            $query->withTrashed();
        }

        // Apply custom scopes
        if (isset($options['scopes'])) {
            foreach ($options['scopes'] as $scope => $parameters) {
                $query->{$scope}(...$parameters);
            }
        }

        // Apply ordering
        $orderBy = $request->get('orderBy', 'id');
        $orderDirection = $request->get('orderByDirection', 'asc');
        $query->orderBy($orderBy, $orderDirection);

        // Apply limit
        $limit = min($request->get('limit', static::$maxExportRecords), static::$maxExportRecords);
        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Format the export data according to the specified format.
     */
    protected static function formatExportData(
        Collection $resources,
        string $format,
        array $options,
    ): string {
        $data = static::transformExportData($resources, $options);

        return match ($format) {
            'csv' => static::formatAsCsv($data, $options),
            'xlsx' => static::formatAsExcel($data, $options),
            'json' => static::formatAsJson($data, $options),
            'xml' => static::formatAsXml($data, $options),
            'pdf' => static::formatAsPdf($data, $options),
            default => static::formatAsCsv($data, $options),
        };
    }

    /**
     * Transform the raw data for export.
     */
    protected static function transformExportData(Collection $resources, array $options): array
    {
        return $resources->map(function ($resource) use ($options) {
            $data = $resource->toArray();

            // Remove excluded fields
            foreach (static::$excludeFromExport as $field) {
                unset($data[$field]);
            }

            // Remove timestamps if not included
            if (! static::$includeTimestamps) {
                unset($data['created_at'], $data['updated_at'], $data['deleted_at']);
            }

            // Apply custom transformations
            foreach (static::$exportTransformations as $field => $transformation) {
                if (isset($data[$field])) {
                    $data[$field] = call_user_func($transformation, $data[$field], $resource);
                }
            }

            // Apply field selection if specified
            if (isset($options['fields']) && is_array($options['fields'])) {
                $data = array_intersect_key($data, array_flip($options['fields']));
            }

            return $data;
        })->toArray();
    }

    /**
     * Format data as CSV.
     */
    protected static function formatAsCsv(array $data, array $options): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // Write headers
        $headers = array_keys($data[0]);
        fputcsv($output, $headers);

        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Format data as Excel (simplified implementation).
     */
    protected static function formatAsExcel(array $data, array $options): string
    {
        // In a real implementation, this would use a library like PhpSpreadsheet
        // For now, we'll return CSV format as a placeholder
        return static::formatAsCsv($data, $options);
    }

    /**
     * Format data as JSON.
     */
    protected static function formatAsJson(array $data, array $options): string
    {
        $jsonOptions = JSON_PRETTY_PRINT;

        if (isset($options['json_options'])) {
            $jsonOptions = $options['json_options'];
        }

        return json_encode($data, $jsonOptions);
    }

    /**
     * Format data as XML.
     */
    protected static function formatAsXml(array $data, array $options): string
    {
        $xml = new \SimpleXMLElement('<resources/>');

        foreach ($data as $item) {
            $resource = $xml->addChild('resource');

            foreach ($item as $key => $value) {
                $resource->addChild($key, htmlspecialchars((string) $value));
            }
        }

        return $xml->asXML();
    }

    /**
     * Format data as PDF (simplified implementation).
     */
    protected static function formatAsPdf(array $data, array $options): string
    {
        // In a real implementation, this would use a library like TCPDF or DOMPDF
        // For now, we'll return a simple text representation
        $output = "PDF Export\n\n";

        if (! empty($data)) {
            $headers = array_keys($data[0]);
            $output .= implode(' | ', $headers)."\n";
            $output .= str_repeat('-', strlen(implode(' | ', $headers)))."\n";

            foreach ($data as $row) {
                $output .= implode(' | ', $row)."\n";
            }
        }

        return $output;
    }

    /**
     * Generate a filename for the export.
     */
    protected static function generateExportFilename(string $format, array $options): string
    {
        $resourceName = static::getResourceName();
        $timestamp = now()->format('Y-m-d_H-i-s');

        $filename = "{$resourceName}_export_{$timestamp}.{$format}";

        if (isset($options['filename'])) {
            $filename = $options['filename'];

            if (! str_ends_with($filename, ".{$format}")) {
                $filename .= ".{$format}";
            }
        }

        return $filename;
    }

    /**
     * Get the resource name for filename generation.
     */
    protected static function getResourceName(): string
    {
        $className = class_basename(static::class);

        return strtolower(str_replace('Resource', '', $className));
    }

    /**
     * Get available export formats for the current user.
     */
    public static function getAvailableExportFormats(Request $request): array
    {
        if (! static::$exportEnabled) {
            return [];
        }

        $formats = [];

        foreach (static::$exportFormats as $key => $label) {
            if (static::canExportFormat($key, $request)) {
                $formats[$key] = [
                    'key' => $key,
                    'label' => $label,
                    'is_default' => $key === static::$defaultExportFormat,
                ];
            }
        }

        return $formats;
    }

    /**
     * Check if the user can export in a specific format.
     */
    protected static function canExportFormat(string $format, Request $request): bool
    {
        // Override in subclasses to implement format-specific authorization
        return true;
    }

    /**
     * Get export statistics.
     */
    public static function getExportStats(): array
    {
        return [
            'enabled' => static::$exportEnabled,
            'available_formats' => array_keys(static::$exportFormats),
            'default_format' => static::$defaultExportFormat,
            'max_records' => static::$maxExportRecords,
            'include_timestamps' => static::$includeTimestamps,
            'include_trashed' => static::$includeTrashed,
            'excluded_fields' => static::$excludeFromExport,
            'transformations_count' => count(static::$exportTransformations),
        ];
    }

    /**
     * Apply search to the export query.
     */
    protected static function applySearchToQuery($query, string $search)
    {
        // Override in subclasses to implement search logic
        return $query;
    }

    /**
     * Apply filters to the export query.
     */
    protected static function applyFiltersToQuery($query, array $filters)
    {
        // Override in subclasses to implement filter logic
        return $query;
    }

    /**
     * Validate export parameters.
     */
    protected static function validateExportParameters(Request $request, array $options): array
    {
        $errors = [];

        $limit = $request->get('limit', static::$maxExportRecords);
        if ($limit > static::$maxExportRecords) {
            $errors[] = 'Export limit cannot exceed '.static::$maxExportRecords.' records.';
        }

        if (isset($options['fields']) && ! is_array($options['fields'])) {
            $errors[] = 'Fields option must be an array.';
        }

        return $errors;
    }
}
