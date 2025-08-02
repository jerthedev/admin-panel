<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Export Action
 * 
 * Bulk export action for exporting resources to various formats
 * including CSV, JSON, and Excel.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Actions
 */
class ExportAction extends Action
{
    /**
     * The action's name.
     */
    public string $name = 'Export';

    /**
     * The action's URI key.
     */
    public string $uriKey = 'export';

    /**
     * The action's icon.
     */
    public ?string $icon = 'ArrowDownTrayIcon';

    /**
     * The export format.
     */
    protected string $format = 'csv';

    /**
     * The export filename.
     */
    protected ?string $filename = null;

    /**
     * The fields to export.
     */
    protected array $fields = [];

    /**
     * Custom field transformers.
     */
    protected array $transformers = [];

    /**
     * Whether to include headers in CSV export.
     */
    protected bool $includeHeaders = true;

    /**
     * Create a new export action instance.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->successMessage = 'Export completed successfully.';
        $this->errorMessage = 'Failed to export resources.';
        $this->withTransaction = false; // Exports don't need transactions
    }

    /**
     * Perform the action on the given models.
     */
    public function handle(Collection $models, Request $request): array
    {
        if ($models->isEmpty()) {
            return $this->error('No resources selected for export.');
        }

        try {
            $filename = $this->generateFilename();
            $data = $this->prepareData($models);

            switch ($this->format) {
                case 'csv':
                    $this->exportToCsv($data, $filename);
                    break;
                case 'json':
                    $this->exportToJson($data, $filename);
                    break;
                case 'excel':
                    $this->exportToExcel($data, $filename);
                    break;
                default:
                    return $this->error("Unsupported export format: {$this->format}");
            }

            $downloadUrl = Storage::url("exports/{$filename}");
            
            return [
                'type' => 'success',
                'message' => "Export completed successfully. {$models->count()} resources exported.",
                'download' => $downloadUrl,
                'filename' => $filename,
            ];
        } catch (\Exception $e) {
            return $this->error("Export failed: {$e->getMessage()}");
        }
    }

    /**
     * Set the export format.
     */
    public function withFormat(string $format): static
    {
        $this->format = strtolower($format);
        
        return $this;
    }

    /**
     * Set the export filename.
     */
    public function withFilename(string $filename): static
    {
        $this->filename = $filename;
        
        return $this;
    }

    /**
     * Set the fields to export.
     */
    public function withFields(array $fields): static
    {
        $this->fields = $fields;
        
        return $this;
    }

    /**
     * Set field transformers.
     */
    public function withTransformers(array $transformers): static
    {
        $this->transformers = $transformers;
        
        return $this;
    }

    /**
     * Set whether to include headers.
     */
    public function withHeaders(bool $includeHeaders = true): static
    {
        $this->includeHeaders = $includeHeaders;
        
        return $this;
    }

    /**
     * Prepare data for export.
     */
    protected function prepareData(Collection $models): array
    {
        $data = [];
        $fields = $this->getExportFields($models->first());

        foreach ($models as $model) {
            $row = [];
            
            foreach ($fields as $field) {
                $value = $this->getFieldValue($model, $field);
                $row[$field] = $this->transformValue($field, $value);
            }
            
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Get the fields to export.
     */
    protected function getExportFields($model): array
    {
        if (! empty($this->fields)) {
            return $this->fields;
        }

        // Get fillable fields as default
        if (method_exists($model, 'getFillable')) {
            return $model->getFillable();
        }

        // Fallback to all attributes
        return array_keys($model->getAttributes());
    }

    /**
     * Get field value from model.
     */
    protected function getFieldValue($model, string $field)
    {
        if (str_contains($field, '.')) {
            // Handle nested relationships
            return data_get($model, $field);
        }

        return $model->getAttribute($field);
    }

    /**
     * Transform field value.
     */
    protected function transformValue(string $field, $value)
    {
        if (isset($this->transformers[$field])) {
            return call_user_func($this->transformers[$field], $value);
        }

        // Default transformations
        if ($value instanceof \Carbon\Carbon) {
            return $value->toISOString();
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * Export data to CSV.
     */
    protected function exportToCsv(array $data, string $filename): void
    {
        $csv = '';
        
        if ($this->includeHeaders && ! empty($data)) {
            $csv .= implode(',', array_map([$this, 'escapeCsvValue'], array_keys($data[0]))) . "\n";
        }

        foreach ($data as $row) {
            $csv .= implode(',', array_map([$this, 'escapeCsvValue'], array_values($row))) . "\n";
        }

        Storage::put("exports/{$filename}", $csv);
    }

    /**
     * Export data to JSON.
     */
    protected function exportToJson(array $data, string $filename): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        Storage::put("exports/{$filename}", $json);
    }

    /**
     * Export data to Excel (requires Laravel Excel package).
     */
    protected function exportToExcel(array $data, string $filename): void
    {
        // This would require the Laravel Excel package
        // For now, fall back to CSV
        $this->exportToCsv($data, str_replace('.xlsx', '.csv', $filename));
    }

    /**
     * Escape CSV value.
     */
    protected function escapeCsvValue($value): string
    {
        $value = (string) $value;
        
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }

    /**
     * Generate export filename.
     */
    protected function generateFilename(): string
    {
        if ($this->filename) {
            return $this->filename;
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        return "export_{$timestamp}.{$this->format}";
    }

    /**
     * Create a CSV export action.
     */
    public static function csv(): static
    {
        return (new static())
            ->withName('Export CSV')
            ->withUriKey('export-csv')
            ->withFormat('csv');
    }

    /**
     * Create a JSON export action.
     */
    public static function json(): static
    {
        return (new static())
            ->withName('Export JSON')
            ->withUriKey('export-json')
            ->withFormat('json');
    }

    /**
     * Create an Excel export action.
     */
    public static function excel(): static
    {
        return (new static())
            ->withName('Export Excel')
            ->withUriKey('export-excel')
            ->withFormat('excel');
    }
}
