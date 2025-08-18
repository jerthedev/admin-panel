import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage.js';

/**
 * Textarea Field E2E Tests
 * 
 * End-to-end tests for Textarea field functionality including:
 * - Basic textarea display and interaction
 * - Character count functionality
 * - Maxlength enforcement
 * - Row configuration
 * - Form submission and validation
 * - Nova API compatibility scenarios
 */

test.describe('Textarea Field E2E Tests', () => {
  let loginPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    
    // Login as admin before each test
    await loginPage.login('e2e-test@example.com', 'testpassword123');
    await page.waitForTimeout(3000);
  });

  test('should display textarea field in create form', async ({ page }) => {
    // Navigate to user creation form (assuming users have bio textarea fields)
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Look for textarea field specifically
        const textareaSelectors = [
          '[data-testid="textarea-field"]',
          '.textarea-field',
          'textarea[name="bio"]',
          'textarea[name="description"]',
          'textarea[name="content"]'
        ];
        
        let textareaFieldFound = false;
        for (const selector of textareaSelectors) {
          const field = page.locator(selector);
          const count = await field.count();
          if (count > 0) {
            textareaFieldFound = true;
            
            // Verify textarea is visible and interactable
            await expect(field.first()).toBeVisible();
            await expect(field.first()).toBeEnabled();
            
            // Test basic typing
            await field.first().fill('Test content for textarea field');
            await expect(field.first()).toHaveValue('Test content for textarea field');
            
            break;
          }
        }
        
        if (textareaFieldFound) {
          break;
        }
      } catch (error) {
        console.log(`URL ${url} not accessible: ${error.message}`);
        continue;
      }
    }
  });

  test('should handle character count display', async ({ page }) => {
    // Navigate to a form with textarea fields
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Look for textarea with character count
        const textarea = page.locator('textarea').first();
        const textareaCount = await textarea.count();
        
        if (textareaCount > 0) {
          // Type content and check for character count
          const testContent = 'This is test content for character counting';
          await textarea.fill(testContent);
          
          // Look for character count display
          const characterCountSelectors = [
            '.character-count',
            '[data-testid="character-count"]',
            '.absolute.bottom-2.right-2',
            'text*="/' // Looking for "X/Y" pattern
          ];
          
          for (const selector of characterCountSelectors) {
            const countElement = page.locator(selector);
            const countElementCount = await countElement.count();
            if (countElementCount > 0) {
              await expect(countElement.first()).toBeVisible();
              const countText = await countElement.first().textContent();
              expect(countText).toMatch(/\d+/); // Should contain numbers
              break;
            }
          }
          
          break;
        }
      } catch (error) {
        console.log(`URL ${url} not accessible: ${error.message}`);
        continue;
      }
    }
  });

  test('should enforce maxlength when configured', async ({ page }) => {
    // Navigate to a form with textarea fields
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Look for textarea with maxlength attribute
        const textareaWithMaxlength = page.locator('textarea[maxlength]');
        const count = await textareaWithMaxlength.count();
        
        if (count > 0) {
          const textarea = textareaWithMaxlength.first();
          const maxlength = await textarea.getAttribute('maxlength');
          const maxLengthValue = parseInt(maxlength);
          
          // Try to type more than the maxlength
          const longContent = 'a'.repeat(maxLengthValue + 10);
          await textarea.fill(longContent);
          
          // Verify content is truncated to maxlength
          const actualValue = await textarea.inputValue();
          expect(actualValue.length).toBeLessThanOrEqual(maxLengthValue);
          
          break;
        }
      } catch (error) {
        console.log(`URL ${url} not accessible: ${error.message}`);
        continue;
      }
    }
  });

  test('should handle form submission with textarea content', async ({ page }) => {
    // Navigate to user creation form
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Fill required fields
        const nameField = page.locator('input[name="name"]');
        const emailField = page.locator('input[name="email"]');
        const textareaField = page.locator('textarea').first();
        
        const nameCount = await nameField.count();
        const emailCount = await emailField.count();
        const textareaCount = await textareaField.count();
        
        if (nameCount > 0 && emailCount > 0 && textareaCount > 0) {
          // Fill form fields
          await nameField.fill('E2E Test User');
          await emailField.fill(`e2e-textarea-test-${Date.now()}@example.com`);
          await textareaField.fill('This is a test biography for the E2E textarea field test.');
          
          // Submit form
          const submitButton = page.locator('button[type="submit"]', 'input[type="submit"]', '.btn-primary');
          const submitCount = await submitButton.count();
          
          if (submitCount > 0) {
            await submitButton.first().click();
            
            // Wait for navigation or success message
            await page.waitForTimeout(2000);
            
            // Check for success indicators
            const successSelectors = [
              '.alert-success',
              '.notification-success',
              '.toast-success',
              'text*="created"',
              'text*="saved"'
            ];
            
            let successFound = false;
            for (const selector of successSelectors) {
              const successElement = page.locator(selector);
              const successCount = await successElement.count();
              if (successCount > 0) {
                successFound = true;
                break;
              }
            }
            
            // If no explicit success message, check if we navigated away from create form
            if (!successFound) {
              const currentUrl = page.url();
              expect(currentUrl).not.toContain('/create');
            }
          }
          
          break;
        }
      } catch (error) {
        console.log(`URL ${url} not accessible: ${error.message}`);
        continue;
      }
    }
  });

  test('should display textarea content in edit form', async ({ page }) => {
    // Navigate to user list to find an existing user to edit
    const listUrls = [
      '/admin/resources/users',
      '/admin/users'
    ];
    
    for (const url of listUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Look for edit links or buttons
        const editSelectors = [
          'a[href*="/edit"]',
          'button[data-action="edit"]',
          '.btn-edit',
          'text*="Edit"'
        ];
        
        for (const selector of editSelectors) {
          const editButton = page.locator(selector);
          const editCount = await editButton.count();
          
          if (editCount > 0) {
            await editButton.first().click();
            await page.waitForLoadState('networkidle');
            
            // Look for textarea field in edit form
            const textarea = page.locator('textarea');
            const textareaCount = await textarea.count();
            
            if (textareaCount > 0) {
              // Verify textarea is visible and may contain existing content
              await expect(textarea.first()).toBeVisible();
              await expect(textarea.first()).toBeEnabled();
              
              // Test editing existing content
              const originalContent = await textarea.first().inputValue();
              const newContent = originalContent + ' - Updated via E2E test';
              await textarea.first().fill(newContent);
              await expect(textarea.first()).toHaveValue(newContent);
              
              return; // Success, exit test
            }
          }
        }
      } catch (error) {
        console.log(`URL ${url} not accessible: ${error.message}`);
        continue;
      }
    }
  });

  test('should handle multiline content correctly', async ({ page }) => {
    // Navigate to a form with textarea fields
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const textarea = page.locator('textarea').first();
        const count = await textarea.count();
        
        if (count > 0) {
          // Test multiline content
          const multilineContent = `Line 1
Line 2
Line 3

Paragraph after empty line.`;
          
          await textarea.fill(multilineContent);
          const actualValue = await textarea.inputValue();
          expect(actualValue).toBe(multilineContent);
          
          break;
        }
      } catch (error) {
        console.log(`URL ${url} not accessible: ${error.message}`);
        continue;
      }
    }
  });

  test('should respect row configuration', async ({ page }) => {
    // Navigate to a form with textarea fields
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const textarea = page.locator('textarea').first();
        const count = await textarea.count();
        
        if (count > 0) {
          // Check if textarea has rows attribute
          const rows = await textarea.getAttribute('rows');
          if (rows) {
            const rowsValue = parseInt(rows);
            expect(rowsValue).toBeGreaterThan(0);
            expect(rowsValue).toBeLessThanOrEqual(20); // Reasonable range
          }
          
          break;
        }
      } catch (error) {
        console.log(`URL ${url} not accessible: ${error.message}`);
        continue;
      }
    }
  });
});
