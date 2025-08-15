<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Code;
use PHPUnit\Framework\TestCase;

class CodeFieldTest extends TestCase
{
    public function test_code_field_creation(): void
    {
        $field = Code::make('Source Code');

        $this->assertInstanceOf(Code::class, $field);
        $this->assertEquals('Source Code', $field->name);
        $this->assertEquals('source_code', $field->attribute);
        $this->assertEquals('CodeField', $field->component);
    }

    public function test_code_field_creation_with_attribute(): void
    {
        $field = Code::make('Configuration', 'config');

        $this->assertEquals('Configuration', $field->name);
        $this->assertEquals('config', $field->attribute);
    }

    public function test_code_field_default_properties(): void
    {
        $field = Code::make('Code');

        $this->assertEquals('text', $field->language);
        $this->assertEquals('light', $field->theme);
        $this->assertFalse($field->showLineNumbers);
        $this->assertEquals(200, $field->height);
        $this->assertFalse($field->readOnly);
        $this->assertFalse($field->wrapLines);
        $this->assertFalse($field->autoDetectLanguage);
    }

    public function test_code_field_language_configuration(): void
    {
        $field = Code::make('PHP Code')->language('php');

        $this->assertEquals('php', $field->language);
    }

    public function test_code_field_language_method_chaining(): void
    {
        $field = Code::make('JavaScript')->language('javascript');

        $this->assertInstanceOf(Code::class, $field);
        $this->assertEquals('javascript', $field->language);
    }

    public function test_code_field_theme_configuration(): void
    {
        $field = Code::make('Code')->theme('dark');

        $this->assertEquals('dark', $field->theme);
    }

    public function test_code_field_theme_method_chaining(): void
    {
        $field = Code::make('Code')->theme('monokai');

        $this->assertInstanceOf(Code::class, $field);
        $this->assertEquals('monokai', $field->theme);
    }

    public function test_code_field_line_numbers_enabled(): void
    {
        $field = Code::make('Code')->lineNumbers();

        $this->assertTrue($field->showLineNumbers);
    }

    public function test_code_field_line_numbers_disabled(): void
    {
        $field = Code::make('Code')->lineNumbers(false);

        $this->assertFalse($field->showLineNumbers);
    }

    public function test_code_field_line_numbers_method_chaining(): void
    {
        $field = Code::make('Code')->lineNumbers(true);

        $this->assertInstanceOf(Code::class, $field);
        $this->assertTrue($field->showLineNumbers);
    }

    public function test_code_field_height_configuration(): void
    {
        $field = Code::make('Code')->height(400);

        $this->assertEquals(400, $field->height);
    }

    public function test_code_field_height_method_chaining(): void
    {
        $field = Code::make('Code')->height(500);

        $this->assertInstanceOf(Code::class, $field);
        $this->assertEquals(500, $field->height);
    }

    public function test_code_field_read_only_enabled(): void
    {
        $field = Code::make('Code')->readOnly();

        $this->assertTrue($field->readOnly);
    }

    public function test_code_field_read_only_disabled(): void
    {
        $field = Code::make('Code')->readOnly(false);

        $this->assertFalse($field->readOnly);
    }

    public function test_code_field_read_only_method_chaining(): void
    {
        $field = Code::make('Code')->readOnly(true);

        $this->assertInstanceOf(Code::class, $field);
        $this->assertTrue($field->readOnly);
    }

    public function test_code_field_wrap_lines_enabled(): void
    {
        $field = Code::make('Code')->wrapLines();

        $this->assertTrue($field->wrapLines);
    }

    public function test_code_field_wrap_lines_disabled(): void
    {
        $field = Code::make('Code')->wrapLines(false);

        $this->assertFalse($field->wrapLines);
    }

    public function test_code_field_wrap_lines_method_chaining(): void
    {
        $field = Code::make('Code')->wrapLines(true);

        $this->assertInstanceOf(Code::class, $field);
        $this->assertTrue($field->wrapLines);
    }

    public function test_code_field_auto_detect_language_enabled(): void
    {
        $field = Code::make('Code')->autoDetectLanguage();

        $this->assertTrue($field->autoDetectLanguage);
    }

    public function test_code_field_auto_detect_language_disabled(): void
    {
        $field = Code::make('Code')->autoDetectLanguage(false);

        $this->assertFalse($field->autoDetectLanguage);
    }

    public function test_code_field_auto_detect_language_method_chaining(): void
    {
        $field = Code::make('Code')->autoDetectLanguage(true);

        $this->assertInstanceOf(Code::class, $field);
        $this->assertTrue($field->autoDetectLanguage);
    }

    public function test_code_field_get_supported_languages(): void
    {
        $field = Code::make('Code');
        $languages = $field->getSupportedLanguages();

        $this->assertIsArray($languages);
        $this->assertContains('php', $languages);
        $this->assertContains('javascript', $languages);
        $this->assertContains('python', $languages);
        $this->assertContains('sql', $languages);
        $this->assertContains('html', $languages);
        $this->assertContains('css', $languages);
        $this->assertContains('json', $languages);
        $this->assertContains('markdown', $languages);
    }

    public function test_code_field_supported_languages_count(): void
    {
        $field = Code::make('Code');
        $languages = $field->getSupportedLanguages();

        // Should have 30 supported languages as mentioned in the class
        $this->assertGreaterThanOrEqual(30, count($languages));
    }

    public function test_code_field_meta_includes_all_properties(): void
    {
        $field = Code::make('Configuration')
            ->language('json')
            ->theme('dark')
            ->lineNumbers(true)
            ->height(350)
            ->readOnly(true)
            ->wrapLines(true)
            ->autoDetectLanguage(true);

        $meta = $field->meta();

        $this->assertArrayHasKey('language', $meta);
        $this->assertArrayHasKey('theme', $meta);
        $this->assertArrayHasKey('showLineNumbers', $meta);
        $this->assertArrayHasKey('height', $meta);
        $this->assertArrayHasKey('readOnly', $meta);
        $this->assertArrayHasKey('wrapLines', $meta);
        $this->assertArrayHasKey('autoDetectLanguage', $meta);
        $this->assertArrayHasKey('supportedLanguages', $meta);

        $this->assertEquals('json', $meta['language']);
        $this->assertEquals('dark', $meta['theme']);
        $this->assertTrue($meta['showLineNumbers']);
        $this->assertEquals(350, $meta['height']);
        $this->assertTrue($meta['readOnly']);
        $this->assertTrue($meta['wrapLines']);
        $this->assertTrue($meta['autoDetectLanguage']);
        $this->assertIsArray($meta['supportedLanguages']);
    }

    public function test_code_field_meta_with_default_values(): void
    {
        $field = Code::make('Code');
        $meta = $field->meta();

        $this->assertEquals('text', $meta['language']);
        $this->assertEquals('light', $meta['theme']);
        $this->assertFalse($meta['showLineNumbers']);
        $this->assertEquals(200, $meta['height']);
        $this->assertFalse($meta['readOnly']);
        $this->assertFalse($meta['wrapLines']);
        $this->assertFalse($meta['autoDetectLanguage']);
    }

    public function test_code_field_complex_configuration(): void
    {
        $field = Code::make('SQL Query')
            ->language('sql')
            ->theme('monokai')
            ->lineNumbers(true)
            ->height(600)
            ->wrapLines(true)
            ->autoDetectLanguage(false);

        $this->assertEquals('sql', $field->language);
        $this->assertEquals('monokai', $field->theme);
        $this->assertTrue($field->showLineNumbers);
        $this->assertEquals(600, $field->height);
        $this->assertFalse($field->readOnly);
        $this->assertTrue($field->wrapLines);
        $this->assertFalse($field->autoDetectLanguage);
    }

    public function test_code_field_method_chaining(): void
    {
        $field = Code::make('Full Configuration')
            ->language('typescript')
            ->theme('vs-dark')
            ->lineNumbers(true)
            ->height(800)
            ->readOnly(false)
            ->wrapLines(true)
            ->autoDetectLanguage(false);

        $this->assertInstanceOf(Code::class, $field);
        $this->assertEquals('typescript', $field->language);
        $this->assertEquals('vs-dark', $field->theme);
        $this->assertTrue($field->showLineNumbers);
        $this->assertEquals(800, $field->height);
        $this->assertFalse($field->readOnly);
        $this->assertTrue($field->wrapLines);
        $this->assertFalse($field->autoDetectLanguage);
    }

    public function test_code_field_json_serialization(): void
    {
        $field = Code::make('Source Code')
            ->language('php')
            ->theme('dark')
            ->lineNumbers(true)
            ->height(400)
            ->required()
            ->help('Enter your PHP code');

        $json = $field->jsonSerialize();

        $this->assertEquals('Source Code', $json['name']);
        $this->assertEquals('source_code', $json['attribute']);
        $this->assertEquals('CodeField', $json['component']);
        $this->assertEquals('php', $json['language']);
        $this->assertEquals('dark', $json['theme']);
        $this->assertTrue($json['showLineNumbers']);
        $this->assertEquals(400, $json['height']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Enter your PHP code', $json['helpText']);
    }
}
