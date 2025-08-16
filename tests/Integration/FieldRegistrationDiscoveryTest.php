<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use JTD\AdminPanel\Fields\Field;
use JTD\AdminPanel\Tests\TestCase;
use ReflectionClass;

/**
 * Field Registration & Discovery Integration Tests.
 *
 * Tests that validate how PHP field classes are registered, discovered,
 * and made available to Vue components in the admin panel system.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class FieldRegistrationDiscoveryTest extends TestCase
{
    /**
     * All field classes that should be available in the system.
     */
    protected array $expectedFieldClasses = [
        \JTD\AdminPanel\Fields\Avatar::class,
        \JTD\AdminPanel\Fields\Badge::class,
        \JTD\AdminPanel\Fields\BelongsTo::class,
        \JTD\AdminPanel\Fields\Boolean::class,
        \JTD\AdminPanel\Fields\Code::class,
        \JTD\AdminPanel\Fields\Color::class,
        \JTD\AdminPanel\Fields\Currency::class,
        \JTD\AdminPanel\Fields\Date::class,
        \JTD\AdminPanel\Fields\DateTime::class,
        \JTD\AdminPanel\Fields\Email::class,
        \JTD\AdminPanel\Fields\File::class,
        \JTD\AdminPanel\Fields\Gravatar::class,
        \JTD\AdminPanel\Fields\HasMany::class,
        \JTD\AdminPanel\Fields\Hidden::class,
        \JTD\AdminPanel\Fields\ID::class,
        \JTD\AdminPanel\Fields\Image::class,
        \JTD\AdminPanel\Fields\ManyToMany::class,
        \JTD\AdminPanel\Fields\Markdown::class,
        \JTD\AdminPanel\Fields\MediaLibraryAvatar::class,
        \JTD\AdminPanel\Fields\MediaLibraryFile::class,
        \JTD\AdminPanel\Fields\MediaLibraryImage::class,
        \JTD\AdminPanel\Fields\MultiSelect::class,
        \JTD\AdminPanel\Fields\Number::class,
        \JTD\AdminPanel\Fields\Password::class,
        \JTD\AdminPanel\Fields\PasswordConfirmation::class,
        \JTD\AdminPanel\Fields\Select::class,
        \JTD\AdminPanel\Fields\Slug::class,
        \JTD\AdminPanel\Fields\Text::class,
        \JTD\AdminPanel\Fields\Textarea::class,
        \JTD\AdminPanel\Fields\Timezone::class,
        \JTD\AdminPanel\Fields\URL::class,
    ];

    /**
     * Expected Vue component mappings for field classes.
     */
    protected array $expectedComponentMappings = [
        \JTD\AdminPanel\Fields\Avatar::class => 'AvatarField',
        \JTD\AdminPanel\Fields\Badge::class => 'BadgeField',
        \JTD\AdminPanel\Fields\BelongsTo::class => 'BelongsToField',
        \JTD\AdminPanel\Fields\Boolean::class => 'BooleanField',
        \JTD\AdminPanel\Fields\Code::class => 'CodeField',
        \JTD\AdminPanel\Fields\Color::class => 'ColorField',
        \JTD\AdminPanel\Fields\Currency::class => 'CurrencyField',
        \JTD\AdminPanel\Fields\Date::class => 'DateField',
        \JTD\AdminPanel\Fields\DateTime::class => 'DateTimeField',
        \JTD\AdminPanel\Fields\Email::class => 'EmailField',
        \JTD\AdminPanel\Fields\File::class => 'FileField',
        \JTD\AdminPanel\Fields\Gravatar::class => 'GravatarField',
        \JTD\AdminPanel\Fields\HasMany::class => 'HasManyField',
        \JTD\AdminPanel\Fields\Hidden::class => 'HiddenField',
        \JTD\AdminPanel\Fields\ID::class => 'IDField',
        \JTD\AdminPanel\Fields\Image::class => 'ImageField',
        \JTD\AdminPanel\Fields\ManyToMany::class => 'ManyToManyField',
        \JTD\AdminPanel\Fields\Markdown::class => 'MarkdownField',
        \JTD\AdminPanel\Fields\MediaLibraryAvatar::class => 'MediaLibraryAvatarField',
        \JTD\AdminPanel\Fields\MediaLibraryFile::class => 'MediaLibraryFileField',
        \JTD\AdminPanel\Fields\MediaLibraryImage::class => 'MediaLibraryImageField',
        \JTD\AdminPanel\Fields\MultiSelect::class => 'MultiSelectField',
        \JTD\AdminPanel\Fields\Number::class => 'NumberField',
        \JTD\AdminPanel\Fields\Password::class => 'PasswordField',
        \JTD\AdminPanel\Fields\PasswordConfirmation::class => 'PasswordConfirmationField',
        \JTD\AdminPanel\Fields\Select::class => 'SelectField',
        \JTD\AdminPanel\Fields\Slug::class => 'SlugField',
        \JTD\AdminPanel\Fields\Text::class => 'TextField',
        \JTD\AdminPanel\Fields\Textarea::class => 'TextareaField',
        \JTD\AdminPanel\Fields\Timezone::class => 'TimezoneField',
        \JTD\AdminPanel\Fields\URL::class => 'URLField',
    ];

    public function test_all_expected_field_classes_exist(): void
    {
        foreach ($this->expectedFieldClasses as $fieldClass) {
            $this->assertTrue(
                class_exists($fieldClass),
                "Field class {$fieldClass} should exist",
            );
        }
    }

    public function test_all_field_classes_extend_base_field(): void
    {
        foreach ($this->expectedFieldClasses as $fieldClass) {
            $reflection = new ReflectionClass($fieldClass);

            $this->assertTrue(
                $reflection->isSubclassOf(Field::class),
                "Field class {$fieldClass} should extend the base Field class",
            );
        }
    }

    public function test_all_field_classes_have_component_property(): void
    {
        foreach ($this->expectedFieldClasses as $fieldClass) {
            $field = new $fieldClass('Test Field');

            $this->assertObjectHasProperty(
                'component',
                $field,
                "Field class {$fieldClass} should have a component property",
            );

            $this->assertIsString(
                $field->component,
                "Field class {$fieldClass} component property should be a string",
            );

            $this->assertNotEmpty(
                $field->component,
                "Field class {$fieldClass} component property should not be empty",
            );
        }
    }

    public function test_field_vue_component_mappings_are_correct(): void
    {
        foreach ($this->expectedComponentMappings as $fieldClass => $expectedComponent) {
            $field = new $fieldClass('Test Field');

            $this->assertEquals(
                $expectedComponent,
                $field->component,
                "Field class {$fieldClass} should map to Vue component {$expectedComponent}",
            );
        }
    }

    public function test_field_classes_can_be_instantiated(): void
    {
        foreach ($this->expectedFieldClasses as $fieldClass) {
            $field = new $fieldClass('Test Field');

            $this->assertInstanceOf(
                $fieldClass,
                $field,
                "Field class {$fieldClass} should be instantiable",
            );

            $this->assertInstanceOf(
                Field::class,
                $field,
                "Field class {$fieldClass} should be an instance of base Field class",
            );
        }
    }

    public function test_field_classes_have_required_properties(): void
    {
        foreach ($this->expectedFieldClasses as $fieldClass) {
            $field = new $fieldClass('Test Field');

            // Test required properties exist
            $this->assertObjectHasProperty('name', $field, "Field {$fieldClass} should have name property");
            $this->assertObjectHasProperty('attribute', $field, "Field {$fieldClass} should have attribute property");
            $this->assertObjectHasProperty('component', $field, "Field {$fieldClass} should have component property");

            // Test property types
            $this->assertIsString($field->name, "Field {$fieldClass} name should be string");
            $this->assertIsString($field->attribute, "Field {$fieldClass} attribute should be string");
            $this->assertIsString($field->component, "Field {$fieldClass} component should be string");

            // Test property values
            $this->assertEquals('Test Field', $field->name, "Field {$fieldClass} should have correct name");

            // Special case for ID field which defaults to 'id' attribute
            if ($fieldClass === \JTD\AdminPanel\Fields\ID::class) {
                $this->assertEquals('id', $field->attribute, "ID field should default to 'id' attribute");
            } else {
                $this->assertEquals('test_field', $field->attribute, "Field {$fieldClass} should have correct attribute");
            }
        }
    }

    public function test_field_classes_support_make_static_method(): void
    {
        foreach ($this->expectedFieldClasses as $fieldClass) {
            $this->assertTrue(
                method_exists($fieldClass, 'make'),
                "Field class {$fieldClass} should have static make method",
            );

            $field = $fieldClass::make('Test Field');

            $this->assertInstanceOf(
                $fieldClass,
                $field,
                "Field class {$fieldClass}::make() should return instance of {$fieldClass}",
            );

            $this->assertEquals(
                'Test Field',
                $field->name,
                "Field class {$fieldClass}::make() should set correct name",
            );
        }
    }

    public function test_field_metadata_extraction(): void
    {
        foreach ($this->expectedFieldClasses as $fieldClass) {
            $field = new $fieldClass('Test Field');

            // Test that meta() method exists and returns array
            $this->assertTrue(
                method_exists($field, 'meta'),
                "Field class {$fieldClass} should have meta() method",
            );

            $meta = $field->meta();

            $this->assertIsArray(
                $meta,
                "Field class {$fieldClass} meta() should return array",
            );

            // Test that jsonSerialize contains component information (this is where component is included)
            $this->assertTrue(
                method_exists($field, 'jsonSerialize'),
                "Field class {$fieldClass} should have jsonSerialize() method",
            );

            $serialized = $field->jsonSerialize();

            $this->assertArrayHasKey(
                'component',
                $serialized,
                "Field class {$fieldClass} jsonSerialize should contain component key",
            );

            $this->assertEquals(
                $field->component,
                $serialized['component'],
                "Field class {$fieldClass} serialized component should match field component",
            );
        }
    }

    public function test_field_serialization_to_array(): void
    {
        foreach ($this->expectedFieldClasses as $fieldClass) {
            $field = new $fieldClass('Test Field');

            // Test that jsonSerialize() method exists (this is the actual serialization method)
            $this->assertTrue(
                method_exists($field, 'jsonSerialize'),
                "Field class {$fieldClass} should have jsonSerialize() method",
            );

            $array = $field->jsonSerialize();

            $this->assertIsArray(
                $array,
                "Field class {$fieldClass} jsonSerialize() should return array",
            );

            // Test required keys in serialized array
            $requiredKeys = ['name', 'attribute', 'component'];
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $array,
                    "Field class {$fieldClass} jsonSerialize() should contain {$key} key",
                );
            }
        }
    }

    public function test_field_discovery_and_enumeration(): void
    {
        // Test that we can discover all field classes
        $discoveredFields = [];

        foreach ($this->expectedFieldClasses as $fieldClass) {
            $field = new $fieldClass('Test Field');
            $discoveredFields[$fieldClass] = $field;
        }

        $this->assertCount(
            count($this->expectedFieldClasses),
            $discoveredFields,
            'Should discover all expected field classes',
        );

        // Test that each discovered field has proper structure
        foreach ($discoveredFields as $fieldClass => $field) {
            $this->assertInstanceOf(
                Field::class,
                $field,
                "Discovered field {$fieldClass} should be instance of Field",
            );

            $this->assertNotEmpty(
                $field->component,
                "Discovered field {$fieldClass} should have non-empty component",
            );
        }
    }

    public function test_field_configuration_inheritance(): void
    {
        foreach ($this->expectedFieldClasses as $fieldClass) {
            $field = new $fieldClass('Test Field');

            // Test that field inherits base configuration methods
            $this->assertTrue(
                method_exists($field, 'rules'),
                "Field {$fieldClass} should inherit rules() method",
            );

            $this->assertTrue(
                method_exists($field, 'sortable'),
                "Field {$fieldClass} should inherit sortable() method",
            );

            $this->assertTrue(
                method_exists($field, 'nullable'),
                "Field {$fieldClass} should inherit nullable() method",
            );

            $this->assertTrue(
                method_exists($field, 'readonly'),
                "Field {$fieldClass} should inherit readonly() method",
            );

            // Test method chaining works
            $configuredField = $field->rules('required')->sortable()->nullable();

            $this->assertInstanceOf(
                $fieldClass,
                $configuredField,
                "Field {$fieldClass} should support method chaining",
            );
        }
    }

    public function test_error_handling_for_invalid_field_instantiation(): void
    {
        // Test that field classes handle invalid constructor parameters gracefully
        foreach ($this->expectedFieldClasses as $fieldClass) {
            // Test with empty name - should not throw exception
            try {
                $field = new $fieldClass('');
                $this->assertInstanceOf($fieldClass, $field);
            } catch (\Exception $e) {
                $this->fail("Field {$fieldClass} should handle empty name gracefully, but threw: ".$e->getMessage());
            }

            // Test with null attribute - should use default
            try {
                $field = new $fieldClass('Test Field', null);
                $this->assertInstanceOf($fieldClass, $field);
                $this->assertIsString($field->attribute);
            } catch (\Exception $e) {
                $this->fail("Field {$fieldClass} should handle null attribute gracefully, but threw: ".$e->getMessage());
            }
        }
    }

    public function test_vue_component_name_resolution(): void
    {
        foreach ($this->expectedFieldClasses as $fieldClass) {
            $field = new $fieldClass('Test Field');

            // Test that component name follows expected pattern
            $this->assertStringEndsWith(
                'Field',
                $field->component,
                "Field {$fieldClass} component name should end with 'Field'",
            );

            // Test that component name doesn't contain spaces or special characters
            $this->assertMatchesRegularExpression(
                '/^[A-Za-z][A-Za-z0-9]*Field$/',
                $field->component,
                "Field {$fieldClass} component name should be valid Vue component name",
            );
        }
    }

    public function test_field_count_matches_expected(): void
    {
        $this->assertCount(
            31,
            $this->expectedFieldClasses,
            'Should have exactly 31 field classes (excluding MediaLibraryField base class)',
        );

        $this->assertCount(
            31,
            $this->expectedComponentMappings,
            'Should have exactly 31 component mappings',
        );
    }
}
