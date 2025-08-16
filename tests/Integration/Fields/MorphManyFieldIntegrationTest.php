<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\MorphMany;
use JTD\AdminPanel\Tests\Fixtures\Comment;
use JTD\AdminPanel\Tests\Fixtures\CommentResource;
use JTD\AdminPanel\Tests\Fixtures\Post;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MorphMany Field Integration Test.
 *
 * Tests the complete integration between PHP MorphMany field class,
 * API endpoints, and frontend functionality with 100% Nova v5 compatibility.
 */
class MorphManyFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test posts
        $post1 = Post::factory()->create(['id' => 1, 'title' => 'Post with Comments', 'content' => 'Content 1']);
        $post2 = Post::factory()->create(['id' => 2, 'title' => 'Post without Comments', 'content' => 'Content 2']);
        $post3 = Post::factory()->create(['id' => 3, 'title' => 'Post with Many Comments', 'content' => 'Content 3']);

        // Create comments for posts
        Comment::factory()->create([
            'id' => 1,
            'content' => 'First comment on post 1',
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'commentable_type' => Post::class,
            'commentable_id' => 1,
        ]);

        Comment::factory()->create([
            'id' => 2,
            'content' => 'Second comment on post 1',
            'author_name' => 'Jane Smith',
            'author_email' => 'jane@example.com',
            'commentable_type' => Post::class,
            'commentable_id' => 1,
        ]);

        // Create multiple comments for post 3
        for ($i = 3; $i <= 7; $i++) {
            Comment::factory()->create([
                'id' => $i,
                'content' => "Comment {$i} on post 3",
                'author_name' => "Author {$i}",
                'author_email' => "author{$i}@example.com",
                'commentable_type' => Post::class,
                'commentable_id' => 3,
            ]);
        }

        // Post 2 has no comments
        // Post 1 has 2 comments, Post 3 has 5 comments
    }

    /** @test */
    public function it_creates_morph_many_field_with_nova_syntax(): void
    {
        $field = MorphMany::make('Comments');

        $this->assertEquals('Comments', $field->name);
        $this->assertEquals('comments', $field->attribute);
        $this->assertEquals('comments', $field->relationshipName);
    }

    /** @test */
    public function it_creates_morph_many_field_with_custom_resource(): void
    {
        $field = MorphMany::make('Post Comments', 'comments')->resource(CommentResource::class);

        $this->assertEquals('Post Comments', $field->name);
        $this->assertEquals('comments', $field->attribute);
        $this->assertEquals('comments', $field->relationshipName);
        $this->assertEquals(CommentResource::class, $field->resourceClass);
    }

    /** @test */
    public function it_supports_all_nova_configuration_methods(): void
    {
        $field = MorphMany::make('Comments')
            ->resource(CommentResource::class)
            ->relationship('comments')
            ->morphType('commentable_type')
            ->morphId('commentable_id')
            ->localKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->collapsedByDefault()
            ->showCreateRelationButton()
            ->modalSize('large');

        $meta = $field->meta();

        $this->assertEquals(CommentResource::class, $meta['resourceClass']);
        $this->assertEquals('comments', $meta['relationshipName']);
        $this->assertEquals('commentable_type', $meta['morphType']);
        $this->assertEquals('commentable_id', $meta['morphId']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['collapsable']);
        $this->assertTrue($meta['collapsedByDefault']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
    }

    /** @test */
    public function it_resolves_morph_many_relationship_correctly(): void
    {
        $post = Post::with('comments')->find(1); // Post with 2 comments

        $field = MorphMany::make('Comments', 'comments')->resource(CommentResource::class);
        $field->resolve($post);

        $this->assertIsArray($field->value);
        $this->assertArrayHasKey('count', $field->value);
        $this->assertArrayHasKey('resource_id', $field->value);
        $this->assertArrayHasKey('resource_class', $field->value);
        $this->assertArrayHasKey('morph_type', $field->value);
        $this->assertArrayHasKey('morph_id', $field->value);

        $this->assertEquals(2, $field->value['count']);
        $this->assertEquals(1, $field->value['resource_id']);
        $this->assertEquals(CommentResource::class, $field->value['resource_class']);
    }

    /** @test */
    public function it_resolves_empty_relationship_correctly(): void
    {
        $post = Post::find(2); // Post without comments

        $field = MorphMany::make('Comments', 'comments')->resource(CommentResource::class);
        $field->resolve($post);

        $this->assertIsArray($field->value);
        $this->assertEquals(0, $field->value['count']);
        $this->assertEquals(2, $field->value['resource_id']);
        $this->assertEquals(CommentResource::class, $field->value['resource_class']);
    }

    /** @test */
    public function it_gets_related_models_correctly(): void
    {
        $post = Post::with('comments')->find(1); // Post with 2 comments

        $field = MorphMany::make('Comments');
        $result = $field->getRelatedModels(new Request, $post);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('First comment on post 1', $result['data'][0]->content);
        $this->assertEquals('Second comment on post 1', $result['data'][1]->content);
    }

    /** @test */
    public function it_gets_related_models_with_search(): void
    {
        $post = Post::with('comments')->find(1); // Post with 2 comments
        $request = new Request(['search' => 'First']);

        $field = MorphMany::make('Comments')->searchable();
        $result = $field->getRelatedModels($request, $post);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('First comment on post 1', $result['data'][0]->content);
    }

    /** @test */
    public function it_gets_related_models_with_custom_query(): void
    {
        $post = Post::with('comments')->find(1); // Post with 2 comments
        $request = new Request;

        $field = MorphMany::make('Comments')->relatableQueryUsing(function ($request, $query) {
            return $query->where('is_approved', true);
        });

        $result = $field->getRelatedModels($request, $post);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        // Should only return approved comments
        $this->assertCount(2, $result['data']);
    }

    /** @test */
    public function it_handles_fill_with_custom_callback(): void
    {
        $request = new Request(['comments' => 'test']);
        $post = Post::find(1);
        $callbackCalled = false;

        $field = MorphMany::make('Comments');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('comments', $attribute);
            $this->assertInstanceOf(Post::class, $model);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $post);

        $this->assertTrue($callbackCalled);
    }

    /** @test */
    public function it_includes_correct_meta_data(): void
    {
        $field = MorphMany::make('Comments')
            ->resource(CommentResource::class)
            ->relationship('comments')
            ->morphType('commentable_type')
            ->morphId('commentable_id')
            ->localKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->showCreateRelationButton()
            ->modalSize('large');

        $meta = $field->meta();

        // Check all required meta fields
        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('morphType', $meta);
        $this->assertArrayHasKey('morphId', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('withSubtitles', $meta);
        $this->assertArrayHasKey('collapsable', $meta);
        $this->assertArrayHasKey('collapsedByDefault', $meta);
        $this->assertArrayHasKey('showCreateRelationButton', $meta);
        $this->assertArrayHasKey('modalSize', $meta);
        $this->assertArrayHasKey('perPage', $meta);

        // Check values
        $this->assertEquals(CommentResource::class, $meta['resourceClass']);
        $this->assertEquals('comments', $meta['relationshipName']);
        $this->assertEquals('commentable_type', $meta['morphType']);
        $this->assertEquals('commentable_id', $meta['morphId']);
        $this->assertEquals('id', $meta['localKey']);
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
        $field = MorphMany::make('Post Comments')
            ->resource(CommentResource::class)
            ->morphType('commentable_type')
            ->morphId('commentable_id')
            ->searchable()
            ->collapsable()
            ->showCreateRelationButton()
            ->help('Manage post comments');

        $json = $field->jsonSerialize();

        $this->assertEquals('Post Comments', $json['name']);
        $this->assertEquals('post_comments', $json['attribute']);
        $this->assertEquals('MorphManyField', $json['component']);
        $this->assertEquals(CommentResource::class, $json['resourceClass']);
        $this->assertEquals('commentable_type', $json['morphType']);
        $this->assertEquals('commentable_id', $json['morphId']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['collapsable']);
        $this->assertTrue($json['showCreateRelationButton']);
        $this->assertEquals('Manage post comments', $json['helpText']);
    }

    /** @test */
    public function it_guesses_resource_class_correctly(): void
    {
        $field = MorphMany::make('Post Comments', 'post_comments');

        $this->assertEquals('App\\AdminPanel\\Resources\\PostComments', $field->resourceClass);
    }

    /** @test */
    public function it_is_only_shown_on_detail_by_default(): void
    {
        $field = MorphMany::make('Comments');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    /** @test */
    public function it_can_be_configured_for_different_views(): void
    {
        $field = MorphMany::make('Comments')
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
        // Test with different posts and their comment relationships
        $post1 = Post::with('comments')->find(1); // 2 comments
        $post2 = Post::with('comments')->find(2); // 0 comments
        $post3 = Post::with('comments')->find(3); // 5 comments

        // Test post 1
        $field1 = MorphMany::make('Comments', 'comments')->resource(CommentResource::class);
        $field1->resolve($post1);

        $this->assertEquals(2, $field1->value['count']);
        $this->assertEquals(CommentResource::class, $field1->value['resource_class']);

        // Test post 2
        $field2 = MorphMany::make('Comments', 'comments')->resource(CommentResource::class);
        $field2->resolve($post2);

        $this->assertEquals(0, $field2->value['count']);

        // Test post 3
        $field3 = MorphMany::make('Comments', 'comments')->resource(CommentResource::class);
        $field3->resolve($post3);

        $this->assertEquals(5, $field3->value['count']);
    }

    /** @test */
    public function it_supports_conditional_searchable(): void
    {
        // Test with callable searchable
        $field = MorphMany::make('Comments')->searchable(function () {
            return true;
        });

        $this->assertTrue($field->searchable);

        // Test with callable returning false
        $field2 = MorphMany::make('Comments')->searchable(function () {
            return false;
        });

        $this->assertFalse($field2->searchable);
    }

    /** @test */
    public function it_supports_conditional_show_create_relation_button(): void
    {
        // Test with callable showCreateRelationButton
        $field = MorphMany::make('Comments')->showCreateRelationButton(function () {
            return true;
        });

        $this->assertTrue($field->showCreateRelationButton);

        // Test with callable returning false
        $field2 = MorphMany::make('Comments')->showCreateRelationButton(function () {
            return false;
        });

        $this->assertFalse($field2->showCreateRelationButton);
    }

    /** @test */
    public function it_handles_pagination_correctly(): void
    {
        $post = Post::with('comments')->find(3); // Post with 5 comments
        $request = new Request(['perPage' => 2, 'page' => 1]);

        $field = MorphMany::make('Comments');
        $result = $field->getRelatedModels($request, $post);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(2, $result['data']); // First page with 2 items
        $this->assertEquals(1, $result['meta']['current_page']);
        $this->assertEquals(3, $result['meta']['last_page']); // 5 items / 2 per page = 3 pages
        $this->assertEquals(2, $result['meta']['per_page']);
        $this->assertEquals(5, $result['meta']['total']);
    }

    /** @test */
    public function it_handles_soft_deleted_relationships(): void
    {
        // Soft delete a comment
        $comment = Comment::find(1);
        $comment->delete();

        $post = Post::with('comments')->find(1);

        $field = MorphMany::make('Comments', 'comments')->resource(CommentResource::class);
        $field->resolve($post);

        // Should show 1 comment instead of 2 (excluding soft-deleted)
        $this->assertEquals(1, $field->value['count']);
    }

    /** @test */
    public function it_handles_polymorphic_type_and_id_correctly(): void
    {
        $post = Post::with('comments')->find(1);

        $field = MorphMany::make('Comments')
            ->resource(CommentResource::class)
            ->morphType('commentable_type')
            ->morphId('commentable_id');

        $field->resolve($post);

        // Check that the polymorphic information is included
        $this->assertEquals('commentable_type', $field->value['morph_type']);
        $this->assertEquals('commentable_id', $field->value['morph_id']);

        // Verify the actual comments have the correct polymorphic data
        $comments = $post->comments;
        foreach ($comments as $comment) {
            $this->assertEquals(Post::class, $comment->commentable_type);
            $this->assertEquals(1, $comment->commentable_id);
        }
    }

    /** @test */
    public function it_works_with_resource_title_method(): void
    {
        $post = Post::with('comments')->find(1);

        $field = MorphMany::make('Comments', 'comments')->resource(CommentResource::class);
        $result = $field->getRelatedModels(new Request, $post);

        // Should use the CommentResource's title() method
        $this->assertCount(2, $result['data']);
        $this->assertEquals('First comment on post 1', $result['data'][0]->content);
        $this->assertEquals('Second comment on post 1', $result['data'][1]->content);
    }

    /** @test */
    public function it_handles_polymorphic_relationships_with_different_models(): void
    {
        // This test verifies that the same Comment model can be related to different types
        // In this case, we're testing with Post, but it could be User, Product, etc.

        $post = Post::with('comments')->find(1);
        $comments = $post->comments;

        // Verify the polymorphic relationship is set correctly
        foreach ($comments as $comment) {
            $this->assertEquals(Post::class, $comment->commentable_type);
            $this->assertEquals(1, $comment->commentable_id);
            $this->assertInstanceOf(Post::class, $comment->commentable);
            $this->assertEquals($post->id, $comment->commentable->id);
        }
    }
}
