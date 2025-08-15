<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\File;
use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

/**
 * File Field Unit Tests
 *
 * Tests for File field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class FileFieldTest extends TestCase
{
    public function test_file_field_creation(): void
    {
        $field = File::make('Document');

        $this->assertEquals('Document', $field->name);
        $this->assertEquals('document', $field->attribute);
        $this->assertEquals('FileField', $field->component);
    }

    public function test_file_field_with_custom_attribute(): void
    {
        $field = File::make('Upload File', 'upload_file');

        $this->assertEquals('Upload File', $field->name);
        $this->assertEquals('upload_file', $field->attribute);
    }

    public function test_file_field_default_properties(): void
    {
        $field = File::make('Document');

        $this->assertEquals('public', $field->disk);
        $this->assertEquals('files', $field->path);
        $this->assertNull($field->acceptedTypes);
        $this->assertNull($field->maxSize);
        $this->assertFalse($field->multiple);
        $this->assertNull($field->downloadCallback);
    }

    public function test_file_field_disk_configuration(): void
    {
        $field = File::make('Document')->disk('private');

        $this->assertEquals('private', $field->disk);
    }

    public function test_file_field_path_configuration(): void
    {
        $field = File::make('Document')->path('documents');

        $this->assertEquals('documents', $field->path);
    }

    public function test_file_field_accepted_types_configuration(): void
    {
        $field = File::make('Document')->acceptedTypes('.pdf,.doc,.docx');

        $this->assertEquals('.pdf,.doc,.docx', $field->acceptedTypes);
    }

    public function test_file_field_max_size_configuration(): void
    {
        $field = File::make('Document')->maxSize(10240);

        $this->assertEquals(10240, $field->maxSize);
    }

    public function test_file_field_multiple_configuration(): void
    {
        $field = File::make('Documents')->multiple();

        $this->assertTrue($field->multiple);
    }

    public function test_file_field_multiple_false(): void
    {
        $field = File::make('Documents')->multiple(false);

        $this->assertFalse($field->multiple);
    }

    public function test_file_field_download_callback_configuration(): void
    {
        $callback = function ($request, $model) {
            return 'download-response';
        };

        $field = File::make('Document')->download($callback);

        $this->assertEquals($callback, $field->downloadCallback);
    }

    public function test_file_field_get_url_with_value(): void
    {
        Storage::fake('public');

        $field = File::make('Document')->disk('public');
        $field->value = 'files/document.pdf';

        $url = $field->getUrl();

        $this->assertStringContains('files/document.pdf', $url);
    }

    public function test_file_field_get_url_with_custom_path(): void
    {
        Storage::fake('public');

        $field = File::make('Document')->disk('public');

        $url = $field->getUrl('custom/path/file.pdf');

        $this->assertStringContains('custom/path/file.pdf', $url);
    }

    public function test_file_field_get_url_with_null_value(): void
    {
        $field = File::make('Document');
        $field->value = null;

        $url = $field->getUrl();

        $this->assertNull($url);
    }

    public function test_file_field_get_url_with_empty_path(): void
    {
        $field = File::make('Document');

        $url = $field->getUrl('');

        $this->assertNull($url);
    }

    public function test_file_field_meta_includes_all_properties(): void
    {
        $field = File::make('Document')
            ->disk('private')
            ->path('documents')
            ->acceptedTypes('.pdf,.doc')
            ->maxSize(5120)
            ->multiple();

        $meta = $field->meta();

        $this->assertArrayHasKey('disk', $meta);
        $this->assertArrayHasKey('path', $meta);
        $this->assertArrayHasKey('acceptedTypes', $meta);
        $this->assertArrayHasKey('maxSize', $meta);
        $this->assertArrayHasKey('multiple', $meta);
        $this->assertEquals('private', $meta['disk']);
        $this->assertEquals('documents', $meta['path']);
        $this->assertEquals('.pdf,.doc', $meta['acceptedTypes']);
        $this->assertEquals(5120, $meta['maxSize']);
        $this->assertTrue($meta['multiple']);
    }

    public function test_file_field_json_serialization(): void
    {
        $field = File::make('Upload Document')
            ->disk('documents')
            ->path('uploads')
            ->acceptedTypes('.pdf,.docx')
            ->maxSize(10240)
            ->multiple()
            ->required()
            ->help('Upload your document');

        $json = $field->jsonSerialize();

        $this->assertEquals('Upload Document', $json['name']);
        $this->assertEquals('upload_document', $json['attribute']);
        $this->assertEquals('FileField', $json['component']);
        $this->assertEquals('documents', $json['disk']);
        $this->assertEquals('uploads', $json['path']);
        $this->assertEquals('.pdf,.docx', $json['acceptedTypes']);
        $this->assertEquals(10240, $json['maxSize']);
        $this->assertTrue($json['multiple']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Upload your document', $json['helpText']);
    }

    public function test_file_field_inheritance_from_field(): void
    {
        $field = File::make('Document');

        // Test that File field inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'placeholder'));
    }

    public function test_file_field_with_validation_rules(): void
    {
        $field = File::make('Document')
            ->rules('required', 'file', 'max:10240');

        $this->assertEquals(['required', 'file', 'max:10240'], $field->rules);
    }

    public function test_file_field_resolve_preserves_value(): void
    {
        $field = File::make('Document');
        $resource = (object) ['document' => 'files/document.pdf'];

        $field->resolve($resource);

        $this->assertEquals('files/document.pdf', $field->value);
    }

    public function test_file_field_complex_configuration(): void
    {
        $downloadCallback = function ($request, $model) {
            return response()->download($model->document_path);
        };

        $field = File::make('Legal Document')
            ->disk('legal')
            ->path('contracts')
            ->acceptedTypes('.pdf,.doc,.docx')
            ->maxSize(20480)
            ->multiple()
            ->download($downloadCallback);

        // Test all configurations are set
        $this->assertEquals('legal', $field->disk);
        $this->assertEquals('contracts', $field->path);
        $this->assertEquals('.pdf,.doc,.docx', $field->acceptedTypes);
        $this->assertEquals(20480, $field->maxSize);
        $this->assertTrue($field->multiple);
        $this->assertEquals($downloadCallback, $field->downloadCallback);
    }

    public function test_file_field_fill_with_valid_file(): void
    {
        Storage::fake('public');

        $field = File::make('Document')->disk('public')->path('uploads');
        $model = new \stdClass();

        // Create a mock uploaded file
        $mockFile = $this->createMock(\Illuminate\Http\UploadedFile::class);
        $mockFile->expects($this->once())
                 ->method('isValid')
                 ->willReturn(true);
        $mockFile->expects($this->once())
                 ->method('getClientOriginalExtension')
                 ->willReturn('pdf');
        $mockFile->expects($this->once())
                 ->method('getClientOriginalName')
                 ->willReturn('test-document.pdf');
        $mockFile->expects($this->once())
                 ->method('storeAs')
                 ->with('uploads', $this->stringContains('test-document'), 'public')
                 ->willReturn('uploads/test-document_2023-01-01_12-00-00.pdf');

        $request = $this->createMock(\Illuminate\Http\Request::class);
        $request->expects($this->once())
                ->method('hasFile')
                ->with('document')
                ->willReturn(true);
        $request->expects($this->once())
                ->method('file')
                ->with('document')
                ->willReturn($mockFile);

        $field->fill($request, $model);

        $this->assertEquals('uploads/test-document_2023-01-01_12-00-00.pdf', $model->document);
    }

    public function test_file_field_fill_with_invalid_file(): void
    {
        $field = File::make('Document');
        $model = new \stdClass();

        // Create a mock uploaded file that is invalid
        $mockFile = $this->createMock(\Illuminate\Http\UploadedFile::class);
        $mockFile->expects($this->once())
                 ->method('isValid')
                 ->willReturn(false);

        $request = $this->createMock(\Illuminate\Http\Request::class);
        $request->expects($this->once())
                ->method('hasFile')
                ->with('document')
                ->willReturn(true);
        $request->expects($this->once())
                ->method('file')
                ->with('document')
                ->willReturn($mockFile);

        $field->fill($request, $model);

        // Model should not be modified when file is invalid
        $this->assertObjectNotHasProperty('document', $model);
    }

    public function test_file_field_fill_with_no_file(): void
    {
        $field = File::make('Document');
        $model = new \stdClass();

        $request = $this->createMock(\Illuminate\Http\Request::class);
        $request->expects($this->once())
                ->method('hasFile')
                ->with('document')
                ->willReturn(false);
        $request->expects($this->once())
                ->method('exists')
                ->with('document')
                ->willReturn(false);

        $field->fill($request, $model);

        // Model should not be modified when no file is uploaded
        $this->assertObjectNotHasProperty('document', $model);
    }

    public function test_file_field_fill_with_null_value(): void
    {
        $field = File::make('Document');
        $model = new \stdClass();
        $model->document = 'existing-file.pdf'; // Set existing value

        $request = $this->createMock(\Illuminate\Http\Request::class);
        $request->expects($this->once())
                ->method('hasFile')
                ->with('document')
                ->willReturn(false);
        $request->expects($this->once())
                ->method('exists')
                ->with('document')
                ->willReturn(true);
        $request->expects($this->once())
                ->method('input')
                ->with('document')
                ->willReturn(null);

        $field->fill($request, $model);

        // Model should preserve existing value when null is sent (file removal)
        $this->assertEquals('existing-file.pdf', $model->document);
    }

    public function test_file_field_fill_with_callback(): void
    {
        $callbackCalled = false;
        $fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $model->{$attribute} = 'custom-file-path.pdf';
        };

        $field = File::make('Document');
        $field->fillCallback = $fillCallback;

        $request = new \Illuminate\Http\Request();
        $model = new \stdClass();

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
        $this->assertEquals('custom-file-path.pdf', $model->document);
    }

    public function test_file_field_inherited_resolve_method(): void
    {
        $field = File::make('Document');
        $resource = (object) ['document' => 'files/test.pdf'];

        $field->resolve($resource);

        $this->assertEquals('files/test.pdf', $field->value);
    }

    public function test_file_field_inherited_authorization_methods(): void
    {
        $field = File::make('Document');

        // Test authorization methods exist
        $this->assertTrue(method_exists($field, 'canSee'));
        $this->assertTrue(method_exists($field, 'canUpdate'));
        $this->assertTrue(method_exists($field, 'authorizedToSee'));
        $this->assertTrue(method_exists($field, 'authorizedToUpdate'));

        // Test default authorization (should be true)
        $request = new \Illuminate\Http\Request();
        $this->assertTrue($field->authorizedToSee($request));
        $this->assertTrue($field->authorizedToUpdate($request));

        // Test with callback
        $field->canSee(function () { return false; });
        $this->assertFalse($field->authorizedToSee($request));
    }
}
