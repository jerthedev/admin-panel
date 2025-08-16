<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\HasManyThrough;
use JTD\AdminPanel\Tests\TestCase;

/**
 * HasManyThrough Field Unit Test.
 *
 * Tests the HasManyThrough field class functionality including field creation,
 * configuration, resolution, and Nova v5 compatibility.
 */
class HasManyThroughFieldTest extends TestCase
{
    public function test_has_many_through_field_creation(): void
    {
        $field = HasManyThrough::make('Posts');

        $this->assertEquals('Posts', $field->name);
        $this->assertEquals('posts', $field->attribute);
        $this->assertEquals('HasManyThroughField', $field->component);
        $this->assertEquals('posts', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Posts', $field->resourceClass);
    }

    public function test_has_many_through_field_with_custom_attribute(): void
    {
        $field = HasManyThrough::make('User Posts', 'user_posts');

        $this->assertEquals('User Posts', $field->name);
        $this->assertEquals('user_posts', $field->attribute);
        $this->assertEquals('user_posts', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\UserPosts', $field->resourceClass);
    }

    public function test_has_many_through_field_with_resource_class(): void
    {
        $field = HasManyThrough::make('Posts', 'posts', 'App\\Nova\\PostResource');

        $this->assertEquals('Posts', $field->name);
        $this->assertEquals('posts', $field->attribute);
        $this->assertEquals('App\\Nova\\PostResource', $field->resourceClass);
    }

    public function test_has_many_through_field_default_properties(): void
    {
        $field = HasManyThrough::make('Posts');

        $this->assertEquals('posts', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Posts', $field->resourceClass);
        $this->assertNull($field->through);
        $this->assertNull($field->firstKey);
        $this->assertNull($field->secondKey);
        $this->assertNull($field->localKey);
        $this->assertNull($field->secondLocalKey);
        $this->assertFalse($field->searchable);
        $this->assertFalse($field->withSubtitles);
        $this->assertFalse($field->collapsable);
        $this->assertFalse($field->collapsedByDefault);
        $this->assertFalse($field->showCreateRelationButton);
        $this->assertNull($field->modalSize);
        $this->assertEquals(15, $field->perPage);
    }

    public function test_has_many_through_field_resource_configuration(): void
    {
        $field = HasManyThrough::make('Posts')->resource('App\\Models\\PostResource');

        $this->assertEquals('App\\Models\\PostResource', $field->resourceClass);
    }

    public function test_has_many_through_field_relationship_configuration(): void
    {
        $field = HasManyThrough::make('Posts')->relationship('userPosts');

        $this->assertEquals('userPosts', $field->relationshipName);
    }

    public function test_has_many_through_field_through_configuration(): void
    {
        $field = HasManyThrough::make('Posts')->through('App\\Models\\User');

        $this->assertEquals('App\\Models\\User', $field->through);
    }

    public function test_has_many_through_field_first_key_configuration(): void
    {
        $field = HasManyThrough::make('Posts')->firstKey('country_id');

        $this->assertEquals('country_id', $field->firstKey);
    }

    public function test_has_many_through_field_second_key_configuration(): void
    {
        $field = HasManyThrough::make('Posts')->secondKey('user_id');

        $this->assertEquals('user_id', $field->secondKey);
    }

    public function test_has_many_through_field_local_key_configuration(): void
    {
        $field = HasManyThrough::make('Posts')->localKey('id');

        $this->assertEquals('id', $field->localKey);
    }

    public function test_has_many_through_field_second_local_key_configuration(): void
    {
        $field = HasManyThrough::make('Posts')->secondLocalKey('id');

        $this->assertEquals('id', $field->secondLocalKey);
    }

    public function test_has_many_through_field_searchable_configuration(): void
    {
        $field = HasManyThrough::make('Posts')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_has_many_through_field_searchable_with_callback(): void
    {
        $field = HasManyThrough::make('Posts')->searchable(function () {
            return true;
        });

        $this->assertTrue($field->searchable);
    }

    public function test_has_many_through_field_searchable_false(): void
    {
        $field = HasManyThrough::make('Posts')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_has_many_through_field_with_subtitles(): void
    {
        $field = HasManyThrough::make('Posts')->withSubtitles();

        $this->assertTrue($field->withSubtitles);
    }

    public function test_has_many_through_field_collapsable(): void
    {
        $field = HasManyThrough::make('Posts')->collapsable();

        $this->assertTrue($field->collapsable);
    }

    public function test_has_many_through_field_collapsed_by_default(): void
    {
        $field = HasManyThrough::make('Posts')->collapsedByDefault();

        $this->assertTrue($field->collapsedByDefault);
        $this->assertTrue($field->collapsable); // Should also be collapsable
    }

    public function test_has_many_through_field_show_create_relation_button(): void
    {
        $field = HasManyThrough::make('Posts')->showCreateRelationButton();

        $this->assertTrue($field->showCreateRelationButton);
    }

    public function test_has_many_through_field_show_create_relation_button_with_callback(): void
    {
        $field = HasManyThrough::make('Posts')->showCreateRelationButton(function () {
            return true;
        });

        $this->assertTrue($field->showCreateRelationButton);
    }

    public function test_has_many_through_field_hide_create_relation_button(): void
    {
        $field = HasManyThrough::make('Posts')
            ->showCreateRelationButton()
            ->hideCreateRelationButton();

        $this->assertFalse($field->showCreateRelationButton);
    }

    public function test_has_many_through_field_modal_size(): void
    {
        $field = HasManyThrough::make('Posts')->modalSize('large');

        $this->assertEquals('large', $field->modalSize);
    }

    public function test_has_many_through_field_relatable_query_using(): void
    {
        $queryCallback = function ($request, $query) {
            return $query->where('published', true);
        };

        $field = HasManyThrough::make('Posts')->relatableQueryUsing($queryCallback);

        $this->assertEquals($queryCallback, $field->relatableQueryCallback);
    }

    public function test_has_many_through_field_is_only_shown_on_detail_by_default(): void
    {
        $field = HasManyThrough::make('Posts');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    public function test_has_many_through_field_can_be_configured_for_different_views(): void
    {
        $field = HasManyThrough::make('Posts')
            ->showOnIndex()
            ->showOnCreating()
            ->showOnUpdating();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    public function test_has_many_through_field_fill_with_custom_callback(): void
    {
        $request = new Request(['posts' => 'test']);
        $model = new \stdClass;
        $callbackCalled = false;

        $field = HasManyThrough::make('Posts');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('posts', $attribute);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
    }

    public function test_has_many_through_field_meta_includes_all_properties(): void
    {
        $field = HasManyThrough::make('Posts')
            ->resource('App\\Models\\PostResource')
            ->relationship('userPosts')
            ->through('App\\Models\\User')
            ->firstKey('country_id')
            ->secondKey('user_id')
            ->localKey('id')
            ->secondLocalKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->collapsedByDefault()
            ->showCreateRelationButton()
            ->modalSize('large');

        $meta = $field->meta();

        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('through', $meta);
        $this->assertArrayHasKey('firstKey', $meta);
        $this->assertArrayHasKey('secondKey', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('secondLocalKey', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('withSubtitles', $meta);
        $this->assertArrayHasKey('collapsable', $meta);
        $this->assertArrayHasKey('collapsedByDefault', $meta);
        $this->assertArrayHasKey('showCreateRelationButton', $meta);
        $this->assertArrayHasKey('modalSize', $meta);
        $this->assertArrayHasKey('perPage', $meta);

        $this->assertEquals('App\\Models\\PostResource', $meta['resourceClass']);
        $this->assertEquals('userPosts', $meta['relationshipName']);
        $this->assertEquals('App\\Models\\User', $meta['through']);
        $this->assertEquals('country_id', $meta['firstKey']);
        $this->assertEquals('user_id', $meta['secondKey']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertEquals('id', $meta['secondLocalKey']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['collapsable']);
        $this->assertTrue($meta['collapsedByDefault']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
        $this->assertEquals(15, $meta['perPage']);
    }

    public function test_has_many_through_field_json_serialization(): void
    {
        $field = HasManyThrough::make('User Posts')
            ->resource('App\\Resources\\PostResource')
            ->through('App\\Models\\User')
            ->searchable()
            ->collapsable()
            ->showCreateRelationButton()
            ->help('Manage user posts through users');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Posts', $json['name']);
        $this->assertEquals('user_posts', $json['attribute']);
        $this->assertEquals('HasManyThroughField', $json['component']);
        $this->assertEquals('App\\Resources\\PostResource', $json['resourceClass']);
        $this->assertEquals('App\\Models\\User', $json['through']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['collapsable']);
        $this->assertTrue($json['showCreateRelationButton']);
        $this->assertEquals('Manage user posts through users', $json['helpText']);
    }

    public function test_has_many_through_field_complex_configuration(): void
    {
        $field = HasManyThrough::make('Country Posts')
            ->resource('App\\Resources\\PostResource')
            ->relationship('countryPosts')
            ->through('App\\Models\\User')
            ->firstKey('country_id')
            ->secondKey('user_id')
            ->localKey('id')
            ->secondLocalKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->showCreateRelationButton()
            ->modalSize('xl');

        // Test all configurations are set
        $this->assertEquals('App\\Resources\\PostResource', $field->resourceClass);
        $this->assertEquals('countryPosts', $field->relationshipName);
        $this->assertEquals('App\\Models\\User', $field->through);
        $this->assertEquals('country_id', $field->firstKey);
        $this->assertEquals('user_id', $field->secondKey);
        $this->assertEquals('id', $field->localKey);
        $this->assertEquals('id', $field->secondLocalKey);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->withSubtitles);
        $this->assertTrue($field->collapsable);
        $this->assertTrue($field->showCreateRelationButton);
        $this->assertEquals('xl', $field->modalSize);
    }

    public function test_has_many_through_field_get_related_models_method_exists(): void
    {
        $field = HasManyThrough::make('Posts');

        // Test that the getRelatedModels method exists
        $this->assertTrue(method_exists($field, 'getRelatedModels'));
    }

    public function test_has_many_through_field_guesses_resource_class_correctly(): void
    {
        $field = HasManyThrough::make('User Posts', 'user_posts');

        $this->assertEquals('App\\AdminPanel\\Resources\\UserPosts', $field->resourceClass);
    }

    public function test_has_many_through_field_with_resolve_callback(): void
    {
        $callbackCalled = false;
        $resolveCallback = function ($value, $resource, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;

            return $value;
        };

        $field = HasManyThrough::make('Posts', 'posts', $resolveCallback);

        $this->assertEquals($resolveCallback, $field->resolveCallback);
    }

    public function test_has_many_through_field_nova_style_make_method(): void
    {
        // Test Nova-style syntax with resource class as third parameter
        $field = HasManyThrough::make('Posts', 'posts', 'App\\Nova\\PostResource');

        $this->assertEquals('Posts', $field->name);
        $this->assertEquals('posts', $field->attribute);
        $this->assertEquals('App\\Nova\\PostResource', $field->resourceClass);
    }

    public function test_has_many_through_field_make_method_with_callback(): void
    {
        // Test with callback as third parameter
        $callback = function ($value) {
            return $value;
        };

        $field = HasManyThrough::make('Posts', 'posts', $callback);

        $this->assertEquals('Posts', $field->name);
        $this->assertEquals('posts', $field->attribute);
        $this->assertEquals($callback, $field->resolveCallback);
    }

    public function test_has_many_through_field_supports_field_chaining(): void
    {
        $field = HasManyThrough::make('Posts')
            ->resource('App\\Nova\\PostResource')
            ->relationship('countryPosts')
            ->through('App\\Models\\User')
            ->firstKey('country_id')
            ->secondKey('user_id')
            ->localKey('id')
            ->secondLocalKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->collapsedByDefault()
            ->showCreateRelationButton()
            ->modalSize('large')
            ->help('Country posts through users')
            ->showOnIndex();

        $this->assertEquals('App\\Nova\\PostResource', $field->resourceClass);
        $this->assertEquals('countryPosts', $field->relationshipName);
        $this->assertEquals('App\\Models\\User', $field->through);
        $this->assertEquals('country_id', $field->firstKey);
        $this->assertEquals('user_id', $field->secondKey);
        $this->assertEquals('id', $field->localKey);
        $this->assertEquals('id', $field->secondLocalKey);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->withSubtitles);
        $this->assertTrue($field->collapsable);
        $this->assertTrue($field->collapsedByDefault);
        $this->assertTrue($field->showCreateRelationButton);
        $this->assertEquals('large', $field->modalSize);
        $this->assertEquals('Country posts through users', $field->helpText);
        $this->assertTrue($field->showOnIndex);
    }
}
