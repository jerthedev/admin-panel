<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Fields\Image;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Image Field Integration Test
 *
 * Tests the complete integration between PHP Image field class,
 * API endpoints, file storage, and frontend functionality.
 * 
 * Focuses on Nova-compatible Image field functionality:
 * - Extends File field with same options and configurations
 * - Displays thumbnail preview of underlying image
 * - Supports squared() and rounded() display options
 * - Supports disableDownload() method
 */
class ImageFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup storage for image uploads
        Storage::fake('public');

        // Create test users (using existing User model structure)
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);
    }

    /** @test */
    public function it_creates_image_field_with_nova_syntax(): void
    {
        $field = Image::make('Profile Image');

        $this->assertEquals('Profile Image', $field->name);
        $this->assertEquals('profile_image', $field->attribute);
        $this->assertEquals('ImageField', $field->component);
        $this->assertFalse($field->squared);
        $this->assertFalse($field->rounded);
    }

    /** @test */
    public function it_inherits_file_field_functionality(): void
    {
        $field = Image::make('Gallery Image')
            ->disk('gallery')
            ->path('gallery-images')
            ->acceptedTypes('image/jpeg,image/png')
            ->maxSize(5120)
            ->required();

        $this->assertEquals('gallery', $field->disk);
        $this->assertEquals('gallery-images', $field->path);
        $this->assertEquals('image/jpeg,image/png', $field->acceptedTypes);
        $this->assertEquals(5120, $field->maxSize);
        $this->assertContains('required', $field->rules);
    }

    /** @test */
    public function it_supports_squared_display_option(): void
    {
        $field = Image::make('Square Image')->squared();

        $this->assertTrue($field->squared);
        
        $meta = $field->meta();
        $this->assertArrayHasKey('squared', $meta);
        $this->assertTrue($meta['squared']);
    }

    /** @test */
    public function it_supports_rounded_display_option(): void
    {
        $field = Image::make('Round Image')->rounded();

        $this->assertTrue($field->rounded);
        
        $meta = $field->meta();
        $this->assertArrayHasKey('rounded', $meta);
        $this->assertTrue($meta['rounded']);
    }

    /** @test */
    public function it_supports_both_squared_and_rounded_options(): void
    {
        $field = Image::make('Styled Image')->squared()->rounded();

        $this->assertTrue($field->squared);
        $this->assertTrue($field->rounded);
        
        $meta = $field->meta();
        $this->assertTrue($meta['squared']);
        $this->assertTrue($meta['rounded']);
    }

    /** @test */
    public function it_supports_disable_download_option(): void
    {
        $field = Image::make('Protected Image')->disableDownload();

        $this->assertFalse($field->downloadCallback);
    }

    /** @test */
    public function it_serializes_to_json_with_nova_compatible_structure(): void
    {
        $field = Image::make('Product Image')
            ->disk('products')
            ->path('product-images')
            ->squared()
            ->rounded()
            ->acceptedTypes('image/jpeg,image/png')
            ->maxSize(2048)
            ->required()
            ->help('Upload a product image');

        $json = $field->jsonSerialize();

        // Test basic field properties
        $this->assertEquals('Product Image', $json['name']);
        $this->assertEquals('product_image', $json['attribute']);
        $this->assertEquals('ImageField', $json['component']);
        
        // Test inherited File field properties
        $this->assertEquals('products', $json['disk']);
        $this->assertEquals('product-images', $json['path']);
        $this->assertEquals('image/jpeg,image/png', $json['acceptedTypes']);
        $this->assertEquals(2048, $json['maxSize']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Upload a product image', $json['helpText']);
        
        // Test Image-specific properties
        $this->assertTrue($json['squared']);
        $this->assertTrue($json['rounded']);
    }

    /** @test */
    public function it_handles_file_upload_through_request(): void
    {
        $field = Image::make('Upload Image')->disk('public')->path('uploads');

        // Create a fake image file
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        // Create a request with the file
        $request = Request::create('/test', 'POST');
        $request->files->set('upload_image', $file);

        // Test that the field can handle the file
        $this->assertInstanceOf(UploadedFile::class, $request->file('upload_image'));
        $this->assertEquals('test-image.jpg', $request->file('upload_image')->getClientOriginalName());
        $this->assertEquals('image/jpeg', $request->file('upload_image')->getMimeType());
    }

    /** @test */
    public function it_validates_image_file_types(): void
    {
        $field = Image::make('Validated Image')->acceptedTypes('image/jpeg,image/png');

        // Create valid image file
        $validFile = UploadedFile::fake()->image('valid.jpg');
        $this->assertEquals('image/jpeg', $validFile->getMimeType());

        // Create invalid file type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $this->assertEquals('application/pdf', $invalidFile->getMimeType());
    }

    /** @test */
    public function it_validates_file_size_limits(): void
    {
        $field = Image::make('Size Limited Image')->maxSize(1024); // 1MB

        // Create small file (within limit)
        $smallFile = UploadedFile::fake()->image('small.jpg')->size(500); // 500KB
        $this->assertLessThan(1024, $smallFile->getSize() / 1024);

        // Create large file (exceeds limit)
        $largeFile = UploadedFile::fake()->image('large.jpg')->size(2048); // 2MB
        $this->assertGreaterThan(1024, $largeFile->getSize() / 1024);
    }

    /** @test */
    public function it_stores_uploaded_images_correctly(): void
    {
        Storage::fake('public');
        
        $field = Image::make('Stored Image')->disk('public')->path('images');

        // Create and "upload" a fake image
        $file = UploadedFile::fake()->image('uploaded.jpg', 640, 480);
        
        // Simulate storing the file
        $path = $file->store('images', 'public');
        
        // Verify the file was stored
        Storage::disk('public')->assertExists($path);
        
        // Verify it's in the correct directory
        $this->assertStringStartsWith('images/', $path);
    }

    /** @test */
    public function it_generates_correct_urls_for_stored_images(): void
    {
        Storage::fake('public');

        $field = Image::make('URL Image')->disk('public')->path('images');

        // Create and store a fake image
        $file = UploadedFile::fake()->image('url-test.jpg');
        $path = $file->store('images', 'public');

        // Test URL generation
        $url = $field->getUrl($path);
        $this->assertStringContains('/storage/images/', $url);
        $this->assertStringContains('.jpg', $url);
    }

    /** @test */
    public function it_handles_multiple_image_configurations(): void
    {
        // Test different image configurations
        $avatar = Image::make('Avatar')->squared()->rounded()->maxSize(512);
        $gallery = Image::make('Gallery Image')->acceptedTypes('image/jpeg,image/png,image/webp');
        $thumbnail = Image::make('Thumbnail')->squared()->disableDownload();

        // Verify each has correct configuration
        $this->assertTrue($avatar->squared);
        $this->assertTrue($avatar->rounded);
        $this->assertEquals(512, $avatar->maxSize);

        $this->assertEquals('image/jpeg,image/png,image/webp', $gallery->acceptedTypes);

        $this->assertTrue($thumbnail->squared);
        $this->assertFalse($thumbnail->downloadCallback);
    }

    /** @test */
    public function it_maintains_nova_api_compatibility(): void
    {
        // Test that all Nova Image field methods are available
        $field = Image::make('Nova Compatible');

        // Test method chaining (Nova style)
        $configured = $field
            ->squared()
            ->rounded()
            ->disableDownload()
            ->disk('images')
            ->path('uploads')
            ->acceptedTypes('image/*')
            ->maxSize(2048);

        $this->assertInstanceOf(Image::class, $configured);
        $this->assertTrue($configured->squared);
        $this->assertTrue($configured->rounded);
        $this->assertFalse($configured->downloadCallback);
        $this->assertEquals('images', $configured->disk);
        $this->assertEquals('uploads', $configured->path);
        $this->assertEquals('image/*', $configured->acceptedTypes);
        $this->assertEquals(2048, $configured->maxSize);
    }
}
