<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Textarea;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Textarea Field Integration Test
 *
 * Tests the complete integration between PHP Textarea field class,
 * API endpoints, and frontend functionality with 100% Nova API compatibility.
 * 
 * Validates all Nova API methods and their behavior in real-world scenarios.
 */
class TextareaFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users (using existing User model structure)
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);
    }

    /** @test */
    public function it_creates_textarea_field_with_nova_syntax(): void
    {
        $field = Textarea::make('Biography');

        $this->assertEquals('Biography', $field->name);
        $this->assertEquals('biography', $field->attribute);
        $this->assertEquals('TextareaField', $field->component);
        $this->assertEquals(4, $field->rows);
        $this->assertNull($field->maxlength);
        $this->assertFalse($field->enforceMaxlength);
        $this->assertFalse($field->alwaysShow);
    }

    /** @test */
    public function it_configures_textarea_with_all_nova_methods(): void
    {
        $field = Textarea::make('Description')
            ->rows(6)
            ->maxlength(500)
            ->enforceMaxlength()
            ->alwaysShow();

        $this->assertEquals(6, $field->rows);
        $this->assertEquals(500, $field->maxlength);
        $this->assertTrue($field->enforceMaxlength);
        $this->assertTrue($field->alwaysShow);
    }

    /** @test */
    public function it_supports_extra_attributes_via_with_meta(): void
    {
        $field = Textarea::make('Notes')->withMeta([
            'extraAttributes' => [
                'aria-label' => 'Notes field',
                'data-test' => 'notes-textarea',
                'spellcheck' => 'false'
            ]
        ]);

        $meta = $field->meta();
        $this->assertArrayHasKey('extraAttributes', $meta);
        $this->assertEquals('Notes field', $meta['extraAttributes']['aria-label']);
        $this->assertEquals('notes-textarea', $meta['extraAttributes']['data-test']);
        $this->assertEquals('false', $meta['extraAttributes']['spellcheck']);
    }

    /** @test */
    public function it_serializes_field_data_correctly_for_frontend(): void
    {
        $field = Textarea::make('Content')
            ->rows(8)
            ->maxlength(1000)
            ->enforceMaxlength()
            ->alwaysShow()
            ->withMeta([
                'extraAttributes' => [
                    'placeholder' => 'Enter your content here...'
                ]
            ]);

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Content', $serialized['name']);
        $this->assertEquals('content', $serialized['attribute']);
        $this->assertEquals('TextareaField', $serialized['component']);
        $this->assertEquals(8, $serialized['rows']);
        $this->assertEquals(1000, $serialized['maxlength']);
        $this->assertTrue($serialized['enforceMaxlength']);
        $this->assertTrue($serialized['alwaysShow']);
        $this->assertArrayHasKey('extraAttributes', $serialized);
        $this->assertEquals('Enter your content here...', $serialized['extraAttributes']['placeholder']);
    }

    /** @test */
    public function it_handles_form_requests_with_textarea_data(): void
    {
        $field = Textarea::make('Biography')->maxlength(500);

        // Simulate form request with textarea data
        $request = Request::create('/test', 'POST', [
            'biography' => 'This is a test biography with some content.'
        ]);

        // Test that field can fill model from request
        $model = new \stdClass();
        $field->fill($request, $model);

        $this->assertTrue(property_exists($model, 'biography'));
        $this->assertEquals('This is a test biography with some content.', $model->biography);
    }

    /** @test */
    public function it_validates_maxlength_constraint(): void
    {
        $field = Textarea::make('Description')->maxlength(10);

        // Test that meta includes maxlength for frontend validation
        $meta = $field->meta();
        $this->assertEquals(10, $meta['maxlength']);
        $this->assertFalse($meta['enforceMaxlength']); // Default is false
    }

    /** @test */
    public function it_enforces_maxlength_when_enabled(): void
    {
        $field = Textarea::make('Summary')
            ->maxlength(50)
            ->enforceMaxlength();

        $meta = $field->meta();
        $this->assertEquals(50, $meta['maxlength']);
        $this->assertTrue($meta['enforceMaxlength']);
    }

    /** @test */
    public function it_configures_always_show_behavior(): void
    {
        $defaultField = Textarea::make('Notes');
        $alwaysShowField = Textarea::make('Description')->alwaysShow();

        $this->assertFalse($defaultField->alwaysShow);
        $this->assertTrue($alwaysShowField->alwaysShow);

        $defaultMeta = $defaultField->meta();
        $alwaysShowMeta = $alwaysShowField->meta();

        $this->assertFalse($defaultMeta['alwaysShow']);
        $this->assertTrue($alwaysShowMeta['alwaysShow']);
    }

    /** @test */
    public function it_handles_nullable_and_required_validation(): void
    {
        $requiredField = Textarea::make('Required Bio')->required();
        $nullableField = Textarea::make('Optional Notes')->nullable();

        $requiredSerialized = $requiredField->jsonSerialize();
        $nullableSerialized = $nullableField->jsonSerialize();

        $this->assertContains('required', $requiredSerialized['rules']);
        $this->assertTrue($nullableSerialized['nullable']);
    }

    /** @test */
    public function it_maintains_field_state_across_method_calls(): void
    {
        $field = Textarea::make('Multi Config')
            ->rows(5)
            ->maxlength(200)
            ->enforceMaxlength()
            ->alwaysShow()
            ->required()
            ->nullable();

        // Verify all configurations are maintained
        $this->assertEquals(5, $field->rows);
        $this->assertEquals(200, $field->maxlength);
        $this->assertTrue($field->enforceMaxlength);
        $this->assertTrue($field->alwaysShow);

        $serialized = $field->jsonSerialize();
        $this->assertContains('required', $serialized['rules']);
        $this->assertTrue($serialized['nullable']);
    }

    /** @test */
    public function it_provides_correct_meta_structure_for_frontend(): void
    {
        $field = Textarea::make('Test Field')
            ->rows(6)
            ->maxlength(300)
            ->enforceMaxlength()
            ->alwaysShow();

        $meta = $field->meta();

        // Verify all required meta keys are present
        $this->assertArrayHasKey('rows', $meta);
        $this->assertArrayHasKey('maxlength', $meta);
        $this->assertArrayHasKey('enforceMaxlength', $meta);
        $this->assertArrayHasKey('alwaysShow', $meta);

        // Verify values are correct types
        $this->assertIsInt($meta['rows']);
        $this->assertIsInt($meta['maxlength']);
        $this->assertIsBool($meta['enforceMaxlength']);
        $this->assertIsBool($meta['alwaysShow']);
    }

    /** @test */
    public function it_handles_complex_extra_attributes_configuration(): void
    {
        $field = Textarea::make('Advanced Field')->withMeta([
            'extraAttributes' => [
                'class' => 'custom-textarea',
                'data-validation' => 'strict',
                'aria-describedby' => 'help-text',
                'autocomplete' => 'off',
                'spellcheck' => 'true'
            ]
        ]);

        $meta = $field->meta();
        $extraAttrs = $meta['extraAttributes'];

        $this->assertEquals('custom-textarea', $extraAttrs['class']);
        $this->assertEquals('strict', $extraAttrs['data-validation']);
        $this->assertEquals('help-text', $extraAttrs['aria-describedby']);
        $this->assertEquals('off', $extraAttrs['autocomplete']);
        $this->assertEquals('true', $extraAttrs['spellcheck']);
    }

    /** @test */
    public function it_works_with_different_row_configurations(): void
    {
        $smallField = Textarea::make('Small')->rows(2);
        $mediumField = Textarea::make('Medium')->rows(6);
        $largeField = Textarea::make('Large')->rows(12);

        $this->assertEquals(2, $smallField->rows);
        $this->assertEquals(6, $mediumField->rows);
        $this->assertEquals(12, $largeField->rows);

        $this->assertEquals(2, $smallField->meta()['rows']);
        $this->assertEquals(6, $mediumField->meta()['rows']);
        $this->assertEquals(12, $largeField->meta()['rows']);
    }

    /** @test */
    public function it_handles_edge_cases_for_maxlength(): void
    {
        $noLimitField = Textarea::make('No Limit');
        $zeroLimitField = Textarea::make('Zero Limit')->maxlength(0);
        $largeLimitField = Textarea::make('Large Limit')->maxlength(10000);

        $this->assertNull($noLimitField->maxlength);
        $this->assertEquals(0, $zeroLimitField->maxlength);
        $this->assertEquals(10000, $largeLimitField->maxlength);

        $noLimitMeta = $noLimitField->meta();
        $zeroLimitMeta = $zeroLimitField->meta();
        $largeLimitMeta = $largeLimitField->meta();

        $this->assertNull($noLimitMeta['maxlength']);
        $this->assertEquals(0, $zeroLimitMeta['maxlength']);
        $this->assertEquals(10000, $largeLimitMeta['maxlength']);
    }
}
