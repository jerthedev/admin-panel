<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Gravatar Field.
 *
 * The Gravatar field does not correspond to any column in your application's database.
 * Instead, it will display the "Gravatar" image of the model it is associated with.
 *
 * By default, the Gravatar URL will be generated based on the value of the model's
 * email column. However, if your user's email addresses are not stored in the email
 * column, you may pass a custom column name to the field's make method.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Gravatar extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'GravatarField';

    /**
     * The email column to use for Gravatar generation.
     * Defaults to 'email' if not specified.
     */
    public string $emailColumn = 'email';

    /**
     * Whether the Gravatar should be displayed as squared.
     */
    public bool $squared = false;

    /**
     * Whether the Gravatar should be displayed as rounded.
     */
    public bool $rounded = true;

    /**
     * Create a new Gravatar field instance.
     *
     * @param string $name The display name of the field
     * @param string|null $emailColumn The email column name (defaults to 'email')
     * @param callable|null $resolveCallback Optional resolve callback
     */
    public function __construct(string $name, ?string $emailColumn = null, ?callable $resolveCallback = null)
    {
        // Gravatar fields don't have a database attribute - they're computed
        // Use a special attribute name to indicate this is not a database column
        parent::__construct($name, '__gravatar_computed__', $resolveCallback);

        // Set email column (defaults to 'email' like Nova)
        $this->emailColumn = $emailColumn ?? 'email';

        // Gravatars are rounded by default (like Nova)
        $this->rounded = true;
    }



    /**
     * Set the Gravatar to be displayed as squared.
     * You may use the squared method to display the image's thumbnail with squared edges.
     */
    public function squared(): static
    {
        $this->squared = true;
        $this->rounded = false;

        return $this;
    }

    /**
     * Set the Gravatar to be displayed as rounded.
     * You may use the rounded method to display the images with fully-rounded edges.
     */
    public function rounded(): static
    {
        $this->rounded = true;
        $this->squared = false;

        return $this;
    }

    /**
     * Generate a Gravatar URL for the given email.
     * Uses basic Gravatar defaults to match Nova behavior.
     */
    protected function generateGravatarUrl(string $email): string
    {
        $hash = md5(strtolower(trim($email)));

        return "https://www.gravatar.com/avatar/{$hash}";
    }

    /**
     * Resolve the field's value for display.
     * Generate the Gravatar URL based on the email column value.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        // Generate Gravatar URL from the email column
        if (isset($resource->{$this->emailColumn})) {
            $email = $resource->{$this->emailColumn};
            if ($email) {
                $this->value = $this->generateGravatarUrl($email);
            }
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     * Gravatar fields don't correspond to any database column, so this is a no-op.
     */
    public function fill(Request $request, $model): void
    {
        // Gravatar fields don't fill the model - they are computed from email
        // This matches Nova's behavior where Gravatar fields don't correspond to database columns
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'emailColumn' => $this->emailColumn,
            'squared' => $this->squared,
            'rounded' => $this->rounded,
        ]);
    }
}
