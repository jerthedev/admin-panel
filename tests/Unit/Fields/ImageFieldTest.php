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

        $this->assertFalse($field->squared);
        $this->assertFalse($field->rounded);
    }

    public function test_image_field_inherits_file_properties(): void
    {
        $field = Image::make('Image');

        // Should inherit File field properties
        $this->assertEquals('public', $field->disk);
        $this->assertEquals('files', $field->path); // Inherits from File field
        $this->assertNull($field->acceptedTypes);
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

    public function test_image_field_rounded_configuration(): void
    {
        $field = Image::make('Image')->rounded();

        $this->assertTrue($field->rounded);
    }

    public function test_image_field_rounded_false(): void
    {
        $field = Image::make('Image')->rounded(false);

        $this->assertFalse($field->rounded);
    }

    public function test_image_field_disable_download(): void
    {
        $field = Image::make('Image')->disableDownload();

        $this->assertFalse($field->downloadCallback);
    }



    public function test_image_field_meta_includes_all_properties(): void
    {
        $field = Image::make('Image')
            ->squared()
            ->rounded();

        $meta = $field->meta();

        $this->assertArrayHasKey('squared', $meta);
        $this->assertArrayHasKey('rounded', $meta);
        $this->assertTrue($meta['squared']);
        $this->assertTrue($meta['rounded']);
    }

    public function test_image_field_json_serialization(): void
    {
        $field = Image::make('Product Image')
            ->disk('products')
            ->path('product-images')
            ->squared()
            ->rounded()
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
        $this->assertTrue($json['rounded']);
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

        // Test Image-specific methods
        $this->assertTrue(method_exists($field, 'squared'));
        $this->assertTrue(method_exists($field, 'rounded'));
        $this->assertTrue(method_exists($field, 'disableDownload'));
    }

    public function test_image_field_with_validation_rules(): void
    {
        $field = Image::make('Image')
            ->rules('required', 'image', 'dimensions:min_width=100,min_height=100');

        $this->assertEquals(['required', 'image', 'dimensions:min_width=100,min_height=100'], $field->rules);
    }

    public function test_image_field_complex_configuration(): void
    {
        $field = Image::make('Gallery Image')
            ->disk('gallery')
            ->path('gallery-images')
            ->squared()
            ->rounded()
            ->disableDownload()
            ->acceptedTypes('image/jpeg,image/png,image/webp')
            ->maxSize(10240);

        // Test all configurations are set
        $this->assertEquals('gallery', $field->disk);
        $this->assertEquals('gallery-images', $field->path);
        $this->assertTrue($field->squared);
        $this->assertTrue($field->rounded);
        $this->assertFalse($field->downloadCallback);
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
        $this->assertEquals('ImageField', $field->component);
        $this->assertFalse($field->squared);
        $this->assertFalse($field->rounded);
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
        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() === 'JTD\\AdminPanel\\Fields\\Image') {
                $imageMethods[] = $method->getName();
            }
        }

        // Debug: Check what methods are declared in Image class
        $this->assertGreaterThan(0, count($imageMethods));

        // Expected methods declared in Image class
        $expectedMethods = [
            'squared',
            'rounded',
            'disableDownload',
            'meta'
        ];

        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains($expectedMethod, $imageMethods, "Method {$expectedMethod} should be declared in Image class");
        }

        // Ensure we only have the expected methods
        $this->assertEquals(count($expectedMethods), count($imageMethods),
            'Image class should only have the expected methods. Found: ' . implode(', ', $imageMethods));
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

        // Test make method with chaining
        $field3 = Image::make('Gallery Image', 'gallery_image');
        $field3->squared()->rounded();

        $this->assertInstanceOf(Image::class, $field3);
        $this->assertEquals('Gallery Image', $field3->name);
        $this->assertEquals('gallery_image', $field3->attribute);
        $this->assertTrue($field3->squared);
        $this->assertTrue($field3->rounded);
    }
}
