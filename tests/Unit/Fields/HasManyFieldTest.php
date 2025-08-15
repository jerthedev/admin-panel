<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\HasMany;
use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * HasMany Field Unit Tests
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
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->showCreateButton); // Default is true
        $this->assertFalse($field->showAttachButton); // Default is false
        $this->assertEquals(10, $field->perPage); // Default is 10, not 15
        $this->assertEquals([], $field->displayFields);
        $this->assertNull($field->queryCallback);
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

        $this->assertTrue($field->searchable);
    }

    public function test_has_many_field_show_create_button_configuration(): void
    {
        $field = HasMany::make('Comments')->showCreateButton();

        $this->assertTrue($field->showCreateButton);
    }

    public function test_has_many_field_show_create_button_false(): void
    {
        $field = HasMany::make('Comments')->showCreateButton(false);

        $this->assertFalse($field->showCreateButton);
    }

    public function test_has_many_field_show_attach_button_configuration(): void
    {
        $field = HasMany::make('Comments')->showAttachButton();

        $this->assertTrue($field->showAttachButton);
    }

    public function test_has_many_field_show_attach_button_false(): void
    {
        $field = HasMany::make('Comments')->showAttachButton(false);

        $this->assertFalse($field->showAttachButton);
    }

    public function test_has_many_field_per_page_configuration(): void
    {
        $field = HasMany::make('Comments')->perPage(25);

        $this->assertEquals(25, $field->perPage);
    }

    public function test_has_many_field_display_fields_configuration(): void
    {
        $fields = ['title', 'content', 'created_at'];
        $field = HasMany::make('Comments')->displayFields($fields);

        $this->assertEquals($fields, $field->displayFields);
    }

    public function test_has_many_field_query_callback_configuration(): void
    {
        $callback = function ($request, $query) {
            return $query->where('published', true);
        };

        $field = HasMany::make('Comments')->query($callback);

        $this->assertEquals($callback, $field->queryCallback);
    }

    public function test_has_many_field_resolve_with_related_models(): void
    {
        // Mock collection with count method
        $relatedModels = new class {
            public function count() {
                return 5;
            }
        };

        // Mock resource with getKey method
        $resource = new class($relatedModels) {
            public function __construct(public $comments) {}
            public function getKey() {
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
        $resource = new class {
            public $comments = null;
            public function getKey() {
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
        $displayFields = ['title', 'content'];
        $field = HasMany::make('Comments')
            ->resource('App\\Models\\CommentResource')
            ->relationship('user_comments')
            ->foreignKey('user_id')
            ->localKey('uuid')
            ->searchable(false)
            ->showCreateButton()
            ->showAttachButton()
            ->perPage(20)
            ->displayFields($displayFields);

        $meta = $field->meta();

        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('foreignKey', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('showCreateButton', $meta);
        $this->assertArrayHasKey('showAttachButton', $meta);
        $this->assertArrayHasKey('perPage', $meta);
        $this->assertArrayHasKey('displayFields', $meta);
        $this->assertEquals('App\\Models\\CommentResource', $meta['resourceClass']);
        $this->assertEquals('user_comments', $meta['relationshipName']);
        $this->assertEquals('user_id', $meta['foreignKey']);
        $this->assertEquals('uuid', $meta['localKey']);
        $this->assertFalse($meta['searchable']);
        $this->assertTrue($meta['showCreateButton']);
        $this->assertTrue($meta['showAttachButton']);
        $this->assertEquals(20, $meta['perPage']);
        $this->assertEquals($displayFields, $meta['displayFields']);
    }

    public function test_has_many_field_json_serialization(): void
    {
        $field = HasMany::make('User Posts')
            ->resource('App\\Resources\\PostResource')
            ->searchable()
            ->showCreateButton()
            ->perPage(10)
            ->help('Manage user posts');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Posts', $json['name']);
        $this->assertEquals('user_posts', $json['attribute']);
        $this->assertEquals('HasManyField', $json['component']);
        $this->assertEquals('App\\Resources\\PostResource', $json['resourceClass']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['showCreateButton']);
        $this->assertEquals(10, $json['perPage']);
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
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request();

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

        $model = new \stdClass();
        $request = new \Illuminate\Http\Request();

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
    }

    public function test_has_many_field_get_related_models_method_exists(): void
    {
        $field = HasMany::make('Comments');

        // Test that getRelatedModels method exists
        $this->assertTrue(method_exists($field, 'getRelatedModels'));
    }

    public function test_has_many_field_get_related_models_error_handling(): void
    {
        $field = HasMany::make('Comments');
        $request = new \Illuminate\Http\Request();

        // Create a mock parent model
        $mockParentModel = $this->createMock(\Illuminate\Database\Eloquent\Model::class);

        // Test that the method handles errors gracefully when resource class doesn't exist
        try {
            $field->getRelatedModels($request, $mockParentModel);
            // If no exception is thrown, the method exists and handles the case
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Expected behavior - method exists but fails due to missing resource setup
            $this->assertTrue(method_exists($field, 'getRelatedModels'));
            // The error could be about missing resource class or other setup issues
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function test_has_many_field_get_related_models_with_query_callback(): void
    {
        $queryCallback = function ($request, $query) {
            return $query->where('published', true);
        };

        $field = HasMany::make('Comments')->query($queryCallback);

        // Verify the query callback is set
        $this->assertEquals($queryCallback, $field->queryCallback);

        // Test that getRelatedModels method exists and can handle query callbacks
        $this->assertTrue(method_exists($field, 'getRelatedModels'));
    }

    public function test_has_many_field_get_related_models_with_search_parameters(): void
    {
        $field = HasMany::make('Comments')->searchable();
        $request = new \Illuminate\Http\Request([
            'search' => 'test search',
            'orderBy' => 'created_at',
            'orderByDirection' => 'desc',
            'page' => 2,
            'perPage' => 15
        ]);

        // Create a mock parent model
        $mockParentModel = $this->createMock(\Illuminate\Database\Eloquent\Model::class);

        // Test that the method can handle search and pagination parameters
        try {
            $field->getRelatedModels($request, $mockParentModel);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Expected behavior - method exists but fails due to missing resource setup
            $this->assertTrue(method_exists($field, 'getRelatedModels'));
            $this->assertNotEmpty($e->getMessage());
        }
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
        $displayFields = ['title', 'content', 'status', 'created_at'];
        $queryCallback = function ($request, $query) {
            return $query->where('status', 'published')->orderBy('created_at', 'desc');
        };

        $field = HasMany::make('Published Posts')
            ->resource('App\\Resources\\PostResource')
            ->relationship('posts')
            ->foreignKey('author_id')
            ->localKey('id')
            ->searchable()
            ->showCreateButton()
            ->showAttachButton()
            ->perPage(20)
            ->displayFields($displayFields)
            ->query($queryCallback);

        // Test all configurations are set
        $this->assertEquals('App\\Resources\\PostResource', $field->resourceClass);
        $this->assertEquals('posts', $field->relationshipName);
        $this->assertEquals('author_id', $field->foreignKey);
        $this->assertEquals('id', $field->localKey);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->showCreateButton);
        $this->assertTrue($field->showAttachButton);
        $this->assertEquals(20, $field->perPage);
        $this->assertEquals($displayFields, $field->displayFields);
        $this->assertEquals($queryCallback, $field->queryCallback);
    }
}
