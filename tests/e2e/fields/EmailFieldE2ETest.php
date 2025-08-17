<?php

declare(strict_types=1);

namespace E2E\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Email;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

class EmailFieldE2ETest extends TestCase
{
    /** @test */
    public function it_serializes_and_fills_like_nova_in_end_to_end_flow(): void
    {
        // Simulate backend field creation like Nova
        $field = Email::make('Email Address', 'email')
            ->clickable(true)
            ->help('Enter your email address')
            ->rules('required', 'email');

        $serialized = $field->jsonSerialize();

        // Verify Nova-compatible serialization
        $this->assertEquals('EmailField', $serialized['component']);
        $this->assertEquals('Email Address', $serialized['name']);
        $this->assertEquals('email', $serialized['attribute']);
        $this->assertEquals('Enter your email address', $serialized['helpText']);
        $this->assertTrue($serialized['clickable']);
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('email', $serialized['rules']);

        // Simulate a client update with email normalization
        $request = new Request(['email' => '  TEST@EXAMPLE.COM  ']);
        $user = new User();
        $field->fill($request, $user);

        // Verify email is normalized (trimmed and lowercased)
        $this->assertEquals('test@example.com', $user->email);
    }

    /** @test */
    public function it_handles_complex_email_scenarios_end_to_end(): void
    {
        $field = Email::make('Email')->clickable(false);

        // Test complex email formats
        $complexEmails = [
            'user+tag@example.com',
            'user.name@sub.domain.com',
            'user123@test-domain.co.uk',
            'test_user@example-site.org',
        ];

        foreach ($complexEmails as $email) {
            $request = new Request(['email' => $email]);
            $user = new User();
            $field->fill($request, $user);

            $this->assertEquals(strtolower($email), $user->email);
        }

        // Verify non-clickable serialization
        $serialized = $field->jsonSerialize();
        $this->assertFalse($serialized['clickable']);
    }

    /** @test */
    public function it_integrates_with_laravel_validation_end_to_end(): void
    {
        $field = Email::make('Email')
            ->rules('required', 'email', 'unique:users,email');

        $serialized = $field->jsonSerialize();

        // Verify validation rules are properly serialized
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('email', $serialized['rules']);
        $this->assertContains('unique:users,email', $serialized['rules']);

        // Test filling with valid email
        $request = new Request(['email' => 'valid@example.com']);
        $user = new User();
        $field->fill($request, $user);

        $this->assertEquals('valid@example.com', $user->email);
    }

    /** @test */
    public function it_handles_nullable_emails_end_to_end(): void
    {
        $field = Email::make('Email')->nullable();

        $serialized = $field->jsonSerialize();
        $this->assertTrue($serialized['nullable']);

        // Test with null value
        $request = new Request(['email' => null]);
        $user = new User();
        $field->fill($request, $user);

        $this->assertNull($user->email);

        // Test with empty string
        $request = new Request(['email' => '']);
        $user = new User();
        $field->fill($request, $user);

        $this->assertTrue($user->email === '' || $user->email === null);
    }

    /** @test */
    public function it_resolves_values_for_display_end_to_end(): void
    {
        $user = User::factory()->create(['email' => 'display@example.com']);

        $field = Email::make('Email', 'email');
        $field->resolve($user);

        // Verify value is resolved correctly for display
        $this->assertEquals('display@example.com', $field->value);

        $serialized = $field->jsonSerialize();
        $this->assertEquals('display@example.com', $serialized['value']);
    }

    /** @test */
    public function it_supports_method_chaining_end_to_end(): void
    {
        $field = Email::make('Contact Email')
            ->clickable(false)
            ->nullable()
            ->sortable()
            ->searchable()
            ->help('Optional contact email')
            ->placeholder('user@example.com')
            ->rules('email');

        $serialized = $field->jsonSerialize();

        // Verify all chained methods are applied
        $this->assertEquals('Contact Email', $serialized['name']);
        $this->assertEquals('contact_email', $serialized['attribute']);
        $this->assertFalse($serialized['clickable']);
        $this->assertTrue($serialized['nullable']);
        $this->assertTrue($serialized['sortable']);
        $this->assertTrue($serialized['searchable']);
        $this->assertEquals('Optional contact email', $serialized['helpText']);
        $this->assertEquals('user@example.com', $serialized['placeholder']);
        $this->assertContains('email', $serialized['rules']);
    }

    /** @test */
    public function it_maintains_nova_compatibility_end_to_end(): void
    {
        // Test basic Nova syntax
        $field1 = Email::make('Email');
        $this->assertEquals('Email', $field1->name);
        $this->assertEquals('email', $field1->attribute);

        // Test Nova syntax with custom attribute
        $field2 = Email::make('Customer Email', 'customer_email');
        $this->assertEquals('Customer Email', $field2->name);
        $this->assertEquals('customer_email', $field2->attribute);

        // Verify both have default email validation
        $this->assertContains('email', $field1->rules);
        $this->assertContains('email', $field2->rules);

        // Verify both are clickable by default
        $this->assertTrue($field1->clickable);
        $this->assertTrue($field2->clickable);
    }
}
