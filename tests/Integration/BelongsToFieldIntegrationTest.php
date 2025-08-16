<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\BelongsTo;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\Fixtures\Post;
use JTD\AdminPanel\Tests\Fixtures\UserResource;

/**
 * BelongsTo Field Integration Test
 *
 * Tests the complete integration between PHP BelongsTo field class,
 * API endpoints, and frontend functionality.
 */
class BelongsToFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);
    }

    /** @test */
    public function it_creates_belongs_to_field_with_nova_syntax(): void
    {
        $field = BelongsTo::make('User');

        $this->assertEquals('User', $field->name);
        $this->assertEquals('user', $field->attribute);
        $this->assertEquals('user', $field->relationshipName);
    }

    /** @test */
    public function it_creates_belongs_to_field_with_custom_resource(): void
    {
        $field = BelongsTo::make('Author', 'user', UserResource::class);

        $this->assertEquals('Author', $field->name);
        $this->assertEquals('user', $field->attribute);
        $this->assertEquals('user', $field->relationshipName);
        $this->assertEquals(UserResource::class, $field->resourceClass);
    }

    /** @test */
    public function it_supports_all_nova_configuration_methods(): void
    {
        $field = BelongsTo::make('User')
            ->searchable()
            ->withSubtitles()
            ->nullable()
            ->noPeeking()
            ->dontReorderAssociatables()
            ->withoutTrashed()
            ->showCreateRelationButton()
            ->modalSize('lg');

        $meta = $field->meta();

        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['nullable']);
        $this->assertFalse($meta['peekable']);
        $this->assertFalse($meta['reorderAssociatables']);
        $this->assertFalse($meta['withTrashed']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('lg', $meta['modalSize']);
    }

    /** @test */
    public function it_supports_conditional_searchable(): void
    {
        $field = BelongsTo::make('User')
            ->searchable(function ($request) {
                return $request->has('enable_search');
            });

        // Test without enable_search parameter
        $request = new Request();
        $this->assertFalse($field->resolveSearchable());

        // Test with enable_search parameter
        $request = new Request(['enable_search' => true]);
        app()->instance('request', $request);
        $this->assertTrue($field->resolveSearchable());
    }

    /** @test */
    public function it_supports_conditional_create_button(): void
    {
        $field = BelongsTo::make('User')
            ->showCreateRelationButton(function ($request) {
                return $request->user()?->isAdmin() ?? false;
            });

        $meta = $field->meta();
        $this->assertFalse($meta['showCreateRelationButton']); // No admin user
    }

    /** @test */
    public function it_supports_conditional_peeking(): void
    {
        $field = BelongsTo::make('User')
            ->peekable(function ($request) {
                return $request->isMethod('GET');
            });

        $request = new Request();
        $request->setMethod('POST');
        app()->instance('request', $request);
        $this->assertFalse($field->resolvePeekable());

        $request->setMethod('GET');
        app()->instance('request', $request);
        $this->assertTrue($field->resolvePeekable());
    }

    /** @test */
    public function it_supports_relatable_query_using(): void
    {
        $field = BelongsTo::make('User', 'user', UserResource::class)
            ->relatableQueryUsing(function ($request, $query) {
                return $query->where('name', 'like', 'John%');
            });

        $request = new Request();
        $options = $field->getOptions($request);

        $this->assertCount(1, $options);
        $this->assertEquals('John Doe', $options[0]['label']);
    }

    /** @test */
    public function it_supports_depends_on_functionality(): void
    {
        $field = BelongsTo::make('User')
            ->dependsOn('category', function ($field, $request, $formData) {
                if ($formData->category === 'admin') {
                    $field->relatableQueryUsing(function ($request, $query) {
                        return $query->where('email', 'like', '%admin%');
                    });
                }
            });

        $meta = $field->meta();
        $this->assertCount(1, $meta['dependentFields']);
        $this->assertEquals(['category'], $meta['dependentFields'][0]['fields']);
    }

    /** @test */
    public function api_endpoint_returns_correct_options(): void
    {
        $response = $this->postJson('/admin-panel/api/fields/belongs-to/options', [
            'field' => [
                'name' => 'User',
                'attribute' => 'user',
                'resourceClass' => UserResource::class,
                'searchable' => true,
                'withTrashed' => true,
                'reorderAssociatables' => true,
            ],
            'search' => '',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'options' => [
                '*' => ['value', 'label']
            ],
            'total'
        ]);

        $data = $response->json();
        $this->assertCount(3, $data['options']);
        $this->assertEquals(3, $data['total']);
    }

    /** @test */
    public function api_endpoint_filters_by_search(): void
    {
        $response = $this->postJson('/admin-panel/api/fields/belongs-to/options', [
            'field' => [
                'name' => 'User',
                'attribute' => 'user',
                'resourceClass' => UserResource::class,
            ],
            'search' => 'John',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(1, $data['options']);
        $this->assertEquals('John Doe', $data['options'][0]['label']);
    }

    /** @test */
    public function api_endpoint_handles_invalid_resource_class(): void
    {
        $response = $this->postJson('/admin-panel/api/fields/belongs-to/options', [
            'field' => [
                'name' => 'User',
                'attribute' => 'user',
                'resourceClass' => 'NonExistentClass',
            ],
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Resource class not found',
            'options' => []
        ]);
    }

    /** @test */
    public function api_endpoint_handles_missing_field_data(): void
    {
        $response = $this->postJson('/admin-panel/api/fields/belongs-to/options', []);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Invalid field data',
            'options' => []
        ]);
    }

    /** @test */
    public function field_resolves_display_value_correctly(): void
    {
        $user = User::find(1);
        $post = new Post(['user_id' => 1]);
        $post->setRelation('user', $user);

        $field = BelongsTo::make('User', 'user', UserResource::class);
        $field->resolve($post);

        $this->assertEquals('John Doe', $field->value);
    }

    /** @test */
    public function field_fills_model_correctly(): void
    {
        $request = new Request(['user' => 2]);
        $post = new Post();

        $field = BelongsTo::make('User', 'user', UserResource::class);
        $field->fill($request, $post);

        $this->assertEquals(2, $post->user_id);
    }

    /** @test */
    public function field_supports_custom_display_callback(): void
    {
        $user = User::find(1);
        $post = new Post(['user_id' => 1]);
        $post->setRelation('user', $user);

        $field = BelongsTo::make('User', 'user', UserResource::class)
            ->display(function ($user) {
                return $user->name . ' (' . $user->email . ')';
            });

        $field->resolve($post);

        $this->assertEquals('John Doe (john@example.com)', $field->value);
    }

    /** @test */
    public function field_orders_options_by_title_when_enabled(): void
    {
        // Create users in reverse alphabetical order
        User::query()->delete();
        User::factory()->create(['name' => 'Zoe']);
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);

        $field = BelongsTo::make('User', 'user', UserResource::class);
        $options = $field->getOptions(new Request());

        $names = array_column($options, 'label');
        $this->assertEquals(['Alice', 'Bob', 'Zoe'], $names);
    }

    /** @test */
    public function field_preserves_order_when_reordering_disabled(): void
    {
        // Create users in reverse alphabetical order
        User::query()->delete();
        User::factory()->create(['name' => 'Zoe']);
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);

        $field = BelongsTo::make('User', 'user', UserResource::class)
            ->dontReorderAssociatables();

        $options = $field->getOptions(new Request());

        $names = array_column($options, 'label');
        $this->assertEquals(['Zoe', 'Alice', 'Bob'], $names);
    }
}
