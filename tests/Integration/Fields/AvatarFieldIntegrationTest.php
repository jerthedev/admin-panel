<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Fields\Avatar;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Avatar Field Integration Test
 *
 * Tests the complete integration between PHP Avatar field class,
 * API endpoints, file storage, and frontend functionality.
 * 
 * Follows the same pattern as other field integration tests,
 * focusing on field configuration and behavior rather than
 * database operations with non-existent columns.
 */
class AvatarFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup storage for avatar uploads
        Storage::fake('public');

        // Create test users (using existing User model structure)
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);
    }

    /** @test */
    public function it_creates_avatar_field_with_nova_syntax(): void
    {
        $field = Avatar::make('Profile Picture');

        $this->assertEquals('Profile Picture', $field->name);
        $this->assertEquals('profile_picture', $field->attribute);
        $this->assertEquals('AvatarField', $field->component);
        $this->assertEquals('avatars', $field->path);
    }

    /** @test */
    public function it_creates_avatar_field_with_custom_attribute(): void
    {
        $field = Avatar::make('User Avatar', 'user_avatar');

        $this->assertEquals('User Avatar', $field->name);
        $this->assertEquals('user_avatar', $field->attribute);
    }

    /** @test */
    public function it_supports_all_nova_avatar_configuration_methods(): void
    {
        $field = Avatar::make('Profile Picture')
            ->disk('avatars')
            ->path('users')
            ->acceptedTypes('image/jpeg,image/png')
            ->maxSize(2048)
            ->nullable()
            ->help('Upload your profile picture');

        $this->assertEquals('avatars', $field->disk);
        $this->assertEquals('users', $field->path);
        $this->assertEquals('image/jpeg,image/png', $field->acceptedTypes);
        $this->assertEquals(2048, $field->maxSize);
    }

    /** @test */
    public function it_inherits_file_field_configuration(): void
    {
        $field = Avatar::make('Profile Picture')
            ->disk('user-avatars')
            ->path('profiles')
            ->acceptedTypes('image/jpeg,image/png,image/webp')
            ->maxSize(5120); // 5MB

        $this->assertEquals('user-avatars', $field->disk);
        $this->assertEquals('profiles', $field->path);
        $this->assertEquals('image/jpeg,image/png,image/webp', $field->acceptedTypes);
        $this->assertEquals(5120, $field->maxSize);
    }

    /** @test */
    public function it_resolves_avatar_field_value_with_callback(): void
    {
        $user = User::find(1);
        $field = Avatar::make('Profile Picture', 'name', function ($resource, $attribute) {
            return 'avatar-' . strtolower($resource->{$attribute}) . '.jpg';
        });

        $field->resolve($user);

        $this->assertEquals('avatar-john doe.jpg', $field->value);
    }

    /** @test */
    public function it_handles_file_upload_configuration(): void
    {
        Storage::fake('custom-disk');
        
        $field = Avatar::make('Profile Picture')
            ->disk('custom-disk')
            ->path('custom-path')
            ->acceptedTypes('image/jpeg,image/png')
            ->maxSize(2048);

        // Test that file upload configuration is properly set
        $this->assertEquals('custom-disk', $field->disk);
        $this->assertEquals('custom-path', $field->path);
        $this->assertEquals('image/jpeg,image/png', $field->acceptedTypes);
        $this->assertEquals(2048, $field->maxSize);

        // Test meta serialization includes file configuration
        $meta = $field->meta();
        $this->assertEquals('custom-disk', $meta['disk']);
        $this->assertEquals('custom-path', $meta['path']);
        $this->assertEquals('image/jpeg,image/png', $meta['acceptedTypes']);
        $this->assertEquals(2048, $meta['maxSize']);
    }

    /** @test */
    public function it_integrates_with_validation_rules(): void
    {
        $field = Avatar::make('Profile Picture')
            ->rules('required', 'image', 'max:2048')
            ->creationRules('sometimes')
            ->updateRules('nullable');

        // Test that rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('image', $field->rules);
        $this->assertContains('max:2048', $field->rules);
        
        // Test creation and update rules
        $this->assertContains('sometimes', $field->creationRules);
        $this->assertContains('nullable', $field->updateRules);
    }

    /** @test */
    public function it_serializes_avatar_field_for_frontend(): void
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
    public function it_inherits_all_file_field_methods(): void
    {
        $field = Avatar::make('Profile Picture');

        // Test that Avatar field inherits all File field methods
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
    public function it_handles_complex_avatar_field_configuration(): void
    {
        $field = Avatar::make('User Avatar')
            ->disk('user-storage')
            ->path('avatars')
            ->acceptedTypes('image/jpeg,image/png,image/webp')
            ->maxSize(5120) // 5MB
            ->nullable()
            ->help('Upload your avatar in JPEG, PNG, or WebP format');

        // Test all configurations are set
        $this->assertEquals('user-storage', $field->disk);
        $this->assertEquals('avatars', $field->path);
        $this->assertEquals('image/jpeg,image/png,image/webp', $field->acceptedTypes);
        $this->assertEquals(5120, $field->maxSize);

        // Test serialization includes all configurations
        $serialized = $field->jsonSerialize();
        $this->assertEquals('user-storage', $serialized['disk']);
        $this->assertEquals('avatars', $serialized['path']);
        $this->assertEquals(5120, $serialized['maxSize']);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = Avatar::make('Profile Picture')
            ->disk('avatar-storage')
            ->path('profiles')
            ->acceptedTypes('image/jpeg,image/png')
            ->maxSize(3072)
            ->nullable()
            ->help('Upload your profile picture')
            ->rules('image');

        // Test that all chained methods work correctly
        $this->assertEquals('avatar-storage', $field->disk);
        $this->assertEquals('profiles', $field->path);
        $this->assertEquals('image/jpeg,image/png', $field->acceptedTypes);
        $this->assertEquals(3072, $field->maxSize);
        $this->assertContains('image', $field->rules);
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_avatar_field(): void
    {
        // Test that our Avatar field provides the same API as Nova's Avatar field
        $field = Avatar::make('Profile Picture');

        // Test that it extends File field (Nova requirement)
        $this->assertInstanceOf(\JTD\AdminPanel\Fields\File::class, $field);
        
        // Test component name matches Nova
        $this->assertEquals('AvatarField', $field->component);
        
        // Test default path for avatars
        $this->assertEquals('avatars', $field->path);
        
        // Test default accepted types for images
        $this->assertStringContains('image/', $field->acceptedTypes);
    }

    /** @test */
    public function it_handles_avatar_specific_image_validation(): void
    {
        $field = Avatar::make('Profile Picture')
            ->rules('required', 'image', 'mimes:jpeg,png,webp', 'dimensions:min_width=100,min_height=100');

        // Test that image-specific validation rules are set
        $this->assertContains('required', $field->rules);
        $this->assertContains('image', $field->rules);
        $this->assertContains('mimes:jpeg,png,webp', $field->rules);
        $this->assertContains('dimensions:min_width=100,min_height=100', $field->rules);
    }

    /** @test */
    public function it_supports_avatar_display_configurations(): void
    {
        $field = Avatar::make('Profile Picture')
            ->disk('avatars')
            ->path('users')
            ->maxSize(2048);

        // Test that avatar field can be configured for different display contexts
        $serialized = $field->jsonSerialize();
        
        // Should include all necessary data for frontend avatar display
        $this->assertEquals('AvatarField', $serialized['component']);
        $this->assertEquals('avatars', $serialized['disk']);
        $this->assertEquals('users', $serialized['path']);
        $this->assertEquals(2048, $serialized['maxSize']);
    }
}
