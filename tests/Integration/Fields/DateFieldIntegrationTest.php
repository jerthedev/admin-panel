<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Fields;

use JTD\AdminPanel\Fields\Date;
use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Date Field Integration Tests
 *
 * Tests for Date field integration with Laravel, Inertia, and Vue components.
 * Validates PHP/Vue interoperability and CRUD operations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class DateFieldIntegrationTest extends TestCase
{
    public function test_date_field_serializes_correctly_for_inertia(): void
    {
        $field = Date::make('Event Date')
            ->format('d/m/Y')
            ->storageFormat('Y-m-d')
            ->min('2020-01-01')
            ->max('2030-12-31')
            ->pickerFormat('d-m-Y')
            ->pickerDisplayFormat('DD-MM-YYYY')
            ->firstDayOfWeek(1)
            ->required()
            ->help('Select the event date');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Event Date', $serialized['name']);
        $this->assertEquals('event_date', $serialized['attribute']);
        $this->assertEquals('DateField', $serialized['component']);
        $this->assertEquals('d/m/Y', $serialized['displayFormat']);
        $this->assertEquals('Y-m-d', $serialized['storageFormat']);
        $this->assertEquals('2020-01-01', $serialized['minDate']);
        $this->assertEquals('2030-12-31', $serialized['maxDate']);
        $this->assertEquals('d-m-Y', $serialized['pickerFormat']);
        $this->assertEquals('DD-MM-YYYY', $serialized['pickerDisplayFormat']);
        $this->assertEquals(1, $serialized['firstDayOfWeek']);
        $this->assertContains('required', $serialized['rules']);
        $this->assertEquals('Select the event date', $serialized['helpText']);
    }

    public function test_date_field_handles_request_data_correctly(): void
    {
        $field = Date::make('Birth Date')
            ->format('Y-m-d')
            ->storageFormat('Y-m-d');

        $model = new \stdClass();
        $request = new Request(['birth_date' => '1990-05-15']);

        $field->fill($request, $model);

        $this->assertEquals('1990-05-15', $model->birth_date);
    }

    public function test_date_field_handles_different_storage_format(): void
    {
        $field = Date::make('Event Date')
            ->format('Y-m-d')
            ->storageFormat('Y/m/d');

        $model = new \stdClass();
        $request = new Request(['event_date' => '2023-12-25']);

        $field->fill($request, $model);

        $this->assertEquals('2023/12/25', $model->event_date);
    }

    public function test_date_field_resolves_carbon_instances(): void
    {
        $field = Date::make('Created At')->format('Y-m-d');
        $carbon = Carbon::create(2023, 6, 15);
        $resource = (object) ['created_at' => $carbon];

        $field->resolve($resource);

        $this->assertEquals('2023-06-15', $field->value);
    }

    public function test_date_field_handles_null_values_gracefully(): void
    {
        $field = Date::make('Optional Date')->nullable();
        $model = new \stdClass();
        $request = new Request(['optional_date' => '']);

        $field->fill($request, $model);

        $this->assertNull($model->optional_date);
    }

    public function test_date_field_with_custom_fill_callback(): void
    {
        $field = Date::make('Special Date')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = '2025-01-01'; // Always set to New Year
        });

        $model = new \stdClass();
        $request = new Request(['special_date' => '2023-06-15']);

        $field->fill($request, $model);

        $this->assertEquals('2025-01-01', $model->special_date);
    }

    public function test_date_field_nova_api_compatibility(): void
    {
        // Test that all Nova API methods exist and work correctly
        $field = Date::make('Nova Date')
            ->pickerFormat('d-m-Y')
            ->pickerDisplayFormat('DD-MM-YYYY')
            ->firstDayOfWeek(1);

        $this->assertEquals('d-m-Y', $field->pickerFormat);
        $this->assertEquals('DD-MM-YYYY', $field->pickerDisplayFormat);
        $this->assertEquals(1, $field->firstDayOfWeek);

        // Test method chaining
        $chainedField = Date::make('Chained Date')
            ->format('d/m/Y')
            ->storageFormat('Y-m-d')
            ->min('2020-01-01')
            ->max('2030-12-31')
            ->pickerFormat('d-m-Y')
            ->pickerDisplayFormat('DD-MM-YYYY')
            ->firstDayOfWeek(1)
            ->showPicker(true);

        $this->assertInstanceOf(Date::class, $chainedField);
        $this->assertEquals('d/m/Y', $chainedField->displayFormat);
        $this->assertEquals('Y-m-d', $chainedField->storageFormat);
        $this->assertEquals('2020-01-01', $chainedField->minDate);
        $this->assertEquals('2030-12-31', $chainedField->maxDate);
        $this->assertEquals('d-m-Y', $chainedField->pickerFormat);
        $this->assertEquals('DD-MM-YYYY', $chainedField->pickerDisplayFormat);
        $this->assertEquals(1, $chainedField->firstDayOfWeek);
        $this->assertTrue($chainedField->showPicker);
    }

    public function test_date_field_meta_data_for_frontend(): void
    {
        $field = Date::make('Frontend Date')
            ->format('d/m/Y')
            ->storageFormat('Y-m-d')
            ->min('2020-01-01')
            ->max('2030-12-31')
            ->pickerFormat('d-m-Y')
            ->pickerDisplayFormat('DD-MM-YYYY')
            ->firstDayOfWeek(1)
            ->showPicker(true);

        $meta = $field->meta();

        // Verify all meta data is present for frontend consumption
        $this->assertArrayHasKey('displayFormat', $meta);
        $this->assertArrayHasKey('storageFormat', $meta);
        $this->assertArrayHasKey('minDate', $meta);
        $this->assertArrayHasKey('maxDate', $meta);
        $this->assertArrayHasKey('showPicker', $meta);
        $this->assertArrayHasKey('pickerFormat', $meta);
        $this->assertArrayHasKey('pickerDisplayFormat', $meta);
        $this->assertArrayHasKey('firstDayOfWeek', $meta);

        $this->assertEquals('d/m/Y', $meta['displayFormat']);
        $this->assertEquals('Y-m-d', $meta['storageFormat']);
        $this->assertEquals('2020-01-01', $meta['minDate']);
        $this->assertEquals('2030-12-31', $meta['maxDate']);
        $this->assertTrue($meta['showPicker']);
        $this->assertEquals('d-m-Y', $meta['pickerFormat']);
        $this->assertEquals('DD-MM-YYYY', $meta['pickerDisplayFormat']);
        $this->assertEquals(1, $meta['firstDayOfWeek']);
    }

    public function test_date_field_handles_invalid_dates_gracefully(): void
    {
        $field = Date::make('Invalid Date');
        $model = new \stdClass();
        $request = new Request(['invalid_date' => 'not-a-date']);

        $field->fill($request, $model);

        // Should store as-is when parsing fails
        $this->assertEquals('not-a-date', $model->invalid_date);
    }

    public function test_date_field_validation_integration(): void
    {
        $field = Date::make('Validated Date')
            ->rules('required', 'date', 'after:today')
            ->min('2023-01-01')
            ->max('2025-12-31');

        $this->assertEquals(['required', 'date', 'after:today'], $field->rules);
        $this->assertEquals('2023-01-01', $field->minDate);
        $this->assertEquals('2025-12-31', $field->maxDate);
    }

    public function test_date_field_crud_operations(): void
    {
        // Test Create operation
        $createField = Date::make('Created Date')->format('Y-m-d');
        $createModel = new \stdClass();
        $createRequest = new Request(['created_date' => '2023-06-15']);

        $createField->fill($createRequest, $createModel);
        $this->assertEquals('2023-06-15', $createModel->created_date);

        // Test Read operation
        $readField = Date::make('Read Date')->format('Y-m-d');
        $readResource = (object) ['read_date' => '2023-06-15'];

        $readField->resolve($readResource);
        $this->assertEquals('2023-06-15', $readField->value);

        // Test Update operation
        $updateField = Date::make('Updated Date')->format('Y-m-d');
        $updateModel = new \stdClass();
        $updateRequest = new Request(['updated_date' => '2023-12-25']);

        $updateField->fill($updateRequest, $updateModel);
        $this->assertEquals('2023-12-25', $updateModel->updated_date);
    }

    public function test_date_field_edge_cases(): void
    {
        $field = Date::make('Edge Case Date');

        // Test leap year
        $leapYearModel = new \stdClass();
        $leapYearRequest = new Request(['edge_case_date' => '2024-02-29']);
        $field->fill($leapYearRequest, $leapYearModel);
        $this->assertEquals('2024-02-29', $leapYearModel->edge_case_date);

        // Test very old date
        $oldDateModel = new \stdClass();
        $oldDateRequest = new Request(['edge_case_date' => '1900-01-01']);
        $field->fill($oldDateRequest, $oldDateModel);
        $this->assertEquals('1900-01-01', $oldDateModel->edge_case_date);

        // Test future date
        $futureDateModel = new \stdClass();
        $futureDateRequest = new Request(['edge_case_date' => '2050-12-31']);
        $field->fill($futureDateRequest, $futureDateModel);
        $this->assertEquals('2050-12-31', $futureDateModel->edge_case_date);
    }

    public function test_date_field_timezone_handling(): void
    {
        $field = Date::make('Timezone Date')->format('Y-m-d');
        $timezoneResource = (object) ['timezone_date' => '2023-06-15T10:30:00+05:30'];

        $field->resolve($timezoneResource);

        // Should handle timezone-aware dates correctly
        $this->assertNotEmpty($field->value);
    }
}
