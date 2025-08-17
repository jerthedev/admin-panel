<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use JTD\AdminPanel\Fields\KeyValue;
use JTD\AdminPanel\Tests\TestCase;

/**
 * KeyValue Field End-to-End Tests
 *
 * Tests real-world usage scenarios for the KeyValue field including
 * database interactions, form submissions, and complete CRUD operations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class KeyValueFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test table with JSON column for key-value data
        Schema::create('test_models', function ($table) {
            $table->id();
            $table->string('name');
            $table->json('meta')->nullable();
            $table->json('settings')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    /** @test */
    public function it_handles_complete_crud_operations_with_database(): void
    {
        // Create a test model
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'test_models';
            protected $fillable = ['name', 'meta', 'settings', 'config'];
            protected $casts = [
                'meta' => 'array',
                'settings' => 'array',
                'config' => 'array',
            ];
        };

        // CREATE: Insert new record with key-value data
        $createData = [
            'name' => 'Test User',
            'meta' => [
                'email' => 'test@example.com',
                'phone' => '+1234567890',
                'department' => 'Engineering',
            ],
        ];

        $record = $model::create($createData);
        $this->assertDatabaseHas('test_models', [
            'id' => $record->id,
            'name' => 'Test User',
        ]);

        // Verify JSON data was stored correctly
        $storedRecord = $model::find($record->id);
        $this->assertEquals([
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'department' => 'Engineering',
        ], $storedRecord->meta);

        // READ: Test field resolution for display
        $field = KeyValue::make('Meta');
        $field->resolve($storedRecord);

        $expectedResolved = [
            ['key' => 'email', 'value' => 'test@example.com'],
            ['key' => 'phone', 'value' => '+1234567890'],
            ['key' => 'department', 'value' => 'Engineering'],
        ];

        $this->assertEquals($expectedResolved, $field->value);

        // UPDATE: Modify existing data
        $updateRequest = new \Illuminate\Http\Request([
            'meta' => [
                ['key' => 'email', 'value' => 'updated@example.com'], // Modified
                ['key' => 'phone', 'value' => '+1234567890'], // Unchanged
                ['key' => 'role', 'value' => 'Senior Engineer'], // Added
                // department removed
            ],
        ]);

        $field->fill($updateRequest, $storedRecord);
        $storedRecord->save();

        // Verify update
        $updatedRecord = $model::find($record->id);
        $this->assertEquals([
            'email' => 'updated@example.com',
            'phone' => '+1234567890',
            'role' => 'Senior Engineer',
        ], $updatedRecord->meta);

        // DELETE: Remove all key-value data
        $deleteRequest = new \Illuminate\Http\Request(['meta' => []]);
        $field->fill($deleteRequest, $updatedRecord);
        $updatedRecord->save();

        $finalRecord = $model::find($record->id);
        $this->assertEquals([], $finalRecord->meta);
    }

    /** @test */
    public function it_handles_user_profile_management_scenario(): void
    {
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'test_models';
            protected $fillable = ['name', 'settings'];
            protected $casts = ['settings' => 'array'];
        };

        // User creates profile with initial preferences
        $user = $model::create([
            'name' => 'John Doe',
            'settings' => [
                'theme' => 'dark',
                'language' => 'en',
                'notifications' => 'enabled',
                'timezone' => 'UTC',
            ],
        ]);

        // Admin panel displays user settings using KeyValue field
        $field = KeyValue::make('User Settings', 'settings')
            ->keyLabel('Setting')
            ->valueLabel('Value')
            ->actionText('Add setting');

        $field->resolve($user);

        $this->assertEquals([
            ['key' => 'theme', 'value' => 'dark'],
            ['key' => 'language', 'value' => 'en'],
            ['key' => 'notifications', 'value' => 'enabled'],
            ['key' => 'timezone', 'value' => 'UTC'],
        ], $field->value);

        // User updates preferences through admin panel
        $updateRequest = new \Illuminate\Http\Request([
            'settings' => [
                ['key' => 'theme', 'value' => 'light'], // Changed
                ['key' => 'language', 'value' => 'es'], // Changed
                ['key' => 'notifications', 'value' => 'enabled'], // Unchanged
                ['key' => 'email_frequency', 'value' => 'weekly'], // Added
                // timezone removed
            ],
        ]);

        $field->fill($updateRequest, $user);
        $user->save();

        // Verify changes persisted
        $updatedUser = $model::find($user->id);
        $this->assertEquals([
            'theme' => 'light',
            'language' => 'es',
            'notifications' => 'enabled',
            'email_frequency' => 'weekly',
        ], $updatedUser->settings);
    }

    /** @test */
    public function it_handles_application_configuration_scenario(): void
    {
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'test_models';
            protected $fillable = ['name', 'config'];
            protected $casts = ['config' => 'array'];
        };

        // Application with complex configuration
        $app = $model::create([
            'name' => 'My Application',
            'config' => [
                'database.host' => 'localhost',
                'database.port' => '5432',
                'cache.driver' => 'redis',
                'mail.from.address' => 'noreply@example.com',
                'app.debug' => 'false',
            ],
        ]);

        $field = KeyValue::make('Configuration', 'config')
            ->keyLabel('Config Key')
            ->valueLabel('Config Value')
            ->actionText('Add configuration')
            ->help('Enter application configuration as key-value pairs')
            ->rules('json');

        // Test field serialization for frontend
        $serialized = $field->jsonSerialize();
        $this->assertEquals('Config Key', $serialized['keyLabel']);
        $this->assertEquals('Config Value', $serialized['valueLabel']);
        $this->assertEquals('Add configuration', $serialized['actionText']);

        // Resolve for editing
        $field->resolve($app);

        $this->assertCount(5, $field->value);
        $this->assertContains(['key' => 'database.host', 'value' => 'localhost'], $field->value);
        $this->assertContains(['key' => 'app.debug', 'value' => 'false'], $field->value);

        // Update configuration
        $configUpdate = new \Illuminate\Http\Request([
            'config' => [
                ['key' => 'database.host', 'value' => 'production-db.example.com'],
                ['key' => 'database.port', 'value' => '5432'],
                ['key' => 'cache.driver', 'value' => 'memcached'], // Changed
                ['key' => 'app.debug', 'value' => 'false'],
                ['key' => 'app.env', 'value' => 'production'], // Added
                // mail.from.address removed
            ],
        ]);

        $field->fill($configUpdate, $app);
        $app->save();

        $updatedApp = $model::find($app->id);
        $this->assertEquals([
            'database.host' => 'production-db.example.com',
            'database.port' => '5432',
            'cache.driver' => 'memcached',
            'app.debug' => 'false',
            'app.env' => 'production',
        ], $updatedApp->config);
    }

    /** @test */
    public function it_handles_product_metadata_scenario(): void
    {
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'test_models';
            protected $fillable = ['name', 'meta'];
            protected $casts = ['meta' => 'array'];
        };

        // E-commerce product with metadata
        $product = $model::create([
            'name' => 'Wireless Headphones',
            'meta' => [
                'brand' => 'TechCorp',
                'model' => 'WH-1000XM4',
                'color' => 'Black',
                'weight' => '254g',
                'battery_life' => '30 hours',
                'connectivity' => 'Bluetooth 5.0',
                'noise_cancellation' => 'Active',
            ],
        ]);

        $field = KeyValue::make('Product Metadata', 'meta')
            ->keyLabel('Attribute')
            ->valueLabel('Value')
            ->actionText('Add attribute');

        // Test readonly display
        $field->resolve($product);
        $this->assertCount(7, $field->value);

        // Simulate admin updating product attributes
        $updateRequest = new \Illuminate\Http\Request([
            'meta' => [
                ['key' => 'brand', 'value' => 'TechCorp'],
                ['key' => 'model', 'value' => 'WH-1000XM5'], // Updated model
                ['key' => 'color', 'value' => 'Silver'], // Updated color
                ['key' => 'weight', 'value' => '250g'], // Updated weight
                ['key' => 'battery_life', 'value' => '40 hours'], // Updated battery
                ['key' => 'connectivity', 'value' => 'Bluetooth 5.2'], // Updated
                ['key' => 'noise_cancellation', 'value' => 'Active'],
                ['key' => 'warranty', 'value' => '2 years'], // Added
            ],
        ]);

        $field->fill($updateRequest, $product);
        $product->save();

        $updatedProduct = $model::find($product->id);
        $this->assertEquals('WH-1000XM5', $updatedProduct->meta['model']);
        $this->assertEquals('Silver', $updatedProduct->meta['color']);
        $this->assertEquals('40 hours', $updatedProduct->meta['battery_life']);
        $this->assertEquals('2 years', $updatedProduct->meta['warranty']);
        $this->assertCount(8, $updatedProduct->meta);
    }

    /** @test */
    public function it_handles_validation_and_error_scenarios(): void
    {
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'test_models';
            protected $fillable = ['name', 'meta'];
            protected $casts = ['meta' => 'array'];
        };

        $field = KeyValue::make('Meta')
            ->rules('required', 'json');

        // Test with invalid/malformed data
        $invalidRequest = new \Illuminate\Http\Request([
            'meta' => [
                ['key' => 'valid', 'value' => 'data'],
                ['key' => '', 'value' => 'empty key'],
                ['value' => 'missing key'],
                'invalid structure',
                ['key' => 'another_valid', 'value' => 'more data'],
            ],
        ]);

        $testModel = new $model();
        $field->fill($invalidRequest, $testModel);

        // Should filter out invalid entries
        $this->assertEquals([
            'valid' => 'data',
            'another_valid' => 'more data',
        ], $testModel->meta);

        // Test with completely empty data
        $emptyRequest = new \Illuminate\Http\Request(['meta' => []]);
        $field->fill($emptyRequest, $testModel);
        $this->assertEquals([], $testModel->meta);
    }

    /** @test */
    public function it_handles_large_scale_data_operations(): void
    {
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'test_models';
            protected $fillable = ['name', 'config'];
            protected $casts = ['config' => 'array'];
        };

        // Create record with large configuration set
        $largeConfig = [];
        for ($i = 1; $i <= 50; $i++) {
            $largeConfig["setting_{$i}"] = "value_{$i}";
        }

        $record = $model::create([
            'name' => 'Large Config Test',
            'config' => $largeConfig,
        ]);

        $field = KeyValue::make('Configuration', 'config');
        $field->resolve($record);

        $this->assertCount(50, $field->value);
        $this->assertEquals(['key' => 'setting_1', 'value' => 'value_1'], $field->value[0]);
        $this->assertEquals(['key' => 'setting_50', 'value' => 'value_50'], $field->value[49]);

        // Update with modified large dataset
        $updateData = [];
        for ($i = 1; $i <= 75; $i++) {
            $updateData[] = ['key' => "config_{$i}", 'value' => "updated_value_{$i}"];
        }

        $updateRequest = new \Illuminate\Http\Request(['config' => $updateData]);
        $field->fill($updateRequest, $record);
        $record->save();

        $updatedRecord = $model::find($record->id);
        $this->assertCount(75, $updatedRecord->config);
        $this->assertEquals('updated_value_1', $updatedRecord->config['config_1']);
        $this->assertEquals('updated_value_75', $updatedRecord->config['config_75']);
    }
}
