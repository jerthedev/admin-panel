<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Fields\MediaLibraryAudioField;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;

/**
 * MediaLibraryAudioField E2E Test
 *
 * Tests the complete end-to-end functionality of MediaLibraryAudioField
 * including database operations, Media Library integration, file storage,
 * and field behavior in realistic scenarios.
 * 
 * Focuses on field integration and data flow rather than
 * web interface testing (which is handled by Playwright tests).
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
        
        // Create test users with and without audio files
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
    }

    /** @test */
    public function it_handles_media_library_audio_field_creation_and_configuration(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song')
            ->collection('audio')
            ->disk('public')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/wav'])
            ->maxFileSize(51200)
            ->disableDownload()
            ->preload(MediaLibraryAudioField::PRELOAD_AUTO);

        // Test field configuration
        $this->assertEquals('Theme Song', $field->name);
        $this->assertEquals('theme_song', $field->attribute);
        $this->assertEquals('MediaLibraryAudioField', $field->component);
        $this->assertEquals('audio', $field->collection);
        $this->assertEquals('public', $field->disk);
        $this->assertEquals(['audio/mpeg', 'audio/wav'], $field->acceptedMimeTypes);
        $this->assertEquals(51200, $field->maxFileSize);
        $this->assertTrue($field->downloadsAreDisabled());
        $this->assertEquals(MediaLibraryAudioField::PRELOAD_AUTO, $field->getPreloadAttribute());
    }

    /** @test */
    public function it_handles_audio_file_upload_with_media_library(): void
    {
        // Create a test audio file
        $audioFile = UploadedFile::fake()->create('theme-song.mp3', 5000, 'audio/mpeg');

        // Create user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Simulate Media Library file upload (as would happen in controller)
        $storedPath = $audioFile->store('audio', 'public');
        
        // Verify file was stored
        Storage::disk('public')->assertExists($storedPath);
        
        // Verify file is an audio file
        $this->assertTrue(str_contains($storedPath, '.mp3') || str_contains($storedPath, '.wav'));
        $this->assertStringContains('audio/', $storedPath);
    }

    /** @test */
    public function it_validates_audio_file_types_for_media_library(): void
    {
        // Test valid audio types
        $validTypes = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
        $mimeTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4', 'audio/aac', 'audio/flac'];
        
        foreach ($validTypes as $index => $type) {
            $audioFile = UploadedFile::fake()->create("audio.{$type}", 2000, $mimeTypes[$index]);
            
            // Verify file can be stored
            $storedPath = $audioFile->store('audio', 'public');
            $this->assertNotNull($storedPath);
            $this->assertStringContains("audio/", $storedPath);
            
            Storage::disk('public')->assertExists($storedPath);
        }
    }

    /** @test */
    public function it_handles_media_library_audio_field_with_collections(): void
    {
        $field = MediaLibraryAudioField::make('Podcast Episode')
            ->collection('podcasts')
            ->disk('s3')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/wav', 'audio/ogg'])
            ->maxFileSize(102400); // 100MB

        // Test collection-specific configuration
        $this->assertEquals('podcasts', $field->collection);
        $this->assertEquals('s3', $field->disk);
        $this->assertEquals(['audio/mpeg', 'audio/wav', 'audio/ogg'], $field->acceptedMimeTypes);
        $this->assertEquals(102400, $field->maxFileSize);

        // Test meta includes collection info
        $meta = $field->meta();
        $this->assertEquals('podcasts', $meta['collection']);
        $this->assertEquals('s3', $meta['disk']);
    }

    /** @test */
    public function it_resolves_media_library_audio_field_values(): void
    {
        $user = User::find(1);
        $field = MediaLibraryAudioField::make('Theme Song');

        $field->resolve($user);

        // Since we're not actually using HasMedia trait in test fixtures,
        // the value should be null initially
        $this->assertNull($field->value);
    }

    /** @test */
    public function it_supports_different_audio_formats_with_media_library(): void
    {
        $formats = [
            ['mp3', 'audio/mpeg'],
            ['wav', 'audio/wav'],
            ['ogg', 'audio/ogg'],
            ['m4a', 'audio/mp4'],
            ['aac', 'audio/aac'],
            ['flac', 'audio/flac'],
        ];

        foreach ($formats as [$extension, $mimeType]) {
            $audioFile = UploadedFile::fake()->create("audio.{$extension}", 8000, $mimeType);
            
            // Store file in media library style
            $storedPath = $audioFile->store('audio', 'public');

            $this->assertNotNull($storedPath);
            $this->assertStringContains('audio/', $storedPath);
            Storage::disk('public')->assertExists($storedPath);
        }
    }

    /** @test */
    public function it_handles_media_library_audio_field_serialization(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song')
            ->collection('audio')
            ->disableDownload()
            ->preload('auto')
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
        
        // Check meta properties
        $this->assertTrue($serialized['downloadsDisabled']);
        $this->assertEquals('auto', $serialized['preload']);
    }

    /** @test */
    public function it_integrates_with_media_library_conversions(): void
    {
        $field = MediaLibraryAudioField::make('Podcast')
            ->collection('podcasts')
            ->conversions([
                'waveform' => ['width' => 800, 'height' => 200],
                'thumbnail' => ['width' => 150, 'height' => 150],
            ])
            ->responsiveImages()
            ->enableCropping();

        // Test conversions configuration
        $meta = $field->meta();
        $this->assertIsArray($meta['conversions']);
        $this->assertTrue($meta['responsiveImages']);
        $this->assertTrue($meta['enableCropping']);
    }

    /** @test */
    public function it_handles_media_library_audio_field_with_custom_disk_and_collection(): void
    {
        Storage::fake('custom-audio');
        
        $field = MediaLibraryAudioField::make('Theme Song')
            ->collection('user-themes')
            ->disk('custom-audio');

        $audioFile = UploadedFile::fake()->create('custom-theme.mp3', 2000, 'audio/mpeg');
        
        // Store on custom disk
        $storedPath = $audioFile->store('user-themes', 'custom-audio');
        
        // Verify file was stored on custom disk
        $this->assertNotNull($storedPath);
        $this->assertStringContains('user-themes/', $storedPath);
        Storage::disk('custom-audio')->assertExists($storedPath);
    }

    /** @test */
    public function it_handles_media_library_audio_field_with_validation_rules(): void
    {
        $field = MediaLibraryAudioField::make('Theme Song')
            ->rules('required', 'mimes:mp3,wav,ogg', 'max:10240')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/wav', 'audio/ogg']);

        // Test that validation rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('mimes:mp3,wav,ogg', $field->rules);
        $this->assertContains('max:10240', $field->rules);
        $this->assertEquals(['audio/mpeg', 'audio/wav', 'audio/ogg'], $field->acceptedMimeTypes);
    }

    /** @test */
    public function it_handles_nova_preload_constants_with_media_library(): void
    {
        // Test all Nova preload constants
        $field1 = MediaLibraryAudioField::make('Audio 1')->preload(MediaLibraryAudioField::PRELOAD_NONE);
        $field2 = MediaLibraryAudioField::make('Audio 2')->preload(MediaLibraryAudioField::PRELOAD_METADATA);
        $field3 = MediaLibraryAudioField::make('Audio 3')->preload(MediaLibraryAudioField::PRELOAD_AUTO);

        $this->assertEquals('none', $field1->getPreloadAttribute());
        $this->assertEquals('metadata', $field2->getPreloadAttribute());
        $this->assertEquals('auto', $field3->getPreloadAttribute());
    }

    /** @test */
    public function it_handles_audio_metadata_with_media_library(): void
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
    }

    /** @test */
    public function it_handles_media_library_fallback_configuration(): void
    {
        $field = MediaLibraryAudioField::make('Background Music')
            ->collection('background')
            ->fallbackUrl('/audio/default-background.mp3')
            ->fallbackPath('/path/to/default-background.mp3');

        // Test fallback configuration
        $meta = $field->meta();
        $this->assertEquals('/audio/default-background.mp3', $meta['fallbackUrl']);
        $this->assertEquals('/path/to/default-background.mp3', $meta['fallbackPath']);
    }

    /** @test */
    public function it_handles_single_file_vs_multiple_files(): void
    {
        // Test single file (default for audio)
        $singleField = MediaLibraryAudioField::make('Theme Song');
        $this->assertTrue($singleField->singleFile);

        // Test multiple files
        $multipleField = MediaLibraryAudioField::make('Sound Effects')
            ->multiple();
        $this->assertTrue($multipleField->multiple);
        $this->assertFalse($multipleField->singleFile);
    }

    /** @test */
    public function it_handles_complex_media_library_configuration(): void
    {
        $field = MediaLibraryAudioField::make('Podcast Episode')
            ->collection('podcasts')
            ->disk('s3')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/wav', 'audio/ogg'])
            ->maxFileSize(102400) // 100MB
            ->disableDownload()
            ->preload(MediaLibraryAudioField::PRELOAD_METADATA)
            ->singleFile()
            ->conversions([
                'waveform' => ['width' => 800, 'height' => 200],
                'thumbnail' => ['width' => 150, 'height' => 150],
            ])
            ->responsiveImages()
            ->enableCropping()
            ->limit(1)
            ->fallbackUrl('/audio/default-podcast.mp3')
            ->nullable()
            ->help('Upload your podcast episode');

        // Test all configurations are set
        $this->assertEquals('podcasts', $field->collection);
        $this->assertEquals('s3', $field->disk);
        $this->assertEquals(['audio/mpeg', 'audio/wav', 'audio/ogg'], $field->acceptedMimeTypes);
        $this->assertEquals(102400, $field->maxFileSize);
        $this->assertTrue($field->downloadsAreDisabled());
        $this->assertEquals(MediaLibraryAudioField::PRELOAD_METADATA, $field->getPreloadAttribute());
        $this->assertTrue($field->singleFile);

        // Test serialization includes all configurations
        $serialized = $field->jsonSerialize();
        $this->assertEquals('podcasts', $serialized['collection']);
        $this->assertEquals('s3', $serialized['disk']);
        $this->assertEquals(102400, $serialized['maxFileSize']);
        $this->assertTrue($serialized['downloadsDisabled']);
        $this->assertEquals('metadata', $serialized['preload']);
        $this->assertTrue($serialized['singleFile']);
        $this->assertEquals('/audio/default-podcast.mp3', $serialized['fallbackUrl']);
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

        // Test component name is specific to MediaLibrary
        $this->assertEquals('MediaLibraryAudioField', $field->component);
    }

    /** @test */
    public function it_handles_method_chaining_like_nova(): void
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
    public function it_handles_default_media_library_audio_configuration(): void
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
    public function it_handles_audio_file_storage_with_different_disks(): void
    {
        $disks = ['public', 's3', 'local'];

        foreach ($disks as $disk) {
            Storage::fake($disk);

            $audioFile = UploadedFile::fake()->create("audio-{$disk}.mp3", 3000, 'audio/mpeg');

            // Store file on specific disk
            $storedPath = $audioFile->store('audio', $disk);

            $this->assertNotNull($storedPath);
            $this->assertStringContains('audio/', $storedPath);
            Storage::disk($disk)->assertExists($storedPath);
        }
    }
}
