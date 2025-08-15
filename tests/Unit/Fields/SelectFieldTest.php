<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Select;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Select Field Unit Tests
 *
 * Tests for Select field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class SelectFieldTest extends TestCase
{
    public function test_select_field_component(): void
    {
        $field = Select::make('Status');

        $this->assertEquals('SelectField', $field->component);
    }

    public function test_select_field_options(): void
    {
        $options = [
            'draft' => 'Draft',
            'published' => 'Published',
        ];

        $field = Select::make('Status')->options($options);

        $this->assertEquals($options, $field->options);
    }

    public function test_select_field_searchable(): void
    {
        $field = Select::make('Status')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_select_field_searchable_false(): void
    {
        $field = Select::make('Status')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_select_field_creation(): void
    {
        $field = Select::make('Status');

        $this->assertEquals('Status', $field->name);
        $this->assertEquals('status', $field->attribute);
        $this->assertEquals('SelectField', $field->component);
    }

    public function test_select_field_with_custom_attribute(): void
    {
        $field = Select::make('Post Status', 'post_status');

        $this->assertEquals('Post Status', $field->name);
        $this->assertEquals('post_status', $field->attribute);
    }

    public function test_select_field_display_using_labels_default(): void
    {
        $field = Select::make('Status');

        $this->assertTrue($field->displayUsingLabels);
    }

    public function test_select_field_display_using_labels_configuration(): void
    {
        $field = Select::make('Status')->displayUsingLabels(false);

        $this->assertFalse($field->displayUsingLabels);
    }

    public function test_select_field_enum_configuration(): void
    {
        // Create a mock enum for testing
        $field = Select::make('Status');

        // Test that enum method exists and can be called
        $this->assertTrue(method_exists($field, 'enum'));
    }

    public function test_select_field_fill_validates_against_options(): void
    {
        $options = [
            'draft' => 'Draft',
            'published' => 'Published',
        ];

        $field = Select::make('Status')->options($options);
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['status' => 'published']);

        $field->fill($request, $model);

        $this->assertEquals('published', $model->status);
    }

    public function test_select_field_fill_rejects_invalid_options(): void
    {
        $options = [
            'draft' => 'Draft',
            'published' => 'Published',
        ];

        $field = Select::make('Status')->options($options);
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['status' => 'invalid']);

        $field->fill($request, $model);

        $this->assertNull($model->status);
    }

    public function test_select_field_fill_handles_empty_value(): void
    {
        $field = Select::make('Status');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['status' => '']);

        $field->fill($request, $model);

        $this->assertEquals('', $model->status);
    }

    public function test_select_field_fill_with_callback(): void
    {
        $field = Select::make('Status')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 'custom-value';
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['status' => 'published']);

        $field->fill($request, $model);

        $this->assertEquals('custom-value', $model->status);
    }

    public function test_select_field_meta_includes_all_properties(): void
    {
        $options = ['draft' => 'Draft', 'published' => 'Published'];
        $field = Select::make('Status')
            ->options($options)
            ->searchable()
            ->displayUsingLabels(false);

        $meta = $field->meta();

        $this->assertArrayHasKey('options', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('displayUsingLabels', $meta);
        $this->assertEquals($options, $meta['options']);
        $this->assertTrue($meta['searchable']);
        $this->assertFalse($meta['displayUsingLabels']);
    }

    public function test_select_field_json_serialization(): void
    {
        $options = ['active' => 'Active', 'inactive' => 'Inactive'];
        $field = Select::make('User Status')
            ->options($options)
            ->searchable()
            ->required()
            ->help('Select user status');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Status', $json['name']);
        $this->assertEquals('user_status', $json['attribute']);
        $this->assertEquals('SelectField', $json['component']);
        $this->assertEquals($options, $json['options']);
        $this->assertTrue($json['searchable']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Select user status', $json['helpText']);
    }

    public function test_select_field_enum_with_valid_enum(): void
    {
        // Create a mock enum class for testing
        if (!enum_exists('SelectTestEnum')) {
            eval('
                enum SelectTestEnum: string {
                    case DRAFT = "draft";
                    case PUBLISHED = "published";
                    case ARCHIVED = "archived";
                }
            ');
        }

        $field = Select::make('Status');
        $field->enum('SelectTestEnum');

        $expectedOptions = [
            'draft' => 'DRAFT',
            'published' => 'PUBLISHED',
            'archived' => 'ARCHIVED',
        ];

        $this->assertEquals($expectedOptions, $field->options);
    }

    public function test_select_field_enum_with_invalid_class(): void
    {
        $field = Select::make('Status');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class NonExistentSelectEnum is not an enum.');

        $field->enum('NonExistentSelectEnum');
    }

    public function test_select_field_resolve_with_display_using_labels(): void
    {
        $options = [
            'draft' => 'Draft Status',
            'published' => 'Published Status',
            'archived' => 'Archived Status',
        ];

        $field = Select::make('Status')
            ->options($options)
            ->displayUsingLabels(true);

        $resource = (object) ['status' => 'published'];

        $field->resolve($resource);

        $expected = [
            'value' => 'published',
            'label' => 'Published Status',
        ];

        $this->assertEquals($expected, $field->value);
    }

    public function test_select_field_resolve_with_display_using_labels_false(): void
    {
        $options = [
            'draft' => 'Draft Status',
            'published' => 'Published Status',
        ];

        $field = Select::make('Status')
            ->options($options)
            ->displayUsingLabels(false);

        $resource = (object) ['status' => 'published'];

        $field->resolve($resource);

        // Should just return the raw value
        $this->assertEquals('published', $field->value);
    }

    public function test_select_field_resolve_with_missing_option(): void
    {
        $options = [
            'draft' => 'Draft Status',
            'published' => 'Published Status',
        ];

        $field = Select::make('Status')
            ->options($options)
            ->displayUsingLabels(true);

        $resource = (object) ['status' => 'unknown'];

        $field->resolve($resource);

        $expected = [
            'value' => 'unknown',
            'label' => 'unknown', // Falls back to the value itself
        ];

        $this->assertEquals($expected, $field->value);
    }

    public function test_select_field_resolve_with_null_value(): void
    {
        $options = [
            'draft' => 'Draft Status',
            'published' => 'Published Status',
        ];

        $field = Select::make('Status')
            ->options($options)
            ->displayUsingLabels(true);

        $resource = (object) ['status' => null];

        $field->resolve($resource);

        // Should remain null when value is null
        $this->assertNull($field->value);
    }

    public function test_select_field_resolve_with_empty_options(): void
    {
        $field = Select::make('Status')
            ->displayUsingLabels(true);

        $resource = (object) ['status' => 'published'];

        $field->resolve($resource);

        // Should just return the raw value when no options are set
        $this->assertEquals('published', $field->value);
    }
}
