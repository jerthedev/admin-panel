<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use JTD\AdminPanel\Fields\Tag;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Tag Field Integration Tests.
 *
 * Tests PHP/Laravel integration for Tag field including database operations,
 * model relationships, request handling, and API endpoints.
 * Validates complete PHP backend functionality.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TagFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tables
        Schema::create('test_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('test_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('test_article_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_article_id')->constrained()->onDelete('cascade');
            $table->foreignId('test_tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /** @test */
    public function it_creates_tag_field_with_proper_configuration(): void
    {
        $field = Tag::make('Tags', 'tags');

        $this->assertEquals('Tags', $field->name);
        $this->assertEquals('tags', $field->attribute);
        $this->assertEquals('TagField', $field->component);
        $this->assertEquals('tags', $field->relationshipName);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnIndex);
    }

    /** @test */
    public function it_resolves_tag_relationships_for_display(): void
    {
        $article = TestArticle::create(['title' => 'Test Article']);
        $tag1 = TestTag::create(['name' => 'PHP', 'description' => 'Programming Language']);
        $tag2 = TestTag::create(['name' => 'Laravel', 'description' => 'PHP Framework']);

        $article->tags()->attach([$tag1->id, $tag2->id]);

        $field = Tag::make('Tags', 'tags');
        $field->resolveForDisplay($article);

        $this->assertIsArray($field->value);
        $this->assertEquals(2, $field->value['count']);
        $this->assertEquals($article->id, $field->value['resource_id']);
        $this->assertCount(2, $field->value['tags']);
        $this->assertEquals('PHP', $field->value['tags'][0]['title']);
        $this->assertEquals('Laravel', $field->value['tags'][1]['title']);
    }

    /** @test */
    public function it_handles_empty_tag_relationships(): void
    {
        $article = TestArticle::create(['title' => 'Test Article']);

        $field = Tag::make('Tags', 'tags');
        $field->resolveForDisplay($article);

        $this->assertIsArray($field->value);
        $this->assertEquals(0, $field->value['count']);
        $this->assertEquals($article->id, $field->value['resource_id']);
        $this->assertEmpty($field->value['tags']);
    }

    /** @test */
    public function it_fills_tag_relationships_from_request(): void
    {
        $article = TestArticle::create(['title' => 'Test Article']);
        $tag1 = TestTag::create(['name' => 'PHP']);
        $tag2 = TestTag::create(['name' => 'Laravel']);

        $request = new Request(['tags' => [$tag1->id, $tag2->id]]);
        $field = Tag::make('Tags', 'tags');

        $field->fill($request, $article);

        $this->assertCount(2, $article->tags);
        $this->assertTrue($article->tags->contains($tag1));
        $this->assertTrue($article->tags->contains($tag2));
    }

    /** @test */
    public function it_syncs_tag_relationships(): void
    {
        $article = TestArticle::create(['title' => 'Test Article']);
        $tag1 = TestTag::create(['name' => 'PHP']);
        $tag2 = TestTag::create(['name' => 'Laravel']);
        $tag3 = TestTag::create(['name' => 'Vue.js']);

        // Initially attach tag1 and tag2
        $article->tags()->attach([$tag1->id, $tag2->id]);
        $this->assertCount(2, $article->fresh()->tags);

        // Sync to only tag2 and tag3
        $request = new Request(['tags' => [$tag2->id, $tag3->id]]);
        $field = Tag::make('Tags', 'tags');
        $field->fill($request, $article);

        $article = $article->fresh();
        $this->assertCount(2, $article->tags);
        $this->assertFalse($article->tags->contains($tag1));
        $this->assertTrue($article->tags->contains($tag2));
        $this->assertTrue($article->tags->contains($tag3));
    }

    /** @test */
    public function it_gets_available_tags_with_search(): void
    {
        TestTag::create(['name' => 'PHP', 'description' => 'Programming Language']);
        TestTag::create(['name' => 'Laravel', 'description' => 'PHP Framework']);
        TestTag::create(['name' => 'Vue.js', 'description' => 'JavaScript Framework']);
        TestTag::create(['name' => 'JavaScript', 'description' => 'Programming Language']);

        $field = Tag::make('Tags', 'tags');
        $request = new Request(['search' => 'PHP']);

        $availableTags = $field->getAvailableTags($request);

        $this->assertCount(2, $availableTags); // PHP and Laravel (contains PHP)
        $this->assertEquals('PHP', $availableTags[0]['title']);
        $this->assertEquals('Laravel', $availableTags[1]['title']);
    }

    /** @test */
    public function it_gets_available_tags_without_search(): void
    {
        TestTag::create(['name' => 'PHP']);
        TestTag::create(['name' => 'Laravel']);
        TestTag::create(['name' => 'Vue.js']);

        $field = Tag::make('Tags', 'tags');
        $request = new Request;

        $availableTags = $field->getAvailableTags($request);

        $this->assertCount(3, $availableTags);
    }

    /** @test */
    public function it_applies_custom_relatable_query(): void
    {
        TestTag::create(['name' => 'PHP', 'description' => 'Active']);
        TestTag::create(['name' => 'Laravel', 'description' => 'Inactive']);
        TestTag::create(['name' => 'Vue.js', 'description' => 'Active']);

        $field = Tag::make('Tags', 'tags')
            ->relatableQueryUsing(function ($request, $query) {
                return $query->where('description', 'Active');
            });

        $request = new Request;
        $availableTags = $field->getAvailableTags($request);

        $this->assertCount(2, $availableTags);
        $this->assertEquals('PHP', $availableTags[0]['title']);
        $this->assertEquals('Vue.js', $availableTags[1]['title']);
    }

    /** @test */
    public function it_includes_all_nova_api_options_in_meta(): void
    {
        $field = Tag::make('Tags', 'tags')
            ->resource('App\\Resources\\TagResource')
            ->withPreview()
            ->displayAsList()
            ->showCreateRelationButton()
            ->modalSize('7xl')
            ->preload()
            ->searchable()
            ->relatableQueryUsing(function ($request, $query) {
                return $query->where('active', true);
            });

        $meta = $field->meta();

        $this->assertEquals('App\\Resources\\TagResource', $meta['resourceClass']);
        $this->assertEquals('tags', $meta['relationshipName']);
        $this->assertTrue($meta['withPreview']);
        $this->assertTrue($meta['displayAsList']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('7xl', $meta['modalSize']);
        $this->assertTrue($meta['preload']);
        $this->assertTrue($meta['searchable']);
    }

    /** @test */
    public function it_handles_tag_attachment_operations(): void
    {
        $article = TestArticle::create(['title' => 'Test Article']);
        $tag1 = TestTag::create(['name' => 'PHP']);
        $tag2 = TestTag::create(['name' => 'Laravel']);

        $field = Tag::make('Tags', 'tags');
        $request = new Request;

        // Test attach
        $field->attachTags($request, $article, [$tag1->id]);
        $this->assertCount(1, $article->fresh()->tags);

        // Test attach more
        $field->attachTags($request, $article, [$tag2->id]);
        $this->assertCount(2, $article->fresh()->tags);

        // Test detach
        $field->detachTags($request, $article, [$tag1->id]);
        $this->assertCount(1, $article->fresh()->tags);
        $this->assertTrue($article->fresh()->tags->contains($tag2));

        // Test sync
        $field->syncTags($request, $article, [$tag1->id]);
        $this->assertCount(1, $article->fresh()->tags);
        $this->assertTrue($article->fresh()->tags->contains($tag1));
        $this->assertFalse($article->fresh()->tags->contains($tag2));
    }

    /** @test */
    public function it_handles_null_request_values_gracefully(): void
    {
        $article = TestArticle::create(['title' => 'Test Article']);
        $tag = TestTag::create(['name' => 'PHP']);
        $article->tags()->attach($tag->id);

        $request = new Request(['tags' => null]);
        $field = Tag::make('Tags', 'tags');

        $field->fill($request, $article);

        // Should not change existing relationships when null
        $this->assertCount(1, $article->fresh()->tags);
    }

    /** @test */
    public function it_gets_display_values_from_different_fields(): void
    {
        $tag1 = TestTag::create(['name' => 'PHP']);
        $tag2 = new class extends Model
        {
            protected $fillable = ['title', 'label'];

            public $id = 2;

            public $title = 'Laravel';

            public function getKey()
            {
                return $this->id;
            }
        };
        $tag3 = new class extends Model
        {
            protected $fillable = ['label'];

            public $id = 3;

            public $label = 'Vue.js';

            public function getKey()
            {
                return $this->id;
            }
        };

        $field = Tag::make('Tags', 'tags');

        $this->assertEquals('PHP', $field->getDisplayValue($tag1));
        $this->assertEquals('Laravel', $field->getDisplayValue($tag2));
        $this->assertEquals('Vue.js', $field->getDisplayValue($tag3));
    }
}

/**
 * Test Models.
 */
class TestArticle extends Model
{
    protected $table = 'test_articles';

    protected $fillable = ['title'];

    public function tags()
    {
        return $this->belongsToMany(TestTag::class, 'test_article_tag', 'test_article_id', 'test_tag_id');
    }
}

class TestTag extends Model
{
    protected $table = 'test_tags';

    protected $fillable = ['name', 'description'];

    public function articles()
    {
        return $this->belongsToMany(TestArticle::class, 'test_article_tag', 'test_tag_id', 'test_article_id');
    }
}
