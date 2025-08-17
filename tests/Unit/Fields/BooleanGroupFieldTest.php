<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\BooleanGroup;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Boolean Group Field Unit Tests
 *
 * Tests for BooleanGroup field class with 100% Nova API compatibility.
 * Tests all Nova BooleanGroup field features including options, hide methods, and noValueText.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class BooleanGroupFieldTest extends TestCase
{
    /** @test */
    public function it_creates_boolean_group_field_with_nova_syntax(): void
    {
        $field = BooleanGroup::make('Permissions');

        $this->assertEquals('Permissions', $field->name);
        $this->assertEquals('permissions', $field->attribute);
        $this->assertEquals('BooleanGroupField', $field->component);
    }

    /** @test */
    public function it_creates_boolean_group_field_with_custom_attribute(): void
    {
        $field = BooleanGroup::make('User Permissions', 'user_permissions');

        $this->assertEquals('User Permissions', $field->name);
        $this->assertEquals('user_permissions', $field->attribute);
    }

    /** @test */
    public function it_has_correct_default_properties(): void
    {
        $field = BooleanGroup::make('Permissions');

        $this->assertEquals([], $field->options);
        $this->assertFalse($field->hideFalseValues);
        $this->assertFalse($field->hideTrueValues);
        $this->assertEquals('No Data', $field->noValueText);
    }

    /** @test */
    public function it_supports_nova_options_method(): void
    {
        $options = [
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ];

        $field = BooleanGroup::make('Permissions')->options($options);

        $this->assertEquals($options, $field->options);
    }

    /** @test */
    public function it_supports_nova_hide_false_values_method(): void
    {
        $field = BooleanGroup::make('Permissions')->hideFalseValues();

        $this->assertTrue($field->hideFalseValues);
        $this->assertFalse($field->hideTrueValues); // Should remain default
    }

    /** @test */
    public function it_supports_nova_hide_true_values_method(): void
    {
        $field = BooleanGroup::make('Permissions')->hideTrueValues();

        $this->assertTrue($field->hideTrueValues);
        $this->assertFalse($field->hideFalseValues); // Should remain default
    }

    /** @test */
    public function it_supports_both_hide_methods(): void
    {
        $field = BooleanGroup::make('Permissions')
            ->hideFalseValues()
            ->hideTrueValues();

        $this->assertTrue($field->hideFalseValues);
        $this->assertTrue($field->hideTrueValues);
    }

    /** @test */
    public function it_supports_nova_no_value_text_method(): void
    {
        $field = BooleanGroup::make('Permissions')->noValueText('No permissions selected.');

        $this->assertEquals('No permissions selected.', $field->noValueText);
    }

    /** @test */
    public function it_serializes_nova_properties_in_meta(): void
    {
        $options = [
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ];

        $field = BooleanGroup::make('Permissions')
            ->options($options)
            ->hideFalseValues()
            ->noValueText('No permissions selected.');

        $meta = $field->meta();

        $this->assertEquals($options, $meta['options']);
        $this->assertTrue($meta['hideFalseValues']);
        $this->assertFalse($meta['hideTrueValues']);
        $this->assertEquals('No permissions selected.', $meta['noValueText']);
    }

    /** @test */
    public function it_serializes_default_values_in_meta(): void
    {
        $field = BooleanGroup::make('Permissions');

        $meta = $field->meta();

        $this->assertEquals([], $meta['options']);
        $this->assertFalse($meta['hideFalseValues']);
        $this->assertFalse($meta['hideTrueValues']);
        $this->assertEquals('No Data', $meta['noValueText']);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = BooleanGroup::make('Permissions')
            ->options(['create' => 'Create', 'read' => 'Read'])
            ->hideFalseValues()
            ->noValueText('No permissions')
            ->nullable()
            ->help('Select user permissions');

        $this->assertInstanceOf(BooleanGroup::class, $field);
        $this->assertEquals(['create' => 'Create', 'read' => 'Read'], $field->options);
        $this->assertTrue($field->hideFalseValues);
        $this->assertEquals('No permissions', $field->noValueText);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Select user permissions', $field->helpText);
    }

    /** @test */
    public function it_inherits_all_field_methods(): void
    {
        $field = BooleanGroup::make('Permissions');

        // Test that BooleanGroup field inherits all base Field methods
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'resolve'));
        $this->assertTrue(method_exists($field, 'jsonSerialize'));

        // Test Nova-specific BooleanGroup methods
        $this->assertTrue(method_exists($field, 'options'));
        $this->assertTrue(method_exists($field, 'hideFalseValues'));
        $this->assertTrue(method_exists($field, 'hideTrueValues'));
        $this->assertTrue(method_exists($field, 'noValueText'));
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_boolean_group_field(): void
    {
        $field = BooleanGroup::make('Permissions');

        // Test Nova-compatible methods exist and return correct types
        $this->assertInstanceOf(BooleanGroup::class, $field->options(['create' => 'Create']));
        $this->assertInstanceOf(BooleanGroup::class, $field->hideFalseValues());
        $this->assertInstanceOf(BooleanGroup::class, $field->hideTrueValues());
        $this->assertInstanceOf(BooleanGroup::class, $field->noValueText('Custom text'));

        // Test component name matches Nova
        $this->assertEquals('BooleanGroupField', $field->component);

        // Test default values match Nova (create fresh field)
        $freshField = BooleanGroup::make('Fresh');
        $this->assertEquals([], $freshField->options);
        $this->assertFalse($freshField->hideFalseValues);
        $this->assertFalse($freshField->hideTrueValues);
        $this->assertEquals('No Data', $freshField->noValueText);
    }

    /** @test */
    public function it_handles_complex_nova_configuration(): void
    {
        $field = BooleanGroup::make('User Permissions')
            ->options([
                'create' => 'Create Posts',
                'edit' => 'Edit Posts',
                'delete' => 'Delete Posts',
                'publish' => 'Publish Posts',
            ])
            ->hideFalseValues()
            ->noValueText('No permissions assigned')
            ->nullable()
            ->help('Select user permissions')
            ->rules('required');

        // Test all configurations are set correctly
        $this->assertEquals([
            'create' => 'Create Posts',
            'edit' => 'Edit Posts',
            'delete' => 'Delete Posts',
            'publish' => 'Publish Posts',
        ], $field->options);
        $this->assertTrue($field->hideFalseValues);
        $this->assertEquals('No permissions assigned', $field->noValueText);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Select user permissions', $field->helpText);
        $this->assertContains('required', $field->rules);
    }

    /** @test */
    public function it_serializes_boolean_group_field_for_frontend(): void
    {
        $field = BooleanGroup::make('Permissions')
            ->options([
                'create' => 'Create',
                'read' => 'Read',
                'update' => 'Update',
                'delete' => 'Delete',
            ])
            ->hideFalseValues()
            ->noValueText('No permissions selected')
            ->help('Select the permissions for this user');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Permissions', $serialized['name']);
        $this->assertEquals('permissions', $serialized['attribute']);
        $this->assertEquals('BooleanGroupField', $serialized['component']);
        $this->assertEquals('Select the permissions for this user', $serialized['helpText']);

        // Check meta properties
        $this->assertEquals([
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ], $serialized['options']);
        $this->assertTrue($serialized['hideFalseValues']);
        $this->assertFalse($serialized['hideTrueValues']);
        $this->assertEquals('No permissions selected', $serialized['noValueText']);
    }

    /** @test */
    public function it_handles_boolean_group_field_with_validation_rules(): void
    {
        $field = BooleanGroup::make('Permissions')
            ->options(['create' => 'Create', 'read' => 'Read'])
            ->rules('required', 'array')
            ->nullable(false);

        // Test that validation rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('array', $field->rules);
        $this->assertFalse($field->nullable);

        // Test field serialization includes validation rules
        $serialized = $field->jsonSerialize();
        $this->assertEquals(['required', 'array'], $serialized['rules']);
        $this->assertFalse($serialized['nullable']);
    }

    /** @test */
    public function it_supports_nova_examples_from_documentation(): void
    {
        // Example from Nova docs: BooleanGroup::make('Permissions')->options([...])
        $field1 = BooleanGroup::make('Permissions')->options([
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ]);

        $this->assertEquals('Permissions', $field1->name);
        $this->assertEquals('permissions', $field1->attribute);
        $this->assertEquals([
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ], $field1->options);

        // Example with hide methods
        $field2 = BooleanGroup::make('Permissions')
            ->options(['create' => 'Create', 'read' => 'Read'])
            ->hideFalseValues();

        $this->assertTrue($field2->hideFalseValues);
        $this->assertFalse($field2->hideTrueValues);

        // Example with custom no value text
        $field3 = BooleanGroup::make('Permissions')
            ->options(['create' => 'Create'])
            ->noValueText('No permissions selected.');

        $this->assertEquals('No permissions selected.', $field3->noValueText);
    }

    /** @test */
    public function it_handles_edge_cases_with_empty_and_null_options(): void
    {
        // Test with empty options
        $field1 = BooleanGroup::make('Permissions')->options([]);
        $this->assertEquals([], $field1->options);

        // Test with null-like values in options
        $field2 = BooleanGroup::make('Permissions')->options([
            '' => 'Empty Key',
            '0' => 'Zero Key',
            'null' => 'Null String',
        ]);

        $this->assertEquals([
            '' => 'Empty Key',
            '0' => 'Zero Key',
            'null' => 'Null String',
        ], $field2->options);
    }

    /** @test */
    public function it_maintains_backward_compatibility_with_standard_field_features(): void
    {
        $field = BooleanGroup::make('Permissions');

        // Should work with standard field features
        $this->assertEquals([], $field->options);
        $this->assertFalse($field->hideFalseValues);
        $this->assertFalse($field->hideTrueValues);
        $this->assertEquals('No Data', $field->noValueText);

        // Meta should serialize correctly
        $meta = $field->meta();
        $this->assertEquals([], $meta['options']);
        $this->assertFalse($meta['hideFalseValues']);
        $this->assertFalse($meta['hideTrueValues']);
        $this->assertEquals('No Data', $meta['noValueText']);
    }

    /** @test */
    public function it_works_with_all_inherited_field_functionality(): void
    {
        $field = BooleanGroup::make('Permissions')
            ->options(['create' => 'Create', 'read' => 'Read'])
            ->hideFalseValues()
            ->noValueText('No permissions')
            ->nullable()
            ->readonly()
            ->help('User permissions')
            ->rules('required');

        // Test inherited functionality works
        $this->assertTrue($field->nullable);
        $this->assertTrue($field->readonly);
        $this->assertEquals('User permissions', $field->helpText);
        $this->assertContains('required', $field->rules);

        // Test Nova-specific functionality still works
        $this->assertEquals(['create' => 'Create', 'read' => 'Read'], $field->options);
        $this->assertTrue($field->hideFalseValues);
        $this->assertEquals('No permissions', $field->noValueText);
    }
}
