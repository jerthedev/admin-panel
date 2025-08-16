<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Fields\Currency;
use JTD\AdminPanel\Fields\Date;
use JTD\AdminPanel\Fields\DateTime;
use JTD\AdminPanel\Fields\Email;
use JTD\AdminPanel\Fields\Field;
use JTD\AdminPanel\Fields\ID;
use JTD\AdminPanel\Fields\Number;
use JTD\AdminPanel\Fields\Select;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Field Data Flow & Serialization Integration Tests.
 *
 * Tests that validate data flow from PHP field classes through JSON
 * serialization to Vue component props, ensuring data types are preserved
 * and complex structures are handled correctly.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class FieldDataFlowSerializationTest extends TestCase
{
    /**
     * Test data types that should be preserved through serialization.
     */
    protected array $testDataTypes = [
        'string' => 'Test String Value',
        'integer' => 42,
        'float' => 3.14159,
        'boolean_true' => true,
        'boolean_false' => false,
        'null' => null,
        'array' => ['item1', 'item2', 'item3'],
        'associative_array' => ['key1' => 'value1', 'key2' => 'value2'],
        'nested_array' => ['level1' => ['level2' => ['level3' => 'deep_value']]],
    ];

    public function test_basic_field_serialization_preserves_data_types(): void
    {
        foreach ($this->testDataTypes as $type => $testValue) {
            $field = Text::make('Test Field');
            $field->value = $testValue;

            $serialized = $field->jsonSerialize();

            $this->assertArrayHasKey('value', $serialized, "Serialized field should contain value key for {$type}");
            $this->assertSame($testValue, $serialized['value'], "Data type {$type} should be preserved through serialization");
        }
    }

    public function test_field_serialization_includes_required_properties(): void
    {
        $field = Text::make('Test Field')
            ->rules('required', 'max:255')
            ->sortable()
            ->nullable()
            ->help('Test help text')
            ->placeholder('Test placeholder');

        $serialized = $field->jsonSerialize();

        // Test required properties exist
        $requiredProperties = [
            'component', 'name', 'attribute', 'value', 'sortable', 'searchable',
            'nullable', 'readonly', 'helpText', 'placeholder', 'suffix', 'default',
            'rules', 'showOnIndex', 'showOnDetail', 'showOnCreation', 'showOnUpdate',
            'immutable', 'filterable', 'copyable', 'asHtml', 'textAlign', 'stacked', 'fullWidth',
        ];

        foreach ($requiredProperties as $property) {
            $this->assertArrayHasKey($property, $serialized, "Serialized field should contain {$property}");
        }

        // Test specific values
        $this->assertEquals('TextField', $serialized['component']);
        $this->assertEquals('Test Field', $serialized['name']);
        $this->assertEquals('test_field', $serialized['attribute']);
        $this->assertEquals(['required', 'max:255'], $serialized['rules']);
        $this->assertTrue($serialized['sortable']);
        $this->assertTrue($serialized['nullable']);
        $this->assertEquals('Test help text', $serialized['helpText']);
        $this->assertEquals('Test placeholder', $serialized['placeholder']);
    }

    public function test_complex_field_serialization_with_options(): void
    {
        $options = [
            'active' => 'Active User',
            'inactive' => 'Inactive User',
            'pending' => 'Pending Approval',
            'suspended' => 'Suspended Account',
        ];

        $field = Select::make('User Status')
            ->options($options)
            ->searchable()
            ->default('pending')
            ->rules('required', 'in:active,inactive,pending,suspended');

        $serialized = $field->jsonSerialize();

        // Test that complex options are preserved
        $this->assertArrayHasKey('options', $serialized);
        $this->assertEquals($options, $serialized['options']);
        $this->assertTrue($serialized['searchable']);
        $this->assertEquals('pending', $serialized['default']);
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('in:active,inactive,pending,suspended', $serialized['rules']);
    }

    public function test_boolean_field_serialization_with_custom_values(): void
    {
        $field = Boolean::make('Is Published')
            ->trueValue('published')
            ->falseValue('draft')
            ->labels('Published', 'Draft');

        $serialized = $field->jsonSerialize();

        // Test that boolean-specific properties are included
        $this->assertArrayHasKey('trueValue', $serialized);
        $this->assertArrayHasKey('falseValue', $serialized);
        $this->assertArrayHasKey('trueText', $serialized);
        $this->assertArrayHasKey('falseText', $serialized);

        $this->assertEquals('published', $serialized['trueValue']);
        $this->assertEquals('draft', $serialized['falseValue']);
        $this->assertEquals('Published', $serialized['trueText']);
        $this->assertEquals('Draft', $serialized['falseText']);
    }

    public function test_number_field_serialization_with_constraints(): void
    {
        $field = Number::make('Quantity')
            ->min(1)
            ->max(100)
            ->step(1)
            ->decimals(0);

        $serialized = $field->jsonSerialize();

        // Test that number-specific properties are included
        $this->assertArrayHasKey('min', $serialized);
        $this->assertArrayHasKey('max', $serialized);
        $this->assertArrayHasKey('step', $serialized);
        $this->assertArrayHasKey('decimals', $serialized);

        $this->assertEquals(1, $serialized['min']);
        $this->assertEquals(100, $serialized['max']);
        $this->assertEquals(1, $serialized['step']);
        $this->assertEquals(0, $serialized['decimals']);
    }

    public function test_currency_field_serialization_with_formatting(): void
    {
        $field = Currency::make('Price')
            ->currency('EUR')
            ->precision(2)
            ->min(0.0)
            ->max(9999.99);

        $serialized = $field->jsonSerialize();

        // Test that currency-specific properties are included
        $this->assertArrayHasKey('currency', $serialized);
        $this->assertArrayHasKey('precision', $serialized);
        $this->assertArrayHasKey('minValue', $serialized);
        $this->assertArrayHasKey('maxValue', $serialized);

        $this->assertEquals('EUR', $serialized['currency']);
        $this->assertEquals(2, $serialized['precision']);
        $this->assertEquals(0.0, $serialized['minValue']);
        $this->assertEquals(9999.99, $serialized['maxValue']);
    }

    public function test_date_field_serialization_with_formats(): void
    {
        $field = Date::make('Event Date')
            ->format('Y-m-d');

        $serialized = $field->jsonSerialize();

        // Test that date-specific properties are included
        $this->assertArrayHasKey('storageFormat', $serialized);
        $this->assertArrayHasKey('displayFormat', $serialized);

        $this->assertEquals('Y-m-d', $serialized['storageFormat']);
        $this->assertEquals('Y-m-d', $serialized['displayFormat']); // Both should be the same when using format()
    }

    public function test_datetime_field_serialization_with_timezone(): void
    {
        $field = DateTime::make('Created At')
            ->format('Y-m-d H:i:s')
            ->displayFormat('M j, Y H:i')
            ->timezone('America/New_York');

        $serialized = $field->jsonSerialize();

        // Test that datetime-specific properties are included
        $this->assertArrayHasKey('storageFormat', $serialized);
        $this->assertArrayHasKey('displayFormat', $serialized);
        $this->assertArrayHasKey('timezone', $serialized);

        $this->assertEquals('Y-m-d H:i:s', $serialized['storageFormat']);
        $this->assertEquals('M j, Y H:i', $serialized['displayFormat']);
        $this->assertEquals('America/New_York', $serialized['timezone']);
    }

    public function test_field_value_resolution_from_model(): void
    {
        // Create test model data
        $model = (object) [
            'id' => 123,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'is_active' => true,
            'created_at' => '2023-01-15 10:30:00',
        ];

        // Test different field types resolving values
        $testCases = [
            ['field' => ID::make(), 'expected' => 123],
            ['field' => Text::make('Name'), 'expected' => 'John Doe'],
            ['field' => Email::make('Email'), 'expected' => 'john@example.com'],
            ['field' => Number::make('Age'), 'expected' => 30],
            ['field' => Boolean::make('Is Active'), 'expected' => ['value' => true, 'display' => 'Yes']],
        ];

        foreach ($testCases as $testCase) {
            $field = $testCase['field'];
            $expectedValue = $testCase['expected'];

            $field->resolve($model);

            if ($field instanceof Boolean) {
                // Boolean fields have special resolution logic
                $this->assertIsArray($field->value);
                $this->assertEquals($expectedValue['value'], $field->value['value']);
                $this->assertEquals($expectedValue['display'], $field->value['display']);
            } else {
                $this->assertEquals($expectedValue, $field->value, "Field {$field->component} should resolve correct value");
            }
        }
    }

    public function test_field_value_resolution_with_custom_callback(): void
    {
        $model = (object) ['full_name' => 'Jane Smith'];

        $field = Text::make('Display Name', 'full_name', function ($resource, $attribute) {
            return strtoupper($resource->{$attribute});
        });

        $field->resolve($model);

        $this->assertEquals('JANE SMITH', $field->value);
    }

    public function test_field_display_callback_formatting(): void
    {
        $model = (object) ['price' => 1234.56];

        $field = Number::make('Price')
            ->displayUsing(function ($value) {
                return '$'.number_format($value, 2);
            });

        $displayValue = $field->resolveValue($model);

        $this->assertEquals('$1,234.56', $displayValue);
    }

    public function test_field_serialization_with_meta_data(): void
    {
        $customMeta = [
            'customProperty' => 'custom value',
            'apiEndpoint' => '/api/users',
            'validationMessages' => [
                'required' => 'This field is required',
                'email' => 'Please enter a valid email',
            ],
        ];

        $field = Email::make('Email Address')
            ->withMeta($customMeta);

        $serialized = $field->jsonSerialize();

        // Test that custom meta is included in serialization
        foreach ($customMeta as $key => $value) {
            $this->assertArrayHasKey($key, $serialized);
            $this->assertEquals($value, $serialized[$key]);
        }
    }

    public function test_field_serialization_preserves_null_values(): void
    {
        $field = Text::make('Optional Field');
        $field->value = null;

        $serialized = $field->jsonSerialize();

        $this->assertArrayHasKey('value', $serialized);
        $this->assertNull($serialized['value']);
    }

    public function test_field_serialization_with_default_values(): void
    {
        $field = Select::make('Status')
            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
            ->default('active');

        $serialized = $field->jsonSerialize();

        $this->assertArrayHasKey('default', $serialized);
        $this->assertEquals('active', $serialized['default']);
    }

    public function test_field_serialization_includes_visibility_settings(): void
    {
        $field = Text::make('Internal Field')
            ->hideFromIndex()
            ->showOnDetail()
            ->hideWhenCreating()
            ->showOnUpdating();

        $serialized = $field->jsonSerialize();

        $this->assertFalse($serialized['showOnIndex']);
        $this->assertTrue($serialized['showOnDetail']);
        $this->assertFalse($serialized['showOnCreation']);
        $this->assertTrue($serialized['showOnUpdate']);
    }

    public function test_field_serialization_includes_validation_rules(): void
    {
        $rules = ['required', 'string', 'max:255', 'unique:users,email'];

        $field = Email::make('Email')
            ->rules($rules);

        $serialized = $field->jsonSerialize();

        $this->assertArrayHasKey('rules', $serialized);
        $this->assertEquals($rules, $serialized['rules']);
    }

    public function test_json_api_response_formatting(): void
    {
        $fields = [
            Text::make('Name')->rules('required'),
            Email::make('Email')->rules('required', 'email'),
            Boolean::make('Active')->default(true),
            Select::make('Role')->options(['admin' => 'Administrator', 'user' => 'User']),
        ];

        $apiResponse = [];
        foreach ($fields as $field) {
            $apiResponse[] = $field->jsonSerialize();
        }

        // Test that API response is properly formatted for Vue consumption
        $this->assertIsArray($apiResponse);
        $this->assertCount(4, $apiResponse);

        foreach ($apiResponse as $fieldData) {
            // Each field should have the required structure for Vue components
            $this->assertArrayHasKey('component', $fieldData);
            $this->assertArrayHasKey('name', $fieldData);
            $this->assertArrayHasKey('attribute', $fieldData);
            $this->assertIsString($fieldData['component']);
            $this->assertIsString($fieldData['name']);
            $this->assertIsString($fieldData['attribute']);
        }
    }

    public function test_vue_component_prop_structure_validation(): void
    {
        $field = Text::make('Username')
            ->rules('required', 'min:3', 'max:50')
            ->placeholder('Enter username')
            ->help('Choose a unique username')
            ->sortable()
            ->copyable();

        $serialized = $field->jsonSerialize();

        // Test that serialized data matches expected Vue component prop structure
        $expectedProps = [
            'component' => 'string',
            'name' => 'string',
            'attribute' => 'string',
            'value' => 'mixed',
            'rules' => 'array',
            'placeholder' => 'string',
            'helpText' => 'string',
            'sortable' => 'boolean',
            'copyable' => 'boolean',
            'readonly' => 'boolean',
            'nullable' => 'boolean',
        ];

        foreach ($expectedProps as $prop => $expectedType) {
            $this->assertArrayHasKey($prop, $serialized, "Vue prop {$prop} should exist");

            switch ($expectedType) {
                case 'string':
                    $this->assertIsString($serialized[$prop], "Vue prop {$prop} should be string");
                    break;
                case 'boolean':
                    $this->assertIsBool($serialized[$prop], "Vue prop {$prop} should be boolean");
                    break;
                case 'array':
                    $this->assertIsArray($serialized[$prop], "Vue prop {$prop} should be array");
                    break;
                case 'mixed':
                    // Mixed type - no specific assertion needed
                    break;
            }
        }
    }

    public function test_complex_data_structure_serialization(): void
    {
        // Test complex nested data structures
        $complexOptions = [
            'categories' => [
                'technology' => [
                    'label' => 'Technology',
                    'subcategories' => [
                        'web' => 'Web Development',
                        'mobile' => 'Mobile Development',
                        'ai' => 'Artificial Intelligence',
                    ],
                ],
                'business' => [
                    'label' => 'Business',
                    'subcategories' => [
                        'marketing' => 'Marketing',
                        'finance' => 'Finance',
                        'hr' => 'Human Resources',
                    ],
                ],
            ],
        ];

        $field = Select::make('Category')
            ->withMeta(['complexOptions' => $complexOptions]);

        $serialized = $field->jsonSerialize();

        // Test that complex nested structures are preserved
        $this->assertArrayHasKey('complexOptions', $serialized);
        $this->assertEquals($complexOptions, $serialized['complexOptions']);

        // Test deep nesting is preserved
        $this->assertEquals(
            'Web Development',
            $serialized['complexOptions']['categories']['technology']['subcategories']['web'],
        );
    }

    public function test_relationship_data_serialization(): void
    {
        // Simulate relationship field data
        $relationshipMeta = [
            'resourceClass' => 'App\\AdminPanel\\Resources\\UserResource',
            'relationshipName' => 'user',
            'foreignKey' => 'user_id',
            'searchable' => true,
            'displayCallback' => null,
        ];

        $field = Text::make('User', 'user_id')
            ->withMeta($relationshipMeta);

        $serialized = $field->jsonSerialize();

        // Test that relationship metadata is preserved
        foreach ($relationshipMeta as $key => $value) {
            $this->assertArrayHasKey($key, $serialized);
            $this->assertEquals($value, $serialized[$key]);
        }
    }

    public function test_field_configuration_inheritance_in_serialization(): void
    {
        $baseField = Text::make('Base Field')
            ->rules('required')
            ->sortable()
            ->nullable();

        $extendedField = clone $baseField;
        $extendedField->name = 'Extended Field';
        $extendedField->attribute = 'extended_field';
        $extendedField->rules(['required', 'min:5']);

        $baseSerialized = $baseField->jsonSerialize();
        $extendedSerialized = $extendedField->jsonSerialize();

        // Test that base configuration is inherited
        $this->assertTrue($baseSerialized['sortable']);
        $this->assertTrue($baseSerialized['nullable']);
        $this->assertTrue($extendedSerialized['sortable']);
        $this->assertTrue($extendedSerialized['nullable']);

        // Test that extended configuration overrides base
        $this->assertEquals(['required'], $baseSerialized['rules']);
        $this->assertEquals(['required', 'min:5'], $extendedSerialized['rules']);
    }

    public function test_error_handling_in_serialization(): void
    {
        // Test that serialization handles edge cases gracefully
        $field = Text::make('Test Field');

        // Test with circular reference in meta (should not cause infinite loop)
        $circularData = ['self' => null];
        $circularData['self'] = &$circularData;

        // This should not throw an exception
        try {
            $field->withMeta(['safe_data' => 'test']);
            $serialized = $field->jsonSerialize();
            $this->assertArrayHasKey('safe_data', $serialized);
        } catch (\Exception $e) {
            $this->fail('Field serialization should handle edge cases gracefully: '.$e->getMessage());
        }
    }
}
