<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\HasManyThrough;
use JTD\AdminPanel\Tests\Fixtures\Country;
use JTD\AdminPanel\Tests\Fixtures\Post;
use JTD\AdminPanel\Tests\Fixtures\PostResource;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * HasManyThrough Field Integration Test.
 *
 * Tests the complete integration between PHP HasManyThrough field class,
 * API endpoints, and frontend functionality with 100% Nova v5 compatibility.
 */
class HasManyThroughFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test countries
        $usa = Country::factory()->create(['id' => 1, 'name' => 'United States', 'code' => 'US', 'continent' => 'North America']);
        $canada = Country::factory()->create(['id' => 2, 'name' => 'Canada', 'code' => 'CA', 'continent' => 'North America']);
        $uk = Country::factory()->create(['id' => 3, 'name' => 'United Kingdom', 'code' => 'GB', 'continent' => 'Europe']);

        // Create test users with countries
        $user1 = User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'country_id' => 1]);
        $user2 = User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'country_id' => 1]);
        $user3 = User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com', 'country_id' => 2]);
        $user4 = User::factory()->create(['id' => 4, 'name' => 'Alice Brown', 'email' => 'alice@example.com', 'country_id' => 3]);

        // Create posts for users
        Post::factory()->create(['id' => 1, 'user_id' => 1, 'title' => 'USA Post 1', 'content' => 'Content 1']);
        Post::factory()->create(['id' => 2, 'user_id' => 1, 'title' => 'USA Post 2', 'content' => 'Content 2']);
        Post::factory()->create(['id' => 3, 'user_id' => 2, 'title' => 'USA Post 3', 'content' => 'Content 3']);
        Post::factory()->create(['id' => 4, 'user_id' => 3, 'title' => 'Canada Post 1', 'content' => 'Content 4']);
        Post::factory()->create(['id' => 5, 'user_id' => 4, 'title' => 'UK Post 1', 'content' => 'Content 5']);
        // USA has 3 posts (through 2 users), Canada has 1 post, UK has 1 post
    }

    /** @test */
    public function it_creates_has_many_through_field_with_nova_syntax(): void
    {
        $field = HasManyThrough::make('Posts');

        $this->assertEquals('Posts', $field->name);
        $this->assertEquals('posts', $field->attribute);
        $this->assertEquals('posts', $field->relationshipName);
    }

    /** @test */
    public function it_creates_has_many_through_field_with_custom_resource(): void
    {
        $field = HasManyThrough::make('Country Posts', 'posts', PostResource::class);

        $this->assertEquals('Country Posts', $field->name);
        $this->assertEquals('posts', $field->attribute);
        $this->assertEquals('posts', $field->relationshipName);
        $this->assertEquals(PostResource::class, $field->resourceClass);
    }

    /** @test */
    public function it_supports_all_nova_configuration_methods(): void
    {
        $field = HasManyThrough::make('Posts')
            ->resource(PostResource::class)
            ->relationship('posts')
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

        $this->assertEquals(PostResource::class, $meta['resourceClass']);
        $this->assertEquals('posts', $meta['relationshipName']);
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
    }

    /** @test */
    public function it_resolves_has_many_through_relationship_correctly(): void
    {
        $country = Country::with(['users.posts'])->find(1); // USA with 3 posts

        $field = HasManyThrough::make('Posts', 'posts', PostResource::class);
        $field->resolve($country);

        $this->assertIsArray($field->value);
        $this->assertArrayHasKey('count', $field->value);
        $this->assertArrayHasKey('resource_id', $field->value);
        $this->assertArrayHasKey('resource_class', $field->value);
        $this->assertArrayHasKey('through', $field->value);

        $this->assertEquals(3, $field->value['count']); // USA has 3 posts through users
        $this->assertEquals(1, $field->value['resource_id']);
        $this->assertEquals(PostResource::class, $field->value['resource_class']);
    }

    /** @test */
    public function it_resolves_empty_relationship_correctly(): void
    {
        // Create a country with no users/posts
        $emptyCountry = Country::factory()->create(['id' => 4, 'name' => 'Empty Country', 'code' => 'EC', 'continent' => 'Antarctica']);

        $field = HasManyThrough::make('Posts', 'posts', PostResource::class);
        $field->resolve($emptyCountry);

        $this->assertIsArray($field->value);
        $this->assertEquals(0, $field->value['count']);
        $this->assertEquals(4, $field->value['resource_id']);
        $this->assertEquals(PostResource::class, $field->value['resource_class']);
    }

    /** @test */
    public function it_gets_related_models_correctly(): void
    {
        $country = Country::with(['users.posts'])->find(1); // USA with 3 posts

        $field = HasManyThrough::make('Posts');
        $result = $field->getRelatedModels(new Request, $country);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(3, $result['data']);
        $this->assertEquals('USA Post 1', $result['data'][0]->title);
        $this->assertEquals('USA Post 2', $result['data'][1]->title);
        $this->assertEquals('USA Post 3', $result['data'][2]->title);
    }

    /** @test */
    public function it_gets_related_models_with_search(): void
    {
        $country = Country::with(['users.posts'])->find(1); // USA with 3 posts
        $request = new Request(['search' => 'USA Post 1']);

        $field = HasManyThrough::make('Posts')->searchable();
        $result = $field->getRelatedModels($request, $country);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('USA Post 1', $result['data'][0]->title);
    }

    /** @test */
    public function it_gets_related_models_with_custom_query(): void
    {
        $country = Country::with(['users.posts'])->find(1); // USA with 3 posts
        $request = new Request;

        $field = HasManyThrough::make('Posts')->relatableQueryUsing(function ($request, $query) {
            return $query->where('title', 'like', '%Post 2%');
        });

        $result = $field->getRelatedModels($request, $country);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('USA Post 2', $result['data'][0]->title);
    }

    /** @test */
    public function it_handles_fill_with_custom_callback(): void
    {
        $request = new Request(['posts' => 'test']);
        $country = Country::find(1);
        $callbackCalled = false;

        $field = HasManyThrough::make('Posts');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('posts', $attribute);
            $this->assertInstanceOf(Country::class, $model);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $country);

        $this->assertTrue($callbackCalled);
    }

    /** @test */
    public function it_includes_correct_meta_data(): void
    {
        $field = HasManyThrough::make('Posts')
            ->resource(PostResource::class)
            ->relationship('posts')
            ->through('App\\Models\\User')
            ->firstKey('country_id')
            ->secondKey('user_id')
            ->localKey('id')
            ->secondLocalKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->showCreateRelationButton()
            ->modalSize('large');

        $meta = $field->meta();

        // Check all required meta fields
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

        // Check values
        $this->assertEquals(PostResource::class, $meta['resourceClass']);
        $this->assertEquals('posts', $meta['relationshipName']);
        $this->assertEquals('App\\Models\\User', $meta['through']);
        $this->assertEquals('country_id', $meta['firstKey']);
        $this->assertEquals('user_id', $meta['secondKey']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertEquals('id', $meta['secondLocalKey']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['collapsable']);
        $this->assertFalse($meta['collapsedByDefault']); // Not set, so should be false
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
        $this->assertEquals(15, $meta['perPage']);
    }

    /** @test */
    public function it_serializes_to_json_correctly(): void
    {
        $field = HasManyThrough::make('Country Posts')
            ->resource(PostResource::class)
            ->through('App\\Models\\User')
            ->searchable()
            ->collapsable()
            ->showCreateRelationButton()
            ->help('Manage country posts through users');

        $json = $field->jsonSerialize();

        $this->assertEquals('Country Posts', $json['name']);
        $this->assertEquals('country_posts', $json['attribute']);
        $this->assertEquals('HasManyThroughField', $json['component']);
        $this->assertEquals(PostResource::class, $json['resourceClass']);
        $this->assertEquals('App\\Models\\User', $json['through']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['collapsable']);
        $this->assertTrue($json['showCreateRelationButton']);
        $this->assertEquals('Manage country posts through users', $json['helpText']);
    }

    /** @test */
    public function it_guesses_resource_class_correctly(): void
    {
        $field = HasManyThrough::make('Country Posts', 'country_posts');

        $this->assertEquals('App\\AdminPanel\\Resources\\CountryPosts', $field->resourceClass);
    }

    /** @test */
    public function it_is_only_shown_on_detail_by_default(): void
    {
        $field = HasManyThrough::make('Posts');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    /** @test */
    public function it_can_be_configured_for_different_views(): void
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

    /** @test */
    public function it_works_with_complex_resource_relationships(): void
    {
        // Test with different countries
        $usa = Country::with(['users.posts'])->find(1);
        $canada = Country::with(['users.posts'])->find(2);
        $uk = Country::with(['users.posts'])->find(3);

        // Test USA (3 posts)
        $usaField = HasManyThrough::make('Posts', 'posts', PostResource::class);
        $usaField->resolve($usa);

        $this->assertEquals(3, $usaField->value['count']);
        $this->assertEquals(PostResource::class, $usaField->value['resource_class']);

        // Test Canada (1 post)
        $canadaField = HasManyThrough::make('Posts', 'posts', PostResource::class);
        $canadaField->resolve($canada);

        $this->assertEquals(1, $canadaField->value['count']);

        // Test UK (1 post)
        $ukField = HasManyThrough::make('Posts', 'posts', PostResource::class);
        $ukField->resolve($uk);

        $this->assertEquals(1, $ukField->value['count']);
    }

    /** @test */
    public function it_supports_conditional_searchable(): void
    {
        // Test with callable searchable
        $field = HasManyThrough::make('Posts')->searchable(function () {
            return true;
        });

        $this->assertTrue($field->searchable);

        // Test with callable returning false
        $field2 = HasManyThrough::make('Posts')->searchable(function () {
            return false;
        });

        $this->assertFalse($field2->searchable);
    }

    /** @test */
    public function it_supports_conditional_show_create_relation_button(): void
    {
        // Test with callable showCreateRelationButton
        $field = HasManyThrough::make('Posts')->showCreateRelationButton(function () {
            return true;
        });

        $this->assertTrue($field->showCreateRelationButton);

        // Test with callable returning false
        $field2 = HasManyThrough::make('Posts')->showCreateRelationButton(function () {
            return false;
        });

        $this->assertFalse($field2->showCreateRelationButton);
    }

    /** @test */
    public function it_handles_pagination_correctly(): void
    {
        $country = Country::with(['users.posts'])->find(1); // USA with 3 posts
        $request = new Request(['perPage' => 2, 'page' => 1]);

        $field = HasManyThrough::make('Posts');
        $result = $field->getRelatedModels($request, $country);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(2, $result['data']); // First page with 2 items
        $this->assertEquals(1, $result['meta']['current_page']);
        $this->assertEquals(2, $result['meta']['last_page']);
        $this->assertEquals(2, $result['meta']['per_page']);
        $this->assertEquals(3, $result['meta']['total']);
    }

    /** @test */
    public function it_handles_soft_deleted_relationships(): void
    {
        // Soft delete a post
        $post = Post::find(1);
        $post->delete();

        $country = Country::with(['users.posts'])->find(1);

        $field = HasManyThrough::make('Posts', 'posts', PostResource::class);
        $field->resolve($country);

        // Should show 2 posts instead of 3 (excluding soft-deleted)
        $this->assertEquals(2, $field->value['count']);
    }
}
