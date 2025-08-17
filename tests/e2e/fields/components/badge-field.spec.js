import { test, expect } from '@playwright/test'

/**
 * Badge Field Playwright E2E Tests
 *
 * Tests the complete end-to-end functionality of Badge fields
 * in the browser environment, including visual rendering,
 * user interactions, and Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

test.describe('Badge Field E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Set up test environment
    await page.goto('/admin-panel/test-badge-field')
  })

  test('renders badge field with basic configuration', async ({ page }) => {
    // Test basic badge field rendering
    const badgeField = page.locator('[data-testid="badge-field"]')
    await expect(badgeField).toBeVisible()

    // Test badge element exists
    const badge = badgeField.locator('.inline-flex')
    await expect(badge).toBeVisible()
    await expect(badge).toHaveClass(/inline-flex/)
    await expect(badge).toHaveClass(/items-center/)
    await expect(badge).toHaveClass(/px-2\.5/)
    await expect(badge).toHaveClass(/py-0\.5/)
    await expect(badge).toHaveClass(/rounded-full/)
    await expect(badge).toHaveClass(/text-xs/)
    await expect(badge).toHaveClass(/font-medium/)
  })

  test('displays correct badge types and colors', async ({ page }) => {
    // Test info badge (default)
    const infoBadge = page.locator('[data-testid="badge-info"]')
    await expect(infoBadge).toBeVisible()
    await expect(infoBadge).toHaveClass(/bg-blue-100/)
    await expect(infoBadge).toHaveClass(/text-blue-800/)

    // Test success badge
    const successBadge = page.locator('[data-testid="badge-success"]')
    await expect(successBadge).toBeVisible()
    await expect(successBadge).toHaveClass(/bg-green-100/)
    await expect(successBadge).toHaveClass(/text-green-800/)

    // Test danger badge
    const dangerBadge = page.locator('[data-testid="badge-danger"]')
    await expect(dangerBadge).toBeVisible()
    await expect(dangerBadge).toHaveClass(/bg-red-100/)
    await expect(dangerBadge).toHaveClass(/text-red-800/)

    // Test warning badge
    const warningBadge = page.locator('[data-testid="badge-warning"]')
    await expect(warningBadge).toBeVisible()
    await expect(warningBadge).toHaveClass(/bg-yellow-100/)
    await expect(warningBadge).toHaveClass(/text-yellow-800/)
  })

  test('displays custom badge types correctly', async ({ page }) => {
    // Test custom badge with custom CSS classes
    const customBadge = page.locator('[data-testid="badge-custom"]')
    await expect(customBadge).toBeVisible()
    await expect(customBadge).toHaveClass(/bg-purple-50/)
    await expect(customBadge).toHaveClass(/text-purple-700/)
    await expect(customBadge).toHaveClass(/ring-purple-600\/20/)
  })

  test('displays badge labels correctly', async ({ page }) => {
    // Test default label (value itself)
    const defaultLabel = page.locator('[data-testid="badge-default-label"]')
    await expect(defaultLabel).toContainText('draft')

    // Test custom label mapping
    const customLabel = page.locator('[data-testid="badge-custom-label"]')
    await expect(customLabel).toContainText('Draft Article')

    // Test label callback
    const callbackLabel = page.locator('[data-testid="badge-callback-label"]')
    await expect(callbackLabel).toContainText('PUBLISHED STATUS')
  })

  test('displays icons when enabled', async ({ page }) => {
    // Test badge without icon
    const badgeWithoutIcon = page.locator('[data-testid="badge-no-icon"]')
    const iconWithoutIcon = badgeWithoutIcon.locator('svg, .icon')
    await expect(iconWithoutIcon).toHaveCount(0)

    // Test badge with icon
    const badgeWithIcon = page.locator('[data-testid="badge-with-icon"]')
    await expect(badgeWithIcon).toBeVisible()
    
    const icon = badgeWithIcon.locator('svg, .icon').first()
    await expect(icon).toBeVisible()
    await expect(icon).toHaveClass(/w-3/)
    await expect(icon).toHaveClass(/h-3/)
    await expect(icon).toHaveClass(/mr-1\.5/)
  })

  test('handles different value types correctly', async ({ page }) => {
    // Test boolean values
    const booleanTrue = page.locator('[data-testid="badge-boolean-true"]')
    await expect(booleanTrue).toContainText('Active')
    await expect(booleanTrue).toHaveClass(/bg-green-100/)

    const booleanFalse = page.locator('[data-testid="badge-boolean-false"]')
    await expect(booleanFalse).toContainText('Inactive')
    await expect(booleanFalse).toHaveClass(/bg-red-100/)

    // Test string values
    const stringValue = page.locator('[data-testid="badge-string"]')
    await expect(stringValue).toContainText('Published')
    await expect(stringValue).toHaveClass(/bg-green-100/)

    // Test numeric values
    const numericValue = page.locator('[data-testid="badge-numeric"]')
    await expect(numericValue).toContainText('Priority 1')
    await expect(numericValue).toHaveClass(/bg-red-100/)
  })

  test('handles null and empty values gracefully', async ({ page }) => {
    // Test null value
    const nullBadge = page.locator('[data-testid="badge-null"]')
    await expect(nullBadge).toBeVisible()
    await expect(nullBadge).toContainText('')
    await expect(nullBadge).toHaveClass(/bg-blue-100/) // Default to info

    // Test empty string value
    const emptyBadge = page.locator('[data-testid="badge-empty"]')
    await expect(emptyBadge).toBeVisible()
    await expect(emptyBadge).toContainText('')
  })

  test('updates reactively when value changes', async ({ page }) => {
    // Test reactive badge that changes based on user interaction
    const reactiveBadge = page.locator('[data-testid="badge-reactive"]')
    
    // Initial state
    await expect(reactiveBadge).toContainText('Draft')
    await expect(reactiveBadge).toHaveClass(/bg-red-100/)

    // Trigger value change
    const changeButton = page.locator('[data-testid="change-status-button"]')
    await changeButton.click()

    // Verify badge updated
    await expect(reactiveBadge).toContainText('Published')
    await expect(reactiveBadge).toHaveClass(/bg-green-100/)

    // Change back
    await changeButton.click()
    await expect(reactiveBadge).toContainText('Draft')
    await expect(reactiveBadge).toHaveClass(/bg-red-100/)
  })

  test('displays correctly in different contexts', async ({ page }) => {
    // Test badge in index/list view
    const indexBadge = page.locator('[data-testid="badge-index-view"]')
    await expect(indexBadge).toBeVisible()
    await expect(indexBadge).toHaveClass(/inline-flex/)

    // Test badge in detail view
    const detailBadge = page.locator('[data-testid="badge-detail-view"]')
    await expect(detailBadge).toBeVisible()
    await expect(detailBadge).toHaveClass(/inline-flex/)

    // Test badge in form context (should still be readonly)
    const formBadge = page.locator('[data-testid="badge-form-view"]')
    await expect(formBadge).toBeVisible()
    
    // Badge fields should not be interactive in forms
    await expect(formBadge.locator('input')).toHaveCount(0)
    await expect(formBadge.locator('select')).toHaveCount(0)
    await expect(formBadge.locator('textarea')).toHaveCount(0)
  })

  test('handles complex Nova configuration correctly', async ({ page }) => {
    // Test badge with full Nova configuration
    const complexBadge = page.locator('[data-testid="badge-complex"]')
    await expect(complexBadge).toBeVisible()

    // Test custom classes are applied
    await expect(complexBadge).toHaveClass(/bg-indigo-50/)
    await expect(complexBadge).toHaveClass(/text-indigo-700/)
    await expect(complexBadge).toHaveClass(/ring-indigo-600\/20/)
    await expect(complexBadge).toHaveClass(/font-medium/)

    // Test custom label
    await expect(complexBadge).toContainText('Premium Account')

    // Test icon is present
    const icon = complexBadge.locator('svg, .icon').first()
    await expect(icon).toBeVisible()
  })

  test('maintains accessibility standards', async ({ page }) => {
    const badgeField = page.locator('[data-testid="badge-field"]')
    
    // Test that badge has proper ARIA attributes
    const badge = badgeField.locator('.inline-flex').first()
    
    // Badge should be readable by screen readers
    await expect(badge).toBeVisible()
    
    // Icon should have proper aria-hidden attribute
    const icon = badge.locator('svg, .icon').first()
    if (await icon.count() > 0) {
      await expect(icon).toHaveAttribute('aria-hidden', 'true')
    }
  })

  test('handles dark mode correctly', async ({ page }) => {
    // Enable dark mode
    await page.locator('[data-testid="dark-mode-toggle"]').click()

    // Test that dark mode classes are applied
    const darkBadge = page.locator('[data-testid="badge-dark-mode"]')
    await expect(darkBadge).toBeVisible()
    await expect(darkBadge).toHaveClass(/dark:bg-blue-900/)
    await expect(darkBadge).toHaveClass(/dark:text-blue-200/)
  })

  test('displays correctly on different screen sizes', async ({ page }) => {
    // Test desktop view
    await page.setViewportSize({ width: 1200, height: 800 })
    const desktopBadge = page.locator('[data-testid="badge-responsive"]')
    await expect(desktopBadge).toBeVisible()

    // Test tablet view
    await page.setViewportSize({ width: 768, height: 1024 })
    await expect(desktopBadge).toBeVisible()

    // Test mobile view
    await page.setViewportSize({ width: 375, height: 667 })
    await expect(desktopBadge).toBeVisible()

    // Badge should maintain its styling across all screen sizes
    await expect(desktopBadge).toHaveClass(/inline-flex/)
    await expect(desktopBadge).toHaveClass(/px-2\.5/)
    await expect(desktopBadge).toHaveClass(/py-0\.5/)
  })

  test('integrates properly with BaseField wrapper', async ({ page }) => {
    const badgeField = page.locator('[data-testid="badge-field"]')
    
    // Test that BaseField wrapper is present
    const baseField = badgeField.locator('[data-component="BaseField"]')
    await expect(baseField).toBeVisible()

    // Test field label is displayed
    const fieldLabel = badgeField.locator('.field-label')
    await expect(fieldLabel).toContainText('Status')

    // Test help text is displayed if provided
    const helpText = badgeField.locator('.field-help')
    if (await helpText.count() > 0) {
      await expect(helpText).toBeVisible()
    }
  })

  test('handles error states gracefully', async ({ page }) => {
    // Test badge field with validation errors
    const errorBadge = page.locator('[data-testid="badge-with-errors"]')
    await expect(errorBadge).toBeVisible()

    // Badge should still display correctly even with errors
    const badge = errorBadge.locator('.inline-flex')
    await expect(badge).toBeVisible()
    await expect(badge).toContainText('Invalid Status')

    // Error message should be displayed
    const errorMessage = errorBadge.locator('.field-error')
    if (await errorMessage.count() > 0) {
      await expect(errorMessage).toBeVisible()
      await expect(errorMessage).toContainText('Status is required')
    }
  })

  test('supports keyboard navigation', async ({ page }) => {
    // Badge fields are display-only, so they shouldn't be focusable
    const badgeField = page.locator('[data-testid="badge-field"]')
    const badge = badgeField.locator('.inline-flex')
    
    // Badge should not be focusable
    await badge.focus()
    await expect(badge).not.toBeFocused()

    // Tab navigation should skip over badge fields
    await page.keyboard.press('Tab')
    await expect(badge).not.toBeFocused()
  })

  test('performs well with many badges', async ({ page }) => {
    // Test performance with multiple badges
    const badgeList = page.locator('[data-testid="badge-list"]')
    await expect(badgeList).toBeVisible()

    // Count badges
    const badges = badgeList.locator('.inline-flex')
    const badgeCount = await badges.count()
    expect(badgeCount).toBeGreaterThan(10)

    // All badges should be visible
    for (let i = 0; i < Math.min(badgeCount, 20); i++) {
      await expect(badges.nth(i)).toBeVisible()
    }

    // Page should remain responsive
    const startTime = Date.now()
    await page.locator('[data-testid="performance-test-button"]').click()
    const endTime = Date.now()
    
    // Should complete within reasonable time (less than 1 second)
    expect(endTime - startTime).toBeLessThan(1000)
  })
})
