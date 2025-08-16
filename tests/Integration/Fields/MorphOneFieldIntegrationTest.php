<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\MorphOne;
use JTD\AdminPanel\Tests\Fixtures\Image;
use JTD\AdminPanel\Tests\Fixtures\ImageResource;
use JTD\AdminPanel\Tests\Fixtures\Post;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MorphOne Field Integration Test.
 *
 * Tests the complete integration between PHP MorphOne field class,
 * API endpoints, and frontend functionality with 100% Nova v5 compatibility.
 */
class MorphOneFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test posts
        $post1 = Post::factory()->create(['id' => 1, 'title' => 'Post with Image', 'content' => 'Content 1']);
        $post2 = Post::factory()->create(['id' => 2, 'title' => 'Post without Image', 'content' => 'Content 2']);
        $post3 = Post::factory()->create(['id' => 3, 'title' => 'Post with Multiple Images', 'content' => 'Content 3']);

        // Create images for posts
        Image::factory()->create([
            'id' => 1,
            'filename' => 'post1-image.jpg',
            'path' => '/images/post1-image.jpg',
            'alt_text' => 'Post 1 Image',
            'imageable_type' => Post::class,
            'imageable_id' => 1,
        ]);

        // Create multiple images for post 3 to test "of many" relationships
        Image::factory()->create([
            'id' => 2,
            'filename' => 'post3-image1.jpg',
            'path' => '/images/post3-image1.jpg',
            'alt_text' => 'Post 3 Image 1',
            'imageable_type' => Post::class,
            'imageable_id' => 3,
            'created_at' => now()->subHour(),
        ]);

        Image::factory()->create([
            'id' => 3,
            'filename' => 'post3-image2.jpg',
            'path' => '/images/post3-image2.jpg',
            'alt_text' => 'Post 3 Image 2 (Latest)',
            'imageable_type' => Post::class,
            'imageable_id' => 3,
            'created_at' => now(),
        ]);

        // Post 2 has no images
    }

    /** @test */
    public function it_creates_morph_one_field_with_nova_syntax(): void
    {
        $field = MorphOne::make('Image');

        $this->assertEquals('Image', $field->name);
        $this->assertEquals('image', $field->attribute);
        $this->assertEquals('image', $field->relationshipName);
    }

    /** @test */
    public function it_creates_morph_one_field_with_custom_resource(): void
    {
        $field = MorphOne::make('Featured Image', 'image')->resource(ImageResource::class);

        $this->assertEquals('Featured Image', $field->name);
        $this->assertEquals('image', $field->attribute);
        $this->assertEquals('image', $field->relationshipName);
        $this->assertEquals(ImageResource::class, $field->resourceClass);
    }

    /** @test */
    public function it_supports_all_nova_configuration_methods(): void
    {
        $field = MorphOne::make('Image')
            ->resource(ImageResource::class)
            ->relationship('image')
            ->morphType('imageable_type')
            ->morphId('imageable_id')
            ->localKey('id');

        $meta = $field->meta();

        $this->assertEquals(ImageResource::class, $meta['resourceClass']);
        $this->assertEquals('image', $meta['relationshipName']);
        $this->assertEquals('imageable_type', $meta['morphType']);
        $this->assertEquals('imageable_id', $meta['morphId']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertFalse($meta['isOfMany']);
    }

    /** @test */
    public function it_resolves_morph_one_relationship_correctly(): void
    {
        $post = Post::with('image')->find(1); // Post with image

        $field = MorphOne::make('Image', 'image')->resource(ImageResource::class);
        $field->resolve($post);

        $this->assertIsArray($field->value);
        $this->assertArrayHasKey('id', $field->value);
        $this->assertArrayHasKey('title', $field->value);
        $this->assertArrayHasKey('resource_class', $field->value);
        $this->assertArrayHasKey('exists', $field->value);
        $this->assertArrayHasKey('morph_type', $field->value);
        $this->assertArrayHasKey('morph_id', $field->value);
        $this->assertArrayHasKey('is_of_many', $field->value);

        $this->assertEquals(1, $field->value['id']);
        $this->assertEquals('post1-image.jpg', $field->value['title']);
        $this->assertEquals(ImageResource::class, $field->value['resource_class']);
        $this->assertTrue($field->value['exists']);
        $this->assertFalse($field->value['is_of_many']);
    }

    /** @test */
    public function it_resolves_empty_relationship_correctly(): void
    {
        $post = Post::find(2); // Post without image

        $field = MorphOne::make('Image', 'image')->resource(ImageResource::class);
        $field->resolve($post);

        $this->assertIsArray($field->value);
        $this->assertNull($field->value['id']);
        $this->assertNull($field->value['title']);
        $this->assertEquals(ImageResource::class, $field->value['resource_class']);
        $this->assertFalse($field->value['exists']);
        $this->assertFalse($field->value['is_of_many']);
    }

    /** @test */
    public function it_resolves_morph_one_of_many_relationship_correctly(): void
    {
        $post = Post::with('latestImage')->find(3); // Post with multiple images

        $field = MorphOne::ofMany('Latest Image', 'latestImage', ImageResource::class);
        $field->resolve($post);

        $this->assertIsArray($field->value);
        $this->assertEquals(3, $field->value['id']); // Should be the latest image (id 3)
        $this->assertEquals('post3-image2.jpg', $field->value['title']);
        $this->assertEquals(ImageResource::class, $field->value['resource_class']);
        $this->assertTrue($field->value['exists']);
        $this->assertTrue($field->value['is_of_many']);
        $this->assertEquals('latestImage', $field->value['of_many_relationship']);
    }

    /** @test */
    public function it_handles_fill_with_custom_callback(): void
    {
        $request = new Request(['image' => 'test']);
        $post = Post::find(1);
        $callbackCalled = false;

        $field = MorphOne::make('Image');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('image', $attribute);
            $this->assertInstanceOf(Post::class, $model);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $post);

        $this->assertTrue($callbackCalled);
    }

    /** @test */
    public function it_includes_correct_meta_data(): void
    {
        $field = MorphOne::make('Image')
            ->resource(ImageResource::class)
            ->relationship('image')
            ->morphType('imageable_type')
            ->morphId('imageable_id')
            ->localKey('id');

        $meta = $field->meta();

        // Check all required meta fields
        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('morphType', $meta);
        $this->assertArrayHasKey('morphId', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('isOfMany', $meta);
        $this->assertArrayHasKey('ofManyRelationship', $meta);
        $this->assertArrayHasKey('ofManyResourceClass', $meta);

        // Check values
        $this->assertEquals(ImageResource::class, $meta['resourceClass']);
        $this->assertEquals('image', $meta['relationshipName']);
        $this->assertEquals('imageable_type', $meta['morphType']);
        $this->assertEquals('imageable_id', $meta['morphId']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertFalse($meta['isOfMany']);
        $this->assertNull($meta['ofManyRelationship']);
        $this->assertNull($meta['ofManyResourceClass']);
    }

    /** @test */
    public function it_includes_correct_meta_data_for_of_many(): void
    {
        $field = MorphOne::ofMany('Latest Image', 'latestImage', ImageResource::class)
            ->morphType('imageable_type')
            ->morphId('imageable_id');

        $meta = $field->meta();

        $this->assertTrue($meta['isOfMany']);
        $this->assertEquals('latestImage', $meta['ofManyRelationship']);
        $this->assertEquals(ImageResource::class, $meta['ofManyResourceClass']);
        $this->assertEquals('imageable_type', $meta['morphType']);
        $this->assertEquals('imageable_id', $meta['morphId']);
    }

    /** @test */
    public function it_serializes_to_json_correctly(): void
    {
        $field = MorphOne::make('Featured Image')
            ->resource(ImageResource::class)
            ->morphType('imageable_type')
            ->morphId('imageable_id')
            ->help('Manage featured image');

        $json = $field->jsonSerialize();

        $this->assertEquals('Featured Image', $json['name']);
        $this->assertEquals('featured_image', $json['attribute']);
        $this->assertEquals('MorphOneField', $json['component']);
        $this->assertEquals(ImageResource::class, $json['resourceClass']);
        $this->assertEquals('imageable_type', $json['morphType']);
        $this->assertEquals('imageable_id', $json['morphId']);
        $this->assertEquals('Manage featured image', $json['helpText']);
    }

    /** @test */
    public function it_guesses_resource_class_correctly(): void
    {
        $field = MorphOne::make('Featured Image', 'featured_image');

        $this->assertEquals('App\\AdminPanel\\Resources\\FeaturedImage', $field->resourceClass);
    }

    /** @test */
    public function it_is_only_shown_on_detail_by_default(): void
    {
        $field = MorphOne::make('Image');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    /** @test */
    public function it_can_be_configured_for_different_views(): void
    {
        $field = MorphOne::make('Image')
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
        // Test with different posts and their image relationships
        $post1 = Post::with('image')->find(1); // Has image
        $post2 = Post::with('image')->find(2); // No image
        $post3 = Post::with('latestImage')->find(3); // Has multiple images

        // Test post 1 (has image)
        $field1 = MorphOne::make('Image', 'image')->resource(ImageResource::class);
        $field1->resolve($post1);

        $this->assertTrue($field1->value['exists']);
        $this->assertEquals('post1-image.jpg', $field1->value['title']);
        $this->assertEquals(ImageResource::class, $field1->value['resource_class']);

        // Test post 2 (no image)
        $field2 = MorphOne::make('Image', 'image')->resource(ImageResource::class);
        $field2->resolve($post2);

        $this->assertFalse($field2->value['exists']);
        $this->assertNull($field2->value['title']);

        // Test post 3 with "of many" (latest image)
        $field3 = MorphOne::ofMany('Latest Image', 'latestImage', ImageResource::class);
        $field3->resolve($post3);

        $this->assertTrue($field3->value['exists']);
        $this->assertEquals('post3-image2.jpg', $field3->value['title']); // Latest image
        $this->assertTrue($field3->value['is_of_many']);
    }

    /** @test */
    public function it_handles_polymorphic_type_and_id_correctly(): void
    {
        $post = Post::with('image')->find(1);

        $field = MorphOne::make('Image')
            ->resource(ImageResource::class)
            ->morphType('imageable_type')
            ->morphId('imageable_id');

        $field->resolve($post);

        // Check that the polymorphic information is included
        $this->assertEquals('imageable_type', $field->value['morph_type']);
        $this->assertEquals('imageable_id', $field->value['morph_id']);

        // Verify the actual image has the correct polymorphic data
        $image = $post->image;
        $this->assertEquals(Post::class, $image->imageable_type);
        $this->assertEquals(1, $image->imageable_id);
    }

    /** @test */
    public function it_handles_soft_deleted_relationships(): void
    {
        // Soft delete the image
        $image = Image::find(1);
        $image->delete();

        $post = Post::with('image')->find(1);

        $field = MorphOne::make('Image', 'image')->resource(ImageResource::class);
        $field->resolve($post);

        // Should show no relationship (excluding soft-deleted)
        $this->assertFalse($field->value['exists']);
        $this->assertNull($field->value['title']);
    }

    /** @test */
    public function it_differentiates_between_regular_and_of_many_fields(): void
    {
        $post = Post::with(['image', 'latestImage'])->find(3); // Post with multiple images

        // Regular MorphOne (should get first image found)
        $regularField = MorphOne::make('Image', 'image')->resource(ImageResource::class);
        $regularField->resolve($post);

        // MorphOne of many (should get latest image)
        $ofManyField = MorphOne::ofMany('Latest Image', 'latestImage', ImageResource::class);
        $ofManyField->resolve($post);

        // Both should exist but have different properties
        $this->assertTrue($regularField->value['exists']);
        $this->assertFalse($regularField->value['is_of_many']);

        $this->assertTrue($ofManyField->value['exists']);
        $this->assertTrue($ofManyField->value['is_of_many']);
        $this->assertEquals('latestImage', $ofManyField->value['of_many_relationship']);

        // The "of many" should get the latest image (id 3)
        $this->assertEquals(3, $ofManyField->value['id']);
        $this->assertEquals('post3-image2.jpg', $ofManyField->value['title']);
    }

    /** @test */
    public function it_works_with_resource_title_method(): void
    {
        $post = Post::with('image')->find(1);

        $field = MorphOne::make('Image', 'image')->resource(ImageResource::class);
        $field->resolve($post);

        // Should use the ImageResource's title() method
        $this->assertEquals('post1-image.jpg', $field->value['title']);
    }

    /** @test */
    public function it_handles_polymorphic_relationships_with_different_models(): void
    {
        // This test verifies that the same Image model can be related to different types
        // In this case, we're testing with Post, but it could be User, Product, etc.

        $post = Post::with('image')->find(1);
        $image = $post->image;

        // Verify the polymorphic relationship is set correctly
        $this->assertEquals(Post::class, $image->imageable_type);
        $this->assertEquals(1, $image->imageable_id);
        $this->assertInstanceOf(Post::class, $image->imageable);
        $this->assertEquals($post->id, $image->imageable->id);
    }
}
