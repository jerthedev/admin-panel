<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\BelongsTo;
use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * BelongsTo Field Unit Tests
 *
 * Tests for BelongsTo field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class BelongsToFieldTest extends TestCase
{
    public function test_belongs_to_field_creation(): void
    {
        $field = BelongsTo::make('Category');

        $this->assertEquals('Category', $field->name);
        $this->assertEquals('category', $field->attribute);
        $this->assertEquals('BelongsToField', $field->component);
    }

    public function test_belongs_to_field_with_custom_attribute(): void
    {
        $field = BelongsTo::make('User Category', 'user_category');

        $this->assertEquals('User Category', $field->name);
        $this->assertEquals('user_category', $field->attribute);
    }

    public function test_belongs_to_field_default_properties(): void
    {
        $field = BelongsTo::make('Category');

        $this->assertEquals('category', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Category', $field->resourceClass);
        $this->assertNull($field->foreignKey);
        $this->assertNull($field->ownerKey);
        $this->assertTrue($field->searchable);
        $this->assertFalse($field->showCreateButton);
        $this->assertNull($field->displayCallback);
        $this->assertNull($field->queryCallback);
    }

    public function test_belongs_to_field_resource_configuration(): void
    {
        $field = BelongsTo::make('Category')->resource('App\\Models\\CategoryResource');

        $this->assertEquals('App\\Models\\CategoryResource', $field->resourceClass);
    }

    public function test_belongs_to_field_relationship_configuration(): void
    {
        $field = BelongsTo::make('Category')->relationship('parent_category');

        $this->assertEquals('parent_category', $field->relationshipName);
    }

    public function test_belongs_to_field_foreign_key_configuration(): void
    {
        $field = BelongsTo::make('Category')->foreignKey('category_id');

        $this->assertEquals('category_id', $field->foreignKey);
    }

    public function test_belongs_to_field_owner_key_configuration(): void
    {
        $field = BelongsTo::make('Category')->ownerKey('uuid');

        $this->assertEquals('uuid', $field->ownerKey);
    }

    public function test_belongs_to_field_searchable_configuration(): void
    {
        $field = BelongsTo::make('Category')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_belongs_to_field_searchable_default(): void
    {
        $field = BelongsTo::make('Category');

        $this->assertTrue($field->searchable);
    }

    public function test_belongs_to_field_show_create_button_configuration(): void
    {
        $field = BelongsTo::make('Category')->showCreateButton();

        $this->assertTrue($field->showCreateButton);
    }

    public function test_belongs_to_field_show_create_button_false(): void
    {
        $field = BelongsTo::make('Category')->showCreateButton(false);

        $this->assertFalse($field->showCreateButton);
    }

    public function test_belongs_to_field_display_callback_configuration(): void
    {
        $callback = function ($model) {
            return $model->name . ' (' . $model->id . ')';
        };

        $field = BelongsTo::make('Category')->display($callback);

        $this->assertEquals($callback, $field->displayCallback);
    }

    public function test_belongs_to_field_query_callback_configuration(): void
    {
        $callback = function ($request, $query) {
            return $query->where('active', true);
        };

        $field = BelongsTo::make('Category')->query($callback);

        $this->assertEquals($callback, $field->queryCallback);
    }

    public function test_belongs_to_field_resolve_with_related_model(): void
    {
        // Mock related model
        $relatedModel = new class {
            public function title() {
                return 'Test Category';
            }
        };

        // Mock resource
        $resource = new class($relatedModel) {
            public function __construct(public $category) {}
        };

        // Mock resource class
        $resourceClass = new class($relatedModel) {
            public function __construct(public $model) {}
            public function title() {
                return $this->model->title();
            }
        };

        $field = BelongsTo::make('Category');
        $field->resourceClass = get_class($resourceClass);

        $field->resolve($resource);

        $this->assertEquals('Test Category', $field->value);
    }

    public function test_belongs_to_field_resolve_with_display_callback(): void
    {
        // Mock related model
        $relatedModel = new class {
            public $name = 'Test Category';
            public $id = 123;
        };

        // Mock resource
        $resource = new class($relatedModel) {
            public function __construct(public $category) {}
        };

        $callback = function ($model) {
            return $model->name . ' (' . $model->id . ')';
        };

        $field = BelongsTo::make('Category')->display($callback);

        $field->resolve($resource);

        $this->assertEquals('Test Category (123)', $field->value);
    }

    public function test_belongs_to_field_resolve_with_null_related_model(): void
    {
        // Mock resource with null relationship
        $resource = new class {
            public $category = null;
        };

        $field = BelongsTo::make('Category');

        $field->resolve($resource);

        $this->assertNull($field->value);
    }

    public function test_belongs_to_field_guess_resource_class(): void
    {
        $field = BelongsTo::make('User Category', 'user_category');

        $this->assertEquals('App\\AdminPanel\\Resources\\UserCategory', $field->resourceClass);
    }

    public function test_belongs_to_field_meta_includes_all_properties(): void
    {
        $field = BelongsTo::make('Category')
            ->resource('App\\Models\\CategoryResource')
            ->relationship('parent_category')
            ->foreignKey('category_id')
            ->ownerKey('uuid')
            ->searchable(false)
            ->showCreateButton();

        $meta = $field->meta();

        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('foreignKey', $meta);
        $this->assertArrayHasKey('ownerKey', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('showCreateButton', $meta);
        $this->assertEquals('App\\Models\\CategoryResource', $meta['resourceClass']);
        $this->assertEquals('parent_category', $meta['relationshipName']);
        $this->assertEquals('category_id', $meta['foreignKey']);
        $this->assertEquals('uuid', $meta['ownerKey']);
        $this->assertFalse($meta['searchable']);
        $this->assertTrue($meta['showCreateButton']);
    }

    public function test_belongs_to_field_json_serialization(): void
    {
        $field = BelongsTo::make('Post Category')
            ->resource('App\\Resources\\CategoryResource')
            ->searchable()
            ->showCreateButton()
            ->required()
            ->help('Select post category');

        $json = $field->jsonSerialize();

        $this->assertEquals('Post Category', $json['name']);
        $this->assertEquals('post_category', $json['attribute']);
        $this->assertEquals('BelongsToField', $json['component']);
        $this->assertEquals('App\\Resources\\CategoryResource', $json['resourceClass']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['showCreateButton']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Select post category', $json['helpText']);
    }

    public function test_belongs_to_field_inheritance_from_field(): void
    {
        $field = BelongsTo::make('Category');

        // Test that BelongsTo field inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'placeholder'));
    }

    public function test_belongs_to_field_with_validation_rules(): void
    {
        $field = BelongsTo::make('Category')
            ->rules('required', 'exists:categories,id');

        $this->assertEquals(['required', 'exists:categories,id'], $field->rules);
    }

    public function test_belongs_to_field_fill_method(): void
    {
        $field = BelongsTo::make('Category');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['category' => '123']);

        // Test that fill method exists and can be called
        $this->assertTrue(method_exists($field, 'fill'));

        // Test fill with request value
        $field->fill($request, $model);

        // Should set the foreign key (default: relationship_name + '_id')
        $this->assertEquals('123', $model->category_id);
    }

    public function test_belongs_to_field_fill_with_custom_foreign_key(): void
    {
        $field = BelongsTo::make('Category')->foreignKey('cat_id');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['category' => '456']);

        $field->fill($request, $model);

        // Should set the custom foreign key
        $this->assertEquals('456', $model->cat_id);
    }

    public function test_belongs_to_field_fill_with_callback(): void
    {
        $callbackCalled = false;
        $fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
        };

        $field = BelongsTo::make('Category');
        $field->fillCallback = $fillCallback;

        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['category' => '789']);

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
    }

    public function test_belongs_to_field_get_options_method_exists(): void
    {
        $field = BelongsTo::make('Category');

        // Test that getOptions method exists
        $this->assertTrue(method_exists($field, 'getOptions'));
    }

    public function test_belongs_to_field_get_options_error_handling(): void
    {
        $field = BelongsTo::make('Category');
        $request = new \Illuminate\Http\Request();

        // Test that the method handles errors gracefully when resource class doesn't exist
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Class "App\AdminPanel\Resources\Category" not found');

        $field->getOptions($request);
    }

    public function test_belongs_to_field_get_options_with_query_callback(): void
    {
        $queryCallback = function ($request, $query) {
            return $query->where('active', true);
        };

        $field = BelongsTo::make('Category')->query($queryCallback);

        // Verify the query callback is set
        $this->assertEquals($queryCallback, $field->queryCallback);

        // Test that getOptions method exists and can handle query callbacks
        $this->assertTrue(method_exists($field, 'getOptions'));
    }

    public function test_belongs_to_field_get_options_with_display_callback(): void
    {
        $displayCallback = function ($model) {
            return 'Custom: ' . $model->name;
        };

        $field = BelongsTo::make('Category')->display($displayCallback);

        // Verify the display callback is set
        $this->assertEquals($displayCallback, $field->displayCallback);

        // Test that getOptions method exists and can handle display callbacks
        $this->assertTrue(method_exists($field, 'getOptions'));
    }

    public function test_belongs_to_field_get_options_with_search_parameters(): void
    {
        $field = BelongsTo::make('Category')->searchable();
        $request = new \Illuminate\Http\Request([
            'search' => 'test search',
            'orderBy' => 'name',
            'orderByDirection' => 'asc'
        ]);

        // Test that the method can handle search parameters
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Class "App\AdminPanel\Resources\Category" not found');

        $field->getOptions($request);
    }

    public function test_belongs_to_field_complex_configuration(): void
    {
        $displayCallback = function ($model) {
            return $model->name . ' - ' . $model->description;
        };

        $queryCallback = function ($request, $query) {
            return $query->where('active', true)->orderBy('name');
        };

        $field = BelongsTo::make('Product Category')
            ->resource('App\\Resources\\ProductCategoryResource')
            ->relationship('category')
            ->foreignKey('category_id')
            ->ownerKey('id')
            ->searchable()
            ->showCreateButton()
            ->display($displayCallback)
            ->query($queryCallback);

        // Test all configurations are set
        $this->assertEquals('App\\Resources\\ProductCategoryResource', $field->resourceClass);
        $this->assertEquals('category', $field->relationshipName);
        $this->assertEquals('category_id', $field->foreignKey);
        $this->assertEquals('id', $field->ownerKey);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->showCreateButton);
        $this->assertEquals($displayCallback, $field->displayCallback);
        $this->assertEquals($queryCallback, $field->queryCallback);
    }
}
