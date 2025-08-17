<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Fields\Audio;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;

/**
 * Audio Field E2E Test
 *
 * Tests the complete end-to-end functionality of Audio fields
 * including database operations, file storage, and field behavior.
 * 
 * Focuses on field integration and data flow rather than
 * web interface testing (which is handled by Playwright tests).
 */
class AudioFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup storage for audio uploads
        Storage::fake('public');
        
        // Create test users with and without audio files
        User::factory()->create([
            'id' => 1, 
            'name' => 'John Doe', 
            'email' => 'john@example.com',
            'theme_song' => null
        ]);
        
        User::factory()->create([
            'id' => 2, 
            'name' => 'Jane Smith', 
            'email' => 'jane@example.com',
            'theme_song' => 'audio/jane-theme.mp3'
        ]);
    }

    /** @test */
    public function it_handles_audio_file_upload_and_storage(): void
    {
        // Create a test audio file
        $audioFile = UploadedFile::fake()->create('theme-song.mp3', 5000, 'audio/mpeg');

        // Create user with audio upload
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'theme_song' => null,
        ]);

        // Simulate file upload and storage (as would happen in controller)
        $storedPath = $audioFile->store('audio', 'public');
        $user->update(['theme_song' => $storedPath]);

        // Verify the user was created with audio
        $this->assertNotNull($user->theme_song);
        $this->assertStringContains('audio/', $user->theme_song);
        
        // Verify file was stored
        Storage::disk('public')->assertExists($user->theme_song);
        
        // Verify file is an audio file
        $this->assertTrue(str_contains($user->theme_song, '.mp3') || str_contains($user->theme_song, '.wav'));
    }

    /** @test */
    public function it_handles_audio_file_replacement(): void
    {
        $user = User::find(2); // User with existing audio
        $oldAudio = $user->theme_song;
        
        // Create new audio file
        $newAudioFile = UploadedFile::fake()->create('new-theme.mp3', 3000, 'audio/mpeg');
        
        // Replace audio
        $newStoredPath = $newAudioFile->store('audio', 'public');
        $user->update(['theme_song' => $newStoredPath]);

        // Verify audio was replaced
        $this->assertNotNull($user->theme_song);
        $this->assertNotEquals($oldAudio, $user->theme_song);
        
        // Verify new file exists
        Storage::disk('public')->assertExists($user->theme_song);
    }

    /** @test */
    public function it_handles_audio_removal(): void
    {
        $user = User::find(2); // User with existing audio
        $this->assertNotNull($user->theme_song);
        
        // Remove audio
        $user->update(['theme_song' => null]);

        // Verify audio was removed
        $this->assertNull($user->theme_song);
    }

    /** @test */
    public function it_validates_audio_file_types(): void
    {
        // Test valid audio types
        $validTypes = ['mp3', 'wav', 'ogg', 'm4a'];
        $mimeTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4'];
        
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
    public function it_handles_audio_field_configuration(): void
    {
        $field = Audio::make('Theme Song')
            ->disableDownload()
            ->preload(Audio::PRELOAD_AUTO)
            ->disk('podcasts')
            ->path('episodes')
            ->acceptedTypes('audio/mpeg,audio/wav')
            ->maxSize(51200);

        // Test field configuration
        $this->assertEquals('Theme Song', $field->name);
        $this->assertEquals('theme_song', $field->attribute);
        $this->assertEquals('AudioField', $field->component);
        $this->assertTrue($field->downloadsAreDisabled());
        $this->assertEquals(Audio::PRELOAD_AUTO, $field->getPreloadAttribute());
        $this->assertEquals('podcasts', $field->disk);
        $this->assertEquals('episodes', $field->path);
        $this->assertEquals('audio/mpeg,audio/wav', $field->acceptedTypes);
        $this->assertEquals(51200, $field->maxSize);
    }

    /** @test */
    public function it_resolves_audio_field_values_from_database(): void
    {
        $user = User::find(2); // User with audio
        $field = Audio::make('Theme Song');

        $field->resolve($user);

        $this->assertEquals('audio/jane-theme.mp3', $field->value);
    }

    /** @test */
    public function it_handles_null_audio_values(): void
    {
        $user = User::find(1); // User without audio
        $field = Audio::make('Theme Song');

        $field->resolve($user);

        $this->assertNull($field->value);
    }

    /** @test */
    public function it_supports_different_audio_formats(): void
    {
        $formats = [
            ['mp3', 'audio/mpeg'],
            ['wav', 'audio/wav'],
            ['ogg', 'audio/ogg'],
            ['m4a', 'audio/mp4'],
        ];

        foreach ($formats as [$extension, $mimeType]) {
            $audioFile = UploadedFile::fake()->create("audio.{$extension}", 8000, $mimeType);
            
            $user = User::create([
                'name' => "User {$extension}",
                'email' => "user-{$extension}@example.com",
                'password' => bcrypt('password'),
                'theme_song' => null,
            ]);

            // Store file
            $storedPath = $audioFile->store('audio', 'public');
            $user->update(['theme_song' => $storedPath]);

            $this->assertNotNull($user->theme_song);
            $this->assertStringContains('audio/', $user->theme_song);
            Storage::disk('public')->assertExists($user->theme_song);
        }
    }

    /** @test */
    public function it_handles_audio_field_serialization(): void
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
        
        // Check meta properties
        $this->assertTrue($serialized['downloadsDisabled']);
        $this->assertEquals('auto', $serialized['preload']);
    }

    /** @test */
    public function it_integrates_with_database_operations(): void
    {
        // Test complete CRUD cycle
        $audioFile = UploadedFile::fake()->create('test-audio.mp3', 4000, 'audio/mpeg');
        
        // CREATE
        $user = User::create([
            'name' => 'CRUD Test User',
            'email' => 'crud@example.com',
            'password' => bcrypt('password'),
            'theme_song' => null,
        ]);
        
        $storedPath = $audioFile->store('audio', 'public');
        $user->update(['theme_song' => $storedPath]);
        
        // READ
        $retrievedUser = User::find($user->id);
        $this->assertEquals($storedPath, $retrievedUser->theme_song);
        
        // UPDATE
        $newAudioFile = UploadedFile::fake()->create('updated-audio.mp3', 6000, 'audio/mpeg');
        $newStoredPath = $newAudioFile->store('audio', 'public');
        $retrievedUser->update(['theme_song' => $newStoredPath]);
        
        $this->assertEquals($newStoredPath, $retrievedUser->fresh()->theme_song);
        
        // DELETE
        $retrievedUser->update(['theme_song' => null]);
        $this->assertNull($retrievedUser->fresh()->theme_song);
    }

    /** @test */
    public function it_handles_audio_field_with_custom_disk_and_path(): void
    {
        Storage::fake('custom-audio');
        
        $field = Audio::make('Theme Song')
            ->disk('custom-audio')
            ->path('user-themes');

        $audioFile = UploadedFile::fake()->create('custom-theme.mp3', 2000, 'audio/mpeg');
        
        // Store on custom disk and path
        $storedPath = $audioFile->store('user-themes', 'custom-audio');
        
        $user = User::create([
            'name' => 'Custom Storage User',
            'email' => 'custom@example.com',
            'password' => bcrypt('password'),
            'theme_song' => $storedPath,
        ]);

        // Verify file was stored on custom disk
        $this->assertNotNull($user->theme_song);
        $this->assertStringContains('user-themes/', $user->theme_song);
        Storage::disk('custom-audio')->assertExists($user->theme_song);
    }

    /** @test */
    public function it_preserves_existing_audio_when_no_new_file_uploaded(): void
    {
        $user = User::find(2); // User with existing audio
        $originalAudio = $user->theme_song;
        
        // Update user without changing audio
        $user->update(['name' => 'Updated Name']);

        // Verify existing audio is preserved
        $this->assertEquals($originalAudio, $user->fresh()->theme_song);
    }

    /** @test */
    public function it_handles_audio_field_with_validation_rules(): void
    {
        $field = Audio::make('Theme Song')
            ->rules('required', 'mimes:mp3,wav,ogg', 'max:10240')
            ->acceptedTypes('audio/mpeg,audio/wav,audio/ogg');

        // Test that validation rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('mimes:mp3,wav,ogg', $field->rules);
        $this->assertContains('max:10240', $field->rules);
        $this->assertEquals('audio/mpeg,audio/wav,audio/ogg', $field->acceptedTypes);
    }

    /** @test */
    public function it_handles_nova_preload_constants(): void
    {
        // Test all Nova preload constants
        $field1 = Audio::make('Audio 1')->preload(Audio::PRELOAD_NONE);
        $field2 = Audio::make('Audio 2')->preload(Audio::PRELOAD_METADATA);
        $field3 = Audio::make('Audio 3')->preload(Audio::PRELOAD_AUTO);

        $this->assertEquals('none', $field1->getPreloadAttribute());
        $this->assertEquals('metadata', $field2->getPreloadAttribute());
        $this->assertEquals('auto', $field3->getPreloadAttribute());
    }
}
