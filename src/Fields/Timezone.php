<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use DateTimeZone;

/**
 * Timezone Field.
 *
 * A field for selecting timezones. Generates a Select field containing
 * a list of the world's timezones.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Timezone extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'TimezoneField';

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'options' => $this->getTimezoneOptions(),
        ]);
    }

    /**
     * Get the timezone options for the select field.
     */
    protected function getTimezoneOptions(): array
    {
        $timezones = [];
        $identifiers = DateTimeZone::listIdentifiers();

        foreach ($identifiers as $identifier) {
            $timezones[$identifier] = $identifier;
        }

        // Sort alphabetically
        asort($timezones);

        return $timezones;
    }
}
