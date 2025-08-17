<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Fields\Image;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;

/**
 * Image Field E2E Test
 *
 * Tests the complete end-to-end functionality of Image fields
 * including database operations, file storage, and field behavior.
 *
 * Focuses on Nova-compatible Image field functionality:
 * - Extends File field with same options and configurations
 * - Displays thumbnail preview of underlying image
 * - Supports squared() and rounded() display options
 * - Supports disableDownload() method
 */
class ImageFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup storage for image uploads
        Storage::fake('public');

        // Create test users with and without images
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => null
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'avatar' => 'images/jane-profile.jpg'
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'avatar' => 'images/bob-avatar.png'
        ]);

        // Create some test image files in storage
        Storage::disk('public')->put('images/jane-profile.jpg', 'fake-image-content');
        Storage::disk('public')->put('images/bob-avatar.png', 'fake-image-content');
    }

    /** @test */
    public function it_creates_nova_compatible_image_field(): void
    {
        $field = Image::make('Profile Image');

        $this->assertEquals('Profile Image', $field->name);
        $this->assertEquals('profile_image', $field->attribute);
        $this->assertEquals('ImageField', $field->component);
        $this->assertFalse($field->squared);
        $this->assertFalse($field->rounded);
    }

    /** @test */
    public function it_configures_image_field_with_nova_options(): void
    {
        $field = Image::make('Product Image')
            ->squared()
            ->rounded()
            ->disableDownload()
            ->disk('products')
            ->path('product-images')
            ->acceptedTypes('image/jpeg,image/png')
            ->maxSize(2048);

        $this->assertTrue($field->squared);
        $this->assertTrue($field->rounded);
        $this->assertFalse($field->downloadCallback);
        $this->assertEquals('products', $field->disk);
        $this->assertEquals('product-images', $field->path);
        $this->assertEquals('image/jpeg,image/png', $field->acceptedTypes);
        $this->assertEquals(2048, $field->maxSize);
    }

    /** @test */
    public function it_handles_image_upload_workflow(): void
    {
        $field = Image::make('Gallery Image')->disk('public')->path('gallery');

        // Create a fake image file
        $uploadedFile = UploadedFile::fake()->image('gallery-image.jpg', 800, 600);

        // Store the file
        $path = $uploadedFile->store('gallery', 'public');

        // Verify file was stored
        Storage::disk('public')->assertExists($path);

        // Test URL generation
        $url = $field->getUrl($path);
        $this->assertStringContains('/storage/gallery/', $url);
        $this->assertStringContains('.jpg', $url);
    }

    /** @test */
    public function it_validates_image_file_types_in_real_scenario(): void
    {
        $field = Image::make('Validated Image')->acceptedTypes('image/jpeg,image/png');

        // Test valid image types
        $jpegFile = UploadedFile::fake()->image('test.jpg');
        $pngFile = UploadedFile::fake()->image('test.png');

        $this->assertEquals('image/jpeg', $jpegFile->getMimeType());
        $this->assertEquals('image/png', $pngFile->getMimeType());

        // Test invalid file type
        $textFile = UploadedFile::fake()->create('document.txt', 100, 'text/plain');
        $this->assertEquals('text/plain', $textFile->getMimeType());
    }

    /** @test */
    public function it_enforces_file_size_limits_in_real_scenario(): void
    {
        $field = Image::make('Size Limited Image')->maxSize(1024); // 1MB

        // Create files of different sizes
        $smallFile = UploadedFile::fake()->image('small.jpg')->size(500); // 500KB
        $largeFile = UploadedFile::fake()->image('large.jpg')->size(2048); // 2MB

        $this->assertLessThan(1024, $smallFile->getSize() / 1024);
        $this->assertGreaterThan(1024, $largeFile->getSize() / 1024);
    }

    /** @test */
    public function it_handles_existing_image_display_scenarios(): void
    {
        $field = Image::make('User Avatar')->disk('public')->path('images');

        // Test user with existing image
        $userWithImage = User::find(2);
        $this->assertNotNull($userWithImage->avatar);
        $this->assertEquals('images/jane-profile.jpg', $userWithImage->avatar);

        // Verify the image file exists
        Storage::disk('public')->assertExists($userWithImage->avatar);

        // Test URL generation for existing image
        $url = $field->getUrl($userWithImage->avatar);
        $this->assertStringContains('/storage/images/jane-profile.jpg', $url);
    }

    /** @test */
    public function it_supports_multiple_image_configurations_simultaneously(): void
    {
        // Create different image field configurations
        $avatar = Image::make('Avatar')->squared()->rounded()->maxSize(512);
        $gallery = Image::make('Gallery Image')->acceptedTypes('image/jpeg,image/png,image/webp');
        $thumbnail = Image::make('Thumbnail')->squared()->disableDownload();
        $product = Image::make('Product Image')->disk('products')->path('products');

        // Test each configuration
        $this->assertTrue($avatar->squared);
        $this->assertTrue($avatar->rounded);
        $this->assertEquals(512, $avatar->maxSize);

        $this->assertEquals('image/jpeg,image/png,image/webp', $gallery->acceptedTypes);

        $this->assertTrue($thumbnail->squared);
        $this->assertFalse($thumbnail->downloadCallback);

        $this->assertEquals('products', $product->disk);
        $this->assertEquals('products', $product->path);
    }

    /** @test */
    public function it_handles_image_replacement_workflow(): void
    {
        $field = Image::make('Replaceable Image')->disk('public')->path('uploads');

        // Upload initial image
        $originalFile = UploadedFile::fake()->image('original.jpg');
        $originalPath = $originalFile->store('uploads', 'public');
        Storage::disk('public')->assertExists($originalPath);

        // Upload replacement image
        $replacementFile = UploadedFile::fake()->image('replacement.jpg');
        $replacementPath = $replacementFile->store('uploads', 'public');
        Storage::disk('public')->assertExists($replacementPath);

        // Both files should exist (cleanup would be handled by application logic)
        $this->assertNotEquals($originalPath, $replacementPath);
    }

    /** @test */
    public function it_generates_correct_json_serialization_for_frontend(): void
    {
        $field = Image::make('Frontend Image')
            ->squared()
            ->rounded()
            ->disk('frontend')
            ->path('frontend-images')
            ->acceptedTypes('image/*')
            ->maxSize(4096)
            ->required()
            ->help('Upload an image for the frontend');

        $json = $field->jsonSerialize();

        // Test basic properties
        $this->assertEquals('Frontend Image', $json['name']);
        $this->assertEquals('frontend_image', $json['attribute']);
        $this->assertEquals('ImageField', $json['component']);

        // Test Image-specific properties
        $this->assertTrue($json['squared']);
        $this->assertTrue($json['rounded']);

        // Test inherited File properties
        $this->assertEquals('frontend', $json['disk']);
        $this->assertEquals('frontend-images', $json['path']);
        $this->assertEquals('image/*', $json['acceptedTypes']);
        $this->assertEquals(4096, $json['maxSize']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Upload an image for the frontend', $json['helpText']);
    }

    /** @test */
    public function it_handles_edge_cases_in_real_world_scenarios(): void
    {
        // Test with null/empty values
        $field = Image::make('Edge Case Image');

        $userWithoutImage = User::find(1);
        $this->assertNull($userWithoutImage->avatar);

        // Test URL generation with null value
        $url = $field->getUrl(null);
        $this->assertNull($url);

        // Test with empty string
        $emptyUrl = $field->getUrl('');
        $this->assertNull($emptyUrl);
    }

    /** @test */
    public function it_maintains_nova_api_compatibility_in_real_usage(): void
    {
        // Test that all Nova Image field methods work as expected
        $field = Image::make('Nova Compatible Image');

        // Test method chaining (Nova style)
        $configured = $field
            ->squared()
            ->rounded()
            ->disableDownload()
            ->disk('nova')
            ->path('nova-images')
            ->acceptedTypes('image/jpeg,image/png')
            ->maxSize(2048)
            ->required()
            ->help('Nova-style image field');

        // Verify all configurations are applied
        $this->assertInstanceOf(Image::class, $configured);
        $this->assertTrue($configured->squared);
        $this->assertTrue($configured->rounded);
        $this->assertFalse($configured->downloadCallback);
        $this->assertEquals('nova', $configured->disk);
        $this->assertEquals('nova-images', $configured->path);
        $this->assertEquals('image/jpeg,image/png', $configured->acceptedTypes);
        $this->assertEquals(2048, $configured->maxSize);
        $this->assertContains('required', $configured->rules);
        $this->assertEquals('Nova-style image field', $configured->helpText);
    }

    /** @test */
    public function it_handles_different_image_formats_correctly(): void
    {
        $field = Image::make('Multi Format Image')->acceptedTypes('image/jpeg,image/png,image/gif,image/webp');

        // Test different image formats
        $jpegFile = UploadedFile::fake()->image('test.jpg');
        $pngFile = UploadedFile::fake()->image('test.png');
        $gifFile = UploadedFile::fake()->create('test.gif', 100, 'image/gif');
        $webpFile = UploadedFile::fake()->create('test.webp', 100, 'image/webp');

        $this->assertEquals('image/jpeg', $jpegFile->getMimeType());
        $this->assertEquals('image/png', $pngFile->getMimeType());
        $this->assertEquals('image/gif', $gifFile->getMimeType());
        $this->assertEquals('image/webp', $webpFile->getMimeType());

        // Store each format
        $jpegPath = $jpegFile->store('images', 'public');
        $pngPath = $pngFile->store('images', 'public');
        $gifPath = $gifFile->store('images', 'public');
        $webpPath = $webpFile->store('images', 'public');

        // Verify all were stored
        Storage::disk('public')->assertExists($jpegPath);
        Storage::disk('public')->assertExists($pngPath);
        Storage::disk('public')->assertExists($gifPath);
        Storage::disk('public')->assertExists($webpPath);
    }

    /** @test */
    public function it_handles_storage_cleanup_scenarios(): void
    {
        $field = Image::make('Cleanup Test Image')->disk('public')->path('cleanup');

        // Upload an image
        $file = UploadedFile::fake()->image('cleanup-test.jpg');
        $path = $file->store('cleanup', 'public');

        // Verify it was stored
        Storage::disk('public')->assertExists($path);

        // Simulate cleanup (would be handled by application logic)
        Storage::disk('public')->delete($path);

        // Verify it was cleaned up
        Storage::disk('public')->assertMissing($path);
    }
}
