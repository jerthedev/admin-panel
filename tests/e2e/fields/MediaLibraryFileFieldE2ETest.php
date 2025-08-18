<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E\Fields;

use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Fields\MediaLibraryFile;
use JTD\AdminPanel\Tests\Fixtures\Post;
use JTD\AdminPanel\Tests\Fixtures\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MediaLibraryFileFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up storage disk for testing
        Storage::fake('public');
    }

    public function test_media_library_file_field_complete_crud_workflow(): void
    {
        // Create a user and post model
        $user = User::factory()->create();
        $post = Post::create([
            'title' => 'Test Post with Documents',
            'content' => 'This post will have document attachments.',
            'user_id' => $user->id,
        ]);

        // Create MediaLibraryFile field with full configuration
        $field = MediaLibraryFile::make('Legal Documents')
            ->collection('legal-docs')
            ->disk('public')
            ->acceptedTypes('.pdf,.doc,.docx')
            ->maxSize(10240) // 10MB
            ->multiple(true)
            ->deletable(true)
            ->prunable(false)
            ->storeOriginalName('original_filename')
            ->storeSize('file_size')
            ->help('Upload legal documents in PDF, DOC, or DOCX format');

        // Test field creation and configuration
        $this->assertInstanceOf(MediaLibraryFile::class, $field);
        $this->assertEquals('Legal Documents', $field->name);
        $this->assertEquals('legal_documents', $field->attribute);
        $this->assertEquals('legal-docs', $field->collection);
        $this->assertEquals('public', $field->disk);
        $this->assertTrue($field->multiple);
        $this->assertTrue($field->deletable);
        $this->assertFalse($field->prunable);
        $this->assertEquals('original_filename', $field->originalNameColumn);
        $this->assertEquals('file_size', $field->sizeColumn);

        // Test MIME type conversion from extensions
        $expectedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        foreach ($expectedMimeTypes as $mimeType) {
            $this->assertContains($mimeType, $field->acceptedMimeTypes);
        }

        // Test field resolution with empty model
        $field->resolve($post);
        $this->assertNull($field->value);

        // Test meta information includes all Nova compatibility fields
        $meta = $field->meta();
        $this->assertArrayHasKey('collection', $meta);
        $this->assertArrayHasKey('disk', $meta);
        $this->assertArrayHasKey('acceptedMimeTypes', $meta);
        $this->assertArrayHasKey('maxFileSize', $meta);
        $this->assertArrayHasKey('multiple', $meta);
        $this->assertArrayHasKey('deletable', $meta);
        $this->assertArrayHasKey('prunable', $meta);
        $this->assertArrayHasKey('originalNameColumn', $meta);
        $this->assertArrayHasKey('sizeColumn', $meta);
        $this->assertArrayHasKey('previewUrl', $meta);
        $this->assertArrayHasKey('thumbnailUrl', $meta);
        $this->assertArrayHasKey('downloadUrl', $meta);

        $this->assertEquals('legal-docs', $meta['collection']);
        $this->assertEquals('public', $meta['disk']);
        $this->assertEquals(10240, $meta['maxFileSize']);
        $this->assertTrue($meta['multiple']);
        $this->assertTrue($meta['deletable']);
        $this->assertFalse($meta['prunable']);
        $this->assertEquals('original_filename', $meta['originalNameColumn']);
        $this->assertEquals('file_size', $meta['sizeColumn']);
    }

    public function test_media_library_file_field_nova_compatibility_scenarios(): void
    {
        // Test Scenario 1: Single file upload with callbacks
        $singleFileField = MediaLibraryFile::make('Contract')
            ->collection('contracts')
            ->acceptedTypes('.pdf')
            ->maxSize(5120)
            ->singleFile()
            ->deletable(true)
            ->storeOriginalName('contract_original_name')
            ->storeSize('contract_size');

        // Add custom callbacks
        $downloadCallback = function ($request, $model, $disk, $path) {
            return response()->json(['download' => 'custom-download-response']);
        };

        $previewCallback = function ($media, $disk) {
            return 'https://preview.example.com/' . $media->id;
        };

        $thumbnailCallback = function ($media, $disk) {
            return 'https://thumb.example.com/' . $media->id;
        };

        $singleFileField->download($downloadCallback)
            ->preview($previewCallback)
            ->thumbnail($thumbnailCallback);

        $this->assertEquals($downloadCallback, $singleFileField->downloadCallback);
        $this->assertEquals($previewCallback, $singleFileField->previewCallback);
        $this->assertEquals($thumbnailCallback, $singleFileField->thumbnailCallback);
        $this->assertTrue($singleFileField->singleFile);
        $this->assertFalse($singleFileField->multiple);

        // Test Scenario 2: Multiple files with restrictions
        $multipleFilesField = MediaLibraryFile::make('Attachments')
            ->collection('attachments')
            ->acceptedTypes('.pdf,.doc,.docx,.txt,.csv')
            ->maxSize(20480) // 20MB
            ->multiple(true)
            ->limit(5)
            ->deletable(false)
            ->prunable(true)
            ->disableDownload();

        $this->assertTrue($multipleFilesField->multiple);
        $this->assertFalse($multipleFilesField->singleFile);
        $this->assertEquals(5, $multipleFilesField->limit);
        $this->assertFalse($multipleFilesField->deletable);
        $this->assertTrue($multipleFilesField->prunable);
        $this->assertTrue($multipleFilesField->downloadsDisabled);

        // Test Scenario 3: Image files with conversions
        $imageField = MediaLibraryFile::make('Gallery Images')
            ->collection('gallery')
            ->acceptedTypes('.jpg,.jpeg,.png,.gif')
            ->maxSize(15360) // 15MB
            ->multiple(true)
            ->conversions([
                'thumb' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
                'medium' => ['width' => 800, 'height' => 600, 'fit' => 'contain'],
            ])
            ->responsiveImages(true)
            ->showImageDimensions(true);

        $expectedImageMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
        ];
        foreach ($expectedImageMimeTypes as $mimeType) {
            $this->assertContains($mimeType, $imageField->acceptedMimeTypes);
        }

        $this->assertTrue($imageField->responsiveImages);
        $this->assertTrue($imageField->showImageDimensions);
        $this->assertArrayHasKey('thumb', $imageField->conversions);
        $this->assertArrayHasKey('medium', $imageField->conversions);
    }

    public function test_media_library_file_field_url_methods_with_mock_media(): void
    {
        $field = MediaLibraryFile::make('Documents');

        // Create mock media object
        $mockMedia = new class {
            public $id = 123;
            public $name = 'test-document.pdf';
            public $file_name = 'test-document.pdf';
            public $size = 1048576;
            public $mime_type = 'application/pdf';

            public function getUrl($conversion = '') {
                return 'https://example.com/media/' . $this->id . '/test-document.pdf';
            }

            public function getPath() {
                return '/storage/media/test-document.pdf';
            }
        };

        // Test URL methods
        $this->assertEquals('https://example.com/media/123/test-document.pdf', $field->getUrl($mockMedia));
        $this->assertEquals('https://example.com/media/123/test-document.pdf', $field->getDownloadUrl($mockMedia));
        $this->assertEquals('https://example.com/media/123/test-document.pdf', $field->getPreviewUrl($mockMedia));
        $this->assertEquals('https://example.com/media/123/test-document.pdf', $field->getThumbnailUrl($mockMedia));

        // Test with custom callbacks
        $field->preview(function ($media, $disk) {
            return 'https://preview.example.com/' . $media->id;
        });

        $field->thumbnail(function ($media, $disk) {
            return 'https://thumb.example.com/' . $media->id;
        });

        $this->assertEquals('https://preview.example.com/123', $field->getPreviewUrl($mockMedia));
        $this->assertEquals('https://thumb.example.com/123', $field->getThumbnailUrl($mockMedia));

        // Test file metadata
        $metadata = $field->getFileMetadata($mockMedia);
        $this->assertEquals('test-document.pdf', $metadata['name']);
        $this->assertEquals(1048576, $metadata['size']);
        $this->assertEquals('application/pdf', $metadata['mime_type']);
        $this->assertEquals('1 MB', $metadata['human_readable_size']);
    }

    public function test_media_library_file_field_error_handling_scenarios(): void
    {
        $field = MediaLibraryFile::make('Documents');

        // Test with null media
        $this->assertNull($field->getUrl(null));
        $this->assertNull($field->getDownloadUrl(null));
        $this->assertNull($field->getPreviewUrl(null));
        $this->assertNull($field->getThumbnailUrl(null));
        $this->assertEquals([], $field->getFileMetadata(null));

        // Test with media without getUrl method
        $invalidMedia = new \stdClass();
        $this->assertNull($field->getUrl($invalidMedia));
        $this->assertNull($field->getDownloadUrl($invalidMedia));

        // Test file size formatting edge cases using reflection
        $reflection = new \ReflectionClass($field);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);

        $this->assertEquals('0 B', $method->invoke($field, 0));
        $this->assertEquals('1 B', $method->invoke($field, 1));
        $this->assertEquals('1023 B', $method->invoke($field, 1023));
        $this->assertEquals('1 KB', $method->invoke($field, 1024));
    }

    public function test_media_library_file_field_chaining_and_fluent_interface(): void
    {
        // Test complete method chaining
        $field = MediaLibraryFile::make('Complete Configuration')
            ->collection('complete')
            ->disk('s3')
            ->acceptedTypes('.pdf,.doc,.docx,.txt,.csv,.zip')
            ->maxSize(51200) // 50MB
            ->multiple(true)
            ->limit(10)
            ->deletable(true)
            ->prunable(false)
            ->storeOriginalName('original_name')
            ->storeSize('file_size')
            ->conversions(['thumb' => ['width' => 200, 'height' => 200]])
            ->responsiveImages(true)
            ->enableCropping(false)
            ->showImageDimensions(true)
            ->cropAspectRatio('16:9')
            ->fallbackUrl('/images/default-file.png')
            ->required()
            ->help('Upload documents with complete configuration');

        // Verify all configurations are applied
        $this->assertEquals('Complete Configuration', $field->name);
        $this->assertEquals('complete_configuration', $field->attribute);
        $this->assertEquals('complete', $field->collection);
        $this->assertEquals('s3', $field->disk);
        $this->assertEquals(51200, $field->maxFileSize);
        $this->assertTrue($field->multiple);
        $this->assertEquals(10, $field->limit);
        $this->assertTrue($field->deletable);
        $this->assertFalse($field->prunable);
        $this->assertEquals('original_name', $field->originalNameColumn);
        $this->assertEquals('file_size', $field->sizeColumn);
        $this->assertTrue($field->responsiveImages);
        $this->assertFalse($field->enableCropping);
        $this->assertTrue($field->showImageDimensions);
        $this->assertEquals('16:9', $field->cropAspectRatio);
        $this->assertEquals('/images/default-file.png', $field->fallbackUrl);

        // Test that field is still functional after chaining
        $this->assertInstanceOf(MediaLibraryFile::class, $field);
        $this->assertEquals('MediaLibraryFileField', $field->component);

        // Test meta includes all configurations
        $meta = $field->meta();
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('collection', $meta);
        $this->assertArrayHasKey('disk', $meta);
        $this->assertArrayHasKey('maxFileSize', $meta);
        $this->assertArrayHasKey('multiple', $meta);
        $this->assertArrayHasKey('limit', $meta);
        $this->assertArrayHasKey('deletable', $meta);
        $this->assertArrayHasKey('prunable', $meta);
        $this->assertArrayHasKey('originalNameColumn', $meta);
        $this->assertArrayHasKey('sizeColumn', $meta);
    }
}
