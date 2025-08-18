<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Textarea;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;

/**
 * Textarea Field E2E Test
 *
 * Tests the complete end-to-end functionality of Textarea fields
 * including database operations, validation, and field behavior.
 *
 * Focuses on field integration and data flow rather than
 * web interface testing (which is handled by Playwright tests).
 */
class TextareaFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users (without bio column since it doesn't exist in test schema)
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com'
        ]);
    }

    /** @test */
    public function it_creates_textarea_field_with_nova_compatible_configuration(): void
    {
        $field = Textarea::make('Biography')
            ->rows(6)
            ->maxlength(500)
            ->enforceMaxlength()
            ->alwaysShow();

        $this->assertEquals('Biography', $field->name);
        $this->assertEquals('biography', $field->attribute);
        $this->assertEquals('TextareaField', $field->component);
        $this->assertEquals(6, $field->rows);
        $this->assertEquals(500, $field->maxlength);
        $this->assertTrue($field->enforceMaxlength);
        $this->assertTrue($field->alwaysShow);
    }

    /** @test */
    public function it_resolves_textarea_values_from_model(): void
    {
        $field = Textarea::make('Name'); // Use existing 'name' field

        // Test resolving from existing model data
        $user1 = User::find(1);
        $field->resolve($user1);
        $this->assertEquals('John Doe', $field->value);

        $user2 = User::find(2);
        $field->resolve($user2);
        $this->assertEquals('Jane Smith', $field->value);

        // Test with different field
        $emailField = Textarea::make('Email');
        $emailField->resolve($user1);
        $this->assertEquals('john@example.com', $emailField->value);
    }

    /** @test */
    public function it_fills_model_from_request_data(): void
    {
        $field = Textarea::make('Name');
        $user = new User();

        $request = Request::create('/test', 'POST', [
            'name' => 'Updated name content from form submission'
        ]);

        $field->fill($request, $user);

        $this->assertEquals('Updated name content from form submission', $user->name);
    }

    /** @test */
    public function it_handles_maxlength_validation_scenarios(): void
    {
        $field = Textarea::make('Name')->maxlength(50);

        // Test content within limit
        $shortContent = 'This is within the 50 character limit.';
        $this->assertLessThanOrEqual(50, strlen($shortContent));

        $user = new User();
        $request = Request::create('/test', 'POST', ['name' => $shortContent]);
        $field->fill($request, $user);
        $this->assertEquals($shortContent, $user->name);

        // Test content exceeding limit (field doesn't enforce, validation would)
        $longContent = 'This content is definitely longer than fifty characters and should be handled appropriately by validation rules.';
        $this->assertGreaterThan(50, strlen($longContent));

        $user2 = new User();
        $request2 = Request::create('/test', 'POST', ['name' => $longContent]);
        $field->fill($request2, $user2);
        $this->assertEquals($longContent, $user2->name); // Field fills, validation would catch this
    }

    /** @test */
    public function it_serializes_correctly_for_frontend(): void
    {
        $field = Textarea::make('Description')
            ->rows(8)
            ->maxlength(1000)
            ->enforceMaxlength()
            ->alwaysShow()
            ->withMeta([
                'extraAttributes' => [
                    'placeholder' => 'Enter your description...',
                    'spellcheck' => 'true'
                ]
            ]);

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Description', $serialized['name']);
        $this->assertEquals('description', $serialized['attribute']);
        $this->assertEquals('TextareaField', $serialized['component']);
        $this->assertEquals(8, $serialized['rows']);
        $this->assertEquals(1000, $serialized['maxlength']);
        $this->assertTrue($serialized['enforceMaxlength']);
        $this->assertTrue($serialized['alwaysShow']);
        $this->assertArrayHasKey('extraAttributes', $serialized);
        $this->assertEquals('Enter your description...', $serialized['extraAttributes']['placeholder']);
        $this->assertEquals('true', $serialized['extraAttributes']['spellcheck']);
    }

    /** @test */
    public function it_handles_complex_content_scenarios(): void
    {
        $field = Textarea::make('Content');

        // Test with multiline content
        $multilineContent = "Line 1\nLine 2\nLine 3\n\nParagraph after empty line.";
        $user = new User();
        $request = Request::create('/test', 'POST', ['content' => $multilineContent]);
        $field->fill($request, $user);
        $this->assertEquals($multilineContent, $user->content);

        // Test with special characters
        $specialContent = "Content with special chars: àáâãäå æç èéêë ìíîï ñ òóôõö ùúûü ý";
        $user2 = new User();
        $request2 = Request::create('/test', 'POST', ['content' => $specialContent]);
        $field->fill($request2, $user2);
        $this->assertEquals($specialContent, $user2->content);

        // Test with HTML-like content (should be preserved as-is)
        $htmlContent = "<p>This looks like HTML but should be treated as plain text</p>";
        $user3 = new User();
        $request3 = Request::create('/test', 'POST', ['content' => $htmlContent]);
        $field->fill($request3, $user3);
        $this->assertEquals($htmlContent, $user3->content);
    }

    /** @test */
    public function it_works_with_validation_rules(): void
    {
        $field = Textarea::make('Name')
            ->required()
            ->maxlength(100);

        $rules = $field->rules;
        $this->assertContains('required', $rules);

        // Test that maxlength is available for validation
        $this->assertEquals(100, $field->maxlength);
    }

    /** @test */
    public function it_handles_different_row_configurations(): void
    {
        $smallField = Textarea::make('Small')->rows(2);
        $mediumField = Textarea::make('Medium')->rows(6);
        $largeField = Textarea::make('Large')->rows(12);

        $this->assertEquals(2, $smallField->rows);
        $this->assertEquals(6, $mediumField->rows);
        $this->assertEquals(12, $largeField->rows);

        // Verify serialization includes rows
        $smallSerialized = $smallField->jsonSerialize();
        $mediumSerialized = $mediumField->jsonSerialize();
        $largeSerialized = $largeField->jsonSerialize();

        $this->assertEquals(2, $smallSerialized['rows']);
        $this->assertEquals(6, $mediumSerialized['rows']);
        $this->assertEquals(12, $largeSerialized['rows']);
    }

    /** @test */
    public function it_supports_accessibility_attributes(): void
    {
        $field = Textarea::make('Accessible Bio')->withMeta([
            'extraAttributes' => [
                'aria-label' => 'User biography',
                'aria-describedby' => 'bio-help-text',
                'aria-required' => 'true'
            ]
        ]);

        $serialized = $field->jsonSerialize();
        $attrs = $serialized['extraAttributes'];

        $this->assertEquals('User biography', $attrs['aria-label']);
        $this->assertEquals('bio-help-text', $attrs['aria-describedby']);
        $this->assertEquals('true', $attrs['aria-required']);
    }

    /** @test */
    public function it_handles_edge_cases_gracefully(): void
    {
        $field = Textarea::make('Name');

        // Test with empty string (using name field)
        $user1 = new User();
        $request1 = Request::create('/test', 'POST', ['name' => '']);
        $field->fill($request1, $user1);
        $this->assertEquals('', $user1->name);

        // Test with content that has internal whitespace (Laravel may trim leading/trailing)
        $user2 = new User();
        $request2 = Request::create('/test', 'POST', ['name' => 'John   Doe']);
        $field->fill($request2, $user2);
        $this->assertEquals('John   Doe', $user2->name);

        // Test with zero maxlength
        $zeroField = Textarea::make('Zero')->maxlength(0);
        $this->assertEquals(0, $zeroField->maxlength);
        $serialized = $zeroField->jsonSerialize();
        $this->assertEquals(0, $serialized['maxlength']);
    }

    /** @test */
    public function it_maintains_field_state_across_operations(): void
    {
        $field = Textarea::make('Stateful Name')
            ->rows(5)
            ->maxlength(200)
            ->enforceMaxlength()
            ->alwaysShow()
            ->required();

        // Verify initial state
        $this->assertEquals(5, $field->rows);
        $this->assertEquals(200, $field->maxlength);
        $this->assertTrue($field->enforceMaxlength);
        $this->assertTrue($field->alwaysShow);
        $this->assertContains('required', $field->rules);

        // Perform operations
        $user = User::find(1);
        $field->resolve($user);

        // Verify state is maintained after resolve
        $this->assertEquals(5, $field->rows);
        $this->assertEquals(200, $field->maxlength);
        $this->assertTrue($field->enforceMaxlength);
        $this->assertTrue($field->alwaysShow);

        // Test serialization maintains state
        $serialized = $field->jsonSerialize();
        $this->assertEquals(5, $serialized['rows']);
        $this->assertEquals(200, $serialized['maxlength']);
        $this->assertTrue($serialized['enforceMaxlength']);
        $this->assertTrue($serialized['alwaysShow']);
        // Note: value may be null if field attribute doesn't exist on model
    }

    /** @test */
    public function it_integrates_with_form_requests_and_validation(): void
    {
        $field = Textarea::make('Validated Content')
            ->maxlength(50)
            ->required();

        // Simulate a form request with valid data
        $validRequest = Request::create('/test', 'POST', [
            'validated_content' => 'This is valid content within the limit.'
        ]);

        $user = new User();
        $field->fill($validRequest, $user);

        $this->assertEquals('This is valid content within the limit.', $user->validated_content);
        $this->assertLessThanOrEqual(50, strlen($user->validated_content));

        // Verify field provides correct validation context
        $this->assertEquals(50, $field->maxlength);
        $this->assertContains('required', $field->rules);
    }
}
