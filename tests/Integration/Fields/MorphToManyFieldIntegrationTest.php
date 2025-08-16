<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\MorphToMany;
use JTD\AdminPanel\Tests\Fixtures\Post;
use JTD\AdminPanel\Tests\Fixtures\Tag;
use JTD\AdminPanel\Tests\Fixtures\TagResource;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MorphToMany Field Integration Test.
 *
 * Tests the complete integration between PHP MorphToMany field class,
 * API endpoints, and frontend functionality with 100% Nova v5 compatibility.
 */
class MorphToManyFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test posts and users
        $post1 = Post::factory()->create(['title' => 'Post with Tags', 'content' => 'Content 1']);
        $post2 = Post::factory()->create(['title' => 'Post without Tags', 'content' => 'Content 2']);
        $post3 = Post::factory()->create(['title' => 'Post with Many Tags', 'content' => 'Content 3']);
        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        // Create tags
        $tag1 = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php', 'description' => 'PHP programming']);
        $tag2 = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel', 'description' => 'Laravel framework']);
        $tag3 = Tag::factory()->create(['name' => 'Vue.js', 'slug' => 'vuejs', 'description' => 'Vue.js framework']);
        $tag4 = Tag::factory()->create(['name' => 'Testing', 'slug' => 'testing', 'description' => 'Software testing']);
        $tag5 = Tag::factory()->create(['name' => 'JavaScript', 'slug' => 'javascript', 'description' => 'JavaScript programming']);

        // Attach tags to posts with pivot data
        $post1->tags()->attach($tag1->id, ['notes' => 'Primary language', 'priority' => 1]);
        $post1->tags()->attach($tag2->id, ['notes' => 'Main framework', 'priority' => 2]);

        $post3->tags()->attach($tag1->id, ['notes' => 'Backend language', 'priority' => 1]);
        $post3->tags()->attach($tag2->id, ['notes' => 'Framework used', 'priority' => 2]);
        $post3->tags()->attach($tag3->id, ['notes' => 'Frontend framework', 'priority' => 3]);
        $post3->tags()->attach($tag4->id, ['notes' => 'Testing approach', 'priority' => 4]);
        $post3->tags()->attach($tag5->id, ['notes' => 'Frontend language', 'priority' => 5]);

        // Attach tags to users
        $user1->tags()->attach($tag1->id, ['notes' => 'Expert level', 'priority' => 1]);
        $user1->tags()->attach($tag2->id, ['notes' => 'Advanced level', 'priority' => 2]);

        // Post 2 and User 2 have no tags
        // Post 1 has 2 tags, Post 3 has 5 tags, User 1 has 2 tags
    }

    /** @test */
    public function it_creates_morph_to_many_field_with_nova_syntax(): void
    {
        $field = MorphToMany::make('Tags');

        $this->assertEquals('Tags', $field->name);
        $this->assertEquals('tags', $field->attribute);
        $this->assertEquals('tags', $field->relationshipName);
    }

    /** @test */
    public function it_creates_morph_to_many_field_with_custom_resource(): void
    {
        $field = MorphToMany::make('Post Tags', 'tags')->resource(TagResource::class);

        $this->assertEquals('Post Tags', $field->name);
        $this->assertEquals('tags', $field->attribute);
        $this->assertEquals('tags', $field->relationshipName);
        $this->assertEquals(TagResource::class, $field->resourceClass);
    }

    /** @test */
    public function it_supports_all_nova_configuration_methods(): void
    {
        $fieldsCallback = function () {
            return ['notes', 'priority'];
        };

        $field = MorphToMany::make('Tags')
            ->resource(TagResource::class)
            ->relationship('tags')
            ->morphType('taggable_type')
            ->morphId('taggable_id')
            ->localKey('id')
            ->pivotTable('taggables')
            ->fields($fieldsCallback)
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->collapsedByDefault()
            ->showCreateRelationButton()
            ->modalSize('large')
            ->allowDuplicateRelations()
            ->dontReorderAttachables();

        $meta = $field->meta();

        $this->assertEquals(TagResource::class, $meta['resourceClass']);
        $this->assertEquals('tags', $meta['relationshipName']);
        $this->assertEquals('taggable_type', $meta['morphType']);
        $this->assertEquals('taggable_id', $meta['morphId']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertEquals('taggables', $meta['pivotTable']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['collapsable']);
        $this->assertTrue($meta['collapsedByDefault']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
        $this->assertTrue($meta['allowDuplicateRelations']);
        $this->assertFalse($meta['reorderAttachables']);
    }

    /** @test */
    public function it_resolves_morph_to_many_relationship_correctly(): void
    {
        $post = Post::with('tags')->where('title', 'Post with Tags')->first(); // Post with 2 tags

        $field = MorphToMany::make('Tags', 'tags')->resource(TagResource::class);
        $field->resolve($post);

        $this->assertIsArray($field->value);
        $this->assertArrayHasKey('count', $field->value);
        $this->assertArrayHasKey('resource_id', $field->value);
        $this->assertArrayHasKey('resource_class', $field->value);
        $this->assertArrayHasKey('morph_type', $field->value);
        $this->assertArrayHasKey('morph_id', $field->value);
        $this->assertArrayHasKey('pivot_fields', $field->value);
        $this->assertArrayHasKey('pivot_computed_fields', $field->value);
        $this->assertArrayHasKey('pivot_actions', $field->value);

        $this->assertEquals(2, $field->value['count']);
        $this->assertEquals($post->id, $field->value['resource_id']);
        $this->assertEquals(TagResource::class, $field->value['resource_class']);
    }

    /** @test */
    public function it_resolves_empty_relationship_correctly(): void
    {
        $post = Post::where('title', 'Post without Tags')->first(); // Post without tags

        $field = MorphToMany::make('Tags', 'tags')->resource(TagResource::class);
        $field->resolve($post);

        $this->assertIsArray($field->value);
        $this->assertEquals(0, $field->value['count']);
        $this->assertEquals($post->id, $field->value['resource_id']);
        $this->assertEquals(TagResource::class, $field->value['resource_class']);
    }

    /** @test */
    public function it_gets_related_models_correctly(): void
    {
        $post = Post::with('tags')->where('title', 'Post with Tags')->first(); // Post with 2 tags

        $field = MorphToMany::make('Tags');
        $result = $field->getRelatedModels(new Request, $post);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(2, $result['data']);

        // Check that we get the correct tags
        $tagNames = collect($result['data'])->pluck('name')->toArray();
        $this->assertContains('PHP', $tagNames);
        $this->assertContains('Laravel', $tagNames);
    }

    /** @test */
    public function it_gets_related_models_with_search(): void
    {
        $post = Post::with('tags')->where('title', 'Post with Many Tags')->first(); // Post with 5 tags
        $request = new Request(['search' => 'PHP']);

        $field = MorphToMany::make('Tags')->searchable();
        $result = $field->getRelatedModels($request, $post);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('PHP', $result['data'][0]->name);
    }

    /** @test */
    public function it_gets_related_models_with_custom_query(): void
    {
        $post = Post::with('tags')->where('title', 'Post with Many Tags')->first(); // Post with 5 tags
        $request = new Request;

        $field = MorphToMany::make('Tags')->relatableQueryUsing(function ($request, $query) {
            return $query->where('is_active', true);
        });

        $result = $field->getRelatedModels($request, $post);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        // Should only return active tags
        $this->assertCount(5, $result['data']);
    }

    /** @test */
    public function it_gets_attachable_models_correctly(): void
    {
        $post = Post::with('tags')->where('title', 'Post with Tags')->first(); // Post with 2 tags
        $request = new Request;

        $field = MorphToMany::make('Tags')->resource(TagResource::class);
        $result = $field->getAttachableModels($request, $post);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);

        // Should return tags that are not already attached (3 out of 5 total tags)
        $this->assertCount(3, $result['data']);

        // Check that attached tags are excluded
        $tagNames = collect($result['data'])->pluck('name')->toArray();
        $this->assertNotContains('PHP', $tagNames);
        $this->assertNotContains('Laravel', $tagNames);
        $this->assertContains('Vue.js', $tagNames);
        $this->assertContains('Testing', $tagNames);
        $this->assertContains('JavaScript', $tagNames);
    }

    /** @test */
    public function it_gets_attachable_models_with_duplicates_allowed(): void
    {
        $post = Post::with('tags')->where('title', 'Post with Tags')->first(); // Post with 2 tags
        $request = new Request;

        $field = MorphToMany::make('Tags')
            ->resource(TagResource::class)
            ->allowDuplicateRelations();

        $result = $field->getAttachableModels($request, $post);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);

        // Should return all tags when duplicates are allowed
        $this->assertCount(5, $result['data']);
    }

    /** @test */
    public function it_gets_attachable_models_with_search(): void
    {
        $post = Post::with('tags')->where('title', 'Post with Tags')->first(); // Post with 2 tags
        $request = new Request(['search' => 'Vue']);

        $field = MorphToMany::make('Tags')
            ->resource(TagResource::class)
            ->searchable();

        $result = $field->getAttachableModels($request, $post);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('Vue.js', $result['data'][0]->name);
    }

    /** @test */
    public function it_handles_fill_with_custom_callback(): void
    {
        $request = new Request(['tags' => 'test']);
        $post = Post::where('title', 'Post with Tags')->first();
        $callbackCalled = false;

        $field = MorphToMany::make('Tags');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('tags', $attribute);
            $this->assertInstanceOf(Post::class, $model);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $post);

        $this->assertTrue($callbackCalled);
    }

    /** @test */
    public function it_includes_correct_meta_data(): void
    {
        $fieldsCallback = function () {
            return ['notes', 'priority'];
        };

        $field = MorphToMany::make('Tags')
            ->resource(TagResource::class)
            ->relationship('tags')
            ->morphType('taggable_type')
            ->morphId('taggable_id')
            ->localKey('id')
            ->pivotTable('taggables')
            ->fields($fieldsCallback)
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->showCreateRelationButton()
            ->modalSize('large')
            ->allowDuplicateRelations()
            ->dontReorderAttachables();

        $meta = $field->meta();

        // Check all required meta fields
        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('morphType', $meta);
        $this->assertArrayHasKey('morphId', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('pivotTable', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('withSubtitles', $meta);
        $this->assertArrayHasKey('collapsable', $meta);
        $this->assertArrayHasKey('collapsedByDefault', $meta);
        $this->assertArrayHasKey('showCreateRelationButton', $meta);
        $this->assertArrayHasKey('modalSize', $meta);
        $this->assertArrayHasKey('perPage', $meta);
        $this->assertArrayHasKey('allowDuplicateRelations', $meta);
        $this->assertArrayHasKey('reorderAttachables', $meta);

        // Check values
        $this->assertEquals(TagResource::class, $meta['resourceClass']);
        $this->assertEquals('tags', $meta['relationshipName']);
        $this->assertEquals('taggable_type', $meta['morphType']);
        $this->assertEquals('taggable_id', $meta['morphId']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertEquals('taggables', $meta['pivotTable']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['collapsable']);
        $this->assertFalse($meta['collapsedByDefault']); // Not set, so should be false
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
        $this->assertEquals(15, $meta['perPage']);
        $this->assertTrue($meta['allowDuplicateRelations']);
        $this->assertFalse($meta['reorderAttachables']);
    }

    /** @test */
    public function it_serializes_to_json_correctly(): void
    {
        $field = MorphToMany::make('Post Tags')
            ->resource(TagResource::class)
            ->morphType('taggable_type')
            ->morphId('taggable_id')
            ->searchable()
            ->collapsable()
            ->showCreateRelationButton()
            ->help('Manage post tags');

        $json = $field->jsonSerialize();

        $this->assertEquals('Post Tags', $json['name']);
        $this->assertEquals('post_tags', $json['attribute']);
        $this->assertEquals('MorphToManyField', $json['component']);
        $this->assertEquals(TagResource::class, $json['resourceClass']);
        $this->assertEquals('taggable_type', $json['morphType']);
        $this->assertEquals('taggable_id', $json['morphId']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['collapsable']);
        $this->assertTrue($json['showCreateRelationButton']);
        $this->assertEquals('Manage post tags', $json['helpText']);
    }

    /** @test */
    public function it_is_only_shown_on_detail_by_default(): void
    {
        $field = MorphToMany::make('Tags');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    /** @test */
    public function it_can_be_configured_for_different_views(): void
    {
        $field = MorphToMany::make('Tags')
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
        // Test with different models and their tag relationships
        $post1 = Post::with('tags')->where('title', 'Post with Tags')->first(); // 2 tags
        $post2 = Post::with('tags')->where('title', 'Post without Tags')->first(); // 0 tags
        $post3 = Post::with('tags')->where('title', 'Post with Many Tags')->first(); // 5 tags
        $user1 = User::with('tags')->where('name', 'John Doe')->first(); // 2 tags

        // Test post 1
        $field1 = MorphToMany::make('Tags', 'tags')->resource(TagResource::class);
        $field1->resolve($post1);

        $this->assertEquals(2, $field1->value['count']);
        $this->assertEquals(TagResource::class, $field1->value['resource_class']);

        // Test post 2
        $field2 = MorphToMany::make('Tags', 'tags')->resource(TagResource::class);
        $field2->resolve($post2);

        $this->assertEquals(0, $field2->value['count']);

        // Test post 3
        $field3 = MorphToMany::make('Tags', 'tags')->resource(TagResource::class);
        $field3->resolve($post3);

        $this->assertEquals(5, $field3->value['count']);

        // Test user 1
        $field4 = MorphToMany::make('Tags', 'tags')->resource(TagResource::class);
        $field4->resolve($user1);

        $this->assertEquals(2, $field4->value['count']);
    }

    /** @test */
    public function it_supports_conditional_searchable(): void
    {
        // Test with callable searchable
        $field = MorphToMany::make('Tags')->searchable(function () {
            return true;
        });

        $this->assertTrue($field->searchable);

        // Test with callable returning false
        $field2 = MorphToMany::make('Tags')->searchable(function () {
            return false;
        });

        $this->assertFalse($field2->searchable);
    }

    /** @test */
    public function it_supports_conditional_show_create_relation_button(): void
    {
        // Test with callable showCreateRelationButton
        $field = MorphToMany::make('Tags')->showCreateRelationButton(function () {
            return true;
        });

        $this->assertTrue($field->showCreateRelationButton);

        // Test with callable returning false
        $field2 = MorphToMany::make('Tags')->showCreateRelationButton(function () {
            return false;
        });

        $this->assertFalse($field2->showCreateRelationButton);
    }

    /** @test */
    public function it_handles_pagination_correctly(): void
    {
        $post = Post::with('tags')->where('title', 'Post with Many Tags')->first(); // Post with 5 tags
        $request = new Request(['perPage' => 2, 'page' => 1]);

        $field = MorphToMany::make('Tags');
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
    public function it_handles_polymorphic_type_and_id_correctly(): void
    {
        $post = Post::with('tags')->where('title', 'Post with Tags')->first();

        $field = MorphToMany::make('Tags')
            ->resource(TagResource::class)
            ->morphType('taggable_type')
            ->morphId('taggable_id');

        $field->resolve($post);

        // Check that the polymorphic information is included
        $this->assertEquals('taggable_type', $field->value['morph_type']);
        $this->assertEquals('taggable_id', $field->value['morph_id']);

        // Verify the actual tags have the correct polymorphic data
        $tags = $post->tags;
        foreach ($tags as $tag) {
            $this->assertEquals(Post::class, $tag->pivot->taggable_type);
            $this->assertEquals($post->id, $tag->pivot->taggable_id);
        }
    }

    /** @test */
    public function it_works_with_resource_title_method(): void
    {
        $post = Post::with('tags')->where('title', 'Post with Tags')->first();

        $field = MorphToMany::make('Tags', 'tags')->resource(TagResource::class);
        $result = $field->getRelatedModels(new Request, $post);

        // Should use the TagResource's title() method
        $this->assertCount(2, $result['data']);
        $tagNames = collect($result['data'])->pluck('name')->toArray();
        $this->assertContains('PHP', $tagNames);
        $this->assertContains('Laravel', $tagNames);
    }

    /** @test */
    public function it_handles_pivot_data_correctly(): void
    {
        $post = Post::with(['tags' => function ($query) {
            $query->withPivot(['notes', 'priority']);
        }])->where('title', 'Post with Tags')->first();
        $tags = $post->tags;

        // Verify pivot data exists
        foreach ($tags as $tag) {
            $this->assertNotNull($tag->pivot);
            $this->assertNotNull($tag->pivot->notes);
            $this->assertNotNull($tag->pivot->priority);

            if ($tag->name === 'PHP') {
                $this->assertEquals('Primary language', $tag->pivot->notes);
                $this->assertEquals(1, $tag->pivot->priority);
            } elseif ($tag->name === 'Laravel') {
                $this->assertEquals('Main framework', $tag->pivot->notes);
                $this->assertEquals(2, $tag->pivot->priority);
            }
        }
    }

    /** @test */
    public function it_handles_polymorphic_relationships_with_different_models(): void
    {
        // This test verifies that the same Tag model can be related to different types
        // In this case, we're testing with Post and User

        $post = Post::with('tags')->where('title', 'Post with Tags')->first();
        $user = User::with('tags')->where('name', 'John Doe')->first();

        $postTags = $post->tags;
        $userTags = $user->tags;

        // Verify the polymorphic relationships are set correctly
        foreach ($postTags as $tag) {
            $this->assertEquals(Post::class, $tag->pivot->taggable_type);
            $this->assertEquals($post->id, $tag->pivot->taggable_id);
        }

        foreach ($userTags as $tag) {
            $this->assertEquals(User::class, $tag->pivot->taggable_type);
            $this->assertEquals($user->id, $tag->pivot->taggable_id);
        }
    }

    /** @test */
    public function it_handles_reorder_attachables_correctly(): void
    {
        $post = Post::with('tags')->where('title', 'Post without Tags')->first(); // Post without tags
        $request = new Request;

        // Test with reordering enabled (default)
        $field1 = MorphToMany::make('Tags')->resource(TagResource::class);
        $result1 = $field1->getAttachableModels($request, $post);

        // Should be ordered by title/name
        $this->assertCount(5, $result1['data']);

        // Test with reordering disabled
        $field2 = MorphToMany::make('Tags')
            ->resource(TagResource::class)
            ->dontReorderAttachables();

        $result2 = $field2->getAttachableModels($request, $post);

        // Should still return all tags but without specific ordering
        $this->assertCount(5, $result2['data']);
    }
}
