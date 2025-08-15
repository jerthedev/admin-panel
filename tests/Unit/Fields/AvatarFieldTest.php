<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Avatar;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Avatar Field Unit Tests
 *
 * Tests for Avatar field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class AvatarFieldTest extends TestCase
{
    public function test_avatar_field_creation(): void
    {
        $field = Avatar::make('Avatar');

        $this->assertEquals('Avatar', $field->name);
        $this->assertEquals('avatar', $field->attribute);
        $this->assertEquals('AvatarField', $field->component);
    }

    public function test_avatar_field_with_custom_attribute(): void
    {
        $field = Avatar::make('Profile Picture', 'profile_picture');

        $this->assertEquals('Profile Picture', $field->name);
        $this->assertEquals('profile_picture', $field->attribute);
    }

    public function test_avatar_field_default_properties(): void
    {
        $field = Avatar::make('Avatar');

        $this->assertEquals('avatars', $field->path); // Different from Image field
        $this->assertFalse($field->rounded);
        $this->assertEquals(80, $field->size);
        $this->assertFalse($field->showInIndex);

        // Should inherit squared from constructor
        $this->assertTrue($field->squared);

        // Should have default avatar settings
        $this->assertEquals('image/jpeg,image/jpg,image/png,image/webp,.jpg,.jpeg,.png,.webp', $field->acceptedTypes);
        $this->assertEquals(400, $field->width);
        $this->assertEquals(400, $field->height);
        $this->assertEquals(85, $field->quality);
    }

    public function test_avatar_field_inherits_image_properties(): void
    {
        $field = Avatar::make('Avatar');

        // Should inherit Image and File field properties
        $this->assertEquals('public', $field->disk);
        $this->assertNull($field->maxSize);
        $this->assertFalse($field->multiple);
        $this->assertNull($field->thumbnailCallback);
        $this->assertNull($field->previewCallback);
    }

    public function test_avatar_field_rounded_configuration(): void
    {
        $field = Avatar::make('Avatar')->rounded();

        $this->assertTrue($field->rounded);
        $this->assertFalse($field->squared); // Should disable squared when rounded
    }

    public function test_avatar_field_rounded_false(): void
    {
        $field = Avatar::make('Avatar')->rounded(false);

        $this->assertFalse($field->rounded);
    }

    public function test_avatar_field_squared_configuration(): void
    {
        $field = Avatar::make('Avatar')->squared();

        $this->assertTrue($field->squared);
        $this->assertFalse($field->rounded); // Should disable rounded when squared
    }

    public function test_avatar_field_squared_false(): void
    {
        $field = Avatar::make('Avatar')->squared(false);

        $this->assertFalse($field->squared);
    }

    public function test_avatar_field_size_configuration(): void
    {
        $field = Avatar::make('Avatar')->size(120);

        $this->assertEquals(120, $field->size);
    }

    public function test_avatar_field_show_in_index_configuration(): void
    {
        $field = Avatar::make('Avatar')->showInIndex();

        $this->assertTrue($field->showInIndex);
    }

    public function test_avatar_field_show_in_index_false(): void
    {
        $field = Avatar::make('Avatar')->showInIndex(false);

        $this->assertFalse($field->showInIndex);
    }

    public function test_avatar_field_rounded_disables_squared(): void
    {
        $field = Avatar::make('Avatar')
            ->squared() // Start with squared
            ->rounded(); // Then set rounded

        $this->assertTrue($field->rounded);
        $this->assertFalse($field->squared);
    }

    public function test_avatar_field_squared_disables_rounded(): void
    {
        $field = Avatar::make('Avatar')
            ->rounded() // Start with rounded
            ->squared(); // Then set squared

        $this->assertTrue($field->squared);
        $this->assertFalse($field->rounded);
    }

    public function test_avatar_field_meta_includes_all_properties(): void
    {
        $field = Avatar::make('Avatar')
            ->rounded()
            ->size(100)
            ->showInIndex();

        $meta = $field->meta();

        $this->assertArrayHasKey('rounded', $meta);
        $this->assertArrayHasKey('size', $meta);
        $this->assertArrayHasKey('showInIndex', $meta);
        $this->assertTrue($meta['rounded']);
        $this->assertEquals(100, $meta['size']);
        $this->assertTrue($meta['showInIndex']);

        // Should also include parent Image meta
        $this->assertArrayHasKey('squared', $meta);
        $this->assertArrayHasKey('width', $meta);
        $this->assertArrayHasKey('height', $meta);
        $this->assertArrayHasKey('quality', $meta);
    }

    public function test_avatar_field_json_serialization(): void
    {
        $field = Avatar::make('User Avatar')
            ->disk('avatars')
            ->rounded()
            ->size(150)
            ->showInIndex()
            ->width(500)
            ->height(500)
            ->quality(90)
            ->required()
            ->help('Upload your profile picture');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Avatar', $json['name']);
        $this->assertEquals('user_avatar', $json['attribute']);
        $this->assertEquals('AvatarField', $json['component']);
        $this->assertEquals('avatars', $json['disk']);
        $this->assertTrue($json['rounded']);
        $this->assertFalse($json['squared']); // Should be false when rounded
        $this->assertEquals(150, $json['size']);
        $this->assertTrue($json['showInIndex']);
        $this->assertEquals(500, $json['width']);
        $this->assertEquals(500, $json['height']);
        $this->assertEquals(90, $json['quality']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Upload your profile picture', $json['helpText']);
    }

    public function test_avatar_field_inheritance_from_image(): void
    {
        $field = Avatar::make('Avatar');

        // Test that Avatar field inherits all Image field functionality
        $this->assertTrue(method_exists($field, 'width'));
        $this->assertTrue(method_exists($field, 'height'));
        $this->assertTrue(method_exists($field, 'quality'));
        $this->assertTrue(method_exists($field, 'thumbnail'));
        $this->assertTrue(method_exists($field, 'preview'));
        $this->assertTrue(method_exists($field, 'getThumbnailUrl'));
        $this->assertTrue(method_exists($field, 'getPreviewUrl'));

        // And File field functionality
        $this->assertTrue(method_exists($field, 'disk'));
        $this->assertTrue(method_exists($field, 'path'));
        $this->assertTrue(method_exists($field, 'acceptedTypes'));
        $this->assertTrue(method_exists($field, 'maxSize'));
        $this->assertTrue(method_exists($field, 'getUrl'));
    }

    public function test_avatar_field_with_validation_rules(): void
    {
        $field = Avatar::make('Avatar')
            ->rules('required', 'image', 'dimensions:ratio=1/1');

        $this->assertEquals(['required', 'image', 'dimensions:ratio=1/1'], $field->rules);
    }

    public function test_avatar_field_constructor_sets_defaults(): void
    {
        $field = Avatar::make('Avatar');

        // Test that constructor sets avatar-specific defaults
        $this->assertTrue($field->squared);
        $this->assertEquals('image/jpeg,image/jpg,image/png,image/webp,.jpg,.jpeg,.png,.webp', $field->acceptedTypes);
        $this->assertEquals(400, $field->width);
        $this->assertEquals(400, $field->height);
        $this->assertEquals(85, $field->quality);
        $this->assertEquals('avatars', $field->path);
    }

    public function test_avatar_field_complex_configuration(): void
    {
        $field = Avatar::make('Team Member Avatar')
            ->disk('team')
            ->path('team-avatars')
            ->rounded()
            ->size(200)
            ->showInIndex()
            ->width(600)
            ->height(600)
            ->quality(95)
            ->maxSize(2048);

        // Test all configurations are set
        $this->assertEquals('team', $field->disk);
        $this->assertEquals('team-avatars', $field->path);
        $this->assertTrue($field->rounded);
        $this->assertFalse($field->squared);
        $this->assertEquals(200, $field->size);
        $this->assertTrue($field->showInIndex);
        $this->assertEquals(600, $field->width);
        $this->assertEquals(600, $field->height);
        $this->assertEquals(95, $field->quality);
        $this->assertEquals(2048, $field->maxSize);
    }

    public function test_avatar_field_resolve_preserves_value(): void
    {
        $field = Avatar::make('Avatar');
        $resource = (object) ['avatar' => 'avatars/user-123.jpg'];

        $field->resolve($resource);

        $this->assertEquals('avatars/user-123.jpg', $field->value);
    }

    public function test_avatar_field_with_callback(): void
    {
        $field = Avatar::make('Avatar', null, function ($resource, $attribute) {
            return 'custom-avatar-' . $resource->{$attribute};
        });

        $resource = (object) ['avatar' => 'test.jpg'];

        $field->resolve($resource);

        $this->assertEquals('custom-avatar-test.jpg', $field->value);
    }
}
