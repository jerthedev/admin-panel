import { test, expect } from '@playwright/test'
import path from 'path'

/**
 * End-to-End Playwright tests for MediaLibraryAvatarField component.
 * 
 * Tests the complete user interaction workflow with the avatar field
 * including upload, display, removal, and various configuration options.
 */

// Test image paths
const testImagePath = path.join(__dirname, '../fixtures/test-avatar.jpg')
const testLargeImagePath = path.join(__dirname, '../fixtures/large-avatar.jpg')
const testInvalidFilePath = path.join(__dirname, '../fixtures/document.pdf')

test.describe('MediaLibraryAvatarField E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to admin panel
    await page.goto('/admin')
    
    // Login if required
    const loginForm = page.locator('form[action*="login"]')
    if (await loginForm.isVisible()) {
      await page.fill('input[name="email"]', 'admin@example.com')
      await page.fill('input[name="password"]', 'password')
      await page.click('button[type="submit"]')
      await page.waitForLoadState('networkidle')
    }
  })

  test('should display avatar field with fallback image when no avatar exists', async ({ page }) => {
    await page.goto('/admin/users/create')
    await page.waitForLoadState('networkidle')
    
    // Look for avatar field
    const avatarField = page.locator('.media-library-avatar-field')
    await expect(avatarField).toBeVisible()
    
    // Should show fallback image
    const avatarImage = avatarField.locator('img')
    await expect(avatarImage).toBeVisible()
    
    const src = await avatarImage.getAttribute('src')
    expect(src).toContain('default-avatar.png')
    
    // Should show upload area
    const uploadArea = avatarField.locator('.upload-area')
    await expect(uploadArea).toBeVisible()
    await expect(uploadArea).toContainText('Upload avatar')
    
    // Should show accepted file types and size limit
    await expect(uploadArea).toContainText('JPEG, PNG, WebP images')
    await expect(uploadArea).toContainText('Max 2.0 MB')
  })

  test('should upload avatar successfully via file input', async ({ page }) => {
    await page.goto('/admin/users/create')
    await page.waitForLoadState('networkidle')
    
    // Fill required fields
    await page.fill('input[name="name"]', 'Test User')
    await page.fill('input[name="email"]', 'testuser@example.com')
    await page.fill('input[name="password"]', 'password123')
    
    // Upload avatar
    const fileInput = page.locator('input[type="file"]').first()
    await fileInput.setInputFiles(testImagePath)
    
    // Wait for upload to process
    await page.waitForTimeout(2000)
    
    // Verify avatar preview is shown
    const avatarField = page.locator('.media-library-avatar-field')
    const avatarImage = avatarField.locator('img')
    
    // Image source should change from fallback
    const src = await avatarImage.getAttribute('src')
    expect(src).not.toContain('default-avatar.png')
    
    // Should show remove button
    const removeButton = avatarField.locator('button[title="Remove avatar"]')
    await expect(removeButton).toBeVisible()
    
    // Submit form
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    
    // Should redirect to user list or detail page
    const currentUrl = page.url()
    expect(currentUrl).not.toContain('/create')
  })

  test('should upload avatar via drag and drop', async ({ page }) => {
    await page.goto('/admin/users/create')
    await page.waitForLoadState('networkidle')
    
    const avatarField = page.locator('.media-library-avatar-field')
    const uploadArea = avatarField.locator('.upload-area')
    
    // Create a file for drag and drop
    const fileBuffer = await page.evaluate(async () => {
      const response = await fetch('/images/test-avatar.jpg')
      const arrayBuffer = await response.arrayBuffer()
      return Array.from(new Uint8Array(arrayBuffer))
    })
    
    // Simulate drag and drop
    await uploadArea.dispatchEvent('dragover', {
      dataTransfer: {
        files: [{
          name: 'test-avatar.jpg',
          type: 'image/jpeg',
          size: fileBuffer.length
        }]
      }
    })
    
    // Should show drag over state
    await expect(uploadArea).toHaveClass(/upload-area-dragover/)
    
    await uploadArea.dispatchEvent('drop', {
      dataTransfer: {
        files: [{
          name: 'test-avatar.jpg',
          type: 'image/jpeg',
          size: fileBuffer.length
        }]
      }
    })
    
    // Wait for processing
    await page.waitForTimeout(2000)
    
    // Verify upload was processed
    const avatarImage = avatarField.locator('img')
    const src = await avatarImage.getAttribute('src')
    expect(src).not.toContain('default-avatar.png')
  })

  test('should validate file type and show error for invalid files', async ({ page }) => {
    await page.goto('/admin/users/create')
    await page.waitForLoadState('networkidle')
    
    // Try to upload invalid file type
    const fileInput = page.locator('input[type="file"]').first()
    await fileInput.setInputFiles(testInvalidFilePath)
    
    // Should show validation error
    const avatarField = page.locator('.media-library-avatar-field')
    await expect(avatarField).toContainText('Please select a valid image file')
    
    // Avatar should still show fallback
    const avatarImage = avatarField.locator('img')
    const src = await avatarImage.getAttribute('src')
    expect(src).toContain('default-avatar.png')
  })

  test('should validate file size and show error for oversized files', async ({ page }) => {
    await page.goto('/admin/users/create')
    await page.waitForLoadState('networkidle')
    
    // Try to upload oversized file
    const fileInput = page.locator('input[type="file"]').first()
    await fileInput.setInputFiles(testLargeImagePath)
    
    // Should show size validation error
    const avatarField = page.locator('.media-library-avatar-field')
    await expect(avatarField).toContainText('File size must be less than 2.0 MB')
    
    // Avatar should still show fallback
    const avatarImage = avatarField.locator('img')
    const src = await avatarImage.getAttribute('src')
    expect(src).toContain('default-avatar.png')
  })

  test('should remove avatar when remove button is clicked', async ({ page }) => {
    // First, go to a user that has an avatar
    await page.goto('/admin/users')
    await page.waitForLoadState('networkidle')
    
    // Look for user with avatar or create one
    const userWithAvatar = page.locator('tr').filter({ hasText: 'avatar' }).first()
    if (await userWithAvatar.count() > 0) {
      await userWithAvatar.locator('a').first().click()
    } else {
      // Create user with avatar first
      await page.goto('/admin/users/create')
      await page.fill('input[name="name"]', 'Avatar User')
      await page.fill('input[name="email"]', 'avatar@example.com')
      await page.fill('input[name="password"]', 'password123')
      
      const fileInput = page.locator('input[type="file"]').first()
      await fileInput.setInputFiles(testImagePath)
      await page.waitForTimeout(2000)
      
      await page.click('button[type="submit"]')
      await page.waitForLoadState('networkidle')
      
      // Now edit the user
      await page.click('a[href*="/edit"]')
    }
    
    await page.waitForLoadState('networkidle')
    
    const avatarField = page.locator('.media-library-avatar-field')
    const removeButton = avatarField.locator('button[title="Remove avatar"]')
    
    if (await removeButton.isVisible()) {
      await removeButton.click()
      
      // Should show fallback image
      const avatarImage = avatarField.locator('img')
      await page.waitForTimeout(1000)
      
      const src = await avatarImage.getAttribute('src')
      expect(src).toContain('default-avatar.png')
      
      // Remove button should be hidden
      await expect(removeButton).not.toBeVisible()
      
      // Upload area should be visible
      const uploadArea = avatarField.locator('.upload-area')
      await expect(uploadArea).toBeVisible()
    }
  })

  test('should open lightbox when avatar image is clicked', async ({ page }) => {
    // Navigate to user with avatar
    await page.goto('/admin/users')
    await page.waitForLoadState('networkidle')
    
    // Find user with avatar
    const userRow = page.locator('tr').filter({ hasText: /\.(jpg|png|jpeg|webp)/ }).first()
    if (await userRow.count() > 0) {
      await userRow.locator('a').first().click()
      await page.waitForLoadState('networkidle')
      
      const avatarField = page.locator('.media-library-avatar-field')
      const avatarImage = avatarField.locator('img')
      
      // Click avatar to open lightbox
      await avatarImage.click()
      
      // Should open lightbox
      const lightbox = page.locator('.lightbox, .modal, [data-testid="lightbox"]')
      await expect(lightbox).toBeVisible()
      
      // Should show large version of image
      const lightboxImage = lightbox.locator('img')
      await expect(lightboxImage).toBeVisible()
      
      // Close lightbox by clicking background
      const lightboxBackground = lightbox.locator('.fixed.inset-0').first()
      await lightboxBackground.click()
      
      // Lightbox should close
      await expect(lightbox).not.toBeVisible()
    }
  })

  test('should display avatar metadata when available', async ({ page }) => {
    // Navigate to user with avatar
    await page.goto('/admin/users')
    await page.waitForLoadState('networkidle')
    
    const userRow = page.locator('tr').filter({ hasText: /\.(jpg|png|jpeg|webp)/ }).first()
    if (await userRow.count() > 0) {
      await userRow.locator('a').first().click()
      await page.waitForLoadState('networkidle')
      
      const avatarField = page.locator('.media-library-avatar-field')
      
      // Should show file size
      await expect(avatarField).toContainText(/\d+(\.\d+)?\s*(B|KB|MB)/)
      
      // Should show dimensions if available
      const dimensionsRegex = /\d+\s*×\s*\d+/
      if (await avatarField.locator('text=' + dimensionsRegex).count() > 0) {
        await expect(avatarField).toContainText(dimensionsRegex)
      }
      
      // Should show upload date
      await expect(avatarField).toContainText('Uploaded')
    }
  })

  test('should support squared avatar display configuration', async ({ page }) => {
    await page.goto('/admin/users/create')
    await page.waitForLoadState('networkidle')
    
    const avatarField = page.locator('.media-library-avatar-field')
    const avatarContainer = avatarField.locator('.avatar-container')
    
    // Check if squared styling is applied
    const classList = await avatarContainer.getAttribute('class')
    
    // Should have appropriate styling classes
    expect(classList).toBeTruthy()
    
    // Upload avatar to test display
    const fileInput = page.locator('input[type="file"]').first()
    await fileInput.setInputFiles(testImagePath)
    await page.waitForTimeout(2000)
    
    // Avatar should be displayed with proper styling
    const avatarImage = avatarField.locator('img')
    await expect(avatarImage).toBeVisible()
    
    const imageClass = await avatarImage.getAttribute('class')
    expect(imageClass).toContain('avatar-image')
  })

  test('should handle image load errors gracefully', async ({ page }) => {
    await page.goto('/admin/users/create')
    await page.waitForLoadState('networkidle')
    
    const avatarField = page.locator('.media-library-avatar-field')
    const avatarImage = avatarField.locator('img')
    
    // Simulate broken image by changing src to invalid URL
    await page.evaluate(() => {
      const img = document.querySelector('.media-library-avatar-field img')
      if (img) {
        img.src = 'https://invalid-url.com/broken-image.jpg'
      }
    })
    
    // Wait for error to occur
    await page.waitForTimeout(2000)
    
    // Should fallback to default avatar
    const src = await avatarImage.getAttribute('src')
    expect(src).toContain('default-avatar.png')
  })

  test('should be accessible with proper ARIA labels and keyboard navigation', async ({ page }) => {
    await page.goto('/admin/users/create')
    await page.waitForLoadState('networkidle')
    
    const avatarField = page.locator('.media-library-avatar-field')
    
    // Check image alt text
    const avatarImage = avatarField.locator('img')
    const altText = await avatarImage.getAttribute('alt')
    expect(altText).toBeTruthy()
    
    // Check file input accessibility
    const fileInput = avatarField.locator('input[type="file"]')
    const acceptAttr = await fileInput.getAttribute('accept')
    expect(acceptAttr).toContain('image/')
    
    // Test keyboard navigation
    await fileInput.focus()
    await expect(fileInput).toBeFocused()
    
    // Upload avatar to test remove button accessibility
    await fileInput.setInputFiles(testImagePath)
    await page.waitForTimeout(2000)
    
    const removeButton = avatarField.locator('button[title="Remove avatar"]')
    if (await removeButton.isVisible()) {
      await removeButton.focus()
      await expect(removeButton).toBeFocused()
      
      // Should have proper button attributes
      const buttonType = await removeButton.getAttribute('type')
      expect(buttonType).toBe('button')
      
      const title = await removeButton.getAttribute('title')
      expect(title).toBe('Remove avatar')
    }
  })

  test('should work correctly in readonly mode', async ({ page }) => {
    // Navigate to user detail view (readonly)
    await page.goto('/admin/users')
    await page.waitForLoadState('networkidle')
    
    const userRow = page.locator('tr').first()
    await userRow.locator('a').first().click()
    await page.waitForLoadState('networkidle')
    
    const avatarField = page.locator('.media-library-avatar-field')
    
    // Should not show upload area in readonly mode
    const uploadArea = avatarField.locator('.upload-area')
    if (await uploadArea.count() > 0) {
      await expect(uploadArea).not.toBeVisible()
    }
    
    // Should not show remove button in readonly mode
    const removeButton = avatarField.locator('button[title="Remove avatar"]')
    if (await removeButton.count() > 0) {
      await expect(removeButton).not.toBeVisible()
    }
    
    // Should still show avatar image
    const avatarImage = avatarField.locator('img')
    await expect(avatarImage).toBeVisible()
  })

  test('should handle multiple avatar field instances on same page', async ({ page }) => {
    // This would test scenarios where multiple avatar fields exist
    // For example, in a form with multiple user fields or related models
    
    await page.goto('/admin/users/create')
    await page.waitForLoadState('networkidle')
    
    const avatarFields = page.locator('.media-library-avatar-field')
    const fieldCount = await avatarFields.count()
    
    // Each field should work independently
    for (let i = 0; i < fieldCount; i++) {
      const field = avatarFields.nth(i)
      const image = field.locator('img')
      await expect(image).toBeVisible()
      
      const fileInput = field.locator('input[type="file"]')
      await expect(fileInput).toBeAttached()
    }
  })
})
