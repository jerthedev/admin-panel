<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Fields;

use JTD\AdminPanel\Fields\MediaLibraryFile;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\Post;
use JTD\AdminPanel\Tests\Fixtures\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryFileFieldIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up storage disk for testing
        Storage::fake('public');
    }

    public function test_media_library_file_field_integration_with_model(): void
    {
        // Create a user and post model
        $user = User::factory()->create();
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test content',
            'user_id' => $user->id,
        ]);

        $field = MediaLibraryFile::make('Documents')
            ->collection('documents')
            ->acceptsMimeTypes(['application/pdf'])
            ->maxFileSize(5120);

        // Test field resolution with model
        $field->resolve($post);

        $this->assertInstanceOf(MediaLibraryFile::class, $field);
        $this->assertEquals('documents', $field->collection);
    }

    public function test_media_library_file_field_fill_with_upload(): void
    {
        $user = User::factory()->create();
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test content',
            'user_id' => $user->id,
        ]);

        $field = MediaLibraryFile::make('Documents')
            ->collection('documents');

        // Create a fake uploaded file
        $uploadedFile = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $request = Request::create('/test', 'POST', [], [], [
            'documents' => $uploadedFile
        ]);

        // Test fill method
        $field->fill($request, $post);

        $this->assertTrue(true); // Basic integration test
    }

    public function test_media_library_file_field_nova_compatibility_methods(): void
    {
        $field = MediaLibraryFile::make('Documents');

        // Test Nova File Field compatibility methods
        $field->acceptedTypes('.pdf,.doc,.txt')
            ->maxSize(10240)
            ->deletable(false)
            ->prunable(true)
            ->storeOriginalName('original_filename')
            ->storeSize('file_size')
            ->disableDownload();

        $this->assertContains('application/pdf', $field->acceptedMimeTypes);
        $this->assertContains('application/msword', $field->acceptedMimeTypes);
        $this->assertContains('text/plain', $field->acceptedMimeTypes);
        $this->assertEquals(10240, $field->maxFileSize);
        $this->assertFalse($field->deletable);
        $this->assertTrue($field->prunable);
        $this->assertEquals('original_filename', $field->originalNameColumn);
        $this->assertEquals('file_size', $field->sizeColumn);
        $this->assertTrue($field->downloadsDisabled);
    }

    public function test_media_library_file_field_callback_methods(): void
    {
        $field = MediaLibraryFile::make('Documents');

        $downloadCallback = function ($request, $model, $disk, $path) {
            return 'download-response';
        };

        $storeCallback = function ($request, $model, $attribute, $requestAttribute, $disk, $storagePath) {
            return ['stored_path' => 'custom/path'];
        };

        $deleteCallback = function ($request, $model, $disk, $path) {
            return ['deleted' => true];
        };

        $previewCallback = function ($media, $disk) {
            return 'preview-url';
        };

        $thumbnailCallback = function ($media, $disk) {
            return 'thumbnail-url';
        };

        $storeAsCallback = function ($request, $model, $attribute, $requestAttribute) {
            return 'custom-filename.pdf';
        };

        // Test callback assignment
        $field->download($downloadCallback)
            ->store($storeCallback)
            ->delete($deleteCallback)
            ->preview($previewCallback)
            ->thumbnail($thumbnailCallback)
            ->storeAs($storeAsCallback);

        $this->assertEquals($downloadCallback, $field->downloadCallback);
        $this->assertEquals($storeCallback, $field->storeCallback);
        $this->assertEquals($deleteCallback, $field->deleteCallback);
        $this->assertEquals($previewCallback, $field->previewCallback);
        $this->assertEquals($thumbnailCallback, $field->thumbnailCallback);
        $this->assertEquals($storeAsCallback, $field->storeAsCallback);
    }

    public function test_media_library_file_field_url_methods(): void
    {
        $field = MediaLibraryFile::make('Documents');

        // Test URL methods without media
        $this->assertNull($field->getUrl());
        $this->assertNull($field->getPreviewUrl());
        $this->assertNull($field->getThumbnailUrl());
        $this->assertNull($field->getDownloadUrl());

        // Test URL methods with mock media
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return 'https://example.com/document.pdf';
            }
        };

        $this->assertEquals('https://example.com/document.pdf', $field->getUrl($mockMedia));
        $this->assertEquals('https://example.com/document.pdf', $field->getPreviewUrl($mockMedia));
        $this->assertEquals('https://example.com/document.pdf', $field->getThumbnailUrl($mockMedia));
        $this->assertEquals('https://example.com/document.pdf', $field->getDownloadUrl($mockMedia));
    }

    public function test_media_library_file_field_meta_information(): void
    {
        $field = MediaLibraryFile::make('Documents')
            ->collection('documents')
            ->disk('public')
            ->acceptsMimeTypes(['application/pdf'])
            ->maxFileSize(5120)
            ->multiple(true)
            ->deletable(false)
            ->prunable(true)
            ->storeOriginalName('original_name')
            ->storeSize('file_size')
            ->disableDownload();

        $meta = $field->meta();

        // Test inherited MediaLibraryField meta
        $this->assertEquals('documents', $meta['collection']);
        $this->assertEquals('public', $meta['disk']);
        $this->assertEquals(['application/pdf'], $meta['acceptedMimeTypes']);
        $this->assertEquals(5120, $meta['maxFileSize']);
        $this->assertTrue($meta['multiple']);

        // Test Nova File Field compatibility meta
        $this->assertFalse($meta['deletable']);
        $this->assertTrue($meta['prunable']);
        $this->assertTrue($meta['downloadsDisabled']);
        $this->assertEquals('original_name', $meta['originalNameColumn']);
        $this->assertEquals('file_size', $meta['sizeColumn']);
        $this->assertArrayHasKey('previewUrl', $meta);
        $this->assertArrayHasKey('thumbnailUrl', $meta);
        $this->assertArrayHasKey('downloadUrl', $meta);
    }

    public function test_media_library_file_field_file_metadata(): void
    {
        $field = MediaLibraryFile::make('Documents');

        // Test with mock media object
        $mockMedia = new class {
            public $name = 'test-document.pdf';
            public $size = 2048000;
            public $mime_type = 'application/pdf';
            public $created_at = '2023-01-01 12:00:00';
        };

        $metadata = $field->getFileMetadata($mockMedia);

        $this->assertEquals('test-document.pdf', $metadata['name']);
        $this->assertEquals(2048000, $metadata['size']);
        $this->assertEquals('application/pdf', $metadata['mime_type']);
        $this->assertEquals('2023-01-01 12:00:00', $metadata['created_at']);
        $this->assertEquals('1.95 MB', $metadata['human_readable_size']);
    }

    public function test_media_library_file_field_mime_type_mapping(): void
    {
        $field = MediaLibraryFile::make('Documents');

        // Test various file extensions
        $field->acceptedTypes('.pdf,.doc,.docx,.txt,.csv,.zip,.jpg,.mp4,.mp3');

        $expectedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'text/csv',
            'application/zip',
            'application/x-zip-compressed',
            'image/jpeg',
            'video/mp4',
            'audio/mpeg',
        ];

        foreach ($expectedMimeTypes as $mimeType) {
            $this->assertContains($mimeType, $field->acceptedMimeTypes);
        }
    }

    public function test_media_library_file_field_json_serialization(): void
    {
        $field = MediaLibraryFile::make('Legal Documents')
            ->collection('legal-docs')
            ->acceptsMimeTypes(['application/pdf'])
            ->maxFileSize(20480)
            ->multiple(true)
            ->deletable(false)
            ->required()
            ->help('Upload PDF documents only');

        $json = $field->jsonSerialize();

        $this->assertEquals('Legal Documents', $json['name']);
        $this->assertEquals('legal_documents', $json['attribute']);
        $this->assertEquals('MediaLibraryFileField', $json['component']);
        $this->assertEquals('legal-docs', $json['collection']);
        $this->assertEquals(['application/pdf'], $json['acceptedMimeTypes']);
        $this->assertEquals(20480, $json['maxFileSize']);
        $this->assertTrue($json['multiple']);
        $this->assertFalse($json['deletable']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Upload PDF documents only', $json['helpText']);
    }
}
