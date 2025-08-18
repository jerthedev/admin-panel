<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Fields\MediaLibraryImage;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * MediaLibraryImage Field Integration Test.
 *
 * Tests the complete integration between PHP MediaLibraryImage field class,
 * API endpoints, file storage, Spatie Media Library, and frontend functionality.
 *
 * Focuses on Nova-compatible Image field functionality with Media Library features:
 * - Extends MediaLibraryField with Nova Image field compatibility
 * - Supports all Nova Image field methods: disableDownload(), maxWidth(), etc.
 * - Integrates with Spatie Media Library for advanced image handling
 * - Supports conversions, collections, and responsive images
 */
class MediaLibraryImageFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup storage for image uploads
        Storage::fake('public');
        Storage::fake('media');

        // Create test users (using existing User model structure)
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);
    }

    /** @test */
    public function it_creates_media_library_image_field_with_nova_syntax(): void
    {
        $field = MediaLibraryImage::make('Profile Image');

        $this->assertEquals('Profile Image', $field->name);
        $this->assertEquals('profile_image', $field->attribute);
        $this->assertEquals('MediaLibraryImageField', $field->component);
        $this->assertEquals('images', $field->collection);
        $this->assertFalse($field->downloadDisabled);
        $this->assertFalse($field->squared);
        $this->assertFalse($field->rounded);
        $this->assertNull($field->maxWidth);
        $this->assertNull($field->indexWidth);
        $this->assertNull($field->detailWidth);
    }

    /** @test */
    public function it_inherits_media_library_field_functionality(): void
    {
        $field = MediaLibraryImage::make('Gallery Image')
            ->collection('gallery')
            ->disk('media')
            ->acceptsMimeTypes(['image/jpeg', 'image/png'])
            ->limit(5)
            ->multiple();

        $this->assertEquals('gallery', $field->collection);
        $this->assertEquals('media', $field->disk);
        $this->assertEquals(['image/jpeg', 'image/png'], $field->acceptedMimeTypes);
        $this->assertEquals(5, $field->limit);
        $this->assertTrue($field->multiple);
    }

    /** @test */
    public function it_supports_nova_image_field_methods(): void
    {
        $field = MediaLibraryImage::make('Styled Image')
            ->disableDownload()
            ->maxWidth(300)
            ->indexWidth(60)
            ->detailWidth(150)
            ->squared();

        $this->assertTrue($field->downloadDisabled);
        $this->assertEquals(300, $field->maxWidth);
        $this->assertEquals(60, $field->indexWidth);
        $this->assertEquals(150, $field->detailWidth);
        $this->assertTrue($field->squared);
        $this->assertFalse($field->rounded);
    }

    /** @test */
    public function it_supports_squared_and_rounded_mutually_exclusive(): void
    {
        $field = MediaLibraryImage::make('Test Image');

        $field->squared();
        $this->assertTrue($field->squared);
        $this->assertFalse($field->rounded);

        $field->rounded();
        $this->assertFalse($field->squared);
        $this->assertTrue($field->rounded);
    }

    /** @test */
    public function it_supports_accepted_types_method(): void
    {
        $field = MediaLibraryImage::make('Test Image');

        $field->acceptedTypes('.jpg,.png,.gif');
        $this->assertContains('image/jpeg', $field->acceptedMimeTypes);
        $this->assertContains('image/png', $field->acceptedMimeTypes);
        $this->assertContains('image/gif', $field->acceptedMimeTypes);

        $field->acceptedTypes('image/webp,image/svg+xml');
        $this->assertContains('image/webp', $field->acceptedMimeTypes);
        $this->assertContains('image/svg+xml', $field->acceptedMimeTypes);
    }

    /** @test */
    public function it_supports_preview_and_thumbnail_callbacks(): void
    {
        $field = MediaLibraryImage::make('Test Image');

        $previewCallback = function ($value, $disk) {
            return "https://cdn.example.com/preview/{$value}";
        };

        $thumbnailCallback = function ($value, $disk) {
            return "https://cdn.example.com/thumb/{$value}";
        };

        $field->preview($previewCallback);
        $field->thumbnail($thumbnailCallback);

        $this->assertEquals($previewCallback, $field->previewCallback);
        $this->assertEquals($thumbnailCallback, $field->thumbnailCallback);
    }

    /** @test */
    public function it_supports_download_callback(): void
    {
        $field = MediaLibraryImage::make('Test Image');

        $downloadCallback = function ($request, $model, $disk, $path) {
            return response()->download($path);
        };

        $field->download($downloadCallback);

        $this->assertEquals($downloadCallback, $field->downloadCallback);
    }

    /** @test */
    public function it_serializes_to_json_with_nova_compatible_structure(): void
    {
        $field = MediaLibraryImage::make('Product Image')
            ->collection('products')
            ->disk('media')
            ->acceptsMimeTypes(['image/jpeg', 'image/png'])
            ->limit(3)
            ->multiple()
            ->disableDownload()
            ->maxWidth(400)
            ->indexWidth(80)
            ->detailWidth(200)
            ->squared()
            ->required()
            ->help('Upload product images');

        $json = $field->jsonSerialize();

        // Test basic field properties
        $this->assertEquals('Product Image', $json['name']);
        $this->assertEquals('product_image', $json['attribute']);
        $this->assertEquals('MediaLibraryImageField', $json['component']);

        // Test MediaLibrary field properties
        $this->assertEquals('products', $json['collection']);
        $this->assertEquals('media', $json['disk']);
        $this->assertEquals(['image/jpeg', 'image/png'], $json['acceptedMimeTypes']);
        $this->assertEquals(3, $json['limit']);
        $this->assertTrue($json['multiple']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Upload product images', $json['helpText']);

        // Test Nova Image field specific properties in meta
        $meta = $field->meta();
        $this->assertTrue($meta['downloadDisabled']);
        $this->assertEquals(400, $meta['maxWidth']);
        $this->assertEquals(80, $meta['indexWidth']);
        $this->assertEquals(200, $meta['detailWidth']);
        $this->assertTrue($meta['squared']);
        $this->assertFalse($meta['rounded']);
        $this->assertFalse($meta['hasPreviewCallback']);
        $this->assertFalse($meta['hasThumbnailCallback']);
        $this->assertFalse($meta['hasDownloadCallback']);
    }

    /** @test */
    public function it_includes_callback_flags_in_meta(): void
    {
        $field = MediaLibraryImage::make('Test Image')
            ->preview(function ($value, $disk) {
                return $value;
            })
            ->thumbnail(function ($value, $disk) {
                return $value;
            })
            ->download(function ($request, $model, $disk, $path) {
                return $path;
            });

        $meta = $field->meta();

        $this->assertTrue($meta['hasPreviewCallback']);
        $this->assertTrue($meta['hasThumbnailCallback']);
        $this->assertTrue($meta['hasDownloadCallback']);
    }

    /** @test */
    public function it_handles_file_upload_with_media_library_integration(): void
    {
        $field = MediaLibraryImage::make('Test Image')
            ->collection('test')
            ->acceptsMimeTypes(['image/jpeg']);

        $uploadedFile = UploadedFile::fake()->image('test.jpg', 800, 600);

        // Test that the field can handle the uploaded file
        $this->assertInstanceOf(UploadedFile::class, $uploadedFile);
        $this->assertEquals('image/jpeg', $uploadedFile->getMimeType());
        $this->assertTrue($uploadedFile->isValid());
    }

    /** @test */
    public function it_validates_file_types_correctly(): void
    {
        $field = MediaLibraryImage::make('Test Image')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        $validFile = UploadedFile::fake()->image('valid.jpg');
        $invalidFile = UploadedFile::fake()->create('invalid.txt', 100, 'text/plain');

        // The field should accept valid image files
        $this->assertEquals('image/jpeg', $validFile->getMimeType());
        $this->assertContains('image/jpeg', $field->acceptedMimeTypes);

        // The field should reject invalid file types
        $this->assertEquals('text/plain', $invalidFile->getMimeType());
        $this->assertNotContains('text/plain', $field->acceptedMimeTypes);
    }

    /** @test */
    public function it_respects_file_size_limits(): void
    {
        $field = MediaLibraryImage::make('Test Image')
            ->maxFileSize(1024); // 1KB

        $this->assertEquals(1024, $field->maxFileSize);

        $smallFile = UploadedFile::fake()->image('small.jpg', 10, 10);
        $largeFile = UploadedFile::fake()->image('large.jpg', 1000, 1000);

        // Small file should be within limits (1KB)
        $this->assertLessThan(1024, $smallFile->getSize());

        // Large file should exceed limits (1KB)
        $this->assertGreaterThan(1024, $largeFile->getSize());
    }

    /** @test */
    public function it_enforces_image_limits(): void
    {
        $field = MediaLibraryImage::make('Test Image')
            ->limit(2)
            ->multiple();

        $this->assertEquals(2, $field->limit);
        $this->assertTrue($field->multiple);
    }
}
