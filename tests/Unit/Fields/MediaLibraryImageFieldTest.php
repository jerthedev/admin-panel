<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\MediaLibraryImage;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Config\Repository as Config;

class MediaLibraryImageFieldTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up Laravel container and config for the field
        $container = new Container();
        $config = new Config([
            'admin-panel.media_library.accepted_mime_types.image' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/webp',
                'image/gif',
                'image/svg+xml',
            ],
            'admin-panel.media_library.file_size_limits.image' => 5120,
            'admin-panel.media_library.default_conversions' => [
                'thumb' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
                'medium' => ['width' => 500, 'height' => 500, 'fit' => 'contain'],
                'large' => ['width' => 1200, 'height' => 1200, 'quality' => 90],
            ],
            'admin-panel.media_library.responsive_images.enabled' => true,
        ]);

        $container->instance('config', $config);
        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_media_library_image_field_creation(): void
    {
        $field = MediaLibraryImage::make('Featured Image');

        $this->assertInstanceOf(MediaLibraryImage::class, $field);
        $this->assertEquals('Featured Image', $field->name);
        $this->assertEquals('featured_image', $field->attribute);
        $this->assertEquals('MediaLibraryImageField', $field->component);
    }

    public function test_media_library_image_field_creation_with_attribute(): void
    {
        $field = MediaLibraryImage::make('Gallery Image', 'gallery_photo');

        $this->assertEquals('Gallery Image', $field->name);
        $this->assertEquals('gallery_photo', $field->attribute);
    }

    public function test_media_library_image_field_default_properties(): void
    {
        $field = MediaLibraryImage::make('Image');

        $this->assertEquals('images', $field->collection);
        $this->assertEquals(5120, $field->maxFileSize);
        $this->assertTrue($field->responsiveImages);
        $this->assertTrue($field->enableCropping);
        $this->assertTrue($field->showImageDimensions);
    }

    public function test_media_library_image_field_default_accepted_mime_types(): void
    {
        $field = MediaLibraryImage::make('Image');

        $expectedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/svg+xml',
        ];

        $this->assertEquals($expectedMimeTypes, $field->acceptedMimeTypes);
    }

    public function test_media_library_image_field_default_conversions(): void
    {
        $field = MediaLibraryImage::make('Image');

        $expectedConversions = [
            'thumb' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
            'medium' => ['width' => 500, 'height' => 500, 'fit' => 'contain'],
            'large' => ['width' => 1200, 'height' => 1200, 'quality' => 90],
        ];

        $this->assertEquals($expectedConversions, $field->conversions);
    }

    public function test_get_thumbnail_url_with_fallback(): void
    {
        $field = MediaLibraryImage::make('Image');

        $url = $field->getThumbnailUrl();

        $this->assertNull($url);
    }

    public function test_get_thumbnail_url_with_media_object(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return $conversion === 'thumb' ? 'https://example.com/image-thumb.jpg' : 'https://example.com/image.jpg';
            }
        };

        $url = $field->getThumbnailUrl($mockMedia);

        $this->assertEquals('https://example.com/image-thumb.jpg', $url);
    }

    public function test_get_thumbnail_url_with_custom_conversion(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return $conversion === 'small' ? 'https://example.com/image-small.jpg' : 'https://example.com/image.jpg';
            }
        };

        $url = $field->getThumbnailUrl($mockMedia, 'small');

        $this->assertEquals('https://example.com/image-small.jpg', $url);
    }

    public function test_get_thumbnail_url_with_field_value(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return 'https://example.com/field-image-thumb.jpg';
            }
        };

        $field->value = $mockMedia;

        $url = $field->getThumbnailUrl();

        $this->assertEquals('https://example.com/field-image-thumb.jpg', $url);
    }

    public function test_get_preview_url_with_fallback(): void
    {
        $field = MediaLibraryImage::make('Image');

        $url = $field->getPreviewUrl();

        $this->assertNull($url);
    }

    public function test_get_preview_url_with_media_object(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return $conversion === 'medium' ? 'https://example.com/image-medium.jpg' : 'https://example.com/image.jpg';
            }
        };

        $url = $field->getPreviewUrl($mockMedia);

        $this->assertEquals('https://example.com/image-medium.jpg', $url);
    }

    public function test_get_preview_url_with_custom_conversion(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return $conversion === 'large' ? 'https://example.com/image-large.jpg' : 'https://example.com/image.jpg';
            }
        };

        $url = $field->getPreviewUrl($mockMedia, 'large');

        $this->assertEquals('https://example.com/image-large.jpg', $url);
    }

    public function test_get_preview_url_with_field_value(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return 'https://example.com/field-image-medium.jpg';
            }
        };

        $field->value = $mockMedia;

        $url = $field->getPreviewUrl();

        $this->assertEquals('https://example.com/field-image-medium.jpg', $url);
    }

    public function test_get_image_metadata_without_media(): void
    {
        $field = MediaLibraryImage::make('Image');

        $metadata = $field->getImageMetadata();

        $this->assertEquals([], $metadata);
    }

    public function test_get_image_metadata_with_media(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a simple mock media object with all required properties
        $mockMedia = new class {
            public $name = 'photo.jpg';
            public $size = 1024000;
            public $mime_type = 'image/jpeg';
            public $created_at = '2023-01-01 12:00:00';
            public $custom_properties = ['width' => 1920, 'height' => 1080];
        };

        $metadata = $field->getImageMetadata($mockMedia);

        $this->assertEquals('photo.jpg', $metadata['name']);
        $this->assertEquals(1024000, $metadata['size']);
        $this->assertEquals('image/jpeg', $metadata['mime_type']);
        $this->assertEquals('2023-01-01 12:00:00', $metadata['created_at']);
        $this->assertEquals('1000 KB', $metadata['human_readable_size']);
        $this->assertEquals(1920, $metadata['width']);
        $this->assertEquals(1080, $metadata['height']);
    }

    public function test_get_image_metadata_with_file_name_fallback(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a mock media object with file_name instead of name
        $mockMedia = new class {
            public $file_name = 'image.png';
            public $size = 512000;
            public $mime_type = 'image/png';
            public $created_at = '2023-01-01 12:00:00';
        };

        $metadata = $field->getImageMetadata($mockMedia);

        $this->assertEquals('image.png', $metadata['name']);
    }

    public function test_get_image_metadata_with_unknown_name_fallback(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a mock media object without name or file_name
        $mockMedia = new class {
            public $size = 256000;
            public $mime_type = 'image/webp';
            public $created_at = '2023-01-01 12:00:00';
        };

        $metadata = $field->getImageMetadata($mockMedia);

        $this->assertEquals('Unknown', $metadata['name']);
    }

    public function test_get_image_metadata_with_default_values(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a minimal mock media object
        $mockMedia = new \stdClass();

        $metadata = $field->getImageMetadata($mockMedia);

        $this->assertEquals('Unknown', $metadata['name']);
        $this->assertEquals(0, $metadata['size']);
        $this->assertEquals('image/jpeg', $metadata['mime_type']);
        $this->assertNull($metadata['created_at']);
        $this->assertEquals('0 B', $metadata['human_readable_size']);
    }

    public function test_get_image_metadata_with_field_value(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a simple mock media object
        $mockMedia = new class {
            public $name = 'field-image.gif';
            public $size = 128000;
            public $mime_type = 'image/gif';
            public $created_at = '2023-01-01 12:00:00';
            public $custom_properties = ['width' => 800, 'height' => 600];
        };

        $field->value = $mockMedia;

        $metadata = $field->getImageMetadata();

        $this->assertEquals('field-image.gif', $metadata['name']);
        $this->assertEquals(128000, $metadata['size']);
        $this->assertEquals('image/gif', $metadata['mime_type']);
        $this->assertEquals('2023-01-01 12:00:00', $metadata['created_at']);
        $this->assertEquals('125 KB', $metadata['human_readable_size']);
        $this->assertEquals(800, $metadata['width']);
        $this->assertEquals(600, $metadata['height']);
    }

    public function test_get_responsive_image_urls_without_media(): void
    {
        $field = MediaLibraryImage::make('Image');

        $urls = $field->getResponsiveImageUrls();

        $this->assertEquals([], $urls);
    }

    public function test_get_responsive_image_urls_with_media(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return match($conversion) {
                    'thumb' => 'https://example.com/image-thumb.jpg',
                    'medium' => 'https://example.com/image-medium.jpg',
                    'large' => 'https://example.com/image-large.jpg',
                    default => 'https://example.com/image.jpg',
                };
            }
        };

        $urls = $field->getResponsiveImageUrls($mockMedia);

        $expected = [
            150 => 'https://example.com/image-thumb.jpg',
            500 => 'https://example.com/image-medium.jpg',
            1200 => 'https://example.com/image-large.jpg',
        ];

        $this->assertEquals($expected, $urls);
    }

    public function test_get_responsive_image_urls_with_field_value(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return match($conversion) {
                    'thumb' => 'https://example.com/field-thumb.jpg',
                    'medium' => 'https://example.com/field-medium.jpg',
                    'large' => 'https://example.com/field-large.jpg',
                    default => 'https://example.com/field.jpg',
                };
            }
        };

        $field->value = $mockMedia;

        $urls = $field->getResponsiveImageUrls();

        $expected = [
            150 => 'https://example.com/field-thumb.jpg',
            500 => 'https://example.com/field-medium.jpg',
            1200 => 'https://example.com/field-large.jpg',
        ];

        $this->assertEquals($expected, $urls);
    }

    public function test_get_responsive_image_urls_with_media_without_geturl_method(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a simple object without getUrl method
        $mockMedia = new \stdClass();

        $urls = $field->getResponsiveImageUrls($mockMedia);

        $this->assertEquals([], $urls);
    }

    public function test_get_srcset_without_media(): void
    {
        $field = MediaLibraryImage::make('Image');

        $srcset = $field->getSrcSet();

        $this->assertEquals('', $srcset);
    }

    public function test_get_srcset_with_media(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return match($conversion) {
                    'thumb' => 'https://example.com/image-thumb.jpg',
                    'medium' => 'https://example.com/image-medium.jpg',
                    'large' => 'https://example.com/image-large.jpg',
                    default => 'https://example.com/image.jpg',
                };
            }
        };

        $srcset = $field->getSrcSet($mockMedia);

        $expected = 'https://example.com/image-thumb.jpg 150w, https://example.com/image-medium.jpg 500w, https://example.com/image-large.jpg 1200w';

        $this->assertEquals($expected, $srcset);
    }

    public function test_get_srcset_with_field_value(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return match($conversion) {
                    'thumb' => 'https://example.com/field-thumb.jpg',
                    'medium' => 'https://example.com/field-medium.jpg',
                    'large' => 'https://example.com/field-large.jpg',
                    default => 'https://example.com/field.jpg',
                };
            }
        };

        $field->value = $mockMedia;

        $srcset = $field->getSrcSet();

        $expected = 'https://example.com/field-thumb.jpg 150w, https://example.com/field-medium.jpg 500w, https://example.com/field-large.jpg 1200w';

        $this->assertEquals($expected, $srcset);
    }

    public function test_format_file_size_zero_bytes(): void
    {
        $field = new MediaLibraryImage('Image');
        $reflection = new \ReflectionClass($field);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);

        $result = $method->invoke($field, 0);

        $this->assertEquals('0 B', $result);
    }

    public function test_format_file_size_various_sizes(): void
    {
        $field = new MediaLibraryImage('Image');
        $reflection = new \ReflectionClass($field);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);

        $this->assertEquals('512 B', $method->invoke($field, 512));
        $this->assertEquals('1 KB', $method->invoke($field, 1024));
        $this->assertEquals('1.5 KB', $method->invoke($field, 1536));
        $this->assertEquals('1 MB', $method->invoke($field, 1048576));
        $this->assertEquals('2.5 MB', $method->invoke($field, 2621440));
        $this->assertEquals('1 GB', $method->invoke($field, 1073741824));
    }

    public function test_media_library_image_field_constructor_with_resolve_callback(): void
    {
        $callback = function ($resource, $attribute) {
            return $resource->{$attribute};
        };

        $field = new MediaLibraryImage('Image', null, $callback);

        $this->assertEquals($callback, $field->resolveCallback);
        $this->assertEquals('images', $field->collection);
        $this->assertEquals(5120, $field->maxFileSize);
        $this->assertTrue($field->responsiveImages);
        $this->assertTrue($field->enableCropping);
        $this->assertTrue($field->showImageDimensions);
    }

    public function test_media_library_image_field_json_serialization(): void
    {
        $field = MediaLibraryImage::make('Featured Image')
            ->required()
            ->help('Upload your featured image');

        $json = $field->jsonSerialize();

        $this->assertEquals('Featured Image', $json['name']);
        $this->assertEquals('featured_image', $json['attribute']);
        $this->assertEquals('MediaLibraryImageField', $json['component']);
        $this->assertEquals('images', $json['collection']);
        $this->assertEquals(5120, $json['maxFileSize']);
        $this->assertTrue($json['responsiveImages']);
        $this->assertTrue($json['enableCropping']);
        $this->assertTrue($json['showImageDimensions']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Upload your featured image', $json['helpText']);
    }

    public function test_media_library_image_field_comprehensive_method_coverage(): void
    {
        $field = MediaLibraryImage::make('Image');

        // Test that all public methods exist and can be called
        $this->assertTrue(method_exists($field, 'getThumbnailUrl'));
        $this->assertTrue(method_exists($field, 'getPreviewUrl'));
        $this->assertTrue(method_exists($field, 'getImageMetadata'));
        $this->assertTrue(method_exists($field, 'getResponsiveImageUrls'));
        $this->assertTrue(method_exists($field, 'getSrcSet'));

        // Test method calls return expected types
        $this->assertIsArray($field->getImageMetadata());
        $this->assertIsArray($field->getResponsiveImageUrls());
        $this->assertIsString($field->getSrcSet());
        $this->assertNull($field->getThumbnailUrl());
        $this->assertNull($field->getPreviewUrl());
    }
}
