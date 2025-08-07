<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Hidden Field
 * 
 * A hidden input field for storing values that should not be visible
 * to users but need to be included in forms (IDs, tokens, CSRF, etc.).
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class Hidden extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'HiddenField';

    /**
     * Create a new hidden field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Hidden fields should not be shown on index or detail by default
        $this->showOnIndex = false;
        $this->showOnDetail = false;
        // But should be included in forms
        $this->showOnCreation = true;
        $this->showOnUpdate = true;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'showOnIndex' => $this->showOnIndex,
            'showOnDetail' => $this->showOnDetail,
            'showOnCreation' => $this->showOnCreation,
            'showOnUpdate' => $this->showOnUpdate,
        ]);
    }
}
