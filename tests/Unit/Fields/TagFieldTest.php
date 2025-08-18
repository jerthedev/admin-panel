<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Tag;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Tag Field Unit Tests.
 *
 * Tests for Tag field class with 100% Nova API compatibility.
 * Tests all Nova Tag field features including make(), withPreview(),
 * displayAsList(), showCreateRelationButton(), modalSize(), preload(),
 * searchable(), and relatableQueryUsing() methods.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TagFieldTest extends TestCase
{
    /** @test */
    public function it_creates_tag_field_with_nova_syntax(): void
    {
        $field = Tag::make('Tags');

        $this->assertEquals('Tags', $field->name);
        $this->assertEquals('tags', $field->attribute);
        $this->assertEquals('TagField', $field->component);
        $this->assertEquals('tags', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Tag', $field->resourceClass);
    }

    /** @test */
    public function it_creates_tag_field_with_custom_attribute(): void
    {
        $field = Tag::make('Article Tags', 'article_tags');

        $this->assertEquals('Article Tags', $field->name);
        $this->assertEquals('article_tags', $field->attribute);
        $this->assertEquals('article_tags', $field->relationshipName);
    }

    /** @test */
    public function it_creates_tag_field_with_resource_class(): void
    {
        $field = Tag::make('Tags', 'tags', 'App\\Resources\\TagResource');

        $this->assertEquals('App\\Resources\\TagResource', $field->resourceClass);
    }

    /** @test */
    public function it_supports_resource_method(): void
    {
        $field = Tag::make('Tags')->resource('App\\Models\\TagResource');

        $this->assertEquals('App\\Models\\TagResource', $field->resourceClass);
    }

    /** @test */
    public function it_supports_with_preview_method(): void
    {
        $field = Tag::make('Tags')->withPreview();

        $this->assertTrue($field->withPreview);
        $this->assertTrue($field->meta()['withPreview']);
    }

    /** @test */
    public function it_supports_display_as_list_method(): void
    {
        $field = Tag::make('Tags')->displayAsList();

        $this->assertTrue($field->displayAsList);
        $this->assertTrue($field->meta()['displayAsList']);
    }

    /** @test */
    public function it_supports_show_create_relation_button_method(): void
    {
        $field = Tag::make('Tags')->showCreateRelationButton();

        $this->assertTrue($field->showCreateRelationButton);
        $this->assertTrue($field->meta()['showCreateRelationButton']);
    }

    /** @test */
    public function it_supports_modal_size_method(): void
    {
        $field = Tag::make('Tags')->modalSize('7xl');

        $this->assertEquals('7xl', $field->modalSize);
        $this->assertEquals('7xl', $field->meta()['modalSize']);
    }

    /** @test */
    public function it_supports_preload_method(): void
    {
        $field = Tag::make('Tags')->preload();

        $this->assertTrue($field->preload);
        $this->assertTrue($field->meta()['preload']);
    }

    /** @test */
    public function it_supports_searchable_method(): void
    {
        $field = Tag::make('Tags')->searchable();

        $this->assertTrue($field->searchable);
        $this->assertTrue($field->meta()['searchable']);
    }

    /** @test */
    public function it_supports_searchable_false(): void
    {
        $field = Tag::make('Tags')->searchable(false);

        $this->assertFalse($field->searchable);
        $this->assertFalse($field->meta()['searchable']);
    }

    /** @test */
    public function it_supports_relatable_query_using_method(): void
    {
        $callback = function ($request, $query) {
            return $query->where('active', true);
        };

        $field = Tag::make('Tags')->relatableQueryUsing($callback);

        $this->assertEquals($callback, $field->relatableQueryCallback);
    }

    /** @test */
    public function it_has_correct_default_values(): void
    {
        $field = Tag::make('Tags');

        $this->assertFalse($field->withPreview);
        $this->assertFalse($field->displayAsList);
        $this->assertFalse($field->showCreateRelationButton);
        $this->assertEquals('md', $field->modalSize);
        $this->assertFalse($field->preload);
        $this->assertTrue($field->searchable);
        $this->assertNull($field->relatableQueryCallback);
    }

    /** @test */
    public function it_shows_on_all_views_by_default(): void
    {
        $field = Tag::make('Tags');

        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnIndex);
    }

    /** @test */
    public function it_guesses_resource_class_correctly(): void
    {
        $field = Tag::make('User Tags');
        $this->assertEquals('App\\AdminPanel\\Resources\\UserTag', $field->resourceClass);

        $field = Tag::make('Categories');
        $this->assertEquals('App\\AdminPanel\\Resources\\Categorie', $field->resourceClass);

        $field = Tag::make('article_tags');
        $this->assertEquals('App\\AdminPanel\\Resources\\ArticleTag', $field->resourceClass);
    }

    /** @test */
    public function it_includes_all_meta_data(): void
    {
        $field = Tag::make('Tags')
            ->resource('App\\Resources\\TagResource')
            ->withPreview()
            ->displayAsList()
            ->showCreateRelationButton()
            ->modalSize('7xl')
            ->preload()
            ->searchable();

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
    public function it_supports_method_chaining(): void
    {
        $field = Tag::make('Tags')
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

        $this->assertEquals('App\\Resources\\TagResource', $field->resourceClass);
        $this->assertTrue($field->withPreview);
        $this->assertTrue($field->displayAsList);
        $this->assertTrue($field->showCreateRelationButton);
        $this->assertEquals('7xl', $field->modalSize);
        $this->assertTrue($field->preload);
        $this->assertTrue($field->searchable);
        $this->assertIsCallable($field->relatableQueryCallback);
    }

    /** @test */
    public function it_handles_fill_with_array_values(): void
    {
        $field = Tag::make('Tags');
        $request = new Request(['tags' => [1, 2, 3]]);
        $model = new class
        {
            public $syncedIds = [];

            public function tags()
            {
                return new class($this)
                {
                    public function __construct(private $parent) {}

                    public function sync($ids)
                    {
                        $this->parent->syncedIds = $ids;
                    }
                };
            }
        };

        $field->fill($request, $model);

        $this->assertEquals([1, 2, 3], $model->syncedIds);
    }

    /** @test */
    public function it_handles_fill_with_null_value(): void
    {
        $field = Tag::make('Tags');
        $request = new Request(['tags' => null]);
        $model = new class
        {
            public $syncedIds = null;

            public function tags()
            {
                return new class($this)
                {
                    public function __construct(private $parent) {}

                    public function sync($ids)
                    {
                        $this->parent->syncedIds = $ids;
                    }
                };
            }
        };

        $field->fill($request, $model);

        $this->assertNull($model->syncedIds);
    }

    /** @test */
    public function it_resolves_for_display_with_tags(): void
    {
        $field = Tag::make('Tags');
        $resource = new class
        {
            public function getKey()
            {
                return 1;
            }

            public $tags;

            public function __construct()
            {
                $this->tags = collect([
                    new class
                    {
                        public $id = 1;

                        public $name = 'PHP';

                        public $description = 'PHP Language';

                        public function getKey()
                        {
                            return $this->id;
                        }
                    },
                    new class
                    {
                        public $id = 2;

                        public $title = 'Laravel';

                        public $subtitle = 'PHP Framework';

                        public function getKey()
                        {
                            return $this->id;
                        }
                    },
                ]);
            }

            public function tags()
            {
                return $this->tags;
            }
        };

        $field->resolveForDisplay($resource);

        $this->assertIsArray($field->value);
        $this->assertEquals(2, $field->value['count']);
        $this->assertEquals(1, $field->value['resource_id']);
        $this->assertCount(2, $field->value['tags']);
        $this->assertEquals('PHP', $field->value['tags'][0]['title']);
        $this->assertEquals('Laravel', $field->value['tags'][1]['title']);
    }

    /** @test */
    public function it_resolves_for_display_with_no_tags(): void
    {
        $field = Tag::make('Tags');
        $resource = new class
        {
            public $tags;

            public function __construct()
            {
                $this->tags = collect([]);
            }

            public function getKey()
            {
                return 1;
            }

            public function tags()
            {
                return $this->tags;
            }
        };

        $field->resolveForDisplay($resource);

        $this->assertIsArray($field->value);
        $this->assertEquals(0, $field->value['count']);
        $this->assertEquals(1, $field->value['resource_id']);
        $this->assertEmpty($field->value['tags']);
    }

    /** @test */
    public function it_handles_resource_without_relationship(): void
    {
        $field = Tag::make('Tags');
        $resource = new class
        {
            public function getKey()
            {
                return 1;
            }
        };

        $field->resolveForDisplay($resource);

        $this->assertIsArray($field->value);
        $this->assertEmpty($field->value);
    }
}
