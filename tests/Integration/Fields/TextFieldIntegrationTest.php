<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

class TextFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_text_field_with_nova_syntax(): void
    {
        $field = Text::make('Name');

        $this->assertEquals('Name', $field->name);
        $this->assertEquals('name', $field->attribute);
        $this->assertEquals('TextField', $field->component);
    }

    /** @test */
    public function it_creates_text_field_with_custom_attribute(): void
    {
        $field = Text::make('Full Name', 'full_name');

        $this->assertEquals('Full Name', $field->name);
        $this->assertEquals('full_name', $field->attribute);
        $this->assertEquals('TextField', $field->component);
    }

    /** @test */
    public function it_resolves_and_fills_values(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        $field = Text::make('Name', 'name');
        $field->resolve($user);
        $this->assertEquals('John Doe', $field->value);

        $request = new Request(['name' => 'Jane Smith']);
        $field->fill($request, $user);
        $this->assertEquals('Jane Smith', $user->name);
    }

    /** @test */
    public function it_trims_whitespace_on_fill(): void
    {
        $user = User::factory()->create();

        $field = Text::make('Name', 'name');
        $request = new Request(['name' => '  John Doe  ']);
        $field->fill($request, $user);

        $this->assertEquals('John Doe', $user->name);
    }

    /** @test */
    public function it_handles_null_values(): void
    {
        $user = User::factory()->create();

        $field = Text::make('Name', 'name');
        $request = new Request(['name' => null]);
        $field->fill($request, $user);

        $this->assertNull($user->name);
    }

    /** @test */
    public function it_supports_nova_suggestions_api(): void
    {
        $field = Text::make('Title')
            ->suggestions(['Article', 'Tutorial', 'Guide']);

        $this->assertEquals(['Article', 'Tutorial', 'Guide'], $field->suggestions);

        $serialized = $field->jsonSerialize();
        $this->assertEquals(['Article', 'Tutorial', 'Guide'], $serialized['suggestions']);
    }

    /** @test */
    public function it_supports_nova_maxlength_api(): void
    {
        $field = Text::make('Title')
            ->maxlength(255);

        $this->assertEquals(255, $field->maxlength);

        $serialized = $field->jsonSerialize();
        $this->assertEquals(255, $serialized['maxlength']);
    }

    /** @test */
    public function it_supports_nova_enforce_maxlength_api(): void
    {
        $field = Text::make('Title')
            ->maxlength(100)
            ->enforceMaxlength();

        $this->assertTrue($field->enforceMaxlength);

        $serialized = $field->jsonSerialize();
        $this->assertTrue($serialized['enforceMaxlength']);
    }

    /** @test */
    public function it_supports_nova_copyable_api(): void
    {
        $field = Text::make('Name')
            ->copyable();

        $this->assertTrue($field->copyable);

        $serialized = $field->jsonSerialize();
        $this->assertTrue($serialized['copyable']);
    }

    /** @test */
    public function it_supports_nova_as_html_api(): void
    {
        $field = Text::make('Content')
            ->asHtml();

        $this->assertTrue($field->asHtml);

        $serialized = $field->jsonSerialize();
        $this->assertTrue($serialized['asHtml']);
    }

    /** @test */
    public function it_supports_nova_as_encoded_html_api(): void
    {
        $field = Text::make('Content')
            ->asEncodedHtml();

        $this->assertTrue($field->asEncodedHtml);

        $serialized = $field->jsonSerialize();
        $this->assertTrue($serialized['asEncodedHtml']);
    }

    /** @test */
    public function it_supports_nova_with_meta_api(): void
    {
        $field = Text::make('Name')
            ->withMeta(['extraAttributes' => ['data-test' => 'value']]);

        $serialized = $field->jsonSerialize();
        $this->assertArrayHasKey('extraAttributes', $serialized);
        $this->assertEquals(['data-test' => 'value'], $serialized['extraAttributes']);
    }

    /** @test */
    public function it_serializes_for_frontend_with_all_nova_features(): void
    {
        $field = Text::make('Article Title')
            ->suggestions(['Article', 'Tutorial', 'Guide'])
            ->maxlength(255)
            ->enforceMaxlength()
            ->copyable()
            ->asHtml()
            ->asEncodedHtml()
            ->help('Enter the article title')
            ->rules('required', 'min:3')
            ->withMeta(['extraAttributes' => ['data-test' => 'nova-compatible']]);

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Article Title', $serialized['name']);
        $this->assertEquals('article_title', $serialized['attribute']);
        $this->assertEquals('TextField', $serialized['component']);
        $this->assertEquals('Enter the article title', $serialized['helpText']);
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('min:3', $serialized['rules']);
        $this->assertEquals(['Article', 'Tutorial', 'Guide'], $serialized['suggestions']);
        $this->assertEquals(255, $serialized['maxlength']);
        $this->assertTrue($serialized['enforceMaxlength']);
        $this->assertTrue($serialized['copyable']);
        $this->assertTrue($serialized['asHtml']);
        $this->assertTrue($serialized['asEncodedHtml']);
        $this->assertEquals(['data-test' => 'nova-compatible'], $serialized['extraAttributes']);
    }

    /** @test */
    public function it_supports_method_chaining(): void
    {
        $field = Text::make('Title')
            ->suggestions(['Article', 'Tutorial'])
            ->maxlength(255)
            ->enforceMaxlength()
            ->copyable()
            ->asHtml()
            ->asEncodedHtml()
            ->required()
            ->sortable()
            ->searchable()
            ->withMeta(['test' => 'value']);

        $this->assertEquals(['Article', 'Tutorial'], $field->suggestions);
        $this->assertEquals(255, $field->maxlength);
        $this->assertTrue($field->enforceMaxlength);
        $this->assertTrue($field->copyable);
        $this->assertTrue($field->asHtml);
        $this->assertTrue($field->asEncodedHtml);
        $this->assertTrue($field->sortable);
        $this->assertTrue($field->searchable);
        $this->assertContains('required', $field->rules);
    }

    /** @test */
    public function it_handles_fill_callback(): void
    {
        $user = User::factory()->create();

        $field = Text::make('Name')
            ->fillUsing(function ($request, $model, $attribute) {
                $model->{$attribute} = strtoupper($request->input($attribute));
            });

        $request = new Request(['name' => 'john doe']);
        $field->fill($request, $user);

        $this->assertEquals('JOHN DOE', $user->name);
    }

    /** @test */
    public function it_handles_resolve_callback(): void
    {
        $user = User::factory()->create(['name' => 'john doe']);

        $field = Text::make('Name', 'name', function ($resource) {
            return strtoupper($resource->name);
        });

        $field->resolve($user);

        $this->assertEquals('JOHN DOE', $field->value);
    }

    /** @test */
    public function it_validates_nova_api_compatibility(): void
    {
        // Test that all Nova Text Field API methods exist and work
        $field = Text::make('Title');

        // Test method existence
        $this->assertTrue(method_exists($field, 'suggestions'));
        $this->assertTrue(method_exists($field, 'maxlength'));
        $this->assertTrue(method_exists($field, 'enforceMaxlength'));
        $this->assertTrue(method_exists($field, 'copyable'));
        $this->assertTrue(method_exists($field, 'asHtml'));
        $this->assertTrue(method_exists($field, 'asEncodedHtml'));
        $this->assertTrue(method_exists($field, 'withMeta'));

        // Test fluent interface
        $result = $field->suggestions(['test'])
            ->maxlength(100)
            ->enforceMaxlength()
            ->copyable()
            ->asHtml()
            ->asEncodedHtml()
            ->withMeta(['test' => 'value']);

        $this->assertInstanceOf(Text::class, $result);
    }
}
