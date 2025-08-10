<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Fields\MediaLibraryFile;
use JTD\AdminPanel\Fields\MediaLibraryImage;
use JTD\AdminPanel\Fields\MediaLibraryAvatar;

/**
 * Media Library Service Provider Tests
 *
 * Tests for Media Library service provider integration including
 * configuration loading, default values, and field initialization.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MediaLibraryServiceProviderTest extends TestCase
{
    public function test_media_library_configuration_is_loaded(): void
    {
        $this->assertIsArray(config('admin-panel.media_library'));
        $this->assertArrayHasKey('default_disk', config('admin-panel.media_library'));
        $this->assertArrayHasKey('auto_cleanup', config('admin-panel.media_library'));
        $this->assertArrayHasKey('file_size_limits', config('admin-panel.media_library'));
        $this->assertArrayHasKey('default_conversions', config('admin-panel.media_library'));
        $this->assertArrayHasKey('avatar_conversions', config('admin-panel.media_library'));
        $this->assertArrayHasKey('accepted_mime_types', config('admin-panel.media_library'));
    }

    public function test_default_disk_configuration(): void
    {
        $defaultDisk = config('admin-panel.media_library.default_disk');
        $this->assertEquals('public', $defaultDisk);
    }

    public function test_file_size_limits_configuration(): void
    {
        $limits = config('admin-panel.media_library.file_size_limits');
        
        $this->assertIsArray($limits);
        $this->assertArrayHasKey('file', $limits);
        $this->assertArrayHasKey('image', $limits);
        $this->assertArrayHasKey('avatar', $limits);
        
        $this->assertEquals(10240, $limits['file']); // 10MB
        $this->assertEquals(5120, $limits['image']); // 5MB
        $this->assertEquals(2048, $limits['avatar']); // 2MB
    }

    public function test_default_conversions_configuration(): void
    {
        $conversions = config('admin-panel.media_library.default_conversions');
        
        $this->assertIsArray($conversions);
        $this->assertArrayHasKey('thumb', $conversions);
        $this->assertArrayHasKey('medium', $conversions);
        $this->assertArrayHasKey('large', $conversions);
        
        // Test thumb conversion
        $this->assertEquals(150, $conversions['thumb']['width']);
        $this->assertEquals(150, $conversions['thumb']['height']);
        $this->assertEquals('crop', $conversions['thumb']['fit']);
    }

    public function test_avatar_conversions_configuration(): void
    {
        $conversions = config('admin-panel.media_library.avatar_conversions');
        
        $this->assertIsArray($conversions);
        $this->assertArrayHasKey('thumb', $conversions);
        $this->assertArrayHasKey('medium', $conversions);
        $this->assertArrayHasKey('large', $conversions);
        
        // Test that avatar conversions are square (crop fit)
        $this->assertEquals('crop', $conversions['thumb']['fit']);
        $this->assertEquals('crop', $conversions['medium']['fit']);
        $this->assertEquals('crop', $conversions['large']['fit']);
        
        // Test avatar-specific sizes
        $this->assertEquals(64, $conversions['thumb']['width']);
        $this->assertEquals(64, $conversions['thumb']['height']);
    }

    public function test_accepted_mime_types_configuration(): void
    {
        $mimeTypes = config('admin-panel.media_library.accepted_mime_types');
        
        $this->assertIsArray($mimeTypes);
        $this->assertArrayHasKey('file', $mimeTypes);
        $this->assertArrayHasKey('image', $mimeTypes);
        $this->assertArrayHasKey('avatar', $mimeTypes);
        
        // Test file MIME types
        $this->assertContains('application/pdf', $mimeTypes['file']);
        $this->assertContains('text/plain', $mimeTypes['file']);
        
        // Test image MIME types
        $this->assertContains('image/jpeg', $mimeTypes['image']);
        $this->assertContains('image/png', $mimeTypes['image']);
        
        // Test avatar MIME types (more restrictive)
        $this->assertContains('image/jpeg', $mimeTypes['avatar']);
        $this->assertContains('image/png', $mimeTypes['avatar']);
        $this->assertNotContains('image/svg+xml', $mimeTypes['avatar']); // SVG not allowed for avatars
    }

    public function test_responsive_images_configuration(): void
    {
        $responsiveConfig = config('admin-panel.media_library.responsive_images');
        
        $this->assertIsArray($responsiveConfig);
        $this->assertArrayHasKey('enabled', $responsiveConfig);
        $this->assertArrayHasKey('breakpoints', $responsiveConfig);
        $this->assertArrayHasKey('quality', $responsiveConfig);
        
        $this->assertTrue($responsiveConfig['enabled']);
        $this->assertEquals(85, $responsiveConfig['quality']);
        
        // Test breakpoints
        $breakpoints = $responsiveConfig['breakpoints'];
        $this->assertEquals(640, $breakpoints['sm']);
        $this->assertEquals(768, $breakpoints['md']);
        $this->assertEquals(1024, $breakpoints['lg']);
        $this->assertEquals(1280, $breakpoints['xl']);
    }

    public function test_media_library_file_field_uses_configuration(): void
    {
        $field = MediaLibraryFile::make('Document');
        
        // Test that field uses configuration defaults
        $this->assertEquals('public', $field->disk);
        $this->assertEquals(10240, $field->maxFileSize);
        
        $expectedMimeTypes = config('admin-panel.media_library.accepted_mime_types.file');
        $this->assertEquals($expectedMimeTypes, $field->acceptedMimeTypes);
    }

    public function test_media_library_image_field_uses_configuration(): void
    {
        $field = MediaLibraryImage::make('Featured Image');
        
        // Test that field uses configuration defaults
        $this->assertEquals('public', $field->disk);
        $this->assertEquals(5120, $field->maxFileSize);
        $this->assertTrue($field->responsiveImages);
        
        $expectedMimeTypes = config('admin-panel.media_library.accepted_mime_types.image');
        $this->assertEquals($expectedMimeTypes, $field->acceptedMimeTypes);
        
        $expectedConversions = config('admin-panel.media_library.default_conversions');
        $this->assertEquals($expectedConversions, $field->conversions);
    }

    public function test_media_library_avatar_field_uses_configuration(): void
    {
        $field = MediaLibraryAvatar::make('Profile Picture');
        
        // Test that field uses configuration defaults
        $this->assertEquals('public', $field->disk);
        $this->assertEquals(2048, $field->maxFileSize);
        $this->assertTrue($field->singleFile);
        $this->assertFalse($field->multiple);
        
        $expectedMimeTypes = config('admin-panel.media_library.accepted_mime_types.avatar');
        $this->assertEquals($expectedMimeTypes, $field->acceptedMimeTypes);
        
        $expectedConversions = config('admin-panel.media_library.avatar_conversions');
        $this->assertEquals($expectedConversions, $field->conversions);
    }

    public function test_field_configuration_can_be_overridden(): void
    {
        $field = MediaLibraryFile::make('Document')
            ->disk('s3')
            ->maxFileSize(20480)
            ->acceptsMimeTypes(['application/pdf']);
        
        // Test that configuration can be overridden
        $this->assertEquals('s3', $field->disk);
        $this->assertEquals(20480, $field->maxFileSize);
        $this->assertEquals(['application/pdf'], $field->acceptedMimeTypes);
    }

    public function test_auto_cleanup_configuration(): void
    {
        $autoCleanup = config('admin-panel.media_library.auto_cleanup');
        $this->assertTrue($autoCleanup);
    }

    public function test_configuration_environment_variables(): void
    {
        // Test that configuration respects environment variables
        $this->assertEquals(
            env('ADMIN_PANEL_MEDIA_DISK', 'public'),
            config('admin-panel.media_library.default_disk')
        );
        
        $this->assertEquals(
            env('ADMIN_PANEL_MEDIA_AUTO_CLEANUP', true),
            config('admin-panel.media_library.auto_cleanup')
        );
        
        $this->assertEquals(
            env('ADMIN_PANEL_MAX_FILE_SIZE', 10240),
            config('admin-panel.media_library.file_size_limits.file')
        );
        
        $this->assertEquals(
            env('ADMIN_PANEL_RESPONSIVE_IMAGES', true),
            config('admin-panel.media_library.responsive_images.enabled')
        );
    }
}
