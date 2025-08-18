import { test, expect } from '@playwright/test'

/**
 * MediaLibraryImageField E2E Tests
 * 
 * Tests the complete end-to-end functionality of MediaLibraryImageField
 * in a real browser environment using Playwright.
 * 
 * Focuses on Nova-compatible Image field functionality with Media Library features:
 * - Extends MediaLibraryField with Nova Image field compatibility
 * - Supports all Nova Image field methods: disableDownload(), maxWidth(), etc.
 * - Integrates with Spatie Media Library for advanced image handling
 * - Supports conversions, collections, and responsive images
 */

test.describe('MediaLibraryImageField E2E', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the admin panel test page for MediaLibraryImageField
    await page.goto('/admin/test/media-library-image-field')
  })

  test('displays media library image field with Nova-compatible structure', async ({ page }) => {
    // Check that the media library image field is rendered
    await expect(page.locator('[data-testid="media-library-image-field"]')).toBeVisible()
    
    // Check for field label
    await expect(page.locator('label')).toContainText('Gallery Images')
    
    // Check for upload area
    await expect(page.locator('.upload-area')).toBeVisible()
    
    // Check for upload instructions
    await expect(page.locator('.upload-area')).toContainText('Click to upload images')
    await expect(page.locator('.upload-area')).toContainText('or drag and drop')
  })

  test('handles multiple image file upload workflow', async ({ page }) => {
    // Locate the file input
    const fileInput = page.locator('input[type="file"]')
    await expect(fileInput).toBeAttached()

    // Create test image files
    const testImagePaths = [
      './tests/fixtures/test-image1.jpg',
      './tests/fixtures/test-image2.png'
    ]
    
    // Upload multiple files
    await fileInput.setInputFiles(testImagePaths)
    
    // Wait for upload to process
    await page.waitForTimeout(2000)
    
    // Check that multiple previews are displayed
    const imageElements = page.locator('img')
    await expect(imageElements).toHaveCount(2)
    
    // Check that images have blob URLs (client-side preview)
    const firstImage = imageElements.first()
    const secondImage = imageElements.nth(1)
    await expect(firstImage).toHaveAttribute('src', /blob:|data:/)
    await expect(secondImage).toHaveAttribute('src', /blob:|data:/)
  })

  test('displays existing images when modelValue is provided', async ({ page }) => {
    // Navigate to page with existing images
    await page.goto('/admin/test/media-library-image-field?existing=true')
    
    // Check that existing images are displayed
    await expect(page.locator('.image-gallery')).toBeVisible()
    
    // Check that images are displayed in grid
    const existingImages = page.locator('.image-gallery img')
    await expect(existingImages).toHaveCount(2)
    
    // Check that images have correct src attributes
    await expect(existingImages.first()).toHaveAttribute('src', /\/storage\//)
    await expect(existingImages.nth(1)).toHaveAttribute('src', /\/storage\//)
  })

  test('applies Nova Image Field styling options', async ({ page }) => {
    // Test squared styling
    await page.goto('/admin/test/media-library-image-field?squared=true')
    
    // Upload an image
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/test-image1.jpg')
    
    // Wait for preview
    await page.waitForTimeout(1000)
    
    // Check that squared class is applied
    const previewImage = page.locator('img').first()
    await expect(previewImage).toHaveClass(/rounded-none/)
  })

  test('applies rounded styling when configured', async ({ page }) => {
    // Navigate to page with rounded image field
    await page.goto('/admin/test/media-library-image-field?rounded=true')
    
    // Upload an image
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/test-image1.jpg')
    
    // Wait for preview
    await page.waitForTimeout(1000)
    
    // Check that rounded class is applied
    const previewImage = page.locator('img').first()
    await expect(previewImage).toHaveClass(/rounded-full/)
  })

  test('applies maxWidth styling when configured', async ({ page }) => {
    // Navigate to page with maxWidth configuration
    await page.goto('/admin/test/media-library-image-field?maxWidth=300')
    
    // Upload an image
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/test-image1.jpg')
    
    // Wait for preview
    await page.waitForTimeout(1000)
    
    // Check that maxWidth style is applied
    const previewImage = page.locator('img').first()
    const maxWidth = await previewImage.evaluate(el => el.style.maxWidth)
    expect(maxWidth).toBe('300px')
  })

  test('shows download button when downloads are enabled', async ({ page }) => {
    // Navigate to page with existing images and downloads enabled
    await page.goto('/admin/test/media-library-image-field?existing=true&downloadDisabled=false')
    
    // Check that download buttons are visible
    const downloadButtons = page.locator('[data-testid="arrow-down-tray-icon"]')
    await expect(downloadButtons).toHaveCount(2)
    
    // Test download functionality
    const firstDownloadButton = downloadButtons.first()
    await expect(firstDownloadButton).toBeVisible()
  })

  test('hides download button when downloads are disabled', async ({ page }) => {
    // Navigate to page with existing images and downloads disabled
    await page.goto('/admin/test/media-library-image-field?existing=true&downloadDisabled=true')
    
    // Check that download buttons are not visible
    const downloadButtons = page.locator('[data-testid="arrow-down-tray-icon"]')
    await expect(downloadButtons).toHaveCount(0)
  })

  test('validates file size limits', async ({ page }) => {
    // Navigate to page with size limit
    await page.goto('/admin/test/media-library-image-field?maxFileSize=1024')
    
    // Try to upload a large file
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/large-image.jpg')
    
    // Check for validation error
    await expect(page.locator('.bg-red-50')).toBeVisible()
    await expect(page.locator('[data-testid="exclamation-circle-icon"]')).toBeVisible()
    await expect(page.getByText('Image size exceeds maximum allowed size')).toBeVisible()
  })

  test('validates accepted MIME types', async ({ page }) => {
    // Navigate to page with MIME type restrictions
    await page.goto('/admin/test/media-library-image-field?acceptedMimeTypes=image/jpeg,image/png')
    
    // Try to upload an invalid file type
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/test-document.pdf')
    
    // Check for validation error
    await expect(page.locator('.bg-red-50')).toBeVisible()
    await expect(page.getByText('Please select valid image files')).toBeVisible()
  })

  test('enforces image limit', async ({ page }) => {
    // Navigate to page with image limit
    await page.goto('/admin/test/media-library-image-field?limit=2')
    
    // Upload one image first
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles('./tests/fixtures/test-image1.jpg')
    await page.waitForTimeout(1000)
    
    // Try to upload two more images (exceeding limit)
    await fileInput.setInputFiles([
      './tests/fixtures/test-image2.png',
      './tests/fixtures/test-image3.jpg'
    ])
    
    // Check for limit error
    await expect(page.getByText('Cannot upload more than 2 images')).toBeVisible()
  })

  test('supports drag and drop upload', async ({ page }) => {
    // Get the upload area
    const uploadArea = page.locator('.upload-area')
    await expect(uploadArea).toBeVisible()
    
    // Simulate dragover event
    await uploadArea.dispatchEvent('dragover', {
      dataTransfer: {
        files: []
      }
    })
    
    // Check that dragover styling is applied
    await expect(uploadArea).toHaveClass(/upload-area-dragover/)
    
    // Simulate dragleave event
    await uploadArea.dispatchEvent('dragleave')
    
    // Check that dragover styling is removed
    await expect(uploadArea).not.toHaveClass(/upload-area-dragover/)
  })

  test('opens lightbox when image is clicked', async ({ page }) => {
    // Navigate to page with existing images
    await page.goto('/admin/test/media-library-image-field?existing=true')
    
    // Click on the first image
    const firstImage = page.locator('.image-gallery img').first()
    await firstImage.click()
    
    // Check that lightbox is opened
    await expect(page.locator('.fixed.inset-0')).toBeVisible()
    
    // Check that lightbox contains the image
    const lightboxImage = page.locator('.fixed.inset-0 img')
    await expect(lightboxImage).toBeVisible()
    
    // Check for navigation arrows (if multiple images)
    await expect(page.locator('[data-testid="chevron-right-icon"]')).toBeVisible()
  })

  test('navigates between images in lightbox', async ({ page }) => {
    // Navigate to page with multiple existing images
    await page.goto('/admin/test/media-library-image-field?existing=true&count=3')
    
    // Open lightbox
    const firstImage = page.locator('.image-gallery img').first()
    await firstImage.click()
    
    // Navigate to next image
    const nextButton = page.locator('[data-testid="chevron-right-icon"]').locator('..')
    await nextButton.click()
    
    // Check that we're on the second image
    // (This would require checking the image src or other identifier)
    
    // Navigate to previous image
    const prevButton = page.locator('[data-testid="chevron-left-icon"]').locator('..')
    await prevButton.click()
    
    // Check that we're back to the first image
  })

  test('closes lightbox when close button is clicked', async ({ page }) => {
    // Navigate to page with existing images
    await page.goto('/admin/test/media-library-image-field?existing=true')
    
    // Open lightbox
    const firstImage = page.locator('.image-gallery img').first()
    await firstImage.click()
    
    // Close lightbox
    const closeButton = page.locator('.fixed.inset-0 [data-testid="x-mark-icon"]').locator('..')
    await closeButton.click()
    
    // Check that lightbox is closed
    await expect(page.locator('.fixed.inset-0')).not.toBeVisible()
  })

  test('removes image when remove button is clicked', async ({ page }) => {
    // Navigate to page with existing images
    await page.goto('/admin/test/media-library-image-field?existing=true&count=2')
    
    // Count initial images
    const initialImages = page.locator('.image-gallery img')
    const initialCount = await initialImages.count()
    
    // Click remove button on first image
    const removeButton = page.locator('[data-testid="x-mark-icon"]').first().locator('..')
    await removeButton.click()
    
    // Check that image count decreased
    const remainingImages = page.locator('.image-gallery img')
    const remainingCount = await remainingImages.count()
    expect(remainingCount).toBe(initialCount - 1)
  })

  test('shows image dimensions when enabled', async ({ page }) => {
    // Navigate to page with image dimensions enabled
    await page.goto('/admin/test/media-library-image-field?existing=true&showImageDimensions=true')
    
    // Check that dimensions are displayed
    await expect(page.getByText(/\d+ × \d+/)).toBeVisible()
  })

  test('displays image count when limit is set', async ({ page }) => {
    // Navigate to page with limit and existing images
    await page.goto('/admin/test/media-library-image-field?existing=true&count=2&limit=5')
    
    // Check that count is displayed
    await expect(page.getByText('2 of 5 images')).toBeVisible()
  })

  test('hides upload area when limit is reached', async ({ page }) => {
    // Navigate to page where limit equals existing images
    await page.goto('/admin/test/media-library-image-field?existing=true&count=3&limit=3')
    
    // Check that upload area is not visible
    await expect(page.locator('.upload-area')).not.toBeVisible()
  })

  test('supports reordering images via drag handles', async ({ page }) => {
    // Navigate to page with multiple existing images
    await page.goto('/admin/test/media-library-image-field?existing=true&count=3')
    
    // Check that drag handles are visible
    const dragHandles = page.locator('[data-testid="bars3-icon"]')
    await expect(dragHandles).toHaveCount(3)
    
    // Test drag functionality (simplified - real drag would be more complex)
    const firstDragHandle = dragHandles.first()
    await expect(firstDragHandle).toBeVisible()
  })
})
