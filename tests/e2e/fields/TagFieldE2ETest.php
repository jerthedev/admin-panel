<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use JTD\AdminPanel\Fields\Tag;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Tag Field E2E Tests.
 *
 * End-to-end tests for Tag field covering real-world usage scenarios,
 * complete workflows, and edge cases. Tests the full stack from
 * PHP backend through to expected frontend behavior.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TagFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create realistic blog scenario tables
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('blog_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::create('blog_post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->onDelete('cascade');
            $table->foreignId('blog_tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /** @test */
    public function it_handles_complete_blog_post_tagging_workflow(): void
    {
        // Create blog post
        $post = BlogPost::create([
            'title' => 'Getting Started with Laravel',
            'content' => 'Laravel is a powerful PHP framework...',
            'status' => 'draft',
        ]);

        // Create various tags
        $phpTag = BlogTag::create([
            'name' => 'PHP',
            'slug' => 'php',
            'description' => 'PHP Programming Language',
            'color' => '#777BB4',
            'is_featured' => true,
        ]);

        $laravelTag = BlogTag::create([
            'name' => 'Laravel',
            'slug' => 'laravel',
            'description' => 'Laravel PHP Framework',
            'color' => '#FF2D20',
            'is_featured' => true,
        ]);

        $tutorialTag = BlogTag::create([
            'name' => 'Tutorial',
            'slug' => 'tutorial',
            'description' => 'Step-by-step guides',
            'color' => '#10B981',
            'is_featured' => false,
        ]);

        // Test Tag field with all Nova API features
        $field = Tag::make('Tags', 'tags')
            ->withPreview()
            ->displayAsList()
            ->showCreateRelationButton()
            ->modalSize('7xl')
            ->preload()
            ->searchable()
            ->relatableQueryUsing(function ($request, $query) {
                // Only show featured tags by default
                return $query->where('is_featured', true);
            });

        // Test initial state (no tags)
        $field->resolveForDisplay($post);
        $this->assertEquals(0, $field->value['count']);
        $this->assertEmpty($field->value['tags']);

        // Test adding tags via request
        $request = new Request(['tags' => [$phpTag->id, $laravelTag->id]]);
        $field->fill($request, $post);

        // Verify tags were attached
        $post = $post->fresh();
        $this->assertCount(2, $post->tags);
        $this->assertTrue($post->tags->contains($phpTag));
        $this->assertTrue($post->tags->contains($laravelTag));

        // Test field resolution after tagging
        $field->resolveForDisplay($post);
        $this->assertEquals(2, $field->value['count']);
        $this->assertCount(2, $field->value['tags']);
        $this->assertEquals('PHP', $field->value['tags'][0]['title']);
        $this->assertEquals('Laravel', $field->value['tags'][1]['title']);

        // Test meta data includes all Nova API options
        $meta = $field->meta();
        $this->assertTrue($meta['withPreview']);
        $this->assertTrue($meta['displayAsList']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('7xl', $meta['modalSize']);
        $this->assertTrue($meta['preload']);
        $this->assertTrue($meta['searchable']);
    }

    /** @test */
    public function it_handles_tag_search_and_filtering_scenarios(): void
    {
        // Create diverse set of tags
        BlogTag::create(['name' => 'PHP', 'slug' => 'php', 'description' => 'Server-side language']);
        BlogTag::create(['name' => 'JavaScript', 'slug' => 'javascript', 'description' => 'Client-side language']);
        BlogTag::create(['name' => 'Laravel', 'slug' => 'laravel', 'description' => 'PHP Framework']);
        BlogTag::create(['name' => 'Vue.js', 'slug' => 'vuejs', 'description' => 'JavaScript Framework']);
        BlogTag::create(['name' => 'React', 'slug' => 'react', 'description' => 'JavaScript Library']);
        BlogTag::create(['name' => 'Node.js', 'slug' => 'nodejs', 'description' => 'JavaScript Runtime']);

        $field = Tag::make('Tags', 'tags');

        // Test search by name
        $request = new Request(['search' => 'PHP']);
        $results = $field->getAvailableTags($request);
        $this->assertCount(2, $results); // PHP and Laravel (contains PHP in description)

        // Test search by description
        $request = new Request(['search' => 'Framework']);
        $results = $field->getAvailableTags($request);
        $this->assertCount(2, $results); // Laravel and Vue.js

        // Test search by partial match
        $request = new Request(['search' => 'Java']);
        $results = $field->getAvailableTags($request);
        $this->assertCount(4, $results); // JavaScript, Vue.js, React, Node.js

        // Test no search (returns all, limited to 50)
        $request = new Request;
        $results = $field->getAvailableTags($request);
        $this->assertCount(6, $results);
    }

    /** @test */
    public function it_handles_custom_relatable_query_scenarios(): void
    {
        // Create tags with different statuses
        BlogTag::create(['name' => 'Active Tag 1', 'slug' => 'active-tag-1', 'is_featured' => true]);
        BlogTag::create(['name' => 'Active Tag 2', 'slug' => 'active-tag-2', 'is_featured' => true]);
        BlogTag::create(['name' => 'Inactive Tag 1', 'slug' => 'inactive-tag-1', 'is_featured' => false]);
        BlogTag::create(['name' => 'Inactive Tag 2', 'slug' => 'inactive-tag-2', 'is_featured' => false]);

        // Test with custom query filtering
        $field = Tag::make('Tags', 'tags')
            ->relatableQueryUsing(function ($request, $query) {
                return $query->where('is_featured', true);
            });

        $request = new Request;
        $results = $field->getAvailableTags($request);

        $this->assertCount(2, $results);
        $this->assertEquals('Active Tag 1', $results[0]['title']);
        $this->assertEquals('Active Tag 2', $results[1]['title']);
    }

    /** @test */
    public function it_handles_tag_synchronization_edge_cases(): void
    {
        $post = BlogPost::create(['title' => 'Test Post', 'content' => 'Content']);
        $tag1 = BlogTag::create(['name' => 'Tag 1', 'slug' => 'tag-1']);
        $tag2 = BlogTag::create(['name' => 'Tag 2', 'slug' => 'tag-2']);
        $tag3 = BlogTag::create(['name' => 'Tag 3', 'slug' => 'tag-3']);

        $field = Tag::make('Tags', 'tags');

        // Start with some tags
        $post->tags()->attach([$tag1->id, $tag2->id]);
        $this->assertCount(2, $post->fresh()->tags);

        // Test complete replacement
        $request = new Request(['tags' => [$tag3->id]]);
        $field->fill($request, $post);
        $post = $post->fresh();
        $this->assertCount(1, $post->tags);
        $this->assertTrue($post->tags->contains($tag3));
        $this->assertFalse($post->tags->contains($tag1));
        $this->assertFalse($post->tags->contains($tag2));

        // Test empty array (remove all)
        $request = new Request(['tags' => []]);
        $field->fill($request, $post);
        $this->assertCount(0, $post->fresh()->tags);

        // Test null value (no change)
        $post->tags()->attach($tag1->id);
        $this->assertCount(1, $post->fresh()->tags);
        $request = new Request(['tags' => null]);
        $field->fill($request, $post);
        $this->assertCount(1, $post->fresh()->tags); // Should remain unchanged
    }

    /** @test */
    public function it_handles_display_value_extraction_from_various_models(): void
    {
        // Test with different field combinations
        $tag1 = BlogTag::create(['name' => 'PHP Tag', 'slug' => 'php-tag']);
        $tag2 = new class extends Model
        {
            public $id = 999;

            public $title = 'Title Field Tag';

            public function getKey()
            {
                return $this->id;
            }
        };
        $tag3 = new class extends Model
        {
            public $id = 998;

            public $label = 'Label Field Tag';

            public function getKey()
            {
                return $this->id;
            }
        };

        $field = Tag::make('Tags', 'tags');

        // Test name field (BlogTag)
        $this->assertEquals('PHP Tag', $field->getDisplayValue($tag1));

        // Test title field
        $this->assertEquals('Title Field Tag', $field->getDisplayValue($tag2));

        // Test label field
        $this->assertEquals('Label Field Tag', $field->getDisplayValue($tag3));

        // Test fallback for model without common fields
        $tag4 = new class extends Model
        {
            public $id = 997;

            public function getKey()
            {
                return $this->id;
            }
        };
        $this->assertEquals('Tag #997', $field->getDisplayValue($tag4));
    }

    /** @test */
    public function it_handles_complex_blog_categorization_scenario(): void
    {
        // Create a complex blog with multiple posts and overlapping tags
        $posts = [
            BlogPost::create(['title' => 'Laravel Basics', 'content' => 'Learn Laravel']),
            BlogPost::create(['title' => 'Advanced PHP', 'content' => 'PHP techniques']),
            BlogPost::create(['title' => 'Vue.js Guide', 'content' => 'Frontend with Vue']),
        ];

        $tags = [
            BlogTag::create(['name' => 'PHP', 'slug' => 'php']),
            BlogTag::create(['name' => 'Laravel', 'slug' => 'laravel']),
            BlogTag::create(['name' => 'Vue.js', 'slug' => 'vuejs']),
            BlogTag::create(['name' => 'Frontend', 'slug' => 'frontend']),
            BlogTag::create(['name' => 'Backend', 'slug' => 'backend']),
        ];

        $field = Tag::make('Tags', 'tags');

        // Tag first post (Laravel Basics)
        $request = new Request(['tags' => [$tags[0]->id, $tags[1]->id, $tags[4]->id]]); // PHP, Laravel, Backend
        $field->fill($request, $posts[0]);

        // Tag second post (Advanced PHP)
        $request = new Request(['tags' => [$tags[0]->id, $tags[4]->id]]); // PHP, Backend
        $field->fill($request, $posts[1]);

        // Tag third post (Vue.js Guide)
        $request = new Request(['tags' => [$tags[2]->id, $tags[3]->id]]); // Vue.js, Frontend
        $field->fill($request, $posts[2]);

        // Verify tagging worked correctly
        $posts[0] = $posts[0]->fresh();
        $posts[1] = $posts[1]->fresh();
        $posts[2] = $posts[2]->fresh();

        $this->assertCount(3, $posts[0]->tags);
        $this->assertCount(2, $posts[1]->tags);
        $this->assertCount(2, $posts[2]->tags);

        // Test field resolution for each post
        $field->resolveForDisplay($posts[0]);
        $this->assertEquals(3, $field->value['count']);
        $tagNames = array_column($field->value['tags'], 'title');
        $this->assertContains('PHP', $tagNames);
        $this->assertContains('Laravel', $tagNames);
        $this->assertContains('Backend', $tagNames);

        $field->resolveForDisplay($posts[2]);
        $this->assertEquals(2, $field->value['count']);
        $tagNames = array_column($field->value['tags'], 'title');
        $this->assertContains('Vue.js', $tagNames);
        $this->assertContains('Frontend', $tagNames);
    }

    /** @test */
    public function it_handles_tag_field_with_all_nova_options_enabled(): void
    {
        $post = BlogPost::create(['title' => 'Test Post', 'content' => 'Content']);
        $tag = BlogTag::create(['name' => 'Test Tag', 'slug' => 'test-tag', 'description' => 'A test tag']);

        // Create field with all Nova options
        $field = Tag::make('Tags', 'tags')
            ->withPreview()
            ->displayAsList()
            ->showCreateRelationButton()
            ->modalSize('7xl')
            ->preload()
            ->searchable(true)
            ->relatableQueryUsing(function ($request, $query) {
                return $query->orderBy('name');
            });

        // Test all meta options are present
        $meta = $field->meta();
        $this->assertTrue($meta['withPreview']);
        $this->assertTrue($meta['displayAsList']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('7xl', $meta['modalSize']);
        $this->assertTrue($meta['preload']);
        $this->assertTrue($meta['searchable']);

        // Test field functionality
        $request = new Request(['tags' => [$tag->id]]);
        $field->fill($request, $post);

        $field->resolveForDisplay($post->fresh());
        $this->assertEquals(1, $field->value['count']);
        $this->assertEquals('Test Tag', $field->value['tags'][0]['title']);
        $this->assertEquals('A test tag', $field->value['tags'][0]['subtitle']);
    }
}

/**
 * Test Models for E2E scenarios.
 */
class BlogPost extends Model
{
    protected $fillable = ['title', 'content', 'status'];

    public function tags()
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }
}

class BlogTag extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'color', 'is_featured'];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    public function posts()
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag');
    }
}
