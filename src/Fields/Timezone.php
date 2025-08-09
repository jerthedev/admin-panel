<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use DateTimeZone;

/**
 * Timezone Field.
 *
 * A field for selecting timezones with searchable dropdown and regional grouping.
 * Supports common timezones filtering and timezone grouping by region.
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
     * Whether the timezone field should be searchable.
     */
    public bool $searchable = true;

    /**
     * Whether to group timezones by region.
     */
    public bool $groupByRegion = false;

    /**
     * Whether to show only common timezones.
     */
    public bool $onlyCommon = false;

    /**
     * Common timezones that are frequently used.
     */
    protected array $commonTimezones = [
        'UTC' => 'UTC',
        'America/New_York' => 'Eastern Time (US & Canada)',
        'America/Chicago' => 'Central Time (US & Canada)',
        'America/Denver' => 'Mountain Time (US & Canada)',
        'America/Los_Angeles' => 'Pacific Time (US & Canada)',
        'America/Anchorage' => 'Alaska',
        'Pacific/Honolulu' => 'Hawaii',
        'Europe/London' => 'London',
        'Europe/Paris' => 'Paris',
        'Europe/Berlin' => 'Berlin',
        'Europe/Rome' => 'Rome',
        'Europe/Madrid' => 'Madrid',
        'Europe/Amsterdam' => 'Amsterdam',
        'Europe/Stockholm' => 'Stockholm',
        'Europe/Moscow' => 'Moscow',
        'Asia/Tokyo' => 'Tokyo',
        'Asia/Shanghai' => 'Beijing',
        'Asia/Hong_Kong' => 'Hong Kong',
        'Asia/Singapore' => 'Singapore',
        'Asia/Seoul' => 'Seoul',
        'Asia/Kolkata' => 'Mumbai',
        'Asia/Dubai' => 'Dubai',
        'Australia/Sydney' => 'Sydney',
        'Australia/Melbourne' => 'Melbourne',
        'Australia/Perth' => 'Perth',
        'Pacific/Auckland' => 'Auckland',
    ];

    /**
     * Make the timezone field searchable.
     */
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Group timezones by region.
     */
    public function groupByRegion(bool $groupByRegion = true): static
    {
        $this->groupByRegion = $groupByRegion;

        return $this;
    }

    /**
     * Show only common timezones.
     */
    public function onlyCommon(bool $onlyCommon = true): static
    {
        $this->onlyCommon = $onlyCommon;

        return $this;
    }

    /**
     * Get the list of timezones.
     */
    public function getTimezones(): array
    {
        if ($this->onlyCommon) {
            return $this->commonTimezones;
        }

        $timezones = [];
        $identifiers = DateTimeZone::listIdentifiers();

        foreach ($identifiers as $identifier) {
            // Skip deprecated timezones
            if (strpos($identifier, '/') === false) {
                continue;
            }

            // Create a human-readable name
            $name = str_replace('_', ' ', $identifier);
            $parts = explode('/', $name);

            if (count($parts) >= 2) {
                $region = $parts[0];
                $city = $parts[1];

                if (count($parts) > 2) {
                    $city .= ' - '.$parts[2];
                }

                $name = $city.' ('.$region.')';
            }

            $timezones[$identifier] = $name;
        }

        // Sort by display name
        asort($timezones);

        return $timezones;
    }

    /**
     * Get timezones grouped by region.
     */
    public function getTimezonesGrouped(): array
    {
        $timezones = $this->getTimezones();
        $grouped = [];

        foreach ($timezones as $identifier => $name) {
            $parts = explode('/', $identifier);
            $region = $parts[0] ?? 'Other';

            if (! isset($grouped[$region])) {
                $grouped[$region] = [];
            }

            $grouped[$region][$identifier] = $name;
        }

        // Sort regions
        ksort($grouped);

        return $grouped;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        $timezones = $this->groupByRegion
            ? $this->getTimezonesGrouped()
            : $this->getTimezones();

        return array_merge(parent::meta(), [
            'searchable' => $this->searchable,
            'groupByRegion' => $this->groupByRegion,
            'onlyCommon' => $this->onlyCommon,
            'timezones' => $timezones,
        ]);
    }
}
