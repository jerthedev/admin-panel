<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\MediaLibraryAvatar;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Config\Repository as Config;

class MediaLibraryAvatarFieldTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up Laravel container and config for the field
        $container = new Container();
        $config = new Config([
            'admin-panel.media_library.avatar_fallback_url' => '/images/default-avatar.png',
        ]);

        $container->instance('config', $config);
        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }
    public function test_media_library_avatar_field_creation(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture');

        $this->assertInstanceOf(MediaLibraryAvatar::class, $field);
        $this->assertEquals('Profile Picture', $field->name);
        $this->assertEquals('profile_picture', $field->attribute);
        $this->assertEquals('MediaLibraryAvatarField', $field->component);
    }

    public function test_media_library_avatar_field_creation_with_attribute(): void
    {
        $field = MediaLibraryAvatar::make('Avatar', 'user_avatar');

        $this->assertEquals('Avatar', $field->name);
        $this->assertEquals('user_avatar', $field->attribute);
    }

    public function test_media_library_avatar_field_default_properties(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        $this->assertEquals('avatars', $field->collection);
        $this->assertTrue($field->singleFile);
        $this->assertFalse($field->multiple);
        $this->assertTrue($field->enableCropping);
        $this->assertEquals('1:1', $field->cropAspectRatio);
        $this->assertEquals('/images/default-avatar.png', $field->fallbackUrl);
    }

    public function test_media_library_avatar_field_default_accepted_mime_types(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        $expectedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
        ];

        $this->assertEquals($expectedMimeTypes, $field->acceptedMimeTypes);
    }

    public function test_media_library_avatar_field_default_conversions(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        $expectedConversions = [
            'thumb' => ['width' => 64, 'height' => 64, 'fit' => 'crop'],
            'medium' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
            'large' => ['width' => 400, 'height' => 400, 'fit' => 'crop'],
        ];

        $this->assertEquals($expectedConversions, $field->conversions);
    }

    public function test_get_avatar_url_with_fallback(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        $url = $field->getAvatarUrl();

        $this->assertEquals('/images/default-avatar.png', $url);
    }

    public function test_get_avatar_url_with_media_object(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return 'https://example.com/avatar-medium.jpg';
            }
        };

        $url = $field->getAvatarUrl($mockMedia);

        $this->assertEquals('https://example.com/avatar-medium.jpg', $url);
    }

    public function test_get_avatar_url_with_field_value(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return 'https://example.com/field-avatar.jpg';
            }
        };

        $field->value = $mockMedia;

        $url = $field->getAvatarUrl();

        $this->assertEquals('https://example.com/field-avatar.jpg', $url);
    }

    public function test_get_avatar_url_with_custom_conversion(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return $conversion === 'large' ? 'https://example.com/avatar-large.jpg' : 'https://example.com/avatar.jpg';
            }
        };

        $url = $field->getAvatarUrl($mockMedia, 'large');

        $this->assertEquals('https://example.com/avatar-large.jpg', $url);
    }

    public function test_get_thumbnail_url(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return $conversion === 'thumb' ? 'https://example.com/avatar-thumb.jpg' : 'https://example.com/avatar.jpg';
            }
        };

        $url = $field->getThumbnailUrl($mockMedia);

        $this->assertEquals('https://example.com/avatar-thumb.jpg', $url);
    }

    public function test_get_thumbnail_url_with_fallback(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        $url = $field->getThumbnailUrl();

        $this->assertEquals('/images/default-avatar.png', $url);
    }

    public function test_get_large_url(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return $conversion === 'large' ? 'https://example.com/avatar-large.jpg' : 'https://example.com/avatar.jpg';
            }
        };

        $url = $field->getLargeUrl($mockMedia);

        $this->assertEquals('https://example.com/avatar-large.jpg', $url);
    }

    public function test_get_large_url_with_fallback(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        $url = $field->getLargeUrl();

        $this->assertEquals('/images/default-avatar.png', $url);
    }

    public function test_get_avatar_metadata_without_media(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        $metadata = $field->getAvatarMetadata();

        $expected = [
            'has_avatar' => false,
            'fallback_url' => '/images/default-avatar.png',
            'urls' => [
                'thumb' => '/images/default-avatar.png',
                'medium' => '/images/default-avatar.png',
                'large' => '/images/default-avatar.png',
            ],
        ];

        $this->assertEquals($expected, $metadata);
    }

    public function test_get_avatar_metadata_with_media(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        // Create a simple mock media object with all required properties
        $mockMedia = new class {
            public $name = 'profile.jpg';
            public $size = 1024000;
            public $mime_type = 'image/jpeg';
            public $created_at = '2023-01-01 12:00:00';
            public $custom_properties = ['width' => 400, 'height' => 400];

            public function getUrl($conversion = '') {
                return match($conversion) {
                    'thumb' => 'https://example.com/thumb.jpg',
                    'large' => 'https://example.com/large.jpg',
                    default => 'https://example.com/medium.jpg',
                };
            }
        };

        $metadata = $field->getAvatarMetadata($mockMedia);

        $this->assertTrue($metadata['has_avatar']);
        $this->assertEquals('profile.jpg', $metadata['name']);
        $this->assertEquals(1024000, $metadata['size']);
        $this->assertEquals('image/jpeg', $metadata['mime_type']);
        $this->assertEquals('2023-01-01 12:00:00', $metadata['created_at']);
        $this->assertEquals('1000 KB', $metadata['human_readable_size']);
        $this->assertEquals(400, $metadata['width']);
        $this->assertEquals(400, $metadata['height']);
        $this->assertEquals('https://example.com/thumb.jpg', $metadata['urls']['thumb']);
        $this->assertEquals('https://example.com/medium.jpg', $metadata['urls']['medium']);
        $this->assertEquals('https://example.com/large.jpg', $metadata['urls']['large']);
    }

    public function test_has_avatar_without_media(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        $this->assertFalse($field->hasAvatar());
    }

    public function test_has_avatar_with_media(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return 'https://example.com/avatar.jpg';
            }
        };

        $this->assertTrue($field->hasAvatar($mockMedia));
    }

    public function test_has_avatar_with_field_value(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return 'https://example.com/avatar.jpg';
            }
        };

        $field->value = $mockMedia;

        $this->assertTrue($field->hasAvatar());
    }

    public function test_get_avatar_sizes(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        // Test the getAvatarSizes method which should return fallback URLs
        $sizes = $field->getAvatarSizes();

        $expected = [
            'thumb' => [
                'width' => 64,
                'height' => 64,
                'url' => '/images/default-avatar.png',
            ],
            'medium' => [
                'width' => 150,
                'height' => 150,
                'url' => '/images/default-avatar.png',
            ],
            'large' => [
                'width' => 400,
                'height' => 400,
                'url' => '/images/default-avatar.png',
            ],
        ];

        $this->assertEquals($expected, $sizes);
    }

    public function test_format_file_size_zero_bytes(): void
    {
        $field = new MediaLibraryAvatar('Avatar');
        $reflection = new \ReflectionClass($field);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);

        $result = $method->invoke($field, 0);

        $this->assertEquals('0 B', $result);
    }

    public function test_format_file_size_various_sizes(): void
    {
        $field = new MediaLibraryAvatar('Avatar');
        $reflection = new \ReflectionClass($field);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);

        $this->assertEquals('512 B', $method->invoke($field, 512));
        $this->assertEquals('1 KB', $method->invoke($field, 1024));
        $this->assertEquals('1.5 KB', $method->invoke($field, 1536));
        $this->assertEquals('1 MB', $method->invoke($field, 1048576));
        $this->assertEquals('2.5 MB', $method->invoke($field, 2621440));
    }

    public function test_media_library_avatar_field_json_serialization(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture')
            ->required()
            ->help('Upload your profile picture');

        $json = $field->jsonSerialize();

        $this->assertEquals('Profile Picture', $json['name']);
        $this->assertEquals('profile_picture', $json['attribute']);
        $this->assertEquals('MediaLibraryAvatarField', $json['component']);
        $this->assertEquals('avatars', $json['collection']);
        $this->assertTrue($json['singleFile']);
        $this->assertFalse($json['multiple']);
        $this->assertTrue($json['enableCropping']);
        $this->assertEquals('1:1', $json['cropAspectRatio']);
        $this->assertEquals('/images/default-avatar.png', $json['fallbackUrl']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Upload your profile picture', $json['helpText']);
    }
}
