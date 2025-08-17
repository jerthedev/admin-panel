<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use JTD\AdminPanel\Fields\Password;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;

/**
 * Password Field E2E Test
 *
 * Tests the complete end-to-end functionality of Password fields
 * including database operations, password hashing, and field behavior.
 *
 * Focuses on field integration and data flow rather than
 * web interface testing (which is handled by Playwright tests).
 */
class PasswordFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different password scenarios
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('currentpassword123')
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('janepassword456')
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'password' => Hash::make('bobsecret789')
        ]);
    }

    /** @test */
    public function it_handles_complete_password_creation_workflow(): void
    {
        $field = Password::make('Password');
        $newUser = new User([
            'name' => 'New User',
            'email' => 'newuser@example.com'
        ]);

        // Simulate form submission with password
        $request = new Request(['password' => 'newsecretpassword']);
        
        // Fill the field
        $field->fill($request, $newUser);
        
        // Save the user
        $newUser->save();
        
        // Verify password was hashed and stored correctly
        $this->assertTrue(Hash::check('newsecretpassword', $newUser->password));
        
        // Verify user can be retrieved and password is still valid
        $retrievedUser = User::find($newUser->id);
        $this->assertTrue(Hash::check('newsecretpassword', $retrievedUser->password));
    }

    /** @test */
    public function it_handles_complete_password_update_workflow(): void
    {
        $user = User::find(1);
        $field = Password::make('Password');
        
        // Verify current password
        $this->assertTrue(Hash::check('currentpassword123', $user->password));
        
        // Simulate password update
        $request = new Request(['password' => 'updatedpassword456']);
        $field->fill($request, $user);
        $user->save();
        
        // Verify new password works
        $updatedUser = User::find(1);
        $this->assertTrue(Hash::check('updatedpassword456', $updatedUser->password));
        
        // Verify old password no longer works
        $this->assertFalse(Hash::check('currentpassword123', $updatedUser->password));
    }

    /** @test */
    public function it_handles_empty_password_updates_correctly(): void
    {
        $user = User::find(1);
        $originalPassword = $user->password;
        $field = Password::make('Password');
        
        // Simulate empty password update (should not change password)
        $request = new Request(['password' => '']);
        $field->fill($request, $user);
        $user->save();
        
        // Verify password unchanged
        $updatedUser = User::find(1);
        $this->assertEquals($originalPassword, $updatedUser->password);
        $this->assertTrue(Hash::check('currentpassword123', $updatedUser->password));
    }

    /** @test */
    public function it_handles_null_password_updates_correctly(): void
    {
        $user = User::find(2);
        $originalPassword = $user->password;
        $field = Password::make('Password');
        
        // Simulate null password update (should not change password)
        $request = new Request(['password' => null]);
        $field->fill($request, $user);
        $user->save();
        
        // Verify password unchanged
        $updatedUser = User::find(2);
        $this->assertEquals($originalPassword, $updatedUser->password);
        $this->assertTrue(Hash::check('janepassword456', $updatedUser->password));
    }

    /** @test */
    public function it_never_exposes_password_values_in_resolution(): void
    {
        $user = User::find(1);
        $field = Password::make('Password');
        
        // Resolve field value
        $field->resolve($user);
        
        // Password should never be exposed
        $this->assertNull($field->value);
        
        // Test with different users
        $user2 = User::find(2);
        $field2 = Password::make('User Password', 'password');
        $field2->resolve($user2);
        
        $this->assertNull($field2->value);
    }

    /** @test */
    public function it_works_with_custom_fill_callbacks_end_to_end(): void
    {
        $field = Password::make('Password')->fillUsing(function ($request, $model, $attribute) {
            // Custom password processing (e.g., additional validation, logging, etc.)
            $password = $request->input($attribute);
            if (!empty($password)) {
                $model->{$attribute} = Hash::make($password . '_custom_suffix');
            }
        });
        
        $user = User::find(3);
        $request = new Request(['password' => 'custompassword']);
        
        $field->fill($request, $user);
        $user->save();
        
        // Verify custom processing was applied
        $updatedUser = User::find(3);
        $this->assertTrue(Hash::check('custompassword_custom_suffix', $updatedUser->password));
        $this->assertFalse(Hash::check('custompassword', $updatedUser->password));
    }

    /** @test */
    public function it_integrates_with_validation_rules_end_to_end(): void
    {
        $field = Password::make('Password')->rules('required', 'min:8');
        
        // Test field configuration
        $this->assertEquals(['required', 'min:8'], $field->rules);
        
        // Test with valid password
        $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
        $validRequest = new Request(['password' => 'validpassword123']);
        
        $field->fill($validRequest, $user);
        $user->save();
        
        $this->assertTrue(Hash::check('validpassword123', $user->password));
    }

    /** @test */
    public function it_handles_multiple_password_fields_correctly(): void
    {
        // Simulate a form with multiple password fields (but only save the main password)
        $passwordField = Password::make('Password', 'password');
        $confirmField = Password::make('Password Confirmation', 'password_confirmation');

        $user = new User(['name' => 'Multi User', 'email' => 'multi@example.com']);
        $request = new Request([
            'password' => 'multipassword123',
            'password_confirmation' => 'multipassword123'
        ]);

        // Fill main password field
        $passwordField->fill($request, $user);
        $user->save();

        // Verify main password was set
        $this->assertTrue(Hash::check('multipassword123', $user->password));

        // Test that confirmation field can be processed
        $tempUser = new User();
        $confirmField->fill($request, $tempUser);

        // Confirmation field should process the value (Password field behavior)
        // Since the request has 'password_confirmation' value, it should be hashed and set
        $this->assertTrue(Hash::check('multipassword123', $tempUser->password_confirmation));
    }

    /** @test */
    public function it_maintains_security_throughout_complete_workflow(): void
    {
        $field = Password::make('Password');
        $user = User::find(1);
        
        // 1. Resolution should never expose password
        $field->resolve($user);
        $this->assertNull($field->value);
        
        // 2. JSON serialization should not expose password value (should be null)
        $json = $field->jsonSerialize();
        if (array_key_exists('value', $json)) {
            $this->assertNull($json['value']);
        }
        
        // 3. Password update should hash the value
        $request = new Request(['password' => 'securitytest123']);
        $field->fill($request, $user);
        $user->save();
        
        // 4. Verify password is hashed in database
        $updatedUser = User::find(1);
        $this->assertNotEquals('securitytest123', $updatedUser->password);
        $this->assertTrue(Hash::check('securitytest123', $updatedUser->password));
        
        // 5. Re-resolution should still not expose password
        $field->resolve($updatedUser);
        $this->assertNull($field->value);
    }

    /** @test */
    public function it_handles_edge_cases_and_error_scenarios(): void
    {
        $field = Password::make('Password');
        
        // Test with missing password field in request
        $user = User::find(1);
        $originalPassword = $user->password;
        $requestWithoutPassword = new Request([]);
        
        $field->fill($requestWithoutPassword, $user);
        $user->save();
        
        // Password should remain unchanged
        $this->assertEquals($originalPassword, User::find(1)->password);
        
        // Test with very long password
        $longPassword = str_repeat('a', 1000);
        $request = new Request(['password' => $longPassword]);
        
        $field->fill($request, $user);
        $user->save();
        
        // Should handle long passwords correctly
        $this->assertTrue(Hash::check($longPassword, User::find(1)->password));
    }

    /** @test */
    public function it_works_correctly_in_resource_context(): void
    {
        $field = Password::make('Password');
        $user = User::find(1);
        
        // Test field in resource context (like Nova resource)
        $field->resolve($user);
        
        // Should maintain Nova-compatible behavior
        $this->assertNull($field->value);
        $this->assertEquals('Password', $field->name);
        $this->assertEquals('password', $field->attribute);
        $this->assertEquals('PasswordField', $field->component);
        
        // Should be hidden from index/detail by default
        $this->assertFalse($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }
}
