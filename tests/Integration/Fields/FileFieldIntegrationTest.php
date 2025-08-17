<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Fields\File;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * File Field Integration Test
 *
 * Tests the complete integration between PHP File field class,
 * API endpoints, file storage, and frontend functionality.
 * 
 * Follows the same pattern as other field integration tests,
 * focusing on field configuration and behavior rather than
 * database operations with non-existent columns.
 */
class FileFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup storage for file uploads
        Storage::fake('public');
        Storage::fake('private');

        // Create test users (using existing User model structure)
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);
    }

    /** @test */
    public function it_creates_file_field_with_nova_syntax(): void
    {
        $field = File::make('Document');

        $this->assertEquals('Document', $field->name);
        $this->assertEquals('document', $field->attribute);
        $this->assertEquals('FileField', $field->component);
        $this->assertEquals('files', $field->path);
        $this->assertEquals('public', $field->disk);
        $this->assertTrue($field->deletable);
        $this->assertFalse($field->prunable);
        $this->assertFalse($field->downloadsDisabled);
    }

    /** @test */
    public function it_configures_field_with_nova_api_methods(): void
    {
        $field = File::make('Document')
            ->disk('private')
            ->path('documents')
            ->acceptedTypes('.pdf,.doc,.docx')
            ->maxSize(5120)
            ->deletable(false)
            ->prunable()
            ->disableDownload()
            ->storeOriginalName('original_name')
            ->storeSize('file_size');

        $this->assertEquals('private', $field->disk);
        $this->assertEquals('documents', $field->path);
        $this->assertEquals('.pdf,.doc,.docx', $field->acceptedTypes);
        $this->assertEquals(5120, $field->maxSize);
        $this->assertFalse($field->deletable);
        $this->assertTrue($field->prunable);
        $this->assertTrue($field->downloadsDisabled);
        $this->assertEquals('original_name', $field->originalNameColumn);
        $this->assertEquals('file_size', $field->sizeColumn);
    }

    /** @test */
    public function it_handles_file_upload_with_metadata_storage(): void
    {
        $field = File::make('Document')
            ->disk('public')
            ->path('uploads')
            ->storeOriginalName('original_name')
            ->storeSize('file_size');

        $file = UploadedFile::fake()->create('test-document.pdf', 1024, 'application/pdf');
        $request = Request::create('/test', 'POST');
        $request->files->set('document', $file);

        $model = new \stdClass();
        $field->fill($request, $model);

        $this->assertStringContains('uploads/test-document', $model->document);
        $this->assertEquals('test-document.pdf', $model->original_name);
        $this->assertEquals(1024 * 1024, $model->file_size); // Size in bytes
        $this->assertTrue(Storage::disk('public')->exists($model->document));
    }

    /** @test */
    public function it_handles_custom_store_callback(): void
    {
        $storeCallback = function ($request, $model, $attribute, $requestAttribute, $disk, $path) {
            return [
                'document' => 'custom-path/document.pdf',
                'original_name' => 'custom-name.pdf',
                'file_size' => 2048,
            ];
        };

        $field = File::make('Document')->store($storeCallback);

        $request = Request::create('/test', 'POST');
        $model = new \stdClass();
        $field->fill($request, $model);

        $this->assertEquals('custom-path/document.pdf', $model->document);
        $this->assertEquals('custom-name.pdf', $model->original_name);
        $this->assertEquals(2048, $model->file_size);
    }

    /** @test */
    public function it_handles_custom_store_as_callback(): void
    {
        $storeAsCallback = function ($request, $model, $attribute, $requestAttribute) {
            return 'custom-filename-' . time() . '.pdf';
        };

        $field = File::make('Document')
            ->disk('public')
            ->path('uploads')
            ->storeAs($storeAsCallback);

        $file = UploadedFile::fake()->create('test.pdf', 512, 'application/pdf');
        $request = Request::create('/test', 'POST');
        $request->files->set('document', $file);

        $model = new \stdClass();
        $field->fill($request, $model);

        $this->assertStringContains('uploads/custom-filename-', $model->document);
        $this->assertTrue(Storage::disk('public')->exists($model->document));
    }

    /** @test */
    public function it_handles_file_deletion_when_deletable(): void
    {
        Storage::disk('public')->put('test-file.pdf', 'content');

        $field = File::make('Document')
            ->disk('public')
            ->deletable()
            ->storeOriginalName('original_name')
            ->storeSize('file_size');

        $request = Request::create('/test', 'POST');
        $request->merge(['document' => null]);

        $model = new \stdClass();
        $model->document = 'test-file.pdf';
        $model->original_name = 'original.pdf';
        $model->file_size = 1024;

        $field->fill($request, $model);

        $this->assertNull($model->document);
        $this->assertNull($model->original_name);
        $this->assertNull($model->file_size);
        $this->assertFalse(Storage::disk('public')->exists('test-file.pdf'));
    }

    /** @test */
    public function it_preserves_file_when_not_deletable(): void
    {
        Storage::disk('public')->put('test-file.pdf', 'content');

        $field = File::make('Document')
            ->disk('public')
            ->deletable(false);

        $request = Request::create('/test', 'POST');
        $request->merge(['document' => null]);

        $model = new \stdClass();
        $model->document = 'test-file.pdf';

        $field->fill($request, $model);

        $this->assertEquals('test-file.pdf', $model->document);
        $this->assertTrue(Storage::disk('public')->exists('test-file.pdf'));
    }

    /** @test */
    public function it_handles_custom_delete_callback(): void
    {
        $deleteCallback = function ($request, $model, $disk, $path) {
            return [
                'document' => null,
                'original_name' => null,
                'file_size' => null,
                'deleted_at' => now(),
            ];
        };

        $field = File::make('Document')->delete($deleteCallback);

        $request = Request::create('/test', 'POST');
        $request->merge(['document' => null]);

        $model = new \stdClass();
        $model->document = 'test-file.pdf';

        $field->fill($request, $model);

        $this->assertNull($model->document);
        $this->assertNull($model->original_name);
        $this->assertNull($model->file_size);
        $this->assertNotNull($model->deleted_at);
    }

    /** @test */
    public function it_generates_preview_and_thumbnail_urls(): void
    {
        $previewCallback = function ($value, $disk) {
            return "preview-{$value}";
        };

        $thumbnailCallback = function ($value, $disk) {
            return "thumbnail-{$value}";
        };

        $field = File::make('Document')
            ->preview($previewCallback)
            ->thumbnail($thumbnailCallback);

        $field->value = 'test.pdf';

        $this->assertEquals('preview-test.pdf', $field->getPreviewUrl());
        $this->assertEquals('thumbnail-test.pdf', $field->getThumbnailUrl());
    }

    /** @test */
    public function it_handles_download_response(): void
    {
        Storage::disk('public')->put('test-file.pdf', 'file content');

        $field = File::make('Document')
            ->disk('public')
            ->storeOriginalName('original_name');

        $model = new \stdClass();
        $model->document = 'test-file.pdf';
        $model->original_name = 'original-document.pdf';

        $request = Request::create('/test', 'GET');
        $response = $field->getDownloadResponse($request, $model);

        $this->assertNotNull($response);
    }

    /** @test */
    public function it_returns_null_download_when_disabled(): void
    {
        $field = File::make('Document')->disableDownload();

        $model = new \stdClass();
        $model->document = 'test-file.pdf';

        $request = Request::create('/test', 'GET');
        $response = $field->getDownloadResponse($request, $model);

        $this->assertNull($response);
    }

    /** @test */
    public function it_includes_all_metadata_in_field_meta(): void
    {
        $field = File::make('Document')
            ->disk('private')
            ->path('documents')
            ->acceptedTypes('.pdf,.doc')
            ->maxSize(2048)
            ->deletable(false)
            ->prunable()
            ->disableDownload()
            ->storeOriginalName('original_name')
            ->storeSize('file_size');

        $field->value = 'test.pdf';

        $meta = $field->meta();

        $this->assertEquals('private', $meta['disk']);
        $this->assertEquals('documents', $meta['path']);
        $this->assertEquals('.pdf,.doc', $meta['acceptedTypes']);
        $this->assertEquals(2048, $meta['maxSize']);
        $this->assertFalse($meta['deletable']);
        $this->assertTrue($meta['prunable']);
        $this->assertTrue($meta['downloadsDisabled']);
        $this->assertEquals('original_name', $meta['originalNameColumn']);
        $this->assertEquals('file_size', $meta['sizeColumn']);
        $this->assertArrayHasKey('previewUrl', $meta);
        $this->assertArrayHasKey('thumbnailUrl', $meta);
    }
}
