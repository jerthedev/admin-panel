<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JTD\AdminPanel\Fields\Password;
use JTD\AdminPanel\Fields\PasswordConfirmation;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;

/**
 * PasswordConfirmation Field E2E Test
 *
 * Tests the complete end-to-end functionality of PasswordConfirmation fields
 * including validation, security, and integration with Password fields.
 *
 * Focuses on real-world password change scenarios and validation workflows.
 */
class PasswordConfirmationFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users for password change scenarios
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('oldpassword123')
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('currentpass456')
        ]);
    }

    /** @test */
    public function it_validates_password_confirmation_in_complete_workflow(): void
    {
        $user = User::find(1);
        
        // Create password and confirmation fields as they would appear in a form
        $passwordField = Password::make('Password')
            ->rules('required', 'min:8', 'confirmed');
            
        $confirmationField = PasswordConfirmation::make('Password Confirmation')
            ->rules('required');

        // Test successful password change
        $validRequest = new Request([
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        // Validate the request
        $validator = Validator::make($validRequest->all(), [
            'password' => ['required', 'min:8', 'confirmed'],
            'password_confirmation' => ['required']
        ]);

        $this->assertFalse($validator->fails());

        // Fill the password field (confirmation field should not modify model)
        $passwordField->fill($validRequest, $user);
        $confirmationField->fill($validRequest, $user);

        // Only password should be set, not password_confirmation
        $this->assertTrue(isset($user->password));
        $this->assertFalse(isset($user->password_confirmation));
    }

    /** @test */
    public function it_fails_validation_when_passwords_dont_match(): void
    {
        // Test mismatched passwords
        $invalidRequest = new Request([
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword'
        ]);

        $validator = Validator::make($invalidRequest->all(), [
            'password' => ['required', 'min:8', 'confirmed'],
            'password_confirmation' => ['required']
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /** @test */
    public function it_handles_user_creation_with_password_confirmation(): void
    {
        // Simulate user creation form with password confirmation
        $passwordField = Password::make('Password')
            ->rules('required', 'min:8', 'confirmed');
            
        $confirmationField = PasswordConfirmation::make('Password Confirmation')
            ->rules('required');

        $createRequest = new Request([
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123'
        ]);

        // Validate
        $validator = Validator::make($createRequest->all(), [
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8', 'confirmed'],
            'password_confirmation' => ['required']
        ]);

        $this->assertFalse($validator->fails());

        // Create new user
        $newUser = new User();
        $newUser->name = $createRequest->input('name');
        $newUser->email = $createRequest->input('email');
        
        // Fill password field
        $passwordField->fill($createRequest, $newUser);
        $confirmationField->fill($createRequest, $newUser);

        // Verify password is set but confirmation is not
        $this->assertTrue(isset($newUser->password));
        $this->assertFalse(isset($newUser->password_confirmation));
    }

    /** @test */
    public function it_handles_password_update_scenarios(): void
    {
        $user = User::find(1);
        $originalPassword = $user->password;

        // Test password update with confirmation
        $updateRequest = new Request([
            'password' => 'updatedpass789',
            'password_confirmation' => 'updatedpass789'
        ]);

        $passwordField = Password::make('Password')
            ->rules('required', 'min:8', 'confirmed')
            ->fillUsing(function ($request, $model, $attribute) {
                if ($request->filled($attribute)) {
                    $model->{$attribute} = Hash::make($request->input($attribute));
                }
            });
            
        $confirmationField = PasswordConfirmation::make('Password Confirmation')
            ->rules('required');

        // Validate
        $validator = Validator::make($updateRequest->all(), [
            'password' => ['required', 'min:8', 'confirmed'],
            'password_confirmation' => ['required']
        ]);

        $this->assertFalse($validator->fails());

        // Update password
        $passwordField->fill($updateRequest, $user);
        $confirmationField->fill($updateRequest, $user);

        // Verify password was updated
        $this->assertNotEquals($originalPassword, $user->password);
        $this->assertFalse(isset($user->password_confirmation));
    }

    /** @test */
    public function it_maintains_security_in_field_resolution(): void
    {
        $user = User::find(1);
        
        // Even if user model somehow has password_confirmation, field should not resolve it
        $user->password_confirmation = 'should-never-be-exposed';
        
        $confirmationField = PasswordConfirmation::make('Password Confirmation');
        $confirmationField->resolve($user);

        $this->assertNull($confirmationField->value);
    }

    /** @test */
    public function it_works_with_different_validation_rules(): void
    {
        // Test with additional validation rules
        $request = new Request([
            'password' => 'ComplexPass123!',
            'password_confirmation' => 'ComplexPass123!'
        ]);

        $validator = Validator::make($request->all(), [
            'password' => [
                'required',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/'
            ],
            'password_confirmation' => ['required']
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_handles_empty_password_confirmation(): void
    {
        $request = new Request([
            'password' => 'newpassword123',
            'password_confirmation' => ''
        ]);

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'min:8', 'confirmed'],
            'password_confirmation' => ['required']
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password_confirmation', $validator->errors()->toArray());
    }

    /** @test */
    public function it_integrates_with_form_field_serialization(): void
    {
        $passwordField = Password::make('Password')
            ->rules('required', 'min:8', 'confirmed')
            ->help('Choose a strong password');
            
        $confirmationField = PasswordConfirmation::make('Password Confirmation')
            ->rules('required')
            ->help('Re-enter your password')
            ->placeholder('Confirm password');

        // Test field serialization for frontend
        $passwordJson = $passwordField->jsonSerialize();
        $confirmationJson = $confirmationField->jsonSerialize();

        // Verify field structure
        $this->assertEquals('PasswordField', $passwordJson['component']);
        $this->assertEquals('PasswordConfirmationField', $confirmationJson['component']);
        
        // Verify visibility settings
        $this->assertFalse($confirmationJson['showOnIndex']);
        $this->assertFalse($confirmationJson['showOnDetail']);
        $this->assertTrue($confirmationJson['showOnCreation']);
        $this->assertTrue($confirmationJson['showOnUpdate']);

        // Verify help text and placeholder
        $this->assertEquals('Re-enter your password', $confirmationJson['helpText']);
        $this->assertEquals('Confirm password', $confirmationJson['placeholder']);
    }

    /** @test */
    public function it_handles_nullable_password_confirmation(): void
    {
        $confirmationField = PasswordConfirmation::make('Password Confirmation')
            ->nullable();

        $request = new Request([
            'password' => 'newpassword123',
            'password_confirmation' => null
        ]);

        // With nullable, empty confirmation should still fail 'confirmed' rule on password
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'min:8', 'confirmed'],
            'password_confirmation' => ['nullable']
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /** @test */
    public function it_supports_custom_attribute_names(): void
    {
        $confirmationField = PasswordConfirmation::make('Confirm New Password', 'new_password_confirmation');

        $request = new Request([
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123'
        ]);

        $validator = Validator::make($request->all(), [
            'new_password' => ['required', 'min:8', 'confirmed:new_password_confirmation'],
            'new_password_confirmation' => ['required']
        ]);

        $this->assertFalse($validator->fails());

        // Test field properties
        $this->assertEquals('Confirm New Password', $confirmationField->name);
        $this->assertEquals('new_password_confirmation', $confirmationField->attribute);
    }
}
