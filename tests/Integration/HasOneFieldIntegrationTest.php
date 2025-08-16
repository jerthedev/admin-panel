<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\HasOne;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\Fixtures\Address;
use JTD\AdminPanel\Tests\Fixtures\Post;
use JTD\AdminPanel\Tests\Fixtures\UserResource;
use JTD\AdminPanel\Tests\Fixtures\AddressResource;
use JTD\AdminPanel\Tests\Fixtures\PostResource;

/**
 * HasOne Field Integration Test
 *
 * Tests the complete integration between PHP HasOne field class,
 * API endpoints, and frontend functionality with 100% Nova v5 compatibility.
 */
class HasOneFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with addresses
        $user1 = User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $user3 = User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);

        // Create addresses
        Address::factory()->create(['id' => 1, 'user_id' => 1, 'street' => '123 Main St', 'city' => 'New York']);
        Address::factory()->create(['id' => 2, 'user_id' => 2, 'street' => '456 Oak Ave', 'city' => 'Los Angeles']);
        // User 3 has no address

        // Create posts for "of many" testing
        Post::factory()->create(['id' => 1, 'user_id' => 1, 'title' => 'First Post', 'created_at' => now()->subDays(2)]);
        Post::factory()->create(['id' => 2, 'user_id' => 1, 'title' => 'Latest Post', 'created_at' => now()]);
    }

    /** @test */
    public function it_creates_has_one_field_with_nova_syntax(): void
    {
        $field = HasOne::make('Address');

        $this->assertEquals('Address', $field->name);
        $this->assertEquals('address', $field->attribute);
        $this->assertEquals('address', $field->relationshipName);
    }

    /** @test */
    public function it_creates_has_one_field_with_custom_resource(): void
    {
        $field = HasOne::make('User Address', 'address', AddressResource::class);

        $this->assertEquals('User Address', $field->name);
        $this->assertEquals('address', $field->attribute);
        $this->assertEquals('address', $field->relationshipName);
        $this->assertEquals(AddressResource::class, $field->resourceClass);
    }

    /** @test */
    public function it_creates_has_one_of_many_field(): void
    {
        $field = HasOne::ofMany('Latest Post', 'latestPost', PostResource::class);

        $this->assertEquals('Latest Post', $field->name);
        $this->assertTrue($field->isOfMany);
        $this->assertEquals('latestPost', $field->ofManyRelationship);
        $this->assertEquals(PostResource::class, $field->ofManyResourceClass);
        $this->assertEquals(PostResource::class, $field->resourceClass);
    }

    /** @test */
    public function it_supports_all_nova_configuration_methods(): void
    {
        $field = HasOne::make('Address')
            ->resource(AddressResource::class)
            ->relationship('userAddress')
            ->foreignKey('user_id')
            ->localKey('id');

        $meta = $field->meta();

        $this->assertEquals(AddressResource::class, $meta['resourceClass']);
        $this->assertEquals('userAddress', $meta['relationshipName']);
        $this->assertEquals('user_id', $meta['foreignKey']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertFalse($meta['isOfMany']);
    }

    /** @test */
    public function it_resolves_has_one_relationship_correctly(): void
    {
        $user = User::with('address')->find(1);

        $field = HasOne::make('Address', 'address', AddressResource::class);
        $field->resolve($user);

        $this->assertIsArray($field->value);
        $this->assertEquals(1, $field->value['id']);
        $this->assertTrue($field->value['exists']);
        $this->assertEquals(AddressResource::class, $field->value['resource_class']);
        $this->assertNotNull($field->value['title']);
    }

    /** @test */
    public function it_resolves_null_relationship_correctly(): void
    {
        $user = User::find(3); // User without address

        $field = HasOne::make('Address', 'address', AddressResource::class);
        $field->resolve($user);

        $this->assertIsArray($field->value);
        $this->assertNull($field->value['id']);
        $this->assertNull($field->value['title']);
        $this->assertFalse($field->value['exists']);
        $this->assertEquals(AddressResource::class, $field->value['resource_class']);
    }

    /** @test */
    public function it_resolves_has_one_of_many_relationship(): void
    {
        $user = User::with('posts')->find(1);

        // Mock the latestPost relationship
        $latestPost = $user->posts()->latest()->first();
        $user->setRelation('latestPost', $latestPost);

        $field = HasOne::ofMany('Latest Post', 'latestPost', PostResource::class);
        $field->resolve($user);

        $this->assertIsArray($field->value);
        $this->assertEquals(2, $field->value['id']); // Latest post ID
        $this->assertTrue($field->value['exists']);
        $this->assertEquals(PostResource::class, $field->value['resource_class']);
    }

    /** @test */
    public function it_gets_related_model_correctly(): void
    {
        $user = User::with('address')->find(1);

        $field = HasOne::make('Address');
        $relatedModel = $field->getRelatedModel($user);

        $this->assertInstanceOf(Address::class, $relatedModel);
        $this->assertEquals(1, $relatedModel->id);
        $this->assertEquals('123 Main St', $relatedModel->street);
    }

    /** @test */
    public function it_gets_related_model_of_many_correctly(): void
    {
        $user = User::with('posts')->find(1);

        // Mock the latestPost relationship
        $latestPost = $user->posts()->latest()->first();
        $user->setRelation('latestPost', $latestPost);

        $field = HasOne::ofMany('Latest Post', 'latestPost', PostResource::class);
        $relatedModel = $field->getRelatedModel($user);

        $this->assertInstanceOf(Post::class, $relatedModel);
        $this->assertEquals(2, $relatedModel->id);
        $this->assertEquals('Latest Post', $relatedModel->title);
    }

    /** @test */
    public function it_returns_null_for_missing_relationship(): void
    {
        $user = User::find(3); // User without address

        $field = HasOne::make('Address');
        $relatedModel = $field->getRelatedModel($user);

        $this->assertNull($relatedModel);
    }

    /** @test */
    public function it_handles_fill_with_custom_callback(): void
    {
        $request = new Request(['address' => 'test']);
        $user = User::find(1);
        $callbackCalled = false;

        $field = HasOne::make('Address');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('address', $attribute);
            $this->assertInstanceOf(User::class, $model);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $user);

        $this->assertTrue($callbackCalled);
    }

    /** @test */
    public function it_includes_correct_meta_data(): void
    {
        $field = HasOne::make('Address')
            ->resource(AddressResource::class)
            ->relationship('userAddress')
            ->foreignKey('user_id')
            ->localKey('uuid');

        $meta = $field->meta();

        // Check all required meta fields
        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('foreignKey', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('isOfMany', $meta);
        $this->assertArrayHasKey('ofManyRelationship', $meta);
        $this->assertArrayHasKey('ofManyResourceClass', $meta);

        // Check values
        $this->assertEquals(AddressResource::class, $meta['resourceClass']);
        $this->assertEquals('userAddress', $meta['relationshipName']);
        $this->assertEquals('user_id', $meta['foreignKey']);
        $this->assertEquals('uuid', $meta['localKey']);
        $this->assertFalse($meta['isOfMany']);
        $this->assertNull($meta['ofManyRelationship']);
        $this->assertNull($meta['ofManyResourceClass']);
    }

    /** @test */
    public function it_includes_correct_meta_data_for_of_many(): void
    {
        $field = HasOne::ofMany('Latest Post', 'latestPost', PostResource::class);

        $meta = $field->meta();

        $this->assertTrue($meta['isOfMany']);
        $this->assertEquals('latestPost', $meta['ofManyRelationship']);
        $this->assertEquals(PostResource::class, $meta['ofManyResourceClass']);
        $this->assertEquals(PostResource::class, $meta['resourceClass']);
    }

    /** @test */
    public function it_serializes_to_json_correctly(): void
    {
        $field = HasOne::make('User Address')
            ->resource(AddressResource::class)
            ->help('Manage user address');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Address', $json['name']);
        $this->assertEquals('user_address', $json['attribute']);
        $this->assertEquals('HasOneField', $json['component']);
        $this->assertEquals(AddressResource::class, $json['resourceClass']);
        $this->assertEquals('Manage user address', $json['helpText']);
        $this->assertFalse($json['isOfMany']);
    }

    /** @test */
    public function it_serializes_of_many_to_json_correctly(): void
    {
        $field = HasOne::ofMany('Latest Post', 'latestPost', PostResource::class)
            ->help('Shows the latest post');

        $json = $field->jsonSerialize();

        $this->assertEquals('Latest Post', $json['name']);
        $this->assertEquals('latest_post', $json['attribute']);
        $this->assertEquals('HasOneField', $json['component']);
        $this->assertEquals(PostResource::class, $json['resourceClass']);
        $this->assertTrue($json['isOfMany']);
        $this->assertEquals('latestPost', $json['ofManyRelationship']);
        $this->assertEquals(PostResource::class, $json['ofManyResourceClass']);
    }

    /** @test */
    public function it_guesses_resource_class_correctly(): void
    {
        $field = HasOne::make('User Profile', 'user_profile');

        $this->assertEquals('App\\AdminPanel\\Resources\\UserProfile', $field->resourceClass);
    }

    /** @test */
    public function it_is_only_shown_on_detail_by_default(): void
    {
        $field = HasOne::make('Address');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    /** @test */
    public function it_can_be_configured_for_different_views(): void
    {
        $field = HasOne::make('Address')
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
        // Test with a user that has both address and posts
        $user = User::with(['address', 'posts'])->find(1);

        // Test regular HasOne
        $addressField = HasOne::make('Address', 'address', AddressResource::class);
        $addressField->resolve($user);

        $this->assertTrue($addressField->value['exists']);
        $this->assertEquals(1, $addressField->value['id']);

        // Test HasOne of many
        $latestPost = $user->posts()->latest()->first();
        $user->setRelation('latestPost', $latestPost);

        $latestPostField = HasOne::ofMany('Latest Post', 'latestPost', PostResource::class);
        $latestPostField->resolve($user);

        $this->assertTrue($latestPostField->value['exists']);
        $this->assertEquals(2, $latestPostField->value['id']);
    }
}
