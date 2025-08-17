<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Audio;
use JTD\AdminPanel\Fields\File;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Audio Field Unit Tests
 *
 * Tests the Audio field functionality including Nova API compatibility,
 * file handling, download control, and preload configuration.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class AudioFieldTest extends TestCase
{
    public function test_audio_field_extends_file_field(): void
    {
        $field = Audio::make('Theme Song');

        $this->assertInstanceOf(File::class, $field);
        $this->assertInstanceOf(Audio::class, $field);
    }

    public function test_audio_field_has_correct_defaults(): void
    {
        $field = Audio::make('Theme Song');

        $this->assertEquals('AudioField', $field->component);
        $this->assertEquals('audio', $field->path);
        $this->assertEquals('audio/mpeg,audio/wav,audio/ogg,audio/mp4,audio/aac,audio/flac,.mp3,.wav,.ogg,.m4a,.aac,.flac', $field->acceptedTypes);
        $this->assertEquals(Audio::PRELOAD_METADATA, $field->getPreloadAttribute());
        $this->assertFalse($field->downloadsAreDisabled());
    }

    public function test_audio_field_nova_api_compatibility(): void
    {
        // Test basic Nova Audio field creation
        $field = Audio::make('Theme Song');
        $this->assertInstanceOf(Audio::class, $field);
        $this->assertEquals('Theme Song', $field->name);
        $this->assertEquals('theme_song', $field->attribute);

        // Test Nova's disableDownload() method returns $this
        $disabledField = Audio::make('Theme Song')->disableDownload();
        $this->assertInstanceOf(Audio::class, $disabledField);
        $this->assertTrue($disabledField->downloadsAreDisabled());

        // Test Nova's preload() method returns $this
        $preloadField = Audio::make('Theme Song')->preload('auto');
        $this->assertInstanceOf(Audio::class, $preloadField);
        $this->assertEquals('auto', $preloadField->getPreloadAttribute());
    }

    public function test_audio_field_disable_download(): void
    {
        $field = Audio::make('Theme Song')->disableDownload();

        $this->assertTrue($field->downloadsAreDisabled());
        
        $meta = $field->meta();
        $this->assertTrue($meta['downloadsDisabled']);
    }

    public function test_audio_field_preload_configuration(): void
    {
        // Test default preload
        $field = Audio::make('Theme Song');
        $this->assertEquals(Audio::PRELOAD_METADATA, $field->getPreloadAttribute());

        // Test preload with string
        $field->preload('auto');
        $this->assertEquals('auto', $field->getPreloadAttribute());

        // Test preload with constant
        $field->preload(Audio::PRELOAD_NONE);
        $this->assertEquals(Audio::PRELOAD_NONE, $field->getPreloadAttribute());

        $field->preload(Audio::PRELOAD_AUTO);
        $this->assertEquals(Audio::PRELOAD_AUTO, $field->getPreloadAttribute());
    }

    public function test_audio_field_preload_constants(): void
    {
        $this->assertEquals('none', Audio::PRELOAD_NONE);
        $this->assertEquals('metadata', Audio::PRELOAD_METADATA);
        $this->assertEquals('auto', Audio::PRELOAD_AUTO);
    }

    public function test_audio_field_extends_file_field_functionality(): void
    {
        $field = Audio::make('Theme Song');
        
        // Should inherit all File field methods
        $this->assertTrue(method_exists($field, 'disk'));
        $this->assertTrue(method_exists($field, 'path'));
        $this->assertTrue(method_exists($field, 'acceptedTypes'));
        $this->assertTrue(method_exists($field, 'maxSize'));
        $this->assertTrue(method_exists($field, 'getUrl'));
        $this->assertTrue(method_exists($field, 'multiple'));
        
        // Should inherit all Field methods
        $this->assertTrue(method_exists($field, 'required'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'help'));
    }

    public function test_audio_field_accepts_same_options_as_file_field(): void
    {
        $field = Audio::make('Podcast Episode')
            ->disk('podcasts')
            ->path('episodes')
            ->acceptedTypes('audio/mpeg,audio/wav')
            ->maxSize(51200) // 50MB
            ->required()
            ->help('Upload your podcast episode');

        // Should accept all File field options
        $this->assertEquals('podcasts', $field->disk);
        $this->assertEquals('episodes', $field->path);
        $this->assertEquals('audio/mpeg,audio/wav', $field->acceptedTypes);
        $this->assertEquals(51200, $field->maxSize);
    }

    public function test_audio_field_with_validation_rules(): void
    {
        $field = Audio::make('Theme Song')
            ->rules('required', 'mimes:mp3,wav,ogg', 'max:10240');

        $this->assertEquals(['required', 'mimes:mp3,wav,ogg', 'max:10240'], $field->rules);
    }

    public function test_audio_field_resolve_preserves_value(): void
    {
        $field = Audio::make('Theme Song');
        $resource = (object) ['theme_song' => 'audio/song.mp3'];

        $field->resolve($resource);

        $this->assertEquals('audio/song.mp3', $field->value);
    }

    public function test_audio_field_with_callback(): void
    {
        $field = Audio::make('Theme Song', null, function ($resource, $attribute) {
            return 'custom-audio-' . $resource->{$attribute};
        });

        $resource = (object) ['theme_song' => 'test.mp3'];

        $field->resolve($resource);

        $this->assertEquals('custom-audio-test.mp3', $field->value);
    }

    public function test_audio_field_meta_includes_audio_properties(): void
    {
        $field = Audio::make('Theme Song')
            ->disableDownload()
            ->preload('auto');

        $meta = $field->meta();

        $this->assertTrue($meta['downloadsDisabled']);
        $this->assertEquals('auto', $meta['preload']);
        
        // Should also include parent File meta
        $this->assertArrayHasKey('disk', $meta);
        $this->assertArrayHasKey('path', $meta);
        $this->assertArrayHasKey('acceptedTypes', $meta);
        $this->assertArrayHasKey('maxSize', $meta);
        $this->assertArrayHasKey('multiple', $meta);
    }

    public function test_audio_field_complex_configuration(): void
    {
        $field = Audio::make('Podcast Episode')
            ->disk('podcasts')
            ->path('episodes')
            ->acceptedTypes('audio/mpeg,audio/wav')
            ->maxSize(51200)
            ->disableDownload()
            ->preload(Audio::PRELOAD_AUTO)
            ->required()
            ->help('Upload your podcast episode in MP3 or WAV format');

        // Test all configurations are set
        $this->assertEquals('podcasts', $field->disk);
        $this->assertEquals('episodes', $field->path);
        $this->assertEquals('audio/mpeg,audio/wav', $field->acceptedTypes);
        $this->assertEquals(51200, $field->maxSize);
        $this->assertTrue($field->downloadsAreDisabled());
        $this->assertEquals(Audio::PRELOAD_AUTO, $field->getPreloadAttribute());
        
        $meta = $field->meta();
        $this->assertTrue($meta['downloadsDisabled']);
        $this->assertEquals(Audio::PRELOAD_AUTO, $meta['preload']);
    }

    public function test_audio_field_constructor_sets_audio_defaults(): void
    {
        $field = Audio::make('Theme Song');

        // Test that constructor sets audio-specific defaults
        $this->assertEquals('audio/mpeg,audio/wav,audio/ogg,audio/mp4,audio/aac,audio/flac,.mp3,.wav,.ogg,.m4a,.aac,.flac', $field->acceptedTypes);
        $this->assertEquals('audio', $field->path);
        $this->assertEquals(Audio::PRELOAD_METADATA, $field->getPreloadAttribute());
        $this->assertFalse($field->downloadsAreDisabled());
    }
}
