<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\Fixtures\UserResource;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Resource Unit Tests
 *
 * Tests for the base Resource class functionality including
 * field definitions, authorization, and model interactions.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ResourceTest extends TestCase
{
    public function test_resource_has_correct_model(): void
    {
        $resource = new UserResource();

        $this->assertEquals(User::class, $resource::$model);
    }

    public function test_resource_has_correct_title_field(): void
    {
        $resource = new UserResource();

        $this->assertEquals('name', $resource::$title);
    }

    public function test_resource_has_search_fields(): void
    {
        $resource = new UserResource();

        $this->assertEquals(['name', 'email'], $resource::$search);
    }

    public function test_resource_returns_fields(): void
    {
        $resource = new UserResource();
        $request = new Request();

        $fields = $resource->fields($request);

        $this->assertIsArray($fields);
        $this->assertCount(5, $fields);

        // Check field names
        $fieldNames = array_map(fn($field) => $field->name, $fields);
        $this->assertContains('Name', $fieldNames);
        $this->assertContains('Email', $fieldNames);
        $this->assertContains('Password', $fieldNames);
        $this->assertContains('Is Admin', $fieldNames);
        $this->assertContains('Is Active', $fieldNames);
    }

    public function test_resource_returns_filters(): void
    {
        $resource = new UserResource();
        $request = new Request();

        $filters = $resource->filters($request);

        $this->assertIsArray($filters);
        $this->assertCount(2, $filters);
    }

    public function test_resource_returns_actions(): void
    {
        $resource = new UserResource();
        $request = new Request();

        $actions = $resource->actions($request);

        $this->assertIsArray($actions);
        $this->assertCount(3, $actions);
    }

    public function test_resource_authorization_methods_return_true_by_default(): void
    {
        $resource = new UserResource();
        $request = new Request();

        $this->assertTrue($resource->authorizedToView($request));
        $this->assertTrue($resource->authorizedToCreate($request));
        $this->assertTrue($resource->authorizedToUpdate($request));
        $this->assertTrue($resource->authorizedToDelete($request));
    }

    public function test_resource_can_get_new_model_instance(): void
    {
        $resource = new UserResource();

        $model = $resource->newModel();

        $this->assertInstanceOf(User::class, $model);
    }

    public function test_resource_can_get_model_query(): void
    {
        $resource = new UserResource();

        $query = $resource->newQuery();

        $this->assertEquals(User::class, get_class($query->getModel()));
    }

    public function test_resource_uri_key_generation(): void
    {
        $resource = new UserResource();

        $this->assertEquals('users', $resource::uriKey());
    }

    public function test_resource_label_generation(): void
    {
        $resource = new UserResource();

        $this->assertEquals('Users', $resource::label());
    }

    public function test_resource_singular_label_generation(): void
    {
        $resource = new UserResource();

        $this->assertEquals('User', $resource::singularLabel());
    }

    public function test_resource_searchable_columns(): void
    {
        $columns = UserResource::searchableColumns();

        $this->assertEquals(['name', 'email'], $columns);
    }

    public function test_resource_fields_for_index(): void
    {
        $resource = new UserResource();
        $request = new Request();

        $fields = $resource->fieldsForIndex($request);

        // Password field should be excluded from index
        $fieldNames = array_map(fn($field) => $field->name, $fields);
        $this->assertNotContains('Password', $fieldNames);
    }

    public function test_resource_fields_for_detail(): void
    {
        $resource = new UserResource();
        $request = new Request();

        $fields = $resource->fieldsForDetail($request);

        // Password field should be excluded from detail
        $fieldNames = array_map(fn($field) => $field->name, $fields);
        $this->assertNotContains('Password', $fieldNames);
    }

    public function test_resource_fields_for_create(): void
    {
        $resource = new UserResource();
        $request = new Request();

        $fields = $resource->fieldsForCreate($request);

        // All fields should be included for create
        $fieldNames = array_map(fn($field) => $field->name, $fields);
        $this->assertContains('Password', $fieldNames);
    }

    public function test_resource_fields_for_update(): void
    {
        $resource = new UserResource();
        $request = new Request();

        $fields = $resource->fieldsForUpdate($request);

        // All fields should be included for update
        $fieldNames = array_map(fn($field) => $field->name, $fields);
        $this->assertContains('Password', $fieldNames);
    }

    public function test_resource_can_resolve_fields_for_display(): void
    {
        $resource = new UserResource();
        $request = new Request();
        $user = User::factory()->make([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $fields = $resource->resolveFieldsForDisplay($user, $request);

        $this->assertIsArray($fields);

        // Find name field and check its value
        $nameField = collect($fields)->firstWhere('attribute', 'name');
        $this->assertNotNull($nameField);
        $this->assertEquals('John Doe', $nameField['value']);
    }

    public function test_searchable_columns_includes_fields_marked_searchable(): void
    {
        // Create a test resource with searchable fields
        $testResource = new class extends \JTD\AdminPanel\Resources\Resource {
            public static string $model = \JTD\AdminPanel\Tests\Fixtures\User::class;
            public static string $title = 'name';
            public static array $search = ['email']; // Explicit search column

            public function fields(\Illuminate\Http\Request $request): array
            {
                return [
                    \JTD\AdminPanel\Fields\Text::make('Name')->searchable(), // Should be added
                    \JTD\AdminPanel\Fields\Text::make('Phone')->searchable(), // Should be added
                    \JTD\AdminPanel\Fields\Text::make('Address'), // Should NOT be added
                ];
            }
        };

        $columns = $testResource::searchableColumns();

        // Should include explicit $search columns
        $this->assertContains('email', $columns);

        // Should include fields marked as searchable
        $this->assertContains('name', $columns);
        $this->assertContains('phone', $columns);

        // Should NOT include non-searchable fields
        $this->assertNotContains('address', $columns);

        // Should have 3 total columns (email + name + phone)
        $this->assertCount(3, $columns);
    }

    public function test_searchable_columns_removes_duplicates(): void
    {
        // Create a test resource with overlapping search definitions
        $testResource = new class extends \JTD\AdminPanel\Resources\Resource {
            public static string $model = \JTD\AdminPanel\Tests\Fixtures\User::class;
            public static string $title = 'name';
            public static array $search = ['name', 'email']; // Explicit search columns

            public function fields(\Illuminate\Http\Request $request): array
            {
                return [
                    \JTD\AdminPanel\Fields\Text::make('Name')->searchable(), // Duplicate with $search
                    \JTD\AdminPanel\Fields\Text::make('Phone')->searchable(), // New field
                ];
            }
        };

        $columns = $testResource::searchableColumns();

        // Should have unique columns only
        $this->assertCount(3, $columns); // name, email, phone (no duplicates)
        $this->assertContains('name', $columns);
        $this->assertContains('email', $columns);
        $this->assertContains('phone', $columns);
    }
}
