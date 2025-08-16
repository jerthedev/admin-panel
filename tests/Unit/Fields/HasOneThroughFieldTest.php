<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\HasOneThrough;
use JTD\AdminPanel\Tests\TestCase;

/**
 * HasOneThrough Field Unit Test.
 *
 * Tests the HasOneThrough field class functionality including field creation,
 * configuration, resolution, and Nova v5 compatibility.
 */
class HasOneThroughFieldTest extends TestCase
{
    public function test_has_one_through_field_creation(): void
    {
        $field = HasOneThrough::make('Owner');

        $this->assertEquals('Owner', $field->name);
        $this->assertEquals('owner', $field->attribute);
        $this->assertEquals('HasOneThroughField', $field->component);
        $this->assertEquals('owner', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Owner', $field->resourceClass);
    }

    public function test_has_one_through_field_with_custom_attribute(): void
    {
        $field = HasOneThrough::make('Car Owner', 'car_owner');

        $this->assertEquals('Car Owner', $field->name);
        $this->assertEquals('car_owner', $field->attribute);
        $this->assertEquals('car_owner', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\CarOwner', $field->resourceClass);
    }

    public function test_has_one_through_field_with_resource_class(): void
    {
        $field = HasOneThrough::make('Owner', 'owner', 'App\\Nova\\OwnerResource');

        $this->assertEquals('Owner', $field->name);
        $this->assertEquals('owner', $field->attribute);
        $this->assertEquals('App\\Nova\\OwnerResource', $field->resourceClass);
    }

    public function test_has_one_through_field_default_properties(): void
    {
        $field = HasOneThrough::make('Owner');

        $this->assertEquals('owner', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Owner', $field->resourceClass);
        $this->assertNull($field->through);
        $this->assertNull($field->firstKey);
        $this->assertNull($field->secondKey);
        $this->assertNull($field->localKey);
        $this->assertNull($field->secondLocalKey);
    }

    public function test_has_one_through_field_resource_configuration(): void
    {
        $field = HasOneThrough::make('Owner')->resource('App\\Models\\OwnerResource');

        $this->assertEquals('App\\Models\\OwnerResource', $field->resourceClass);
    }

    public function test_has_one_through_field_relationship_configuration(): void
    {
        $field = HasOneThrough::make('Owner')->relationship('carOwner');

        $this->assertEquals('carOwner', $field->relationshipName);
    }

    public function test_has_one_through_field_through_configuration(): void
    {
        $field = HasOneThrough::make('Owner')->through('App\\Models\\Car');

        $this->assertEquals('App\\Models\\Car', $field->through);
    }

    public function test_has_one_through_field_first_key_configuration(): void
    {
        $field = HasOneThrough::make('Owner')->firstKey('mechanic_id');

        $this->assertEquals('mechanic_id', $field->firstKey);
    }

    public function test_has_one_through_field_second_key_configuration(): void
    {
        $field = HasOneThrough::make('Owner')->secondKey('car_id');

        $this->assertEquals('car_id', $field->secondKey);
    }

    public function test_has_one_through_field_local_key_configuration(): void
    {
        $field = HasOneThrough::make('Owner')->localKey('id');

        $this->assertEquals('id', $field->localKey);
    }

    public function test_has_one_through_field_second_local_key_configuration(): void
    {
        $field = HasOneThrough::make('Owner')->secondLocalKey('id');

        $this->assertEquals('id', $field->secondLocalKey);
    }

    public function test_has_one_through_field_is_only_shown_on_detail_by_default(): void
    {
        $field = HasOneThrough::make('Owner');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    public function test_has_one_through_field_can_be_configured_for_different_views(): void
    {
        $field = HasOneThrough::make('Owner')
            ->showOnIndex()
            ->showOnCreating()
            ->showOnUpdating();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    public function test_has_one_through_field_fill_with_custom_callback(): void
    {
        $request = new Request(['owner' => 'test']);
        $model = new \stdClass;
        $callbackCalled = false;

        $field = HasOneThrough::make('Owner');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('owner', $attribute);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
    }

    public function test_has_one_through_field_meta_includes_all_properties(): void
    {
        $field = HasOneThrough::make('Owner')
            ->resource('App\\Models\\OwnerResource')
            ->relationship('carOwner')
            ->through('App\\Models\\Car')
            ->firstKey('mechanic_id')
            ->secondKey('car_id')
            ->localKey('id')
            ->secondLocalKey('id');

        $meta = $field->meta();

        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('through', $meta);
        $this->assertArrayHasKey('firstKey', $meta);
        $this->assertArrayHasKey('secondKey', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('secondLocalKey', $meta);

        $this->assertEquals('App\\Models\\OwnerResource', $meta['resourceClass']);
        $this->assertEquals('carOwner', $meta['relationshipName']);
        $this->assertEquals('App\\Models\\Car', $meta['through']);
        $this->assertEquals('mechanic_id', $meta['firstKey']);
        $this->assertEquals('car_id', $meta['secondKey']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertEquals('id', $meta['secondLocalKey']);
    }

    public function test_has_one_through_field_json_serialization(): void
    {
        $field = HasOneThrough::make('Car Owner')
            ->resource('App\\Resources\\OwnerResource')
            ->through('App\\Models\\Car')
            ->help('View the car owner through the car relationship');

        $json = $field->jsonSerialize();

        $this->assertEquals('Car Owner', $json['name']);
        $this->assertEquals('car_owner', $json['attribute']);
        $this->assertEquals('HasOneThroughField', $json['component']);
        $this->assertEquals('App\\Resources\\OwnerResource', $json['resourceClass']);
        $this->assertEquals('App\\Models\\Car', $json['through']);
        $this->assertEquals('View the car owner through the car relationship', $json['helpText']);
    }

    public function test_has_one_through_field_complex_configuration(): void
    {
        $field = HasOneThrough::make('Vehicle Owner')
            ->resource('App\\Resources\\OwnerResource')
            ->relationship('vehicleOwner')
            ->through('App\\Models\\Vehicle')
            ->firstKey('mechanic_id')
            ->secondKey('vehicle_id')
            ->localKey('id')
            ->secondLocalKey('id');

        // Test all configurations are set
        $this->assertEquals('App\\Resources\\OwnerResource', $field->resourceClass);
        $this->assertEquals('vehicleOwner', $field->relationshipName);
        $this->assertEquals('App\\Models\\Vehicle', $field->through);
        $this->assertEquals('mechanic_id', $field->firstKey);
        $this->assertEquals('vehicle_id', $field->secondKey);
        $this->assertEquals('id', $field->localKey);
        $this->assertEquals('id', $field->secondLocalKey);
    }

    public function test_has_one_through_field_get_related_model_method_exists(): void
    {
        $field = HasOneThrough::make('Owner');

        // Test that the getRelatedModel method exists
        $this->assertTrue(method_exists($field, 'getRelatedModel'));
    }

    public function test_has_one_through_field_guesses_resource_class_correctly(): void
    {
        $field = HasOneThrough::make('Car Owner', 'car_owner');

        $this->assertEquals('App\\AdminPanel\\Resources\\CarOwner', $field->resourceClass);
    }

    public function test_has_one_through_field_with_resolve_callback(): void
    {
        $callbackCalled = false;
        $resolveCallback = function ($value, $resource, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;

            return $value;
        };

        $field = HasOneThrough::make('Owner', 'owner', $resolveCallback);

        $this->assertEquals($resolveCallback, $field->resolveCallback);
    }

    public function test_has_one_through_field_nova_style_make_method(): void
    {
        // Test Nova-style syntax with resource class as third parameter
        $field = HasOneThrough::make('Owner', 'owner', 'App\\Nova\\OwnerResource');

        $this->assertEquals('Owner', $field->name);
        $this->assertEquals('owner', $field->attribute);
        $this->assertEquals('App\\Nova\\OwnerResource', $field->resourceClass);
    }

    public function test_has_one_through_field_make_method_with_callback(): void
    {
        // Test with callback as third parameter
        $callback = function ($value) {
            return $value;
        };

        $field = HasOneThrough::make('Owner', 'owner', $callback);

        $this->assertEquals('Owner', $field->name);
        $this->assertEquals('owner', $field->attribute);
        $this->assertEquals($callback, $field->resolveCallback);
    }

    public function test_has_one_through_field_supports_field_chaining(): void
    {
        $field = HasOneThrough::make('Owner')
            ->resource('App\\Nova\\OwnerResource')
            ->relationship('carOwner')
            ->through('App\\Models\\Car')
            ->firstKey('mechanic_id')
            ->secondKey('car_id')
            ->localKey('id')
            ->secondLocalKey('id')
            ->help('Car owner through vehicle')
            ->showOnIndex();

        $this->assertEquals('App\\Nova\\OwnerResource', $field->resourceClass);
        $this->assertEquals('carOwner', $field->relationshipName);
        $this->assertEquals('App\\Models\\Car', $field->through);
        $this->assertEquals('mechanic_id', $field->firstKey);
        $this->assertEquals('car_id', $field->secondKey);
        $this->assertEquals('id', $field->localKey);
        $this->assertEquals('id', $field->secondLocalKey);
        $this->assertEquals('Car owner through vehicle', $field->helpText);
        $this->assertTrue($field->showOnIndex);
    }
}
