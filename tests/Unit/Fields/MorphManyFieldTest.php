<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\MorphMany;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MorphMany Field Unit Test.
 *
 * Tests the MorphMany field class functionality including field creation,
 * configuration, resolution, and Nova v5 compatibility.
 */
class MorphManyFieldTest extends TestCase
{
    public function test_morph_many_field_creation(): void
    {
        $field = MorphMany::make('Comments');

        $this->assertEquals('Comments', $field->name);
        $this->assertEquals('comments', $field->attribute);
        $this->assertEquals('MorphManyField', $field->component);
        $this->assertEquals('comments', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Comments', $field->resourceClass);
    }

    public function test_morph_many_field_with_custom_attribute(): void
    {
        $field = MorphMany::make('User Comments', 'user_comments');

        $this->assertEquals('User Comments', $field->name);
        $this->assertEquals('user_comments', $field->attribute);
        $this->assertEquals('user_comments', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\UserComments', $field->resourceClass);
    }

    public function test_morph_many_field_default_properties(): void
    {
        $field = MorphMany::make('Comments');

        $this->assertEquals('comments', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Comments', $field->resourceClass);
        $this->assertNull($field->morphType);
        $this->assertNull($field->morphId);
        $this->assertNull($field->localKey);
        $this->assertFalse($field->searchable);
        $this->assertFalse($field->withSubtitles);
        $this->assertFalse($field->collapsable);
        $this->assertFalse($field->collapsedByDefault);
        $this->assertFalse($field->showCreateRelationButton);
        $this->assertNull($field->modalSize);
        $this->assertEquals(15, $field->perPage);
        $this->assertNull($field->relatableQueryCallback);
    }

    public function test_morph_many_field_resource_configuration(): void
    {
        $field = MorphMany::make('Comments')->resource('App\\Models\\CommentResource');

        $this->assertEquals('App\\Models\\CommentResource', $field->resourceClass);
    }

    public function test_morph_many_field_relationship_configuration(): void
    {
        $field = MorphMany::make('Comments')->relationship('postComments');

        $this->assertEquals('postComments', $field->relationshipName);
    }

    public function test_morph_many_field_morph_type_configuration(): void
    {
        $field = MorphMany::make('Comments')->morphType('commentable_type');

        $this->assertEquals('commentable_type', $field->morphType);
    }

    public function test_morph_many_field_morph_id_configuration(): void
    {
        $field = MorphMany::make('Comments')->morphId('commentable_id');

        $this->assertEquals('commentable_id', $field->morphId);
    }

    public function test_morph_many_field_local_key_configuration(): void
    {
        $field = MorphMany::make('Comments')->localKey('id');

        $this->assertEquals('id', $field->localKey);
    }

    public function test_morph_many_field_searchable_configuration(): void
    {
        $field = MorphMany::make('Comments')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_morph_many_field_searchable_with_callback(): void
    {
        $field = MorphMany::make('Comments')->searchable(function () {
            return true;
        });

        $this->assertTrue($field->searchable);
    }

    public function test_morph_many_field_searchable_false(): void
    {
        $field = MorphMany::make('Comments')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_morph_many_field_with_subtitles(): void
    {
        $field = MorphMany::make('Comments')->withSubtitles();

        $this->assertTrue($field->withSubtitles);
    }

    public function test_morph_many_field_collapsable(): void
    {
        $field = MorphMany::make('Comments')->collapsable();

        $this->assertTrue($field->collapsable);
    }

    public function test_morph_many_field_collapsed_by_default(): void
    {
        $field = MorphMany::make('Comments')->collapsedByDefault();

        $this->assertTrue($field->collapsedByDefault);
        $this->assertTrue($field->collapsable); // Should also be collapsable
    }

    public function test_morph_many_field_show_create_relation_button(): void
    {
        $field = MorphMany::make('Comments')->showCreateRelationButton();

        $this->assertTrue($field->showCreateRelationButton);
    }

    public function test_morph_many_field_show_create_relation_button_with_callback(): void
    {
        $field = MorphMany::make('Comments')->showCreateRelationButton(function () {
            return true;
        });

        $this->assertTrue($field->showCreateRelationButton);
    }

    public function test_morph_many_field_hide_create_relation_button(): void
    {
        $field = MorphMany::make('Comments')
            ->showCreateRelationButton()
            ->hideCreateRelationButton();

        $this->assertFalse($field->showCreateRelationButton);
    }

    public function test_morph_many_field_modal_size(): void
    {
        $field = MorphMany::make('Comments')->modalSize('large');

        $this->assertEquals('large', $field->modalSize);
    }

    public function test_morph_many_field_relatable_query_using(): void
    {
        $queryCallback = function ($request, $query) {
            return $query->where('active', true);
        };

        $field = MorphMany::make('Comments')->relatableQueryUsing($queryCallback);

        $this->assertEquals($queryCallback, $field->relatableQueryCallback);
    }

    public function test_morph_many_field_is_only_shown_on_detail_by_default(): void
    {
        $field = MorphMany::make('Comments');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    public function test_morph_many_field_can_be_configured_for_different_views(): void
    {
        $field = MorphMany::make('Comments')
            ->showOnIndex()
            ->showOnCreating()
            ->showOnUpdating();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    public function test_morph_many_field_fill_with_custom_callback(): void
    {
        $request = new Request(['comments' => 'test']);
        $model = new \stdClass;
        $callbackCalled = false;

        $field = MorphMany::make('Comments');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('comments', $attribute);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
    }

    public function test_morph_many_field_meta_includes_all_properties(): void
    {
        $field = MorphMany::make('Comments')
            ->resource('App\\Models\\CommentResource')
            ->relationship('postComments')
            ->morphType('commentable_type')
            ->morphId('commentable_id')
            ->localKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->collapsedByDefault()
            ->showCreateRelationButton()
            ->modalSize('large');

        $meta = $field->meta();

        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('morphType', $meta);
        $this->assertArrayHasKey('morphId', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('withSubtitles', $meta);
        $this->assertArrayHasKey('collapsable', $meta);
        $this->assertArrayHasKey('collapsedByDefault', $meta);
        $this->assertArrayHasKey('showCreateRelationButton', $meta);
        $this->assertArrayHasKey('modalSize', $meta);
        $this->assertArrayHasKey('perPage', $meta);

        $this->assertEquals('App\\Models\\CommentResource', $meta['resourceClass']);
        $this->assertEquals('postComments', $meta['relationshipName']);
        $this->assertEquals('commentable_type', $meta['morphType']);
        $this->assertEquals('commentable_id', $meta['morphId']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['collapsable']);
        $this->assertTrue($meta['collapsedByDefault']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
        $this->assertEquals(15, $meta['perPage']);
    }

    public function test_morph_many_field_json_serialization(): void
    {
        $field = MorphMany::make('Post Comments')
            ->resource('App\\Resources\\CommentResource')
            ->morphType('commentable_type')
            ->morphId('commentable_id')
            ->searchable()
            ->collapsable()
            ->showCreateRelationButton()
            ->help('Manage post comments');

        $json = $field->jsonSerialize();

        $this->assertEquals('Post Comments', $json['name']);
        $this->assertEquals('post_comments', $json['attribute']);
        $this->assertEquals('MorphManyField', $json['component']);
        $this->assertEquals('App\\Resources\\CommentResource', $json['resourceClass']);
        $this->assertEquals('commentable_type', $json['morphType']);
        $this->assertEquals('commentable_id', $json['morphId']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['collapsable']);
        $this->assertTrue($json['showCreateRelationButton']);
        $this->assertEquals('Manage post comments', $json['helpText']);
    }

    public function test_morph_many_field_complex_configuration(): void
    {
        $field = MorphMany::make('User Comments')
            ->resource('App\\Resources\\CommentResource')
            ->relationship('userComments')
            ->morphType('commentable_type')
            ->morphId('commentable_id')
            ->localKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->showCreateRelationButton()
            ->modalSize('xl');

        // Test all configurations are set
        $this->assertEquals('App\\Resources\\CommentResource', $field->resourceClass);
        $this->assertEquals('userComments', $field->relationshipName);
        $this->assertEquals('commentable_type', $field->morphType);
        $this->assertEquals('commentable_id', $field->morphId);
        $this->assertEquals('id', $field->localKey);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->withSubtitles);
        $this->assertTrue($field->collapsable);
        $this->assertTrue($field->showCreateRelationButton);
        $this->assertEquals('xl', $field->modalSize);
    }

    public function test_morph_many_field_guesses_resource_class_correctly(): void
    {
        $field = MorphMany::make('User Comments', 'user_comments');

        $this->assertEquals('App\\AdminPanel\\Resources\\UserComments', $field->resourceClass);
    }

    public function test_morph_many_field_with_resolve_callback(): void
    {
        $callbackCalled = false;
        $resolveCallback = function ($value, $resource, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;

            return $value;
        };

        $field = MorphMany::make('Comments', 'comments', $resolveCallback);

        $this->assertEquals($resolveCallback, $field->resolveCallback);
    }

    public function test_morph_many_field_supports_field_chaining(): void
    {
        $field = MorphMany::make('Comments')
            ->resource('App\\Nova\\CommentResource')
            ->relationship('postComments')
            ->morphType('commentable_type')
            ->morphId('commentable_id')
            ->localKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->collapsedByDefault()
            ->showCreateRelationButton()
            ->modalSize('large')
            ->help('Post comments management')
            ->showOnIndex();

        $this->assertEquals('App\\Nova\\CommentResource', $field->resourceClass);
        $this->assertEquals('postComments', $field->relationshipName);
        $this->assertEquals('commentable_type', $field->morphType);
        $this->assertEquals('commentable_id', $field->morphId);
        $this->assertEquals('id', $field->localKey);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->withSubtitles);
        $this->assertTrue($field->collapsable);
        $this->assertTrue($field->collapsedByDefault);
        $this->assertTrue($field->showCreateRelationButton);
        $this->assertEquals('large', $field->modalSize);
        $this->assertEquals('Post comments management', $field->helpText);
        $this->assertTrue($field->showOnIndex);
    }

    public function test_morph_many_field_methods_exist(): void
    {
        $field = MorphMany::make('Comments');

        // Test that the required methods exist
        $this->assertTrue(method_exists($field, 'getRelatedModels'));
    }

    public function test_morph_many_field_component_name(): void
    {
        $field = MorphMany::make('Comments');

        $this->assertEquals('MorphManyField', $field->component);
    }

    public function test_morph_many_field_attribute_guessing(): void
    {
        // Test various field names and their attribute guessing
        $testCases = [
            ['Post Comments', 'post_comments'],
            ['User Reviews', 'user_reviews'],
            ['Product Ratings', 'product_ratings'],
            ['Comments', 'comments'],
        ];

        foreach ($testCases as [$name, $expectedAttribute]) {
            $field = MorphMany::make($name);
            $this->assertEquals($expectedAttribute, $field->attribute);
            $this->assertEquals($expectedAttribute, $field->relationshipName);
        }
    }

    public function test_morph_many_field_polymorphic_properties(): void
    {
        $field = MorphMany::make('Comments')
            ->morphType('commentable_type')
            ->morphId('commentable_id');

        // Test that polymorphic properties are set correctly
        $this->assertEquals('commentable_type', $field->morphType);
        $this->assertEquals('commentable_id', $field->morphId);

        // Test that these are included in meta
        $meta = $field->meta();
        $this->assertEquals('commentable_type', $meta['morphType']);
        $this->assertEquals('commentable_id', $meta['morphId']);
    }

    public function test_morph_many_field_make_method_signature(): void
    {
        // Test basic make method
        $field1 = MorphMany::make('Comments');
        $this->assertEquals('Comments', $field1->name);
        $this->assertEquals('comments', $field1->attribute);

        // Test make method with attribute
        $field2 = MorphMany::make('Comments', 'post_comments');
        $this->assertEquals('Comments', $field2->name);
        $this->assertEquals('post_comments', $field2->attribute);

        // Test make method with callback
        $callback = function ($value) {
            return $value;
        };
        $field3 = MorphMany::make('Comments', 'comments', $callback);
        $this->assertEquals('Comments', $field3->name);
        $this->assertEquals('comments', $field3->attribute);
        $this->assertEquals($callback, $field3->resolveCallback);
    }
}
