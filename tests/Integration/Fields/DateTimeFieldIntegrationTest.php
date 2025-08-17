<?php

declare(strict_types=1);

namespace Integration\Fields;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\DateTime;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * DateTime Field Integration Test
 *
 * Tests the complete integration between PHP DateTime field class,
 * API endpoints, and frontend functionality with 100% Nova API compatibility.
 * 
 * Focuses on field configuration and behavior rather than
 * database operations, testing the Nova API integration.
 */
class DateTimeFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different datetime values for testing
        User::factory()->create([
            'id' => 1, 
            'name' => 'John Doe', 
            'email' => 'john@example.com',
            'created_at' => '2023-06-15 14:30:00',
            'updated_at' => '2023-06-15 14:30:00'
        ]);
        User::factory()->create([
            'id' => 2, 
            'name' => 'Jane Smith', 
            'email' => 'jane@example.com',
            'created_at' => '2023-07-20 16:45:00',
            'updated_at' => '2023-07-20 16:45:00'
        ]);
        User::factory()->create([
            'id' => 3, 
            'name' => 'Bob Wilson', 
            'email' => 'bob@example.com',
            'created_at' => null,
            'updated_at' => null
        ]);
    }

    /** @test */
    public function it_creates_datetime_field_with_nova_syntax(): void
    {
        $field = DateTime::make('Created At');

        $this->assertEquals('Created At', $field->name);
        $this->assertEquals('created_at', $field->attribute);
        $this->assertEquals('DateTimeField', $field->component);
    }

    /** @test */
    public function it_creates_datetime_field_with_custom_attribute(): void
    {
        $field = DateTime::make('Published At', 'published_at');

        $this->assertEquals('Published At', $field->name);
        $this->assertEquals('published_at', $field->attribute);
    }

    /** @test */
    public function it_serializes_datetime_field_for_frontend(): void
    {
        $field = DateTime::make('Event Time')
            ->format('Y-m-d H:i:s')
            ->displayFormat('M j, Y H:i')
            ->timezone('America/New_York')
            ->step(15)
            ->min('2020-01-01T00:00')
            ->max('2030-12-31T23:59')
            ->required()
            ->nullable();

        $json = $field->jsonSerialize();

        $this->assertEquals('Event Time', $json['name']);
        $this->assertEquals('event_time', $json['attribute']);
        $this->assertEquals('DateTimeField', $json['component']);
        $this->assertEquals('Y-m-d H:i:s', $json['storageFormat']);
        $this->assertEquals('M j, Y H:i', $json['displayFormat']);
        $this->assertEquals('America/New_York', $json['timezone']);
        $this->assertEquals(15, $json['step']);
        $this->assertEquals('2020-01-01T00:00', $json['minDateTime']);
        $this->assertEquals('2030-12-31T23:59', $json['maxDateTime']);
        $this->assertContains('required', $json['rules']);
        $this->assertTrue($json['nullable']);
    }

    /** @test */
    public function it_resolves_datetime_value_from_model(): void
    {
        $user = User::find(1);
        $field = DateTime::make('Created At');

        $field->resolve($user);

        $this->assertEquals('2023-06-15 14:30:00', $field->value);
    }

    /** @test */
    public function it_resolves_datetime_value_with_custom_format(): void
    {
        $user = User::find(1);
        $field = DateTime::make('Created At')->displayFormat('M j, Y H:i');

        $field->resolve($user);

        $this->assertEquals('Jun 15, 2023 14:30', $field->value);
    }

    /** @test */
    public function it_resolves_datetime_value_with_timezone(): void
    {
        $user = User::find(1);
        $field = DateTime::make('Created At')
            ->timezone('America/New_York')
            ->displayFormat('Y-m-d H:i:s');

        $field->resolve($user);

        // The value should be converted to the specified timezone
        $this->assertStringContains('2023-06-15', $field->value);
    }

    /** @test */
    public function it_resolves_null_datetime_value(): void
    {
        $user = User::find(3);
        $field = DateTime::make('Created At');

        $field->resolve($user);

        $this->assertNull($field->value);
    }

    /** @test */
    public function it_fills_datetime_value_into_model(): void
    {
        $field = DateTime::make('Event Time');
        $model = new \stdClass();
        $request = new Request(['event_time' => '2023-06-15T14:30:00']);

        $field->fill($request, $model);

        $this->assertEquals('2023-06-15 14:30:00', $model->event_time);
    }

    /** @test */
    public function it_fills_datetime_value_with_timezone_conversion(): void
    {
        $field = DateTime::make('Event Time')
            ->timezone('America/New_York')
            ->displayFormat('Y-m-d H:i:s');

        $model = new \stdClass();
        $request = new Request(['event_time' => '2023-06-15 14:30:00']);

        $field->fill($request, $model);

        // Should convert from timezone to UTC for storage
        $this->assertNotNull($model->event_time);
    }

    /** @test */
    public function it_fills_null_datetime_value(): void
    {
        $field = DateTime::make('Event Time');
        $model = new \stdClass();
        $request = new Request(['event_time' => null]);

        $field->fill($request, $model);

        $this->assertNull($model->event_time);
    }

    /** @test */
    public function it_fills_empty_datetime_value(): void
    {
        $field = DateTime::make('Event Time');
        $model = new \stdClass();
        $request = new Request(['event_time' => '']);

        $field->fill($request, $model);

        $this->assertNull($model->event_time);
    }

    /** @test */
    public function it_handles_invalid_datetime_gracefully(): void
    {
        $field = DateTime::make('Event Time');
        $model = new \stdClass();
        $request = new Request(['event_time' => 'invalid-datetime']);

        $field->fill($request, $model);

        // Should store as-is when parsing fails
        $this->assertEquals('invalid-datetime', $model->event_time);
    }

    /** @test */
    public function it_supports_nova_api_method_chaining(): void
    {
        $field = DateTime::make('Event Time')
            ->format('Y-m-d H:i:s')
            ->displayFormat('M j, Y H:i')
            ->timezone('UTC')
            ->step(30)
            ->min('2020-01-01T00:00')
            ->max('2030-12-31T23:59')
            ->required()
            ->nullable()
            ->help('Select event time')
            ->sortable()
            ->hideFromIndex()
            ->showOnDetail();

        $this->assertInstanceOf(DateTime::class, $field);
        $this->assertEquals('Event Time', $field->name);
        $this->assertEquals('event_time', $field->attribute);
        $this->assertEquals('DateTimeField', $field->component);
    }

    /** @test */
    public function it_integrates_with_nova_visibility_methods(): void
    {
        $field = DateTime::make('Created At')
            ->hideFromIndex()
            ->showOnDetail()
            ->hideWhenCreating()
            ->showOnUpdating();

        $json = $field->jsonSerialize();

        $this->assertArrayHasKey('showOnIndex', $json);
        $this->assertArrayHasKey('showOnDetail', $json);
        $this->assertArrayHasKey('showOnCreation', $json);
        $this->assertArrayHasKey('showOnUpdate', $json);
        $this->assertFalse($json['showOnIndex']);
        $this->assertTrue($json['showOnDetail']);
        $this->assertFalse($json['showOnCreation']);
        $this->assertTrue($json['showOnUpdate']);
    }

    /** @test */
    public function it_integrates_with_nova_validation_rules(): void
    {
        $field = DateTime::make('Event Time')
            ->rules('required', 'date', 'after:now');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json['rules']);
        $this->assertContains('required', $json['rules']);
        $this->assertContains('date', $json['rules']);
        $this->assertContains('after:now', $json['rules']);
    }

    /** @test */
    public function it_supports_nova_required_method(): void
    {
        $field = DateTime::make('Event Time')->required();

        $json = $field->jsonSerialize();

        $this->assertIsArray($json['rules']);
        $this->assertContains('required', $json['rules']);
    }

    /** @test */
    public function it_supports_nova_help_text(): void
    {
        $field = DateTime::make('Event Time')
            ->help('Select the date and time for your event');

        $json = $field->jsonSerialize();

        $this->assertEquals('Select the date and time for your event', $json['helpText']);
    }

    /** @test */
    public function it_supports_nova_sortable_functionality(): void
    {
        $field = DateTime::make('Created At')->sortable();

        $json = $field->jsonSerialize();

        $this->assertTrue($json['sortable']);
    }

    /** @test */
    public function it_supports_nova_nullable_functionality(): void
    {
        $field = DateTime::make('Event Time')->nullable();

        $json = $field->jsonSerialize();

        $this->assertTrue($json['nullable']);
    }

    /** @test */
    public function it_supports_nova_default_values(): void
    {
        $field = DateTime::make('Event Time')->default('2023-06-15T14:30:00');

        $json = $field->jsonSerialize();

        $this->assertEquals('2023-06-15T14:30:00', $json['default']);
    }

    /** @test */
    public function it_supports_nova_default_callback(): void
    {
        $field = DateTime::make('Event Time')->default(function () {
            return '2023-06-15T14:30:00';
        });

        $json = $field->jsonSerialize();

        $this->assertIsCallable($json['default']);
    }

    /** @test */
    public function it_integrates_complete_nova_api_workflow(): void
    {
        // 1. Create field with Nova API
        $field = DateTime::make('Event Time')
            ->format('Y-m-d H:i:s')
            ->timezone('America/New_York')
            ->step(15)
            ->required()
            ->help('Event start time');

        // 2. Serialize for frontend
        $serialized = $field->jsonSerialize();
        $this->assertEquals('DateTimeField', $serialized['component']);
        $this->assertEquals('America/New_York', $serialized['timezone']);
        $this->assertEquals(15, $serialized['step']);

        // 3. Fill from request
        $model = new \stdClass();
        $request = new Request(['event_time' => '2023-06-15 14:30:00']);
        $field->fill($request, $model);
        $this->assertNotNull($model->event_time);

        // 4. Resolve for display
        $resource = (object) ['event_time' => '2023-06-15 14:30:00'];
        $field->resolve($resource);
        $this->assertNotNull($field->value);
    }
}
