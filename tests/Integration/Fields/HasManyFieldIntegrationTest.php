<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\HasMany;
use JTD\AdminPanel\Tests\Fixtures\Post;
use JTD\AdminPanel\Tests\Fixtures\PostResource;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * HasMany Field Integration Test.
 *
 * Tests the complete integration between PHP HasMany field class,
 * API endpoints, and frontend functionality with 100% Nova v5 compatibility.
 */
class HasManyFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with posts
        $user1 = User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $user3 = User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);

        // Create posts for users
        Post::factory()->create(['id' => 1, 'user_id' => 1, 'title' => 'First Post', 'content' => 'Content 1']);
        Post::factory()->create(['id' => 2, 'user_id' => 1, 'title' => 'Second Post', 'content' => 'Content 2']);
        Post::factory()->create(['id' => 3, 'user_id' => 1, 'title' => 'Third Post', 'content' => 'Content 3']);
        Post::factory()->create(['id' => 4, 'user_id' => 2, 'title' => 'Jane Post', 'content' => 'Jane Content']);
        // User 3 has no posts
    }

    /** @test */
    public function it_creates_has_many_field_with_nova_syntax(): void
    {
        $field = HasMany::make('Posts');

        $this->assertEquals('Posts', $field->name);
        $this->assertEquals('posts', $field->attribute);
        $this->assertEquals('posts', $field->relationshipName);
    }

    /** @test */
    public function it_creates_has_many_field_with_custom_resource(): void
    {
        $field = HasMany::make('User Posts', 'posts', PostResource::class);

        $this->assertEquals('User Posts', $field->name);
        $this->assertEquals('posts', $field->attribute);
        $this->assertEquals('posts', $field->relationshipName);
        $this->assertEquals(PostResource::class, $field->resourceClass);
    }

    /** @test */
    public function it_supports_all_nova_configuration_methods(): void
    {
        $field = HasMany::make('Posts')
            ->resource(PostResource::class)
            ->relationship('userPosts')
            ->foreignKey('author_id')
            ->localKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->collapsedByDefault()
            ->showCreateRelationButton()
            ->modalSize('large');

        $meta = $field->meta();

        $this->assertEquals(PostResource::class, $meta['resourceClass']);
        $this->assertEquals('userPosts', $meta['relationshipName']);
        $this->assertEquals('author_id', $meta['foreignKey']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['collapsable']);
        $this->assertTrue($meta['collapsedByDefault']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
    }

    /** @test */
    public function it_resolves_has_many_relationship_correctly(): void
    {
        $user = User::with('posts')->find(1);

        $field = HasMany::make('Posts', 'posts', PostResource::class);
        $field->resolve($user);

        $this->assertIsArray($field->value);
        $this->assertArrayHasKey('count', $field->value);
        $this->assertArrayHasKey('resource_class', $field->value);
        $this->assertEquals(3, $field->value['count']); // User 1 has 3 posts
        $this->assertEquals(PostResource::class, $field->value['resource_class']);
    }

    /** @test */
    public function it_resolves_empty_relationship_correctly(): void
    {
        $user = User::find(3); // User without posts

        $field = HasMany::make('Posts', 'posts', PostResource::class);
        $field->resolve($user);

        $this->assertIsArray($field->value);
        $this->assertEquals(0, $field->value['count']);
        $this->assertEquals(PostResource::class, $field->value['resource_class']);
    }

    /** @test */
    public function it_gets_related_models_correctly(): void
    {
        $user = User::with('posts')->find(1);

        $field = HasMany::make('Posts');
        $result = $field->getRelatedModels(new Request, $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(3, $result['data']);
        $this->assertEquals('First Post', $result['data'][0]->title);
        $this->assertEquals('Second Post', $result['data'][1]->title);
        $this->assertEquals('Third Post', $result['data'][2]->title);
    }

    /** @test */
    public function it_gets_related_models_with_search(): void
    {
        $user = User::with('posts')->find(1);
        $request = new Request(['search' => 'First']);

        $field = HasMany::make('Posts')->searchable();
        $result = $field->getRelatedModels($request, $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('First Post', $result['data'][0]->title);
    }

    /** @test */
    public function it_gets_related_models_with_custom_query(): void
    {
        $user = User::with('posts')->find(1);
        $request = new Request;

        $field = HasMany::make('Posts')->relatableQueryUsing(function ($request, $query) {
            return $query->where('title', 'like', '%Second%');
        });

        $result = $field->getRelatedModels($request, $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('Second Post', $result['data'][0]->title);
    }

    /** @test */
    public function it_handles_fill_with_custom_callback(): void
    {
        $request = new Request(['posts' => 'test']);
        $user = User::find(1);
        $callbackCalled = false;

        $field = HasMany::make('Posts');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('posts', $attribute);
            $this->assertInstanceOf(User::class, $model);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $user);

        $this->assertTrue($callbackCalled);
    }

    /** @test */
    public function it_includes_correct_meta_data(): void
    {
        $field = HasMany::make('Posts')
            ->resource(PostResource::class)
            ->relationship('userPosts')
            ->foreignKey('author_id')
            ->localKey('uuid')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->showCreateRelationButton()
            ->modalSize('large');

        $meta = $field->meta();

        // Check all required meta fields
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

        // Check values
        $this->assertEquals(PostResource::class, $meta['resourceClass']);
        $this->assertEquals('userPosts', $meta['relationshipName']);
        $this->assertEquals('author_id', $meta['foreignKey']);
        $this->assertEquals('uuid', $meta['localKey']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['collapsable']);
        $this->assertFalse($meta['collapsedByDefault']); // Not set, so should be false
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
    }

    /** @test */
    public function it_serializes_to_json_correctly(): void
    {
        $field = HasMany::make('User Posts')
            ->resource(PostResource::class)
            ->searchable()
            ->collapsable()
            ->showCreateRelationButton()
            ->help('Manage user posts');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Posts', $json['name']);
        $this->assertEquals('user_posts', $json['attribute']);
        $this->assertEquals('HasManyField', $json['component']);
        $this->assertEquals(PostResource::class, $json['resourceClass']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['collapsable']);
        $this->assertTrue($json['showCreateRelationButton']);
        $this->assertEquals('Manage user posts', $json['helpText']);
    }

    /** @test */
    public function it_guesses_resource_class_correctly(): void
    {
        $field = HasMany::make('User Posts', 'user_posts');

        $this->assertEquals('App\\AdminPanel\\Resources\\UserPosts', $field->resourceClass);
    }

    /** @test */
    public function it_is_only_shown_on_detail_by_default(): void
    {
        $field = HasMany::make('Posts');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    /** @test */
    public function it_can_be_configured_for_different_views(): void
    {
        $field = HasMany::make('Posts')
            ->showOnIndex()
            ->showOnCreating()
            ->showOnUpdating();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    /** @test */
    public function it_works_with_complex_resource_relationships(): void
    {
        // Test with a user that has multiple posts
        $user = User::with('posts')->find(1);

        // Test regular HasMany
        $postsField = HasMany::make('Posts', 'posts', PostResource::class);
        $postsField->resolve($user);

        $this->assertEquals(3, $postsField->value['count']);
        $this->assertEquals(PostResource::class, $postsField->value['resource_class']);

        // Test with search functionality
        $searchableField = HasMany::make('Posts', 'posts', PostResource::class)
            ->searchable()
            ->withSubtitles();

        $searchableField->resolve($user);

        $this->assertEquals(3, $searchableField->value['count']);
        $this->assertTrue($searchableField->searchable);
        $this->assertTrue($searchableField->withSubtitles);
    }

    /** @test */
    public function it_supports_conditional_searchable(): void
    {
        // Test with callable searchable
        $field = HasMany::make('Posts')->searchable(function () {
            return true;
        });

        $this->assertTrue($field->searchable);

        // Test with callable returning false
        $field2 = HasMany::make('Posts')->searchable(function () {
            return false;
        });

        $this->assertFalse($field2->searchable);
    }

    /** @test */
    public function it_supports_conditional_show_create_relation_button(): void
    {
        // Test with callable showCreateRelationButton
        $field = HasMany::make('Posts')->showCreateRelationButton(function () {
            return true;
        });

        $this->assertTrue($field->showCreateRelationButton);

        // Test with callable returning false
        $field2 = HasMany::make('Posts')->showCreateRelationButton(function () {
            return false;
        });

        $this->assertFalse($field2->showCreateRelationButton);
    }
}
