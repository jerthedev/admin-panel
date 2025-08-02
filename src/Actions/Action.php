<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Base Action Class
 *
 * Abstract base class for all admin panel actions providing common
 * functionality and interface for performing bulk operations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Actions
 */
abstract class Action
{
    /**
     * The action's name.
     */
    public string $name;

    /**
     * The action's URI key.
     */
    public string $uriKey;

    /**
     * The action's icon.
     */
    public ?string $icon = null;

    /**
     * The action's confirmation message.
     */
    public ?string $confirmationMessage = null;

    /**
     * Whether the action is destructive.
     */
    public bool $destructive = false;

    /**
     * Whether the action should run in a transaction.
     */
    public bool $withTransaction = true;

    /**
     * The action's success message.
     */
    public ?string $successMessage = null;

    /**
     * The action's error message.
     */
    public ?string $errorMessage = null;

    /**
     * Create a new action instance.
     */
    public function __construct()
    {
        $this->name = $this->name ?? $this->generateName();
        $this->uriKey = $this->uriKey ?? $this->generateUriKey();
    }

    /**
     * Create a new action instance.
     */
    public static function make(): static
    {
        return new static();
    }

    /**
     * Perform the action on the given models.
     */
    abstract public function handle(Collection $models, Request $request): array;

    /**
     * Determine if the action should be displayed.
     */
    public function authorize(Request $request): bool
    {
        return true;
    }

    /**
     * Get the action's name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Set the action's name.
     */
    public function withName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the action's URI key.
     */
    public function uriKey(): string
    {
        return $this->uriKey;
    }

    /**
     * Set the action's URI key.
     */
    public function withUriKey(string $uriKey): static
    {
        $this->uriKey = $uriKey;

        return $this;
    }

    /**
     * Get the action's icon.
     */
    public function icon(): ?string
    {
        return $this->icon;
    }

    /**
     * Set the action's icon.
     */
    public function withIcon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get the confirmation message.
     */
    public function confirmationMessage(): ?string
    {
        return $this->confirmationMessage;
    }

    /**
     * Set the confirmation message.
     */
    public function withConfirmation(string $message): static
    {
        $this->confirmationMessage = $message;

        return $this;
    }

    /**
     * Mark the action as destructive.
     */
    public function destructive(bool $destructive = true): static
    {
        $this->destructive = $destructive;

        return $this;
    }

    /**
     * Set whether to run in a transaction.
     */
    public function withTransaction(bool $withTransaction = true): static
    {
        $this->withTransaction = $withTransaction;

        return $this;
    }

    /**
     * Set the success message.
     */
    public function withSuccessMessage(string $message): static
    {
        $this->successMessage = $message;

        return $this;
    }

    /**
     * Set the error message.
     */
    public function withErrorMessage(string $message): static
    {
        $this->errorMessage = $message;

        return $this;
    }

    /**
     * Execute the action with proper error handling and transactions.
     */
    public function execute(Collection $models, Request $request): array
    {
        try {
            if ($this->withTransaction) {
                return DB::transaction(function () use ($models, $request) {
                    return $this->handle($models, $request);
                });
            }

            return $this->handle($models, $request);
        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'message' => $this->errorMessage ?? $e->getMessage(),
            ];
        }
    }

    /**
     * Generate the action name from the class name.
     */
    protected function generateName(): string
    {
        $className = class_basename(static::class);

        // Remove 'Action' suffix if present
        if (str_ends_with($className, 'Action')) {
            $className = substr($className, 0, -6);
        }

        // Convert PascalCase to Title Case
        return ucfirst(preg_replace('/(?<!^)[A-Z]/', ' $0', $className));
    }

    /**
     * Generate the URI key from the class name.
     */
    protected function generateUriKey(): string
    {
        $className = class_basename(static::class);

        // Remove 'Action' suffix if present
        if (str_ends_with($className, 'Action')) {
            $className = substr($className, 0, -6);
        }

        // Convert PascalCase to kebab-case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className));
    }

    /**
     * Get the action's metadata for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name(),
            'uriKey' => $this->uriKey(),
            'icon' => $this->icon(),
            'confirmationMessage' => $this->confirmationMessage(),
            'destructive' => $this->destructive,
            'successMessage' => $this->successMessage,
            'errorMessage' => $this->errorMessage,
        ];
    }

    /**
     * Create a success response.
     */
    protected function success(string $message, ?string $redirect = null): array
    {
        return [
            'type' => 'success',
            'message' => $message,
            'redirect' => $redirect,
        ];
    }

    /**
     * Create an error response.
     */
    protected function error(string $message): array
    {
        return [
            'type' => 'error',
            'message' => $message,
        ];
    }

    /**
     * Create an info response.
     */
    protected function info(string $message, ?string $redirect = null): array
    {
        return [
            'type' => 'info',
            'message' => $message,
            'redirect' => $redirect,
        ];
    }

    /**
     * Create a warning response.
     */
    protected function warning(string $message): array
    {
        return [
            'type' => 'warning',
            'message' => $message,
        ];
    }
}
