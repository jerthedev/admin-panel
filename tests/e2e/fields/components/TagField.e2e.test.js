import { test, expect } from '@playwright/test'

/**
 * TagField E2E Tests (Playwright)
 *
 * End-to-end tests for TagField component using Playwright.
 * Tests real browser interactions, user workflows, and
 * complete tag management scenarios.
 *
 * Note: These tests are written but not executed as part of the
 * current implementation. They would require a full Laravel/Inertia
 * application setup with the TagField component integrated.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

test.describe('TagField E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to a page with TagField component
    // This would be a real route in the admin panel
    await page.goto('/admin/blog-posts/create')
    
    // Wait for the page to load
    await page.waitForLoadState('networkidle')
  })

  test('should display tag field with correct initial state', async ({ page }) => {
    // Check that the tag field is present
    await expect(page.locator('[data-testid="tag-field"]')).toBeVisible()
    
    // Check initial tag count
    await expect(page.locator('[data-testid="tag-count"]')).toContainText('0 tags')
    
    // Check empty state is shown
    await expect(page.locator('[data-testid="empty-state"]')).toBeVisible()
    await expect(page.locator('[data-testid="empty-state"]')).toContainText('No tags selected')
    
    // Check add tags button is present
    await expect(page.locator('[data-testid="add-tags-button"]')).toBeVisible()
  })

  test('should open tag selector when add tags button is clicked', async ({ page }) => {
    // Click the add tags button
    await page.click('[data-testid="add-tags-button"]')
    
    // Check that tag selector appears
    await expect(page.locator('[data-testid="tag-selector"]')).toBeVisible()
    
    // Check that search input is visible
    await expect(page.locator('[data-testid="tag-search"]')).toBeVisible()
    
    // Check placeholder text
    await expect(page.locator('[data-testid="tag-search"] input')).toHaveAttribute('placeholder', 'Search tags...')
  })

  test('should search and filter available tags', async ({ page }) => {
    // Open tag selector
    await page.click('[data-testid="add-tags-button"]')
    
    // Type in search input
    await page.fill('[data-testid="tag-search"] input', 'PHP')
    
    // Wait for search results
    await page.waitForTimeout(500) // Wait for debounced search
    
    // Check that filtered results are shown
    const tagOptions = page.locator('[data-testid="tag-option"]')
    await expect(tagOptions).toHaveCount(2) // PHP and Laravel (contains PHP)
    
    // Check specific tags are present
    await expect(page.locator('[data-testid="tag-option"]').first()).toContainText('PHP')
    await expect(page.locator('[data-testid="tag-option"]').nth(1)).toContainText('Laravel')
  })

  test('should select and deselect tags', async ({ page }) => {
    // Open tag selector
    await page.click('[data-testid="add-tags-button"]')
    
    // Click on a tag to select it
    await page.click('[data-testid="tag-option"]', { hasText: 'PHP' })
    
    // Check that tag is marked as selected
    await expect(page.locator('[data-testid="tag-option"]', { hasText: 'PHP' })).toHaveClass(/selected/)
    
    // Check that checkmark is visible
    await expect(page.locator('[data-testid="tag-option"]', { hasText: 'PHP' }).locator('[data-testid="check-icon"]')).toBeVisible()
    
    // Click again to deselect
    await page.click('[data-testid="tag-option"]', { hasText: 'PHP' })
    
    // Check that tag is no longer selected
    await expect(page.locator('[data-testid="tag-option"]', { hasText: 'PHP' })).not.toHaveClass(/selected/)
  })

  test('should display selected tags in inline format by default', async ({ page }) => {
    // Select some tags
    await page.click('[data-testid="add-tags-button"]')
    await page.click('[data-testid="tag-option"]', { hasText: 'PHP' })
    await page.click('[data-testid="tag-option"]', { hasText: 'Laravel' })
    
    // Close tag selector by clicking outside or pressing escape
    await page.keyboard.press('Escape')
    
    // Check that tags are displayed inline
    await expect(page.locator('[data-testid="inline-tags"]')).toBeVisible()
    await expect(page.locator('[data-testid="list-tags"]')).not.toBeVisible()
    
    // Check tag count
    await expect(page.locator('[data-testid="tag-count"]')).toContainText('2 tags')
    
    // Check individual tags
    await expect(page.locator('[data-testid="inline-tag"]')).toHaveCount(2)
    await expect(page.locator('[data-testid="inline-tag"]').first()).toContainText('PHP')
    await expect(page.locator('[data-testid="inline-tag"]').nth(1)).toContainText('Laravel')
  })

  test('should display selected tags in list format when configured', async ({ page }) => {
    // This test assumes the field is configured with displayAsList: true
    // Navigate to a page with list-style tag field
    await page.goto('/admin/blog-posts/create?tagDisplayMode=list')
    
    // Select some tags
    await page.click('[data-testid="add-tags-button"]')
    await page.click('[data-testid="tag-option"]', { hasText: 'PHP' })
    await page.click('[data-testid="tag-option"]', { hasText: 'Laravel' })
    await page.keyboard.press('Escape')
    
    // Check that tags are displayed as list
    await expect(page.locator('[data-testid="list-tags"]')).toBeVisible()
    await expect(page.locator('[data-testid="inline-tags"]')).not.toBeVisible()
    
    // Check individual list items
    await expect(page.locator('[data-testid="list-tag"]')).toHaveCount(2)
  })

  test('should remove tags when remove button is clicked', async ({ page }) => {
    // Select some tags first
    await page.click('[data-testid="add-tags-button"]')
    await page.click('[data-testid="tag-option"]', { hasText: 'PHP' })
    await page.click('[data-testid="tag-option"]', { hasText: 'Laravel' })
    await page.keyboard.press('Escape')
    
    // Check initial count
    await expect(page.locator('[data-testid="tag-count"]')).toContainText('2 tags')
    
    // Click remove button on first tag
    await page.click('[data-testid="remove-tag-button"]')
    
    // Check that tag count decreased
    await expect(page.locator('[data-testid="tag-count"]')).toContainText('1 tag')
    
    // Check that only one tag remains
    await expect(page.locator('[data-testid="inline-tag"]')).toHaveCount(1)
  })

  test('should show create tag button when configured', async ({ page }) => {
    // Navigate to a page with create button enabled
    await page.goto('/admin/blog-posts/create?showCreateButton=true')
    
    // Check that create button is visible
    await expect(page.locator('[data-testid="create-tag-button"]')).toBeVisible()
    await expect(page.locator('[data-testid="create-tag-button"]')).toContainText('Create Tag')
  })

  test('should open create modal when create button is clicked', async ({ page }) => {
    // Navigate to a page with create button enabled
    await page.goto('/admin/blog-posts/create?showCreateButton=true')
    
    // Click create tag button
    await page.click('[data-testid="create-tag-button"]')
    
    // Check that create modal opens
    await expect(page.locator('[data-testid="create-modal"]')).toBeVisible()
    
    // Check modal title
    await expect(page.locator('[data-testid="create-modal"] h3')).toContainText('Create Tag')
  })

  test('should show preview modal when preview is enabled and tag is clicked', async ({ page }) => {
    // Navigate to a page with preview enabled
    await page.goto('/admin/blog-posts/create?withPreview=true')
    
    // Select a tag first
    await page.click('[data-testid="add-tags-button"]')
    await page.click('[data-testid="tag-option"]', { hasText: 'PHP' })
    await page.keyboard.press('Escape')
    
    // Click on the tag to preview
    await page.click('[data-testid="preview-tag"]')
    
    // Check that preview modal opens
    await expect(page.locator('[data-testid="preview-modal"]')).toBeVisible()
    
    // Check modal content
    await expect(page.locator('[data-testid="preview-modal"]')).toContainText('Tag Preview')
    await expect(page.locator('[data-testid="preview-modal"]')).toContainText('PHP')
  })

  test('should handle keyboard navigation', async ({ page }) => {
    // Open tag selector
    await page.click('[data-testid="add-tags-button"]')
    
    // Use keyboard to navigate
    await page.keyboard.press('ArrowDown')
    await page.keyboard.press('ArrowDown')
    await page.keyboard.press('Enter') // Select tag
    
    // Check that tag was selected
    await expect(page.locator('[data-testid="tag-option"]').nth(1)).toHaveClass(/selected/)
    
    // Use Escape to close selector
    await page.keyboard.press('Escape')
    
    // Check that selector is closed
    await expect(page.locator('[data-testid="tag-selector"]')).not.toBeVisible()
  })

  test('should persist tag selections when form is submitted', async ({ page }) => {
    // Fill out form with tags
    await page.fill('[data-testid="title-input"]', 'Test Blog Post')
    await page.fill('[data-testid="content-textarea"]', 'This is test content')
    
    // Add tags
    await page.click('[data-testid="add-tags-button"]')
    await page.click('[data-testid="tag-option"]', { hasText: 'PHP' })
    await page.click('[data-testid="tag-option"]', { hasText: 'Laravel' })
    await page.keyboard.press('Escape')
    
    // Submit form
    await page.click('[data-testid="submit-button"]')
    
    // Wait for navigation or success message
    await page.waitForURL('**/blog-posts/**')
    
    // Navigate back to edit page
    await page.click('[data-testid="edit-button"]')
    
    // Check that tags are still selected
    await expect(page.locator('[data-testid="tag-count"]')).toContainText('2 tags')
    await expect(page.locator('[data-testid="inline-tag"]')).toHaveCount(2)
  })

  test('should handle loading states gracefully', async ({ page }) => {
    // Intercept API calls to simulate slow responses
    await page.route('**/api/tags/search*', async route => {
      await new Promise(resolve => setTimeout(resolve, 2000)) // 2 second delay
      await route.continue()
    })
    
    // Open tag selector and search
    await page.click('[data-testid="add-tags-button"]')
    await page.fill('[data-testid="tag-search"] input', 'PHP')
    
    // Check that loading spinner is shown
    await expect(page.locator('[data-testid="loading-spinner"]')).toBeVisible()
    
    // Wait for loading to complete
    await expect(page.locator('[data-testid="loading-spinner"]')).not.toBeVisible({ timeout: 5000 })
    
    // Check that results are shown
    await expect(page.locator('[data-testid="tag-option"]')).toHaveCount(2)
  })

  test('should display error states appropriately', async ({ page }) => {
    // Intercept API calls to simulate errors
    await page.route('**/api/tags/search*', route => route.abort('failed'))
    
    // Try to search for tags
    await page.click('[data-testid="add-tags-button"]')
    await page.fill('[data-testid="tag-search"] input', 'PHP')
    
    // Check that error message is shown
    await expect(page.locator('[data-testid="error-message"]')).toBeVisible()
    await expect(page.locator('[data-testid="error-message"]')).toContainText('Failed to load tags')
  })

  test('should work correctly in readonly mode', async ({ page }) => {
    // Navigate to a readonly form
    await page.goto('/admin/blog-posts/1/view')
    
    // Check that add/remove buttons are not present
    await expect(page.locator('[data-testid="add-tags-button"]')).not.toBeVisible()
    await expect(page.locator('[data-testid="remove-tag-button"]')).not.toBeVisible()
    
    // Check that tags are still displayed
    await expect(page.locator('[data-testid="inline-tags"]')).toBeVisible()
    
    // Check that tags are not interactive
    const tagElements = page.locator('[data-testid="inline-tag"]')
    await expect(tagElements.first()).not.toHaveClass(/cursor-pointer/)
  })
})
