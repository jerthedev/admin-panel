import { test, expect } from '@playwright/test'

/**
 * Playwright E2E tests for DateTime field component
 * 
 * Note: These tests are designed to run against a live application
 * with the DateTime field properly integrated. They test the complete
 * user interaction flow in a real browser environment.
 */

test.describe('DateTime Field E2E', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to a page with DateTime field
    // This would typically be a form page in your admin panel
    await page.goto('/admin/test-form-with-datetime')
  })

  test('should display datetime field with proper attributes', async ({ page }) => {
    // Locate the datetime field
    const datetimeField = page.locator('[data-testid="datetime-field-event-time"]')
    await expect(datetimeField).toBeVisible()

    // Check input type and attributes
    const input = datetimeField.locator('input[type="datetime-local"]')
    await expect(input).toBeVisible()
    await expect(input).toHaveAttribute('step', '900') // 15 minutes in seconds
    await expect(input).toHaveAttribute('min', '2020-01-01T00:00')
    await expect(input).toHaveAttribute('max', '2030-12-31T23:59')

    // Check for clock icon
    const clockIcon = datetimeField.locator('[data-testid="clock-icon"]')
    await expect(clockIcon).toBeVisible()
  })

  test('should handle user input correctly', async ({ page }) => {
    const input = page.locator('[data-testid="datetime-field-event-time"] input')
    
    // Clear and type a datetime value
    await input.clear()
    await input.fill('2023-06-15T14:30')
    
    // Verify the value is set
    await expect(input).toHaveValue('2023-06-15T14:30')
    
    // Trigger blur to ensure any formatting
    await input.blur()
    
    // Value should remain as entered
    await expect(input).toHaveValue('2023-06-15T14:30')
  })

  test('should validate min/max datetime constraints', async ({ page }) => {
    const input = page.locator('[data-testid="datetime-field-event-time"] input')
    
    // Test minimum datetime validation
    await input.clear()
    await input.fill('2019-12-31T23:59')
    await input.blur()
    
    // Should show validation error or prevent invalid value
    const errorMessage = page.locator('[data-testid="field-error"]')
    await expect(errorMessage).toBeVisible()
    
    // Test maximum datetime validation
    await input.clear()
    await input.fill('2031-01-01T00:00')
    await input.blur()
    
    await expect(errorMessage).toBeVisible()
  })

  test('should handle timezone display', async ({ page }) => {
    // Navigate to form with timezone-aware datetime field
    await page.goto('/admin/test-form-with-timezone-datetime')
    
    const datetimeField = page.locator('[data-testid="datetime-field-meeting-time"]')
    await expect(datetimeField).toBeVisible()
    
    // Should show timezone information
    const timezoneDisplay = datetimeField.locator('[data-testid="timezone-display"]')
    await expect(timezoneDisplay).toBeVisible()
    await expect(timezoneDisplay).toContainText('America/New_York')
  })

  test('should handle step intervals correctly', async ({ page }) => {
    // Test 15-minute intervals
    const input15 = page.locator('[data-testid="datetime-field-15min"] input')
    await expect(input15).toHaveAttribute('step', '900') // 15 * 60
    
    // Test 30-minute intervals
    const input30 = page.locator('[data-testid="datetime-field-30min"] input')
    await expect(input30).toHaveAttribute('step', '1800') // 30 * 60
    
    // Test 1-hour intervals
    const input60 = page.locator('[data-testid="datetime-field-60min"] input')
    await expect(input60).toHaveAttribute('step', '3600') // 60 * 60
  })

  test('should support keyboard navigation', async ({ page }) => {
    const input = page.locator('[data-testid="datetime-field-event-time"] input')
    
    // Focus the field
    await input.focus()
    await expect(input).toBeFocused()
    
    // Use arrow keys to navigate date/time components
    await input.press('ArrowUp')
    await input.press('ArrowDown')
    
    // Use Tab to navigate away
    await input.press('Tab')
    await expect(input).not.toBeFocused()
  })

  test('should handle copy/paste operations', async ({ page }) => {
    const input = page.locator('[data-testid="datetime-field-event-time"] input')
    
    // Type a datetime value
    await input.clear()
    await input.fill('2023-07-20T16:45')
    
    // Select all and copy
    await input.selectText()
    await page.keyboard.press('Control+c')
    
    // Clear and paste
    await input.clear()
    await page.keyboard.press('Control+v')
    
    // Verify pasted value
    await expect(input).toHaveValue('2023-07-20T16:45')
  })

  test('should work with form submission', async ({ page }) => {
    const input = page.locator('[data-testid="datetime-field-event-time"] input')
    
    // Fill the datetime field
    await input.clear()
    await input.fill('2023-12-25T10:00')
    
    // Fill other required fields if any
    const nameField = page.locator('[data-testid="text-field-name"] input')
    if (await nameField.isVisible()) {
      await nameField.fill('Test Event')
    }
    
    // Submit the form
    await page.click('[data-testid="submit-button"]')
    
    // Wait for submission to complete
    await page.waitForLoadState('networkidle')
    
    // Verify success (could be redirect, success message, etc.)
    const currentUrl = page.url()
    expect(currentUrl).toContain('/admin/')
  })

  test('should display formatted values in readonly mode', async ({ page }) => {
    // Navigate to a detail/show page with datetime values
    await page.goto('/admin/events/1')
    
    const datetimeDisplay = page.locator('[data-testid="datetime-display-event-time"]')
    await expect(datetimeDisplay).toBeVisible()
    
    // Should show formatted datetime
    const displayText = await datetimeDisplay.textContent()
    expect(displayText).toMatch(/\w+ \d{1,2}, \d{4} \d{1,2}:\d{2}/) // e.g., "Jun 15, 2023 14:30"
  })

  test('should handle relative time display', async ({ page }) => {
    // Navigate to page with relative time enabled
    await page.goto('/admin/events/recent')
    
    const relativeTimeDisplay = page.locator('[data-testid="relative-time-display"]')
    await expect(relativeTimeDisplay).toBeVisible()
    
    // Should show relative time (e.g., "2 hours ago", "in 3 days")
    const relativeText = await relativeTimeDisplay.textContent()
    expect(relativeText).toMatch(/(ago|in \d+|just now|yesterday|tomorrow)/)
  })

  test('should handle dark mode correctly', async ({ page }) => {
    // Toggle dark mode
    await page.click('[data-testid="dark-mode-toggle"]')
    
    const datetimeField = page.locator('[data-testid="datetime-field-event-time"]')
    const input = datetimeField.locator('input')
    
    // Verify dark mode classes are applied
    await expect(input).toHaveClass(/admin-input-dark/)
    
    // Clock icon should also have dark mode styling
    const clockIcon = datetimeField.locator('[data-testid="clock-icon"]')
    await expect(clockIcon).toHaveClass(/text-gray-400/)
  })

  test('should be accessible', async ({ page }) => {
    const input = page.locator('[data-testid="datetime-field-event-time"] input')
    
    // Check for proper attributes
    await expect(input).toHaveAttribute('type', 'datetime-local')
    await expect(input).toHaveAttribute('id')
    
    // Should be focusable
    await input.focus()
    await expect(input).toBeFocused()
    
    // Should work with screen reader labels
    const label = page.locator('label[for]')
    if (await label.isVisible()) {
      const forAttr = await label.getAttribute('for')
      const inputId = await input.getAttribute('id')
      expect(forAttr).toBe(inputId)
    }
  })

  test('should handle edge cases', async ({ page }) => {
    const input = page.locator('[data-testid="datetime-field-event-time"] input')
    
    // Test leap year date
    await input.clear()
    await input.fill('2024-02-29T12:00')
    await expect(input).toHaveValue('2024-02-29T12:00')
    
    // Test midnight
    await input.clear()
    await input.fill('2023-06-15T00:00')
    await expect(input).toHaveValue('2023-06-15T00:00')
    
    // Test end of day
    await input.clear()
    await input.fill('2023-06-15T23:59')
    await expect(input).toHaveValue('2023-06-15T23:59')
    
    // Test daylight saving time transition
    await input.clear()
    await input.fill('2023-03-12T02:30')
    await expect(input).toHaveValue('2023-03-12T02:30')
  })

  test('should handle null and empty values', async ({ page }) => {
    // Navigate to form with nullable datetime field
    await page.goto('/admin/test-form-with-nullable-datetime')
    
    const input = page.locator('[data-testid="datetime-field-optional-time"] input')
    
    // Should start empty for nullable field
    await expect(input).toHaveValue('')
    
    // Should allow clearing the field
    await input.fill('2023-06-15T14:30')
    await expect(input).toHaveValue('2023-06-15T14:30')
    
    await input.clear()
    await expect(input).toHaveValue('')
  })
})

test.describe('DateTime Field Integration with Forms', () => {
  test('should integrate properly with Nova-style forms', async ({ page }) => {
    await page.goto('/admin/events/create')
    
    // Fill out a complete form with datetime field
    await page.fill('[data-testid="text-field-name"] input', 'Test Event')
    await page.fill('[data-testid="datetime-field-start-time"] input', '2023-12-25T10:00')
    await page.fill('[data-testid="datetime-field-end-time"] input', '2023-12-25T12:00')
    
    // Submit form
    await page.click('[data-testid="create-button"]')
    
    // Verify redirect to show page or index
    await page.waitForURL(/\/admin\/events\/\d+/)
    
    // Verify datetime values are displayed correctly
    const startTimeDisplay = page.locator('[data-testid="datetime-display-start-time"]')
    await expect(startTimeDisplay).toContainText('Dec 25, 2023 10:00')
  })

  test('should handle validation errors gracefully', async ({ page }) => {
    await page.goto('/admin/events/create')
    
    // Submit form with invalid datetime value
    await page.fill('[data-testid="datetime-field-start-time"] input', '2019-01-01T00:00') // Before min
    await page.click('[data-testid="create-button"]')
    
    // Should show validation error
    const errorMessage = page.locator('[data-testid="field-error-start-time"]')
    await expect(errorMessage).toBeVisible()
    await expect(errorMessage).toContainText('after')
  })

  test('should handle timezone conversion in forms', async ({ page }) => {
    await page.goto('/admin/meetings/create')
    
    // Fill timezone-aware datetime field
    await page.fill('[data-testid="datetime-field-meeting-time"] input', '2023-06-15T14:30')
    
    // Should show timezone info
    const timezoneInfo = page.locator('[data-testid="timezone-display"]')
    await expect(timezoneInfo).toContainText('America/New_York')
    
    // Submit and verify
    await page.click('[data-testid="create-button"]')
    await page.waitForURL(/\/admin\/meetings\/\d+/)
    
    // Datetime should be stored and displayed correctly
    const meetingTimeDisplay = page.locator('[data-testid="datetime-display-meeting-time"]')
    await expect(meetingTimeDisplay).toBeVisible()
  })
})
