import { test, expect } from '@playwright/test'
import path from 'path'

test.describe('MediaLibraryFileField E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to a test page with MediaLibraryFileField
    await page.goto('/admin/test/media-library-file-field')
    await page.waitForLoadState('networkidle')
  })

  test('renders MediaLibraryFileField with basic configuration', async ({ page }) => {
    // Check that the field renders with expected elements
    await expect(page.locator('[data-testid="media-library-file-field"]')).toBeVisible()
    await expect(page.locator('[data-testid="cloud-arrow-up-icon"]')).toBeVisible()
    await expect(page.getByText('Click to upload')).toBeVisible()
    await expect(page.getByText('or drag and drop')).toBeVisible()
  })

  test('displays accepted file types and size limits', async ({ page }) => {
    // Check that file type restrictions are displayed
    await expect(page.getByText(/PDF, DOC, DOCX files/)).toBeVisible()
    await expect(page.getByText(/Max \d+(\.\d+)?\s*(KB|MB|GB)/)).toBeVisible()
  })

  test('handles single file upload via file input', async ({ page }) => {
    // Create a test file
    const testFilePath = path.join(__dirname, '../../../fixtures/test-document.pdf')
    
    // Upload file via file input
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles(testFilePath)
    
    // Wait for upload to process
    await page.waitForTimeout(1000)
    
    // Check that file appears in the list
    await expect(page.getByText('test-document.pdf')).toBeVisible()
    await expect(page.locator('[data-testid="document-icon"]')).toBeVisible()
  })

  test('handles multiple file upload', async ({ page }) => {
    // Navigate to multiple file field
    await page.goto('/admin/test/media-library-file-field?multiple=true')
    await page.waitForLoadState('networkidle')
    
    const testFiles = [
      path.join(__dirname, '../../../fixtures/test-document.pdf'),
      path.join(__dirname, '../../../fixtures/test-document.txt')
    ]
    
    // Upload multiple files
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles(testFiles)
    
    // Wait for uploads to process
    await page.waitForTimeout(2000)
    
    // Check that both files appear
    await expect(page.getByText('test-document.pdf')).toBeVisible()
    await expect(page.getByText('test-document.txt')).toBeVisible()
  })

  test('handles drag and drop file upload', async ({ page }) => {
    const uploadArea = page.locator('.upload-area')
    
    // Create a test file for drag and drop
    const testFilePath = path.join(__dirname, '../../../fixtures/test-document.pdf')
    
    // Simulate drag and drop
    await uploadArea.hover()
    
    // Use the file input as a fallback since true drag-and-drop is complex in Playwright
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles(testFilePath)
    
    await page.waitForTimeout(1000)
    
    // Verify file was uploaded
    await expect(page.getByText('test-document.pdf')).toBeVisible()
  })

  test('shows upload progress during file upload', async ({ page }) => {
    // Mock slow upload to see progress
    await page.route('**/api/upload', async route => {
      await new Promise(resolve => setTimeout(resolve, 2000))
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ success: true, file: { name: 'test.pdf' } })
      })
    })
    
    const testFilePath = path.join(__dirname, '../../../fixtures/test-document.pdf')
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles(testFilePath)
    
    // Check for progress indicator
    await expect(page.getByText('Uploading...')).toBeVisible()
    await expect(page.locator('.bg-blue-600')).toBeVisible() // Progress bar
    
    // Wait for upload to complete
    await page.waitForTimeout(3000)
  })

  test('validates file size restrictions', async ({ page }) => {
    // Navigate to field with small size limit
    await page.goto('/admin/test/media-library-file-field?maxSize=1') // 1KB limit
    await page.waitForLoadState('networkidle')
    
    // Try to upload a larger file
    const testFilePath = path.join(__dirname, '../../../fixtures/large-document.pdf')
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles(testFilePath)
    
    // Check for validation error
    await expect(page.getByText(/File size exceeds maximum allowed size/)).toBeVisible()
    await expect(page.locator('[data-testid="exclamation-circle-icon"]')).toBeVisible()
  })

  test('validates file type restrictions', async ({ page }) => {
    // Navigate to field with PDF-only restriction
    await page.goto('/admin/test/media-library-file-field?acceptedTypes=.pdf')
    await page.waitForLoadState('networkidle')
    
    // Try to upload a non-PDF file
    const testFilePath = path.join(__dirname, '../../../fixtures/test-image.jpg')
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles(testFilePath)
    
    // Check for validation error
    await expect(page.getByText(/File type not allowed/)).toBeVisible()
    await expect(page.locator('[data-testid="exclamation-circle-icon"]')).toBeVisible()
  })

  test('displays existing files with metadata', async ({ page }) => {
    // Navigate to field with existing files
    await page.goto('/admin/test/media-library-file-field?hasExisting=true')
    await page.waitForLoadState('networkidle')
    
    // Check that existing files are displayed
    await expect(page.getByText('existing-document.pdf')).toBeVisible()
    await expect(page.getByText(/\d+(\.\d+)?\s*(B|KB|MB|GB)/)).toBeVisible() // File size
    await expect(page.getByText('application/pdf')).toBeVisible() // MIME type
    await expect(page.locator('[data-testid="document-icon"]')).toBeVisible()
  })

  test('handles file removal', async ({ page }) => {
    // Navigate to field with existing files
    await page.goto('/admin/test/media-library-file-field?hasExisting=true')
    await page.waitForLoadState('networkidle')
    
    // Verify file exists
    await expect(page.getByText('existing-document.pdf')).toBeVisible()
    
    // Click remove button
    const removeButton = page.locator('[data-testid="x-mark-icon"]').first()
    await removeButton.click()
    
    // Verify file is removed
    await expect(page.getByText('existing-document.pdf')).not.toBeVisible()
  })

  test('handles file download', async ({ page }) => {
    // Navigate to field with existing files
    await page.goto('/admin/test/media-library-file-field?hasExisting=true')
    await page.waitForLoadState('networkidle')
    
    // Set up download handling
    const downloadPromise = page.waitForEvent('download')
    
    // Click download button
    const downloadButton = page.locator('[data-testid="arrow-down-tray-icon"]').first()
    await downloadButton.click()
    
    // Verify download started
    const download = await downloadPromise
    expect(download.suggestedFilename()).toBeTruthy()
  })

  test('respects readonly mode', async ({ page }) => {
    // Navigate to readonly field
    await page.goto('/admin/test/media-library-file-field?readonly=true')
    await page.waitForLoadState('networkidle')
    
    // Check that upload area is not present
    await expect(page.locator('.upload-area')).not.toBeVisible()
    
    // Check that file input is disabled
    const fileInput = page.locator('input[type="file"]')
    await expect(fileInput).toBeDisabled()
    
    // Check that remove buttons are not present
    await expect(page.locator('[data-testid="x-mark-icon"]')).not.toBeVisible()
  })

  test('respects disabled mode', async ({ page }) => {
    // Navigate to disabled field
    await page.goto('/admin/test/media-library-file-field?disabled=true')
    await page.waitForLoadState('networkidle')
    
    // Check that upload area has disabled styling
    await expect(page.locator('.upload-area-disabled')).toBeVisible()
    
    // Check that file input is disabled
    const fileInput = page.locator('input[type="file"]')
    await expect(fileInput).toBeDisabled()
  })

  test('handles dark theme correctly', async ({ page }) => {
    // Enable dark theme
    await page.goto('/admin/test/media-library-file-field?theme=dark')
    await page.waitForLoadState('networkidle')
    
    // Check for dark theme classes
    await expect(page.locator('.upload-area-dark')).toBeVisible()
    
    // Check that text colors are appropriate for dark theme
    const uploadText = page.getByText('Click to upload')
    await expect(uploadText).toHaveClass(/text-gray-300/)
  })

  test('handles single file mode correctly', async ({ page }) => {
    // Navigate to single file field
    await page.goto('/admin/test/media-library-file-field?singleFile=true')
    await page.waitForLoadState('networkidle')
    
    // Upload first file
    const testFilePath1 = path.join(__dirname, '../../../fixtures/test-document.pdf')
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles(testFilePath1)
    
    await page.waitForTimeout(1000)
    await expect(page.getByText('test-document.pdf')).toBeVisible()
    
    // Check that upload area is hidden after single file upload
    await expect(page.locator('.upload-area')).not.toBeVisible()
    
    // Upload second file (should replace first)
    await page.locator('[data-testid="x-mark-icon"]').click() // Remove first file
    await page.waitForTimeout(500)
    
    const testFilePath2 = path.join(__dirname, '../../../fixtures/test-document.txt')
    await fileInput.setInputFiles(testFilePath2)
    
    await page.waitForTimeout(1000)
    await expect(page.getByText('test-document.txt')).toBeVisible()
    await expect(page.getByText('test-document.pdf')).not.toBeVisible()
  })

  test('displays file icons for different file types', async ({ page }) => {
    // Navigate to field with various file types
    await page.goto('/admin/test/media-library-file-field?hasVariousTypes=true')
    await page.waitForLoadState('networkidle')
    
    // Check that document icons are displayed for different file types
    await expect(page.locator('[data-testid="document-icon"]')).toHaveCount(3) // PDF, DOC, TXT
    
    // Check that file names and types are displayed
    await expect(page.getByText('document.pdf')).toBeVisible()
    await expect(page.getByText('document.doc')).toBeVisible()
    await expect(page.getByText('document.txt')).toBeVisible()
    
    await expect(page.getByText('application/pdf')).toBeVisible()
    await expect(page.getByText('application/msword')).toBeVisible()
    await expect(page.getByText('text/plain')).toBeVisible()
  })

  test('handles error states gracefully', async ({ page }) => {
    // Mock upload error
    await page.route('**/api/upload', async route => {
      await route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({ error: 'Upload failed' })
      })
    })
    
    const testFilePath = path.join(__dirname, '../../../fixtures/test-document.pdf')
    const fileInput = page.locator('input[type="file"]')
    await fileInput.setInputFiles(testFilePath)
    
    // Check for error message
    await expect(page.getByText(/Upload failed/)).toBeVisible()
    await expect(page.locator('[data-testid="exclamation-circle-icon"]')).toBeVisible()
  })
})
