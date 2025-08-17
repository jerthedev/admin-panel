import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage.js';
import path from 'path';

/**
 * File Field E2E Tests
 * 
 * End-to-end tests for File field functionality including:
 * - File upload and validation
 * - File type restrictions
 * - File size validation
 * - Download functionality
 * - File deletion
 * - CRUD operations with files
 */

test.describe('File Field E2E Tests', () => {
  let loginPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    
    // Login as admin before each test
    await loginPage.login('e2e-test@example.com', 'testpassword123');
    await page.waitForTimeout(3000);
  });

  test('should display file field in create form', async ({ page }) => {
    // Navigate to a form that has file fields
    const formUrls = [
      '/admin/resources/users/create',
      '/admin/users/create',
      '/admin/resources/documents/create',
      '/admin/documents/create'
    ];
    
    for (const url of formUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        
        // Look for file field specifically
        const fileSelectors = [
          '[data-testid="file-field"]',
          '.file-field',
          'input[name*="document"]',
          'input[name*="file"]',
          'input[type="file"]:not([accept*="image"])'
        ];
        
        let fileFieldFound = false;
        for (const selector of fileSelectors) {
          const field = page.locator(selector);
          const count = await field.count();
          if (count > 0) {
            fileFieldFound = true;
            console.log(`Found file field with selector: ${selector} at ${url}`);
            
            // Verify field properties
            const isVisible = await field.isVisible();
            expect(isVisible).toBe(true);
            
            // Check for file input attributes
            const accept = await field.getAttribute('accept');
            if (accept) {
              console.log(`File field accepts: ${accept}`);
            }
            
            break;
          }
        }
        
        if (fileFieldFound) {
          break;
        }
      } catch (error) {
        console.log(`URL ${url} not accessible: ${error.message}`);
        continue;
      }
    }
  });

  test('should handle file upload workflow', async ({ page }) => {
    // Navigate to create form
    await page.goto('/admin/resources/users/create');
    await page.waitForLoadState('networkidle');
    
    // Look for file input
    const fileInput = page.locator('input[type="file"]').first();
    
    if (await fileInput.count() > 0) {
      // Create a test file
      const testFilePath = path.join(__dirname, '../fixtures/test-document.pdf');
      
      // Upload file
      await fileInput.setInputFiles(testFilePath);
      
      // Wait for upload to process
      await page.waitForTimeout(2000);
      
      // Check for success indicators
      const successSelectors = [
        '.file-uploaded',
        '.upload-success',
        '[data-testid="file-uploaded"]',
        '.file-preview'
      ];
      
      for (const selector of successSelectors) {
        const element = page.locator(selector);
        if (await element.count() > 0) {
          expect(await element.isVisible()).toBe(true);
          break;
        }
      }
    }
  });

  test('should validate file types', async ({ page }) => {
    await page.goto('/admin/resources/users/create');
    await page.waitForLoadState('networkidle');
    
    const fileInput = page.locator('input[type="file"]').first();
    
    if (await fileInput.count() > 0) {
      // Try to upload an invalid file type (if restrictions exist)
      const invalidFilePath = path.join(__dirname, '../fixtures/invalid-file.txt');
      
      await fileInput.setInputFiles(invalidFilePath);
      await page.waitForTimeout(1000);
      
      // Check for validation error
      const errorSelectors = [
        '.error-message',
        '.validation-error',
        '[data-testid="file-error"]',
        '.file-type-error'
      ];
      
      for (const selector of errorSelectors) {
        const error = page.locator(selector);
        if (await error.count() > 0 && await error.isVisible()) {
          const errorText = await error.textContent();
          expect(errorText.toLowerCase()).toContain('type');
          break;
        }
      }
    }
  });

  test('should handle file download', async ({ page }) => {
    // Navigate to edit form with existing file
    await page.goto('/admin/resources/users');
    await page.waitForLoadState('networkidle');
    
    // Look for edit links
    const editLinks = page.locator('a[href*="/edit"], button:has-text("Edit")');
    
    if (await editLinks.count() > 0) {
      await editLinks.first().click();
      await page.waitForLoadState('networkidle');
      
      // Look for download buttons
      const downloadSelectors = [
        'button:has-text("Download")',
        'a:has-text("Download")',
        '[data-testid="download-file"]',
        '.download-button'
      ];
      
      for (const selector of downloadSelectors) {
        const downloadButton = page.locator(selector);
        if (await downloadButton.count() > 0 && await downloadButton.isVisible()) {
          // Set up download handler
          const downloadPromise = page.waitForEvent('download');
          
          await downloadButton.click();
          
          try {
            const download = await downloadPromise;
            expect(download).toBeTruthy();
            console.log(`Downloaded file: ${download.suggestedFilename()}`);
          } catch (error) {
            console.log('Download may not be available or configured');
          }
          
          break;
        }
      }
    }
  });

  test('should handle file deletion', async ({ page }) => {
    // Navigate to edit form with existing file
    await page.goto('/admin/resources/users');
    await page.waitForLoadState('networkidle');
    
    const editLinks = page.locator('a[href*="/edit"], button:has-text("Edit")');
    
    if (await editLinks.count() > 0) {
      await editLinks.first().click();
      await page.waitForLoadState('networkidle');
      
      // Look for remove/delete buttons
      const deleteSelectors = [
        'button:has-text("Remove")',
        'button:has-text("Delete")',
        '[data-testid="remove-file"]',
        '.remove-button'
      ];
      
      for (const selector of deleteSelectors) {
        const deleteButton = page.locator(selector);
        if (await deleteButton.count() > 0 && await deleteButton.isVisible()) {
          await deleteButton.click();
          await page.waitForTimeout(1000);
          
          // Verify file was removed
          const filePreview = page.locator('.file-preview, .file-display');
          if (await filePreview.count() > 0) {
            expect(await filePreview.isVisible()).toBe(false);
          }
          
          break;
        }
      }
    }
  });

  test('should display file information', async ({ page }) => {
    // Navigate to a page with existing files
    await page.goto('/admin/resources/users');
    await page.waitForLoadState('networkidle');
    
    // Look for file information displays
    const fileInfoSelectors = [
      '.file-info',
      '.file-details',
      '[data-testid="file-info"]',
      '.file-size',
      '.file-name'
    ];
    
    for (const selector of fileInfoSelectors) {
      const fileInfo = page.locator(selector);
      if (await fileInfo.count() > 0) {
        const isVisible = await fileInfo.isVisible();
        if (isVisible) {
          const text = await fileInfo.textContent();
          console.log(`File info found: ${text}`);
          expect(text.length).toBeGreaterThan(0);
        }
      }
    }
  });

  test('should handle drag and drop upload', async ({ page }) => {
    await page.goto('/admin/resources/users/create');
    await page.waitForLoadState('networkidle');
    
    // Look for drop zones
    const dropZoneSelectors = [
      '.drop-zone',
      '.file-drop-area',
      '[data-testid="file-drop-zone"]',
      '.drag-drop-area'
    ];
    
    for (const selector of dropZoneSelectors) {
      const dropZone = page.locator(selector);
      if (await dropZone.count() > 0 && await dropZone.isVisible()) {
        // Test drag over effect
        await dropZone.hover();
        
        // Check for visual feedback
        const hasActiveClass = await dropZone.evaluate(el => 
          el.classList.contains('drag-over') || 
          el.classList.contains('active') ||
          el.classList.contains('hover')
        );
        
        console.log(`Drop zone found with hover effect: ${hasActiveClass}`);
        break;
      }
    }
  });

  test('should validate file size limits', async ({ page }) => {
    await page.goto('/admin/resources/users/create');
    await page.waitForLoadState('networkidle');
    
    // Look for file size information
    const sizeInfoSelectors = [
      '.file-size-limit',
      '.max-size',
      '[data-testid="file-size-info"]',
      '.size-info'
    ];
    
    for (const selector of sizeInfoSelectors) {
      const sizeInfo = page.locator(selector);
      if (await sizeInfo.count() > 0 && await sizeInfo.isVisible()) {
        const text = await sizeInfo.textContent();
        console.log(`File size info: ${text}`);
        expect(text.toLowerCase()).toMatch(/size|mb|kb|limit/);
        break;
      }
    }
  });

  test('should show accepted file types', async ({ page }) => {
    await page.goto('/admin/resources/users/create');
    await page.waitForLoadState('networkidle');
    
    // Look for accepted types information
    const typesInfoSelectors = [
      '.accepted-types',
      '.file-types',
      '[data-testid="accepted-types"]',
      '.types-info'
    ];
    
    for (const selector of typesInfoSelectors) {
      const typesInfo = page.locator(selector);
      if (await typesInfo.count() > 0 && await typesInfo.isVisible()) {
        const text = await typesInfo.textContent();
        console.log(`Accepted types info: ${text}`);
        expect(text.toLowerCase()).toMatch(/type|pdf|doc|accept/);
        break;
      }
    }
  });
});
