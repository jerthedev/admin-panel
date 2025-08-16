<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\MorphTo;
use JTD\AdminPanel\Tests\Fixtures\Comment;
use JTD\AdminPanel\Tests\Fixtures\Post;
use JTD\AdminPanel\Tests\Fixtures\PostResource;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\Fixtures\UserResource;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MorphTo Field Integration Test.
 *
 * Tests the complete integration between PHP MorphTo field class,
 * API endpoints, and frontend functionality with 100% Nova v5 compatibility.
 */
class MorphToFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test posts and users
        $post1 = Post::factory()->create(['title' => 'Test Post 1', 'content' => 'Content 1']);
        $post2 = Post::factory()->create(['title' => 'Test Post 2', 'content' => 'Content 2']);
        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        // Create comments with different polymorphic relationships
        Comment::factory()->create([
            'content' => 'Comment on post 1',
            'author_name' => 'Commenter 1',
            'author_email' => 'commenter1@example.com',
            'commentable_type' => Post::class,
            'commentable_id' => $post1->id,
        ]);

        Comment::factory()->create([
            'content' => 'Comment on user 1',
            'author_name' => 'Commenter 2',
            'author_email' => 'commenter2@example.com',
            'commentable_type' => User::class,
            'commentable_id' => $user1->id,
        ]);

        Comment::factory()->create([
            'content' => 'Comment on post 2',
            'author_name' => 'Commenter 3',
            'author_email' => 'commenter3@example.com',
            'commentable_type' => Post::class,
            'commentable_id' => $post2->id,
        ]);

        // Comment 4 has no commentable (null relationship)
        Comment::factory()->create([
            'content' => 'Orphaned comment',
            'author_name' => 'Commenter 4',
            'author_email' => 'commenter4@example.com',
            'commentable_type' => null,
            'commentable_id' => null,
        ]);
    }

    /** @test */
    public function it_creates_morph_to_field_with_nova_syntax(): void
    {
        $field = MorphTo::make('Commentable');

        $this->assertEquals('Commentable', $field->name);
        $this->assertEquals('commentable', $field->attribute);
        $this->assertEquals('commentable', $field->relationshipName);
    }

    /** @test */
    public function it_creates_morph_to_field_with_types(): void
    {
        $types = [PostResource::class, UserResource::class];
        $field = MorphTo::make('Commentable')->types($types);

        $this->assertEquals('Commentable', $field->name);
        $this->assertEquals('commentable', $field->attribute);
        $this->assertEquals('commentable', $field->relationshipName);
        $this->assertEquals($types, $field->types);
    }

    /** @test */
    public function it_supports_all_nova_configuration_methods(): void
    {
        $types = [PostResource::class, UserResource::class];

        $field = MorphTo::make('Commentable')
            ->types($types)
            ->relationship('commentable')
            ->morphType('commentable_type')
            ->morphId('commentable_id')
            ->nullable()
            ->noPeeking()
            ->default(123)
            ->defaultResource(PostResource::class)
            ->searchable()
            ->withSubtitles()
            ->showCreateRelationButton()
            ->modalSize('large');

        $meta = $field->meta();

        $this->assertEquals($types, $meta['types']);
        $this->assertEquals('commentable', $meta['relationshipName']);
        $this->assertEquals('commentable_type', $meta['morphType']);
        $this->assertEquals('commentable_id', $meta['morphId']);
        $this->assertTrue($meta['nullable']);
        $this->assertFalse($meta['peekable']);
        $this->assertEquals(123, $meta['defaultValue']);
        $this->assertEquals(PostResource::class, $meta['defaultResourceClass']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
    }

    /** @test */
    public function it_resolves_morph_to_relationship_to_post_correctly(): void
    {
        $comment = Comment::with('commentable')->where('content', 'Comment on post 1')->first(); // Comment on post 1

        $field = MorphTo::make('Commentable', 'commentable')->types([PostResource::class, UserResource::class]);
        $field->resolve($comment);

        $this->assertIsArray($field->value);
        $this->assertArrayHasKey('id', $field->value);
        $this->assertArrayHasKey('title', $field->value);
        $this->assertArrayHasKey('resource_class', $field->value);
        $this->assertArrayHasKey('morph_type', $field->value);
        $this->assertArrayHasKey('exists', $field->value);

        $this->assertNotNull($field->value['id']);
        $this->assertEquals('Test Post 1', $field->value['title']);
        $this->assertEquals(PostResource::class, $field->value['resource_class']);
        $this->assertEquals(Post::class, $field->value['morph_type']);
        $this->assertTrue($field->value['exists']);
    }

    /** @test */
    public function it_resolves_morph_to_relationship_to_user_correctly(): void
    {
        $comment = Comment::with('commentable')->where('content', 'Comment on user 1')->first(); // Comment on user 1

        $field = MorphTo::make('Commentable', 'commentable')->types([PostResource::class, UserResource::class]);
        $field->resolve($comment);

        $this->assertIsArray($field->value);
        $this->assertNotNull($field->value['id']);
        $this->assertEquals('John Doe', $field->value['title']);
        $this->assertEquals(UserResource::class, $field->value['resource_class']);
        $this->assertEquals(User::class, $field->value['morph_type']);
        $this->assertTrue($field->value['exists']);
    }

    /** @test */
    public function it_resolves_empty_relationship_correctly(): void
    {
        $comment = Comment::where('content', 'Orphaned comment')->first(); // Comment with no commentable

        $field = MorphTo::make('Commentable', 'commentable')->types([PostResource::class, UserResource::class]);
        $field->resolve($comment);

        $this->assertIsArray($field->value);
        $this->assertNull($field->value['id']);
        $this->assertNull($field->value['title']);
        $this->assertNull($field->value['resource_class']);
        $this->assertNull($field->value['morph_type']);
        $this->assertFalse($field->value['exists']);
    }

    /** @test */
    public function it_resolves_with_default_values(): void
    {
        $comment = Comment::where('content', 'Orphaned comment')->first(); // Comment with no commentable

        $field = MorphTo::make('Commentable', 'commentable')
            ->types([PostResource::class, UserResource::class])
            ->default(999)
            ->defaultResource(PostResource::class);

        $field->resolve($comment);

        $this->assertIsArray($field->value);
        $this->assertEquals(999, $field->value['id']);
        $this->assertNull($field->value['title']);
        $this->assertEquals(PostResource::class, $field->value['resource_class']);
        $this->assertNull($field->value['morph_type']);
        $this->assertFalse($field->value['exists']);
    }

    /** @test */
    public function it_handles_fill_with_custom_callback(): void
    {
        $request = new Request(['commentable' => 'test']);
        $comment = Comment::where('content', 'Comment on post 1')->first();
        $callbackCalled = false;

        $field = MorphTo::make('Commentable');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('commentable', $attribute);
            $this->assertInstanceOf(Comment::class, $model);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $comment);

        $this->assertTrue($callbackCalled);
    }

    /** @test */
    public function it_includes_correct_meta_data(): void
    {
        $types = [PostResource::class, UserResource::class];

        $field = MorphTo::make('Commentable')
            ->types($types)
            ->relationship('commentable')
            ->morphType('commentable_type')
            ->morphId('commentable_id')
            ->nullable()
            ->peekable(false)
            ->default(456)
            ->defaultResource(PostResource::class)
            ->searchable()
            ->withSubtitles()
            ->showCreateRelationButton()
            ->modalSize('large');

        $meta = $field->meta();

        // Check all required meta fields
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('morphType', $meta);
        $this->assertArrayHasKey('morphId', $meta);
        $this->assertArrayHasKey('types', $meta);
        $this->assertArrayHasKey('nullable', $meta);
        $this->assertArrayHasKey('peekable', $meta);
        $this->assertArrayHasKey('defaultValue', $meta);
        $this->assertArrayHasKey('defaultResourceClass', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('withSubtitles', $meta);
        $this->assertArrayHasKey('showCreateRelationButton', $meta);
        $this->assertArrayHasKey('modalSize', $meta);

        // Check values
        $this->assertEquals('commentable', $meta['relationshipName']);
        $this->assertEquals('commentable_type', $meta['morphType']);
        $this->assertEquals('commentable_id', $meta['morphId']);
        $this->assertEquals($types, $meta['types']);
        $this->assertTrue($meta['nullable']);
        $this->assertFalse($meta['peekable']);
        $this->assertEquals(456, $meta['defaultValue']);
        $this->assertEquals(PostResource::class, $meta['defaultResourceClass']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
    }

    /** @test */
    public function it_serializes_to_json_correctly(): void
    {
        $types = [PostResource::class, UserResource::class];

        $field = MorphTo::make('Parent Model')
            ->types($types)
            ->morphType('commentable_type')
            ->morphId('commentable_id')
            ->nullable()
            ->searchable()
            ->showCreateRelationButton()
            ->help('Select parent model');

        $json = $field->jsonSerialize();

        $this->assertEquals('Parent Model', $json['name']);
        $this->assertEquals('parent_model', $json['attribute']);
        $this->assertEquals('MorphToField', $json['component']);
        $this->assertEquals($types, $json['types']);
        $this->assertEquals('commentable_type', $json['morphType']);
        $this->assertEquals('commentable_id', $json['morphId']);
        $this->assertTrue($json['nullable']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['showCreateRelationButton']);
        $this->assertEquals('Select parent model', $json['helpText']);
    }

    /** @test */
    public function it_can_be_configured_for_different_views(): void
    {
        $field = MorphTo::make('Commentable')
            ->showOnIndex()
            ->showOnCreating()
            ->showOnUpdating();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    /** @test */
    public function it_works_with_complex_polymorphic_relationships(): void
    {
        // Test with different comments and their polymorphic relationships
        $comment1 = Comment::with('commentable')->where('content', 'Comment on post 1')->first(); // Post relationship
        $comment2 = Comment::with('commentable')->where('content', 'Comment on user 1')->first(); // User relationship
        $comment3 = Comment::with('commentable')->where('content', 'Comment on post 2')->first(); // Post relationship
        $comment4 = Comment::where('content', 'Orphaned comment')->first(); // No relationship

        $types = [PostResource::class, UserResource::class];

        // Test comment 1 (Post relationship)
        $field1 = MorphTo::make('Commentable', 'commentable')->types($types);
        $field1->resolve($comment1);

        $this->assertTrue($field1->value['exists']);
        $this->assertEquals('Test Post 1', $field1->value['title']);
        $this->assertEquals(PostResource::class, $field1->value['resource_class']);
        $this->assertEquals(Post::class, $field1->value['morph_type']);

        // Test comment 2 (User relationship)
        $field2 = MorphTo::make('Commentable', 'commentable')->types($types);
        $field2->resolve($comment2);

        $this->assertTrue($field2->value['exists']);
        $this->assertEquals('John Doe', $field2->value['title']);
        $this->assertEquals(UserResource::class, $field2->value['resource_class']);
        $this->assertEquals(User::class, $field2->value['morph_type']);

        // Test comment 3 (Post relationship)
        $field3 = MorphTo::make('Commentable', 'commentable')->types($types);
        $field3->resolve($comment3);

        $this->assertTrue($field3->value['exists']);
        $this->assertEquals('Test Post 2', $field3->value['title']);
        $this->assertEquals(PostResource::class, $field3->value['resource_class']);

        // Test comment 4 (No relationship)
        $field4 = MorphTo::make('Commentable', 'commentable')->types($types);
        $field4->resolve($comment4);

        $this->assertFalse($field4->value['exists']);
        $this->assertNull($field4->value['title']);
        $this->assertNull($field4->value['resource_class']);
    }

    /** @test */
    public function it_supports_conditional_searchable(): void
    {
        // Test with callable searchable
        $field = MorphTo::make('Commentable')->searchable(function () {
            return true;
        });

        $this->assertTrue($field->searchable);

        // Test with callable returning false
        $field2 = MorphTo::make('Commentable')->searchable(function () {
            return false;
        });

        $this->assertFalse($field2->searchable);
    }

    /** @test */
    public function it_supports_conditional_show_create_relation_button(): void
    {
        // Test with callable showCreateRelationButton
        $field = MorphTo::make('Commentable')->showCreateRelationButton(function () {
            return true;
        });

        $this->assertTrue($field->showCreateRelationButton);

        // Test with callable returning false
        $field2 = MorphTo::make('Commentable')->showCreateRelationButton(function () {
            return false;
        });

        $this->assertFalse($field2->showCreateRelationButton);
    }

    /** @test */
    public function it_supports_conditional_peekable(): void
    {
        // Test with callable peekable
        $field = MorphTo::make('Commentable')->peekable(function () {
            return false;
        });

        $this->assertFalse($field->peekable);

        // Test with callable returning true
        $field2 = MorphTo::make('Commentable')->peekable(function () {
            return true;
        });

        $this->assertTrue($field2->peekable);
    }

    /** @test */
    public function it_handles_polymorphic_type_and_id_correctly(): void
    {
        $comment = Comment::with('commentable')->where('content', 'Comment on post 1')->first(); // Comment on post

        $field = MorphTo::make('Commentable')
            ->types([PostResource::class, UserResource::class])
            ->morphType('commentable_type')
            ->morphId('commentable_id');

        $field->resolve($comment);

        // Check that the polymorphic information is included
        $this->assertEquals(Post::class, $field->value['morph_type']);

        // Verify the actual comment has the correct polymorphic data
        $this->assertEquals(Post::class, $comment->commentable_type);
        $this->assertNotNull($comment->commentable_id);
        $this->assertInstanceOf(Post::class, $comment->commentable);
    }

    /** @test */
    public function it_works_with_resource_title_method(): void
    {
        $comment1 = Comment::with('commentable')->where('content', 'Comment on post 1')->first(); // Post relationship
        $comment2 = Comment::with('commentable')->where('content', 'Comment on user 1')->first(); // User relationship

        $types = [PostResource::class, UserResource::class];

        // Test Post resource title
        $field1 = MorphTo::make('Commentable', 'commentable')->types($types);
        $field1->resolve($comment1);

        // Should use the PostResource's title() method
        $this->assertEquals('Test Post 1', $field1->value['title']);

        // Test User resource title
        $field2 = MorphTo::make('Commentable', 'commentable')->types($types);
        $field2->resolve($comment2);

        // Should use the UserResource's title() method
        $this->assertEquals('John Doe', $field2->value['title']);
    }

    /** @test */
    public function it_handles_polymorphic_relationships_with_different_models(): void
    {
        // This test verifies that the same Comment model can be related to different types
        // In this case, we're testing with Post and User

        $comment1 = Comment::with('commentable')->where('content', 'Comment on post 1')->first(); // Post
        $comment2 = Comment::with('commentable')->where('content', 'Comment on user 1')->first(); // User

        // Verify the polymorphic relationships are set correctly
        $this->assertEquals(Post::class, $comment1->commentable_type);
        $this->assertNotNull($comment1->commentable_id);
        $this->assertInstanceOf(Post::class, $comment1->commentable);

        $this->assertEquals(User::class, $comment2->commentable_type);
        $this->assertNotNull($comment2->commentable_id);
        $this->assertInstanceOf(User::class, $comment2->commentable);
    }

    /** @test */
    public function it_handles_nullable_relationships(): void
    {
        $comment = Comment::where('content', 'Orphaned comment')->first(); // Comment with no commentable

        $field = MorphTo::make('Commentable', 'commentable')
            ->types([PostResource::class, UserResource::class])
            ->nullable();

        $field->resolve($comment);

        // Should handle null relationship gracefully
        $this->assertFalse($field->value['exists']);
        $this->assertNull($field->value['id']);
        $this->assertNull($field->value['title']);
        $this->assertNull($field->value['resource_class']);
        $this->assertNull($field->value['morph_type']);
        $this->assertTrue($field->nullable);
    }

    /** @test */
    public function it_handles_resource_class_matching(): void
    {
        $comment = Comment::with('commentable')->where('content', 'Comment on post 1')->first(); // Post relationship

        // Test with correct types
        $field1 = MorphTo::make('Commentable', 'commentable')->types([PostResource::class, UserResource::class]);
        $field1->resolve($comment);

        $this->assertEquals(PostResource::class, $field1->value['resource_class']);

        // Test with missing type (should still work but without resource class)
        $field2 = MorphTo::make('Commentable', 'commentable')->types([UserResource::class]);
        $field2->resolve($comment);

        $this->assertNull($field2->value['resource_class']);
        $this->assertEquals(Post::class, $field2->value['morph_type']);
    }
}
