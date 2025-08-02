<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Update Status Action
 *
 * Bulk status update action for changing the status of multiple
 * resources at once with validation and error handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Actions
 */
class UpdateStatusAction extends Action
{
    /**
     * The action's name.
     */
    public string $name = 'Update Status';

    /**
     * The action's URI key.
     */
    public string $uriKey = 'update-status';

    /**
     * The action's icon.
     */
    public ?string $icon = 'PencilSquareIcon';

    /**
     * The status field name.
     */
    protected string $statusField = 'status';

    /**
     * The new status value.
     */
    protected mixed $statusValue = null;

    /**
     * Available status options.
     */
    protected array $statusOptions = [];

    /**
     * Whether to validate the status value.
     */
    protected bool $validateStatus = true;

    /**
     * Create a new update status action instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->successMessage = 'Status updated successfully.';
        $this->errorMessage = 'Failed to update status.';
    }

    /**
     * Perform the action on the given models.
     */
    public function handle(Collection $models, Request $request): array
    {
        $statusValue = $this->getStatusValue($request);

        if ($statusValue === null) {
            return $this->error('No status value provided.');
        }

        if ($this->validateStatus && ! $this->isValidStatus($statusValue)) {
            return $this->error('Invalid status value provided.');
        }

        $updatedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($models as $model) {
            try {
                // Check if the model has the status field
                if (! $this->hasStatusField($model)) {
                    $failedCount++;
                    $errors[] = "Model {$model->getKey()} does not have a {$this->statusField} field.";
                    continue;
                }

                // Update the status
                $model->update([$this->statusField => $statusValue]);
                $updatedCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Failed to update {$model->getKey()}: {$e->getMessage()}";
            }
        }

        if ($failedCount === 0) {
            $statusLabel = $this->getStatusLabel($statusValue);
            $message = $updatedCount === 1
                ? "1 resource updated to {$statusLabel}."
                : "{$updatedCount} resources updated to {$statusLabel}.";

            return $this->success($message);
        }

        if ($updatedCount === 0) {
            return $this->error('Failed to update any resources. ' . implode(' ', $errors));
        }

        $message = "{$updatedCount} resources updated successfully, {$failedCount} failed.";
        return $this->warning($message);
    }

    /**
     * Set the status field name.
     */
    public function withStatusField(string $field): static
    {
        $this->statusField = $field;

        return $this;
    }

    /**
     * Set the status value.
     */
    public function withStatusValue(mixed $value): static
    {
        $this->statusValue = $value;

        return $this;
    }

    /**
     * Set the available status options.
     */
    public function withStatusOptions(array $options): static
    {
        $this->statusOptions = $options;

        return $this;
    }

    /**
     * Disable status validation.
     */
    public function withoutValidation(): static
    {
        $this->validateStatus = false;

        return $this;
    }

    /**
     * Get the status value from the request.
     */
    protected function getStatusValue(Request $request): mixed
    {
        return $this->statusValue ?? $request->get('status');
    }

    /**
     * Check if the status value is valid.
     */
    protected function isValidStatus(mixed $value): bool
    {
        if (empty($this->statusOptions)) {
            return true; // No validation if no options provided
        }

        return array_key_exists($value, $this->statusOptions) || in_array($value, $this->statusOptions);
    }

    /**
     * Check if the model has the status field.
     */
    protected function hasStatusField($model): bool
    {
        return $model->isFillable($this->statusField) ||
               array_key_exists($this->statusField, $model->getAttributes());
    }

    /**
     * Get the status label for display.
     */
    protected function getStatusLabel(mixed $value): string
    {
        if (isset($this->statusOptions[$value])) {
            return $this->statusOptions[$value];
        }

        return ucfirst(str_replace('_', ' ', (string) $value));
    }

    /**
     * Create an activate action.
     */
    public static function activate(): static
    {
        return (new static())
            ->withName('Activate')
            ->withUriKey('activate')
            ->withIcon('CheckCircleIcon')
            ->withStatusField('is_active')
            ->withStatusValue(true)
            ->withStatusOptions([
                true => 'Active',
                false => 'Inactive',
                1 => 'Active',
                0 => 'Inactive',
            ])
            ->withConfirmation('Are you sure you want to activate the selected resources?');
    }

    /**
     * Create a deactivate action.
     */
    public static function deactivate(): static
    {
        return (new static())
            ->withName('Deactivate')
            ->withUriKey('deactivate')
            ->withIcon('XCircleIcon')
            ->withStatusField('is_active')
            ->withStatusValue(false)
            ->withStatusOptions([
                true => 'Active',
                false => 'Inactive',
                1 => 'Active',
                0 => 'Inactive',
            ])
            ->withConfirmation('Are you sure you want to deactivate the selected resources?');
    }

    /**
     * Create a publish action.
     */
    public static function publish(): static
    {
        return (new static())
            ->withName('Publish')
            ->withUriKey('publish')
            ->withIcon('EyeIcon')
            ->withStatusField('is_published')
            ->withStatusValue(true)
            ->withConfirmation('Are you sure you want to publish the selected resources?');
    }

    /**
     * Create an unpublish action.
     */
    public static function unpublish(): static
    {
        return (new static())
            ->withName('Unpublish')
            ->withUriKey('unpublish')
            ->withIcon('EyeSlashIcon')
            ->withStatusField('is_published')
            ->withStatusValue(false)
            ->withConfirmation('Are you sure you want to unpublish the selected resources?');
    }

    /**
     * Create a feature action.
     */
    public static function feature(): static
    {
        return (new static())
            ->withName('Feature')
            ->withUriKey('feature')
            ->withIcon('StarIcon')
            ->withStatusField('is_featured')
            ->withStatusValue(true)
            ->withConfirmation('Are you sure you want to feature the selected resources?');
    }

    /**
     * Create an unfeature action.
     */
    public static function unfeature(): static
    {
        return (new static())
            ->withName('Remove Feature')
            ->withUriKey('unfeature')
            ->withIcon('StarIcon')
            ->withStatusField('is_featured')
            ->withStatusValue(false)
            ->withConfirmation('Are you sure you want to remove feature from the selected resources?');
    }
}
