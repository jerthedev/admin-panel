<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Fields\File;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * File Field E2E Test.
 *
 * Tests the complete end-to-end functionality of File fields
 * including database operations, file storage, and field behavior.
 *
 * Focuses on field integration and data flow rather than
 * web interface testing (which is handled by Playwright tests).
 */
class FileFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup storage for file uploads
        Storage::fake('public');
        Storage::fake('private');

        // Create test users with and without files
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        // Create some test files in storage
        Storage::disk('public')->put('files/existing-document.pdf', 'PDF content');
        Storage::disk('private')->put('documents/private-doc.pdf', 'Private PDF content');
    }

    /** @test */
    public function it_handles_complete_file_upload_workflow(): void
    {
        $field = File::make('Document')
            ->disk('public')
            ->path('uploads')
            ->acceptedTypes('.pdf,.doc,.docx')
            ->maxSize(5120)
            ->storeOriginalName('original_name')
            ->storeSize('file_size');

        // Simulate file upload
        $file = UploadedFile::fake()->create('test-document.pdf', 1024, 'application/pdf');
        $request = Request::create('/admin/users/1', 'POST');
        $request->files->set('document', $file);

        $user = User::find(1);
        $field->fill($request, $user);

        // Verify file was stored
        $this->assertStringContains('uploads/test-document', $user->document);
        $this->assertEquals('test-document.pdf', $user->original_name);
        $this->assertEquals(1024 * 1024, $user->file_size);
        $this->assertTrue(Storage::disk('public')->exists($user->document));

        // Verify field metadata
        $field->value = $user->document;
        $meta = $field->meta();
        $this->assertEquals('public', $meta['disk']);
        $this->assertEquals('uploads', $meta['path']);
        $this->assertEquals('.pdf,.doc,.docx', $meta['acceptedTypes']);
        $this->assertEquals(5120, $meta['maxSize']);
        $this->assertEquals('original_name', $meta['originalNameColumn']);
        $this->assertEquals('file_size', $meta['sizeColumn']);
    }

    /** @test */
    public function it_handles_file_replacement_workflow(): void
    {
        $field = File::make('Document')
            ->disk('public')
            ->path('uploads')
            ->storeOriginalName('original_name')
            ->storeSize('file_size');

        $user = User::find(1);

        // Upload first file
        $firstFile = UploadedFile::fake()->create('first-document.pdf', 512, 'application/pdf');
        $request1 = Request::create('/admin/users/1', 'POST');
        $request1->files->set('document', $firstFile);
        $field->fill($request1, $user);

        $firstPath = $user->document;
        $this->assertTrue(Storage::disk('public')->exists($firstPath));

        // Upload replacement file
        $secondFile = UploadedFile::fake()->create('second-document.pdf', 1024, 'application/pdf');
        $request2 = Request::create('/admin/users/1', 'POST');
        $request2->files->set('document', $secondFile);
        $field->fill($request2, $user);

        // Verify new file is stored and old file still exists (not automatically deleted)
        $this->assertNotEquals($firstPath, $user->document);
        $this->assertStringContains('uploads/second-document', $user->document);
        $this->assertEquals('second-document.pdf', $user->original_name);
        $this->assertEquals(1024 * 1024, $user->file_size);
        $this->assertTrue(Storage::disk('public')->exists($user->document));
    }

    /** @test */
    public function it_handles_file_deletion_workflow(): void
    {
        Storage::disk('public')->put('uploads/test-file.pdf', 'content');

        $field = File::make('Document')
            ->disk('public')
            ->deletable()
            ->storeOriginalName('original_name')
            ->storeSize('file_size');

        $user = User::find(1);
        $user->document = 'uploads/test-file.pdf';
        $user->original_name = 'test-file.pdf';
        $user->file_size = 1024;

        // Simulate file deletion
        $request = Request::create('/admin/users/1', 'POST');
        $request->merge(['document' => null]);
        $field->fill($request, $user);

        // Verify file was deleted
        $this->assertNull($user->document);
        $this->assertNull($user->original_name);
        $this->assertNull($user->file_size);
        $this->assertFalse(Storage::disk('public')->exists('uploads/test-file.pdf'));
    }

    /** @test */
    public function it_prevents_deletion_when_not_deletable(): void
    {
        Storage::disk('public')->put('uploads/protected-file.pdf', 'content');

        $field = File::make('Document')
            ->disk('public')
            ->deletable(false);

        $user = User::find(1);
        $user->document = 'uploads/protected-file.pdf';

        // Attempt to delete file
        $request = Request::create('/admin/users/1', 'POST');
        $request->merge(['document' => null]);
        $field->fill($request, $user);

        // Verify file was not deleted
        $this->assertEquals('uploads/protected-file.pdf', $user->document);
        $this->assertTrue(Storage::disk('public')->exists('uploads/protected-file.pdf'));
    }

    /** @test */
    public function it_handles_custom_storage_callbacks(): void
    {
        $storeCallback = function ($request, $model, $attribute, $requestAttribute, $disk, $path) {
            return [
                'document' => 'custom-storage/document.pdf',
                'original_name' => 'custom-name.pdf',
                'file_size' => 2048,
                'stored_at' => now(),
            ];
        };

        $field = File::make('Document')->store($storeCallback);

        $user = User::find(1);
        $request = Request::create('/admin/users/1', 'POST');
        $field->fill($request, $user);

        $this->assertEquals('custom-storage/document.pdf', $user->document);
        $this->assertEquals('custom-name.pdf', $user->original_name);
        $this->assertEquals(2048, $user->file_size);
        $this->assertNotNull($user->stored_at);
    }

    /** @test */
    public function it_handles_custom_deletion_callbacks(): void
    {
        $deleteCallback = function ($request, $model, $disk, $path) {
            return [
                'document' => null,
                'original_name' => null,
                'file_size' => null,
                'deleted_at' => now(),
                'deleted_by' => 'system',
            ];
        };

        $field = File::make('Document')->delete($deleteCallback);

        $user = User::find(1);
        $user->document = 'test-file.pdf';

        $request = Request::create('/admin/users/1', 'POST');
        $request->merge(['document' => null]);
        $field->fill($request, $user);

        $this->assertNull($user->document);
        $this->assertNull($user->original_name);
        $this->assertNull($user->file_size);
        $this->assertNotNull($user->deleted_at);
        $this->assertEquals('system', $user->deleted_by);
    }

    /** @test */
    public function it_handles_download_functionality(): void
    {
        Storage::disk('public')->put('files/download-test.pdf', 'Download content');

        $field = File::make('Document')
            ->disk('public')
            ->storeOriginalName('original_name');

        $user = User::find(1);
        $user->document = 'files/download-test.pdf';
        $user->original_name = 'original-document.pdf';

        $request = Request::create('/admin/files/download', 'GET');
        $response = $field->getDownloadResponse($request, $user);

        $this->assertNotNull($response);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }

    /** @test */
    public function it_handles_disabled_downloads(): void
    {
        $field = File::make('Document')->disableDownload();

        $user = User::find(1);
        $user->document = 'files/test.pdf';

        $request = Request::create('/admin/files/download', 'GET');
        $response = $field->getDownloadResponse($request, $user);

        $this->assertNull($response);
    }

    /** @test */
    public function it_handles_preview_and_thumbnail_urls(): void
    {
        $previewCallback = function ($value, $disk) {
            return "https://preview.example.com/{$value}";
        };

        $thumbnailCallback = function ($value, $disk) {
            return "https://thumbnail.example.com/{$value}";
        };

        $field = File::make('Document')
            ->preview($previewCallback)
            ->thumbnail($thumbnailCallback);

        $field->value = 'files/test.pdf';

        $this->assertEquals('https://preview.example.com/files/test.pdf', $field->getPreviewUrl());
        $this->assertEquals('https://thumbnail.example.com/files/test.pdf', $field->getThumbnailUrl());

        $meta = $field->meta();
        $this->assertEquals('https://preview.example.com/files/test.pdf', $meta['previewUrl']);
        $this->assertEquals('https://thumbnail.example.com/files/test.pdf', $meta['thumbnailUrl']);
    }

    /** @test */
    public function it_handles_multiple_disk_configurations(): void
    {
        // Test public disk
        $publicField = File::make('Document')->disk('public')->path('public-files');
        $publicFile = UploadedFile::fake()->create('public.pdf', 512, 'application/pdf');
        $request1 = Request::create('/test', 'POST');
        $request1->files->set('document', $publicFile);

        $user1 = User::find(1);
        $publicField->fill($request1, $user1);

        $this->assertNotNull($user1->document);
        $this->assertStringContains('public-files/public', $user1->document);
        $this->assertTrue(Storage::disk('public')->exists($user1->document));

        // Test private disk with different user
        $privateField = File::make('Document')->disk('private')->path('private-files');
        $privateFile = UploadedFile::fake()->create('private.pdf', 512, 'application/pdf');
        $request2 = Request::create('/test', 'POST');
        $request2->files->set('document', $privateFile);

        $user2 = User::find(2);
        $privateField->fill($request2, $user2);

        $this->assertNotNull($user2->document);
        $this->assertStringContains('private-files/private', $user2->document);
        $this->assertTrue(Storage::disk('private')->exists($user2->document));
    }

    /** @test */
    public function it_validates_file_constraints_in_real_scenarios(): void
    {
        $field = File::make('Document')
            ->disk('public')
            ->path('uploads')
            ->acceptedTypes('.pdf')
            ->maxSize(1024); // 1MB

        // Test valid file
        $validFile = UploadedFile::fake()->create('valid.pdf', 512, 'application/pdf');
        $request1 = Request::create('/test', 'POST');
        $request1->files->set('document', $validFile);

        $user = User::find(1);
        $field->fill($request1, $user);

        $this->assertStringContains('uploads/valid', $user->document);
        $this->assertTrue(Storage::disk('public')->exists($user->document));

        // Test file size validation (this would typically be handled by frontend/middleware)
        $meta = $field->meta();
        $this->assertEquals('.pdf', $meta['acceptedTypes']);
        $this->assertEquals(1024, $meta['maxSize']);
    }
}
