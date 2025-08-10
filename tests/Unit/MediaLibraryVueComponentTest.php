<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Support\Facades\File;

/**
 * Media Library Vue Component Tests
 *
 * Tests for Media Library Vue components including file existence,
 * structure validation, and component registration.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MediaLibraryVueComponentTest extends TestCase
{
    protected string $componentsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->componentsPath = __DIR__ . '/../../resources/js/components/Fields';
    }

    public function test_media_library_file_field_component_exists(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryFileField.vue';

        $this->assertTrue(
            File::exists($componentPath),
            'MediaLibraryFileField.vue component should exist'
        );
    }

    public function test_media_library_file_field_component_has_valid_structure(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryFileField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryFileField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for required Vue SFC sections
        $this->assertStringContains('<template>', $content, 'Component should have template section');
        $this->assertStringContains('<script setup>', $content, 'Component should have script setup section');
        $this->assertStringContains('<style scoped>', $content, 'Component should have scoped style section');

        // Check for required imports
        $this->assertStringContains('import BaseField from \'./BaseField.vue\'', $content, 'Component should import BaseField');
        $this->assertStringContains('import { useAdminStore }', $content, 'Component should import admin store');

        // Check for required props
        $this->assertStringContains('field:', $content, 'Component should define field prop');
        $this->assertStringContains('modelValue:', $content, 'Component should define modelValue prop');
        $this->assertStringContains('errors:', $content, 'Component should define errors prop');

        // Check for required functionality
        $this->assertStringContains('drag', $content, 'Component should support drag and drop');
        $this->assertStringContains('upload', $content, 'Component should support file upload');
        $this->assertStringContains('progress', $content, 'Component should show upload progress');
    }

    public function test_media_library_file_field_component_has_proper_props(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryFileField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryFileField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for standard field props
        $requiredProps = [
            'field:',
            'modelValue:',
            'errors:',
            'disabled:',
            'readonly:',
            'size:'
        ];

        foreach ($requiredProps as $prop) {
            $this->assertStringContains($prop, $content, "Component should define {$prop} prop");
        }
    }

    public function test_media_library_file_field_component_has_proper_emits(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryFileField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryFileField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for standard field emits
        $this->assertStringContains('update:modelValue', $content, 'Component should emit update:modelValue');
        $this->assertStringContains('change', $content, 'Component should emit change');
    }

    public function test_media_library_file_field_component_has_drag_drop_functionality(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryFileField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryFileField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for drag and drop event handlers
        $dragDropFeatures = [
            '@dragover.prevent',
            '@dragleave.prevent',
            '@drop.prevent',
            'handleDragOver',
            'handleDragLeave',
            'handleDrop'
        ];

        foreach ($dragDropFeatures as $feature) {
            $this->assertStringContains($feature, $content, "Component should have {$feature} for drag and drop");
        }
    }

    public function test_media_library_file_field_component_has_file_validation(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryFileField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryFileField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for file validation features
        $validationFeatures = [
            'validateFile',
            'maxFileSize',
            'acceptedMimeTypes',
            'uploadError'
        ];

        foreach ($validationFeatures as $feature) {
            $this->assertStringContains($feature, $content, "Component should have {$feature} for file validation");
        }
    }

    public function test_media_library_file_field_component_has_progress_indication(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryFileField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryFileField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for progress indication features
        $progressFeatures = [
            'uploadProgress',
            'Uploading...',
            'bg-blue-600'
        ];

        foreach ($progressFeatures as $feature) {
            $this->assertStringContains($feature, $content, "Component should have {$feature} for progress indication");
        }
    }

    public function test_media_library_file_field_component_has_file_management(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryFileField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryFileField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for file management features
        $managementFeatures = [
            'removeFile',
            'downloadFile',
            'existingFiles',
            'formatFileSize'
        ];

        foreach ($managementFeatures as $feature) {
            $this->assertStringContains($feature, $content, "Component should have {$feature} for file management");
        }
    }

    public function test_media_library_file_field_component_uses_heroicons(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryFileField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryFileField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for Heroicons usage (consistent with other components)
        $icons = [
            'DocumentIcon',
            'CloudArrowUpIcon',
            'ArrowDownTrayIcon',
            'XMarkIcon'
        ];

        foreach ($icons as $icon) {
            $this->assertStringContains($icon, $content, "Component should use {$icon} from Heroicons");
        }
    }

    // ========================================
    // MediaLibraryImageField Component Tests
    // ========================================

    public function test_media_library_image_field_component_exists(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryImageField.vue';

        $this->assertTrue(
            File::exists($componentPath),
            'MediaLibraryImageField.vue component should exist'
        );
    }

    public function test_media_library_image_field_component_has_valid_structure(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryImageField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryImageField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for required Vue SFC sections
        $this->assertStringContains('<template>', $content, 'Component should have template section');
        $this->assertStringContains('<script setup>', $content, 'Component should have script setup section');
        $this->assertStringContains('<style scoped>', $content, 'Component should have scoped style section');

        // Check for required imports
        $this->assertStringContains('import BaseField from \'./BaseField.vue\'', $content, 'Component should import BaseField');
        $this->assertStringContains('import { useAdminStore }', $content, 'Component should import admin store');

        // Check for image-specific functionality
        $this->assertStringContains('gallery', $content, 'Component should support gallery view');
        $this->assertStringContains('lightbox', $content, 'Component should support lightbox');
        $this->assertStringContains('preview', $content, 'Component should support image preview');
    }

    public function test_media_library_image_field_component_has_gallery_features(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryImageField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryImageField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for gallery features
        $galleryFeatures = [
            'grid grid-cols',
            'aspect-square',
            'existingImages',
            'getImagePreviewUrl',
            'removeImage'
        ];

        foreach ($galleryFeatures as $feature) {
            $this->assertStringContains($feature, $content, "Component should have {$feature} for gallery functionality");
        }
    }

    public function test_media_library_image_field_component_has_lightbox_features(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryImageField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryImageField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for lightbox features
        $lightboxFeatures = [
            'openLightbox',
            'closeLightbox',
            'navigateLightbox',
            'lightboxImage',
            'lightboxIndex'
        ];

        foreach ($lightboxFeatures as $feature) {
            $this->assertStringContains($feature, $content, "Component should have {$feature} for lightbox functionality");
        }
    }

    public function test_media_library_image_field_component_has_image_validation(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryImageField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryImageField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for image-specific validation
        $validationFeatures = [
            'image/',
            'imageFiles',
            'filter(file => file.type.startsWith(\'image/\'))',
            'Please select valid image files'
        ];

        foreach ($validationFeatures as $feature) {
            $this->assertStringContains($feature, $content, "Component should have {$feature} for image validation");
        }
    }

    public function test_media_library_image_field_component_has_limit_support(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryImageField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryImageField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for limit functionality
        $limitFeatures = [
            'field.limit',
            'Cannot upload more than',
            'of {{ field.limit }} images'
        ];

        foreach ($limitFeatures as $feature) {
            $this->assertStringContains($feature, $content, "Component should have {$feature} for limit functionality");
        }
    }

    public function test_media_library_image_field_component_uses_image_icons(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryImageField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryImageField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for image-specific icons
        $icons = [
            'PhotoIcon',
            'EyeIcon',
            'ChevronLeftIcon',
            'ChevronRightIcon'
        ];

        foreach ($icons as $icon) {
            $this->assertStringContains($icon, $content, "Component should use {$icon} from Heroicons");
        }
    }

    // ========================================
    // MediaLibraryAvatarField Component Tests
    // ========================================

    public function test_media_library_avatar_field_component_exists(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryAvatarField.vue';

        $this->assertTrue(
            File::exists($componentPath),
            'MediaLibraryAvatarField.vue component should exist'
        );
    }

    public function test_media_library_avatar_field_component_has_valid_structure(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryAvatarField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryAvatarField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for required Vue SFC sections
        $this->assertStringContains('<template>', $content, 'Component should have template section');
        $this->assertStringContains('<script setup>', $content, 'Component should have script setup section');
        $this->assertStringContains('<style scoped>', $content, 'Component should have scoped style section');

        // Check for required imports
        $this->assertStringContains('import BaseField from \'./BaseField.vue\'', $content, 'Component should import BaseField');
        $this->assertStringContains('import { useAdminStore }', $content, 'Component should import admin store');

        // Check for avatar-specific functionality
        $this->assertStringContains('avatar', $content, 'Component should support avatar functionality');
        $this->assertStringContains('rounded-full', $content, 'Component should support circular preview');
        $this->assertStringContains('fallback', $content, 'Component should support fallback images');
    }

    public function test_media_library_avatar_field_component_has_avatar_features(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryAvatarField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryAvatarField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for avatar-specific features
        $avatarFeatures = [
            'avatar-container',
            'avatar-image',
            'avatar-overlay',
            'currentAvatarUrl',
            'hasCurrentAvatar',
            'removeAvatar'
        ];

        foreach ($avatarFeatures as $feature) {
            $this->assertStringContains($feature, $content, "Component should have {$feature} for avatar functionality");
        }
    }

    public function test_media_library_avatar_field_component_has_single_file_upload(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryAvatarField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryAvatarField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for single file upload features
        $singleFileFeatures = [
            'handleFile(files[0])',
            'single file upload',
            'Replace avatar',
            'Upload avatar'
        ];

        foreach ($singleFileFeatures as $feature) {
            $this->assertStringContains($feature, $content, "Component should have {$feature} for single file upload");
        }
    }

    public function test_media_library_avatar_field_component_has_fallback_support(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryAvatarField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryAvatarField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for fallback support
        $fallbackFeatures = [
            'fallbackUrl',
            'default-avatar.png',
            'handleImageError',
            'No avatar uploaded'
        ];

        foreach ($fallbackFeatures as $feature) {
            $this->assertStringContains($feature, $content, "Component should have {$feature} for fallback support");
        }
    }

    public function test_media_library_avatar_field_component_has_size_variants(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryAvatarField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryAvatarField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for size variants
        $sizeFeatures = [
            'avatar-container-small',
            'avatar-container-large',
            'size === \'small\'',
            'size === \'large\'',
            'Available sizes:'
        ];

        foreach ($sizeFeatures as $feature) {
            $this->assertStringContains($feature, $content, "Component should have {$feature} for size variants");
        }
    }

    public function test_media_library_avatar_field_component_uses_avatar_icons(): void
    {
        $componentPath = $this->componentsPath . '/MediaLibraryAvatarField.vue';

        if (!File::exists($componentPath)) {
            $this->markTestSkipped('MediaLibraryAvatarField.vue component does not exist');
        }

        $content = File::get($componentPath);

        // Check for avatar-specific icons
        $icons = [
            'UserCircleIcon',
            'CameraIcon',
            'XMarkIcon'
        ];

        foreach ($icons as $icon) {
            $this->assertStringContains($icon, $content, "Component should use {$icon} from Heroicons");
        }
    }
}
