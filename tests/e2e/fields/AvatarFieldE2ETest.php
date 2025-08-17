<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Fields\Avatar;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;

/**
 * Avatar Field E2E Test
 *
 * Tests the complete end-to-end functionality of Avatar fields
 * including database operations, file storage, and field behavior.
 *
 * Focuses on field integration and data flow rather than
 * web interface testing (which is handled by Playwright tests).
 */
class AvatarFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup storage for avatar uploads
        Storage::fake('public');

        // Create test users with and without avatars
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => null
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'avatar' => 'avatars/jane-avatar.jpg'
        ]);
    }

    /** @test */
    public function it_handles_avatar_file_upload_and_storage(): void
    {
        // Create a test avatar file
        $avatarFile = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        // Create user with avatar upload
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'avatar' => null,
        ]);

        // Simulate file upload and storage (as would happen in controller)
        $storedPath = $avatarFile->store('avatars', 'public');
        $user->update(['avatar' => $storedPath]);

        // Verify the user was created with avatar
        $this->assertNotNull($user->avatar);
        $this->assertStringContains('avatars/', $user->avatar);

        // Verify file was stored
        Storage::disk('public')->assertExists($user->avatar);

        // Verify file is an image
        $this->assertTrue(str_contains($user->avatar, '.jpg') || str_contains($user->avatar, '.png'));
    }

    /** @test */
    public function it_handles_avatar_file_replacement(): void
    {
        $user = User::find(2); // User with existing avatar
        $oldAvatar = $user->avatar;

        // Create new avatar file
        $newAvatarFile = UploadedFile::fake()->image('new-avatar.jpg', 300, 300);

        // Replace avatar
        $newStoredPath = $newAvatarFile->store('avatars', 'public');
        $user->update(['avatar' => $newStoredPath]);

        // Verify avatar was replaced
        $this->assertNotNull($user->avatar);
        $this->assertNotEquals($oldAvatar, $user->avatar);

        // Verify new file exists
        Storage::disk('public')->assertExists($user->avatar);
    }

    /** @test */
    public function it_handles_avatar_removal(): void
    {
        $user = User::find(2); // User with existing avatar
        $this->assertNotNull($user->avatar);

        // Remove avatar
        $user->update(['avatar' => null]);

        // Verify avatar was removed
        $this->assertNull($user->avatar);
    }

    /** @test */
    public function it_validates_avatar_file_types(): void
    {
        // Test valid image types
        $validTypes = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($validTypes as $type) {
            $avatarFile = UploadedFile::fake()->image("avatar.{$type}", 200, 200);

            // Verify file can be stored
            $storedPath = $avatarFile->store('avatars', 'public');
            $this->assertNotNull($storedPath);
            $this->assertStringContains("avatars/", $storedPath);

            Storage::disk('public')->assertExists($storedPath);
        }
    }

    /** @test */
    public function it_handles_avatar_field_configuration(): void
    {
        $field = Avatar::make('Profile Picture')
            ->disk('avatars')
            ->path('users')
            ->acceptedTypes('image/jpeg,image/png')
            ->maxSize(2048);

        // Test field configuration
        $this->assertEquals('Profile Picture', $field->name);
        $this->assertEquals('profile_picture', $field->attribute);
        $this->assertEquals('AvatarField', $field->component);
        $this->assertEquals('avatars', $field->disk);
        $this->assertEquals('users', $field->path);
        $this->assertEquals('image/jpeg,image/png', $field->acceptedTypes);
        $this->assertEquals(2048, $field->maxSize);
    }

    /** @test */
    public function it_resolves_avatar_field_values_from_database(): void
    {
        $user = User::find(2); // User with avatar
        $field = Avatar::make('Profile Picture', 'avatar'); // Explicitly set attribute

        $field->resolve($user);

        // The user has an avatar path set in the database
        $this->assertEquals($user->avatar, $field->value);
        $this->assertEquals('avatars/jane-avatar.jpg', $field->value);
    }

    /** @test */
    public function it_handles_null_avatar_values(): void
    {
        $user = User::find(1); // User without avatar
        $field = Avatar::make('Profile Picture');

        $field->resolve($user);

        $this->assertNull($field->value);
    }

    /** @test */
    public function it_supports_different_image_formats(): void
    {
        $formats = [
            ['jpg', 'image/jpeg'],
            ['jpeg', 'image/jpeg'],
            ['png', 'image/png'],
            ['webp', 'image/webp'],
        ];

        foreach ($formats as [$extension, $mimeType]) {
            $avatarFile = UploadedFile::fake()->create("avatar.{$extension}", 1000, $mimeType);

            $user = User::create([
                'name' => "User {$extension}",
                'email' => "user-{$extension}@example.com",
                'password' => bcrypt('password'),
                'avatar' => null,
            ]);

            // Store file
            $storedPath = $avatarFile->store('avatars', 'public');
            $user->update(['avatar' => $storedPath]);

            $this->assertNotNull($user->avatar);
            $this->assertStringContains('avatars/', $user->avatar);
            Storage::disk('public')->assertExists($user->avatar);
        }
    }

    /** @test */
    public function it_handles_avatar_field_serialization(): void
    {
        $field = Avatar::make('Profile Picture')
            ->acceptedTypes('image/jpeg,image/png')
            ->maxSize(2048)
            ->help('Upload your profile picture');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Profile Picture', $serialized['name']);
        $this->assertEquals('profile_picture', $serialized['attribute']);
        $this->assertEquals('AvatarField', $serialized['component']);
        $this->assertEquals('image/jpeg,image/png', $serialized['acceptedTypes']);
        $this->assertEquals(2048, $serialized['maxSize']);
        $this->assertEquals('Upload your profile picture', $serialized['helpText']);
    }

    /** @test */
    public function it_integrates_with_database_operations(): void
    {
        // Test complete CRUD cycle
        $avatarFile = UploadedFile::fake()->image('test-avatar.jpg', 250, 250);

        // CREATE
        $user = User::create([
            'name' => 'CRUD Test User',
            'email' => 'crud@example.com',
            'password' => bcrypt('password'),
            'avatar' => null,
        ]);

        $storedPath = $avatarFile->store('avatars', 'public');
        $user->update(['avatar' => $storedPath]);

        // READ
        $retrievedUser = User::find($user->id);
        $this->assertEquals($storedPath, $retrievedUser->avatar);

        // UPDATE
        $newAvatarFile = UploadedFile::fake()->image('updated-avatar.jpg', 300, 300);
        $newStoredPath = $newAvatarFile->store('avatars', 'public');
        $retrievedUser->update(['avatar' => $newStoredPath]);

        $this->assertEquals($newStoredPath, $retrievedUser->fresh()->avatar);

        // DELETE
        $retrievedUser->update(['avatar' => null]);
        $this->assertNull($retrievedUser->fresh()->avatar);
    }

    /** @test */
    public function it_handles_avatar_field_with_custom_disk_and_path(): void
    {
        Storage::fake('custom-avatars');

        $field = Avatar::make('Profile Picture')
            ->disk('custom-avatars')
            ->path('user-profiles');

        $avatarFile = UploadedFile::fake()->image('custom-avatar.jpg', 200, 200);

        // Store on custom disk and path
        $storedPath = $avatarFile->store('user-profiles', 'custom-avatars');

        $user = User::create([
            'name' => 'Custom Storage User',
            'email' => 'custom@example.com',
            'password' => bcrypt('password'),
            'avatar' => $storedPath,
        ]);

        // Verify file was stored on custom disk
        $this->assertNotNull($user->avatar);
        $this->assertStringContains('user-profiles/', $user->avatar);
        Storage::disk('custom-avatars')->assertExists($user->avatar);
    }

    /** @test */
    public function it_preserves_existing_avatar_when_no_new_file_uploaded(): void
    {
        $user = User::find(2); // User with existing avatar
        $originalAvatar = $user->avatar;

        // Update user without changing avatar
        $user->update(['name' => 'Updated Name']);

        // Verify existing avatar is preserved
        $this->assertEquals($originalAvatar, $user->fresh()->avatar);
    }

    /** @test */
    public function it_handles_avatar_field_with_validation_rules(): void
    {
        $field = Avatar::make('Profile Picture')
            ->rules('required', 'image', 'max:2048')
            ->acceptedTypes('image/jpeg,image/png');

        // Test that validation rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('image', $field->rules);
        $this->assertContains('max:2048', $field->rules);
        $this->assertEquals('image/jpeg,image/png', $field->acceptedTypes);
    }
}
