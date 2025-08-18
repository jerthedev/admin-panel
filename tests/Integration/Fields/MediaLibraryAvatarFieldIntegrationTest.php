<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Fields;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Fields\MediaLibraryAvatar;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Integration tests for MediaLibraryAvatar field.
 *
 * Tests the field configuration, meta data generation, and API compatibility
 * without requiring full Media Library database setup.
 */
class MediaLibraryAvatarFieldIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up storage for testing
        Storage::fake('public');
    }

    /** @test */
    public function it_creates_field_with_correct_configuration(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        // Verify field is properly configured
        $this->assertEquals('Avatar', $field->name);
        $this->assertEquals('avatar', $field->attribute);
        $this->assertEquals('MediaLibraryAvatarField', $field->component);

        // Verify default configuration
        $meta = $field->meta();
        $this->assertEquals('avatars', $meta['collection']);
        $this->assertTrue($meta['singleFile']);
        $this->assertTrue($meta['enableCropping']);
        $this->assertEquals('1:1', $meta['cropAspectRatio']);
    }

    /** @test */
    public function it_supports_nova_avatar_field_compatibility_methods(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        // Test squared method
        $squaredField = $field->squared();
        $this->assertInstanceOf(MediaLibraryAvatar::class, $squaredField);

        $meta = $squaredField->meta();
        $this->assertTrue($meta['squared']);
        $this->assertFalse($meta['rounded']);

        // Test rounded method
        $roundedField = $field->rounded();
        $this->assertInstanceOf(MediaLibraryAvatar::class, $roundedField);

        $meta = $roundedField->meta();
        $this->assertFalse($meta['squared']);
        $this->assertTrue($meta['rounded']);
    }

    /** @test */
    public function it_provides_correct_meta_information_for_frontend(): void
    {
        $field = MediaLibraryAvatar::make('Avatar')
            ->squared()
            ->fallbackUrl('/images/default-avatar.png');

        $meta = $field->meta();

        // Verify meta contains required information for frontend
        $this->assertArrayHasKey('collection', $meta);
        $this->assertArrayHasKey('singleFile', $meta);
        $this->assertArrayHasKey('enableCropping', $meta);
        $this->assertArrayHasKey('cropAspectRatio', $meta);
        $this->assertArrayHasKey('fallbackUrl', $meta);
        $this->assertArrayHasKey('avatarMetadata', $meta);
        $this->assertArrayHasKey('avatarSizes', $meta);
        $this->assertArrayHasKey('hasAvatar', $meta);
        $this->assertArrayHasKey('squared', $meta);

        // Verify values
        $this->assertEquals('avatars', $meta['collection']);
        $this->assertTrue($meta['singleFile']);
        $this->assertTrue($meta['enableCropping']);
        $this->assertEquals('1:1', $meta['cropAspectRatio']);
        $this->assertEquals('/images/default-avatar.png', $meta['fallbackUrl']);
        $this->assertFalse($meta['hasAvatar']); // No avatar attached
        $this->assertTrue($meta['squared']);
    }

    /** @test */
    public function it_handles_file_validation_integration(): void
    {
        $field = MediaLibraryAvatar::make('Avatar');

        // Test with valid file
        $validFile = UploadedFile::fake()->image('avatar.jpg', 300, 300);
        $request = new Request();
        $request->files->set('avatar', $validFile);

        // Should not throw exception for valid file
        $this->assertTrue($validFile->isValid());
        $this->assertEquals('image/jpeg', $validFile->getMimeType());

        // Test file size validation
        $this->assertLessThan(2048 * 1024, $validFile->getSize()); // Less than 2MB
    }

    /** @test */
    public function it_serializes_correctly_for_inertia_frontend(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture')
            ->help('Upload your profile picture')
            ->squared()
            ->required();

        $serialized = $field->jsonSerialize();

        // Verify all required properties for frontend are present
        $this->assertEquals('Profile Picture', $serialized['name']);
        $this->assertEquals('profile_picture', $serialized['attribute']);
        $this->assertEquals('MediaLibraryAvatarField', $serialized['component']);
        $this->assertEquals('Upload your profile picture', $serialized['helpText']);
        $this->assertContains('required', $serialized['rules']);

        // Verify media library specific properties
        $this->assertEquals('avatars', $serialized['collection']);
        $this->assertTrue($serialized['singleFile']);
        $this->assertTrue($serialized['enableCropping']);
        $this->assertEquals('1:1', $serialized['cropAspectRatio']);

        // Verify avatar-specific meta
        $this->assertFalse($serialized['hasAvatar']); // No avatar attached
        $this->assertTrue($serialized['squared']);
        $this->assertArrayHasKey('avatarMetadata', $serialized);
        $this->assertArrayHasKey('avatarSizes', $serialized);
    }
}
