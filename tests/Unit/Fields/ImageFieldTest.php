<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Image;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Image Field Unit Tests
 *
 * Tests for Image field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ImageFieldTest extends TestCase
{
    public function test_image_field_creation(): void
    {
        $field = Image::make('Profile Picture');

        $this->assertEquals('Profile Picture', $field->name);
        $this->assertEquals('profile_picture', $field->attribute);
        $this->assertEquals('ImageField', $field->component);
    }

    public function test_image_field_with_custom_attribute(): void
    {
        $field = Image::make('Banner Image', 'banner_image');

        $this->assertEquals('Banner Image', $field->name);
        $this->assertEquals('banner_image', $field->attribute);
    }

    public function test_image_field_default_properties(): void
    {
        $field = Image::make('Image');

        $this->assertEquals('images', $field->path); // Different from File field
        $this->assertFalse($field->squared);
        $this->assertNull($field->thumbnailCallback);
        $this->assertNull($field->previewCallback);
        $this->assertNull($field->width);
        $this->assertNull($field->height);
        $this->assertEquals(90, $field->quality); // Set in constructor
    }

    public function test_image_field_inherits_file_properties(): void
    {
        $field = Image::make('Image');

        // Should inherit File field properties
        $this->assertEquals('public', $field->disk);
        $this->assertEquals('image/*,.jpg,.jpeg,.png,.gif,.webp', $field->acceptedTypes); // Set in constructor
        $this->assertNull($field->maxSize);
        $this->assertFalse($field->multiple);
    }

    public function test_image_field_squared_configuration(): void
    {
        $field = Image::make('Image')->squared();

        $this->assertTrue($field->squared);
    }

    public function test_image_field_squared_false(): void
    {
        $field = Image::make('Image')->squared(false);

        $this->assertFalse($field->squared);
    }

    public function test_image_field_width_height_configuration(): void
    {
        $field = Image::make('Image')->width(800)->height(600);

        $this->assertEquals(800, $field->width);
        $this->assertEquals(600, $field->height);
    }

    public function test_image_field_quality_configuration(): void
    {
        $field = Image::make('Image')->quality(90);

        $this->assertEquals(90, $field->quality);
    }

    public function test_image_field_thumbnail_callback_configuration(): void
    {
        $callback = function ($path) {
            return 'thumbnail-' . $path;
        };

        $field = Image::make('Image')->thumbnail($callback);

        $this->assertEquals($callback, $field->thumbnailCallback);
    }

    public function test_image_field_preview_callback_configuration(): void
    {
        $callback = function ($path) {
            return 'preview-' . $path;
        };

        $field = Image::make('Image')->preview($callback);

        $this->assertEquals($callback, $field->previewCallback);
    }

    public function test_image_field_get_thumbnail_url_with_callback(): void
    {
        $callback = function () {
            return 'https://example.com/thumbnails/image.jpg';
        };

        $field = Image::make('Image')->thumbnail($callback);

        $url = $field->getThumbnailUrl('image.jpg');

        $this->assertEquals('https://example.com/thumbnails/image.jpg', $url);
    }

    public function test_image_field_get_thumbnail_url_without_callback(): void
    {
        $field = Image::make('Image');

        $url = $field->getThumbnailUrl('image.jpg');

        // Without callback, it returns the storage URL
        $this->assertStringContains('image.jpg', $url);
    }

    public function test_image_field_get_preview_url_with_callback(): void
    {
        $callback = function () {
            return 'https://example.com/previews/image.jpg';
        };

        $field = Image::make('Image')->preview($callback);

        $url = $field->getPreviewUrl('image.jpg');

        $this->assertEquals('https://example.com/previews/image.jpg', $url);
    }

    public function test_image_field_get_preview_url_without_callback(): void
    {
        $field = Image::make('Image');

        $url = $field->getPreviewUrl('image.jpg');

        // Without callback, it returns the storage URL
        $this->assertStringContains('image.jpg', $url);
    }

    public function test_image_field_meta_includes_all_properties(): void
    {
        $thumbnailCallback = function () { return 'thumb-url'; };
        $previewCallback = function () { return 'preview-url'; };

        $field = Image::make('Image')
            ->squared()
            ->width(1200)
            ->height(800)
            ->quality(85)
            ->thumbnail($thumbnailCallback)
            ->preview($previewCallback);

        $meta = $field->meta();

        $this->assertArrayHasKey('squared', $meta);
        $this->assertArrayHasKey('width', $meta);
        $this->assertArrayHasKey('height', $meta);
        $this->assertArrayHasKey('quality', $meta);
        $this->assertTrue($meta['squared']);
        $this->assertEquals(1200, $meta['width']);
        $this->assertEquals(800, $meta['height']);
        $this->assertEquals(85, $meta['quality']);
    }

    public function test_image_field_json_serialization(): void
    {
        $field = Image::make('Product Image')
            ->disk('products')
            ->path('product-images')
            ->squared()
            ->width(800)
            ->height(600)
            ->quality(90)
            ->acceptedTypes('image/jpeg,image/png')
            ->maxSize(5120)
            ->required()
            ->help('Upload product image');

        $json = $field->jsonSerialize();

        $this->assertEquals('Product Image', $json['name']);
        $this->assertEquals('product_image', $json['attribute']);
        $this->assertEquals('ImageField', $json['component']);
        $this->assertEquals('products', $json['disk']);
        $this->assertEquals('product-images', $json['path']);
        $this->assertTrue($json['squared']);
        $this->assertEquals(800, $json['width']);
        $this->assertEquals(600, $json['height']);
        $this->assertEquals(90, $json['quality']);
        $this->assertEquals('image/jpeg,image/png', $json['acceptedTypes']);
        $this->assertEquals(5120, $json['maxSize']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Upload product image', $json['helpText']);
    }

    public function test_image_field_inheritance_from_file(): void
    {
        $field = Image::make('Image');

        // Test that Image field inherits all File field functionality
        $this->assertTrue(method_exists($field, 'disk'));
        $this->assertTrue(method_exists($field, 'path'));
        $this->assertTrue(method_exists($field, 'acceptedTypes'));
        $this->assertTrue(method_exists($field, 'maxSize'));
        $this->assertTrue(method_exists($field, 'multiple'));
        $this->assertTrue(method_exists($field, 'download'));
        $this->assertTrue(method_exists($field, 'getUrl'));
    }

    public function test_image_field_with_validation_rules(): void
    {
        $field = Image::make('Image')
            ->rules('required', 'image', 'dimensions:min_width=100,min_height=100');

        $this->assertEquals(['required', 'image', 'dimensions:min_width=100,min_height=100'], $field->rules);
    }

    public function test_image_field_complex_configuration(): void
    {
        $thumbnailCallback = function () {
            return url('thumbnails/image.jpg');
        };

        $previewCallback = function () {
            return url('previews/image.jpg');
        };

        $field = Image::make('Gallery Image')
            ->disk('gallery')
            ->path('gallery-images')
            ->squared()
            ->width(1920)
            ->height(1080)
            ->quality(95)
            ->thumbnail($thumbnailCallback)
            ->preview($previewCallback)
            ->acceptedTypes('image/jpeg,image/png,image/webp')
            ->maxSize(10240);

        // Test all configurations are set
        $this->assertEquals('gallery', $field->disk);
        $this->assertEquals('gallery-images', $field->path);
        $this->assertTrue($field->squared);
        $this->assertEquals(1920, $field->width);
        $this->assertEquals(1080, $field->height);
        $this->assertEquals(95, $field->quality);
        $this->assertEquals($thumbnailCallback, $field->thumbnailCallback);
        $this->assertEquals($previewCallback, $field->previewCallback);
        $this->assertEquals('image/jpeg,image/png,image/webp', $field->acceptedTypes);
        $this->assertEquals(10240, $field->maxSize);
    }

    public function test_image_field_resolve_preserves_value(): void
    {
        $field = Image::make('Image');
        $resource = (object) ['image' => 'images/photo.jpg'];

        $field->resolve($resource);

        $this->assertEquals('images/photo.jpg', $field->value);
    }

    public function test_image_field_constructor_sets_defaults(): void
    {
        $field = Image::make('Test Image');

        // Test that constructor sets image-specific defaults
        $this->assertEquals('images', $field->path);
        $this->assertEquals('image/*,.jpg,.jpeg,.png,.gif,.webp', $field->acceptedTypes);
        $this->assertEquals(90, $field->quality);
        $this->assertEquals('ImageField', $field->component);
    }

    public function test_image_field_quality_bounds_validation(): void
    {
        // Test quality is clamped to 0-100 range
        $field1 = Image::make('Image')->quality(-10);
        $this->assertEquals(0, $field1->quality);

        $field2 = Image::make('Image')->quality(150);
        $this->assertEquals(100, $field2->quality);

        $field3 = Image::make('Image')->quality(75);
        $this->assertEquals(75, $field3->quality);
    }

    public function test_image_field_fill_method_inherited_from_file(): void
    {
        $field = Image::make('Image');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request();

        // Test that fill method exists and can be called (inherited from File)
        $this->assertTrue(method_exists($field, 'fill'));

        // Test fill with empty request doesn't break
        $field->fill($request, $model);

        // Model should not have the attribute set since no file was uploaded
        $this->assertFalse(property_exists($model, 'image'));
    }

    public function test_image_field_static_make_method(): void
    {
        // Test the static make method (inherited from Field but may be counted)
        $field = Image::make('Test Image', 'test_image');

        $this->assertInstanceOf(Image::class, $field);
        $this->assertEquals('Test Image', $field->name);
        $this->assertEquals('test_image', $field->attribute);
    }

    public function test_image_field_debug_method_count(): void
    {
        $field = Image::make('Image');
        $reflection = new \ReflectionClass($field);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        $imageMethods = [];
        $allMethods = [];
        foreach ($methods as $method) {
            $allMethods[] = $method->getName() . ' (' . $method->getDeclaringClass()->getName() . ')';
            if ($method->getDeclaringClass()->getName() === 'JTD\\AdminPanel\\Fields\\Image') {
                $imageMethods[] = $method->getName();
            }
        }

        // Debug: Check what methods are declared in Image class
        $this->assertGreaterThan(0, count($imageMethods));

        // Expected methods declared in Image class
        $expectedMethods = [
            '__construct',
            'squared',
            'thumbnail',
            'preview',
            'width',
            'height',
            'quality',
            'getThumbnailUrl',
            'getPreviewUrl',
            'meta'
        ];

        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains($expectedMethod, $imageMethods, "Method {$expectedMethod} should be declared in Image class");
        }

        // If there's an 11th method, this will help us find it
        if (count($imageMethods) > 10) {
            $extraMethods = array_diff($imageMethods, $expectedMethods);
            $this->fail('Found extra methods in Image class: ' . implode(', ', $extraMethods));
        }
    }

    public function test_image_field_comprehensive_inheritance_testing(): void
    {
        $field = Image::make('Profile Picture');

        // Test that Image field inherits File field functionality
        $this->assertInstanceOf(\JTD\AdminPanel\Fields\File::class, $field);

        // Test that inherited methods can be called
        $field->disk('images')
              ->path('profile-pictures')
              ->acceptedTypes('image/jpeg,image/png')
              ->maxSize(5120);

        $this->assertEquals('images', $field->disk);
        $this->assertEquals('profile-pictures', $field->path);
        $this->assertEquals('image/jpeg,image/png', $field->acceptedTypes);
        $this->assertEquals(5120, $field->maxSize);
    }

    public function test_image_field_static_methods(): void
    {
        // Test static make method with different parameters
        $field1 = Image::make('Avatar');
        $this->assertInstanceOf(Image::class, $field1);
        $this->assertEquals('Avatar', $field1->name);
        $this->assertEquals('avatar', $field1->attribute);

        $field2 = Image::make('Profile Picture', 'profile_pic');
        $this->assertInstanceOf(Image::class, $field2);
        $this->assertEquals('Profile Picture', $field2->name);
        $this->assertEquals('profile_pic', $field2->attribute);

        // Test make method with callback
        $field3 = Image::make('Gallery Image', 'gallery_image');
        $field3->squared()->width(800)->height(600);

        $this->assertInstanceOf(Image::class, $field3);
        $this->assertEquals('Gallery Image', $field3->name);
        $this->assertEquals('gallery_image', $field3->attribute);
        $this->assertTrue($field3->squared);
        $this->assertEquals(800, $field3->width);
        $this->assertEquals(600, $field3->height);
    }
}
