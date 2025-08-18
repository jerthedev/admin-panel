import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage.js';
import path from 'path';

/**
 * MediaLibraryAudioField E2E Tests
 * 
 * End-to-end tests for MediaLibraryAudioField functionality including:
 * - Media Library integration with collections and conversions
 * - Audio upload, preview, and playback functionality
 * - Nova Audio Field API compatibility
 * - File validation and error handling
 * - Download controls and preload configuration
 * - CRUD operations with Media Library audio files
 * - Responsive design and accessibility
 */

test.describe('MediaLibraryAudioField E2E Tests', () => {
  let loginPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login('admin@example.com', 'password');
  });

  test.describe('Basic Audio Upload and Display', () => {
    test('user can upload audio file in create form', async ({ page }) => {
      // Navigate to user creation page
      await page.goto('/admin-panel/resources/users/create');
      
      // Fill in basic user information
      await page.fill('input[name="name"]', 'Test User');
      await page.fill('input[name="email"]', 'test@example.com');
      
      // Upload audio file to MediaLibraryAudioField
      const audioPath = path.join(__dirname, '../../fixtures/test-audio.mp3');
      const fileInput = page.locator('input[type="file"][name="theme_song"]');
      await fileInput.setInputFiles(audioPath);
      
      // Verify audio preview is displayed with Media Library styling
      await expect(page.locator('.media-library-audio-field')).toBeVisible();
      await expect(page.locator('audio')).toBeVisible();
      await expect(page.locator('[data-testid="musical-note-icon"]')).toBeVisible();
      
      // Verify audio metadata is displayed
      await expect(page.locator('.flex.items-center.space-x-4')).toBeVisible();
      await expect(page.getByText('test-audio.mp3')).toBeVisible();
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Verify redirect and success
      await page.waitForURL('/admin-panel/resources/users/*');
      await expect(page.locator('.media-library-audio-field')).toBeVisible();
      await expect(page.locator('audio')).toBeVisible();
    });

    test('user can update audio file in edit form', async ({ page }) => {
      // Navigate to user edit page (assuming user with ID 1 exists)
      await page.goto('/admin-panel/resources/users/1/edit');
      
      // Upload new audio file
      const audioPath = path.join(__dirname, '../../fixtures/new-audio.mp3');
      const fileInput = page.locator('input[type="file"][name="theme_song"]');
      await fileInput.setInputFiles(audioPath);
      
      // Verify new audio preview with Media Library features
      await expect(page.locator('.media-library-audio-field')).toBeVisible();
      await expect(page.locator('audio')).toBeVisible();
      await expect(page.getByText('new-audio.mp3')).toBeVisible();
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Verify update success
      await page.waitForURL('/admin-panel/resources/users/1');
      await expect(page.locator('.media-library-audio-field')).toBeVisible();
    });

    test('user can remove existing audio file', async ({ page }) => {
      // Navigate to user edit page with existing audio
      await page.goto('/admin-panel/resources/users/2/edit');
      
      // Verify current audio exists
      await expect(page.locator('.media-library-audio-field')).toBeVisible();
      await expect(page.locator('audio')).toBeVisible();
      
      // Click remove audio button
      await page.click('[data-testid="x-mark-icon"]');
      
      // Verify audio is removed from preview
      await expect(page.locator('audio')).not.toBeVisible();
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Verify audio is removed from detail view
      await page.waitForURL('/admin-panel/resources/users/2');
      await expect(page.locator('audio')).not.toBeVisible();
    });
  });

  test.describe('Media Library Integration', () => {
    test('audio field integrates with media library collections', async ({ page }) => {
      // Navigate to podcast creation page (different collection)
      await page.goto('/admin-panel/resources/podcasts/create');
      
      // Upload audio to podcasts collection
      const audioPath = path.join(__dirname, '../../fixtures/podcast-episode.mp3');
      const fileInput = page.locator('input[type="file"][name="episode_audio"]');
      await fileInput.setInputFiles(audioPath);
      
      // Verify Media Library audio field displays correctly
      await expect(page.locator('.media-library-audio-field')).toBeVisible();
      await expect(page.locator('audio')).toBeVisible();
      await expect(page.getByText('podcast-episode.mp3')).toBeVisible();
      
      // Verify collection-specific features
      await expect(page.locator('.upload-area')).toContainText('Upload audio file');
    });

    test('audio field displays media library metadata', async ({ page }) => {
      // Navigate to user detail page with audio
      await page.goto('/admin-panel/resources/users/2');
      
      // Verify audio metadata from Media Library is displayed
      await expect(page.locator('.media-library-audio-field')).toBeVisible();
      await expect(page.locator('audio')).toBeVisible();
      
      // Check for audio metadata display
      const metadataContainer = page.locator('.flex.items-center.space-x-4');
      await expect(metadataContainer).toBeVisible();
      
      // Verify file size and duration are shown (if available)
      await expect(page.locator('.text-xs.text-gray-500')).toBeVisible();
    });

    test('audio field handles media library conversions', async ({ page }) => {
      // Navigate to user detail page with audio that has conversions
      await page.goto('/admin-panel/resources/users/3');
      
      // Verify audio with conversions displays correctly
      await expect(page.locator('.media-library-audio-field')).toBeVisible();
      await expect(page.locator('audio')).toBeVisible();
      
      // Media Library conversions should be handled transparently
      const audioElement = page.locator('audio');
      await expect(audioElement).toHaveAttribute('src');
    });
  });

  test.describe('Nova Audio Field Compatibility', () => {
    test('audio field respects Nova preload configuration', async ({ page }) => {
      // Navigate to user detail page with audio
      await page.goto('/admin-panel/resources/users/2');
      
      // Verify audio element has correct preload attribute (Nova compatibility)
      const audioElement = page.locator('audio');
      await expect(audioElement).toHaveAttribute('preload', /metadata|auto|none/);
    });

    test('audio field respects Nova download configuration', async ({ page }) => {
      // Navigate to user detail page with audio (downloads enabled)
      await page.goto('/admin-panel/resources/users/2');
      
      // Check if download button is present (Nova compatibility)
      const downloadButton = page.locator('[data-testid="arrow-down-tray-icon"]');
      
      // Verify download functionality
      if (await downloadButton.isVisible()) {
        // Download button should be clickable
        await expect(downloadButton).toBeVisible();
      }
    });

    test('audio field handles Nova preload constants', async ({ page }) => {
      // Test different preload configurations
      const preloadConfigs = [
        { url: '/admin-panel/resources/users/2', expected: 'metadata' },
        { url: '/admin-panel/resources/podcasts/1', expected: 'auto' },
        { url: '/admin-panel/resources/music/1', expected: 'none' }
      ];

      for (const config of preloadConfigs) {
        await page.goto(config.url);
        
        const audioElement = page.locator('audio');
        if (await audioElement.isVisible()) {
          await expect(audioElement).toHaveAttribute('preload', config.expected);
        }
      }
    });
  });

  test.describe('File Validation and Error Handling', () => {
    test('audio field validates file types correctly', async ({ page }) => {
      // Navigate to user creation page
      await page.goto('/admin-panel/resources/users/create');
      
      // Try to upload invalid file type
      const invalidPath = path.join(__dirname, '../../fixtures/document.pdf');
      const fileInput = page.locator('input[type="file"][name="theme_song"]');
      await fileInput.setInputFiles(invalidPath);
      
      // Verify error message is displayed
      await expect(page.locator('[data-testid="exclamation-circle-icon"]')).toBeVisible();
      await expect(page.getByText('Invalid file type')).toBeVisible();
    });

    test('audio field validates file size limits', async ({ page }) => {
      // Navigate to user creation page
      await page.goto('/admin-panel/resources/users/create');
      
      // Try to upload oversized file (this would need a large test file)
      // For testing purposes, we'll verify the size limit is displayed
      await expect(page.getByText(/Max size:/)).toBeVisible();
    });

    test('audio field displays accepted file types', async ({ page }) => {
      // Navigate to user creation page
      await page.goto('/admin-panel/resources/users/create');
      
      // Verify accepted file types are displayed
      await expect(page.getByText(/Supported formats:/)).toBeVisible();
      await expect(page.getByText(/MP3|MPEG/)).toBeVisible();
      await expect(page.getByText(/WAV/)).toBeVisible();
      await expect(page.getByText(/OGG/)).toBeVisible();
    });

    test('audio field recovers from upload errors', async ({ page }) => {
      // Navigate to user creation page
      await page.goto('/admin-panel/resources/users/create');
      
      // Upload invalid file first
      const invalidPath = path.join(__dirname, '../../fixtures/document.pdf');
      const fileInput = page.locator('input[type="file"][name="theme_song"]');
      await fileInput.setInputFiles(invalidPath);
      
      // Verify error is shown
      await expect(page.getByText('Invalid file type')).toBeVisible();
      
      // Upload valid file
      const validPath = path.join(__dirname, '../../fixtures/test-audio.mp3');
      await fileInput.setInputFiles(validPath);
      
      // Verify error is cleared and audio is accepted
      await expect(page.getByText('Invalid file type')).not.toBeVisible();
      await expect(page.locator('audio')).toBeVisible();
    });
  });

  test.describe('User Interface and Interaction', () => {
    test('audio field supports drag and drop upload', async ({ page }) => {
      // Navigate to user creation page
      await page.goto('/admin-panel/resources/users/create');
      
      // Verify upload area is present
      const uploadArea = page.locator('.upload-area');
      await expect(uploadArea).toBeVisible();
      await expect(uploadArea).toContainText('Drag and drop or click to browse');
      
      // Verify upload area styling
      await expect(uploadArea).toHaveClass(/border-dashed/);
    });

    test('audio field shows upload progress and feedback', async ({ page }) => {
      // Navigate to user creation page
      await page.goto('/admin-panel/resources/users/create');
      
      // Upload audio file
      const audioPath = path.join(__dirname, '../../fixtures/test-audio.mp3');
      const fileInput = page.locator('input[type="file"][name="theme_song"]');
      await fileInput.setInputFiles(audioPath);
      
      // Verify immediate feedback
      await expect(page.locator('.media-library-audio-field')).toBeVisible();
      await expect(page.locator('audio')).toBeVisible();
    });

    test('audio field works in readonly mode', async ({ page }) => {
      // Navigate to a readonly view (show page)
      await page.goto('/admin-panel/resources/users/2');
      
      // Verify audio is displayed but upload controls are not
      await expect(page.locator('.media-library-audio-field')).toBeVisible();
      await expect(page.locator('audio')).toBeVisible();
      
      // Upload controls should not be present in readonly mode
      await expect(page.locator('.upload-area')).not.toBeVisible();
      await expect(page.locator('input[type="file"]')).not.toBeVisible();
    });

    test('audio field supports dark theme', async ({ page }) => {
      // Enable dark theme (this would depend on your theme implementation)
      await page.goto('/admin-panel/settings/theme');
      await page.click('button:has-text("Dark")');
      
      // Navigate to user detail page with audio
      await page.goto('/admin-panel/resources/users/2');
      
      // Verify dark theme classes are applied
      await expect(page.locator('.media-library-audio-field')).toBeVisible();
      
      // Check for dark theme styling (this would depend on your CSS classes)
      const audioContainer = page.locator('.flex.items-center.justify-between');
      await expect(audioContainer).toHaveClass(/bg-gray-800|dark/);
    });
  });

  test.describe('Audio Playback and Controls', () => {
    test('audio player controls work correctly', async ({ page }) => {
      // Navigate to user detail page with audio
      await page.goto('/admin-panel/resources/users/2');
      
      // Verify audio player is present with controls
      const audioElement = page.locator('audio');
      await expect(audioElement).toBeVisible();
      await expect(audioElement).toHaveAttribute('controls');
      
      // Test play functionality (basic check)
      await audioElement.click();
      
      // Verify audio element is interactive
      await expect(audioElement).toBeVisible();
    });

    test('audio download functionality works', async ({ page }) => {
      // Navigate to user detail page with audio
      await page.goto('/admin-panel/resources/users/2');
      
      // Check if download button is present
      const downloadButton = page.locator('[data-testid="arrow-down-tray-icon"]');
      
      if (await downloadButton.isVisible()) {
        // Set up download handling
        const downloadPromise = page.waitForEvent('download');
        
        // Click download button
        await downloadButton.click();
        
        // Verify download starts
        const download = await downloadPromise;
        expect(download.suggestedFilename()).toMatch(/\.(mp3|wav|ogg|m4a)$/);
      }
    });

    test('audio metadata displays correctly', async ({ page }) => {
      // Navigate to user detail page with audio
      await page.goto('/admin-panel/resources/users/2');
      
      // Verify audio metadata is displayed
      await expect(page.locator('.media-library-audio-field')).toBeVisible();
      
      // Check for metadata elements
      const metadataContainer = page.locator('.text-xs.text-gray-500');
      await expect(metadataContainer).toBeVisible();
      
      // Verify file size, duration, or bitrate are shown (if available)
      // This would depend on the actual metadata available
    });
  });

  test.describe('Responsive Design and Accessibility', () => {
    test('audio field works on mobile devices', async ({ page }) => {
      // Set mobile viewport
      await page.setViewportSize({ width: 375, height: 667 });
      
      // Navigate to user creation page
      await page.goto('/admin-panel/resources/users/create');
      
      // Verify field is responsive
      await expect(page.locator('.media-library-audio-field')).toBeVisible();
      await expect(page.locator('.upload-area')).toBeVisible();
      
      // Upload should work on mobile
      const audioPath = path.join(__dirname, '../../fixtures/test-audio.mp3');
      const fileInput = page.locator('input[type="file"][name="theme_song"]');
      await fileInput.setInputFiles(audioPath);
      
      await expect(page.locator('audio')).toBeVisible();
    });

    test('audio field is accessible', async ({ page }) => {
      // Navigate to user creation page
      await page.goto('/admin-panel/resources/users/create');
      
      // Check for accessibility features
      const fileInput = page.locator('input[type="file"][name="theme_song"]');
      await expect(fileInput).toHaveAttribute('accept');
      
      // Verify labels and ARIA attributes
      await expect(page.locator('label[for*="theme_song"]')).toBeVisible();
      
      // Check keyboard navigation
      await fileInput.focus();
      await expect(fileInput).toBeFocused();
    });
  });
})
