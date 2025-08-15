<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Markdown;
use PHPUnit\Framework\TestCase;

class MarkdownFieldTest extends TestCase
{
    public function test_markdown_field_creation(): void
    {
        $field = Markdown::make('Content');

        $this->assertInstanceOf(Markdown::class, $field);
        $this->assertEquals('Content', $field->name);
        $this->assertEquals('content', $field->attribute);
        $this->assertEquals('MarkdownField', $field->component);
    }

    public function test_markdown_field_creation_with_attribute(): void
    {
        $field = Markdown::make('Article Content', 'body');

        $this->assertEquals('Article Content', $field->name);
        $this->assertEquals('body', $field->attribute);
    }

    public function test_markdown_field_default_properties(): void
    {
        $field = Markdown::make('Content');

        $this->assertTrue($field->showToolbar);
        $this->assertTrue($field->enableSlashCommands);
        $this->assertNull($field->height);
        $this->assertTrue($field->autoResize);
    }

    public function test_markdown_field_with_toolbar(): void
    {
        $field = Markdown::make('Content')->withToolbar();

        $this->assertTrue($field->showToolbar);
    }

    public function test_markdown_field_with_toolbar_false(): void
    {
        $field = Markdown::make('Content')->withToolbar(false);

        $this->assertFalse($field->showToolbar);
    }

    public function test_markdown_field_with_toolbar_method_chaining(): void
    {
        $field = Markdown::make('Content')->withToolbar(true);

        $this->assertInstanceOf(Markdown::class, $field);
        $this->assertTrue($field->showToolbar);
    }

    public function test_markdown_field_without_toolbar(): void
    {
        $field = Markdown::make('Content')->withoutToolbar();

        $this->assertFalse($field->showToolbar);
    }

    public function test_markdown_field_without_toolbar_method_chaining(): void
    {
        $field = Markdown::make('Content')->withoutToolbar();

        $this->assertInstanceOf(Markdown::class, $field);
        $this->assertFalse($field->showToolbar);
    }

    public function test_markdown_field_with_slash_commands(): void
    {
        $field = Markdown::make('Content')->withSlashCommands();

        $this->assertTrue($field->enableSlashCommands);
    }

    public function test_markdown_field_with_slash_commands_false(): void
    {
        $field = Markdown::make('Content')->withSlashCommands(false);

        $this->assertFalse($field->enableSlashCommands);
    }

    public function test_markdown_field_with_slash_commands_method_chaining(): void
    {
        $field = Markdown::make('Content')->withSlashCommands(true);

        $this->assertInstanceOf(Markdown::class, $field);
        $this->assertTrue($field->enableSlashCommands);
    }

    public function test_markdown_field_without_slash_commands(): void
    {
        $field = Markdown::make('Content')->withoutSlashCommands();

        $this->assertFalse($field->enableSlashCommands);
    }

    public function test_markdown_field_without_slash_commands_method_chaining(): void
    {
        $field = Markdown::make('Content')->withoutSlashCommands();

        $this->assertInstanceOf(Markdown::class, $field);
        $this->assertFalse($field->enableSlashCommands);
    }

    public function test_markdown_field_height_configuration(): void
    {
        $field = Markdown::make('Content')->height(400);

        $this->assertEquals(400, $field->height);
        $this->assertFalse($field->autoResize); // Height setting disables auto-resize
    }

    public function test_markdown_field_height_method_chaining(): void
    {
        $field = Markdown::make('Content')->height(500);

        $this->assertInstanceOf(Markdown::class, $field);
        $this->assertEquals(500, $field->height);
        $this->assertFalse($field->autoResize);
    }

    public function test_markdown_field_auto_resize_enabled(): void
    {
        $field = Markdown::make('Content')->autoResize();

        $this->assertTrue($field->autoResize);
    }

    public function test_markdown_field_auto_resize_disabled(): void
    {
        $field = Markdown::make('Content')->autoResize(false);

        $this->assertFalse($field->autoResize);
    }

    public function test_markdown_field_auto_resize_method_chaining(): void
    {
        $field = Markdown::make('Content')->autoResize(true);

        $this->assertInstanceOf(Markdown::class, $field);
        $this->assertTrue($field->autoResize);
    }

    public function test_markdown_field_maxlength_configuration(): void
    {
        $field = Markdown::make('Content')->maxlength(1000);

        $this->assertContains('max:1000', $field->rules);
    }

    public function test_markdown_field_maxlength_method_chaining(): void
    {
        $field = Markdown::make('Content')->maxlength(500);

        $this->assertInstanceOf(Markdown::class, $field);
        $this->assertContains('max:500', $field->rules);
    }

    public function test_markdown_field_fill_normalizes_line_endings(): void
    {
        $field = Markdown::make('Content');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['content' => "Line 1\r\nLine 2\rLine 3\n"]);

        $field->fill($request, $model);

        $this->assertEquals("Line 1\nLine 2\nLine 3", $model->content);
    }

    public function test_markdown_field_fill_trims_whitespace(): void
    {
        $field = Markdown::make('Content');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['content' => "  \n\n# Heading\n\nContent\n\n  "]);

        $field->fill($request, $model);

        $this->assertEquals("# Heading\n\nContent", $model->content);
    }

    public function test_markdown_field_fill_with_null_value(): void
    {
        $field = Markdown::make('Content');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['content' => null]);

        $field->fill($request, $model);

        $this->assertNull($model->content);
    }

    public function test_markdown_field_fill_with_non_string_value(): void
    {
        $field = Markdown::make('Content');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['content' => 123]);

        $field->fill($request, $model);

        $this->assertEquals(123, $model->content);
    }

    public function test_markdown_field_fill_with_callback(): void
    {
        $field = Markdown::make('Content')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = '# Custom Content';
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['content' => '# Original Content']);

        $field->fill($request, $model);

        $this->assertEquals('# Custom Content', $model->content);
    }

    public function test_markdown_field_fill_without_request_value(): void
    {
        $field = Markdown::make('Content');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request([]);

        $field->fill($request, $model);

        $this->assertObjectNotHasProperty('content', $model);
    }

    public function test_markdown_field_meta_includes_all_properties(): void
    {
        $field = Markdown::make('Content')
            ->withoutToolbar()
            ->withoutSlashCommands()
            ->height(600)
            ->autoResize(false);

        $meta = $field->meta();

        $this->assertArrayHasKey('showToolbar', $meta);
        $this->assertArrayHasKey('enableSlashCommands', $meta);
        $this->assertArrayHasKey('height', $meta);
        $this->assertArrayHasKey('autoResize', $meta);

        $this->assertFalse($meta['showToolbar']);
        $this->assertFalse($meta['enableSlashCommands']);
        $this->assertEquals(600, $meta['height']);
        $this->assertFalse($meta['autoResize']);
    }

    public function test_markdown_field_meta_with_default_values(): void
    {
        $field = Markdown::make('Content');
        $meta = $field->meta();

        $this->assertTrue($meta['showToolbar']);
        $this->assertTrue($meta['enableSlashCommands']);
        $this->assertNull($meta['height']);
        $this->assertTrue($meta['autoResize']);
    }

    public function test_markdown_field_complex_configuration(): void
    {
        $field = Markdown::make('Article Body')
            ->withToolbar(true)
            ->withSlashCommands(false)
            ->height(800)
            ->maxlength(5000)
            ->required()
            ->help('Write your article content in markdown');

        $this->assertEquals('Article Body', $field->name);
        $this->assertEquals('article_body', $field->attribute);
        $this->assertTrue($field->showToolbar);
        $this->assertFalse($field->enableSlashCommands);
        $this->assertEquals(800, $field->height);
        $this->assertFalse($field->autoResize);
        $this->assertContains('max:5000', $field->rules);
        $this->assertContains('required', $field->rules);
        $this->assertEquals('Write your article content in markdown', $field->helpText);
    }

    public function test_markdown_field_method_chaining(): void
    {
        $field = Markdown::make('Full Configuration')
            ->withToolbar(false)
            ->withSlashCommands(true)
            ->height(400)
            ->autoResize(false)
            ->maxlength(2000);

        $this->assertInstanceOf(Markdown::class, $field);
        $this->assertFalse($field->showToolbar);
        $this->assertTrue($field->enableSlashCommands);
        $this->assertEquals(400, $field->height);
        $this->assertFalse($field->autoResize);
        $this->assertContains('max:2000', $field->rules);
    }

    public function test_markdown_field_json_serialization(): void
    {
        $field = Markdown::make('Content')
            ->withoutToolbar()
            ->withSlashCommands(true)
            ->height(300)
            ->required()
            ->help('Enter markdown content');

        $json = $field->jsonSerialize();

        $this->assertEquals('Content', $json['name']);
        $this->assertEquals('content', $json['attribute']);
        $this->assertEquals('MarkdownField', $json['component']);
        $this->assertFalse($json['showToolbar']);
        $this->assertTrue($json['enableSlashCommands']);
        $this->assertEquals(300, $json['height']);
        $this->assertFalse($json['autoResize']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Enter markdown content', $json['helpText']);
    }
}
