import { test, expect } from '@playwright/test'

/**
 * Image Field E2E Tests
 * 
 * Tests the complete end-to-end functionality of Image fields
 * in a real browser environment using Playwright.
 * 
 * Focuses on Nova-compatible Image field functionality:
 * - Extends File field with same options and configurations
 * - Displays thumbnail preview of underlying image
 * - Supports squared() and rounded() display options
 * - Supports disableDownload() method
 */

test.describe('Image Field E2E', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the admin panel test page
    await page.goto('/admin/test/image-field')
  })

  test('displays image field with Nova-compatible structure', async ({ page }) => {
    // Check that the image field is rendered
    await expect(page.locator('[data-testid="image-field"]')).toBeVisible()
    
    // Check for field label
    await expect(page.locator('label')).toContainText('Profile Image')
    
    // Check for upload area
    await expect(page.locator('[data-testid="upload-area"]')).toBeVisible()
  })

  test('handles image file upload workflow', async ({ page }) => {
    // Locate the file input
    const fileInput = page.locator('input[type="file"]')
    await expect(fileInput).toBeAttached()

    // Create a test image file
    const testImagePath = './tests/fixtures/test-image.jpg'
    
    // Upload the file
    await fileInput.setInputFiles(testImagePath)
    
    // Wait for upload to process
    await page.waitForTimeout(1000)
    
    // Check that preview is displayed
    await expect(page.locator('[data-testid="image-preview"]')).toBeVisible()
    
    // Check that the image src is set
    const previewImage = page.locator('[data-testid="image-preview"] img')
    await expect(previewImage).toHaveAttribute('src', /blob:|data:/)
  })

  test('displays existing image when modelValue is provided', async ({ page }) => {
    // Navigate to page with existing image
    await page.goto('/admin/test/image-field?existing=true')
    
    // Check that existing image is displayed
    await expect(page.locator('[data-testid="existing-image"]')).toBeVisible()
    
    // Check that image has correct src
    const existingImage = page.locator('[data-testid="existing-image"] img')
    await expect(existingImage).toHaveAttribute('src', /\/storage\//)
  })

  test('applies squared styling when configured', async ({ page }) => {
    // Navigate to page with squared image field
    await page.goto('/admin/test/image-field?squared=true')
    
    // Upload an image
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/test-image.jpg')
    
    // Wait for preview
    await page.waitForTimeout(1000)
    
    // Check that squared class is applied
    const previewImage = page.locator('[data-testid="image-preview"] img')
    await expect(previewImage).toHaveClass(/image-preview-squared/)
  })

  test('applies rounded styling when configured', async ({ page }) => {
    // Navigate to page with rounded image field
    await page.goto('/admin/test/image-field?rounded=true')
    
    // Upload an image
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/test-image.jpg')
    
    // Wait for preview
    await page.waitForTimeout(1000)
    
    // Check that rounded class is applied
    const previewImage = page.locator('[data-testid="image-preview"] img')
    await expect(previewImage).toHaveClass(/image-preview-rounded/)
  })

  test('applies both squared and rounded styling when both configured', async ({ page }) => {
    // Navigate to page with both squared and rounded
    await page.goto('/admin/test/image-field?squared=true&rounded=true')
    
    // Upload an image
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/test-image.jpg')
    
    // Wait for preview
    await page.waitForTimeout(1000)
    
    // Check that both classes are applied
    const previewImage = page.locator('[data-testid="image-preview"] img')
    await expect(previewImage).toHaveClass(/image-preview-squared/)
    await expect(previewImage).toHaveClass(/image-preview-rounded/)
  })

  test('validates file size limits', async ({ page }) => {
    // Navigate to page with size limit
    await page.goto('/admin/test/image-field?maxSize=1024')
    
    // Try to upload a large file (this would need to be a real large file)
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/large-image.jpg')
    
    // Check for validation error
    await expect(page.locator('[data-testid="validation-error"]')).toBeVisible()
    await expect(page.locator('[data-testid="validation-error"]')).toContainText('File size exceeds')
  })

  test('validates accepted file types', async ({ page }) => {
    // Navigate to page with type restrictions
    await page.goto('/admin/test/image-field?acceptedTypes=image/jpeg,image/png')
    
    // Try to upload an invalid file type
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/document.pdf')
    
    // Check for validation error
    await expect(page.locator('[data-testid="validation-error"]')).toBeVisible()
    await expect(page.locator('[data-testid="validation-error"]')).toContainText('Invalid file type')
  })

  test('supports drag and drop upload', async ({ page }) => {
    // Create a test file
    const testFile = await page.evaluateHandle(() => {
      const file = new File(['test content'], 'drag-test.jpg', { type: 'image/jpeg' })
      return file
    })
    
    // Get the upload area
    const uploadArea = page.locator('[data-testid="upload-area"]')
    
    // Simulate drag and drop
    await uploadArea.dispatchEvent('dragenter', { dataTransfer: { files: [testFile] } })
    await uploadArea.dispatchEvent('dragover', { dataTransfer: { files: [testFile] } })
    await uploadArea.dispatchEvent('drop', { dataTransfer: { files: [testFile] } })
    
    // Wait for processing
    await page.waitForTimeout(1000)
    
    // Check that preview is displayed
    await expect(page.locator('[data-testid="image-preview"]')).toBeVisible()
  })

  test('removes uploaded image when remove button clicked', async ({ page }) => {
    // Upload an image first
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/test-image.jpg')
    
    // Wait for preview
    await page.waitForTimeout(1000)
    
    // Check preview is visible
    await expect(page.locator('[data-testid="image-preview"]')).toBeVisible()
    
    // Click remove button
    await page.locator('[data-testid="remove-image"]').click()
    
    // Check preview is hidden
    await expect(page.locator('[data-testid="image-preview"]')).not.toBeVisible()
    
    // Check upload area is visible again
    await expect(page.locator('[data-testid="upload-area"]')).toBeVisible()
  })

  test('opens image in fullscreen when view button clicked', async ({ page }) => {
    // Navigate to page with existing image
    await page.goto('/admin/test/image-field?existing=true')
    
    // Click view button
    await page.locator('[data-testid="view-image"]').click()
    
    // Check that fullscreen modal is opened
    await expect(page.locator('[data-testid="fullscreen-modal"]')).toBeVisible()
    
    // Check that fullscreen image is displayed
    await expect(page.locator('[data-testid="fullscreen-image"]')).toBeVisible()
  })

  test('closes fullscreen modal when close button clicked', async ({ page }) => {
    // Navigate to page with existing image
    await page.goto('/admin/test/image-field?existing=true')
    
    // Open fullscreen
    await page.locator('[data-testid="view-image"]').click()
    await expect(page.locator('[data-testid="fullscreen-modal"]')).toBeVisible()
    
    // Close fullscreen
    await page.locator('[data-testid="close-fullscreen"]').click()
    
    // Check modal is closed
    await expect(page.locator('[data-testid="fullscreen-modal"]')).not.toBeVisible()
  })

  test('handles disabled state correctly', async ({ page }) => {
    // Navigate to page with disabled field
    await page.goto('/admin/test/image-field?disabled=true')
    
    // Check that upload area is disabled
    const uploadArea = page.locator('[data-testid="upload-area"]')
    await expect(uploadArea).toHaveClass(/opacity-50/)
    await expect(uploadArea).toHaveClass(/cursor-not-allowed/)
    
    // Check that file input is disabled
    const fileInput = page.locator('input[type="file"]')
    await expect(fileInput).toBeDisabled()
  })

  test('handles readonly state correctly', async ({ page }) => {
    // Navigate to page with readonly field
    await page.goto('/admin/test/image-field?readonly=true')
    
    // Check that upload area indicates readonly
    const uploadArea = page.locator('[data-testid="upload-area"]')
    await expect(uploadArea).toHaveClass(/cursor-not-allowed/)
    
    // File input should not be interactable
    const fileInput = page.locator('input[type="file"]')
    await expect(fileInput).toBeDisabled()
  })

  test('displays help text when provided', async ({ page }) => {
    // Navigate to page with help text
    await page.goto('/admin/test/image-field?helpText=Upload a high-quality image')
    
    // Check that help text is displayed
    await expect(page.locator('[data-testid="help-text"]')).toBeVisible()
    await expect(page.locator('[data-testid="help-text"]')).toContainText('Upload a high-quality image')
  })

  test('shows validation errors in real-time', async ({ page }) => {
    // Navigate to required field
    await page.goto('/admin/test/image-field?required=true')
    
    // Try to submit without uploading
    await page.locator('[data-testid="submit-button"]').click()
    
    // Check for required validation error
    await expect(page.locator('[data-testid="validation-error"]')).toBeVisible()
    await expect(page.locator('[data-testid="validation-error"]')).toContainText('required')
  })

  test('maintains image quality and dimensions', async ({ page }) => {
    // Upload a high-quality image
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/high-quality-image.jpg')
    
    // Wait for processing
    await page.waitForTimeout(2000)
    
    // Check that preview maintains quality
    const previewImage = page.locator('[data-testid="image-preview"] img')
    await expect(previewImage).toBeVisible()
    
    // Check image dimensions are reasonable
    const imageDimensions = await previewImage.evaluate((img) => ({
      width: img.naturalWidth,
      height: img.naturalHeight
    }))
    
    expect(imageDimensions.width).toBeGreaterThan(0)
    expect(imageDimensions.height).toBeGreaterThan(0)
  })

  test('handles multiple image uploads in sequence', async ({ page }) => {
    // Upload first image
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/test-image-1.jpg')
    await page.waitForTimeout(1000)
    
    // Check first image is displayed
    await expect(page.locator('[data-testid="image-preview"]')).toBeVisible()
    
    // Upload second image (replace first)
    await fileInput.setInputFiles('./tests/fixtures/test-image-2.jpg')
    await page.waitForTimeout(1000)
    
    // Check second image replaced the first
    await expect(page.locator('[data-testid="image-preview"]')).toBeVisible()
  })

  test('works correctly in dark theme', async ({ page }) => {
    // Enable dark theme
    await page.goto('/admin/test/image-field?theme=dark')
    
    // Check that dark theme classes are applied
    const uploadArea = page.locator('[data-testid="upload-area"]')
    await expect(uploadArea).toHaveClass(/dark:/)
    
    // Upload an image
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/test-image.jpg')
    
    // Wait for preview
    await page.waitForTimeout(1000)
    
    // Check that preview works in dark theme
    await expect(page.locator('[data-testid="image-preview"]')).toBeVisible()
  })
})
