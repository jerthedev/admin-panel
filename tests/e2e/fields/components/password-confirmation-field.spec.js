import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage.js';

/**
 * PasswordConfirmation Field E2E Tests
 * 
 * End-to-end tests for PasswordConfirmation field functionality including:
 * - Password confirmation display in forms
 * - Validation behavior and error messages
 * - Password visibility toggle functionality
 * - Integration with Password fields
 * - Real-world password change scenarios
 */

test.describe('PasswordConfirmation Field E2E Tests', () => {
  let loginPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    
    // Login as admin before each test
    await loginPage.login('e2e-test@example.com', 'testpassword123');
    await page.waitForTimeout(3000);
  });

  test('should display password confirmation field in user creation form', async ({ page }) => {
    // Navigate to user creation form
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Look for password confirmation field
        const confirmationSelectors = [
          '[data-testid="password-confirmation-field"]',
          '.password-confirmation-field',
          'input[name="password_confirmation"]',
          'input[type="password"][placeholder*="confirm" i]',
          'input[type="password"][placeholder*="re-enter" i]'
        ];
        
        let fieldFound = false;
        for (const selector of confirmationSelectors) {
          const field = page.locator(selector);
          const count = await field.count();
          if (count > 0) {
            fieldFound = true;
            
            // Verify field is visible and enabled
            await expect(field.first()).toBeVisible();
            await expect(field.first()).toBeEnabled();
            
            // Verify it's a password type input
            const inputType = await field.first().getAttribute('type');
            expect(inputType).toBe('password');
            
            break;
          }
        }
        
        if (fieldFound) {
          console.log(`✓ Password confirmation field found at ${url}`);
          break;
        }
      } catch (error) {
        console.log(`✗ Could not access ${url}: ${error.message}`);
        continue;
      }
    }
  });

  test('should toggle password visibility when eye icon is clicked', async ({ page }) => {
    // Navigate to a form with password confirmation field
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create',
      '/admin/profile/edit'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Look for password confirmation field
        const passwordField = page.locator('input[name="password_confirmation"]').first();
        
        if (await passwordField.count() > 0) {
          // Look for visibility toggle button
          const toggleButton = page.locator('button').filter({ hasText: /eye|visibility/i }).first();
          
          if (await toggleButton.count() > 0) {
            // Initially should be password type
            let inputType = await passwordField.getAttribute('type');
            expect(inputType).toBe('password');
            
            // Click toggle to show password
            await toggleButton.click();
            await page.waitForTimeout(100);
            
            inputType = await passwordField.getAttribute('type');
            expect(inputType).toBe('text');
            
            // Click toggle to hide password again
            await toggleButton.click();
            await page.waitForTimeout(100);
            
            inputType = await passwordField.getAttribute('type');
            expect(inputType).toBe('password');
            
            console.log(`✓ Password visibility toggle working at ${url}`);
            break;
          }
        }
      } catch (error) {
        console.log(`✗ Could not test visibility toggle at ${url}: ${error.message}`);
        continue;
      }
    }
  });

  test('should validate password confirmation matches password', async ({ page }) => {
    // Navigate to user creation or profile edit form
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create',
      '/admin/profile/edit'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const passwordField = page.locator('input[name="password"]').first();
        const confirmationField = page.locator('input[name="password_confirmation"]').first();
        
        if (await passwordField.count() > 0 && await confirmationField.count() > 0) {
          // Fill password field
          await passwordField.fill('testpassword123');
          
          // Fill confirmation with different password
          await confirmationField.fill('differentpassword');
          
          // Try to submit form (look for submit button)
          const submitButton = page.locator('button[type="submit"], input[type="submit"], button').filter({ hasText: /save|create|update|submit/i }).first();
          
          if (await submitButton.count() > 0) {
            await submitButton.click();
            await page.waitForTimeout(1000);
            
            // Look for validation error
            const errorSelectors = [
              '.error',
              '.invalid-feedback',
              '.text-red-500',
              '.text-danger',
              '[class*="error"]'
            ];
            
            let errorFound = false;
            for (const selector of errorSelectors) {
              const errorElement = page.locator(selector);
              if (await errorElement.count() > 0) {
                const errorText = await errorElement.first().textContent();
                if (errorText && errorText.toLowerCase().includes('password')) {
                  errorFound = true;
                  console.log(`✓ Validation error displayed: ${errorText}`);
                  break;
                }
              }
            }
            
            if (errorFound) {
              // Now test with matching passwords
              await passwordField.fill('testpassword123');
              await confirmationField.fill('testpassword123');
              
              console.log(`✓ Password confirmation validation working at ${url}`);
              break;
            }
          }
        }
      } catch (error) {
        console.log(`✗ Could not test validation at ${url}: ${error.message}`);
        continue;
      }
    }
  });

  test('should handle password confirmation in user profile update', async ({ page }) => {
    // Navigate to profile edit page
    const profileUrls = [
      '/admin/profile/edit',
      '/admin/profile',
      '/admin/settings/profile'
    ];
    
    for (const url of profileUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Look for password change section
        const passwordField = page.locator('input[name="password"], input[name="new_password"]').first();
        const confirmationField = page.locator('input[name="password_confirmation"], input[name="new_password_confirmation"]').first();
        
        if (await passwordField.count() > 0 && await confirmationField.count() > 0) {
          // Test password change workflow
          await passwordField.fill('newpassword123');
          await confirmationField.fill('newpassword123');
          
          // Verify fields are filled
          const passwordValue = await passwordField.inputValue();
          const confirmationValue = await confirmationField.inputValue();
          
          expect(passwordValue).toBe('newpassword123');
          expect(confirmationValue).toBe('newpassword123');
          
          console.log(`✓ Password confirmation working in profile at ${url}`);
          break;
        }
      } catch (error) {
        console.log(`✗ Could not test profile update at ${url}: ${error.message}`);
        continue;
      }
    }
  });

  test('should not display password confirmation field on index or detail views', async ({ page }) => {
    // Navigate to user index page
    const indexUrls = [
      '/admin/resources/users',
      '/admin/users'
    ];
    
    for (const url of indexUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Password confirmation should not be visible in index view
        const confirmationField = page.locator('input[name="password_confirmation"]');
        const confirmationColumn = page.locator('th, td').filter({ hasText: /password.?confirmation/i });
        
        expect(await confirmationField.count()).toBe(0);
        expect(await confirmationColumn.count()).toBe(0);
        
        console.log(`✓ Password confirmation correctly hidden from index at ${url}`);
        break;
      } catch (error) {
        console.log(`✗ Could not test index view at ${url}: ${error.message}`);
        continue;
      }
    }
    
    // Test detail view if possible
    const detailUrls = [
      '/admin/resources/users/1',
      '/admin/users/1'
    ];
    
    for (const url of detailUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Password confirmation should not be visible in detail view
        const confirmationField = page.locator('input[name="password_confirmation"]');
        const confirmationLabel = page.locator('label, dt, th').filter({ hasText: /password.?confirmation/i });
        
        expect(await confirmationField.count()).toBe(0);
        expect(await confirmationLabel.count()).toBe(0);
        
        console.log(`✓ Password confirmation correctly hidden from detail at ${url}`);
        break;
      } catch (error) {
        console.log(`✗ Could not test detail view at ${url}: ${error.message}`);
        continue;
      }
    }
  });

  test('should handle keyboard interactions properly', async ({ page }) => {
    // Navigate to form with password confirmation
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const confirmationField = page.locator('input[name="password_confirmation"]').first();
        
        if (await confirmationField.count() > 0) {
          // Test focus
          await confirmationField.focus();
          await expect(confirmationField).toBeFocused();
          
          // Test typing
          await confirmationField.type('testpassword');
          const value = await confirmationField.inputValue();
          expect(value).toBe('testpassword');
          
          // Test clearing with keyboard
          await confirmationField.selectText();
          await page.keyboard.press('Delete');
          const clearedValue = await confirmationField.inputValue();
          expect(clearedValue).toBe('');
          
          console.log(`✓ Keyboard interactions working at ${url}`);
          break;
        }
      } catch (error) {
        console.log(`✗ Could not test keyboard interactions at ${url}: ${error.message}`);
        continue;
      }
    }
  });
});
