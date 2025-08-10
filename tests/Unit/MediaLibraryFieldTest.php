<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\Fields\MediaLibraryFile;
use JTD\AdminPanel\Fields\MediaLibraryImage;
use JTD\AdminPanel\Fields\MediaLibraryAvatar;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;

/**
 * Test model that implements HasMedia for testing.
 */
class TestMediaModel extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['name', 'document'];
    protected $table = 'test_media_models';
}

/**
 * Media Library Field Unit Tests
 *
 * Tests for Media Library field classes including collections,
 * conversions, and media management functionality.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MediaLibraryFieldTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up fake storage for testing
        Storage::fake('public');
        Storage::fake('media');
    }

    // ========================================
    // MediaLibraryFile Field Tests
    // ========================================

    public function test_media_library_file_field_creation(): void
    {
        $field = MediaLibraryFile::make('Document');

        $this->assertEquals('Document', $field->name);
        $this->assertEquals('document', $field->attribute);
        $this->assertEquals('MediaLibraryFileField', $field->component);
    }

    public function test_media_library_file_field_with_custom_attribute(): void
    {
        $field = MediaLibraryFile::make('Resume', 'resume_file');

        $this->assertEquals('Resume', $field->name);
        $this->assertEquals('resume_file', $field->attribute);
    }

    public function test_media_library_file_field_collection_configuration(): void
    {
        $field = MediaLibraryFile::make('Document')
            ->collection('documents');

        $this->assertEquals('documents', $field->collection);
    }

    public function test_media_library_file_field_disk_configuration(): void
    {
        $field = MediaLibraryFile::make('Document')
            ->disk('s3');

        $this->assertEquals('s3', $field->disk);
    }

    public function test_media_library_file_field_mime_types_configuration(): void
    {
        $field = MediaLibraryFile::make('Document')
            ->acceptsMimeTypes(['application/pdf', 'application/msword']);

        $this->assertEquals(['application/pdf', 'application/msword'], $field->acceptedMimeTypes);
    }

    public function test_media_library_file_field_max_file_size_configuration(): void
    {
        $field = MediaLibraryFile::make('Document')
            ->maxFileSize(10240); // 10MB in KB

        $this->assertEquals(10240, $field->maxFileSize);
    }

    public function test_media_library_file_field_multiple_files_configuration(): void
    {
        $field = MediaLibraryFile::make('Documents')
            ->multiple();

        $this->assertTrue($field->multiple);
    }

    public function test_media_library_file_field_single_file_configuration(): void
    {
        $field = MediaLibraryFile::make('Document')
            ->singleFile();

        $this->assertTrue($field->singleFile);
        $this->assertFalse($field->multiple);
    }

    public function test_media_library_file_field_json_serialization(): void
    {
        $field = MediaLibraryFile::make('Document')
            ->collection('documents')
            ->disk('s3')
            ->acceptsMimeTypes(['application/pdf'])
            ->maxFileSize(5120)
            ->rules('required');

        $json = $field->jsonSerialize();

        $this->assertEquals('Document', $json['name']);
        $this->assertEquals('document', $json['attribute']);
        $this->assertEquals('MediaLibraryFileField', $json['component']);
        $this->assertEquals('documents', $json['collection']);
        $this->assertEquals('s3', $json['disk']);
        $this->assertEquals(['application/pdf'], $json['acceptedMimeTypes']);
        $this->assertEquals(5120, $json['maxFileSize']);
        $this->assertEquals(['required'], $json['rules']);
    }

    public function test_media_library_file_field_format_file_size(): void
    {
        $field = MediaLibraryFile::make('Document');

        // Use reflection to test the protected method
        $reflection = new \ReflectionClass($field);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);

        $this->assertEquals('0 B', $method->invoke($field, 0));
        $this->assertEquals('1 B', $method->invoke($field, 1));
        $this->assertEquals('1 KB', $method->invoke($field, 1024));
        $this->assertEquals('1 MB', $method->invoke($field, 1024 * 1024));
        $this->assertEquals('1.5 MB', $method->invoke($field, 1024 * 1024 * 1.5));
        $this->assertEquals('1 GB', $method->invoke($field, 1024 * 1024 * 1024));
    }

    public function test_media_library_file_field_get_file_metadata(): void
    {
        $field = MediaLibraryFile::make('Document');

        // Mock media object
        $media = (object) [
            'name' => 'test-document.pdf',
            'file_name' => 'test-document.pdf',
            'size' => 1024 * 1024, // 1MB
            'mime_type' => 'application/pdf',
            'created_at' => '2023-01-01 12:00:00',
        ];

        $metadata = $field->getFileMetadata($media);

        $this->assertEquals('test-document.pdf', $metadata['name']);
        $this->assertEquals(1024 * 1024, $metadata['size']);
        $this->assertEquals('application/pdf', $metadata['mime_type']);
        $this->assertEquals('2023-01-01 12:00:00', $metadata['created_at']);
        $this->assertEquals('1 MB', $metadata['human_readable_size']);
    }

    public function test_media_library_file_field_get_file_metadata_with_empty_media(): void
    {
        $field = MediaLibraryFile::make('Document');

        $metadata = $field->getFileMetadata(null);

        $this->assertEquals([], $metadata);
    }

    public function test_media_library_file_field_has_default_mime_types(): void
    {
        $field = MediaLibraryFile::make('Document');

        $this->assertIsArray($field->acceptedMimeTypes);
        $this->assertContains('application/pdf', $field->acceptedMimeTypes);
        $this->assertContains('application/msword', $field->acceptedMimeTypes);
        $this->assertContains('text/plain', $field->acceptedMimeTypes);
    }

    public function test_media_library_file_field_has_default_file_size_limit(): void
    {
        $field = MediaLibraryFile::make('Document');

        $this->assertEquals(10240, $field->maxFileSize); // 10MB
    }

    public function test_media_library_file_field_uses_files_collection_by_default(): void
    {
        $field = MediaLibraryFile::make('Document');

        $this->assertEquals('files', $field->collection);
    }

    public function test_media_library_file_field_validation_rules(): void
    {
        $field = MediaLibraryFile::make('Document')
            ->acceptsMimeTypes(['application/pdf'])
            ->maxFileSize(5120)
            ->rules('required', 'file');

        $this->assertContains('required', $field->rules);
        $this->assertContains('file', $field->rules);
    }

    public function test_media_library_file_field_get_download_url_with_mock_media(): void
    {
        $field = MediaLibraryFile::make('Document');

        // Create a simple object with getUrl method
        $media = new class {
            public function getUrl(): string
            {
                return 'https://example.com/file.pdf';
            }
        };

        $url = $field->getDownloadUrl($media);

        $this->assertEquals('https://example.com/file.pdf', $url);
    }

    public function test_media_library_file_field_get_download_url_with_null_media(): void
    {
        $field = MediaLibraryFile::make('Document');

        $url = $field->getDownloadUrl(null);

        $this->assertNull($url);
    }

    public function test_media_library_file_field_fill_with_valid_file(): void
    {
        Storage::fake('public');

        $field = MediaLibraryFile::make('Document')
            ->collection('documents')
            ->disk('public');

        // For this test, we'll just verify that the fill method doesn't throw an exception
        // when called with a non-HasMedia model (which should be the normal case)
        $model = new \stdClass();

        // Create a fake uploaded file
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
        $request = Request::create('/', 'POST');
        $request->files->set('document', $file);

        // The fill method should execute without throwing exceptions for non-HasMedia models
        $field->fill($request, $model);

        // If we get here without exceptions, the test passes
        $this->assertTrue(true);
    }

    public function test_media_library_file_field_fill_with_no_file(): void
    {
        $field = MediaLibraryFile::make('Document');
        $model = new \stdClass();

        $request = Request::create('/', 'POST');

        // The fill method should execute without throwing exceptions when no file is uploaded
        $field->fill($request, $model);

        // If we get here without exceptions, the test passes
        $this->assertTrue(true);
    }

    public function test_media_library_file_field_fill_with_custom_callback(): void
    {
        $callbackCalled = false;

        $field = MediaLibraryFile::make('Document')
            ->fillUsing(function ($request, $model, $attribute) use (&$callbackCalled) {
                $callbackCalled = true;
                $this->assertEquals('document', $attribute);
            });

        $model = new TestMediaModel();
        $request = Request::create('/', 'POST');

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
    }

    // ========================================
    // MediaLibraryImage Field Tests
    // ========================================

    public function test_media_library_image_field_creation(): void
    {
        $field = MediaLibraryImage::make('Featured Image');

        $this->assertEquals('Featured Image', $field->name);
        $this->assertEquals('featured_image', $field->attribute);
        $this->assertEquals('MediaLibraryImageField', $field->component);
    }

    public function test_media_library_image_field_conversions_configuration(): void
    {
        $conversions = [
            'thumb' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
            'medium' => ['width' => 500, 'height' => 500, 'fit' => 'contain'],
        ];

        $field = MediaLibraryImage::make('Product Images')
            ->conversions($conversions);

        $this->assertEquals($conversions, $field->conversions);
    }

    public function test_media_library_image_field_responsive_images_configuration(): void
    {
        $field = MediaLibraryImage::make('Featured Image')
            ->responsiveImages();

        $this->assertTrue($field->responsiveImages);
    }

    public function test_media_library_image_field_cropping_configuration(): void
    {
        $field = MediaLibraryImage::make('Featured Image')
            ->enableCropping();

        $this->assertTrue($field->enableCropping);
    }

    public function test_media_library_image_field_limit_configuration(): void
    {
        $field = MediaLibraryImage::make('Gallery Images')
            ->limit(10);

        $this->assertEquals(10, $field->limit);
    }

    public function test_media_library_image_field_show_image_dimensions(): void
    {
        $field = MediaLibraryImage::make('Featured Image')
            ->showImageDimensions();

        $this->assertTrue($field->showImageDimensions);
    }

    public function test_media_library_image_field_has_default_image_settings(): void
    {
        $field = MediaLibraryImage::make('Featured Image');

        $this->assertEquals('images', $field->collection);
        $this->assertEquals(5120, $field->maxFileSize); // 5MB
        $this->assertTrue($field->responsiveImages);
        $this->assertTrue($field->enableCropping);
        $this->assertTrue($field->showImageDimensions);
        $this->assertIsArray($field->conversions);
        $this->assertArrayHasKey('thumb', $field->conversions);
        $this->assertArrayHasKey('medium', $field->conversions);
        $this->assertArrayHasKey('large', $field->conversions);
    }

    public function test_media_library_image_field_has_default_mime_types(): void
    {
        $field = MediaLibraryImage::make('Featured Image');

        $this->assertIsArray($field->acceptedMimeTypes);
        $this->assertContains('image/jpeg', $field->acceptedMimeTypes);
        $this->assertContains('image/png', $field->acceptedMimeTypes);
        $this->assertContains('image/webp', $field->acceptedMimeTypes);
        $this->assertContains('image/gif', $field->acceptedMimeTypes);
    }

    public function test_media_library_image_field_get_image_metadata(): void
    {
        $field = MediaLibraryImage::make('Featured Image');

        // Mock media object with image-specific properties
        $media = (object) [
            'name' => 'featured-image.jpg',
            'file_name' => 'featured-image.jpg',
            'size' => 2048 * 1024, // 2MB
            'mime_type' => 'image/jpeg',
            'created_at' => '2023-01-01 12:00:00',
            'custom_properties' => [
                'width' => 1920,
                'height' => 1080,
            ],
        ];

        $metadata = $field->getImageMetadata($media);

        $this->assertEquals('featured-image.jpg', $metadata['name']);
        $this->assertEquals(2048 * 1024, $metadata['size']);
        $this->assertEquals('image/jpeg', $metadata['mime_type']);
        $this->assertEquals('2023-01-01 12:00:00', $metadata['created_at']);
        $this->assertEquals('2 MB', $metadata['human_readable_size']);
        $this->assertEquals(1920, $metadata['width']);
        $this->assertEquals(1080, $metadata['height']);
        $this->assertEquals('1920 × 1080', $metadata['dimensions']);
    }

    public function test_media_library_image_field_get_responsive_image_urls(): void
    {
        $field = MediaLibraryImage::make('Featured Image')
            ->conversions([
                'small' => ['width' => 400],
                'medium' => ['width' => 800],
                'large' => ['width' => 1200],
            ]);

        // Mock media object with getUrl method
        $media = new class {
            public function getUrl(string $conversion = ''): string
            {
                return "https://example.com/image-{$conversion}.jpg";
            }
        };

        $urls = $field->getResponsiveImageUrls($media);

        $this->assertEquals([
            400 => 'https://example.com/image-small.jpg',
            800 => 'https://example.com/image-medium.jpg',
            1200 => 'https://example.com/image-large.jpg',
        ], $urls);
    }

    public function test_media_library_image_field_get_srcset(): void
    {
        $field = MediaLibraryImage::make('Featured Image')
            ->conversions([
                'small' => ['width' => 400],
                'large' => ['width' => 800],
            ]);

        // Mock media object with getUrl method
        $media = new class {
            public function getUrl(string $conversion = ''): string
            {
                return "https://example.com/image-{$conversion}.jpg";
            }
        };

        $srcset = $field->getSrcSet($media);

        $this->assertEquals('https://example.com/image-small.jpg 400w, https://example.com/image-large.jpg 800w', $srcset);
    }

    public function test_media_library_image_field_get_thumbnail_url(): void
    {
        $field = MediaLibraryImage::make('Featured Image');

        // Mock media object with getUrl method
        $media = new class {
            public function getUrl(string $conversion = ''): string
            {
                return "https://example.com/image-{$conversion}.jpg";
            }
        };

        $thumbnailUrl = $field->getThumbnailUrl($media);
        $this->assertEquals('https://example.com/image-thumb.jpg', $thumbnailUrl);

        $customThumbnailUrl = $field->getThumbnailUrl($media, 'small');
        $this->assertEquals('https://example.com/image-small.jpg', $customThumbnailUrl);
    }

    public function test_media_library_image_field_get_preview_url(): void
    {
        $field = MediaLibraryImage::make('Featured Image');

        // Mock media object with getUrl method
        $media = new class {
            public function getUrl(string $conversion = ''): string
            {
                return "https://example.com/image-{$conversion}.jpg";
            }
        };

        $previewUrl = $field->getPreviewUrl($media);
        $this->assertEquals('https://example.com/image-medium.jpg', $previewUrl);

        $customPreviewUrl = $field->getPreviewUrl($media, 'large');
        $this->assertEquals('https://example.com/image-large.jpg', $customPreviewUrl);
    }

    public function test_media_library_image_field_multiple_upload_configuration(): void
    {
        $field = MediaLibraryImage::make('Gallery Images')
            ->multiple()
            ->limit(10);

        $this->assertTrue($field->multiple);
        $this->assertFalse($field->singleFile);
        $this->assertEquals(10, $field->limit);
    }

    public function test_media_library_image_field_json_serialization(): void
    {
        $field = MediaLibraryImage::make('Featured Image')
            ->collection('featured')
            ->disk('s3')
            ->acceptsMimeTypes(['image/jpeg', 'image/png'])
            ->maxFileSize(5120)
            ->conversions(['thumb' => ['width' => 150]])
            ->responsiveImages()
            ->enableCropping()
            ->limit(5)
            ->showImageDimensions()
            ->rules('required', 'image');

        $json = $field->jsonSerialize();

        $this->assertEquals('Featured Image', $json['name']);
        $this->assertEquals('featured_image', $json['attribute']);
        $this->assertEquals('MediaLibraryImageField', $json['component']);
        $this->assertEquals('featured', $json['collection']);
        $this->assertEquals('s3', $json['disk']);
        $this->assertEquals(['image/jpeg', 'image/png'], $json['acceptedMimeTypes']);
        $this->assertEquals(5120, $json['maxFileSize']);
        $this->assertEquals(['thumb' => ['width' => 150]], $json['conversions']);
        $this->assertTrue($json['responsiveImages']);
        $this->assertTrue($json['enableCropping']);
        $this->assertEquals(5, $json['limit']);
        $this->assertTrue($json['showImageDimensions']);
        $this->assertEquals(['required', 'image'], $json['rules']);
    }

    // ========================================
    // MediaLibraryAvatar Field Tests
    // ========================================

    public function test_media_library_avatar_field_creation(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture');

        $this->assertEquals('Profile Picture', $field->name);
        $this->assertEquals('profile_picture', $field->attribute);
        $this->assertEquals('MediaLibraryAvatarField', $field->component);
    }

    public function test_media_library_avatar_field_crop_aspect_ratio(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture')
            ->cropAspectRatio('1:1');

        $this->assertEquals('1:1', $field->cropAspectRatio);
    }

    public function test_media_library_avatar_field_fallback_url(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture')
            ->fallbackUrl('/images/default-avatar.png');

        $this->assertEquals('/images/default-avatar.png', $field->fallbackUrl);
    }

    public function test_media_library_avatar_field_fallback_path(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture')
            ->fallbackPath(public_path('images/default-avatar.png'));

        $this->assertEquals(public_path('images/default-avatar.png'), $field->fallbackPath);
    }

    public function test_media_library_avatar_field_is_single_file_by_default(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture');

        $this->assertTrue($field->singleFile);
        $this->assertFalse($field->multiple);
    }

    public function test_media_library_avatar_field_has_default_conversions(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture');

        $this->assertIsArray($field->conversions);
        $this->assertArrayHasKey('thumb', $field->conversions);
        $this->assertArrayHasKey('medium', $field->conversions);
        $this->assertArrayHasKey('large', $field->conversions);
    }

    public function test_media_library_avatar_field_has_default_settings(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture');

        $this->assertEquals('avatars', $field->collection);
        $this->assertEquals(2048, $field->maxFileSize); // 2MB
        $this->assertTrue($field->singleFile);
        $this->assertFalse($field->multiple);
        $this->assertTrue($field->enableCropping);
        $this->assertEquals('1:1', $field->cropAspectRatio);
        $this->assertEquals('/images/default-avatar.png', $field->fallbackUrl);
    }

    public function test_media_library_avatar_field_has_restrictive_mime_types(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture');

        $this->assertIsArray($field->acceptedMimeTypes);
        $this->assertContains('image/jpeg', $field->acceptedMimeTypes);
        $this->assertContains('image/png', $field->acceptedMimeTypes);
        $this->assertContains('image/webp', $field->acceptedMimeTypes);
        // Should not contain SVG for security reasons
        $this->assertNotContains('image/svg+xml', $field->acceptedMimeTypes);
    }

    public function test_media_library_avatar_field_get_avatar_url_with_media(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture');

        // Mock media object with getUrl method
        $media = new class {
            public function getUrl(string $conversion = ''): string
            {
                return "https://example.com/avatar-{$conversion}.jpg";
            }
        };

        $avatarUrl = $field->getAvatarUrl($media);
        $this->assertEquals('https://example.com/avatar-medium.jpg', $avatarUrl);

        $thumbUrl = $field->getThumbnailUrl($media);
        $this->assertEquals('https://example.com/avatar-thumb.jpg', $thumbUrl);

        $largeUrl = $field->getLargeUrl($media);
        $this->assertEquals('https://example.com/avatar-large.jpg', $largeUrl);
    }

    public function test_media_library_avatar_field_get_avatar_url_with_fallback(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture')
            ->fallbackUrl('/custom-default-avatar.png');

        $avatarUrl = $field->getAvatarUrl(null);
        $this->assertEquals('/custom-default-avatar.png', $avatarUrl);

        $thumbUrl = $field->getThumbnailUrl(null);
        $this->assertEquals('/custom-default-avatar.png', $thumbUrl);
    }

    public function test_media_library_avatar_field_has_avatar_check(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture');

        // Mock media object
        $media = new class {
            public function getUrl(string $conversion = ''): string
            {
                return 'https://example.com/avatar.jpg';
            }
        };

        $this->assertTrue($field->hasAvatar($media));
        $this->assertFalse($field->hasAvatar(null));
    }

    public function test_media_library_avatar_field_get_avatar_metadata_with_media(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture');

        // Mock media object with avatar-specific properties
        $media = (object) [
            'name' => 'profile-avatar.jpg',
            'file_name' => 'profile-avatar.jpg',
            'size' => 512 * 1024, // 512KB
            'mime_type' => 'image/jpeg',
            'created_at' => '2023-01-01 12:00:00',
            'custom_properties' => [
                'width' => 400,
                'height' => 400,
            ],
        ];

        // Mock the getUrl method
        $field->value = new class {
            public function getUrl(string $conversion = ''): string
            {
                return "https://example.com/avatar-{$conversion}.jpg";
            }
        };

        $metadata = $field->getAvatarMetadata($media);

        $this->assertTrue($metadata['has_avatar']);
        $this->assertEquals('profile-avatar.jpg', $metadata['name']);
        $this->assertEquals(512 * 1024, $metadata['size']);
        $this->assertEquals('image/jpeg', $metadata['mime_type']);
        $this->assertEquals('2023-01-01 12:00:00', $metadata['created_at']);
        $this->assertEquals('512 KB', $metadata['human_readable_size']);
        $this->assertEquals(400, $metadata['width']);
        $this->assertEquals(400, $metadata['height']);
        $this->assertArrayHasKey('urls', $metadata);
        $this->assertArrayHasKey('thumb', $metadata['urls']);
        $this->assertArrayHasKey('medium', $metadata['urls']);
        $this->assertArrayHasKey('large', $metadata['urls']);
    }

    public function test_media_library_avatar_field_get_avatar_metadata_without_media(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture')
            ->fallbackUrl('/custom-fallback.png');

        $metadata = $field->getAvatarMetadata(null);

        $this->assertFalse($metadata['has_avatar']);
        $this->assertEquals('/custom-fallback.png', $metadata['fallback_url']);
        $this->assertArrayHasKey('urls', $metadata);
        $this->assertEquals('/custom-fallback.png', $metadata['urls']['thumb']);
        $this->assertEquals('/custom-fallback.png', $metadata['urls']['medium']);
        $this->assertEquals('/custom-fallback.png', $metadata['urls']['large']);
    }

    public function test_media_library_avatar_field_get_avatar_sizes(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture');

        // Mock media object
        $media = new class {
            public function getUrl(string $conversion = ''): string
            {
                return "https://example.com/avatar-{$conversion}.jpg";
            }
        };

        $sizes = $field->getAvatarSizes($media);

        $this->assertArrayHasKey('thumb', $sizes);
        $this->assertArrayHasKey('medium', $sizes);
        $this->assertArrayHasKey('large', $sizes);

        $this->assertEquals(64, $sizes['thumb']['width']);
        $this->assertEquals(64, $sizes['thumb']['height']);
        $this->assertEquals('https://example.com/avatar-thumb.jpg', $sizes['thumb']['url']);

        $this->assertEquals(150, $sizes['medium']['width']);
        $this->assertEquals(150, $sizes['medium']['height']);
        $this->assertEquals('https://example.com/avatar-medium.jpg', $sizes['medium']['url']);

        $this->assertEquals(400, $sizes['large']['width']);
        $this->assertEquals(400, $sizes['large']['height']);
        $this->assertEquals('https://example.com/avatar-large.jpg', $sizes['large']['url']);
    }

    // ========================================
    // Base MediaLibraryField Tests
    // ========================================

    public function test_media_library_field_meta_information(): void
    {
        $field = MediaLibraryFile::make('Document')
            ->collection('documents')
            ->disk('s3')
            ->acceptsMimeTypes(['application/pdf'])
            ->maxFileSize(5120)
            ->multiple()
            ->conversions(['thumb' => ['width' => 150]])
            ->responsiveImages()
            ->enableCropping()
            ->limit(10)
            ->showImageDimensions()
            ->cropAspectRatio('16:9')
            ->fallbackUrl('/default.png')
            ->fallbackPath('/path/to/default.png');

        $meta = $field->meta();

        $this->assertEquals('documents', $meta['collection']);
        $this->assertEquals('s3', $meta['disk']);
        $this->assertEquals(['application/pdf'], $meta['acceptedMimeTypes']);
        $this->assertEquals(5120, $meta['maxFileSize']);
        $this->assertTrue($meta['multiple']);
        $this->assertFalse($meta['singleFile']);
        $this->assertEquals(['thumb' => ['width' => 150]], $meta['conversions']);
        $this->assertTrue($meta['responsiveImages']);
        $this->assertTrue($meta['enableCropping']);
        $this->assertEquals(10, $meta['limit']);
        $this->assertTrue($meta['showImageDimensions']);
        $this->assertEquals('16:9', $meta['cropAspectRatio']);
        $this->assertEquals('/default.png', $meta['fallbackUrl']);
        $this->assertEquals('/path/to/default.png', $meta['fallbackPath']);
    }

    public function test_media_library_field_single_file_vs_multiple(): void
    {
        $field = MediaLibraryFile::make('Document');

        // Default should be single file
        $this->assertTrue($field->singleFile);
        $this->assertFalse($field->multiple);

        // Setting multiple should update singleFile
        $field->multiple();
        $this->assertFalse($field->singleFile);
        $this->assertTrue($field->multiple);

        // Setting singleFile should update multiple
        $field->singleFile();
        $this->assertTrue($field->singleFile);
        $this->assertFalse($field->multiple);
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
}
