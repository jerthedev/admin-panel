<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E\Fields;

use JTD\AdminPanel\Fields\Gravatar;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;

/**
 * Gravatar Field End-to-End Tests
 *
 * Tests that validate the complete Gravatar field functionality
 * in real-world usage scenarios with actual data and Nova compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class GravatarFieldE2ETest extends TestCase
{
    /** @test */
    public function it_displays_gravatar_in_resource_index(): void
    {
        $user = (object) ['email' => 'user@example.com']; // Mock user object
        $field = Gravatar::make('Avatar');

        $field->resolve($user);

        $this->assertNotNull($field->value);
        $this->assertStringStartsWith('https://www.gravatar.com/avatar/', $field->value);

        // Verify it contains the MD5 hash of the email
        $expectedHash = md5(strtolower(trim($user->email)));
        $this->assertStringContains($expectedHash, $field->value);
    }

    /** @test */
    public function it_displays_gravatar_in_resource_detail(): void
    {
        $user = (object) ['email' => 'detail@example.com']; // Mock user object
        $field = Gravatar::make('Profile Picture', 'email');

        $field->resolve($user);

        $this->assertNotNull($field->value);
        $this->assertStringStartsWith('https://www.gravatar.com/avatar/', $field->value);

        // Should be a simple URL without parameters (Nova-compatible)
        $this->assertStringNotContainsString('?', $field->value);
    }

    /** @test */
    public function it_handles_gravatar_field_with_custom_email_column(): void
    {
        // Mock user with custom email column
        $user = (object) [
            'email' => 'primary@example.com',
            'work_email' => 'work@company.com'
        ];

        $field = Gravatar::make('Work Avatar', 'work_email');
        $field->resolve($user);

        $this->assertNotNull($field->value);
        $expectedHash = md5('work@company.com');
        $this->assertStringContains($expectedHash, $field->value);
    }

    /** @test */
    public function it_handles_missing_email_gracefully(): void
    {
        $user = (object) ['name' => 'No Email User']; // No email property

        $field = Gravatar::make('Avatar');
        $field->resolve($user);

        $this->assertNull($field->value);
    }

    /** @test */
    public function it_supports_nova_squared_display_option(): void
    {
        $field = Gravatar::make('Avatar')->squared();

        $serialized = $field->jsonSerialize();

        $this->assertTrue($serialized['squared']);
        $this->assertFalse($serialized['rounded']);
        $this->assertEquals('GravatarField', $serialized['component']);
    }

    /** @test */
    public function it_supports_nova_rounded_display_option(): void
    {
        $field = Gravatar::make('Avatar')->rounded();

        $serialized = $field->jsonSerialize();

        $this->assertTrue($serialized['rounded']);
        $this->assertFalse($serialized['squared']);
        $this->assertEquals('GravatarField', $serialized['component']);
    }

    /** @test */
    public function it_maintains_nova_compatibility_end_to_end(): void
    {
        // Test Nova documentation examples exactly

        // Using the "email" column...
        $field1 = Gravatar::make('Gravatar');
        $user = (object) ['email' => 'test@example.com']; // Mock user object
        $field1->resolve($user);

        $this->assertEquals('Gravatar', $field1->name);
        $this->assertEquals('email', $field1->emailColumn);
        $this->assertNotNull($field1->value);

        // Using the "email_address" column...
        $userWithCustomEmail = (object) [
            'email' => 'primary@example.com',
            'email_address' => 'custom@example.com'
        ];

        $field2 = Gravatar::make('Avatar', 'email_address');
        $field2->resolve($userWithCustomEmail);

        $this->assertEquals('Avatar', $field2->name);
        $this->assertEquals('email_address', $field2->emailColumn);
        $this->assertNotNull($field2->value);
        $this->assertStringContains(md5('custom@example.com'), $field2->value);

        // Test squared method
        $field3 = Gravatar::make('Avatar', 'email_address')->squared();
        $this->assertTrue($field3->squared);
        $this->assertFalse($field3->rounded);
    }

    /** @test */
    public function it_works_in_resource_crud_operations(): void
    {
        // Create operation - Gravatar should resolve from new user data
        $newUser = User::create([
            'name' => 'CRUD Test User',
            'email' => 'crud@example.com',
            'password' => bcrypt('password'),
        ]);

        $field = Gravatar::make('Avatar');
        $field->resolve($newUser);

        $this->assertNotNull($field->value);
        $this->assertStringContains(md5('crud@example.com'), $field->value);

        // Update operation - Gravatar should reflect updated email
        $newUser->email = 'updated@example.com';
        $newUser->save();

        $field->resolve($newUser);
        $this->assertStringContains(md5('updated@example.com'), $field->value);
        $this->assertStringNotContainsString(md5('crud@example.com'), $field->value);

        // Delete operation - field should still work with soft-deleted models
        $newUser->delete();
        $field->resolve($newUser);
        $this->assertStringContains(md5('updated@example.com'), $field->value);
    }

    /** @test */
    public function it_handles_various_email_formats_consistently(): void
    {
        $testEmails = [
            'test@example.com',
            'TEST@EXAMPLE.COM',
            '  test@example.com  ',
            'Test@Example.Com',
        ];

        $expectedHash = md5('test@example.com');
        $field = Gravatar::make('Avatar');

        foreach ($testEmails as $email) {
            $user = User::create([
                'name' => 'Email Format Test',
                'email' => $email,
                'password' => bcrypt('password'),
            ]);

            $field->resolve($user);
            
            $this->assertStringContains($expectedHash, $field->value);
            $user->delete();
        }
    }

    /** @test */
    public function it_integrates_with_resource_authorization(): void
    {
        $user = (object) ['email' => 'auth@example.com'];
        $field = Gravatar::make('Avatar')
            ->canSee(function () {
                return true; // Always visible
            });

        $field->resolve($user);

        $this->assertNotNull($field->value);
        $this->assertTrue($field->isShownOnIndex());
        $this->assertTrue($field->isShownOnDetail());
    }

    /** @test */
    public function it_works_with_field_visibility_methods(): void
    {
        $field = Gravatar::make('Avatar')
            ->hideFromIndex()
            ->showOnDetail();

        $this->assertFalse($field->isShownOnIndex());
        $this->assertTrue($field->isShownOnDetail());
    }

    /** @test */
    public function it_supports_field_help_text(): void
    {
        $field = Gravatar::make('Avatar')
            ->help('This displays your Gravatar image based on your email address');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('This displays your Gravatar image based on your email address', $serialized['helpText']);
    }

    /** @test */
    public function it_handles_complex_real_world_scenarios(): void
    {
        // Scenario: User management system with multiple email fields
        $user = (object) [
            'email' => 'primary@example.com',
            'work_email' => 'work@company.com',
            'personal_email' => 'personal@gmail.com'
        ];

        // Primary avatar
        $primaryAvatar = Gravatar::make('Primary Avatar')->rounded();
        $primaryAvatar->resolve($user);

        // Work avatar
        $workAvatar = Gravatar::make('Work Avatar', 'work_email')->squared();
        $workAvatar->resolve($user);

        // Personal avatar
        $personalAvatar = Gravatar::make('Personal Avatar', 'personal_email');
        $personalAvatar->resolve($user);

        // All should generate different URLs
        $this->assertNotEquals($primaryAvatar->value, $workAvatar->value);
        $this->assertNotEquals($workAvatar->value, $personalAvatar->value);

        // All should be valid Gravatar URLs
        $this->assertStringStartsWith('https://www.gravatar.com/avatar/', $primaryAvatar->value);
        $this->assertStringStartsWith('https://www.gravatar.com/avatar/', $workAvatar->value);
        $this->assertStringStartsWith('https://www.gravatar.com/avatar/', $personalAvatar->value);

        // Styling should be preserved
        $primarySerialized = $primaryAvatar->jsonSerialize();
        $workSerialized = $workAvatar->jsonSerialize();

        $this->assertTrue($primarySerialized['rounded']);
        $this->assertFalse($primarySerialized['squared']);
        $this->assertTrue($workSerialized['squared']);
        $this->assertFalse($workSerialized['rounded']);
    }
}
