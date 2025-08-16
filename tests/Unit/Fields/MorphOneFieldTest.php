<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\MorphOne;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MorphOne Field Unit Test.
 *
 * Tests the MorphOne field class functionality including field creation,
 * configuration, resolution, and Nova v5 compatibility.
 */
class MorphOneFieldTest extends TestCase
{
    public function test_morph_one_field_creation(): void
    {
        $field = MorphOne::make('Image');

        $this->assertEquals('Image', $field->name);
        $this->assertEquals('image', $field->attribute);
        $this->assertEquals('MorphOneField', $field->component);
        $this->assertEquals('image', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Image', $field->resourceClass);
    }

    public function test_morph_one_field_with_custom_attribute(): void
    {
        $field = MorphOne::make('Profile Image', 'profile_image');

        $this->assertEquals('Profile Image', $field->name);
        $this->assertEquals('profile_image', $field->attribute);
        $this->assertEquals('profile_image', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\ProfileImage', $field->resourceClass);
    }

    public function test_morph_one_field_default_properties(): void
    {
        $field = MorphOne::make('Image');

        $this->assertEquals('image', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Image', $field->resourceClass);
        $this->assertNull($field->morphType);
        $this->assertNull($field->morphId);
        $this->assertNull($field->localKey);
        $this->assertFalse($field->isOfMany);
        $this->assertNull($field->ofManyRelationship);
        $this->assertNull($field->ofManyResourceClass);
    }

    public function test_morph_one_field_resource_configuration(): void
    {
        $field = MorphOne::make('Image')->resource('App\\Models\\ImageResource');

        $this->assertEquals('App\\Models\\ImageResource', $field->resourceClass);
    }

    public function test_morph_one_field_relationship_configuration(): void
    {
        $field = MorphOne::make('Image')->relationship('featuredImage');

        $this->assertEquals('featuredImage', $field->relationshipName);
    }

    public function test_morph_one_field_morph_type_configuration(): void
    {
        $field = MorphOne::make('Image')->morphType('imageable_type');

        $this->assertEquals('imageable_type', $field->morphType);
    }

    public function test_morph_one_field_morph_id_configuration(): void
    {
        $field = MorphOne::make('Image')->morphId('imageable_id');

        $this->assertEquals('imageable_id', $field->morphId);
    }

    public function test_morph_one_field_local_key_configuration(): void
    {
        $field = MorphOne::make('Image')->localKey('id');

        $this->assertEquals('id', $field->localKey);
    }

    public function test_morph_one_field_of_many_creation(): void
    {
        $field = MorphOne::ofMany('Latest Image', 'latestImage', 'App\\Resources\\ImageResource');

        $this->assertEquals('Latest Image', $field->name);
        $this->assertEquals('latest_image', $field->attribute);
        $this->assertTrue($field->isOfMany);
        $this->assertEquals('latestImage', $field->ofManyRelationship);
        $this->assertEquals('App\\Resources\\ImageResource', $field->ofManyResourceClass);
        $this->assertEquals('App\\Resources\\ImageResource', $field->resourceClass);
    }

    public function test_morph_one_field_is_only_shown_on_detail_by_default(): void
    {
        $field = MorphOne::make('Image');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    public function test_morph_one_field_can_be_configured_for_different_views(): void
    {
        $field = MorphOne::make('Image')
            ->showOnIndex()
            ->showOnCreating()
            ->showOnUpdating();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    public function test_morph_one_field_fill_with_custom_callback(): void
    {
        $request = new Request(['image' => 'test']);
        $model = new \stdClass;
        $callbackCalled = false;

        $field = MorphOne::make('Image');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('image', $attribute);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
    }

    public function test_morph_one_field_meta_includes_all_properties(): void
    {
        $field = MorphOne::make('Image')
            ->resource('App\\Models\\ImageResource')
            ->relationship('featuredImage')
            ->morphType('imageable_type')
            ->morphId('imageable_id')
            ->localKey('id');

        $meta = $field->meta();

        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('morphType', $meta);
        $this->assertArrayHasKey('morphId', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('isOfMany', $meta);
        $this->assertArrayHasKey('ofManyRelationship', $meta);
        $this->assertArrayHasKey('ofManyResourceClass', $meta);

        $this->assertEquals('App\\Models\\ImageResource', $meta['resourceClass']);
        $this->assertEquals('featuredImage', $meta['relationshipName']);
        $this->assertEquals('imageable_type', $meta['morphType']);
        $this->assertEquals('imageable_id', $meta['morphId']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertFalse($meta['isOfMany']);
        $this->assertNull($meta['ofManyRelationship']);
        $this->assertNull($meta['ofManyResourceClass']);
    }

    public function test_morph_one_field_of_many_meta(): void
    {
        $field = MorphOne::ofMany('Latest Image', 'latestImage', 'App\\Resources\\ImageResource');

        $meta = $field->meta();

        $this->assertTrue($meta['isOfMany']);
        $this->assertEquals('latestImage', $meta['ofManyRelationship']);
        $this->assertEquals('App\\Resources\\ImageResource', $meta['ofManyResourceClass']);
    }

    public function test_morph_one_field_json_serialization(): void
    {
        $field = MorphOne::make('Featured Image')
            ->resource('App\\Resources\\ImageResource')
            ->morphType('imageable_type')
            ->morphId('imageable_id')
            ->help('Manage featured image');

        $json = $field->jsonSerialize();

        $this->assertEquals('Featured Image', $json['name']);
        $this->assertEquals('featured_image', $json['attribute']);
        $this->assertEquals('MorphOneField', $json['component']);
        $this->assertEquals('App\\Resources\\ImageResource', $json['resourceClass']);
        $this->assertEquals('imageable_type', $json['morphType']);
        $this->assertEquals('imageable_id', $json['morphId']);
        $this->assertEquals('Manage featured image', $json['helpText']);
    }

    public function test_morph_one_field_complex_configuration(): void
    {
        $field = MorphOne::make('Profile Avatar')
            ->resource('App\\Resources\\AvatarResource')
            ->relationship('profileAvatar')
            ->morphType('avatarable_type')
            ->morphId('avatarable_id')
            ->localKey('id');

        // Test all configurations are set
        $this->assertEquals('App\\Resources\\AvatarResource', $field->resourceClass);
        $this->assertEquals('profileAvatar', $field->relationshipName);
        $this->assertEquals('avatarable_type', $field->morphType);
        $this->assertEquals('avatarable_id', $field->morphId);
        $this->assertEquals('id', $field->localKey);
    }

    public function test_morph_one_field_guesses_resource_class_correctly(): void
    {
        $field = MorphOne::make('Profile Image', 'profile_image');

        $this->assertEquals('App\\AdminPanel\\Resources\\ProfileImage', $field->resourceClass);
    }

    public function test_morph_one_field_with_resolve_callback(): void
    {
        $callbackCalled = false;
        $resolveCallback = function ($value, $resource, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;

            return $value;
        };

        $field = MorphOne::make('Image', 'image', $resolveCallback);

        $this->assertEquals($resolveCallback, $field->resolveCallback);
    }

    public function test_morph_one_field_supports_field_chaining(): void
    {
        $field = MorphOne::make('Image')
            ->resource('App\\Nova\\ImageResource')
            ->relationship('featuredImage')
            ->morphType('imageable_type')
            ->morphId('imageable_id')
            ->localKey('id')
            ->help('Featured image management')
            ->showOnIndex();

        $this->assertEquals('App\\Nova\\ImageResource', $field->resourceClass);
        $this->assertEquals('featuredImage', $field->relationshipName);
        $this->assertEquals('imageable_type', $field->morphType);
        $this->assertEquals('imageable_id', $field->morphId);
        $this->assertEquals('id', $field->localKey);
        $this->assertEquals('Featured image management', $field->helpText);
        $this->assertTrue($field->showOnIndex);
    }

    public function test_morph_one_field_of_many_chaining(): void
    {
        $field = MorphOne::ofMany('Latest Image', 'latestImage', 'App\\Resources\\ImageResource')
            ->morphType('imageable_type')
            ->morphId('imageable_id')
            ->localKey('id')
            ->help('Latest image in collection');

        $this->assertTrue($field->isOfMany);
        $this->assertEquals('latestImage', $field->ofManyRelationship);
        $this->assertEquals('App\\Resources\\ImageResource', $field->ofManyResourceClass);
        $this->assertEquals('imageable_type', $field->morphType);
        $this->assertEquals('imageable_id', $field->morphId);
        $this->assertEquals('id', $field->localKey);
        $this->assertEquals('Latest image in collection', $field->helpText);
    }

    public function test_morph_one_field_make_method_signature(): void
    {
        // Test basic make method
        $field1 = MorphOne::make('Image');
        $this->assertEquals('Image', $field1->name);
        $this->assertEquals('image', $field1->attribute);

        // Test make method with attribute
        $field2 = MorphOne::make('Image', 'featured_image');
        $this->assertEquals('Image', $field2->name);
        $this->assertEquals('featured_image', $field2->attribute);

        // Test make method with callback
        $callback = function ($value) {
            return $value;
        };
        $field3 = MorphOne::make('Image', 'image', $callback);
        $this->assertEquals('Image', $field3->name);
        $this->assertEquals('image', $field3->attribute);
        $this->assertEquals($callback, $field3->resolveCallback);
    }

    public function test_morph_one_field_polymorphic_properties(): void
    {
        $field = MorphOne::make('Image')
            ->morphType('imageable_type')
            ->morphId('imageable_id');

        // Test that polymorphic properties are set correctly
        $this->assertEquals('imageable_type', $field->morphType);
        $this->assertEquals('imageable_id', $field->morphId);

        // Test that these are included in meta
        $meta = $field->meta();
        $this->assertEquals('imageable_type', $meta['morphType']);
        $this->assertEquals('imageable_id', $meta['morphId']);
    }

    public function test_morph_one_field_of_many_vs_regular(): void
    {
        // Test regular MorphOne
        $regularField = MorphOne::make('Image');
        $this->assertFalse($regularField->isOfMany);
        $this->assertNull($regularField->ofManyRelationship);
        $this->assertNull($regularField->ofManyResourceClass);

        // Test MorphOne of many
        $ofManyField = MorphOne::ofMany('Latest Image', 'latestImage', 'App\\Resources\\ImageResource');
        $this->assertTrue($ofManyField->isOfMany);
        $this->assertEquals('latestImage', $ofManyField->ofManyRelationship);
        $this->assertEquals('App\\Resources\\ImageResource', $ofManyField->ofManyResourceClass);
    }

    public function test_morph_one_field_component_name(): void
    {
        $field = MorphOne::make('Image');

        $this->assertEquals('MorphOneField', $field->component);
    }

    public function test_morph_one_field_attribute_guessing(): void
    {
        // Test various field names and their attribute guessing
        $testCases = [
            ['Featured Image', 'featured_image'],
            ['Profile Avatar', 'profile_avatar'],
            ['Cover Photo', 'cover_photo'],
            ['Thumbnail', 'thumbnail'],
        ];

        foreach ($testCases as [$name, $expectedAttribute]) {
            $field = MorphOne::make($name);
            $this->assertEquals($expectedAttribute, $field->attribute);
            $this->assertEquals($expectedAttribute, $field->relationshipName);
        }
    }
}
