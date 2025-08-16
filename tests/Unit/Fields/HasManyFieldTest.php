<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\HasMany;
use JTD\AdminPanel\Tests\TestCase;

/**
 * HasMany Field Unit Tests.
 *
 * Tests for HasMany field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class HasManyFieldTest extends TestCase
{
    public function test_has_many_field_creation(): void
    {
        $field = HasMany::make('Comments');

        $this->assertEquals('Comments', $field->name);
        $this->assertEquals('comments', $field->attribute);
        $this->assertEquals('HasManyField', $field->component);
    }

    public function test_has_many_field_with_custom_attribute(): void
    {
        $field = HasMany::make('Blog Posts', 'blog_posts');

        $this->assertEquals('Blog Posts', $field->name);
        $this->assertEquals('blog_posts', $field->attribute);
    }

    public function test_has_many_field_default_properties(): void
    {
        $field = HasMany::make('Comments');

        $this->assertEquals('comments', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Comments', $field->resourceClass);
        $this->assertNull($field->foreignKey);
        $this->assertNull($field->localKey);
        $this->assertFalse($field->searchable); // Default is false in Nova
        $this->assertFalse($field->withSubtitles);
        $this->assertFalse($field->collapsable);
        $this->assertFalse($field->collapsedByDefault);
        $this->assertFalse($field->showCreateRelationButton); // Default is false
        $this->assertNull($field->modalSize);
        $this->assertNull($field->relatableQueryCallback);
    }

    public function test_has_many_field_only_on_detail_by_default(): void
    {
        $field = HasMany::make('Comments');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    public function test_has_many_field_resource_configuration(): void
    {
        $field = HasMany::make('Comments')->resource('App\\Models\\CommentResource');

        $this->assertEquals('App\\Models\\CommentResource', $field->resourceClass);
    }

    public function test_has_many_field_relationship_configuration(): void
    {
        $field = HasMany::make('Comments')->relationship('user_comments');

        $this->assertEquals('user_comments', $field->relationshipName);
    }

    public function test_has_many_field_foreign_key_configuration(): void
    {
        $field = HasMany::make('Comments')->foreignKey('user_id');

        $this->assertEquals('user_id', $field->foreignKey);
    }

    public function test_has_many_field_local_key_configuration(): void
    {
        $field = HasMany::make('Comments')->localKey('uuid');

        $this->assertEquals('uuid', $field->localKey);
    }

    public function test_has_many_field_searchable_configuration(): void
    {
        $field = HasMany::make('Comments')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_has_many_field_searchable_default(): void
    {
        $field = HasMany::make('Comments');

        $this->assertFalse($field->searchable); // Default is false in Nova v5
    }

    public function test_has_many_field_show_create_relation_button_configuration(): void
    {
        $field = HasMany::make('Comments')->showCreateRelationButton();

        $this->assertTrue($field->showCreateRelationButton);
    }

    public function test_has_many_field_show_create_relation_button_false(): void
    {
        $field = HasMany::make('Comments')->showCreateRelationButton(false);

        $this->assertFalse($field->showCreateRelationButton);
    }

    public function test_has_many_field_hide_create_relation_button(): void
    {
        $field = HasMany::make('Comments')->hideCreateRelationButton();

        $this->assertFalse($field->showCreateRelationButton);
    }

    public function test_has_many_field_collapsable_configuration(): void
    {
        $field = HasMany::make('Comments')->collapsable();

        $this->assertTrue($field->collapsable);
    }

    public function test_has_many_field_collapsed_by_default_configuration(): void
    {
        $field = HasMany::make('Comments')->collapsedByDefault();

        $this->assertTrue($field->collapsedByDefault);
        $this->assertTrue($field->collapsable); // Should also set collapsable to true
    }

    public function test_has_many_field_with_subtitles_configuration(): void
    {
        $field = HasMany::make('Comments')->withSubtitles();

        $this->assertTrue($field->withSubtitles);
    }

    public function test_has_many_field_modal_size_configuration(): void
    {
        $field = HasMany::make('Comments')->modalSize('large');

        $this->assertEquals('large', $field->modalSize);
    }

    public function test_has_many_field_relatable_query_using_configuration(): void
    {
        $callback = function ($request, $query) {
            return $query->where('published', true);
        };

        $field = HasMany::make('Comments')->relatableQueryUsing($callback);

        $this->assertEquals($callback, $field->relatableQueryCallback);
    }

    public function test_has_many_field_resolve_with_related_models(): void
    {
        // Mock collection with count method
        $relatedModels = new class
        {
            public function count()
            {
                return 5;
            }
        };

        // Mock resource with getKey method
        $resource = new class($relatedModels)
        {
            public function __construct(public $comments) {}

            public function getKey()
            {
                return 123;
            }
        };

        $field = HasMany::make('Comments');

        $field->resolve($resource);

        $this->assertIsArray($field->value);
        $this->assertEquals(5, $field->value['count']);
        $this->assertEquals(123, $field->value['resource_id']);
    }

    public function test_has_many_field_resolve_with_null_related_models(): void
    {
        // Mock resource with null relationship
        $resource = new class
        {
            public $comments = null;

            public function getKey()
            {
                return 123;
            }
        };

        $field = HasMany::make('Comments');

        $field->resolve($resource);

        $this->assertIsArray($field->value);
        $this->assertEquals(0, $field->value['count']);
        $this->assertEquals(123, $field->value['resource_id']);
    }

    public function test_has_many_field_guess_resource_class(): void
    {
        $field = HasMany::make('Blog Posts', 'blog_posts');

        $this->assertEquals('App\\AdminPanel\\Resources\\BlogPosts', $field->resourceClass);
    }

    public function test_has_many_field_meta_includes_all_properties(): void
    {
        $field = HasMany::make('Comments')
            ->resource('App\\Models\\CommentResource')
            ->relationship('user_comments')
            ->foreignKey('user_id')
            ->localKey('uuid')
            ->searchable(false)
            ->withSubtitles()
            ->collapsable()
            ->collapsedByDefault()
            ->showCreateRelationButton()
            ->modalSize('large');

        $meta = $field->meta();

        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('foreignKey', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('withSubtitles', $meta);
        $this->assertArrayHasKey('collapsable', $meta);
        $this->assertArrayHasKey('collapsedByDefault', $meta);
        $this->assertArrayHasKey('showCreateRelationButton', $meta);
        $this->assertArrayHasKey('modalSize', $meta);

        $this->assertEquals('App\\Models\\CommentResource', $meta['resourceClass']);
        $this->assertEquals('user_comments', $meta['relationshipName']);
        $this->assertEquals('user_id', $meta['foreignKey']);
        $this->assertEquals('uuid', $meta['localKey']);
        $this->assertFalse($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['collapsable']);
        $this->assertTrue($meta['collapsedByDefault']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
    }

    public function test_has_many_field_json_serialization(): void
    {
        $field = HasMany::make('User Posts')
            ->resource('App\\Resources\\PostResource')
            ->searchable()
            ->showCreateRelationButton()
            ->help('Manage user posts');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Posts', $json['name']);
        $this->assertEquals('user_posts', $json['attribute']);
        $this->assertEquals('HasManyField', $json['component']);
        $this->assertEquals('App\\Resources\\PostResource', $json['resourceClass']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['showCreateRelationButton']);
        $this->assertEquals('Manage user posts', $json['helpText']);
    }

    public function test_has_many_field_inheritance_from_field(): void
    {
        $field = HasMany::make('Comments');

        // Test that HasMany field inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'placeholder'));
    }

    public function test_has_many_field_with_validation_rules(): void
    {
        $field = HasMany::make('Comments')
            ->rules('required', 'array', 'min:1');

        $this->assertEquals(['required', 'array', 'min:1'], $field->rules);
    }

    public function test_has_many_field_fill_method(): void
    {
        $field = HasMany::make('Comments');
        $model = new \stdClass;
        $request = new \Illuminate\Http\Request;

        // Test that fill method exists and can be called
        $this->assertTrue(method_exists($field, 'fill'));

        // Test fill with empty request doesn't break
        $field->fill($request, $model);

        // HasMany fields typically don't fill directly, so no changes expected
        $this->assertTrue(true); // Just verify no exceptions thrown
    }

    public function test_has_many_field_fill_with_callback(): void
    {
        $callbackCalled = false;
        $fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
        };

        $field = HasMany::make('Comments');
        $field->fillCallback = $fillCallback;

        $model = new \stdClass;
        $request = new \Illuminate\Http\Request;

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
    }

    public function test_has_many_field_get_related_models_method_exists(): void
    {
        $field = HasMany::make('Comments');

        // Test that getRelatedModels method exists
        $this->assertTrue(method_exists($field, 'getRelatedModels'));
    }

    public function test_has_many_field_get_related_models_method_availability(): void
    {
        $field = HasMany::make('Comments');

        // Test that the getRelatedModels method exists
        $this->assertTrue(method_exists($field, 'getRelatedModels'));
    }

    public function test_has_many_field_get_related_models_with_relatable_query_callback(): void
    {
        $queryCallback = function ($request, $query) {
            return $query->where('published', true);
        };

        $field = HasMany::make('Comments')->relatableQueryUsing($queryCallback);

        // Verify the query callback is set
        $this->assertEquals($queryCallback, $field->relatableQueryCallback);

        // Test that getRelatedModels method exists and can handle query callbacks
        $this->assertTrue(method_exists($field, 'getRelatedModels'));
    }

    public function test_has_many_field_searchable_true_configuration(): void
    {
        $field = HasMany::make('Comments')->searchable();

        // Test that the getRelatedModels method exists
        $this->assertTrue(method_exists($field, 'getRelatedModels'));

        // Test that searchable property is set correctly
        $this->assertTrue($field->searchable);
    }

    public function test_has_many_field_resource_class_guessing(): void
    {
        // Test different attribute formats and their guessed resource classes
        $field1 = HasMany::make('User Comments', 'user_comments');
        $this->assertEquals('App\\AdminPanel\\Resources\\UserComments', $field1->resourceClass);

        $field2 = HasMany::make('Blog Posts', 'blog_posts');
        $this->assertEquals('App\\AdminPanel\\Resources\\BlogPosts', $field2->resourceClass);

        $field3 = HasMany::make('Simple', 'simple');
        $this->assertEquals('App\\AdminPanel\\Resources\\Simple', $field3->resourceClass);
    }

    public function test_has_many_field_complex_configuration(): void
    {
        $queryCallback = function ($request, $query) {
            return $query->where('status', 'published')->orderBy('created_at', 'desc');
        };

        $field = HasMany::make('Published Posts')
            ->resource('App\\Resources\\PostResource')
            ->relationship('posts')
            ->foreignKey('author_id')
            ->localKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->collapsedByDefault()
            ->showCreateRelationButton()
            ->modalSize('large')
            ->relatableQueryUsing($queryCallback);

        // Test all configurations are set
        $this->assertEquals('App\\Resources\\PostResource', $field->resourceClass);
        $this->assertEquals('posts', $field->relationshipName);
        $this->assertEquals('author_id', $field->foreignKey);
        $this->assertEquals('id', $field->localKey);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->withSubtitles);
        $this->assertTrue($field->collapsable);
        $this->assertTrue($field->collapsedByDefault);
        $this->assertTrue($field->showCreateRelationButton);
        $this->assertEquals('large', $field->modalSize);
        $this->assertEquals($queryCallback, $field->relatableQueryCallback);
    }
}
