import { test, expect } from '@playwright/test'

/**
 * Status Field Playwright E2E Tests
 *
 * Tests the complete end-to-end functionality of Status fields
 * in the browser environment, including visual rendering,
 * user interactions, and Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

test.describe('Status Field E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Set up test environment
    await page.goto('/admin-panel/test-status-field')
  })

  test('renders status field with basic configuration', async ({ page }) => {
    // Test basic status field rendering
    const statusField = page.locator('[data-testid="status-field"]')
    await expect(statusField).toBeVisible()

    // Test status element exists
    const status = statusField.locator('.inline-flex')
    await expect(status).toBeVisible()
    await expect(status).toHaveClass(/inline-flex/)
    await expect(status).toHaveClass(/items-center/)
    await expect(status).toHaveClass(/px-2\.5/)
    await expect(status).toHaveClass(/py-0\.5/)
    await expect(status).toHaveClass(/rounded-full/)
    await expect(status).toHaveClass(/text-xs/)
    await expect(status).toHaveClass(/font-medium/)
  })

  test('displays correct status types and colors', async ({ page }) => {
    // Test loading status
    const loadingStatus = page.locator('[data-testid="status-loading"]')
    await expect(loadingStatus).toBeVisible()
    await expect(loadingStatus).toHaveClass(/bg-yellow-100/)
    await expect(loadingStatus).toHaveClass(/text-yellow-800/)

    // Test failed status
    const failedStatus = page.locator('[data-testid="status-failed"]')
    await expect(failedStatus).toBeVisible()
    await expect(failedStatus).toHaveClass(/bg-red-100/)
    await expect(failedStatus).toHaveClass(/text-red-800/)

    // Test success status
    const successStatus = page.locator('[data-testid="status-success"]')
    await expect(successStatus).toBeVisible()
    await expect(successStatus).toHaveClass(/bg-green-100/)
    await expect(successStatus).toHaveClass(/text-green-800/)

    // Test default status
    const defaultStatus = page.locator('[data-testid="status-default"]')
    await expect(defaultStatus).toBeVisible()
    await expect(defaultStatus).toHaveClass(/bg-gray-100/)
    await expect(defaultStatus).toHaveClass(/text-gray-800/)
  })

  test('displays custom status types correctly', async ({ page }) => {
    // Test custom status with custom CSS classes
    const customStatus = page.locator('[data-testid="status-custom"]')
    await expect(customStatus).toBeVisible()
    await expect(customStatus).toHaveClass(/bg-blue-50/)
    await expect(customStatus).toHaveClass(/text-blue-700/)
    await expect(customStatus).toHaveClass(/ring-blue-600\/20/)
  })

  test('displays status labels correctly', async ({ page }) => {
    // Test default label (formatted value)
    const defaultLabel = page.locator('[data-testid="status-default-label"]')
    await expect(defaultLabel).toContainText('Waiting')

    // Test custom label mapping
    const customLabel = page.locator('[data-testid="status-custom-label"]')
    await expect(customLabel).toContainText('Waiting in Queue')

    // Test label callback
    const callbackLabel = page.locator('[data-testid="status-callback-label"]')
    await expect(callbackLabel).toContainText('PROCESSING STATUS')
  })

  test('displays icons when enabled', async ({ page }) => {
    // Test status without icon
    const statusWithoutIcon = page.locator('[data-testid="status-no-icon"]')
    const iconWithoutIcon = statusWithoutIcon.locator('span[aria-hidden="true"]')
    await expect(iconWithoutIcon).toHaveCount(0)

    // Test status with icon
    const statusWithIcon = page.locator('[data-testid="status-with-icon"]')
    await expect(statusWithIcon).toBeVisible()
    
    const icon = statusWithIcon.locator('span[aria-hidden="true"]').first()
    await expect(icon).toBeVisible()
    await expect(icon).toHaveClass(/w-3/)
    await expect(icon).toHaveClass(/h-3/)
    await expect(icon).toHaveClass(/mr-1\.5/)
  })

  test('displays loading animation for loading status', async ({ page }) => {
    // Test loading status with spinning animation
    const loadingStatus = page.locator('[data-testid="status-loading-animated"]')
    await expect(loadingStatus).toBeVisible()
    
    const icon = loadingStatus.locator('span[aria-hidden="true"]').first()
    await expect(icon).toBeVisible()
    await expect(icon).toHaveClass(/animate-spin/)
  })

  test('handles different value types correctly', async ({ page }) => {
    // Test boolean values
    const booleanTrue = page.locator('[data-testid="status-boolean-true"]')
    await expect(booleanTrue).toContainText('Active')
    await expect(booleanTrue).toHaveClass(/bg-green-100/)

    const booleanFalse = page.locator('[data-testid="status-boolean-false"]')
    await expect(booleanFalse).toContainText('Inactive')
    await expect(booleanFalse).toHaveClass(/bg-red-100/)

    // Test string values
    const stringValue = page.locator('[data-testid="status-string"]')
    await expect(stringValue).toContainText('Completed')
    await expect(stringValue).toHaveClass(/bg-green-100/)

    // Test numeric values
    const numericValue = page.locator('[data-testid="status-numeric"]')
    await expect(numericValue).toContainText('Status 1')
    await expect(numericValue).toHaveClass(/bg-yellow-100/)
  })

  test('handles null and empty values gracefully', async ({ page }) => {
    // Test null value
    const nullStatus = page.locator('[data-testid="status-null"]')
    await expect(nullStatus).toBeVisible()
    await expect(nullStatus).toContainText('')
    await expect(nullStatus).toHaveClass(/bg-gray-100/) // Default to default

    // Test empty string value
    const emptyStatus = page.locator('[data-testid="status-empty"]')
    await expect(emptyStatus).toBeVisible()
    await expect(emptyStatus).toContainText('')
  })

  test('updates reactively when value changes', async ({ page }) => {
    // Test reactive status that changes based on user interaction
    const reactiveStatus = page.locator('[data-testid="status-reactive"]')
    
    // Initial state
    await expect(reactiveStatus).toContainText('Waiting')
    await expect(reactiveStatus).toHaveClass(/bg-yellow-100/)

    // Trigger value change
    const changeButton = page.locator('[data-testid="change-status-button"]')
    await changeButton.click()

    // Verify status updated
    await expect(reactiveStatus).toContainText('Completed')
    await expect(reactiveStatus).toHaveClass(/bg-green-100/)

    // Change back
    await changeButton.click()
    await expect(reactiveStatus).toContainText('Waiting')
    await expect(reactiveStatus).toHaveClass(/bg-yellow-100/)
  })

  test('displays correctly in different contexts', async ({ page }) => {
    // Test status in index/list view
    const indexStatus = page.locator('[data-testid="status-index-view"]')
    await expect(indexStatus).toBeVisible()
    await expect(indexStatus).toHaveClass(/inline-flex/)

    // Test status in detail view
    const detailStatus = page.locator('[data-testid="status-detail-view"]')
    await expect(detailStatus).toBeVisible()
    await expect(detailStatus).toHaveClass(/inline-flex/)

    // Test status in form context (should still be readonly)
    const formStatus = page.locator('[data-testid="status-form-view"]')
    await expect(formStatus).toBeVisible()
    
    // Status fields should not be interactive in forms
    await expect(formStatus.locator('input')).toHaveCount(0)
    await expect(formStatus.locator('select')).toHaveCount(0)
    await expect(formStatus.locator('textarea')).toHaveCount(0)
  })

  test('handles complex Nova configuration correctly', async ({ page }) => {
    // Test status with full Nova configuration
    const complexStatus = page.locator('[data-testid="status-complex"]')
    await expect(complexStatus).toBeVisible()

    // Test custom classes are applied
    await expect(complexStatus).toHaveClass(/bg-indigo-50/)
    await expect(complexStatus).toHaveClass(/text-indigo-700/)
    await expect(complexStatus).toHaveClass(/ring-indigo-600\/20/)
    await expect(complexStatus).toHaveClass(/font-medium/)

    // Test custom label
    await expect(complexStatus).toContainText('Premium Processing')

    // Test icon is present
    const icon = complexStatus.locator('span[aria-hidden="true"]').first()
    await expect(icon).toBeVisible()
  })

  test('handles Nova loadingWhen configuration', async ({ page }) => {
    // Test multiple loading states
    const waitingStatus = page.locator('[data-testid="status-waiting"]')
    await expect(waitingStatus).toContainText('Waiting')
    await expect(waitingStatus).toHaveClass(/bg-yellow-100/)

    const runningStatus = page.locator('[data-testid="status-running"]')
    await expect(runningStatus).toContainText('Running')
    await expect(runningStatus).toHaveClass(/bg-yellow-100/)

    const processingStatus = page.locator('[data-testid="status-processing"]')
    await expect(processingStatus).toContainText('Processing')
    await expect(processingStatus).toHaveClass(/bg-yellow-100/)
  })

  test('handles Nova failedWhen configuration', async ({ page }) => {
    // Test multiple failed states
    const failedStatus = page.locator('[data-testid="status-failed"]')
    await expect(failedStatus).toContainText('Failed')
    await expect(failedStatus).toHaveClass(/bg-red-100/)

    const errorStatus = page.locator('[data-testid="status-error"]')
    await expect(errorStatus).toContainText('Error')
    await expect(errorStatus).toHaveClass(/bg-red-100/)

    const cancelledStatus = page.locator('[data-testid="status-cancelled"]')
    await expect(cancelledStatus).toContainText('Cancelled')
    await expect(cancelledStatus).toHaveClass(/bg-red-100/)
  })

  test('handles Nova successWhen configuration', async ({ page }) => {
    // Test multiple success states
    const completedStatus = page.locator('[data-testid="status-completed"]')
    await expect(completedStatus).toContainText('Completed')
    await expect(completedStatus).toHaveClass(/bg-green-100/)

    const finishedStatus = page.locator('[data-testid="status-finished"]')
    await expect(finishedStatus).toContainText('Finished')
    await expect(finishedStatus).toHaveClass(/bg-green-100/)

    const doneStatus = page.locator('[data-testid="status-done"]')
    await expect(doneStatus).toContainText('Done')
    await expect(doneStatus).toHaveClass(/bg-green-100/)
  })

  test('maintains accessibility standards', async ({ page }) => {
    const statusField = page.locator('[data-testid="status-field"]')
    
    // Test that status has proper ARIA attributes
    const status = statusField.locator('.inline-flex').first()
    
    // Status should be readable by screen readers
    await expect(status).toBeVisible()
    
    // Icon should have proper aria-hidden attribute
    const icon = status.locator('span[aria-hidden="true"]').first()
    if (await icon.count() > 0) {
      await expect(icon).toHaveAttribute('aria-hidden', 'true')
    }
  })

  test('handles dark mode correctly', async ({ page }) => {
    // Enable dark mode
    await page.locator('[data-testid="dark-mode-toggle"]').click()

    // Test that dark mode classes are applied
    const darkStatus = page.locator('[data-testid="status-dark-mode"]')
    await expect(darkStatus).toBeVisible()
    await expect(darkStatus).toHaveClass(/dark:bg-yellow-900/)
    await expect(darkStatus).toHaveClass(/dark:text-yellow-200/)
  })

  test('displays correctly on different screen sizes', async ({ page }) => {
    // Test desktop view
    await page.setViewportSize({ width: 1200, height: 800 })
    const responsiveStatus = page.locator('[data-testid="status-responsive"]')
    await expect(responsiveStatus).toBeVisible()

    // Test tablet view
    await page.setViewportSize({ width: 768, height: 1024 })
    await expect(responsiveStatus).toBeVisible()

    // Test mobile view
    await page.setViewportSize({ width: 375, height: 667 })
    await expect(responsiveStatus).toBeVisible()

    // Status should maintain its styling across all screen sizes
    await expect(responsiveStatus).toHaveClass(/inline-flex/)
    await expect(responsiveStatus).toHaveClass(/px-2\.5/)
    await expect(responsiveStatus).toHaveClass(/py-0\.5/)
  })

  test('integrates properly with BaseField wrapper', async ({ page }) => {
    const statusField = page.locator('[data-testid="status-field"]')
    
    // Test that BaseField wrapper is present
    const baseField = statusField.locator('[data-component="BaseField"]')
    await expect(baseField).toBeVisible()

    // Test field label is displayed
    const fieldLabel = statusField.locator('.field-label')
    await expect(fieldLabel).toContainText('Status')

    // Test help text is displayed if provided
    const helpText = statusField.locator('.field-help')
    if (await helpText.count() > 0) {
      await expect(helpText).toBeVisible()
    }
  })

  test('handles error states gracefully', async ({ page }) => {
    // Test status field with validation errors
    const errorStatus = page.locator('[data-testid="status-with-errors"]')
    await expect(errorStatus).toBeVisible()

    // Status should still display correctly even with errors
    const status = errorStatus.locator('.inline-flex')
    await expect(status).toBeVisible()
    await expect(status).toContainText('Invalid Status')

    // Error message should be displayed
    const errorMessage = errorStatus.locator('.field-error')
    if (await errorMessage.count() > 0) {
      await expect(errorMessage).toBeVisible()
      await expect(errorMessage).toContainText('Status is required')
    }
  })

  test('supports keyboard navigation', async ({ page }) => {
    // Status fields are display-only, so they shouldn't be focusable
    const statusField = page.locator('[data-testid="status-field"]')
    const status = statusField.locator('.inline-flex')
    
    // Status should not be focusable
    await status.focus()
    await expect(status).not.toBeFocused()

    // Tab navigation should skip over status fields
    await page.keyboard.press('Tab')
    await expect(status).not.toBeFocused()
  })

  test('performs well with many statuses', async ({ page }) => {
    // Test performance with multiple statuses
    const statusList = page.locator('[data-testid="status-list"]')
    await expect(statusList).toBeVisible()

    // Count statuses
    const statuses = statusList.locator('.inline-flex')
    const statusCount = await statuses.count()
    expect(statusCount).toBeGreaterThan(10)

    // All statuses should be visible
    for (let i = 0; i < Math.min(statusCount, 20); i++) {
      await expect(statuses.nth(i)).toBeVisible()
    }

    // Page should remain responsive
    const startTime = Date.now()
    await page.locator('[data-testid="performance-test-button"]').click()
    const endTime = Date.now()
    
    // Should complete within reasonable time (less than 1 second)
    expect(endTime - startTime).toBeLessThan(1000)
  })
})
