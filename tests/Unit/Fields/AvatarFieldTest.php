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

        $this->assertEquals('AvatarField', $field->component);
        $this->assertEquals('avatars', $field->path); // Different from Image field

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

        $meta = $field->meta();
        $this->assertTrue($meta['rounded']);
        $this->assertFalse($meta['squared']); // Should disable squared when rounded
    }

    public function test_avatar_field_squared_configuration(): void
    {
        $field = Avatar::make('Avatar')->squared();

        $meta = $field->meta();
        $this->assertTrue($meta['squared']);
        $this->assertFalse($meta['rounded']); // Should disable rounded when squared
    }

    public function test_avatar_field_nova_api_compatibility(): void
    {
        // Test basic Nova Avatar field creation
        $field = Avatar::make('Avatar');
        $this->assertInstanceOf(Avatar::class, $field);
        $this->assertEquals('Avatar', $field->name);
        $this->assertEquals('avatar', $field->attribute);

        // Test Nova's squared() method returns $this
        $squaredField = Avatar::make('Avatar')->squared();
        $this->assertInstanceOf(Avatar::class, $squaredField);
        $meta = $squaredField->meta();
        $this->assertTrue($meta['squared']);
        $this->assertFalse($meta['rounded']);

        // Test Nova's rounded() method returns $this
        $roundedField = Avatar::make('Avatar')->rounded();
        $this->assertInstanceOf(Avatar::class, $roundedField);
        $meta = $roundedField->meta();
        $this->assertTrue($meta['rounded']);
        $this->assertFalse($meta['squared']);
    }

    public function test_avatar_field_extends_image_field(): void
    {
        $field = Avatar::make('Avatar');

        // Should extend Image field
        $this->assertInstanceOf(\JTD\AdminPanel\Fields\Image::class, $field);

        // Should inherit all Image field methods
        $this->assertTrue(method_exists($field, 'width'));
        $this->assertTrue(method_exists($field, 'height'));
        $this->assertTrue(method_exists($field, 'quality'));
        $this->assertTrue(method_exists($field, 'thumbnail'));
        $this->assertTrue(method_exists($field, 'preview'));

        // Should inherit all File field methods
        $this->assertTrue(method_exists($field, 'disk'));
        $this->assertTrue(method_exists($field, 'acceptedTypes'));
        $this->assertTrue(method_exists($field, 'maxSize'));
    }

    public function test_avatar_field_accepts_same_options_as_image_field(): void
    {
        $field = Avatar::make('User Avatar')
            ->disk('avatars')
            ->width(500)
            ->height(500)
            ->quality(90)
            ->acceptedTypes('image/jpeg,image/png')
            ->maxSize(2048)
            ->required()
            ->help('Upload your profile picture');

        // Should accept all Image field options
        $this->assertEquals('avatars', $field->disk);
        $this->assertEquals(500, $field->width);
        $this->assertEquals(500, $field->height);
        $this->assertEquals(90, $field->quality);
        $this->assertEquals('image/jpeg,image/png', $field->acceptedTypes);
        $this->assertEquals(2048, $field->maxSize);
    }

    public function test_avatar_field_with_validation_rules(): void
    {
        $field = Avatar::make('Avatar')
            ->rules('required', 'image', 'dimensions:ratio=1/1');

        $this->assertEquals(['required', 'image', 'dimensions:ratio=1/1'], $field->rules);
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
