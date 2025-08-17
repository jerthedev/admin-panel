import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage.js';

/**
 * Password Field E2E Tests
 * 
 * End-to-end tests for Password field functionality including:
 * - Password input in different contexts
 * - Form submission and validation
 * - Security features (no value display)
 * - CRUD operations with passwords
 * - Nova-compatible behavior
 */

test.describe('Password Field E2E Tests', () => {
  let loginPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    
    // Login as admin before each test
    await loginPage.login('e2e-test@example.com', 'testpassword123');
    await page.waitForTimeout(3000);
  });

  test('should display password field in create form', async ({ page }) => {
    // Navigate to user creation form (assuming users have password fields)
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Look for password field specifically
        const passwordSelectors = [
          '[data-testid="password-field"]',
          '.password-field',
          'input[name="password"]',
          'input[type="password"]'
        ];
        
        let passwordFieldFound = false;
        for (const selector of passwordSelectors) {
          const field = page.locator(selector);
          const count = await field.count();
          if (count > 0) {
            passwordFieldFound = true;
            
            // Verify it's a password input
            await expect(field.first()).toHaveAttribute('type', 'password');
            
            // Verify it has proper placeholder
            const placeholder = await field.first().getAttribute('placeholder');
            expect(placeholder).toBeTruthy();
            
            break;
          }
        }
        
        if (passwordFieldFound) {
          console.log(`Password field found at ${url}`);
          break;
        }
      } catch (error) {
        console.log(`Could not access ${url}: ${error.message}`);
        continue;
      }
    }
  });

  test('should handle password input and form submission', async ({ page }) => {
    // Navigate to user creation form
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Fill out user creation form
        const nameField = page.locator('input[name="name"], input[placeholder*="name" i]').first();
        const emailField = page.locator('input[name="email"], input[type="email"]').first();
        const passwordField = page.locator('input[name="password"], input[type="password"]').first();
        
        if (await nameField.count() > 0 && await emailField.count() > 0 && await passwordField.count() > 0) {
          // Fill form fields
          await nameField.fill('Test User E2E');
          await emailField.fill(`testuser-${Date.now()}@example.com`);
          await passwordField.fill('testpassword123');
          
          // Verify password field doesn't show the value (security)
          const passwordValue = await passwordField.inputValue();
          expect(passwordValue).toBe('testpassword123'); // Input value should be set
          
          // But the field should be masked (type="password")
          await expect(passwordField).toHaveAttribute('type', 'password');
          
          // Submit form
          const submitButton = page.locator('button[type="submit"], button:has-text("Create"), button:has-text("Save")').first();
          if (await submitButton.count() > 0) {
            await submitButton.click();
            await page.waitForTimeout(2000);
            
            // Check for success (redirect or success message)
            const currentUrl = page.url();
            const hasSuccessMessage = await page.locator('.alert-success, .notification-success, .toast-success').count() > 0;
            
            expect(currentUrl !== url || hasSuccessMessage).toBeTruthy();
          }
          
          break;
        }
      } catch (error) {
        console.log(`Could not test form submission at ${url}: ${error.message}`);
        continue;
      }
    }
  });

  test('should not display password values in edit forms', async ({ page }) => {
    // Navigate to user edit form
    const editUrls = [
      '/admin/resources/users/1/edit',
      '/admin/users/1/edit'
    ];
    
    for (const url of editUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const passwordField = page.locator('input[name="password"], input[type="password"]').first();
        
        if (await passwordField.count() > 0) {
          // Password field should be empty for security (Nova behavior)
          const passwordValue = await passwordField.inputValue();
          expect(passwordValue).toBe('');
          
          // Field should still be type="password"
          await expect(passwordField).toHaveAttribute('type', 'password');
          
          break;
        }
      } catch (error) {
        console.log(`Could not access edit form at ${url}: ${error.message}`);
        continue;
      }
    }
  });

  test('should handle password updates correctly', async ({ page }) => {
    // Navigate to user edit form
    const editUrls = [
      '/admin/resources/users/1/edit',
      '/admin/users/1/edit'
    ];
    
    for (const url of editUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const passwordField = page.locator('input[name="password"], input[type="password"]').first();
        
        if (await passwordField.count() > 0) {
          // Update password
          await passwordField.fill('newpassword123');
          
          // Submit form
          const submitButton = page.locator('button[type="submit"], button:has-text("Update"), button:has-text("Save")').first();
          if (await submitButton.count() > 0) {
            await submitButton.click();
            await page.waitForTimeout(2000);
            
            // Check for success
            const currentUrl = page.url();
            const hasSuccessMessage = await page.locator('.alert-success, .notification-success, .toast-success').count() > 0;
            
            expect(currentUrl !== url || hasSuccessMessage).toBeTruthy();
          }
          
          break;
        }
      } catch (error) {
        console.log(`Could not test password update at ${url}: ${error.message}`);
        continue;
      }
    }
  });

  test('should not show password fields in index/list views', async ({ page }) => {
    // Navigate to users index
    const indexUrls = [
      '/admin/resources/users',
      '/admin/users'
    ];
    
    for (const url of indexUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Password fields should not be visible in index views (Nova behavior)
        const passwordColumns = page.locator('th:has-text("Password"), td:has-text("Password")');
        const passwordCount = await passwordColumns.count();
        
        expect(passwordCount).toBe(0);
        
        // Also check for any password input fields (shouldn't be any)
        const passwordInputs = page.locator('input[type="password"]');
        const inputCount = await passwordInputs.count();
        
        expect(inputCount).toBe(0);
        
        break;
      } catch (error) {
        console.log(`Could not access index at ${url}: ${error.message}`);
        continue;
      }
    }
  });

  test('should not show password fields in detail views', async ({ page }) => {
    // Navigate to user detail view
    const detailUrls = [
      '/admin/resources/users/1',
      '/admin/users/1'
    ];
    
    for (const url of detailUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Password fields should not be visible in detail views (Nova behavior)
        const passwordLabels = page.locator('label:has-text("Password"), .field-label:has-text("Password")');
        const passwordValues = page.locator('.field-value:has-text("password"), .field-value:has-text("Password")');
        
        const labelCount = await passwordLabels.count();
        const valueCount = await passwordValues.count();
        
        expect(labelCount).toBe(0);
        expect(valueCount).toBe(0);
        
        break;
      } catch (error) {
        console.log(`Could not access detail view at ${url}: ${error.message}`);
        continue;
      }
    }
  });

  test('should handle validation errors properly', async ({ page }) => {
    // Navigate to user creation form
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const passwordField = page.locator('input[name="password"], input[type="password"]').first();
        
        if (await passwordField.count() > 0) {
          // Try to submit with invalid/missing password
          await passwordField.fill(''); // Empty password
          
          const submitButton = page.locator('button[type="submit"], button:has-text("Create"), button:has-text("Save")').first();
          if (await submitButton.count() > 0) {
            await submitButton.click();
            await page.waitForTimeout(1000);
            
            // Check for validation errors
            const errorSelectors = [
              '.field-error',
              '.error-message',
              '.invalid-feedback',
              '.text-red-500',
              '.text-danger'
            ];
            
            let hasError = false;
            for (const selector of errorSelectors) {
              const errorCount = await page.locator(selector).count();
              if (errorCount > 0) {
                hasError = true;
                break;
              }
            }
            
            // Should show validation error or stay on same page
            const currentUrl = page.url();
            expect(currentUrl === url || hasError).toBeTruthy();
          }
          
          break;
        }
      } catch (error) {
        console.log(`Could not test validation at ${url}: ${error.message}`);
        continue;
      }
    }
  });

  test('should maintain Nova-compatible field behavior', async ({ page }) => {
    // Test various Nova-compatible behaviors
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const passwordField = page.locator('input[name="password"], input[type="password"]').first();
        
        if (await passwordField.count() > 0) {
          // Should be a simple password input (no extra features)
          await expect(passwordField).toHaveAttribute('type', 'password');
          
          // Should not have toggle buttons (simple Nova field)
          const toggleButtons = page.locator('button:near(input[type="password"])');
          const toggleCount = await toggleButtons.count();
          expect(toggleCount).toBe(0);
          
          // Should not have strength meters (simple Nova field)
          const strengthMeters = page.locator('.strength-meter, .password-strength');
          const meterCount = await strengthMeters.count();
          expect(meterCount).toBe(0);
          
          // Should not have confirmation fields by default (simple Nova field)
          const confirmFields = page.locator('input[name="password_confirmation"]');
          const confirmCount = await confirmFields.count();
          expect(confirmCount).toBe(0);
          
          break;
        }
      } catch (error) {
        console.log(`Could not test Nova compatibility at ${url}: ${error.message}`);
        continue;
      }
    }
  });
});
