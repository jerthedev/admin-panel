<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use Illuminate\Container\Container;
use Illuminate\Config\Repository as Config;
use JTD\AdminPanel\Fields\MediaLibraryAudioField;
use JTD\AdminPanel\Fields\MediaLibraryField;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MediaLibraryAudioField Unit Tests
 *
 * Tests the MediaLibraryAudioField functionality including Nova Audio API compatibility,
 * Media Library integration, audio-specific features, and comprehensive coverage.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MediaLibraryAudioFieldTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up Laravel container and config for the field
        $container = new Container();
        $config = new Config([
            'admin-panel.media_library.accepted_mime_types.audio' => [
                'audio/mpeg',
                'audio/wav',
                'audio/ogg',
                'audio/mp4',
                'audio/aac',
                'audio/flac',
                'audio/x-wav',
                'audio/x-m4a',
            ],
            'admin-panel.media_library.file_size_limits.audio' => 51200,
            'admin-panel.media_library.default_disk' => 'public',
        ]);

        $container->instance('config', $config);
        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_media_library_audio_field_creation(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        $this->assertInstanceOf(MediaLibraryAudioField::class, $field);
        $this->assertEquals('Theme Song', $field->name);
        $this->assertEquals('theme_song', $field->attribute);
        $this->assertEquals('MediaLibraryAudioField', $field->component);
    }

    public function test_media_library_audio_field_creation_with_attribute(): void
    {
        $field = MediaLibraryAudioField::make('Background Music', 'bg_music');

        $this->assertEquals('Background Music', $field->name);
        $this->assertEquals('bg_music', $field->attribute);
    }

    public function test_media_library_audio_field_extends_media_library_field(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        $this->assertInstanceOf(MediaLibraryField::class, $field);
        $this->assertInstanceOf(MediaLibraryAudioField::class, $field);
    }

    public function test_media_library_audio_field_default_properties(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        $this->assertEquals('audio', $field->collection);
        $this->assertEquals(51200, $field->maxFileSize);
        $this->assertTrue($field->singleFile);
        $this->assertEquals('public', $field->disk);
        $this->assertEquals(MediaLibraryAudioField::PRELOAD_METADATA, $field->getPreloadAttribute());
        $this->assertFalse($field->downloadsAreDisabled());
    }

    public function test_media_library_audio_field_default_accepted_mime_types(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        $expectedMimeTypes = [
            'audio/mpeg',
            'audio/wav',
            'audio/ogg',
            'audio/mp4',
            'audio/aac',
            'audio/flac',
            'audio/x-wav',
            'audio/x-m4a',
        ];

        $this->assertEquals($expectedMimeTypes, $field->acceptedMimeTypes);
    }

    public function test_audio_field_preload_constants(): void
    {
        $this->assertEquals('none', MediaLibraryAudioField::PRELOAD_NONE);
        $this->assertEquals('metadata', MediaLibraryAudioField::PRELOAD_METADATA);
        $this->assertEquals('auto', MediaLibraryAudioField::PRELOAD_AUTO);
    }

    public function test_disable_download_method(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        $this->assertFalse($field->downloadsAreDisabled());

        $result = $field->disableDownload();

        $this->assertInstanceOf(MediaLibraryAudioField::class, $result);
        $this->assertTrue($field->downloadsAreDisabled());
    }

    public function test_preload_method(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        $this->assertEquals(MediaLibraryAudioField::PRELOAD_METADATA, $field->getPreloadAttribute());

        $result = $field->preload(MediaLibraryAudioField::PRELOAD_AUTO);

        $this->assertInstanceOf(MediaLibraryAudioField::class, $result);
        $this->assertEquals(MediaLibraryAudioField::PRELOAD_AUTO, $field->getPreloadAttribute());

        $field->preload(MediaLibraryAudioField::PRELOAD_NONE);
        $this->assertEquals(MediaLibraryAudioField::PRELOAD_NONE, $field->getPreloadAttribute());
    }

    public function test_get_audio_url_with_no_media(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        $url = $field->getAudioUrl();

        $this->assertNull($url);
    }

    public function test_get_audio_url_with_media_object(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return 'https://example.com/audio/song.mp3';
            }
        };

        $url = $field->getAudioUrl($mockMedia);

        $this->assertEquals('https://example.com/audio/song.mp3', $url);
    }

    public function test_get_audio_url_with_field_value(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return 'https://example.com/audio/field-song.mp3';
            }
        };

        $field->value = $mockMedia;

        $url = $field->getAudioUrl();

        $this->assertEquals('https://example.com/audio/field-song.mp3', $url);
    }

    public function test_get_audio_url_with_media_without_geturl_method(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Create a simple object without getUrl method
        $mockMedia = new \stdClass();

        $url = $field->getAudioUrl($mockMedia);

        $this->assertNull($url);
    }

    public function test_get_audio_metadata_with_no_media(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        $metadata = $field->getAudioMetadata();

        $this->assertEquals([], $metadata);
    }

    public function test_get_audio_metadata_with_basic_media(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Create a simple mock media object with basic properties
        $mockMedia = new class {
            public $name = 'theme-song.mp3';
            public $size = 5242880; // 5MB
            public $mime_type = 'audio/mpeg';
            public $created_at = '2023-01-01 12:00:00';
        };

        $metadata = $field->getAudioMetadata($mockMedia);

        $this->assertEquals('theme-song.mp3', $metadata['name']);
        $this->assertEquals(5242880, $metadata['size']);
        $this->assertEquals('audio/mpeg', $metadata['mime_type']);
        $this->assertEquals('2023-01-01 12:00:00', $metadata['created_at']);
        $this->assertEquals('5 MB', $metadata['human_readable_size']);
    }

    public function test_get_audio_metadata_with_audio_properties(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Create a mock media object with audio-specific properties
        $mockMedia = new class {
            public $name = 'podcast.mp3';
            public $size = 10485760; // 10MB
            public $mime_type = 'audio/mpeg';
            public $created_at = '2023-01-01 12:00:00';
            public $custom_properties = [
                'duration' => 180.5,
                'bitrate' => 320,
                'sample_rate' => 44100,
            ];
        };

        $metadata = $field->getAudioMetadata($mockMedia);

        $this->assertEquals('podcast.mp3', $metadata['name']);
        $this->assertEquals(10485760, $metadata['size']);
        $this->assertEquals('audio/mpeg', $metadata['mime_type']);
        $this->assertEquals('2023-01-01 12:00:00', $metadata['created_at']);
        $this->assertEquals('10 MB', $metadata['human_readable_size']);
        $this->assertEquals(180.5, $metadata['duration']);
        $this->assertEquals('3:00', $metadata['formatted_duration']);
        $this->assertEquals(320, $metadata['bitrate']);
        $this->assertEquals(44100, $metadata['sample_rate']);
    }

    public function test_get_audio_metadata_with_field_value(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Create a simple mock media object
        $mockMedia = new class {
            public $name = 'field-audio.wav';
            public $size = 1024;
            public $mime_type = 'audio/wav';
            public $created_at = '2023-01-01 12:00:00';
        };

        $field->value = $mockMedia;

        $metadata = $field->getAudioMetadata();

        $this->assertEquals('field-audio.wav', $metadata['name']);
        $this->assertEquals(1024, $metadata['size']);
        $this->assertEquals('audio/wav', $metadata['mime_type']);
        $this->assertEquals('2023-01-01 12:00:00', $metadata['created_at']);
        $this->assertEquals('1 KB', $metadata['human_readable_size']);
    }

    public function test_get_audio_metadata_with_fallback_values(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Create a mock media object with minimal properties
        $mockMedia = new class {
            public $file_name = 'fallback-audio.mp3';
            // No name, size, mime_type, or created_at
        };

        $metadata = $field->getAudioMetadata($mockMedia);

        $this->assertEquals('fallback-audio.mp3', $metadata['name']);
        $this->assertEquals(0, $metadata['size']);
        $this->assertEquals('audio/mpeg', $metadata['mime_type']);
        $this->assertNull($metadata['created_at']);
        $this->assertEquals('0 B', $metadata['human_readable_size']);
    }

    public function test_format_file_size_method(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($field);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);

        $this->assertEquals('0 B', $method->invoke($field, 0));
        $this->assertEquals('1 KB', $method->invoke($field, 1024));
        $this->assertEquals('1 MB', $method->invoke($field, 1048576));
        $this->assertEquals('1 GB', $method->invoke($field, 1073741824));
        $this->assertEquals('1.5 KB', $method->invoke($field, 1536));
        $this->assertEquals('2.5 MB', $method->invoke($field, 2621440));
    }

    public function test_format_duration_method(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($field);
        $method = $reflection->getMethod('formatDuration');
        $method->setAccessible(true);

        $this->assertEquals('0:30', $method->invoke($field, 30));
        $this->assertEquals('1:00', $method->invoke($field, 60));
        $this->assertEquals('2:30', $method->invoke($field, 150));
        $this->assertEquals('1:00:00', $method->invoke($field, 3600));
        $this->assertEquals('1:30:45', $method->invoke($field, 5445));
        $this->assertEquals('0:05', $method->invoke($field, 5.7));
    }

    public function test_meta_includes_audio_properties(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song')
            ->disableDownload()
            ->preload(MediaLibraryAudioField::PRELOAD_AUTO);

        $meta = $field->meta();

        // Test audio-specific meta
        $this->assertTrue($meta['downloadsDisabled']);
        $this->assertEquals(MediaLibraryAudioField::PRELOAD_AUTO, $meta['preload']);
        $this->assertArrayHasKey('audioUrl', $meta);
        $this->assertArrayHasKey('audioMetadata', $meta);

        // Test inherited MediaLibraryField meta
        $this->assertArrayHasKey('collection', $meta);
        $this->assertArrayHasKey('disk', $meta);
        $this->assertArrayHasKey('acceptedMimeTypes', $meta);
        $this->assertArrayHasKey('maxFileSize', $meta);
        $this->assertArrayHasKey('singleFile', $meta);

        // Test specific values
        $this->assertEquals('audio', $meta['collection']);
        $this->assertEquals('public', $meta['disk']);
        $this->assertTrue($meta['singleFile']);
    }

    public function test_nova_api_compatibility(): void
    {
        // Test basic Nova Audio field creation pattern
        $field = MediaLibraryAudioField::make('Theme Song');
        $this->assertInstanceOf(MediaLibraryAudioField::class, $field);
        $this->assertEquals('Theme Song', $field->name);
        $this->assertEquals('theme_song', $field->attribute);

        // Test Nova's disableDownload() method returns $this
        $disabledField = MediaLibraryAudioField::make('Theme Song')->disableDownload();
        $this->assertInstanceOf(MediaLibraryAudioField::class, $disabledField);
        $this->assertTrue($disabledField->downloadsAreDisabled());

        // Test Nova's preload() method returns $this
        $preloadField = MediaLibraryAudioField::make('Theme Song')->preload('auto');
        $this->assertInstanceOf(MediaLibraryAudioField::class, $preloadField);
        $this->assertEquals('auto', $preloadField->getPreloadAttribute());
    }

    public function test_method_chaining_compatibility(): void
    {
        $field = MediaLibraryAudioField::make('Podcast Episode')
            ->collection('podcasts')
            ->disk('s3')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/wav'])
            ->maxFileSize(102400) // 100MB
            ->disableDownload()
            ->preload(MediaLibraryAudioField::PRELOAD_AUTO)
            ->required()
            ->help('Upload your podcast episode');

        // Test all configurations are set
        $this->assertEquals('podcasts', $field->collection);
        $this->assertEquals('s3', $field->disk);
        $this->assertEquals(['audio/mpeg', 'audio/wav'], $field->acceptedMimeTypes);
        $this->assertEquals(102400, $field->maxFileSize);
        $this->assertTrue($field->downloadsAreDisabled());
        $this->assertEquals(MediaLibraryAudioField::PRELOAD_AUTO, $field->getPreloadAttribute());

        $meta = $field->meta();
        $this->assertTrue($meta['downloadsDisabled']);
        $this->assertEquals(MediaLibraryAudioField::PRELOAD_AUTO, $meta['preload']);
        $this->assertEquals('podcasts', $meta['collection']);
        $this->assertEquals('s3', $meta['disk']);
    }

    public function test_constructor_with_callback(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song', null, function ($resource, $attribute) {
            return 'custom-audio-' . $resource->{$attribute};
        });

        $resource = (object) ['theme_song' => 'test.mp3'];

        $field->resolve($resource);

        $this->assertEquals('custom-audio-test.mp3', $field->value);
    }

    public function test_media_library_field_inheritance(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Should inherit all MediaLibraryField methods
        $this->assertTrue(method_exists($field, 'collection'));
        $this->assertTrue(method_exists($field, 'disk'));
        $this->assertTrue(method_exists($field, 'acceptsMimeTypes'));
        $this->assertTrue(method_exists($field, 'maxFileSize'));
        $this->assertTrue(method_exists($field, 'singleFile'));
        $this->assertTrue(method_exists($field, 'multiple'));
        $this->assertTrue(method_exists($field, 'conversions'));
        $this->assertTrue(method_exists($field, 'responsiveImages'));
        $this->assertTrue(method_exists($field, 'enableCropping'));
        $this->assertTrue(method_exists($field, 'limit'));
        $this->assertTrue(method_exists($field, 'fallbackUrl'));
        $this->assertTrue(method_exists($field, 'fallbackPath'));

        // Should inherit all Field methods
        $this->assertTrue(method_exists($field, 'required'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'help'));
    }

    public function test_audio_specific_methods(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Test audio-specific methods exist
        $this->assertTrue(method_exists($field, 'disableDownload'));
        $this->assertTrue(method_exists($field, 'preload'));
        $this->assertTrue(method_exists($field, 'downloadsAreDisabled'));
        $this->assertTrue(method_exists($field, 'getPreloadAttribute'));
        $this->assertTrue(method_exists($field, 'getAudioUrl'));
        $this->assertTrue(method_exists($field, 'getAudioMetadata'));

        // Test method calls return expected types
        $this->assertIsBool($field->downloadsAreDisabled());
        $this->assertIsString($field->getPreloadAttribute());
        $this->assertIsArray($field->getAudioMetadata());
    }

    public function test_comprehensive_configuration(): void
    {
        $field = MediaLibraryAudioField::make('Background Music')
            ->collection('background')
            ->disk('audio-storage')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/wav', 'audio/ogg'])
            ->maxFileSize(20480) // 20MB
            ->disableDownload()
            ->preload(MediaLibraryAudioField::PRELOAD_NONE)
            ->conversions(['waveform' => ['width' => 800, 'height' => 200]])
            ->fallbackUrl('/audio/default.mp3')
            ->nullable()
            ->help('Upload background music for your content');

        // Test all configurations
        $this->assertEquals('background', $field->collection);
        $this->assertEquals('audio-storage', $field->disk);
        $this->assertEquals(['audio/mpeg', 'audio/wav', 'audio/ogg'], $field->acceptedMimeTypes);
        $this->assertEquals(20480, $field->maxFileSize);
        $this->assertTrue($field->downloadsAreDisabled());
        $this->assertEquals(MediaLibraryAudioField::PRELOAD_NONE, $field->getPreloadAttribute());

        $meta = $field->meta();
        $this->assertEquals('background', $meta['collection']);
        $this->assertEquals('audio-storage', $meta['disk']);
        $this->assertTrue($meta['downloadsDisabled']);
        $this->assertEquals(MediaLibraryAudioField::PRELOAD_NONE, $meta['preload']);
        $this->assertEquals('/audio/default.mp3', $meta['fallbackUrl']);
    }

    public function test_json_serialization(): void
    {
        $field = MediaLibraryAudioField::make('Podcast Episode')
            ->collection('podcasts')
            ->required()
            ->help('Upload your podcast episode');

        $json = $field->jsonSerialize();

        $this->assertEquals('Podcast Episode', $json['name']);
        $this->assertEquals('podcast_episode', $json['attribute']);
        $this->assertEquals('MediaLibraryAudioField', $json['component']);
        $this->assertEquals('podcasts', $json['collection']);
        $this->assertEquals(51200, $json['maxFileSize']);
        $this->assertTrue($json['singleFile']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Upload your podcast episode', $json['helpText']);
    }

    public function test_edge_cases_and_error_handling(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Test with empty media object
        $emptyMedia = new \stdClass();
        $this->assertNull($field->getAudioUrl($emptyMedia));

        // Empty media object will return default metadata structure
        $expectedEmptyMetadata = [
            'name' => 'Unknown',
            'size' => 0,
            'mime_type' => 'audio/mpeg',
            'created_at' => null,
            'human_readable_size' => '0 B',
        ];
        $this->assertEquals($expectedEmptyMetadata, $field->getAudioMetadata($emptyMedia));

        // Test with null values
        $this->assertNull($field->getAudioUrl(null));
        $this->assertEquals([], $field->getAudioMetadata(null));

        // Test preload with invalid value (should still work)
        $field->preload('invalid');
        $this->assertEquals('invalid', $field->getPreloadAttribute());
    }
}
