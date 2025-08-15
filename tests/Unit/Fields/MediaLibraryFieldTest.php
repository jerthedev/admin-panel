<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\MediaLibraryField;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MediaLibraryField Unit Tests
 *
 * Tests for MediaLibraryField abstract base class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MediaLibraryFieldTest extends TestCase
{
    /**
     * Create a concrete implementation of MediaLibraryField for testing.
     */
    private function createConcreteMediaLibraryField(string $name, ?string $attribute = null, ?callable $resolveCallback = null): MediaLibraryField
    {
        return new class($name, $attribute, $resolveCallback) extends MediaLibraryField {
            public string $component = 'TestMediaLibraryField';
        };
    }

    public function test_media_library_field_creation(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery');

        $this->assertEquals('Gallery', $field->name);
        $this->assertEquals('gallery', $field->attribute);
        $this->assertEquals('TestMediaLibraryField', $field->component);
    }

    public function test_media_library_field_with_custom_attribute(): void
    {
        $field = $this->createConcreteMediaLibraryField('Media Gallery', 'media_gallery');

        $this->assertEquals('Media Gallery', $field->name);
        $this->assertEquals('media_gallery', $field->attribute);
    }

    public function test_media_library_field_default_properties(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery');

        $this->assertEquals('default', $field->collection);
        $this->assertEquals('public', $field->disk); // From config default
        $this->assertEquals([], $field->acceptedMimeTypes);
        $this->assertNull($field->maxFileSize);
        $this->assertFalse($field->multiple);
        $this->assertEquals([], $field->conversions);
    }

    public function test_media_library_field_collection_configuration(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->collection('images');

        $this->assertEquals('images', $field->collection);
    }

    public function test_media_library_field_disk_configuration(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->disk('media');

        $this->assertEquals('media', $field->disk);
    }

    public function test_media_library_field_accepts_mime_types_configuration(): void
    {
        $mimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $field = $this->createConcreteMediaLibraryField('Gallery')->acceptsMimeTypes($mimeTypes);

        $this->assertEquals($mimeTypes, $field->acceptedMimeTypes);
    }

    public function test_media_library_field_max_file_size_configuration(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->maxFileSize(10240);

        $this->assertEquals(10240, $field->maxFileSize);
    }

    public function test_media_library_field_multiple_configuration(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->multiple();

        $this->assertTrue($field->multiple);
    }

    public function test_media_library_field_multiple_false(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->multiple(false);

        $this->assertFalse($field->multiple);
    }

    public function test_media_library_field_conversions_configuration(): void
    {
        $conversions = ['thumb', 'medium', 'large'];
        $field = $this->createConcreteMediaLibraryField('Gallery')->conversions($conversions);

        $this->assertEquals($conversions, $field->conversions);
    }

    public function test_media_library_field_basic_configuration_complete(): void
    {
        $mimeTypes = ['image/jpeg', 'image/png'];
        $conversions = ['thumb', 'medium'];

        $field = $this->createConcreteMediaLibraryField('Gallery')
            ->collection('photos')
            ->disk('media')
            ->acceptsMimeTypes($mimeTypes)
            ->maxFileSize(5120)
            ->multiple()
            ->conversions($conversions);

        // Test all basic configurations are set
        $this->assertEquals('photos', $field->collection);
        $this->assertEquals('media', $field->disk);
        $this->assertEquals($mimeTypes, $field->acceptedMimeTypes);
        $this->assertEquals(5120, $field->maxFileSize);
        $this->assertTrue($field->multiple);
        $this->assertEquals($conversions, $field->conversions);
    }

    public function test_media_library_field_meta_includes_all_properties(): void
    {
        $mimeTypes = ['image/jpeg', 'image/png'];
        $conversions = ['thumb', 'medium'];

        $field = $this->createConcreteMediaLibraryField('Gallery')
            ->collection('photos')
            ->disk('media')
            ->acceptsMimeTypes($mimeTypes)
            ->maxFileSize(5120)
            ->multiple()
            ->conversions($conversions);

        $meta = $field->meta();

        $this->assertArrayHasKey('collection', $meta);
        $this->assertArrayHasKey('disk', $meta);
        $this->assertArrayHasKey('acceptedMimeTypes', $meta);
        $this->assertArrayHasKey('maxFileSize', $meta);
        $this->assertArrayHasKey('multiple', $meta);
        $this->assertArrayHasKey('conversions', $meta);
        $this->assertEquals('photos', $meta['collection']);
        $this->assertEquals('media', $meta['disk']);
        $this->assertEquals($mimeTypes, $meta['acceptedMimeTypes']);
        $this->assertEquals(5120, $meta['maxFileSize']);
        $this->assertTrue($meta['multiple']);
        $this->assertEquals($conversions, $meta['conversions']);
    }

    public function test_media_library_field_json_serialization(): void
    {
        $field = $this->createConcreteMediaLibraryField('Product Gallery')
            ->collection('products')
            ->disk('products')
            ->acceptsMimeTypes(['image/jpeg', 'image/png'])
            ->maxFileSize(10240)
            ->multiple()
            ->conversions(['thumb', 'large'])
            ->required()
            ->help('Upload product images');

        $json = $field->jsonSerialize();

        $this->assertEquals('Product Gallery', $json['name']);
        $this->assertEquals('product_gallery', $json['attribute']);
        $this->assertEquals('TestMediaLibraryField', $json['component']);
        $this->assertEquals('products', $json['collection']);
        $this->assertEquals('products', $json['disk']);
        $this->assertEquals(['image/jpeg', 'image/png'], $json['acceptedMimeTypes']);
        $this->assertEquals(10240, $json['maxFileSize']);
        $this->assertTrue($json['multiple']);
        $this->assertEquals(['thumb', 'large'], $json['conversions']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Upload product images', $json['helpText']);
    }

    public function test_media_library_field_inheritance_from_field(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery');

        // Test that MediaLibraryField inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'placeholder'));
    }

    public function test_media_library_field_with_validation_rules(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')
            ->rules('required', 'array');

        $this->assertEquals(['required', 'array'], $field->rules);
    }

    public function test_media_library_field_single_file_configuration(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->singleFile();

        $this->assertTrue($field->singleFile);
        $this->assertFalse($field->multiple);
    }

    public function test_media_library_field_single_file_false(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->singleFile(false);

        $this->assertFalse($field->singleFile);
        $this->assertTrue($field->multiple);
    }

    public function test_media_library_field_responsive_images_configuration(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->responsiveImages();

        $this->assertTrue($field->responsiveImages);
    }

    public function test_media_library_field_responsive_images_false(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->responsiveImages(false);

        $this->assertFalse($field->responsiveImages);
    }

    public function test_media_library_field_enable_cropping_configuration(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->enableCropping();

        $this->assertTrue($field->enableCropping);
    }

    public function test_media_library_field_enable_cropping_false(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->enableCropping(false);

        $this->assertFalse($field->enableCropping);
    }

    public function test_media_library_field_limit_configuration(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->limit(5);

        $this->assertEquals(5, $field->limit);
    }

    public function test_media_library_field_show_image_dimensions_configuration(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->showImageDimensions();

        $this->assertTrue($field->showImageDimensions);
    }

    public function test_media_library_field_show_image_dimensions_false(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->showImageDimensions(false);

        $this->assertFalse($field->showImageDimensions);
    }

    public function test_media_library_field_crop_aspect_ratio_configuration(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->cropAspectRatio('16:9');

        $this->assertEquals('16:9', $field->cropAspectRatio);
    }

    public function test_media_library_field_fallback_url_configuration(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->fallbackUrl('/default.jpg');

        $this->assertEquals('/default.jpg', $field->fallbackUrl);
    }

    public function test_media_library_field_fallback_path_configuration(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->fallbackPath('/path/to/default.jpg');

        $this->assertEquals('/path/to/default.jpg', $field->fallbackPath);
    }

    public function test_media_library_field_complex_configuration(): void
    {
        $field = $this->createConcreteMediaLibraryField('Advanced Gallery')
            ->collection('advanced')
            ->disk('s3')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxFileSize(20480)
            ->multiple()
            ->conversions(['thumb', 'medium', 'large', 'xl'])
            ->responsiveImages()
            ->enableCropping()
            ->limit(10)
            ->showImageDimensions()
            ->cropAspectRatio('16:9')
            ->fallbackUrl('/default.jpg')
            ->fallbackPath('/path/to/default.jpg');

        // Test all configurations are set
        $this->assertEquals('advanced', $field->collection);
        $this->assertEquals('s3', $field->disk);
        $this->assertEquals(['image/jpeg', 'image/png', 'image/webp'], $field->acceptedMimeTypes);
        $this->assertEquals(20480, $field->maxFileSize);
        $this->assertTrue($field->multiple);
        $this->assertEquals(['thumb', 'medium', 'large', 'xl'], $field->conversions);
        $this->assertTrue($field->responsiveImages);
        $this->assertTrue($field->enableCropping);
        $this->assertEquals(10, $field->limit);
        $this->assertTrue($field->showImageDimensions);
        $this->assertEquals('16:9', $field->cropAspectRatio);
        $this->assertEquals('/default.jpg', $field->fallbackUrl);
        $this->assertEquals('/path/to/default.jpg', $field->fallbackPath);
    }

    public function test_media_library_field_resolve_preserves_value(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery');
        $resource = (object) ['gallery' => 'media-collection-data'];

        $field->resolve($resource);

        $this->assertEquals('media-collection-data', $field->value);
    }

    public function test_media_library_field_with_callback(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery', null, function ($resource, $attribute) {
            return 'custom-media-' . $resource->{$attribute};
        });

        $resource = (object) ['gallery' => 'test-data'];

        $field->resolve($resource);

        $this->assertEquals('custom-media-test-data', $field->value);
    }

    public function test_media_library_field_constructor_sets_default_disk(): void
    {
        // Mock config to return a specific default disk
        config(['admin-panel.media_library.default_disk' => 'custom-disk']);

        $field = $this->createConcreteMediaLibraryField('Gallery');

        $this->assertEquals('custom-disk', $field->disk);
    }

    public function test_media_library_field_resolve_method_with_mock_media_resource(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->singleFile();

        // Create a mock HasMedia resource
        $mockResource = $this->createMock(\Spatie\MediaLibrary\HasMedia::class);
        $mockMedia = $this->createMock(\Spatie\MediaLibrary\MediaCollections\Models\Media::class);
        $mockMediaCollection = collect([$mockMedia]);

        $mockResource->expects($this->once())
                    ->method('getMedia')
                    ->with('default')
                    ->willReturn($mockMediaCollection);

        // Test resolve with HasMedia resource (single file)
        $field->resolve($mockResource);

        $this->assertEquals($mockMedia, $field->value);
    }

    public function test_media_library_field_resolve_method_with_multiple_files(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery')->multiple();

        // Create a mock HasMedia resource
        $mockResource = $this->createMock(\Spatie\MediaLibrary\HasMedia::class);
        $mockMedia1 = $this->createMock(\Spatie\MediaLibrary\MediaCollections\Models\Media::class);
        $mockMedia2 = $this->createMock(\Spatie\MediaLibrary\MediaCollections\Models\Media::class);
        $mockMediaCollection = collect([$mockMedia1, $mockMedia2]);

        $mockResource->expects($this->once())
                    ->method('getMedia')
                    ->with('default')
                    ->willReturn($mockMediaCollection);

        // Test resolve with HasMedia resource (multiple files)
        $field->resolve($mockResource);

        $this->assertEquals($mockMediaCollection, $field->value);
    }

    public function test_media_library_field_fill_method_with_no_file(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery');
        $request = new \Illuminate\Http\Request();
        $model = new \stdClass();

        // Test fill with no file uploaded
        $field->fill($request, $model);

        // Should complete without errors
        $this->assertTrue(true);
    }

    public function test_media_library_field_fill_method_with_callback(): void
    {
        $callbackCalled = false;
        $fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
        };

        $field = $this->createConcreteMediaLibraryField('Gallery');
        $field->fillCallback = $fillCallback;

        $request = new \Illuminate\Http\Request();
        $model = new \stdClass();

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
    }

    public function test_media_library_field_fill_method_comprehensive_coverage(): void
    {
        $field = $this->createConcreteMediaLibraryField('Gallery');

        // Test that fill method exists and can handle different scenarios
        $this->assertTrue(method_exists($field, 'fill'));

        // Test with non-HasMedia model
        $regularModel = new \stdClass();
        $request = new \Illuminate\Http\Request();

        $field->fill($request, $regularModel);
        $this->assertTrue(true); // Should complete without errors

        // Test with HasMedia model but no files
        $mockModel = $this->createMock(\Spatie\MediaLibrary\HasMedia::class);
        $field->fill($request, $mockModel);
        $this->assertTrue(true); // Should complete without errors
    }
}
