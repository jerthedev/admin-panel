<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Fields\Email;
use JTD\AdminPanel\Fields\ID;
use JTD\AdminPanel\Fields\Number;
use JTD\AdminPanel\Fields\Select;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Resources\Resource;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Field API Endpoints & Laravel Integration Tests.
 *
 * Tests that validate API endpoints serving field data and Laravel
 * framework integration points including middleware, validation,
 * and request/response handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class FieldApiEndpointsLaravelIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable middleware for integration testing
        $this->withoutMiddleware();

        // Register test resource for API testing
        $this->registerTestResource();
    }

    protected function registerTestResource(): void
    {
        // Register the test resource
        AdminPanel::resources([TestUserResource::class]);
    }

    protected function createTestUser(array $attributes = []): object
    {
        $defaults = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ];

        $userData = array_merge($defaults, $attributes);

        // Create user manually since factory might not be available
        $user = new \Illuminate\Foundation\Auth\User;
        foreach ($userData as $key => $value) {
            $user->{$key} = $value;
        }
        $user->save();

        return $user;
    }

    public function test_api_field_suggestions_endpoint_returns_correct_structure(): void
    {
        // Create test data
        $this->createTestUser(['name' => 'John Doe']);
        $this->createTestUser(['name' => 'Jane Smith']);
        $this->createTestUser(['name' => 'Bob Johnson']);

        $response = $this->getJson('/admin/api/resources/test-users/fields/name/suggestions?q=Jo');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'suggestions',
            ]);

        $suggestions = $response->json('suggestions');
        $this->assertIsArray($suggestions);
        $this->assertContains('John Doe', $suggestions);
        $this->assertContains('Bob Johnson', $suggestions);
        $this->assertNotContains('Jane Smith', $suggestions);
    }

    public function test_api_resource_data_endpoint_returns_correct_structure(): void
    {
        // Create test data
        $user1 = $this->createTestUser(['name' => 'John Doe']);
        $user2 = $this->createTestUser(['name' => 'Jane Smith']);

        $response = $this->getJson('/admin/api/resources/test-users/data');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'value',
                        'label',
                        'subtitle',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Check that data contains expected structure
        foreach ($data as $item) {
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('subtitle', $item);
        }
    }

    public function test_api_resource_data_endpoint_supports_search(): void
    {
        // Create test data
        $this->createTestUser(['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->createTestUser(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $response = $this->getJson('/admin/api/resources/test-users/data?search=John');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('John Doe', $data[0]['label']);
    }

    public function test_api_endpoints_handle_nonexistent_resource(): void
    {
        $response = $this->getJson('/admin/api/resources/nonexistent/data');
        $response->assertStatus(404);

        $response = $this->getJson('/admin/api/resources/nonexistent/fields/name/suggestions');
        $response->assertStatus(404);
    }

    public function test_resource_controller_index_includes_field_data(): void
    {
        // Create test data
        $this->createTestUser(['name' => 'John Doe']);

        $response = $this->get('/admin/resources/test-users');

        $response->assertStatus(200);

        // Check that Inertia response includes field data
        $page = $response->viewData('page');
        $this->assertArrayHasKey('props', $page);
        $this->assertArrayHasKey('fields', $page['props']);

        $fields = $page['props']['fields'];
        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);

        // Check that each field has required structure
        foreach ($fields as $field) {
            $this->assertArrayHasKey('component', $field);
            $this->assertArrayHasKey('name', $field);
            $this->assertArrayHasKey('attribute', $field);
        }
    }

    public function test_resource_controller_create_includes_field_definitions(): void
    {
        $response = $this->get('/admin/resources/test-users/create');

        $response->assertStatus(200);

        // Check that Inertia response includes field definitions
        $page = $response->viewData('page');
        $this->assertArrayHasKey('props', $page);
        $this->assertArrayHasKey('fields', $page['props']);

        $fields = $page['props']['fields'];
        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);

        // Verify creation fields are included
        $fieldNames = collect($fields)->pluck('name')->toArray();
        $this->assertContains('Name', $fieldNames);
        $this->assertContains('Email', $fieldNames);
        $this->assertContains('Age', $fieldNames);
        $this->assertContains('Active', $fieldNames);
        $this->assertContains('Role', $fieldNames);
    }

    public function test_resource_controller_show_includes_field_data_with_values(): void
    {
        // Create test data
        $user = $this->createTestUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->get("/admin/resources/test-users/{$user->id}");

        $response->assertStatus(200);

        // Check that Inertia response includes field data with resolved values
        $page = $response->viewData('page');
        $this->assertArrayHasKey('props', $page);
        $this->assertArrayHasKey('fields', $page['props']);

        $fields = $page['props']['fields'];
        $this->assertIsArray($fields);

        // Find specific fields and check their values
        $nameField = collect($fields)->firstWhere('attribute', 'name');
        $emailField = collect($fields)->firstWhere('attribute', 'email');

        $this->assertNotNull($nameField);
        $this->assertNotNull($emailField);
        $this->assertEquals('John Doe', $nameField['value']);
        $this->assertEquals('john@example.com', $emailField['value']);
    }

    public function test_resource_controller_edit_includes_field_data_for_update(): void
    {
        // Create test data
        $user = $this->createTestUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->get("/admin/resources/test-users/{$user->id}/edit");

        $response->assertStatus(200);

        // Check that Inertia response includes field data for editing
        $page = $response->viewData('page');
        $this->assertArrayHasKey('props', $page);
        $this->assertArrayHasKey('fields', $page['props']);

        $fields = $page['props']['fields'];
        $this->assertIsArray($fields);

        // Verify update fields are included with current values
        $nameField = collect($fields)->firstWhere('attribute', 'name');
        $this->assertNotNull($nameField);
        $this->assertEquals('John Doe', $nameField['value']);
    }

    public function test_resource_validation_integration_with_field_rules(): void
    {
        // Test validation with invalid data
        $response = $this->postJson('/admin/resources/test-users', [
            'name' => '', // Required field
            'email' => 'invalid-email', // Invalid email format
            'age' => -5, // Below minimum
            'role' => 'invalid-role', // Not in allowed options
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'email',
                'age',
                'role',
            ]);
    }

    public function test_resource_validation_passes_with_valid_data(): void
    {
        $validData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'active' => true,
            'role' => 'user',
        ];

        $response = $this->postJson('/admin/resources/test-users', $validData);

        // Should redirect after successful creation
        $response->assertRedirect();

        // Verify data was saved
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_resource_update_validation_integration(): void
    {
        // Create existing user
        $user = $this->createTestUser([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        // Test update with invalid data
        $response = $this->putJson("/admin/resources/test-users/{$user->id}", [
            'name' => '', // Required field
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_resource_update_passes_with_valid_data(): void
    {
        // Create existing user
        $user = $this->createTestUser([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'age' => 25,
        ];

        $response = $this->putJson("/admin/resources/test-users/{$user->id}", $updateData);

        // Should redirect after successful update
        $response->assertRedirect();

        // Verify data was updated
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_field_fill_integration_with_model(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'age' => 25,
            'active' => true,
            'role' => 'user',
        ]);

        // Get test resource instance
        $adminPanel = app(AdminPanel::class);
        $resourceInstance = $adminPanel->findResource('test-users');
        $this->assertNotNull($resourceInstance);

        // Create new model and fill with field data
        $model = $resourceInstance->newModel();
        $resourceInstance->fill($request, $model);

        // Verify fields filled the model correctly
        $this->assertEquals('Test User', $model->name);
        $this->assertEquals('test@example.com', $model->email);
    }

    public function test_field_resolution_integration_with_model_data(): void
    {
        // Create test user
        $user = $this->createTestUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Get test resource instance
        $adminPanel = app(AdminPanel::class);
        $resourceInstance = $adminPanel->findResource('test-users');
        $this->assertNotNull($resourceInstance);

        // Create resource with model and resolve fields
        $resourceWithModel = new $resourceInstance($user);
        $request = Request::create('/test');
        $fields = $resourceWithModel->detailFields($request);

        // Verify fields resolved values from model
        $nameField = $fields->firstWhere('attribute', 'name');
        $emailField = $fields->firstWhere('attribute', 'email');

        $this->assertNotNull($nameField);
        $this->assertNotNull($emailField);
        $this->assertEquals('John Doe', $nameField->value);
        $this->assertEquals('john@example.com', $emailField->value);
    }

    public function test_api_endpoints_respect_authorization(): void
    {
        // Register a protected resource
        AdminPanel::resources([ProtectedUserResource::class]);

        $response = $this->getJson('/admin/api/resources/protected-users/data');
        $response->assertStatus(403);
    }

    public function test_error_handling_for_invalid_field_data(): void
    {
        // Test with malformed field data
        $response = $this->postJson('/admin/resources/test-users', [
            'name' => ['invalid' => 'array'], // Should be string
            'email' => 123, // Should be string
            'age' => 'not-a-number', // Should be integer
        ]);

        $response->assertStatus(422);
    }

    public function test_http_status_codes_for_different_scenarios(): void
    {
        // Test 404 for non-existent resource
        $response = $this->get('/admin/resources/non-existent');
        $response->assertStatus(404);

        // Test 404 for non-existent model
        $response = $this->get('/admin/resources/test-users/99999');
        $response->assertStatus(404);

        // Test 200 for valid requests
        $user = $this->createTestUser();
        $response = $this->get("/admin/resources/test-users/{$user->id}");
        $response->assertStatus(200);
    }
}

/**
 * Test Resource for API Integration Testing.
 */
class TestUserResource extends Resource
{
    public static string $model = 'Illuminate\Foundation\Auth\User';

    public static function uriKey(): string
    {
        return 'test-users';
    }

    public function fields(Request $request): array
    {
        return [
            ID::make(),
            Text::make('Name')->rules('required', 'string', 'max:255'),
            Email::make('Email')->rules('required', 'email', 'unique:users,email'),
            Number::make('Age')->rules('nullable', 'integer', 'min:0', 'max:120'),
            Boolean::make('Active')->default(true),
            Select::make('Role')->options([
                'admin' => 'Administrator',
                'user' => 'User',
                'moderator' => 'Moderator',
            ])->rules('required', 'in:admin,user,moderator'),
        ];
    }

    public static function searchableColumns(): array
    {
        return ['name', 'email'];
    }
}

/**
 * Protected Test Resource for Authorization Testing.
 */
class ProtectedUserResource extends Resource
{
    public static string $model = 'Illuminate\Foundation\Auth\User';

    public static function uriKey(): string
    {
        return 'protected-users';
    }

    public function authorizedToView(Request $request): bool
    {
        return false; // Always deny
    }

    public function fields(Request $request): array
    {
        return [ID::make(), Text::make('Name')];
    }
}
