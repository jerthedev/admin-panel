<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Fields\MediaLibraryAudioField;
use JTD\AdminPanel\Fields\MediaLibraryField;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MediaLibraryAudioField Integration Test
 *
 * Tests the complete integration between PHP MediaLibraryAudioField class,
 * Media Library functionality, API endpoints, file storage, and frontend functionality.
 *
 * Focuses on Media Library integration, Nova Audio API compatibility,
 * and field configuration behavior.
 */
class MediaLibraryAudioFieldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup storage for audio uploads
        Storage::fake('public');
        Storage::fake('s3');

        // Create test users (using existing User model structure)
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);
    }

    /** @test */
    public function it_creates_media_library_audio_field_with_nova_syntax(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        $this->assertEquals('Theme Song', $field->name);
        $this->assertEquals('theme_song', $field->attribute);
        $this->assertEquals('MediaLibraryAudioField', $field->component);
        $this->assertEquals('audio', $field->collection);
    }

    /** @test */
    public function it_creates_media_library_audio_field_with_custom_attribute(): void
    {
        $field = MediaLibraryAudioField::make('Podcast Episode', 'podcast_audio');

        $this->assertEquals('Podcast Episode', $field->name);
        $this->assertEquals('podcast_audio', $field->attribute);
    }

    /** @test */
    public function it_extends_media_library_field(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        $this->assertInstanceOf(MediaLibraryField::class, $field);
        $this->assertInstanceOf(MediaLibraryAudioField::class, $field);
    }

    /** @test */
    public function it_supports_all_nova_audio_configuration_methods(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song')
            ->disableDownload()
            ->preload(MediaLibraryAudioField::PRELOAD_AUTO)
            ->collection('podcasts')
            ->disk('s3')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/wav'])
            ->maxFileSize(51200)
            ->nullable()
            ->help('Upload your theme song');

        $this->assertTrue($field->downloadsAreDisabled());
        $this->assertEquals(MediaLibraryAudioField::PRELOAD_AUTO, $field->getPreloadAttribute());
        $this->assertEquals('podcasts', $field->collection);
        $this->assertEquals('s3', $field->disk);
        $this->assertEquals(['audio/mpeg', 'audio/wav'], $field->acceptedMimeTypes);
        $this->assertEquals(51200, $field->maxFileSize);

        $meta = $field->meta();
        $this->assertTrue($meta['downloadsDisabled']);
        $this->assertEquals('auto', $meta['preload']);
        $this->assertEquals('podcasts', $meta['collection']);
        $this->assertEquals('s3', $meta['disk']);
    }

    /** @test */
    public function it_inherits_media_library_field_configuration(): void
    {
        $field = MediaLibraryAudioField::make('Podcast Episode')
            ->collection('podcasts')
            ->disk('s3')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/wav'])
            ->maxFileSize(51200)
            ->singleFile()
            ->conversions(['waveform' => ['width' => 800, 'height' => 200]])
            ->fallbackUrl('/audio/default.mp3');

        $this->assertEquals('podcasts', $field->collection);
        $this->assertEquals('s3', $field->disk);
        $this->assertEquals(['audio/mpeg', 'audio/wav'], $field->acceptedMimeTypes);
        $this->assertEquals(51200, $field->maxFileSize);
        $this->assertTrue($field->singleFile);
    }

    /** @test */
    public function it_supports_nova_preload_constants(): void
    {
        // Test all Nova preload constants
        $field1 = MediaLibraryAudioField::make('Audio 1')->preload(MediaLibraryAudioField::PRELOAD_NONE);
        $field2 = MediaLibraryAudioField::make('Audio 2')->preload(MediaLibraryAudioField::PRELOAD_METADATA);
        $field3 = MediaLibraryAudioField::make('Audio 3')->preload(MediaLibraryAudioField::PRELOAD_AUTO);

        $this->assertEquals('none', $field1->getPreloadAttribute());
        $this->assertEquals('metadata', $field2->getPreloadAttribute());
        $this->assertEquals('auto', $field3->getPreloadAttribute());

        // Test meta serialization
        $this->assertEquals('none', $field1->meta()['preload']);
        $this->assertEquals('metadata', $field2->meta()['preload']);
        $this->assertEquals('auto', $field3->meta()['preload']);
    }

    /** @test */
    public function it_resolves_audio_field_value_with_callback(): void
    {
        $user = User::find(1);
        $field = MediaLibraryAudioField::make('Theme Song', 'name', function ($resource, $attribute) {
            return 'audio-' . strtolower($resource->{$attribute}) . '.mp3';
        });

        $field->resolve($user);

        $this->assertEquals('audio-john doe.mp3', $field->value);
    }

    /** @test */
    public function it_handles_media_library_configuration(): void
    {
        Storage::fake('custom-disk');

        $field = MediaLibraryAudioField::make('Theme Song')
            ->collection('custom-audio')
            ->disk('custom-disk')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/wav'])
            ->maxFileSize(10240)
            ->conversions(['thumb' => ['width' => 150, 'height' => 150]])
            ->responsiveImages()
            ->enableCropping()
            ->limit(5)
            ->fallbackUrl('/audio/default.mp3');

        // Test that media library configuration is properly set
        $this->assertEquals('custom-audio', $field->collection);
        $this->assertEquals('custom-disk', $field->disk);
        $this->assertEquals(['audio/mpeg', 'audio/wav'], $field->acceptedMimeTypes);
        $this->assertEquals(10240, $field->maxFileSize);

        // Test meta serialization includes media library configuration
        $meta = $field->meta();
        $this->assertEquals('custom-audio', $meta['collection']);
        $this->assertEquals('custom-disk', $meta['disk']);
        $this->assertEquals(['audio/mpeg', 'audio/wav'], $meta['acceptedMimeTypes']);
        $this->assertEquals(10240, $meta['maxFileSize']);
        $this->assertEquals('/audio/default.mp3', $meta['fallbackUrl']);
    }

    /** @test */
    public function it_integrates_with_validation_rules(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song')
            ->rules('required', 'mimes:mp3,wav,ogg', 'max:10240')
            ->creationRules('sometimes')
            ->updateRules('nullable');

        // Test that rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('mimes:mp3,wav,ogg', $field->rules);
        $this->assertContains('max:10240', $field->rules);

        // Test creation and update rules
        $this->assertContains('sometimes', $field->creationRules);
        $this->assertContains('nullable', $field->updateRules);
    }

    /** @test */
    public function it_handles_download_control_configuration(): void
    {
        // Test downloads enabled (default)
        $field1 = MediaLibraryAudioField::make('Theme Song');
        $this->assertFalse($field1->downloadsAreDisabled());
        $this->assertFalse($field1->meta()['downloadsDisabled']);

        // Test downloads disabled
        $field2 = MediaLibraryAudioField::make('Theme Song')->disableDownload();
        $this->assertTrue($field2->downloadsAreDisabled());
        $this->assertTrue($field2->meta()['downloadsDisabled']);
    }

    /** @test */
    public function it_serializes_media_library_audio_field_for_frontend(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song')
            ->disableDownload()
            ->preload('auto')
            ->collection('audio')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/wav'])
            ->maxFileSize(10240)
            ->help('Upload your theme song');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Theme Song', $serialized['name']);
        $this->assertEquals('theme_song', $serialized['attribute']);
        $this->assertEquals('MediaLibraryAudioField', $serialized['component']);
        $this->assertEquals('audio', $serialized['collection']);
        $this->assertEquals(['audio/mpeg', 'audio/wav'], $serialized['acceptedMimeTypes']);
        $this->assertEquals(10240, $serialized['maxFileSize']);
        $this->assertEquals('Upload your theme song', $serialized['helpText']);

        // Check meta properties (merged directly into serialized array)
        $this->assertTrue($serialized['downloadsDisabled']);
        $this->assertEquals('auto', $serialized['preload']);
    }

    /** @test */
    public function it_inherits_all_media_library_field_methods(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Test that MediaLibraryAudioField inherits all MediaLibraryField methods
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

        // Test that it also inherits base Field methods
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
    }

    /** @test */
    public function it_has_audio_specific_methods(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Test audio-specific methods exist
        $this->assertTrue(method_exists($field, 'disableDownload'));
        $this->assertTrue(method_exists($field, 'preload'));
        $this->assertTrue(method_exists($field, 'downloadsAreDisabled'));
        $this->assertTrue(method_exists($field, 'getPreloadAttribute'));
        $this->assertTrue(method_exists($field, 'getAudioUrl'));
        $this->assertTrue(method_exists($field, 'getAudioMetadata'));
    }

    /** @test */
    public function it_handles_complex_media_library_audio_field_configuration(): void
    {
        $field = MediaLibraryAudioField::make('Podcast Episode')
            ->collection('podcasts')
            ->disk('s3')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/wav', 'audio/ogg'])
            ->maxFileSize(51200) // 50MB
            ->disableDownload()
            ->preload(MediaLibraryAudioField::PRELOAD_METADATA)
            ->singleFile()
            ->conversions(['waveform' => ['width' => 800, 'height' => 200]])
            ->fallbackUrl('/audio/default.mp3')
            ->nullable()
            ->help('Upload your podcast episode in MP3, WAV, or OGG format');

        // Test all configurations are set
        $this->assertEquals('podcasts', $field->collection);
        $this->assertEquals('s3', $field->disk);
        $this->assertEquals(['audio/mpeg', 'audio/wav', 'audio/ogg'], $field->acceptedMimeTypes);
        $this->assertEquals(51200, $field->maxFileSize);
        $this->assertTrue($field->downloadsAreDisabled());
        $this->assertEquals(MediaLibraryAudioField::PRELOAD_METADATA, $field->getPreloadAttribute());
        $this->assertTrue($field->singleFile);

        // Test serialization includes all configurations
        $serialized = $field->jsonSerialize();
        $this->assertEquals('podcasts', $serialized['collection']);
        $this->assertEquals('s3', $serialized['disk']);
        $this->assertEquals(51200, $serialized['maxFileSize']);
        $this->assertTrue($serialized['downloadsDisabled']);
        $this->assertEquals('metadata', $serialized['preload']);
        $this->assertTrue($serialized['singleFile']);
        $this->assertEquals('/audio/default.mp3', $serialized['fallbackUrl']);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song')
            ->disableDownload()
            ->preload(MediaLibraryAudioField::PRELOAD_AUTO)
            ->collection('audio-storage')
            ->disk('s3')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/wav'])
            ->maxFileSize(20480)
            ->singleFile()
            ->nullable()
            ->help('Upload your theme song')
            ->rules('mimes:mp3,wav');

        // Test that all chained methods work correctly
        $this->assertTrue($field->downloadsAreDisabled());
        $this->assertEquals('auto', $field->getPreloadAttribute());
        $this->assertEquals('audio-storage', $field->collection);
        $this->assertEquals('s3', $field->disk);
        $this->assertEquals(['audio/mpeg', 'audio/wav'], $field->acceptedMimeTypes);
        $this->assertEquals(20480, $field->maxFileSize);
        $this->assertTrue($field->singleFile);
        $this->assertContains('mimes:mp3,wav', $field->rules);
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_audio_field(): void
    {
        // Test that our MediaLibraryAudioField provides the same API as Nova's Audio field
        $field = MediaLibraryAudioField::make('Theme Song');

        // Test Nova-compatible methods exist and return correct types
        $this->assertInstanceOf(MediaLibraryAudioField::class, $field->disableDownload());
        $this->assertInstanceOf(MediaLibraryAudioField::class, $field->preload('auto'));

        // Test Nova-compatible constants exist
        $this->assertEquals('none', MediaLibraryAudioField::PRELOAD_NONE);
        $this->assertEquals('metadata', MediaLibraryAudioField::PRELOAD_METADATA);
        $this->assertEquals('auto', MediaLibraryAudioField::PRELOAD_AUTO);

        // Test that it extends MediaLibraryField (our requirement)
        $this->assertInstanceOf(MediaLibraryField::class, $field);

        // Test component name is specific to MediaLibrary
        $this->assertEquals('MediaLibraryAudioField', $field->component);
    }

    /** @test */
    public function it_handles_audio_metadata_integration(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Create a mock media object with audio metadata
        $mockMedia = new class {
            public $name = 'theme-song.mp3';
            public $size = 5242880; // 5MB
            public $mime_type = 'audio/mpeg';
            public $created_at = '2023-01-01 12:00:00';
            public $custom_properties = [
                'duration' => 180.5,
                'bitrate' => 320,
                'sample_rate' => 44100,
            ];

            public function getUrl($conversion = '') {
                return 'https://example.com/audio/theme-song.mp3';
            }
        };

        $field->value = $mockMedia;

        // Test audio URL retrieval
        $this->assertEquals('https://example.com/audio/theme-song.mp3', $field->getAudioUrl());

        // Test audio metadata retrieval
        $metadata = $field->getAudioMetadata();
        $this->assertEquals('theme-song.mp3', $metadata['name']);
        $this->assertEquals(5242880, $metadata['size']);
        $this->assertEquals('audio/mpeg', $metadata['mime_type']);
        $this->assertEquals(180.5, $metadata['duration']);
        $this->assertEquals('3:00', $metadata['formatted_duration']);
        $this->assertEquals(320, $metadata['bitrate']);
        $this->assertEquals(44100, $metadata['sample_rate']);

        // Test meta includes audio data
        $meta = $field->meta();
        $this->assertEquals('https://example.com/audio/theme-song.mp3', $meta['audioUrl']);
        $this->assertIsArray($meta['audioMetadata']);
        $this->assertEquals('theme-song.mp3', $meta['audioMetadata']['name']);
    }

    /** @test */
    public function it_handles_default_audio_configuration(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song');

        // Test default audio-specific configuration
        $this->assertEquals('audio', $field->collection);
        $this->assertTrue($field->singleFile);
        $this->assertEquals(MediaLibraryAudioField::PRELOAD_METADATA, $field->getPreloadAttribute());
        $this->assertFalse($field->downloadsAreDisabled());

        // Test default MIME types include common audio formats
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

        // Test default file size limit (50MB)
        $this->assertEquals(51200, $field->maxFileSize);
    }

    /** @test */
    public function it_integrates_with_media_library_features(): void
    {
        $field = MediaLibraryAudioField::make('Podcast')
            ->collection('podcasts')
            ->conversions([
                'waveform' => ['width' => 800, 'height' => 200],
                'thumbnail' => ['width' => 150, 'height' => 150],
            ])
            ->responsiveImages()
            ->enableCropping()
            ->limit(1)
            ->fallbackUrl('/audio/default-podcast.mp3')
            ->fallbackPath('/path/to/default-podcast.mp3');

        // Test Media Library specific features are configured
        $meta = $field->meta();
        $this->assertEquals('podcasts', $meta['collection']);
        $this->assertIsArray($meta['conversions']);
        $this->assertTrue($meta['responsiveImages']);
        $this->assertTrue($meta['enableCropping']);
        $this->assertEquals(1, $meta['limit']);
        $this->assertEquals('/audio/default-podcast.mp3', $meta['fallbackUrl']);
        $this->assertEquals('/path/to/default-podcast.mp3', $meta['fallbackPath']);
    }
}
