<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Country;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Country Field Unit Tests
 *
 * Ensures Nova API compatibility and complete PHP coverage for Country field.
 */
class CountryFieldTest extends TestCase
{
    public function test_country_field_component(): void
    {
        $field = Country::make('Country');

        $this->assertEquals('CountryField', $field->component);
    }

    public function test_country_field_creation_defaults(): void
    {
        $field = Country::make('Country');

        $this->assertEquals('Country', $field->name);
        $this->assertEquals('country', $field->attribute);
        $this->assertFalse($field->searchable);

        $meta = $field->meta();
        $this->assertArrayHasKey('options', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertFalse($meta['searchable']);

        // Ensure some expected countries are present
        $this->assertEquals('United States', $meta['options']['US']);
        $this->assertEquals('Canada', $meta['options']['CA']);
        $this->assertEquals('United Kingdom', $meta['options']['GB']);
        $this->assertEquals('France', $meta['options']['FR']);
        $this->assertEquals('Germany', $meta['options']['DE']);
        $this->assertEquals('Japan', $meta['options']['JP']);
    }

    public function test_country_field_searchable_toggle(): void
    {
        $field = Country::make('Country')->searchable();

        $this->assertTrue($field->searchable);

        $meta = $field->meta();
        $this->assertTrue($meta['searchable']);
    }

    public function test_country_field_json_serialization(): void
    {
        $field = Country::make('Country Code', 'country_code')
            ->searchable()
            ->help('Select your country')
            ->rules('required');

        $json = $field->jsonSerialize();

        $this->assertEquals('Country Code', $json['name']);
        $this->assertEquals('country_code', $json['attribute']);
        $this->assertEquals('CountryField', $json['component']);
        $this->assertTrue($json['searchable']);
        $this->assertEquals(['required'], $json['rules']);
        $this->assertEquals('Select your country', $json['helpText']);

        $this->assertArrayHasKey('options', $json);
        $this->assertEquals('United States', $json['options']['US']);
    }
}

