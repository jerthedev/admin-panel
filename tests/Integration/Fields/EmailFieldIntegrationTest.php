<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Email;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

class EmailFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_email_field_with_nova_syntax(): void
    {
        $field = Email::make('Email');

        $this->assertEquals('Email', $field->name);
        $this->assertEquals('email', $field->attribute);
        $this->assertEquals('EmailField', $field->component);
    }

    /** @test */
    public function it_creates_email_field_with_custom_attribute(): void
    {
        $field = Email::make('Customer Email', 'customer_email');

        $this->assertEquals('Customer Email', $field->name);
        $this->assertEquals('customer_email', $field->attribute);
        $this->assertEquals('EmailField', $field->component);
    }

    /** @test */
    public function it_resolves_and_fills_values(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $field = Email::make('Email', 'email');
        $field->resolve($user);
        $this->assertEquals('test@example.com', $field->value);

        $request = new Request(['email' => 'new@example.com']);
        $field->fill($request, $user);
        $this->assertEquals('new@example.com', $user->email);
    }

    /** @test */
    public function it_normalizes_email_on_fill(): void
    {
        $user = User::factory()->create();

        $field = Email::make('Email', 'email');
        $request = new Request(['email' => '  JOHN@EXAMPLE.COM  ']);
        $field->fill($request, $user);

        $this->assertEquals('john@example.com', $user->email);
    }

    /** @test */
    public function it_handles_null_values(): void
    {
        $user = User::factory()->create();

        $field = Email::make('Email', 'email');
        $request = new Request(['email' => null]);
        $field->fill($request, $user);

        $this->assertNull($user->email);
    }

    /** @test */
    public function it_serializes_for_frontend_with_clickable_meta(): void
    {
        $field = Email::make('Email Address')
            ->clickable(false)
            ->help('Enter your email')
            ->rules('required', 'email');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Email Address', $serialized['name']);
        $this->assertEquals('email_address', $serialized['attribute']);
        $this->assertEquals('EmailField', $serialized['component']);
        $this->assertEquals('Enter your email', $serialized['helpText']);
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('email', $serialized['rules']);
        $this->assertFalse($serialized['clickable']);
    }

    /** @test */
    public function it_has_default_clickable_true(): void
    {
        $field = Email::make('Email');

        $serialized = $field->jsonSerialize();

        $this->assertTrue($serialized['clickable']);
    }

    /** @test */
    public function it_automatically_adds_email_validation(): void
    {
        $field = Email::make('Email');

        $this->assertContains('email', $field->rules);
    }

    /** @test */
    public function it_supports_method_chaining(): void
    {
        $field = Email::make('Email')
            ->clickable(false)
            ->required()
            ->sortable()
            ->searchable();

        $this->assertInstanceOf(Email::class, $field);
        $this->assertFalse($field->clickable);
        $this->assertContains('required', $field->rules);
        $this->assertTrue($field->sortable);
        $this->assertTrue($field->searchable);
    }

    /** @test */
    public function it_handles_complex_email_formats(): void
    {
        $user = User::factory()->create();
        $field = Email::make('Email', 'email');

        $complexEmails = [
            'user+tag@example.com',
            'user.name@sub.domain.com',
            'user123@test-domain.co.uk',
        ];

        foreach ($complexEmails as $email) {
            $request = new Request(['email' => $email]);
            $field->fill($request, $user);
            $this->assertEquals(strtolower($email), $user->email);
        }
    }

    /** @test */
    public function it_integrates_with_laravel_validation(): void
    {
        $field = Email::make('Email')
            ->rules('required', 'email', 'unique:users,email');

        $rules = $field->rules;

        $this->assertContains('required', $rules);
        $this->assertContains('email', $rules);
        $this->assertContains('unique:users,email', $rules);
    }

    /** @test */
    public function it_supports_nullable_emails(): void
    {
        $field = Email::make('Email')->nullable();

        $this->assertTrue($field->nullable);

        $user = User::factory()->create();
        $request = new Request(['email' => '']);
        $field->fill($request, $user);

        // Empty string should be handled appropriately
        $this->assertTrue($user->email === '' || $user->email === null);
    }

    /** @test */
    public function it_preserves_email_case_in_display_but_normalizes_storage(): void
    {
        $user = User::factory()->create(['email' => 'Test@Example.Com']);

        $field = Email::make('Email', 'email');
        $field->resolve($user);

        // Display should show the stored value
        $this->assertEquals('Test@Example.Com', $field->value);

        // But filling should normalize
        $request = new Request(['email' => 'NEW@EXAMPLE.COM']);
        $field->fill($request, $user);
        $this->assertEquals('new@example.com', $user->email);
    }
}
