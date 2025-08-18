<?php

declare(strict_types=1);

namespace Tests\E2E\Fields;

use DateTimeZone;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Timezone;
use JTD\AdminPanel\Tests\TestCase;

/**
 * End-to-end tests for Timezone field covering the complete flow:
 * PHP Field -> JSON Serialization -> Client Processing -> Form Submission -> PHP Fill
 *
 * Tests real-world usage scenarios with Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TimezoneFieldE2ETest extends TestCase
{
    /** @test */
    public function it_handles_complete_timezone_field_flow_basic(): void
    {
        // 1. Create Timezone field (PHP)
        $field = Timezone::make('User Timezone')
            ->required()
            ->help('Select your timezone');

        // 2. Serialize for frontend (simulates API response)
        $serialized = $field->jsonSerialize();

        // 3. Verify frontend receives correct structure
        $this->assertEquals('TimezoneField', $serialized['component']);
        $this->assertEquals('User Timezone', $serialized['name']);
        $this->assertEquals('user_timezone', $serialized['attribute']);
        $this->assertArrayHasKey('options', $serialized);
        $this->assertIsArray($serialized['options']);
        $this->assertNotEmpty($serialized['options']);
        $this->assertContains('required', $serialized['rules']);

        // 4. Simulate form submission (client -> server)
        $newField = Timezone::make('User Timezone');
        $model = new \stdClass();
        $request = new Request(['user_timezone' => 'America/New_York']);
        $newField->fill($request, $model);

        // 5. Verify final storage
        $this->assertEquals('America/New_York', $model->user_timezone);
    }

    /** @test */
    public function it_handles_complete_timezone_field_flow_with_validation(): void
    {
        // 1. Create Timezone field with validation
        $field = Timezone::make('Timezone')
            ->rules('required', 'timezone')
            ->nullable(false);

        // 2. Serialize for frontend
        $serialized = $field->jsonSerialize();

        // 3. Verify validation rules are included
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('timezone', $serialized['rules']);
        $this->assertFalse($serialized['nullable']);

        // 4. Test valid timezone submission
        $newField = Timezone::make('Timezone')->rules('required', 'timezone');
        $model = new \stdClass();
        $request = new Request(['timezone' => 'Europe/London']);
        $newField->fill($request, $model);

        $this->assertEquals('Europe/London', $model->timezone);

        // 5. Test empty submission (should be handled by validation)
        $emptyModel = new \stdClass();
        $emptyRequest = new Request(['timezone' => '']);
        $newField->fill($emptyRequest, $emptyModel);

        $this->assertEquals('', $emptyModel->timezone);
    }

    /** @test */
    public function it_handles_complete_timezone_field_flow_nullable(): void
    {
        // 1. Create nullable Timezone field
        $field = Timezone::make('Optional Timezone')
            ->nullable()
            ->placeholder('Choose timezone (optional)');

        // 2. Serialize for frontend
        $serialized = $field->jsonSerialize();

        // 3. Verify nullable configuration
        $this->assertTrue($serialized['nullable']);
        $this->assertEquals('Choose timezone (optional)', $serialized['placeholder']);

        // 4. Test null submission
        $newField = Timezone::make('Optional Timezone')->nullable();
        $model = new \stdClass();
        $request = new Request(['optional_timezone' => null]);
        $newField->fill($request, $model);

        $this->assertNull($model->optional_timezone);

        // 5. Test empty string submission
        $emptyModel = new \stdClass();
        $emptyRequest = new Request(['optional_timezone' => '']);
        $newField->fill($emptyRequest, $emptyModel);

        $this->assertEquals('', $emptyModel->optional_timezone);
    }

    /** @test */
    public function it_handles_all_php_timezones_end_to_end(): void
    {
        // 1. Create Timezone field
        $field = Timezone::make('Timezone');

        // 2. Get serialized options
        $serialized = $field->jsonSerialize();
        $options = $serialized['options'];

        // 3. Verify all PHP timezones are available
        $phpTimezones = DateTimeZone::listIdentifiers();
        foreach ($phpTimezones as $timezone) {
            $this->assertArrayHasKey($timezone, $options);
            $this->assertEquals($timezone, $options[$timezone]);
        }

        // 4. Test submission with various timezone types
        $testTimezones = [
            'UTC',
            'America/New_York',
            'Europe/London',
            'Asia/Tokyo',
            'Australia/Sydney',
            'Pacific/Honolulu',
            'Antarctica/McMurdo',
            'America/Argentina/Buenos_Aires',
        ];

        foreach ($testTimezones as $timezone) {
            if (in_array($timezone, $phpTimezones)) {
                $newField = Timezone::make('Timezone');
                $model = new \stdClass();
                $request = new Request(['timezone' => $timezone]);
                $newField->fill($request, $model);

                $this->assertEquals($timezone, $model->timezone);
            }
        }
    }

    /** @test */
    public function it_handles_timezone_field_with_default_values(): void
    {
        // 1. Create field with default value
        $field = Timezone::make('Timezone')->default('UTC');

        // 2. Serialize for frontend
        $serialized = $field->jsonSerialize();

        // 3. Verify default is included
        $this->assertEquals('UTC', $serialized['default']);

        // 4. Test with callable default
        $callableField = Timezone::make('Timezone')->default(function () {
            return 'America/New_York';
        });

        $callableSerialized = $callableField->jsonSerialize();
        $this->assertIsCallable($callableSerialized['default']);

        // 5. Test form submission overrides default
        $newField = Timezone::make('Timezone')->default('UTC');
        $model = new \stdClass();
        $request = new Request(['timezone' => 'Europe/London']);
        $newField->fill($request, $model);

        $this->assertEquals('Europe/London', $model->timezone);
    }

    /** @test */
    public function it_handles_timezone_field_with_custom_callback(): void
    {
        // 1. Create field with custom fill callback
        $field = Timezone::make('Timezone')->fillUsing(function ($request, $model, $attribute) {
            // Custom logic: always set to UTC regardless of input
            $model->{$attribute} = 'UTC';
        });

        // 2. Test custom callback behavior
        $model = new \stdClass();
        $request = new Request(['timezone' => 'America/New_York']);
        $field->fill($request, $model);

        // Should use custom callback, not the submitted value
        $this->assertEquals('UTC', $model->timezone);
    }

    /** @test */
    public function it_handles_timezone_field_resolution_flow(): void
    {
        // 1. Create field for resolution
        $field = Timezone::make('User Timezone');

        // 2. Test resolving from model
        $resource = (object) ['user_timezone' => 'America/New_York'];
        $field->resolve($resource);

        $this->assertEquals('America/New_York', $field->value);

        // 3. Test resolving null value
        $nullResource = (object) ['user_timezone' => null];
        $field->resolve($nullResource);

        $this->assertNull($field->value);

        // 4. Test with custom resolve callback
        $customField = Timezone::make('Timezone')->resolveUsing(function ($resource, $attribute) {
            return 'UTC'; // Always return UTC
        });

        $customField->resolve($resource);
        $this->assertEquals('UTC', $customField->value);
    }

    /** @test */
    public function it_handles_timezone_field_edge_cases(): void
    {
        // 1. Test with edge case timezones
        $field = Timezone::make('Timezone');
        $serialized = $field->jsonSerialize();

        $edgeCases = [
            'GMT',
            'UTC',
            'Pacific/Kiritimati', // UTC+14
            'Pacific/Niue', // UTC-11
            'America/Argentina/Buenos_Aires', // Long name
            'Antarctica/McMurdo', // Antarctica
        ];

        foreach ($edgeCases as $timezone) {
            if (in_array($timezone, DateTimeZone::listIdentifiers())) {
                $this->assertArrayHasKey($timezone, $serialized['options']);

                // Test submission
                $newField = Timezone::make('Timezone');
                $model = new \stdClass();
                $request = new Request(['timezone' => $timezone]);
                $newField->fill($request, $model);

                $this->assertEquals($timezone, $model->timezone);
            }
        }
    }

    /** @test */
    public function it_handles_timezone_field_nova_api_compatibility_flow(): void
    {
        // 1. Create field using Nova API methods
        $field = Timezone::make('User Timezone')
            ->required()
            ->nullable()
            ->help('Select your timezone')
            ->placeholder('Choose timezone...')
            ->sortable()
            ->hideFromIndex()
            ->showOnDetail();

        // 2. Serialize for frontend
        $serialized = $field->jsonSerialize();

        // 3. Verify Nova API properties are preserved
        $this->assertEquals('User Timezone', $serialized['name']);
        $this->assertEquals('user_timezone', $serialized['attribute']);
        $this->assertEquals('TimezoneField', $serialized['component']);
        $this->assertContains('required', $serialized['rules']);
        $this->assertTrue($serialized['nullable']);
        $this->assertEquals('Select your timezone', $serialized['helpText']);
        $this->assertEquals('Choose timezone...', $serialized['placeholder']);
        $this->assertTrue($serialized['sortable']);
        $this->assertFalse($serialized['showOnIndex']);
        $this->assertTrue($serialized['showOnDetail']);

        // 4. Test complete CRUD flow
        $newField = Timezone::make('User Timezone');

        // Create
        $createModel = new \stdClass();
        $createRequest = new Request(['user_timezone' => 'America/New_York']);
        $newField->fill($createRequest, $createModel);
        $this->assertEquals('America/New_York', $createModel->user_timezone);

        // Read
        $readField = Timezone::make('User Timezone');
        $readField->resolve($createModel);
        $this->assertEquals('America/New_York', $readField->value);

        // Update
        $updateModel = $createModel;
        $updateRequest = new Request(['user_timezone' => 'Europe/London']);
        $newField->fill($updateRequest, $updateModel);
        $this->assertEquals('Europe/London', $updateModel->user_timezone);
    }

    /** @test */
    public function it_handles_timezone_field_sorting_and_options(): void
    {
        // 1. Create field
        $field = Timezone::make('Timezone');

        // 2. Get options
        $serialized = $field->jsonSerialize();
        $options = $serialized['options'];

        // 3. Verify options are sorted
        $sortedOptions = $options;
        asort($sortedOptions);
        $this->assertEquals($sortedOptions, $options);

        // 4. Verify all options are valid timezone identifiers
        foreach (array_keys($options) as $timezone) {
            $this->assertContains($timezone, DateTimeZone::listIdentifiers());
        }

        // 5. Verify options format (key equals value)
        foreach ($options as $key => $value) {
            $this->assertEquals($key, $value);
            $this->assertIsString($key);
            $this->assertIsString($value);
        }
    }

    /** @test */
    public function it_handles_timezone_field_real_world_scenarios(): void
    {
        // Scenario 1: User registration with timezone
        $registrationField = Timezone::make('Timezone')
            ->required()
            ->help('This helps us show you times in your local timezone');

        $user = new \stdClass();
        $registrationRequest = new Request(['timezone' => 'America/Los_Angeles']);
        $registrationField->fill($registrationRequest, $user);
        $this->assertEquals('America/Los_Angeles', $user->timezone);

        // Scenario 2: Event scheduling with timezone
        $eventField = Timezone::make('Event Timezone')
            ->default('UTC')
            ->help('Timezone for this event');

        $event = new \stdClass();
        $eventRequest = new Request(['event_timezone' => 'Europe/Paris']);
        $eventField->fill($eventRequest, $event);
        $this->assertEquals('Europe/Paris', $event->event_timezone);

        // Scenario 3: Optional timezone in profile
        $profileField = Timezone::make('Preferred Timezone')
            ->nullable()
            ->placeholder('Leave empty to use system default');

        $profile = new \stdClass();
        $profileRequest = new Request(['preferred_timezone' => '']);
        $profileField->fill($profileRequest, $profile);
        $this->assertEquals('', $profile->preferred_timezone);

        // Scenario 4: Admin setting global timezone
        $adminField = Timezone::make('System Timezone')
            ->default('UTC')
            ->help('Default timezone for the application');

        $settings = new \stdClass();
        $adminRequest = new Request(['system_timezone' => 'America/New_York']);
        $adminField->fill($adminRequest, $settings);
        $this->assertEquals('America/New_York', $settings->system_timezone);
    }
}
