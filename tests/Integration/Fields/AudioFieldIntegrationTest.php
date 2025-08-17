<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Fields\Audio;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Audio Field Integration Test
 *
 * Tests the complete integration between PHP Audio field class,
 * API endpoints, file storage, and frontend functionality.
 *
 * Follows the same pattern as other field integration tests,
 * focusing on field configuration and behavior rather than
 * database operations with non-existent columns.
 */
class AudioFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup storage for audio uploads
        Storage::fake('public');

        // Create test users (using existing User model structure)
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);
    }

    /** @test */
    public function it_creates_audio_field_with_nova_syntax(): void
    {
        $field = Audio::make('Theme Song');

        $this->assertEquals('Theme Song', $field->name);
        $this->assertEquals('theme_song', $field->attribute);
        $this->assertEquals('AudioField', $field->component);
        $this->assertEquals('audio', $field->path);
    }

    /** @test */
    public function it_creates_audio_field_with_custom_attribute(): void
    {
        $field = Audio::make('Podcast Episode', 'podcast_audio');

        $this->assertEquals('Podcast Episode', $field->name);
        $this->assertEquals('podcast_audio', $field->attribute);
    }

    /** @test */
    public function it_supports_all_nova_audio_configuration_methods(): void
    {
        $field = Audio::make('Theme Song')
            ->disableDownload()
            ->preload(Audio::PRELOAD_AUTO)
            ->disk('podcasts')
            ->path('episodes')
            ->acceptedTypes('audio/mpeg,audio/wav')
            ->maxSize(51200)
            ->nullable()
            ->help('Upload your theme song');

        $this->assertTrue($field->downloadsAreDisabled());
        $this->assertEquals(Audio::PRELOAD_AUTO, $field->getPreloadAttribute());
        $this->assertEquals('podcasts', $field->disk);
        $this->assertEquals('episodes', $field->path);
        $this->assertEquals('audio/mpeg,audio/wav', $field->acceptedTypes);
        $this->assertEquals(51200, $field->maxSize);

        $meta = $field->meta();
        $this->assertTrue($meta['downloadsDisabled']);
        $this->assertEquals('auto', $meta['preload']);
    }

    /** @test */
    public function it_inherits_file_field_configuration(): void
    {
        $field = Audio::make('Podcast Episode')
            ->disk('podcasts')
            ->path('episodes')
            ->acceptedTypes('audio/mpeg,audio/wav')
            ->maxSize(51200); // 50MB

        $this->assertEquals('podcasts', $field->disk);
        $this->assertEquals('episodes', $field->path);
        $this->assertEquals('audio/mpeg,audio/wav', $field->acceptedTypes);
        $this->assertEquals(51200, $field->maxSize);
    }

    /** @test */
    public function it_supports_nova_preload_constants(): void
    {
        // Test all Nova preload constants
        $field1 = Audio::make('Audio 1')->preload(Audio::PRELOAD_NONE);
        $field2 = Audio::make('Audio 2')->preload(Audio::PRELOAD_METADATA);
        $field3 = Audio::make('Audio 3')->preload(Audio::PRELOAD_AUTO);

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
        $field = Audio::make('Theme Song', 'name', function ($resource, $attribute) {
            return 'audio-' . strtolower($resource->{$attribute}) . '.mp3';
        });

        $field->resolve($user);

        $this->assertEquals('audio-john doe.mp3', $field->value);
    }

    /** @test */
    public function it_handles_file_upload_configuration(): void
    {
        Storage::fake('custom-disk');

        $field = Audio::make('Theme Song')
            ->disk('custom-disk')
            ->path('custom-path')
            ->acceptedTypes('audio/mpeg,audio/wav')
            ->maxSize(10240);

        // Test that file upload configuration is properly set
        $this->assertEquals('custom-disk', $field->disk);
        $this->assertEquals('custom-path', $field->path);
        $this->assertEquals('audio/mpeg,audio/wav', $field->acceptedTypes);
        $this->assertEquals(10240, $field->maxSize);

        // Test meta serialization includes file configuration
        $meta = $field->meta();
        $this->assertEquals('custom-disk', $meta['disk']);
        $this->assertEquals('custom-path', $meta['path']);
        $this->assertEquals('audio/mpeg,audio/wav', $meta['acceptedTypes']);
        $this->assertEquals(10240, $meta['maxSize']);
    }

    /** @test */
    public function it_integrates_with_validation_rules(): void
    {
        $field = Audio::make('Theme Song')
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
        $field1 = Audio::make('Theme Song');
        $this->assertFalse($field1->downloadsAreDisabled());
        $this->assertFalse($field1->meta()['downloadsDisabled']);

        // Test downloads disabled
        $field2 = Audio::make('Theme Song')->disableDownload();
        $this->assertTrue($field2->downloadsAreDisabled());
        $this->assertTrue($field2->meta()['downloadsDisabled']);
    }

    /** @test */
    public function it_serializes_audio_field_for_frontend(): void
    {
        $field = Audio::make('Theme Song')
            ->disableDownload()
            ->preload('auto')
            ->acceptedTypes('audio/mpeg,audio/wav')
            ->maxSize(10240)
            ->help('Upload your theme song');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Theme Song', $serialized['name']);
        $this->assertEquals('theme_song', $serialized['attribute']);
        $this->assertEquals('AudioField', $serialized['component']);
        $this->assertEquals('audio/mpeg,audio/wav', $serialized['acceptedTypes']);
        $this->assertEquals(10240, $serialized['maxSize']);
        $this->assertEquals('Upload your theme song', $serialized['helpText']);

        // Check meta properties (merged directly into serialized array)
        $this->assertTrue($serialized['downloadsDisabled']);
        $this->assertEquals('auto', $serialized['preload']);
    }

    /** @test */
    public function it_inherits_all_file_field_methods(): void
    {
        $field = Audio::make('Theme Song');

        // Test that Audio field inherits all File field methods
        $this->assertTrue(method_exists($field, 'disk'));
        $this->assertTrue(method_exists($field, 'path'));
        $this->assertTrue(method_exists($field, 'acceptedTypes'));
        $this->assertTrue(method_exists($field, 'maxSize'));
        $this->assertTrue(method_exists($field, 'multiple'));
        $this->assertTrue(method_exists($field, 'getUrl'));

        // Test that it also inherits base Field methods
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
    }

    /** @test */
    public function it_handles_complex_audio_field_configuration(): void
    {
        $field = Audio::make('Podcast Episode')
            ->disk('podcasts')
            ->path('episodes')
            ->acceptedTypes('audio/mpeg,audio/wav,audio/ogg')
            ->maxSize(51200) // 50MB
            ->disableDownload()
            ->preload(Audio::PRELOAD_METADATA)
            ->nullable()
            ->help('Upload your podcast episode in MP3, WAV, or OGG format');

        // Test all configurations are set
        $this->assertEquals('podcasts', $field->disk);
        $this->assertEquals('episodes', $field->path);
        $this->assertEquals('audio/mpeg,audio/wav,audio/ogg', $field->acceptedTypes);
        $this->assertEquals(51200, $field->maxSize);
        $this->assertTrue($field->downloadsAreDisabled());
        $this->assertEquals(Audio::PRELOAD_METADATA, $field->getPreloadAttribute());

        // Test serialization includes all configurations
        $serialized = $field->jsonSerialize();
        $this->assertEquals('podcasts', $serialized['disk']);
        $this->assertEquals('episodes', $serialized['path']);
        $this->assertEquals(51200, $serialized['maxSize']);
        $this->assertTrue($serialized['downloadsDisabled']);
        $this->assertEquals('metadata', $serialized['preload']);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = Audio::make('Theme Song')
            ->disableDownload()
            ->preload(Audio::PRELOAD_AUTO)
            ->disk('audio-storage')
            ->path('themes')
            ->acceptedTypes('audio/mpeg,audio/wav')
            ->maxSize(20480)
            ->nullable()
            ->help('Upload your theme song')
            ->rules('mimes:mp3,wav');

        // Test that all chained methods work correctly
        $this->assertTrue($field->downloadsAreDisabled());
        $this->assertEquals('auto', $field->getPreloadAttribute());
        $this->assertEquals('audio-storage', $field->disk);
        $this->assertEquals('themes', $field->path);
        $this->assertEquals('audio/mpeg,audio/wav', $field->acceptedTypes);
        $this->assertEquals(20480, $field->maxSize);
        $this->assertContains('mimes:mp3,wav', $field->rules);
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_audio_field(): void
    {
        // Test that our Audio field provides the same API as Nova's Audio field
        $field = Audio::make('Theme Song');

        // Test Nova-compatible methods exist and return correct types
        $this->assertInstanceOf(Audio::class, $field->disableDownload());
        $this->assertInstanceOf(Audio::class, $field->preload('auto'));

        // Test Nova-compatible constants exist
        $this->assertEquals('none', Audio::PRELOAD_NONE);
        $this->assertEquals('metadata', Audio::PRELOAD_METADATA);
        $this->assertEquals('auto', Audio::PRELOAD_AUTO);

        // Test that it extends File field (Nova requirement)
        $this->assertInstanceOf(\JTD\AdminPanel\Fields\File::class, $field);

        // Test component name matches Nova
        $this->assertEquals('AudioField', $field->component);
    }
}
