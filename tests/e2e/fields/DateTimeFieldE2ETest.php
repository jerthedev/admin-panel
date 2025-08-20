<?php

namespace JTD\AdminPanel\Tests\E2E\Fields;

use Carbon\Carbon;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\DateTime;
use JTD\AdminPanel\Tests\TestCase;

/**
 * End-to-end tests for DateTime field covering the complete flow:
 * PHP Field -> JSON Serialization -> Client Processing -> Form Submission -> PHP Fill
 */
class DateTimeFieldE2ETest extends TestCase
{
    public function test_datetime_field_complete_flow_basic(): void
    {
        // 1. Create DateTime field (PHP)
        $field = DateTime::make('Event Time')
            ->format('Y-m-d H:i:s')
            ->displayFormat('M j, Y H:i')
            ->timezone('UTC')
            ->step(15)
            ->required();

        // 2. Simulate resource with data
        $resource = (object) ['event_time' => '2023-06-15 14:30:00'];
        $field->resolve($resource);

        // 3. Serialize for client (simulates API response)
        $serialized = $field->jsonSerialize();
        
        // Verify serialization structure
        $this->assertEquals('Event Time', $serialized['name']);
        $this->assertEquals('event_time', $serialized['attribute']);
        $this->assertEquals('DateTimeField', $serialized['component']);
        $this->assertEquals('2023-06-15 14:30:00', $serialized['value']);
        $this->assertEquals('Y-m-d H:i:s', $serialized['storageFormat']);
        $this->assertEquals('M j, Y H:i', $serialized['displayFormat']);
        $this->assertEquals('UTC', $serialized['timezone']);
        $this->assertEquals(15, $serialized['step']);
        $this->assertContains('required', $serialized['rules']);

        // 4. Simulate form submission (client -> server)
        $newField = DateTime::make('Event Time')
            ->format('Y-m-d H:i:s')
            ->timezone('UTC');

        $model = new \stdClass();
        $request = new Request(['event_time' => '2023-07-20T16:45:00']);
        $newField->fill($request, $model);

        // 5. Verify final storage
        $this->assertEquals('2023-07-20 16:45:00', $model->event_time);
    }

    public function test_datetime_field_complete_flow_with_timezone(): void
    {
        // 1. Create DateTime field with timezone (PHP)
        $field = DateTime::make('Meeting Time')
            ->format('Y-m-d H:i:s')
            ->displayFormat('Y-m-d H:i:s')
            ->timezone('America/New_York')
            ->step(30)
            ->min('2020-01-01T00:00')
            ->max('2030-12-31T23:59');

        // 2. Simulate resource with UTC data
        $resource = (object) ['meeting_time' => '2023-06-15 18:30:00']; // UTC
        $field->resolve($resource);

        // 3. Serialize for client
        $serialized = $field->jsonSerialize();
        
        $this->assertEquals('Meeting Time', $serialized['name']);
        $this->assertEquals('meeting_time', $serialized['attribute']);
        $this->assertEquals('DateTimeField', $serialized['component']);
        $this->assertEquals('America/New_York', $serialized['timezone']);
        $this->assertEquals(30, $serialized['step']);
        $this->assertEquals('2020-01-01T00:00', $serialized['minDateTime']);
        $this->assertEquals('2030-12-31T23:59', $serialized['maxDateTime']);

        // 4. Simulate form submission with timezone
        $newField = DateTime::make('Meeting Time')
            ->format('Y-m-d H:i:s')
            ->timezone('America/New_York');

        $model = new \stdClass();
        $request = new Request(['meeting_time' => '2023-06-15 14:30:00']);
        $newField->fill($request, $model);

        // 5. Verify storage (should be converted to UTC)
        $this->assertNotNull($model->meeting_time);
    }

    public function test_datetime_field_complete_flow_null_values(): void
    {
        // 1. Create nullable DateTime field
        $field = DateTime::make('Optional Time')
            ->nullable()
            ->format('Y-m-d H:i:s');

        // 2. Simulate resource with null data
        $resource = (object) ['optional_time' => null];
        $field->resolve($resource);

        // 3. Serialize for client
        $serialized = $field->jsonSerialize();
        
        $this->assertEquals('Optional Time', $serialized['name']);
        $this->assertEquals('optional_time', $serialized['attribute']);
        $this->assertNull($serialized['value']);
        $this->assertTrue($serialized['nullable']);

        // 4. Simulate form submission with null
        $newField = DateTime::make('Optional Time')->nullable();
        $model = new \stdClass();
        $request = new Request(['optional_time' => null]);
        $newField->fill($request, $model);

        // 5. Verify null storage
        $this->assertNull($model->optional_time);

        // 6. Test empty string handling
        $emptyField = DateTime::make('Optional Time')->nullable();
        $emptyModel = new \stdClass();
        $emptyRequest = new Request(['optional_time' => '']);
        $emptyField->fill($emptyRequest, $emptyModel);

        $this->assertNull($emptyModel->optional_time);
    }

    public function test_datetime_field_complete_flow_validation_rules(): void
    {
        // 1. Create DateTime field with validation
        $field = DateTime::make('Appointment Time')
            ->rules('required', 'date', 'after:now')
            ->help('Schedule your appointment');

        // 2. Serialize for client
        $serialized = $field->jsonSerialize();

        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('date', $serialized['rules']);
        $this->assertContains('after:now', $serialized['rules']);
        $this->assertEquals('Schedule your appointment', $serialized['helpText']);

        // 3. Simulate form submission
        $model = new \stdClass();
        $request = new Request(['appointment_time' => '2023-12-25T10:00:00']);
        $field->fill($request, $model);

        // 4. Verify storage
        $this->assertEquals('2023-12-25 10:00:00', $model->appointment_time);
    }

    public function test_datetime_field_complete_flow_invalid_data(): void
    {
        // 1. Create DateTime field
        $field = DateTime::make('Event Time');

        // 2. Simulate form submission with invalid data
        $model = new \stdClass();
        $request = new Request(['event_time' => 'invalid-datetime']);
        $field->fill($request, $model);

        // 3. Verify graceful handling (stores as-is when parsing fails)
        $this->assertEquals('invalid-datetime', $model->event_time);
    }

    public function test_datetime_field_complete_flow_with_default(): void
    {
        // 1. Create DateTime field with string default
        $field = DateTime::make('Created At')
            ->default('2023-06-15T14:30:00');

        // 2. Serialize for client
        $serialized = $field->jsonSerialize();

        $this->assertEquals('2023-06-15T14:30:00', $serialized['default']);

        // 3. Test with callback default
        $callbackField = DateTime::make('Updated At')
            ->default(function () {
                return Carbon::now()->format('Y-m-d H:i:s');
            });

        $callbackSerialized = $callbackField->jsonSerialize();
        $this->assertIsCallable($callbackSerialized['default']);
    }

    public function test_datetime_field_e2e_complete_crud_simulation(): void
    {
        // Simulate a complete CRUD operation
        
        // CREATE: New record with datetime field
        $createField = DateTime::make('Event Start')->format('Y-m-d H:i:s');
        $createModel = new \stdClass();
        $createRequest = new Request(['event_start' => '2023-06-15T14:30:00']);
        $createField->fill($createRequest, $createModel);
        $this->assertEquals('2023-06-15 14:30:00', $createModel->event_start);

        // READ: Display existing record
        $readField = DateTime::make('Event Start')->displayFormat('M j, Y H:i');
        $readResource = (object) ['event_start' => '2023-06-15 14:30:00'];
        $readField->resolve($readResource);
        $readSerialized = $readField->jsonSerialize();
        $this->assertEquals('2023-06-15 14:30:00', $readSerialized['value']);

        // UPDATE: Modify existing record
        $updateField = DateTime::make('Event Start')->format('Y-m-d H:i:s');
        $updateModel = new \stdClass();
        $updateRequest = new Request(['event_start' => '2023-07-20T16:45:00']);
        $updateField->fill($updateRequest, $updateModel);
        $this->assertEquals('2023-07-20 16:45:00', $updateModel->event_start);

        // DELETE: Null the field
        $deleteField = DateTime::make('Event Start')->nullable();
        $deleteModel = new \stdClass();
        $deleteRequest = new Request(['event_start' => null]);
        $deleteField->fill($deleteRequest, $deleteModel);
        $this->assertNull($deleteModel->event_start);
    }

    public function test_datetime_field_e2e_nova_api_compatibility(): void
    {
        // Test complete Nova API compatibility
        $field = DateTime::make('Event Time')
            ->format('Y-m-d H:i:s')
            ->displayFormat('M j, Y H:i')
            ->timezone('America/New_York')
            ->step(15)
            ->min('2020-01-01T00:00')
            ->max('2030-12-31T23:59')
            ->required()
            ->nullable()
            ->help('Select event time')
            ->sortable()
            ->hideFromIndex()
            ->showOnDetail()
            ->hideWhenCreating()
            ->showOnUpdating();

        // Serialize and verify all Nova API features
        $serialized = $field->jsonSerialize();

        $this->assertEquals('Event Time', $serialized['name']);
        $this->assertEquals('event_time', $serialized['attribute']);
        $this->assertEquals('DateTimeField', $serialized['component']);
        $this->assertEquals('Y-m-d H:i:s', $serialized['storageFormat']);
        $this->assertEquals('M j, Y H:i', $serialized['displayFormat']);
        $this->assertEquals('America/New_York', $serialized['timezone']);
        $this->assertEquals(15, $serialized['step']);
        $this->assertEquals('2020-01-01T00:00', $serialized['minDateTime']);
        $this->assertEquals('2030-12-31T23:59', $serialized['maxDateTime']);
        $this->assertContains('required', $serialized['rules']);
        $this->assertTrue($serialized['nullable']);
        $this->assertEquals('Select event time', $serialized['helpText']);
        $this->assertTrue($serialized['sortable']);
        $this->assertArrayHasKey('showOnIndex', $serialized);
        $this->assertArrayHasKey('showOnDetail', $serialized);
        $this->assertArrayHasKey('showOnCreation', $serialized);
        $this->assertArrayHasKey('showOnUpdate', $serialized);

        // Test fill operation
        $model = new \stdClass();
        $request = new Request(['event_time' => '2023-06-15 14:30:00']);
        $field->fill($request, $model);
        $this->assertNotNull($model->event_time);
    }

    public function test_datetime_field_e2e_edge_cases(): void
    {
        // Test various edge cases
        
        // Leap year
        $field = DateTime::make('Leap Year Test');
        $model = new \stdClass();
        $request = new Request(['leap_year_test' => '2024-02-29T12:00:00']);
        $field->fill($request, $model);
        $this->assertEquals('2024-02-29 12:00:00', $model->leap_year_test);

        // Midnight
        $midnightField = DateTime::make('Midnight Test');
        $midnightModel = new \stdClass();
        $midnightRequest = new Request(['midnight_test' => '2023-06-15T00:00:00']);
        $midnightField->fill($midnightRequest, $midnightModel);
        $this->assertEquals('2023-06-15 00:00:00', $midnightModel->midnight_test);

        // End of day
        $endDayField = DateTime::make('End Day Test');
        $endDayModel = new \stdClass();
        $endDayRequest = new Request(['end_day_test' => '2023-06-15T23:59:59']);
        $endDayField->fill($endDayRequest, $endDayModel);
        $this->assertEquals('2023-06-15 23:59:59', $endDayModel->end_day_test);
    }
}
