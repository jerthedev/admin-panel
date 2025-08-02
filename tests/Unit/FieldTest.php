<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Fields\Email;
use JTD\AdminPanel\Fields\Number;
use JTD\AdminPanel\Fields\Password;
use JTD\AdminPanel\Fields\Select;
use JTD\AdminPanel\Fields\Textarea;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Field Unit Tests
 *
 * Tests for field classes including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class FieldTest extends TestCase
{
    public function test_text_field_creation(): void
    {
        $field = Text::make('Name');

        $this->assertEquals('Name', $field->name);
        $this->assertEquals('name', $field->attribute);
        $this->assertEquals('TextField', $field->component);
    }

    public function test_field_with_custom_attribute(): void
    {
        $field = Text::make('Full Name', 'full_name');

        $this->assertEquals('Full Name', $field->name);
        $this->assertEquals('full_name', $field->attribute);
    }

    public function test_field_rules_configuration(): void
    {
        $field = Text::make('Name')->rules('required', 'max:255');

        $this->assertEquals(['required', 'max:255'], $field->rules);
    }

    public function test_field_creation_rules(): void
    {
        $field = Text::make('Name')
            ->rules('required')
            ->creationRules('min:3');

        $this->assertEquals(['required'], $field->rules);
        $this->assertEquals(['min:3'], $field->creationRules);
    }

    public function test_field_update_rules(): void
    {
        $field = Text::make('Name')
            ->rules('required')
            ->updateRules('nullable');

        $this->assertEquals(['required'], $field->rules);
        $this->assertEquals(['nullable'], $field->updateRules);
    }

    public function test_field_sortable(): void
    {
        $field = Text::make('Name')->sortable();

        $this->assertTrue($field->sortable);
    }

    public function test_field_nullable(): void
    {
        $field = Text::make('Name')->nullable();

        $this->assertTrue($field->nullable);
    }

    public function test_field_readonly(): void
    {
        $field = Text::make('Name')->readonly();

        $this->assertTrue($field->readonly);
    }

    public function test_field_visibility_methods(): void
    {
        $field = Text::make('Name');

        // Default visibility
        $this->assertTrue($field->isShownOnIndex());
        $this->assertTrue($field->isShownOnDetail());
        $this->assertTrue($field->isShownOnForms());

        // Hide on index
        $field->hideFromIndex();
        $this->assertFalse($field->isShownOnIndex());

        // Only on forms
        $field = Text::make('Password')->onlyOnForms();
        $this->assertFalse($field->isShownOnIndex());
        $this->assertFalse($field->isShownOnDetail());
        $this->assertTrue($field->isShownOnForms());

        // Except on forms
        $field = Text::make('ID')->exceptOnForms();
        $this->assertTrue($field->isShownOnIndex());
        $this->assertTrue($field->isShownOnDetail());
        $this->assertFalse($field->isShownOnForms());
    }

    public function test_email_field_component(): void
    {
        $field = Email::make('Email');

        $this->assertEquals('EmailField', $field->component);
    }

    public function test_password_field_component(): void
    {
        $field = Password::make('Password');

        $this->assertEquals('PasswordField', $field->component);
    }

    public function test_textarea_field_component(): void
    {
        $field = Textarea::make('Description');

        $this->assertEquals('TextareaField', $field->component);
    }

    public function test_number_field_component(): void
    {
        $field = Number::make('Age');

        $this->assertEquals('NumberField', $field->component);
    }

    public function test_number_field_configuration(): void
    {
        $field = Number::make('Price')
            ->min(0)
            ->max(1000)
            ->step(0.01);

        $this->assertEquals(0, $field->min);
        $this->assertEquals(1000, $field->max);
        $this->assertEquals(0.01, $field->step);
    }

    public function test_boolean_field_component(): void
    {
        $field = Boolean::make('Active');

        $this->assertEquals('BooleanField', $field->component);
    }

    public function test_boolean_field_values(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('yes')
            ->falseValue('no');

        $this->assertEquals('yes', $field->trueValue);
        $this->assertEquals('no', $field->falseValue);
    }

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

    public function test_field_help_text(): void
    {
        $field = Text::make('Name')->help('Enter your full name');

        $this->assertEquals('Enter your full name', $field->helpText);
    }

    public function test_field_placeholder(): void
    {
        $field = Text::make('Name')->placeholder('Enter name...');

        $this->assertEquals('Enter name...', $field->placeholder);
    }

    public function test_field_default_value(): void
    {
        $field = Text::make('Status')->default('active');

        $this->assertEquals('active', $field->default);
    }

    public function test_field_json_serialization(): void
    {
        $field = Text::make('Name')
            ->rules('required')
            ->sortable()
            ->help('Enter your name');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Name', $json['name']);
        $this->assertEquals('name', $json['attribute']);
        $this->assertEquals('TextField', $json['component']);
        $this->assertEquals(['required'], $json['rules']);
        $this->assertTrue($json['sortable']);
        $this->assertEquals('Enter your name', $json['helpText']);
    }

    public function test_field_resolve_value(): void
    {
        $field = Text::make('Name');
        $model = (object) ['name' => 'John Doe'];

        $value = $field->resolveValue($model);

        $this->assertEquals('John Doe', $value);
    }

    public function test_field_resolve_nested_value(): void
    {
        $field = Text::make('User Name', 'user.name');
        $model = (object) [
            'user' => (object) ['name' => 'John Doe']
        ];

        $value = $field->resolveValue($model);

        $this->assertEquals('John Doe', $value);
    }
}
