<?php

declare(strict_types=1);

namespace Integration\Fields;

use DateTimeZone;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Timezone;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Timezone Field Integration Tests
 *
 * Tests the complete integration between PHP backend and frontend
 * for the Timezone field, ensuring Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TimezoneFieldIntegrationTest extends TestCase
{
    /** @test */
    public function it_creates_timezone_field_with_nova_api(): void
    {
        $field = Timezone::make('User Timezone');

        $this->assertEquals('User Timezone', $field->name);
        $this->assertEquals('user_timezone', $field->attribute);
        $this->assertEquals('TimezoneField', $field->component);
    }

    /** @test */
    public function it_creates_timezone_field_with_custom_attribute(): void
    {
        $field = Timezone::make('Timezone', 'custom_timezone');

        $this->assertEquals('Timezone', $field->name);
        $this->assertEquals('custom_timezone', $field->attribute);
    }

    /** @test */
    public function it_serializes_timezone_field_for_frontend(): void
    {
        $field = Timezone::make('User Timezone')
            ->required()
            ->help('Select your timezone')
            ->placeholder('Choose timezone...');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Timezone', $json['name']);
        $this->assertEquals('user_timezone', $json['attribute']);
        $this->assertEquals('TimezoneField', $json['component']);
        $this->assertArrayHasKey('options', $json);
        $this->assertIsArray($json['options']);
        $this->assertNotEmpty($json['options']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Select your timezone', $json['helpText']);
        $this->assertEquals('Choose timezone...', $json['placeholder']);
    }

    /** @test */
    public function it_includes_all_php_timezones_in_options(): void
    {
        $field = Timezone::make('Timezone');
        $json = $field->jsonSerialize();

        $phpTimezones = DateTimeZone::listIdentifiers();
        $fieldOptions = $json['options'];

        // Should include all PHP timezones
        foreach ($phpTimezones as $timezone) {
            $this->assertArrayHasKey($timezone, $fieldOptions);
            $this->assertEquals($timezone, $fieldOptions[$timezone]);
        }

        // Should have same count as PHP timezones
        $this->assertCount(count($phpTimezones), $fieldOptions);
    }

    /** @test */
    public function it_includes_common_timezones(): void
    {
        $field = Timezone::make('Timezone');
        $json = $field->jsonSerialize();

        $commonTimezones = [
            'UTC',
            'America/New_York',
            'America/Chicago',
            'America/Denver',
            'America/Los_Angeles',
            'Europe/London',
            'Europe/Paris',
            'Asia/Tokyo',
            'Australia/Sydney',
        ];

        foreach ($commonTimezones as $timezone) {
            $this->assertArrayHasKey($timezone, $json['options']);
            $this->assertEquals($timezone, $json['options'][$timezone]);
        }
    }

    /** @test */
    public function it_resolves_timezone_value_from_model(): void
    {
        $field = Timezone::make('Timezone');
        $resource = (object) ['timezone' => 'America/New_York'];

        $field->resolve($resource);

        $this->assertEquals('America/New_York', $field->value);
    }

    /** @test */
    public function it_resolves_null_timezone_value(): void
    {
        $field = Timezone::make('Timezone');
        $resource = (object) ['timezone' => null];

        $field->resolve($resource);

        $this->assertNull($field->value);
    }

    /** @test */
    public function it_fills_timezone_value_into_model(): void
    {
        $field = Timezone::make('Timezone');
        $model = new \stdClass();
        $request = new Request(['timezone' => 'America/New_York']);

        $field->fill($request, $model);

        $this->assertEquals('America/New_York', $model->timezone);
    }

    /** @test */
    public function it_fills_empty_timezone_value(): void
    {
        $field = Timezone::make('Timezone');
        $model = new \stdClass();
        $request = new Request(['timezone' => '']);

        $field->fill($request, $model);

        $this->assertEquals('', $model->timezone);
    }

    /** @test */
    public function it_fills_null_timezone_value(): void
    {
        $field = Timezone::make('Timezone');
        $model = new \stdClass();
        $request = new Request(['timezone' => null]);

        $field->fill($request, $model);

        $this->assertNull($model->timezone);
    }

    /** @test */
    public function it_supports_nova_api_method_chaining(): void
    {
        $field = Timezone::make('User Timezone')
            ->required()
            ->nullable()
            ->help('Select your timezone')
            ->placeholder('Choose timezone...')
            ->sortable()
            ->hideFromIndex()
            ->showOnDetail();

        $this->assertInstanceOf(Timezone::class, $field);
        $this->assertEquals('User Timezone', $field->name);
        $this->assertEquals('user_timezone', $field->attribute);
        $this->assertEquals('TimezoneField', $field->component);
    }

    /** @test */
    public function it_supports_nova_validation_rules(): void
    {
        $field = Timezone::make('Timezone')
            ->rules('required', 'timezone')
            ->nullable();

        $json = $field->jsonSerialize();

        $this->assertContains('required', $json['rules']);
        $this->assertContains('timezone', $json['rules']);
        $this->assertTrue($json['nullable']);
    }

    /** @test */
    public function it_supports_nova_default_values(): void
    {
        $field = Timezone::make('Timezone')->default('UTC');

        $json = $field->jsonSerialize();

        $this->assertEquals('UTC', $json['default']);
    }

    /** @test */
    public function it_supports_nova_default_callback(): void
    {
        $field = Timezone::make('Timezone')->default(function () {
            return 'America/New_York';
        });

        $json = $field->jsonSerialize();

        $this->assertIsCallable($json['default']);
    }

    /** @test */
    public function it_integrates_complete_nova_api_workflow(): void
    {
        // 1. Create field with Nova API
        $field = Timezone::make('User Timezone')
            ->required()
            ->help('Select your timezone');

        // 2. Serialize for frontend
        $serialized = $field->jsonSerialize();
        $this->assertEquals('TimezoneField', $serialized['component']);
        $this->assertArrayHasKey('options', $serialized);
        $this->assertIsArray($serialized['options']);

        // 3. Fill from request
        $model = new \stdClass();
        $request = new Request(['user_timezone' => 'America/New_York']);
        $field->fill($request, $model);
        $this->assertEquals('America/New_York', $model->user_timezone);

        // 4. Resolve for display
        $resource = (object) ['user_timezone' => 'America/New_York'];
        $field->resolve($resource);
        $this->assertEquals('America/New_York', $field->value);
    }

    /** @test */
    public function it_handles_edge_case_timezones(): void
    {
        $field = Timezone::make('Timezone');
        $json = $field->jsonSerialize();

        // Test edge case timezones
        $edgeCases = [
            'UTC',
            'GMT',
            'America/Argentina/Buenos_Aires',
            'Pacific/Kiritimati',
            'Antarctica/McMurdo',
        ];

        foreach ($edgeCases as $timezone) {
            if (in_array($timezone, DateTimeZone::listIdentifiers())) {
                $this->assertArrayHasKey($timezone, $json['options']);
                $this->assertEquals($timezone, $json['options'][$timezone]);
            }
        }
    }

    /** @test */
    public function it_maintains_timezone_options_sorting(): void
    {
        $field = Timezone::make('Timezone');
        $json = $field->jsonSerialize();

        $options = $json['options'];
        $sortedOptions = $options;
        asort($sortedOptions);

        $this->assertEquals($sortedOptions, $options);
    }

    /** @test */
    public function it_validates_timezone_identifiers(): void
    {
        $field = Timezone::make('Timezone');
        $json = $field->jsonSerialize();

        // All options should be valid timezone identifiers
        foreach (array_keys($json['options']) as $timezone) {
            $this->assertContains($timezone, DateTimeZone::listIdentifiers());
        }
    }

    /** @test */
    public function it_handles_fill_with_custom_callback(): void
    {
        $field = Timezone::make('Timezone')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 'UTC';
        });

        $model = new \stdClass();
        $request = new Request(['timezone' => 'America/New_York']);

        $field->fill($request, $model);

        $this->assertEquals('UTC', $model->timezone);
    }

    /** @test */
    public function it_ensures_nova_api_compatibility(): void
    {
        $field = Timezone::make('Timezone');
        $json = $field->jsonSerialize();

        // Should use 'options' key for Nova compatibility
        $this->assertArrayHasKey('options', $json);

        // Should NOT have custom properties from old implementation
        $this->assertArrayNotHasKey('timezones', $json);
        $this->assertArrayNotHasKey('groupByRegion', $json);
        $this->assertArrayNotHasKey('onlyCommon', $json);

        // Should have standard Nova field properties
        $this->assertArrayHasKey('searchable', $json);
        $this->assertArrayHasKey('sortable', $json);
        $this->assertArrayHasKey('nullable', $json);
        $this->assertFalse($json['searchable']); // Default value

        // Options should be simple key-value pairs
        foreach ($json['options'] as $key => $value) {
            $this->assertEquals($key, $value);
            $this->assertIsString($key);
            $this->assertIsString($value);
        }
    }
}
