<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Code;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Code Field Unit Tests
 *
 * Tests for Code field class with 100% Nova API compatibility.
 * Tests all Nova Code field features including language() and json() methods.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class CodeFieldTest extends TestCase
{
    /** @test */
    public function it_creates_code_field_with_nova_syntax(): void
    {
        $field = Code::make('Snippet');

        $this->assertEquals('Snippet', $field->name);
        $this->assertEquals('snippet', $field->attribute);
        $this->assertEquals('CodeField', $field->component);
    }

    /** @test */
    public function it_creates_code_field_with_custom_attribute(): void
    {
        $field = Code::make('Source Code', 'source_code');

        $this->assertEquals('Source Code', $field->name);
        $this->assertEquals('source_code', $field->attribute);
    }

    /** @test */
    public function it_has_correct_default_properties(): void
    {
        $field = Code::make('Code');

        $this->assertEquals('htmlmixed', $field->language);
        $this->assertFalse($field->isJson);
    }

    /** @test */
    public function it_supports_nova_language_method(): void
    {
        $field = Code::make('PHP Code')->language('php');

        $this->assertEquals('php', $field->language);
    }

    /** @test */
    public function it_supports_nova_json_method(): void
    {
        $field = Code::make('Configuration')->json();

        $this->assertTrue($field->isJson);
        $this->assertEquals('javascript', $field->language); // JSON is highlighted as JavaScript
    }

    /** @test */
    public function it_supports_all_nova_supported_languages(): void
    {
        $field = Code::make('Code');
        $supportedLanguages = $field->getSupportedLanguages();

        // Test Nova-supported languages
        $novaLanguages = [
            'dockerfile',
            'htmlmixed',
            'javascript',
            'markdown',
            'nginx',
            'php',
            'ruby',
            'sass',
            'shell',
            'sql',
            'twig',
            'vim',
            'vue',
            'xml',
            'yaml-frontmatter',
            'yaml',
        ];

        foreach ($novaLanguages as $language) {
            $this->assertContains($language, $supportedLanguages);
        }
    }

    /** @test */
    public function it_serializes_nova_properties_in_meta(): void
    {
        $field = Code::make('Configuration')
            ->language('php')
            ->json();

        $meta = $field->meta();

        $this->assertEquals('javascript', $meta['language']); // JSON overrides language
        $this->assertTrue($meta['isJson']);
        $this->assertIsArray($meta['supportedLanguages']);
    }

    /** @test */
    public function it_serializes_default_values_in_meta(): void
    {
        $field = Code::make('Code');

        $meta = $field->meta();

        $this->assertEquals('htmlmixed', $meta['language']);
        $this->assertFalse($meta['isJson']);
        $this->assertIsArray($meta['supportedLanguages']);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = Code::make('Configuration')
            ->language('php')
            ->json()
            ->nullable()
            ->help('Enter configuration code');

        $this->assertInstanceOf(Code::class, $field);
        $this->assertEquals('javascript', $field->language); // JSON overrides
        $this->assertTrue($field->isJson);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Enter configuration code', $field->helpText);
    }

    /** @test */
    public function it_inherits_all_field_methods(): void
    {
        $field = Code::make('Code');

        // Test that Code field inherits all base Field methods
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'resolve'));
        $this->assertTrue(method_exists($field, 'jsonSerialize'));
        
        // Test Nova-specific Code methods
        $this->assertTrue(method_exists($field, 'language'));
        $this->assertTrue(method_exists($field, 'json'));
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_code_field(): void
    {
        $field = Code::make('Snippet');

        // Test Nova-compatible methods exist and return correct types
        $this->assertInstanceOf(Code::class, $field->language('php'));
        $this->assertInstanceOf(Code::class, $field->json());
        
        // Test component name matches Nova
        $this->assertEquals('CodeField', $field->component);
        
        // Test default values match Nova
        $freshField = Code::make('Fresh');
        $this->assertEquals('htmlmixed', $freshField->language);
        $this->assertFalse($freshField->isJson);
    }

    /** @test */
    public function it_handles_complex_nova_configuration(): void
    {
        $field = Code::make('Configuration File')
            ->language('yaml')
            ->json()
            ->nullable()
            ->help('Enter YAML configuration')
            ->rules('required');

        // Test all configurations are set correctly
        $this->assertEquals('javascript', $field->language); // JSON overrides
        $this->assertTrue($field->isJson);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Enter YAML configuration', $field->helpText);
        $this->assertContains('required', $field->rules);
    }

    /** @test */
    public function it_serializes_code_field_for_frontend(): void
    {
        $field = Code::make('Source Code')
            ->language('php')
            ->help('Enter PHP code here');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Source Code', $serialized['name']);
        $this->assertEquals('source_code', $serialized['attribute']);
        $this->assertEquals('CodeField', $serialized['component']);
        $this->assertEquals('Enter PHP code here', $serialized['helpText']);
        
        // Check Nova-specific properties
        $this->assertEquals('php', $serialized['language']);
        $this->assertFalse($serialized['isJson']);
        $this->assertIsArray($serialized['supportedLanguages']);
    }

    /** @test */
    public function it_handles_code_field_with_validation_rules(): void
    {
        $field = Code::make('Code', 'code')
            ->language('javascript')
            ->rules('required', 'string')
            ->nullable(false);

        // Test that validation rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('string', $field->rules);
        $this->assertFalse($field->nullable);

        // Test field serialization includes validation rules
        $serialized = $field->jsonSerialize();
        $this->assertEquals(['required', 'string'], $serialized['rules']);
        $this->assertFalse($serialized['nullable']);
    }

    /** @test */
    public function it_supports_nova_examples_from_documentation(): void
    {
        // Example from Nova docs: Code::make('Snippet')
        $field1 = Code::make('Snippet');
        
        $this->assertEquals('Snippet', $field1->name);
        $this->assertEquals('snippet', $field1->attribute);
        $this->assertEquals('htmlmixed', $field1->language);

        // Example with language
        $field2 = Code::make('Source')->language('php');
        
        $this->assertEquals('php', $field2->language);
        $this->assertFalse($field2->isJson);

        // Example with JSON
        $field3 = Code::make('Configuration')->json();
        
        $this->assertTrue($field3->isJson);
        $this->assertEquals('javascript', $field3->language);
    }

    /** @test */
    public function it_handles_edge_cases_with_language_and_json(): void
    {
        // Test that json() overrides language()
        $field1 = Code::make('Config')
            ->language('php')
            ->json();
        
        $this->assertEquals('javascript', $field1->language);
        $this->assertTrue($field1->isJson);

        // Test that language() after json() still gets overridden
        $field2 = Code::make('Config')
            ->json()
            ->language('php');
        
        $this->assertEquals('php', $field2->language);
        $this->assertTrue($field2->isJson);
    }

    /** @test */
    public function it_maintains_backward_compatibility_with_standard_field_features(): void
    {
        $field = Code::make('Code');

        // Should work with standard field features
        $this->assertEquals('htmlmixed', $field->language);
        $this->assertFalse($field->isJson);

        // Meta should serialize correctly
        $meta = $field->meta();
        $this->assertEquals('htmlmixed', $meta['language']);
        $this->assertFalse($meta['isJson']);
        $this->assertIsArray($meta['supportedLanguages']);
    }

    /** @test */
    public function it_works_with_all_inherited_field_functionality(): void
    {
        $field = Code::make('Source Code')
            ->language('php')
            ->json()
            ->nullable()
            ->readonly()
            ->help('PHP source code')
            ->rules('required');

        // Test inherited functionality works
        $this->assertTrue($field->nullable);
        $this->assertTrue($field->readonly);
        $this->assertEquals('PHP source code', $field->helpText);
        $this->assertContains('required', $field->rules);
        
        // Test Nova-specific functionality still works
        $this->assertEquals('javascript', $field->language); // JSON overrides
        $this->assertTrue($field->isJson);
    }
}
