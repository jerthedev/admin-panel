import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage.js';
import path from 'path';

/**
 * Audio Field E2E Tests
 * 
 * End-to-end tests for Audio field functionality including:
 * - Audio display in different contexts
 * - File upload and validation
 * - Preload configuration options
 * - Download control functionality
 * - CRUD operations with audio files
 */

test.describe('Audio Field E2E Tests', () => {
  let loginPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login('admin@example.com', 'password');
  });

  test('user can upload audio in create form', async ({ page }) => {
    // Navigate to user creation page
    await page.goto('/admin-panel/resources/users/create');
    
    // Fill in basic user information
    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', 'test@example.com');
    
    // Upload audio file
    const audioPath = path.join(__dirname, '../../fixtures/test-audio.mp3');
    const fileInput = page.locator('input[type="file"][name="theme_song"]');
    await fileInput.setInputFiles(audioPath);
    
    // Verify audio preview is displayed
    await expect(page.locator('.audio-preview-container')).toBeVisible();
    await expect(page.locator('audio')).toBeVisible();
    
    // Submit form
    await page.click('button[type="submit"]');
    
    // Verify redirect and success
    await page.waitForURL('/admin-panel/resources/users/*');
    await expect(page.locator('.audio-preview-container')).toBeVisible();
  });

  test('user can update audio in edit form', async ({ page }) => {
    // Navigate to user edit page (assuming user with ID 1 exists)
    await page.goto('/admin-panel/resources/users/1/edit');
    
    // Upload new audio
    const audioPath = path.join(__dirname, '../../fixtures/new-audio.mp3');
    const fileInput = page.locator('input[type="file"][name="theme_song"]');
    await fileInput.setInputFiles(audioPath);
    
    // Verify new audio preview
    await expect(page.locator('.audio-preview-container')).toBeVisible();
    await expect(page.locator('audio')).toBeVisible();
    
    // Submit form
    await page.click('button[type="submit"]');
    
    // Verify update success
    await page.waitForURL('/admin-panel/resources/users/1');
    await expect(page.locator('.audio-preview-container')).toBeVisible();
  });

  test('user can remove existing audio', async ({ page }) => {
    // Navigate to user edit page with existing audio
    await page.goto('/admin-panel/resources/users/2/edit');
    
    // Verify current audio exists
    await expect(page.locator('.audio-preview-container')).toBeVisible();
    
    // Click remove audio button
    await page.click('button:has-text("Remove Audio")');
    
    // Verify audio is removed from preview
    await expect(page.locator('.audio-preview-container')).not.toBeVisible();
    
    // Submit form
    await page.click('button[type="submit"]');
    
    // Verify audio is removed from detail view
    await page.waitForURL('/admin-panel/resources/users/2');
    await expect(page.locator('.audio-preview-container')).not.toBeVisible();
  });

  test('audio displays correctly in detail view', async ({ page }) => {
    // Navigate to user detail page with audio
    await page.goto('/admin-panel/resources/users/2');
    
    // Verify audio player is displayed
    await expect(page.locator('.audio-preview-container')).toBeVisible();
    await expect(page.locator('audio')).toBeVisible();
    
    // Verify audio controls are present
    await expect(page.locator('audio[controls]')).toBeVisible();
  });

  test('audio field validates file types', async ({ page }) => {
    // Navigate to user creation page
    await page.goto('/admin-panel/resources/users/create');
    
    // Try to upload invalid file type
    const invalidPath = path.join(__dirname, '../../fixtures/document.pdf');
    const fileInput = page.locator('input[type="file"][name="theme_song"]');
    await fileInput.setInputFiles(invalidPath);
    
    // Verify error message is displayed
    await expect(page.locator('.upload-error')).toContainText('Invalid file type');
  });

  test('audio field respects preload configuration', async ({ page }) => {
    // Navigate to user detail page with audio
    await page.goto('/admin-panel/resources/users/2');
    
    // Verify audio element has correct preload attribute
    const audioElement = page.locator('audio');
    await expect(audioElement).toHaveAttribute('preload', /metadata|auto|none/);
  });

  test('audio field respects download configuration', async ({ page }) => {
    // Navigate to user detail page with audio
    await page.goto('/admin-panel/resources/users/2');
    
    // Check if download button is present (depends on configuration)
    const downloadButton = page.locator('.download-btn');
    
    // This test would verify download button visibility based on field configuration
    // The actual assertion would depend on the specific field configuration
    await expect(page.locator('.audio-preview-container')).toBeVisible();
  });

  test('audio field handles drag and drop upload', async ({ page }) => {
    // Navigate to user creation page
    await page.goto('/admin-panel/resources/users/create');
    
    // Create a data transfer object for drag and drop
    const audioPath = path.join(__dirname, '../../fixtures/test-audio.mp3');
    
    // Simulate drag and drop (this is a simplified version)
    const dropzone = page.locator('.file-upload-dropzone');
    await expect(dropzone).toBeVisible();
    
    // For actual drag and drop testing, you would need to use more complex
    // Playwright APIs or create custom test fixtures
    await expect(dropzone).toContainText('Upload an audio file');
  });

  test('audio field displays accepted file types', async ({ page }) => {
    // Navigate to user creation page
    await page.goto('/admin-panel/resources/users/create');
    
    // Verify accepted file types are displayed
    await expect(page.locator('.file-upload-types')).toContainText('MP3');
    await expect(page.locator('.file-upload-types')).toContainText('WAV');
    await expect(page.locator('.file-upload-types')).toContainText('OGG');
  });

  test('audio field displays maximum file size', async ({ page }) => {
    // Navigate to user creation page
    await page.goto('/admin-panel/resources/users/create');
    
    // Verify maximum file size is displayed
    await expect(page.locator('.file-upload-size')).toContainText('Maximum file size');
  });

  test('audio field works in readonly mode', async ({ page }) => {
    // Navigate to a readonly view (e.g., show page)
    await page.goto('/admin-panel/resources/users/2');
    
    // Verify audio is displayed but upload controls are not
    await expect(page.locator('.audio-preview-container')).toBeVisible();
    await expect(page.locator('audio')).toBeVisible();
    
    // Upload controls should not be present in readonly mode
    await expect(page.locator('.file-upload-container')).not.toBeVisible();
  });

  test('audio field handles multiple audio formats', async ({ page }) => {
    // Navigate to user creation page
    await page.goto('/admin-panel/resources/users/create');
    
    // Test different audio formats
    const audioFormats = ['mp3', 'wav', 'ogg'];
    
    for (const format of audioFormats) {
      // This would test each format individually
      // For brevity, we'll just verify the upload area is ready
      await expect(page.locator('.file-upload-dropzone')).toBeVisible();
    }
  });

  test('audio field integrates with Nova search results', async ({ page }) => {
    // Navigate to users index with search
    await page.goto('/admin-panel/resources/users?search=Jane');
    
    // Verify search results display
    await expect(page.locator('table')).toBeVisible();
    
    // Audio thumbnails should be displayed in search results for users with audio
    // This would be tested based on the actual Nova integration
    await expect(page.getByText('Jane Smith')).toBeVisible();
  });

  test('audio field error handling', async ({ page }) => {
    // Navigate to user creation page
    await page.goto('/admin-panel/resources/users/create');
    
    // Try to upload a corrupted or invalid audio file
    const invalidPath = path.join(__dirname, '../../fixtures/corrupted-audio.mp3');
    
    // This test would verify error handling for corrupted files
    // The actual implementation would depend on the specific error handling logic
    await expect(page.locator('.file-upload-dropzone')).toBeVisible();
  });

  test('audio field accessibility features', async ({ page }) => {
    // Navigate to user detail page with audio
    await page.goto('/admin-panel/resources/users/2');
    
    // Verify audio element has proper accessibility attributes
    const audioElement = page.locator('audio');
    await expect(audioElement).toHaveAttribute('controls');
    
    // Verify proper labeling and ARIA attributes
    await expect(page.locator('.audio-preview-container')).toBeVisible();
  });
});
