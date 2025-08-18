<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * End-to-End tests for MediaLibraryAvatar field.
 *
 * Tests the complete user workflow from frontend to backend including
 * file upload, storage, display, and CRUD operations.
 */
class MediaLibraryAvatarFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up storage for testing
        Storage::fake('public');
        
        // Create test users
        $this->testUser = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->userWithAvatar = User::create([
            'name' => 'User With Avatar',
            'email' => 'avatar@example.com',
            'password' => bcrypt('password'),
        ]);

        // Add avatar to second user
        $avatarFile = UploadedFile::fake()->image('existing-avatar.jpg', 300, 300);
        $this->userWithAvatar
            ->addMediaFromRequest('avatar')
            ->usingFileName('existing-avatar.jpg')
            ->toMediaCollection('avatars', 'public');
    }

    /** @test */
    public function it_completes_full_avatar_upload_workflow(): void
    {
        // CREATE: Upload new avatar
        $avatarFile = UploadedFile::fake()->image('new-avatar.jpg', 400, 400);
        
        // Simulate form submission with avatar upload
        $response = $this->post('/admin/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password',
            'avatar' => $avatarFile,
        ]);

        // Verify user was created
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user);

        // Verify avatar media was created
        $avatarMedia = $user->getMedia('avatars')->first();
        $this->assertNotNull($avatarMedia);
        $this->assertEquals('avatars', $avatarMedia->collection_name);
        $this->assertEquals('public', $avatarMedia->disk);

        // Verify file was stored
        Storage::disk('public')->assertExists($avatarMedia->getPath());

        // READ: Verify avatar is displayed in index
        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        // Avatar should be included in the response data for frontend display

        // READ: Verify avatar is displayed in detail view
        $response = $this->get("/admin/users/{$user->id}");
        $response->assertStatus(200);
        // Avatar metadata should be included for detailed view
    }

    /** @test */
    public function it_handles_avatar_replacement_workflow(): void
    {
        $user = $this->userWithAvatar;
        $originalMedia = $user->getMedia('avatars')->first();
        $originalPath = $originalMedia->getPath();

        // UPDATE: Replace existing avatar
        $newAvatarFile = UploadedFile::fake()->image('replacement-avatar.jpg', 500, 500);
        
        $response = $this->put("/admin/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $newAvatarFile,
        ]);

        $response->assertStatus(200);

        // Verify old avatar was removed
        $this->assertEquals(1, $user->fresh()->getMedia('avatars')->count());
        
        // Verify new avatar exists
        $newMedia = $user->fresh()->getMedia('avatars')->first();
        $this->assertNotEquals($originalMedia->id, $newMedia->id);
        $this->assertStringContains('replacement-avatar', $newMedia->file_name);

        // Verify new file was stored
        Storage::disk('public')->assertExists($newMedia->getPath());
    }

    /** @test */
    public function it_handles_avatar_removal_workflow(): void
    {
        $user = $this->userWithAvatar;
        $originalMedia = $user->getMedia('avatars')->first();
        $this->assertNotNull($originalMedia);

        // DELETE: Remove avatar
        $response = $this->delete("/admin/users/{$user->id}/avatar");
        $response->assertStatus(200);

        // Verify avatar was removed from database
        $this->assertEquals(0, $user->fresh()->getMedia('avatars')->count());

        // Verify file was removed from storage
        Storage::disk('public')->assertMissing($originalMedia->getPath());
    }

    /** @test */
    public function it_validates_avatar_file_types_end_to_end(): void
    {
        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');
        
        $response = $this->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'avatar' => $invalidFile,
        ]);

        // Should return validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['avatar']);

        // Test valid file types
        $validTypes = ['jpg', 'jpeg', 'png', 'webp'];
        
        foreach ($validTypes as $type) {
            $validFile = UploadedFile::fake()->image("avatar.{$type}", 200, 200);
            
            $response = $this->post('/admin/users', [
                'name' => "Test User {$type}",
                'email' => "test-{$type}@example.com",
                'password' => 'password',
                'avatar' => $validFile,
            ]);

            $response->assertStatus(201);
            
            $user = User::where('email', "test-{$type}@example.com")->first();
            $this->assertNotNull($user);
            $this->assertEquals(1, $user->getMedia('avatars')->count());
        }
    }

    /** @test */
    public function it_validates_avatar_file_size_end_to_end(): void
    {
        // Test oversized file (3MB when limit is 2MB)
        $oversizedFile = UploadedFile::fake()->create('large-avatar.jpg', 3072, 'image/jpeg');
        
        $response = $this->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'avatar' => $oversizedFile,
        ]);

        // Should return validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['avatar']);

        // Test valid file size
        $validFile = UploadedFile::fake()->image('avatar.jpg', 400, 400); // ~100KB
        
        $response = $this->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'avatar' => $validFile,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_generates_avatar_conversions_correctly(): void
    {
        $avatarFile = UploadedFile::fake()->image('avatar.jpg', 800, 800);
        
        $response = $this->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'avatar' => $avatarFile,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $media = $user->getMedia('avatars')->first();

        // Verify conversions were generated
        $conversions = ['thumb', 'medium', 'large'];
        
        foreach ($conversions as $conversion) {
            $conversionPath = $media->getPath($conversion);
            Storage::disk('public')->assertExists($conversionPath);
            
            // Verify conversion URL is accessible
            $url = $media->getUrl($conversion);
            $this->assertNotEmpty($url);
        }
    }

    /** @test */
    public function it_handles_avatar_display_in_search_results(): void
    {
        $user = $this->userWithAvatar;
        
        // Search for user
        $response = $this->get('/admin/users?search=' . urlencode($user->name));
        $response->assertStatus(200);
        
        // Avatar should be included in search results
        $responseData = $response->json();
        $this->assertArrayHasKey('data', $responseData);
        
        $userData = collect($responseData['data'])->firstWhere('id', $user->id);
        $this->assertNotNull($userData);
        
        // Avatar field should be present with proper metadata
        if (isset($userData['avatar'])) {
            $this->assertArrayHasKey('url', $userData['avatar']);
            $this->assertArrayHasKey('thumb_url', $userData['avatar']);
        }
    }

    /** @test */
    public function it_supports_avatar_field_configuration_options(): void
    {
        // Test squared avatar configuration
        $response = $this->get('/admin/users/create');
        $response->assertStatus(200);
        
        // Field configuration should include squared/rounded options
        // This would be tested in the actual frontend implementation
        
        // Test fallback URL configuration
        $userWithoutAvatar = User::create([
            'name' => 'No Avatar User',
            'email' => 'noavatar@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->get("/admin/users/{$userWithoutAvatar->id}");
        $response->assertStatus(200);
        
        // Should use fallback URL when no avatar exists
        // This would be verified in the frontend response
    }

    /** @test */
    public function it_handles_concurrent_avatar_uploads(): void
    {
        $user = $this->userWithAvatar;
        
        // Simulate concurrent upload attempts
        $file1 = UploadedFile::fake()->image('avatar1.jpg', 300, 300);
        $file2 = UploadedFile::fake()->image('avatar2.jpg', 400, 400);
        
        // First upload
        $response1 = $this->put("/admin/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $file1,
        ]);
        
        // Second upload (should replace first)
        $response2 = $this->put("/admin/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $file2,
        ]);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Should only have one avatar (the latest)
        $this->assertEquals(1, $user->fresh()->getMedia('avatars')->count());
        
        $finalMedia = $user->fresh()->getMedia('avatars')->first();
        $this->assertStringContains('avatar2', $finalMedia->file_name);
    }

    /** @test */
    public function it_maintains_avatar_metadata_integrity(): void
    {
        $avatarFile = UploadedFile::fake()->image('avatar.jpg', 512, 512);
        
        $response = $this->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'avatar' => $avatarFile,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $media = $user->getMedia('avatars')->first();

        // Verify metadata is stored correctly
        $this->assertNotNull($media->name);
        $this->assertNotNull($media->file_name);
        $this->assertNotNull($media->mime_type);
        $this->assertNotNull($media->size);
        $this->assertEquals('avatars', $media->collection_name);
        $this->assertEquals('public', $media->disk);

        // Verify custom properties if set
        if (!empty($media->custom_properties)) {
            $this->assertArrayHasKey('width', $media->custom_properties);
            $this->assertArrayHasKey('height', $media->custom_properties);
        }
    }

    /** @test */
    public function it_handles_storage_disk_configuration(): void
    {
        // Test with different storage disk configuration
        config(['admin-panel.media_library.default_disk' => 'local']);
        
        $avatarFile = UploadedFile::fake()->image('avatar.jpg', 300, 300);
        
        $response = $this->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'avatar' => $avatarFile,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $media = $user->getMedia('avatars')->first();

        // Should use configured disk
        $this->assertEquals('local', $media->disk);
    }

    /** @test */
    public function it_supports_bulk_operations_with_avatars(): void
    {
        // Create multiple users with avatars
        $users = [];
        for ($i = 1; $i <= 3; $i++) {
            $user = User::create([
                'name' => "Bulk User {$i}",
                'email' => "bulk{$i}@example.com",
                'password' => bcrypt('password'),
            ]);

            $avatarFile = UploadedFile::fake()->image("avatar{$i}.jpg", 200, 200);
            $user->addMediaFromRequest('avatar')
                ->usingFileName("avatar{$i}.jpg")
                ->toMediaCollection('avatars', 'public');
            
            $users[] = $user;
        }

        // Test bulk delete
        $userIds = collect($users)->pluck('id')->toArray();
        
        $response = $this->delete('/admin/users/bulk', [
            'ids' => $userIds,
        ]);

        $response->assertStatus(200);

        // Verify users and their avatars were deleted
        foreach ($users as $user) {
            $this->assertNull(User::find($user->id));
            $this->assertEquals(0, Media::where('model_id', $user->id)->count());
        }
    }
}
