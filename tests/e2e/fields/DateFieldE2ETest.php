<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E\Fields;

use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Fields\Date;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;

/**
 * Date Field End-to-End Tests
 *
 * Tests real-world usage scenarios for the Date field including
 * full CRUD operations, validation, and edge cases.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class DateFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test routes for E2E testing
        Route::post('/test-date-create', function (Request $request) {
            $field = Date::make('Event Date')
                ->format('d/m/Y')
                ->storageFormat('Y-m-d')
                ->min('2020-01-01')
                ->max('2030-12-31')
                ->pickerFormat('d-m-Y')
                ->pickerDisplayFormat('DD-MM-YYYY')
                ->firstDayOfWeek(1)
                ->required();

            $model = new \stdClass();
            $field->fill($request, $model);

            return response()->json([
                'success' => true,
                'data' => $model,
                'field_meta' => $field->meta()
            ]);
        });

        Route::get('/test-date-read/{date}', function ($date) {
            $field = Date::make('Event Date')
                ->format('d/m/Y')
                ->storageFormat('Y-m-d')
                ->pickerDisplayFormat('DD-MM-YYYY');

            $resource = (object) ['event_date' => $date];
            $field->resolve($resource);

            return response()->json([
                'success' => true,
                'value' => $field->value,
                'formatted' => $field->displayValue ?? $field->value,
                'field_meta' => $field->meta()
            ]);
        });
    }

    public function test_date_field_complete_crud_workflow(): void
    {
        // Test CREATE operation
        $createResponse = $this->postJson('/test-date-create', [
            'event_date' => '2023-06-15'
        ]);

        $createResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'event_date' => '2023-06-15'
                ]
            ]);

        $createData = $createResponse->json();
        $this->assertEquals('2023-06-15', $createData['data']['event_date']);
        $this->assertEquals('d/m/Y', $createData['field_meta']['displayFormat']);
        $this->assertEquals('Y-m-d', $createData['field_meta']['storageFormat']);
        $this->assertEquals('d-m-Y', $createData['field_meta']['pickerFormat']);
        $this->assertEquals('DD-MM-YYYY', $createData['field_meta']['pickerDisplayFormat']);
        $this->assertEquals(1, $createData['field_meta']['firstDayOfWeek']);

        // Test READ operation
        $readResponse = $this->getJson('/test-date-read/2023-06-15');

        $readResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'value' => '2023-06-15'
            ]);

        $readData = $readResponse->json();
        $this->assertEquals('2023-06-15', $readData['value']);
        $this->assertEquals('DD-MM-YYYY', $readData['field_meta']['pickerDisplayFormat']);
    }

    public function test_date_field_validation_scenarios(): void
    {
        // Test required validation
        $requiredResponse = $this->postJson('/test-date-create', [
            'event_date' => ''
        ]);

        // Should handle empty value (validation would be handled by Laravel's validator)
        $requiredResponse->assertStatus(200);
        $data = $requiredResponse->json();
        $this->assertEmpty($data['data']['event_date']);

        // Test valid date within range
        $validResponse = $this->postJson('/test-date-create', [
            'event_date' => '2025-12-25'
        ]);

        $validResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'event_date' => '2025-12-25'
                ]
            ]);
    }

    public function test_date_field_format_conversion_scenarios(): void
    {
        // Test different input formats
        $testCases = [
            '2023-06-15',
            '2023/06/15',
            '15-06-2023',
            '15/06/2023'
        ];

        foreach ($testCases as $inputDate) {
            $response = $this->postJson('/test-date-create', [
                'event_date' => $inputDate
            ]);

            $response->assertStatus(200);
            $data = $response->json();
            $this->assertNotEmpty($data['data']['event_date']);
        }
    }

    public function test_date_field_edge_cases(): void
    {
        // Test leap year
        $leapYearResponse = $this->postJson('/test-date-create', [
            'event_date' => '2024-02-29'
        ]);

        $leapYearResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'event_date' => '2024-02-29'
                ]
            ]);

        // Test year boundaries
        $boundaryDates = ['2020-01-01', '2030-12-31'];
        
        foreach ($boundaryDates as $boundaryDate) {
            $response = $this->postJson('/test-date-create', [
                'event_date' => $boundaryDate
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'event_date' => $boundaryDate
                    ]
                ]);
        }

        // Test invalid date format
        $invalidResponse = $this->postJson('/test-date-create', [
            'event_date' => 'not-a-date'
        ]);

        $invalidResponse->assertStatus(200);
        $data = $invalidResponse->json();
        $this->assertEquals('not-a-date', $data['data']['event_date']);
    }

    public function test_date_field_carbon_integration(): void
    {
        // Test with Carbon instance
        $carbonDate = Carbon::create(2023, 6, 15);
        
        $response = $this->getJson('/test-date-read/' . $carbonDate->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'value' => '2023-06-15'
            ]);
    }

    public function test_date_field_timezone_handling(): void
    {
        // Test timezone-aware date
        $timezoneDate = '2023-06-15T10:30:00+05:30';
        
        $response = $this->getJson('/test-date-read/' . urlencode($timezoneDate));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data['value']);
    }

    public function test_date_field_nova_api_compatibility(): void
    {
        // Test all Nova API methods work in real scenario
        Route::post('/test-nova-date', function (Request $request) {
            $field = Date::make('Nova Compatible Date')
                ->pickerFormat('d-m-Y')
                ->pickerDisplayFormat('DD-MM-YYYY')
                ->firstDayOfWeek(1)
                ->format('d/m/Y')
                ->storageFormat('Y-m-d')
                ->min('2020-01-01')
                ->max('2030-12-31')
                ->showPicker(true)
                ->required()
                ->help('Select a date using Nova API');

            $model = new \stdClass();
            $field->fill($request, $model);

            return response()->json([
                'success' => true,
                'data' => $model,
                'field_config' => [
                    'pickerFormat' => $field->pickerFormat,
                    'pickerDisplayFormat' => $field->pickerDisplayFormat,
                    'firstDayOfWeek' => $field->firstDayOfWeek,
                    'displayFormat' => $field->displayFormat,
                    'storageFormat' => $field->storageFormat,
                    'minDate' => $field->minDate,
                    'maxDate' => $field->maxDate,
                    'showPicker' => $field->showPicker
                ],
                'meta' => $field->meta()
            ]);
        });

        $response = $this->postJson('/test-nova-date', [
            'nova_compatible_date' => '2023-06-15'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nova_compatible_date' => '2023-06-15'
                ],
                'field_config' => [
                    'pickerFormat' => 'd-m-Y',
                    'pickerDisplayFormat' => 'DD-MM-YYYY',
                    'firstDayOfWeek' => 1,
                    'displayFormat' => 'd/m/Y',
                    'storageFormat' => 'Y-m-d',
                    'minDate' => '2020-01-01',
                    'maxDate' => '2030-12-31',
                    'showPicker' => true
                ]
            ]);

        $data = $response->json();
        
        // Verify meta data includes all Nova API properties
        $meta = $data['meta'];
        $this->assertArrayHasKey('pickerFormat', $meta);
        $this->assertArrayHasKey('pickerDisplayFormat', $meta);
        $this->assertArrayHasKey('firstDayOfWeek', $meta);
        $this->assertEquals('d-m-Y', $meta['pickerFormat']);
        $this->assertEquals('DD-MM-YYYY', $meta['pickerDisplayFormat']);
        $this->assertEquals(1, $meta['firstDayOfWeek']);
    }

    public function test_date_field_performance_with_large_datasets(): void
    {
        // Test performance with multiple date fields
        Route::post('/test-bulk-dates', function (Request $request) {
            $fields = [];
            $model = new \stdClass();

            for ($i = 1; $i <= 10; $i++) {
                $field = Date::make("Date {$i}", "date_{$i}")
                    ->format('Y-m-d')
                    ->pickerFormat('d-m-Y')
                    ->firstDayOfWeek(1);

                $field->fill($request, $model);
                $fields[] = $field->meta();
            }

            return response()->json([
                'success' => true,
                'data' => $model,
                'fields_count' => count($fields)
            ]);
        });

        $bulkData = [];
        for ($i = 1; $i <= 10; $i++) {
            $bulkData["date_{$i}"] = '2023-06-' . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
        }

        $startTime = microtime(true);
        $response = $this->postJson('/test-bulk-dates', $bulkData);
        $endTime = microtime(true);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'fields_count' => 10
            ]);

        // Should complete within reasonable time (less than 1 second)
        $this->assertLessThan(1.0, $endTime - $startTime);
    }

    public function test_date_field_real_world_scenarios(): void
    {
        // Scenario 1: Event booking system
        Route::post('/test-event-booking', function (Request $request) {
            $eventDateField = Date::make('Event Date')
                ->format('d/m/Y')
                ->storageFormat('Y-m-d')
                ->min(now()->format('Y-m-d'))
                ->max(now()->addYear()->format('Y-m-d'))
                ->pickerFormat('d-m-Y')
                ->firstDayOfWeek(1)
                ->required();

            $model = new \stdClass();
            $eventDateField->fill($request, $model);

            return response()->json([
                'success' => true,
                'event_date' => $model->event_date
            ]);
        });

        $futureDate = now()->addDays(30)->format('Y-m-d');
        $eventResponse = $this->postJson('/test-event-booking', [
            'event_date' => $futureDate
        ]);

        $eventResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'event_date' => $futureDate
            ]);

        // Scenario 2: Birth date registration
        Route::post('/test-birth-date', function (Request $request) {
            $birthDateField = Date::make('Birth Date')
                ->format('d/m/Y')
                ->storageFormat('Y-m-d')
                ->max(now()->subYears(13)->format('Y-m-d')) // Must be at least 13 years old
                ->pickerDisplayFormat('DD/MM/YYYY')
                ->firstDayOfWeek(1);

            $model = new \stdClass();
            $birthDateField->fill($request, $model);

            return response()->json([
                'success' => true,
                'birth_date' => $model->birth_date
            ]);
        });

        $validBirthDate = now()->subYears(25)->format('Y-m-d');
        $birthResponse = $this->postJson('/test-birth-date', [
            'birth_date' => $validBirthDate
        ]);

        $birthResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'birth_date' => $validBirthDate
            ]);
    }
}
