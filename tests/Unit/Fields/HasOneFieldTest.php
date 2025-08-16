<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\HasOne;
use JTD\AdminPanel\Tests\TestCase;

/**
 * HasOne Field Unit Tests.
 *
 * Tests for HasOne field class including validation, visibility,
 * and value handling with 100% Nova v5 compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class HasOneFieldTest extends TestCase
{
    public function test_has_one_field_creation(): void
    {
        $field = HasOne::make('Address');

        $this->assertEquals('Address', $field->name);
        $this->assertEquals('address', $field->attribute);
        $this->assertEquals('HasOneField', $field->component);
    }

    public function test_has_one_field_with_custom_attribute(): void
    {
        $field = HasOne::make('Home Address', 'home_address');

        $this->assertEquals('Home Address', $field->name);
        $this->assertEquals('home_address', $field->attribute);
    }

    public function test_has_one_field_default_properties(): void
    {
        $field = HasOne::make('Address');

        $this->assertEquals('address', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Address', $field->resourceClass);
        $this->assertNull($field->foreignKey);
        $this->assertNull($field->localKey);
        $this->assertFalse($field->isOfMany);
        $this->assertNull($field->ofManyRelationship);
        $this->assertNull($field->ofManyResourceClass);
    }

    public function test_has_one_field_only_on_detail_by_default(): void
    {
        $field = HasOne::make('Address');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    public function test_has_one_field_nova_make_syntax_with_resource_class(): void
    {
        $field = HasOne::make('Address', 'user_address', 'App\\Nova\\AddressResource');

        $this->assertEquals('Address', $field->name);
        $this->assertEquals('user_address', $field->attribute);
        $this->assertEquals('App\\Nova\\AddressResource', $field->resourceClass);
    }

    public function test_has_one_field_nova_make_syntax_with_callback(): void
    {
        $callback = function ($resource) {
            return $resource->address;
        };

        $field = HasOne::make('Address', 'address', $callback);

        $this->assertEquals('Address', $field->name);
        $this->assertEquals('address', $field->attribute);
        $this->assertEquals($callback, $field->resolveCallback);
    }

    public function test_has_one_field_of_many_creation(): void
    {
        $field = HasOne::ofMany('Latest Post', 'latestPost', 'App\\Nova\\PostResource');

        $this->assertEquals('Latest Post', $field->name);
        $this->assertTrue($field->isOfMany);
        $this->assertEquals('latestPost', $field->ofManyRelationship);
        $this->assertEquals('App\\Nova\\PostResource', $field->ofManyResourceClass);
        $this->assertEquals('App\\Nova\\PostResource', $field->resourceClass);
    }

    public function test_has_one_field_resource_configuration(): void
    {
        $field = HasOne::make('Address')->resource('App\\Models\\AddressResource');

        $this->assertEquals('App\\Models\\AddressResource', $field->resourceClass);
    }

    public function test_has_one_field_relationship_configuration(): void
    {
        $field = HasOne::make('Address')->relationship('user_address');

        $this->assertEquals('user_address', $field->relationshipName);
    }

    public function test_has_one_field_foreign_key_configuration(): void
    {
        $field = HasOne::make('Address')->foreignKey('user_id');

        $this->assertEquals('user_id', $field->foreignKey);
    }

    public function test_has_one_field_local_key_configuration(): void
    {
        $field = HasOne::make('Address')->localKey('uuid');

        $this->assertEquals('uuid', $field->localKey);
    }

    public function test_has_one_field_resolve_with_related_model(): void
    {
        // Mock related model
        $relatedModel = new class
        {
            public function getKey()
            {
                return 123;
            }
        };

        // Mock resource class
        $resourceClass = new class($relatedModel)
        {
            public function __construct($model)
            {
                $this->model = $model;
            }

            public function title()
            {
                return 'Test Address';
            }
        };

        // Mock resource with relationship
        $resource = new class($relatedModel)
        {
            public $address;

            public function __construct($address)
            {
                $this->address = $address;
            }
        };

        $field = HasOne::make('Address');
        $field->resourceClass = get_class($resourceClass);

        // Mock the resource class instantiation
        $field->resolveCallback = function () use ($resourceClass) {
            return $resourceClass;
        };

        $field->resolve($resource);

        $this->assertIsArray($field->value);
        $this->assertEquals(123, $field->value['id']);
        $this->assertTrue($field->value['exists']);
        $this->assertEquals(get_class($resourceClass), $field->value['resource_class']);
    }

    public function test_has_one_field_resolve_with_null_related_model(): void
    {
        // Mock resource with null relationship
        $resource = new class
        {
            public $address = null;
        };

        $field = HasOne::make('Address');

        $field->resolve($resource);

        $this->assertIsArray($field->value);
        $this->assertNull($field->value['id']);
        $this->assertNull($field->value['title']);
        $this->assertFalse($field->value['exists']);
        $this->assertEquals('App\\AdminPanel\\Resources\\Address', $field->value['resource_class']);
    }

    public function test_has_one_field_resolve_of_many_relationship(): void
    {
        // Mock related model
        $relatedModel = new class
        {
            public function getKey()
            {
                return 456;
            }
        };

        // Mock resource class
        $resourceClass = new class($relatedModel)
        {
            public function __construct($model)
            {
                $this->model = $model;
            }

            public function title()
            {
                return 'Latest Post';
            }
        };

        // Mock resource with "of many" relationship
        $resource = new class($relatedModel)
        {
            public $latestPost;

            public function __construct($latestPost)
            {
                $this->latestPost = $latestPost;
            }
        };

        $field = HasOne::ofMany('Latest Post', 'latestPost', get_class($resourceClass));

        // Mock the resource class instantiation
        $field->resolveCallback = function () use ($resourceClass) {
            return $resourceClass;
        };

        $field->resolve($resource);

        $this->assertIsArray($field->value);
        $this->assertEquals(456, $field->value['id']);
        $this->assertTrue($field->value['exists']);
        $this->assertEquals(get_class($resourceClass), $field->value['resource_class']);
    }

    public function test_has_one_field_guess_resource_class(): void
    {
        $field = HasOne::make('User Profile', 'user_profile');

        $this->assertEquals('App\\AdminPanel\\Resources\\UserProfile', $field->resourceClass);
    }

    public function test_has_one_field_meta_includes_all_properties(): void
    {
        $field = HasOne::make('Address')
            ->resource('App\\Models\\AddressResource')
            ->relationship('user_address')
            ->foreignKey('user_id')
            ->localKey('uuid');

        $meta = $field->meta();

        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('foreignKey', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('isOfMany', $meta);
        $this->assertArrayHasKey('ofManyRelationship', $meta);
        $this->assertArrayHasKey('ofManyResourceClass', $meta);

        $this->assertEquals('App\\Models\\AddressResource', $meta['resourceClass']);
        $this->assertEquals('user_address', $meta['relationshipName']);
        $this->assertEquals('user_id', $meta['foreignKey']);
        $this->assertEquals('uuid', $meta['localKey']);
        $this->assertFalse($meta['isOfMany']);
        $this->assertNull($meta['ofManyRelationship']);
        $this->assertNull($meta['ofManyResourceClass']);
    }

    public function test_has_one_field_of_many_meta(): void
    {
        $field = HasOne::ofMany('Latest Post', 'latestPost', 'App\\Nova\\PostResource');

        $meta = $field->meta();

        $this->assertTrue($meta['isOfMany']);
        $this->assertEquals('latestPost', $meta['ofManyRelationship']);
        $this->assertEquals('App\\Nova\\PostResource', $meta['ofManyResourceClass']);
    }

    public function test_has_one_field_json_serialization(): void
    {
        $field = HasOne::make('User Address')
            ->resource('App\\Resources\\AddressResource')
            ->help('Manage user address');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Address', $json['name']);
        $this->assertEquals('user_address', $json['attribute']);
        $this->assertEquals('HasOneField', $json['component']);
        $this->assertEquals('App\\Resources\\AddressResource', $json['resourceClass']);
        $this->assertEquals('Manage user address', $json['helpText']);
    }

    public function test_has_one_field_fill_does_nothing_by_default(): void
    {
        $request = new Request;
        $model = new class {};

        $field = HasOne::make('Address');

        // Should not throw any exceptions
        $field->fill($request, $model);

        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function test_has_one_field_fill_with_callback(): void
    {
        $request = new Request(['address' => 'test']);
        $model = new class
        {
            public $filled = false;
        };

        $field = HasOne::make('Address');
        $field->fillCallback = function ($request, $model, $attribute) {
            $model->filled = true;
        };

        $field->fill($request, $model);

        $this->assertTrue($model->filled);
    }

    public function test_has_one_field_get_related_model(): void
    {
        // Create a simple test model class
        $relatedModel = new class extends \Illuminate\Database\Eloquent\Model
        {
            public function getKey()
            {
                return 123;
            }
        };

        // Create a parent model with the relationship
        $parentModel = new class extends \Illuminate\Database\Eloquent\Model
        {
            public $address;

            public function __construct($address = null)
            {
                parent::__construct();
                $this->address = $address;
            }
        };

        $parentModel = new $parentModel($relatedModel);

        $field = HasOne::make('Address');
        $result = $field->getRelatedModel($parentModel);

        $this->assertSame($relatedModel, $result);
    }

    public function test_has_one_field_get_related_model_of_many(): void
    {
        // Create a simple test model class
        $relatedModel = new class extends \Illuminate\Database\Eloquent\Model
        {
            public function getKey()
            {
                return 456;
            }
        };

        // Create a parent model with the relationship
        $parentModel = new class extends \Illuminate\Database\Eloquent\Model
        {
            public $latestPost;

            public function __construct($latestPost = null)
            {
                parent::__construct();
                $this->latestPost = $latestPost;
            }
        };

        $parentModel = new $parentModel($relatedModel);

        $field = HasOne::ofMany('Latest Post', 'latestPost', 'App\\Nova\\PostResource');
        $result = $field->getRelatedModel($parentModel);

        $this->assertSame($relatedModel, $result);
    }
}
