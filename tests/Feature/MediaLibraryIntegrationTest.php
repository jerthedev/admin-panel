<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Feature;

use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Fields\MediaLibraryFile;
use JTD\AdminPanel\Fields\MediaLibraryImage;
use JTD\AdminPanel\Fields\MediaLibraryAvatar;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;



/**
 * Media Library Integration Tests
 *
 * Integration tests for Media Library field functionality including
 * field creation, configuration, and basic behavior testing.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MediaLibraryIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up fake storage
        Storage::fake('public');
    }

    public function test_media_library_file_field_creation_and_configuration(): void
    {
        $field = MediaLibraryFile::make('Document')
            ->collection('documents')
            ->disk('s3')
            ->acceptsMimeTypes(['application/pdf'])
            ->maxFileSize(5120)
            ->rules('required');

        // Test field properties
        $this->assertEquals('Document', $field->name);
        $this->assertEquals('document', $field->attribute);
        $this->assertEquals('MediaLibraryFileField', $field->component);
        $this->assertEquals('documents', $field->collection);
        $this->assertEquals('s3', $field->disk);
        $this->assertEquals(['application/pdf'], $field->acceptedMimeTypes);
        $this->assertEquals(5120, $field->maxFileSize);
        $this->assertEquals(['required'], $field->rules);

        // Test JSON serialization
        $json = $field->jsonSerialize();
        $this->assertArrayHasKey('collection', $json);
        $this->assertArrayHasKey('disk', $json);
        $this->assertArrayHasKey('acceptedMimeTypes', $json);
        $this->assertArrayHasKey('maxFileSize', $json);
    }

    public function test_media_library_image_field_creation_and_configuration(): void
    {
        $field = MediaLibraryImage::make('Featured Image')
            ->collection('images')
            ->conversions([
                'thumb' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
                'medium' => ['width' => 500, 'height' => 500, 'fit' => 'contain'],
            ])
            ->responsiveImages()
            ->enableCropping()
            ->limit(5)
            ->showImageDimensions();

        // Test field properties
        $this->assertEquals('Featured Image', $field->name);
        $this->assertEquals('featured_image', $field->attribute);
        $this->assertEquals('MediaLibraryImageField', $field->component);
        $this->assertEquals('images', $field->collection);
        $this->assertTrue($field->responsiveImages);
        $this->assertTrue($field->enableCropping);
        $this->assertEquals(5, $field->limit);
        $this->assertTrue($field->showImageDimensions);

        // Test conversions
        $this->assertArrayHasKey('thumb', $field->conversions);
        $this->assertArrayHasKey('medium', $field->conversions);
        $this->assertEquals(150, $field->conversions['thumb']['width']);
        $this->assertEquals('crop', $field->conversions['thumb']['fit']);
    }

    public function test_media_library_avatar_field_creation_and_configuration(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture')
            ->collection('avatars')
            ->cropAspectRatio('1:1')
            ->fallbackUrl('/images/default-avatar.png')
            ->fallbackPath(public_path('images/default-avatar.png'));

        // Test field properties
        $this->assertEquals('Profile Picture', $field->name);
        $this->assertEquals('profile_picture', $field->attribute);
        $this->assertEquals('MediaLibraryAvatarField', $field->component);
        $this->assertEquals('avatars', $field->collection);
        $this->assertTrue($field->singleFile);
        $this->assertFalse($field->multiple);
        $this->assertEquals('1:1', $field->cropAspectRatio);
        $this->assertEquals('/images/default-avatar.png', $field->fallbackUrl);

        // Test default conversions
        $this->assertArrayHasKey('thumb', $field->conversions);
        $this->assertArrayHasKey('medium', $field->conversions);
        $this->assertArrayHasKey('large', $field->conversions);

        // Test that all conversions use crop fit for avatars
        $this->assertEquals('crop', $field->conversions['thumb']['fit']);
        $this->assertEquals('crop', $field->conversions['medium']['fit']);
        $this->assertEquals('crop', $field->conversions['large']['fit']);
    }

    public function test_media_library_field_method_chaining(): void
    {
        $field = MediaLibraryImage::make('Gallery')
            ->collection('gallery')
            ->disk('s3')
            ->acceptsMimeTypes(['image/jpeg', 'image/png'])
            ->maxFileSize(10240)
            ->multiple()
            ->conversions(['thumb' => ['width' => 200]])
            ->responsiveImages()
            ->enableCropping()
            ->limit(5)
            ->showImageDimensions();

        // Test that all methods can be chained and work correctly
        $this->assertEquals('gallery', $field->collection);
        $this->assertEquals('s3', $field->disk);
        $this->assertEquals(['image/jpeg', 'image/png'], $field->acceptedMimeTypes);
        $this->assertEquals(10240, $field->maxFileSize);
        $this->assertTrue($field->multiple);
        $this->assertEquals(['thumb' => ['width' => 200]], $field->conversions);
        $this->assertTrue($field->responsiveImages);
        $this->assertTrue($field->enableCropping);
        $this->assertEquals(5, $field->limit);
        $this->assertTrue($field->showImageDimensions);
    }

    public function test_media_library_field_validation_rules(): void
    {
        $field = MediaLibraryFile::make('Document')
            ->acceptsMimeTypes(['application/pdf'])
            ->maxFileSize(5120)
            ->rules('required', 'file');

        // Test that validation rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('file', $field->rules);

        // Test that MIME types and file size are configured
        $this->assertEquals(['application/pdf'], $field->acceptedMimeTypes);
        $this->assertEquals(5120, $field->maxFileSize);
    }

    public function test_media_library_field_single_vs_multiple_configuration(): void
    {
        // Test single file field (default)
        $singleField = MediaLibraryFile::make('Document');
        $this->assertTrue($singleField->singleFile);
        $this->assertFalse($singleField->multiple);

        // Test multiple file field
        $multipleField = MediaLibraryImage::make('Gallery')->multiple();
        $this->assertFalse($multipleField->singleFile);
        $this->assertTrue($multipleField->multiple);

        // Test that setting singleFile updates multiple
        $field = MediaLibraryImage::make('Image')->multiple();
        $field->singleFile();
        $this->assertTrue($field->singleFile);
        $this->assertFalse($field->multiple);
    }

    public function test_media_library_configuration_integration(): void
    {
        // Test that fields use configuration defaults
        $fileField = MediaLibraryFile::make('Document');
        $this->assertEquals(config('admin-panel.media_library.default_disk'), $fileField->disk);
        $this->assertEquals(config('admin-panel.media_library.file_size_limits.file'), $fileField->maxFileSize);

        $imageField = MediaLibraryImage::make('Image');
        $this->assertEquals(config('admin-panel.media_library.default_conversions'), $imageField->conversions);
        $this->assertEquals(config('admin-panel.media_library.responsive_images.enabled'), $imageField->responsiveImages);

        $avatarField = MediaLibraryAvatar::make('Avatar');
        $this->assertEquals(config('admin-panel.media_library.avatar_conversions'), $avatarField->conversions);
        $this->assertTrue($avatarField->singleFile);
    }

    public function test_media_library_field_meta_information(): void
    {
        $field = MediaLibraryImage::make('Featured Image')
            ->collection('images')
            ->disk('s3')
            ->conversions(['thumb' => ['width' => 150]])
            ->responsiveImages()
            ->enableCropping();

        $meta = $field->meta();

        // Test that all media library specific meta information is included
        $this->assertArrayHasKey('collection', $meta);
        $this->assertArrayHasKey('disk', $meta);
        $this->assertArrayHasKey('conversions', $meta);
        $this->assertArrayHasKey('responsiveImages', $meta);
        $this->assertArrayHasKey('enableCropping', $meta);
        $this->assertArrayHasKey('acceptedMimeTypes', $meta);
        $this->assertArrayHasKey('maxFileSize', $meta);

        // Test meta values
        $this->assertEquals('images', $meta['collection']);
        $this->assertEquals('s3', $meta['disk']);
        $this->assertTrue($meta['responsiveImages']);
        $this->assertTrue($meta['enableCropping']);
    }

    public function test_media_library_field_component_names(): void
    {
        $fileField = MediaLibraryFile::make('Document');
        $imageField = MediaLibraryImage::make('Image');
        $avatarField = MediaLibraryAvatar::make('Avatar');

        // Test that each field has the correct Vue component name
        $this->assertEquals('MediaLibraryFileField', $fileField->component);
        $this->assertEquals('MediaLibraryImageField', $imageField->component);
        $this->assertEquals('MediaLibraryAvatarField', $avatarField->component);
    }
}
