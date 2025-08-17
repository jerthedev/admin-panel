<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\ID;
use JTD\AdminPanel\Tests\TestCase;

/**
 * ID Field Integration Tests
 *
 * Tests for ID field integration with Laravel, Inertia, and Vue components.
 * Validates PHP/Vue interoperability and CRUD operations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class IDFieldIntegrationTest extends TestCase
{
    public function test_id_field_serializes_correctly_for_inertia(): void
    {
        $field = ID::make('User ID', 'user_id')
            ->asBigInt()
            ->copyable()
            ->help('Unique identifier for the user');

        $serialized = $field->jsonSerialize();

        // Test basic field structure
        $this->assertEquals('User ID', $serialized['name']);
        $this->assertEquals('user_id', $serialized['attribute']);
        $this->assertEquals('IDField', $serialized['component']);

        // Test Nova API compatibility
        $this->assertTrue($serialized['sortable']);
        $this->assertFalse($serialized['showOnCreation']);
        $this->assertTrue($serialized['showOnIndex']);
        $this->assertTrue($serialized['showOnDetail']);
        $this->assertTrue($serialized['showOnUpdate']);

        // Test ID-specific features
        $this->assertTrue($serialized['asBigInt']);
        $this->assertTrue($serialized['copyable']);
        $this->assertEquals('Unique identifier for the user', $serialized['helpText']);
    }

    public function test_id_field_default_serialization(): void
    {
        $field = ID::make();

        $serialized = $field->jsonSerialize();

        // Test defaults match Nova behavior
        $this->assertEquals('ID', $serialized['name']);
        $this->assertEquals('id', $serialized['attribute']);
        $this->assertEquals('IDField', $serialized['component']);
        $this->assertTrue($serialized['sortable']);
        $this->assertFalse($serialized['showOnCreation']);
        $this->assertFalse($serialized['asBigInt']);
    }

    public function test_id_field_resolves_values_correctly(): void
    {
        // Test numeric ID
        $numericField = ID::make('ID');
        $numericResource = (object) ['id' => 123];
        $numericField->resolve($numericResource);
        $this->assertEquals(123, $numericField->value);

        // Test string ID (UUID)
        $stringField = ID::make('UUID', 'uuid');
        $stringResource = (object) ['uuid' => 'abc-123-def-456'];
        $stringField->resolve($stringResource);
        $this->assertEquals('abc-123-def-456', $stringField->value);

        // Test big integer ID
        $bigIntField = ID::make('Big ID', 'big_id')->asBigInt();
        $bigIntResource = (object) ['big_id' => '9223372036854775807'];
        $bigIntField->resolve($bigIntResource);
        $this->assertEquals('9223372036854775807', $bigIntField->value);

        // Test null ID
        $nullField = ID::make('Null ID', 'null_id');
        $nullResource = (object) ['null_id' => null];
        $nullField->resolve($nullResource);
        $this->assertNull($nullField->value);
    }

    public function test_id_field_fill_behavior(): void
    {
        // ID fields should typically not be fillable from requests
        $field = ID::make('ID');
        $model = new \stdClass();
        $request = new Request(['id' => 999]);

        // Fill should modify the model's ID (base behavior)
        $field->fill($request, $model);

        // ID will be set via fill (this is the base Field behavior)
        $this->assertTrue(property_exists($model, 'id'));
        $this->assertEquals(999, $model->id);
    }

    public function test_id_field_with_custom_attribute(): void
    {
        $field = ID::make('User ID', 'user_id');
        $resource = (object) ['user_id' => 456];

        $field->resolve($resource);
        $this->assertEquals(456, $field->value);

        $serialized = $field->jsonSerialize();
        $this->assertEquals('User ID', $serialized['name']);
        $this->assertEquals('user_id', $serialized['attribute']);
    }

    public function test_id_field_as_big_int_integration(): void
    {
        $field = ID::make('Big Integer ID')->asBigInt();
        
        $serialized = $field->jsonSerialize();
        $this->assertTrue($serialized['asBigInt']);

        // Test with very large integer
        $resource = (object) ['id' => '18446744073709551615'];
        $field->resolve($resource);
        $this->assertEquals('18446744073709551615', $field->value);
    }

    public function test_id_field_copyable_integration(): void
    {
        $copyableField = ID::make('Copyable ID')->copyable();
        $nonCopyableField = ID::make('Non-Copyable ID');

        $copyableSerialized = $copyableField->jsonSerialize();
        $nonCopyableSerialized = $nonCopyableField->jsonSerialize();

        $this->assertTrue($copyableSerialized['copyable']);
        $this->assertFalse($nonCopyableSerialized['copyable']);
    }

    public function test_id_field_visibility_integration(): void
    {
        $field = ID::make('Test ID');

        // Test default visibility (Nova behavior)
        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnUpdate);
        $this->assertFalse($field->showOnCreation); // IDs hidden on creation by default

        // Test custom visibility
        $customField = ID::make('Custom ID')->showOnCreating();
        $this->assertTrue($customField->showOnCreation);
    }

    public function test_id_field_sortable_integration(): void
    {
        $field = ID::make('Sortable ID');
        
        // ID fields should be sortable by default
        $this->assertTrue($field->sortable);

        $serialized = $field->jsonSerialize();
        $this->assertTrue($serialized['sortable']);
    }

    public function test_id_field_crud_operations(): void
    {
        // Test Create operation (ID typically not provided)
        $createField = ID::make('ID');
        $createModel = new \stdClass();
        $createRequest = new Request([]);

        $createField->fill($createRequest, $createModel);
        // ID should not be set during creation when not in request
        $this->assertFalse(property_exists($createModel, 'id'));

        // Test Read operation
        $readField = ID::make('ID');
        $readResource = (object) ['id' => 123];

        $readField->resolve($readResource);
        $this->assertEquals(123, $readField->value);

        // Test Update operation (ID will be changed by base fill behavior)
        $updateField = ID::make('ID');
        $updateModel = new \stdClass();
        $updateModel->id = 456;
        $updateRequest = new Request(['id' => 999]); // Attempt to change ID

        $updateField->fill($updateRequest, $updateModel);
        // ID will be changed (this is base Field behavior - applications should handle readonly logic)
        $this->assertEquals(999, $updateModel->id);
    }

    public function test_id_field_inertia_response_structure(): void
    {
        $field = ID::make('ID', 'id')
            ->asBigInt()
            ->copyable()
            ->help('Primary key')
            ->sortable();

        $resource = (object) ['id' => '9007199254740991'];
        $field->resolve($resource);

        $inertiaData = $field->jsonSerialize();

        // Verify complete structure for Inertia/Vue consumption
        $expectedKeys = [
            'name', 'attribute', 'component', 'value', 'sortable',
            'showOnIndex', 'showOnDetail', 'showOnCreation', 'showOnUpdate',
            'asBigInt', 'copyable', 'helpText'
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $inertiaData, "Missing key: {$key}");
        }

        // Verify values
        $this->assertEquals('ID', $inertiaData['name']);
        $this->assertEquals('id', $inertiaData['attribute']);
        $this->assertEquals('IDField', $inertiaData['component']);
        $this->assertEquals('9007199254740991', $inertiaData['value']);
        $this->assertTrue($inertiaData['asBigInt']);
        $this->assertTrue($inertiaData['copyable']);
        $this->assertEquals('Primary key', $inertiaData['helpText']);
    }

    public function test_id_field_edge_cases(): void
    {
        // Test with zero ID
        $zeroField = ID::make('Zero ID');
        $zeroResource = (object) ['id' => 0];
        $zeroField->resolve($zeroResource);
        $this->assertEquals(0, $zeroField->value);

        // Test with negative ID
        $negativeField = ID::make('Negative ID');
        $negativeResource = (object) ['id' => -1];
        $negativeField->resolve($negativeResource);
        $this->assertEquals(-1, $negativeField->value);

        // Test with string numeric ID
        $stringNumericField = ID::make('String Numeric ID');
        $stringNumericResource = (object) ['id' => '12345'];
        $stringNumericField->resolve($stringNumericResource);
        $this->assertEquals('12345', $stringNumericField->value);
    }

    public function test_id_field_nova_api_method_chaining(): void
    {
        $field = ID::make('Chained ID', 'chained_id')
            ->asBigInt()
            ->copyable()
            ->help('Test chaining')
            ->sortable();

        // Test that all methods return the field instance for chaining
        $this->assertInstanceOf(ID::class, $field);
        $this->assertTrue($field->asBigInt);
        $this->assertTrue($field->copyable);
        $this->assertEquals('Test chaining', $field->helpText);
        $this->assertTrue($field->sortable);
    }
}
