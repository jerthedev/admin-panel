<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\MediaLibraryFile;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Config\Repository as Config;

class MediaLibraryFileFieldTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up Laravel container and config for the field
        $container = new Container();
        $config = new Config([
            'admin-panel.media_library.accepted_mime_types.file' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain',
                'text/csv',
                'application/zip',
                'application/x-zip-compressed',
            ],
            'admin-panel.media_library.file_size_limits.file' => 10240,
        ]);

        $container->instance('config', $config);
        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_media_library_file_field_creation(): void
    {
        $field = MediaLibraryFile::make('Document');

        $this->assertInstanceOf(MediaLibraryFile::class, $field);
        $this->assertEquals('Document', $field->name);
        $this->assertEquals('document', $field->attribute);
        $this->assertEquals('MediaLibraryFileField', $field->component);
    }

    public function test_media_library_file_field_creation_with_attribute(): void
    {
        $field = MediaLibraryFile::make('Contract', 'legal_document');

        $this->assertEquals('Contract', $field->name);
        $this->assertEquals('legal_document', $field->attribute);
    }

    public function test_media_library_file_field_default_properties(): void
    {
        $field = MediaLibraryFile::make('Document');

        $this->assertEquals('files', $field->collection);
        $this->assertEquals(10240, $field->maxFileSize);
    }

    public function test_media_library_file_field_default_accepted_mime_types(): void
    {
        $field = MediaLibraryFile::make('Document');

        $expectedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'text/csv',
            'application/zip',
            'application/x-zip-compressed',
        ];

        $this->assertEquals($expectedMimeTypes, $field->acceptedMimeTypes);
    }

    public function test_get_download_url_with_fallback(): void
    {
        $field = MediaLibraryFile::make('Document');

        $url = $field->getDownloadUrl();

        $this->assertNull($url);
    }

    public function test_get_download_url_with_media_object(): void
    {
        $field = MediaLibraryFile::make('Document');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return 'https://example.com/document.pdf';
            }
        };

        $url = $field->getDownloadUrl($mockMedia);

        $this->assertEquals('https://example.com/document.pdf', $url);
    }

    public function test_get_download_url_with_field_value(): void
    {
        $field = MediaLibraryFile::make('Document');

        // Create a simple mock object with getUrl method
        $mockMedia = new class {
            public function getUrl($conversion = '') {
                return 'https://example.com/field-document.pdf';
            }
        };

        $field->value = $mockMedia;

        $url = $field->getDownloadUrl();

        $this->assertEquals('https://example.com/field-document.pdf', $url);
    }

    public function test_get_download_url_with_media_without_geturl_method(): void
    {
        $field = MediaLibraryFile::make('Document');

        // Create a simple object without getUrl method
        $mockMedia = new \stdClass();

        $url = $field->getDownloadUrl($mockMedia);

        $this->assertNull($url);
    }

    public function test_get_file_metadata_without_media(): void
    {
        $field = MediaLibraryFile::make('Document');

        $metadata = $field->getFileMetadata();

        $this->assertEquals([], $metadata);
    }

    public function test_get_file_metadata_with_media(): void
    {
        $field = MediaLibraryFile::make('Document');

        // Create a simple mock media object with all required properties
        $mockMedia = new class {
            public $name = 'contract.pdf';
            public $size = 2048000;
            public $mime_type = 'application/pdf';
            public $created_at = '2023-01-01 12:00:00';
        };

        $metadata = $field->getFileMetadata($mockMedia);

        $this->assertEquals('contract.pdf', $metadata['name']);
        $this->assertEquals(2048000, $metadata['size']);
        $this->assertEquals('application/pdf', $metadata['mime_type']);
        $this->assertEquals('2023-01-01 12:00:00', $metadata['created_at']);
        $this->assertEquals('1.95 MB', $metadata['human_readable_size']);
    }

    public function test_get_file_metadata_with_file_name_fallback(): void
    {
        $field = MediaLibraryFile::make('Document');

        // Create a mock media object with file_name instead of name
        $mockMedia = new class {
            public $file_name = 'document.docx';
            public $size = 1024;
            public $mime_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            public $created_at = '2023-01-01 12:00:00';
        };

        $metadata = $field->getFileMetadata($mockMedia);

        $this->assertEquals('document.docx', $metadata['name']);
    }

    public function test_get_file_metadata_with_unknown_name_fallback(): void
    {
        $field = MediaLibraryFile::make('Document');

        // Create a mock media object without name or file_name
        $mockMedia = new class {
            public $size = 1024;
            public $mime_type = 'text/plain';
            public $created_at = '2023-01-01 12:00:00';
        };

        $metadata = $field->getFileMetadata($mockMedia);

        $this->assertEquals('Unknown', $metadata['name']);
    }

    public function test_get_file_metadata_with_default_values(): void
    {
        $field = MediaLibraryFile::make('Document');

        // Create a minimal mock media object
        $mockMedia = new \stdClass();

        $metadata = $field->getFileMetadata($mockMedia);

        $this->assertEquals('Unknown', $metadata['name']);
        $this->assertEquals(0, $metadata['size']);
        $this->assertEquals('application/octet-stream', $metadata['mime_type']);
        $this->assertNull($metadata['created_at']);
        $this->assertEquals('0 B', $metadata['human_readable_size']);
    }

    public function test_get_file_metadata_with_field_value(): void
    {
        $field = MediaLibraryFile::make('Document');

        // Create a simple mock media object
        $mockMedia = new class {
            public $name = 'field-document.txt';
            public $size = 512;
            public $mime_type = 'text/plain';
            public $created_at = '2023-01-01 12:00:00';
        };

        $field->value = $mockMedia;

        $metadata = $field->getFileMetadata();

        $this->assertEquals('field-document.txt', $metadata['name']);
        $this->assertEquals(512, $metadata['size']);
        $this->assertEquals('text/plain', $metadata['mime_type']);
        $this->assertEquals('2023-01-01 12:00:00', $metadata['created_at']);
        $this->assertEquals('512 B', $metadata['human_readable_size']);
    }

    public function test_format_file_size_zero_bytes(): void
    {
        $field = new MediaLibraryFile('Document');
        $reflection = new \ReflectionClass($field);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);

        $result = $method->invoke($field, 0);

        $this->assertEquals('0 B', $result);
    }

    public function test_format_file_size_various_sizes(): void
    {
        $field = new MediaLibraryFile('Document');
        $reflection = new \ReflectionClass($field);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);

        $this->assertEquals('512 B', $method->invoke($field, 512));
        $this->assertEquals('1 KB', $method->invoke($field, 1024));
        $this->assertEquals('1.5 KB', $method->invoke($field, 1536));
        $this->assertEquals('1 MB', $method->invoke($field, 1048576));
        $this->assertEquals('2.5 MB', $method->invoke($field, 2621440));
        $this->assertEquals('1 GB', $method->invoke($field, 1073741824));
    }

    public function test_media_library_file_field_constructor_with_resolve_callback(): void
    {
        $callback = function ($resource, $attribute) {
            return $resource->{$attribute};
        };

        $field = new MediaLibraryFile('Document', null, $callback);

        $this->assertEquals($callback, $field->resolveCallback);
        $this->assertEquals('files', $field->collection);
        $this->assertEquals(10240, $field->maxFileSize);
    }

    public function test_media_library_file_field_json_serialization(): void
    {
        $field = MediaLibraryFile::make('Legal Document')
            ->required()
            ->help('Upload your legal document');

        $json = $field->jsonSerialize();

        $this->assertEquals('Legal Document', $json['name']);
        $this->assertEquals('legal_document', $json['attribute']);
        $this->assertEquals('MediaLibraryFileField', $json['component']);
        $this->assertEquals('files', $json['collection']);
        $this->assertEquals(10240, $json['maxFileSize']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Upload your legal document', $json['helpText']);
    }

    public function test_media_library_file_field_comprehensive_method_coverage(): void
    {
        $field = MediaLibraryFile::make('Document');

        // Test that all public methods exist and can be called
        $this->assertTrue(method_exists($field, 'getDownloadUrl'));
        $this->assertTrue(method_exists($field, 'getFileMetadata'));

        // Test method calls return expected types
        $this->assertIsArray($field->getFileMetadata());
        $this->assertNull($field->getDownloadUrl());
    }
}
