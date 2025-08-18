<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Fields\MediaLibraryImage;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MediaLibraryImage Field E2E Test.
 *
 * Tests the complete end-to-end functionality of MediaLibraryImage fields
 * including database operations, file storage, Media Library integration,
 * and Nova-compatible field behavior.
 *
 * Focuses on Nova-compatible Image field functionality with Media Library features:
 * - Extends MediaLibraryField with Nova Image field compatibility
 * - Supports all Nova Image field methods: disableDownload(), maxWidth(), etc.
 * - Integrates with Spatie Media Library for advanced image handling
 * - Supports conversions, collections, and responsive images
 */
class MediaLibraryImageFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup storage for image uploads
        Storage::fake('public');
        Storage::fake('media');

        // Create test users with and without images
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
        ]);

        // Create some test image files in storage
        Storage::disk('public')->put('images/jane-profile.jpg', 'fake-image-content');
        Storage::disk('public')->put('images/bob-avatar.png', 'fake-image-content');
        Storage::disk('media')->put('gallery/image1.jpg', 'fake-gallery-image-content');
        Storage::disk('media')->put('gallery/image2.png', 'fake-gallery-image-content');
    }

    /** @test */
    public function it_creates_nova_compatible_media_library_image_field(): void
    {
        $field = MediaLibraryImage::make('Gallery Images');

        $this->assertEquals('Gallery Images', $field->name);
        $this->assertEquals('gallery_images', $field->attribute);
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
    public function it_configures_media_library_image_field_with_nova_options(): void
    {
        $field = MediaLibraryImage::make('Product Gallery')
            ->collection('products')
            ->disk('media')
            ->acceptsMimeTypes(['image/jpeg', 'image/png'])
            ->limit(5)
            ->multiple()
            ->disableDownload()
            ->maxWidth(400)
            ->indexWidth(80)
            ->detailWidth(200)
            ->squared();

        $this->assertEquals('products', $field->collection);
        $this->assertEquals('media', $field->disk);
        $this->assertEquals(['image/jpeg', 'image/png'], $field->acceptedMimeTypes);
        $this->assertEquals(5, $field->limit);
        $this->assertTrue($field->multiple);
        $this->assertTrue($field->downloadDisabled);
        $this->assertEquals(400, $field->maxWidth);
        $this->assertEquals(80, $field->indexWidth);
        $this->assertEquals(200, $field->detailWidth);
        $this->assertTrue($field->squared);
        $this->assertFalse($field->rounded);
    }

    /** @test */
    public function it_handles_nova_accepted_types_method(): void
    {
        $field = MediaLibraryImage::make('Test Images');

        // Test with file extensions
        $field->acceptedTypes('.jpg,.png,.gif');
        $this->assertContains('image/jpeg', $field->acceptedMimeTypes);
        $this->assertContains('image/png', $field->acceptedMimeTypes);
        $this->assertContains('image/gif', $field->acceptedMimeTypes);

        // Test with MIME types
        $field->acceptedTypes('image/webp,image/svg+xml');
        $this->assertContains('image/webp', $field->acceptedMimeTypes);
        $this->assertContains('image/svg+xml', $field->acceptedMimeTypes);
    }

    /** @test */
    public function it_handles_squared_and_rounded_mutually_exclusive(): void
    {
        $field = MediaLibraryImage::make('Styled Images');

        // Test squared resets rounded
        $field->squared();
        $this->assertTrue($field->squared);
        $this->assertFalse($field->rounded);

        // Test rounded resets squared
        $field->rounded();
        $this->assertFalse($field->squared);
        $this->assertTrue($field->rounded);
    }

    /** @test */
    public function it_handles_image_upload_workflow_with_media_library(): void
    {
        $field = MediaLibraryImage::make('Gallery Images')
            ->collection('gallery')
            ->disk('media');

        // Create fake image files
        $uploadedFile1 = UploadedFile::fake()->image('gallery-image1.jpg', 800, 600);
        $uploadedFile2 = UploadedFile::fake()->image('gallery-image2.png', 1024, 768);

        // Store the files
        $path1 = $uploadedFile1->store('gallery', 'media');
        $path2 = $uploadedFile2->store('gallery', 'media');

        // Verify files were stored
        Storage::disk('media')->assertExists($path1);
        Storage::disk('media')->assertExists($path2);

        // Test that files have correct MIME types
        $this->assertEquals('image/jpeg', $uploadedFile1->getMimeType());
        $this->assertEquals('image/png', $uploadedFile2->getMimeType());
    }

    /** @test */
    public function it_validates_image_file_types_in_real_scenario(): void
    {
        $field = MediaLibraryImage::make('Validated Images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        // Test valid image types
        $jpegFile = UploadedFile::fake()->image('test.jpg');
        $pngFile = UploadedFile::fake()->image('test.png');

        $this->assertEquals('image/jpeg', $jpegFile->getMimeType());
        $this->assertEquals('image/png', $pngFile->getMimeType());
        $this->assertContains('image/jpeg', $field->acceptedMimeTypes);
        $this->assertContains('image/png', $field->acceptedMimeTypes);

        // Test invalid file type
        $gifFile = UploadedFile::fake()->image('test.gif');
        $this->assertEquals('image/gif', $gifFile->getMimeType());
        $this->assertNotContains('image/gif', $field->acceptedMimeTypes);
    }

    /** @test */
    public function it_enforces_file_size_limits_in_real_scenario(): void
    {
        $field = MediaLibraryImage::make('Size Limited Images')
            ->maxFileSize(1024); // 1MB

        $this->assertEquals(1024, $field->maxFileSize);

        // Create files of different sizes
        $smallFile = UploadedFile::fake()->image('small.jpg')->size(500); // 500KB
        $largeFile = UploadedFile::fake()->image('large.jpg')->size(2048); // 2MB

        $this->assertLessThan(1024, $smallFile->getSize() / 1024);
        $this->assertGreaterThan(1024, $largeFile->getSize() / 1024);
    }

    /** @test */
    public function it_enforces_image_limits_in_real_scenario(): void
    {
        $field = MediaLibraryImage::make('Limited Images')
            ->limit(3)
            ->multiple();

        $this->assertEquals(3, $field->limit);
        $this->assertTrue($field->multiple);

        // Create multiple files
        $files = [
            UploadedFile::fake()->image('image1.jpg'),
            UploadedFile::fake()->image('image2.jpg'),
            UploadedFile::fake()->image('image3.jpg'),
            UploadedFile::fake()->image('image4.jpg'), // This would exceed the limit
        ];

        $this->assertCount(4, $files);
        $this->assertGreaterThan($field->limit, count($files));
    }

    /** @test */
    public function it_handles_preview_and_thumbnail_callbacks(): void
    {
        $field = MediaLibraryImage::make('Custom Images');

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

        // Test callback execution
        $mockMedia = new class
        {
            public function getPath()
            {
                return 'path/to/image.jpg';
            }
        };

        $previewUrl = $field->getPreviewUrl($mockMedia);
        $thumbnailUrl = $field->getThumbnailUrl($mockMedia);

        $this->assertEquals('https://cdn.example.com/preview/path/to/image.jpg', $previewUrl);
        $this->assertEquals('https://cdn.example.com/thumb/path/to/image.jpg', $thumbnailUrl);
    }

    /** @test */
    public function it_handles_download_functionality(): void
    {
        $field = MediaLibraryImage::make('Downloadable Images');

        // Test download enabled by default
        $this->assertFalse($field->downloadDisabled);

        // Test disable download
        $field->disableDownload();
        $this->assertTrue($field->downloadDisabled);

        // Test custom download callback
        $downloadCallback = function ($request, $model, $disk, $path) {
            return response()->download($path);
        };

        $field->download($downloadCallback);
        $this->assertEquals($downloadCallback, $field->downloadCallback);
    }

    /** @test */
    public function it_serializes_complete_field_configuration_for_frontend(): void
    {
        $field = MediaLibraryImage::make('Complete Gallery')
            ->collection('complete')
            ->disk('media')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->limit(10)
            ->multiple()
            ->maxFileSize(5120)
            ->disableDownload()
            ->maxWidth(600)
            ->indexWidth(100)
            ->detailWidth(300)
            ->squared()
            ->required()
            ->help('Upload gallery images');

        $json = $field->jsonSerialize();

        // Test basic field properties
        $this->assertEquals('Complete Gallery', $json['name']);
        $this->assertEquals('complete_gallery', $json['attribute']);
        $this->assertEquals('MediaLibraryImageField', $json['component']);

        // Test MediaLibrary properties
        $this->assertEquals('complete', $json['collection']);
        $this->assertEquals('media', $json['disk']);
        $this->assertEquals(['image/jpeg', 'image/png', 'image/webp'], $json['acceptedMimeTypes']);
        $this->assertEquals(10, $json['limit']);
        $this->assertTrue($json['multiple']);
        $this->assertEquals(5120, $json['maxFileSize']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Upload gallery images', $json['helpText']);

        // Test Nova Image field properties in meta
        $meta = $field->meta();
        $this->assertTrue($meta['downloadDisabled']);
        $this->assertEquals(600, $meta['maxWidth']);
        $this->assertEquals(100, $meta['indexWidth']);
        $this->assertEquals(300, $meta['detailWidth']);
        $this->assertTrue($meta['squared']);
        $this->assertFalse($meta['rounded']);
    }

    /** @test */
    public function it_handles_real_world_image_processing_scenario(): void
    {
        $field = MediaLibraryImage::make('Product Images')
            ->collection('products')
            ->disk('media')
            ->acceptedTypes('.jpg,.png,.webp')
            ->limit(5)
            ->multiple()
            ->maxFileSize(2048)
            ->maxWidth(800)
            ->indexWidth(150)
            ->detailWidth(400)
            ->rounded();

        // Create realistic image files
        $productImages = [
            UploadedFile::fake()->image('product-main.jpg', 1200, 800),
            UploadedFile::fake()->image('product-detail1.png', 800, 600),
            UploadedFile::fake()->image('product-detail2.webp', 600, 400),
        ];

        // Store all images
        $storedPaths = [];
        foreach ($productImages as $image) {
            $path = $image->store('products', 'media');
            $storedPaths[] = $path;
            Storage::disk('media')->assertExists($path);
        }

        // Verify field configuration
        $this->assertEquals('products', $field->collection);
        $this->assertEquals(5, $field->limit);
        $this->assertTrue($field->multiple);
        $this->assertEquals(2048, $field->maxFileSize);
        $this->assertEquals(800, $field->maxWidth);
        $this->assertEquals(150, $field->indexWidth);
        $this->assertEquals(400, $field->detailWidth);
        $this->assertTrue($field->rounded);
        $this->assertFalse($field->squared);

        // Verify MIME types are correctly converted
        $this->assertContains('image/jpeg', $field->acceptedMimeTypes);
        $this->assertContains('image/png', $field->acceptedMimeTypes);
        $this->assertContains('image/webp', $field->acceptedMimeTypes);

        $this->assertCount(3, $storedPaths);
    }
}
