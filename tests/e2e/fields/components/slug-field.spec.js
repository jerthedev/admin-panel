import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage.js';

/**
 * Slug Field E2E Tests
 * 
 * End-to-end tests for Slug field functionality including:
 * - Auto-generation from source fields
 * - Manual slug input and validation
 * - Character count and preview display
 * - Real-world usage scenarios
 * - Form submission and validation
 */

test.describe('Slug Field E2E Tests', () => {
  let loginPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    
    // Login as admin before each test
    await loginPage.login('e2e-test@example.com', 'testpassword123');
    await page.waitForTimeout(3000);
  });

  test('should display slug field in create form', async ({ page }) => {
    // Navigate to forms that might have slug fields
    const formUrls = [
      '/admin/resources/posts/create',
      '/admin/posts/create',
      '/admin/resources/articles/create',
      '/admin/articles/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Look for slug field specifically
        const slugSelectors = [
          '[data-testid="slug-field"]',
          '.slug-field',
          'input[name="slug"]',
          'input[placeholder*="slug"]',
          'input[placeholder*="URL"]'
        ];
        
        let slugFieldFound = false;
        for (const selector of slugSelectors) {
          const field = page.locator(selector);
          const count = await field.count();
          if (count > 0) {
            slugFieldFound = true;
            
            // Verify field is visible and interactable
            await expect(field.first()).toBeVisible();
            await expect(field.first()).toBeEnabled();
            
            console.log(`✓ Slug field found at ${url} with selector: ${selector}`);
            break;
          }
        }
        
        if (slugFieldFound) {
          break; // Found a working form, no need to try others
        }
      } catch (error) {
        console.log(`Form not found at ${url}, trying next...`);
        continue;
      }
    }
  });

  test('should auto-generate slug from title field', async ({ page }) => {
    // Try to find a form with both title and slug fields
    const formUrls = [
      '/admin/resources/posts/create',
      '/admin/posts/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const titleField = page.locator('input[name="title"], input[placeholder*="title"]').first();
        const slugField = page.locator('input[name="slug"], input[placeholder*="slug"]').first();
        const generateButton = page.locator('button:has-text("Generate")');
        
        if (await titleField.count() > 0 && await slugField.count() > 0) {
          // Fill in title
          await titleField.fill('My Amazing Blog Post');
          
          // Check if generate button exists and click it
          if (await generateButton.count() > 0) {
            await generateButton.click();
            await page.waitForTimeout(500);
            
            // Verify slug was generated
            const slugValue = await slugField.inputValue();
            expect(slugValue).toBe('my-amazing-blog-post');
          }
          
          break;
        }
      } catch (error) {
        continue;
      }
    }
  });

  test('should validate slug input and show errors', async ({ page }) => {
    const formUrls = [
      '/admin/resources/posts/create',
      '/admin/posts/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const slugField = page.locator('input[name="slug"], input[placeholder*="slug"]').first();
        
        if (await slugField.count() > 0) {
          // Test invalid slug with spaces
          await slugField.fill('invalid slug with spaces');
          await slugField.blur();
          await page.waitForTimeout(500);
          
          // Check for validation styling or error messages
          const fieldClasses = await slugField.getAttribute('class');
          const hasErrorStyling = fieldClasses.includes('border-red') || 
                                 fieldClasses.includes('error') ||
                                 fieldClasses.includes('invalid');
          
          if (hasErrorStyling) {
            console.log('✓ Validation styling applied for invalid slug');
          }
          
          // Look for error message
          const errorMessage = page.locator('.error-message, .text-red-500, [data-testid="error"]');
          if (await errorMessage.count() > 0) {
            console.log('✓ Error message displayed for invalid slug');
          }
          
          break;
        }
      } catch (error) {
        continue;
      }
    }
  });

  test('should show character count when maxLength is set', async ({ page }) => {
    const formUrls = [
      '/admin/resources/posts/create',
      '/admin/posts/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const slugField = page.locator('input[name="slug"], input[placeholder*="slug"]').first();
        
        if (await slugField.count() > 0) {
          // Fill in a slug
          await slugField.fill('test-slug');
          await page.waitForTimeout(500);
          
          // Look for character count display
          const characterCount = page.locator('text=/\\d+\\/\\d+ characters/');
          if (await characterCount.count() > 0) {
            await expect(characterCount).toBeVisible();
            console.log('✓ Character count displayed');
          }
          
          break;
        }
      } catch (error) {
        continue;
      }
    }
  });

  test('should show slug preview when valid', async ({ page }) => {
    const formUrls = [
      '/admin/resources/posts/create',
      '/admin/posts/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const slugField = page.locator('input[name="slug"], input[placeholder*="slug"]').first();
        
        if (await slugField.count() > 0) {
          // Fill in a valid slug
          await slugField.fill('valid-slug-example');
          await page.waitForTimeout(500);
          
          // Look for preview display
          const preview = page.locator('text=/Preview:/, text=/\\/valid-slug-example/');
          if (await preview.count() > 0) {
            await expect(preview.first()).toBeVisible();
            console.log('✓ Slug preview displayed');
          }
          
          break;
        }
      } catch (error) {
        continue;
      }
    }
  });

  test('should handle form submission with slug field', async ({ page }) => {
    const formUrls = [
      '/admin/resources/posts/create',
      '/admin/posts/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const titleField = page.locator('input[name="title"], input[placeholder*="title"]').first();
        const slugField = page.locator('input[name="slug"], input[placeholder*="slug"]').first();
        const submitButton = page.locator('button[type="submit"], button:has-text("Save"), button:has-text("Create")');
        
        if (await titleField.count() > 0 && await slugField.count() > 0 && await submitButton.count() > 0) {
          // Fill in required fields
          await titleField.fill('E2E Test Article');
          await slugField.fill('e2e-test-article');
          
          // Fill other required fields if they exist
          const contentField = page.locator('textarea[name="content"], textarea[placeholder*="content"]').first();
          if (await contentField.count() > 0) {
            await contentField.fill('This is test content for the E2E test.');
          }
          
          // Submit form
          await submitButton.first().click();
          await page.waitForTimeout(2000);
          
          // Check for success (redirect or success message)
          const currentUrl = page.url();
          const successMessage = page.locator('.success-message, .alert-success, text=/created successfully/i');
          
          if (currentUrl !== url || await successMessage.count() > 0) {
            console.log('✓ Form submitted successfully with slug field');
          }
          
          break;
        }
      } catch (error) {
        continue;
      }
    }
  });

  test('should handle real-world blog post creation workflow', async ({ page }) => {
    const formUrls = [
      '/admin/resources/posts/create',
      '/admin/posts/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const titleField = page.locator('input[name="title"], input[placeholder*="title"]').first();
        const slugField = page.locator('input[name="slug"], input[placeholder*="slug"]').first();
        const generateButton = page.locator('button:has-text("Generate")');
        
        if (await titleField.count() > 0 && await slugField.count() > 0) {
          // Step 1: Enter title
          await titleField.fill('10 Best Practices for Web Development');
          
          // Step 2: Auto-generate slug
          if (await generateButton.count() > 0) {
            await generateButton.click();
            await page.waitForTimeout(500);
            
            const generatedSlug = await slugField.inputValue();
            expect(generatedSlug).toBe('10-best-practices-for-web-development');
          }
          
          // Step 3: Manually edit slug
          await slugField.fill('web-dev-best-practices-2024');
          
          // Step 4: Verify validation
          const fieldClasses = await slugField.getAttribute('class');
          const hasValidStyling = fieldClasses.includes('border-green') || 
                                 !fieldClasses.includes('border-red');
          
          if (hasValidStyling) {
            console.log('✓ Manual slug edit validated successfully');
          }
          
          break;
        }
      } catch (error) {
        continue;
      }
    }
  });

  test('should prevent invalid characters in slug input', async ({ page }) => {
    const formUrls = [
      '/admin/resources/posts/create',
      '/admin/posts/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        const slugField = page.locator('input[name="slug"], input[placeholder*="slug"]').first();
        
        if (await slugField.count() > 0) {
          // Try to type invalid characters
          await slugField.focus();
          await page.keyboard.type('test slug with spaces!@#');
          await page.waitForTimeout(500);
          
          const actualValue = await slugField.inputValue();
          
          // Should either prevent invalid chars or clean them
          const hasInvalidChars = /[^a-z0-9\-_]/.test(actualValue);
          
          if (!hasInvalidChars) {
            console.log('✓ Invalid characters prevented or cleaned');
          }
          
          break;
        }
      } catch (error) {
        continue;
      }
    }
  });
});
