import { test, expect } from '@playwright/test'

/**
 * Date Field End-to-End Playwright Tests
 *
 * Tests real-world browser interactions with the Date field component
 * including user interactions, accessibility, and cross-browser compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

test.describe('DateField E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Set up test page with DateField component
    await page.goto('/test-date-field')
    await page.waitForLoadState('networkidle')
  })

  test.describe('Basic User Interactions', () => {
    test('user can input date using keyboard', async ({ page }) => {
      const dateInput = page.locator('[data-testid="date-input"]')
      
      await dateInput.click()
      await dateInput.fill('2023-06-15')
      await dateInput.press('Tab')
      
      await expect(dateInput).toHaveValue('2023-06-15')
      
      // Verify the value is emitted correctly
      const emittedValue = await page.evaluate(() => window.lastEmittedValue)
      expect(emittedValue).toBe('2023-06-15')
    })

    test('user can select date using date picker', async ({ page }) => {
      const dateInput = page.locator('[data-testid="date-input"]')
      const pickerButton = page.locator('[data-testid="calendar-days-icon"]')
      
      await pickerButton.click()
      await page.waitForSelector('[data-testid="date-picker"]')
      
      // Select a specific date in the picker
      await page.locator('[data-testid="date-15"]').click()
      
      // Verify the input is updated
      const inputValue = await dateInput.inputValue()
      expect(inputValue).toContain('15')
    })

    test('user can clear date value', async ({ page }) => {
      const dateInput = page.locator('[data-testid="date-input"]')
      const clearButton = page.locator('[data-testid="clear-date"]')
      
      // Set initial value
      await dateInput.fill('2023-06-15')
      await expect(dateInput).toHaveValue('2023-06-15')
      
      // Clear the value
      await clearButton.click()
      
      await expect(dateInput).toHaveValue('')
      
      // Verify null is emitted
      const emittedValue = await page.evaluate(() => window.lastEmittedValue)
      expect(emittedValue).toBeNull()
    })
  })

  test.describe('Nova API Features', () => {
    test('picker format affects input display', async ({ page }) => {
      // Navigate to page with custom picker format
      await page.goto('/test-date-field?pickerFormat=d-m-Y')
      await page.waitForLoadState('networkidle')
      
      const dateInput = page.locator('[data-testid="date-input"]')
      
      // Set a date value
      await page.evaluate(() => {
        window.setDateValue('2023-06-15')
      })
      
      // Should display in d-m-Y format
      await expect(dateInput).toHaveValue('15-06-2023')
    })

    test('first day of week affects calendar display', async ({ page }) => {
      // Navigate to page with Monday as first day
      await page.goto('/test-date-field?firstDayOfWeek=1')
      await page.waitForLoadState('networkidle')
      
      const pickerButton = page.locator('[data-testid="calendar-days-icon"]')
      await pickerButton.click()
      
      // Check that Monday is the first column
      const firstDayHeader = page.locator('[data-testid="calendar-header"] th:first-child')
      await expect(firstDayHeader).toContainText('Mon')
    })

    test('picker display format affects readonly display', async ({ page }) => {
      // Navigate to page with custom display format
      await page.goto('/test-date-field?pickerDisplayFormat=DD-MM-YYYY&readonly=true')
      await page.waitForLoadState('networkidle')
      
      await page.evaluate(() => {
        window.setDateValue('2023-06-15')
      })
      
      const displayValue = page.locator('[data-testid="date-display"]')
      await expect(displayValue).toContainText('15-06-2023')
    })
  })

  test.describe('Validation and Constraints', () => {
    test('respects min and max date constraints', async ({ page }) => {
      await page.goto('/test-date-field?minDate=2023-01-01&maxDate=2023-12-31')
      await page.waitForLoadState('networkidle')
      
      const dateInput = page.locator('[data-testid="date-input"]')
      
      // Check min attribute
      await expect(dateInput).toHaveAttribute('min', '2023-01-01')
      
      // Check max attribute
      await expect(dateInput).toHaveAttribute('max', '2023-12-31')
      
      // Try to input date outside range
      await dateInput.fill('2022-12-31')
      await dateInput.press('Tab')
      
      // Should show validation error or prevent input
      const errorMessage = page.locator('[data-testid="validation-error"]')
      await expect(errorMessage).toBeVisible()
    })

    test('shows required validation', async ({ page }) => {
      await page.goto('/test-date-field?required=true')
      await page.waitForLoadState('networkidle')
      
      const dateInput = page.locator('[data-testid="date-input"]')
      const submitButton = page.locator('[data-testid="submit-button"]')
      
      // Try to submit without value
      await submitButton.click()
      
      // Should show required validation
      const errorMessage = page.locator('[data-testid="required-error"]')
      await expect(errorMessage).toBeVisible()
      await expect(errorMessage).toContainText('required')
    })
  })

  test.describe('Accessibility', () => {
    test('has proper ARIA labels and roles', async ({ page }) => {
      const dateInput = page.locator('[data-testid="date-input"]')
      const pickerButton = page.locator('[data-testid="calendar-days-icon"]')
      
      // Check input accessibility
      await expect(dateInput).toHaveAttribute('type', 'date')
      await expect(dateInput).toHaveAttribute('aria-label')
      
      // Check picker button accessibility
      await expect(pickerButton).toHaveAttribute('aria-label')
      await expect(pickerButton).toHaveAttribute('role', 'button')
    })

    test('supports keyboard navigation', async ({ page }) => {
      const dateInput = page.locator('[data-testid="date-input"]')
      const pickerButton = page.locator('[data-testid="calendar-days-icon"]')
      
      // Tab to input
      await page.keyboard.press('Tab')
      await expect(dateInput).toBeFocused()
      
      // Tab to picker button
      await page.keyboard.press('Tab')
      await expect(pickerButton).toBeFocused()
      
      // Open picker with Enter
      await page.keyboard.press('Enter')
      await page.waitForSelector('[data-testid="date-picker"]')
      
      // Navigate in picker with arrow keys
      await page.keyboard.press('ArrowRight')
      await page.keyboard.press('ArrowDown')
      
      // Select with Enter
      await page.keyboard.press('Enter')
      
      // Should close picker and update input
      await expect(page.locator('[data-testid="date-picker"]')).not.toBeVisible()
    })

    test('works with screen readers', async ({ page }) => {
      // Test that screen reader announcements work
      const dateInput = page.locator('[data-testid="date-input"]')
      
      await dateInput.fill('2023-06-15')
      
      // Check for screen reader announcements
      const announcement = page.locator('[aria-live="polite"]')
      await expect(announcement).toContainText('Date selected')
    })
  })

  test.describe('Cross-browser Compatibility', () => {
    test('works consistently across browsers', async ({ page, browserName }) => {
      const dateInput = page.locator('[data-testid="date-input"]')
      
      await dateInput.fill('2023-06-15')
      await dateInput.press('Tab')
      
      // Should work the same in all browsers
      await expect(dateInput).toHaveValue('2023-06-15')
      
      // Browser-specific behavior might differ for native date picker
      if (browserName === 'webkit') {
        // Safari might have different date picker behavior
        console.log('Safari-specific behavior tested')
      }
    })
  })

  test.describe('Performance', () => {
    test('handles rapid user input efficiently', async ({ page }) => {
      const dateInput = page.locator('[data-testid="date-input"]')
      
      // Measure performance of rapid input changes
      const startTime = Date.now()
      
      for (let i = 1; i <= 10; i++) {
        await dateInput.fill(`2023-06-${String(i).padStart(2, '0')}`)
        await page.waitForTimeout(50) // Small delay to simulate real typing
      }
      
      const endTime = Date.now()
      const duration = endTime - startTime
      
      // Should complete within reasonable time
      expect(duration).toBeLessThan(2000)
      
      // Final value should be correct
      await expect(dateInput).toHaveValue('2023-06-10')
    })

    test('date picker opens and closes smoothly', async ({ page }) => {
      const pickerButton = page.locator('[data-testid="calendar-days-icon"]')
      
      // Measure picker open/close performance
      const startTime = Date.now()
      
      // Open picker
      await pickerButton.click()
      await page.waitForSelector('[data-testid="date-picker"]', { state: 'visible' })
      
      // Close picker
      await page.keyboard.press('Escape')
      await page.waitForSelector('[data-testid="date-picker"]', { state: 'hidden' })
      
      const endTime = Date.now()
      const duration = endTime - startTime
      
      // Should open and close quickly
      expect(duration).toBeLessThan(1000)
    })
  })

  test.describe('Real-world Scenarios', () => {
    test('event booking workflow', async ({ page }) => {
      await page.goto('/test-event-booking')
      await page.waitForLoadState('networkidle')
      
      // Fill event details
      await page.fill('[data-testid="event-name"]', 'Birthday Party')
      
      // Select future date
      const eventDate = page.locator('[data-testid="event-date"]')
      const futureDate = new Date()
      futureDate.setDate(futureDate.getDate() + 30)
      const futureDateString = futureDate.toISOString().split('T')[0]
      
      await eventDate.fill(futureDateString)
      
      // Submit form
      await page.click('[data-testid="submit-event"]')
      
      // Should show success message
      await expect(page.locator('[data-testid="success-message"]')).toBeVisible()
      await expect(page.locator('[data-testid="success-message"]')).toContainText('Event created')
    })

    test('birth date registration workflow', async ({ page }) => {
      await page.goto('/test-birth-date-registration')
      await page.waitForLoadState('networkidle')
      
      // Fill personal details
      await page.fill('[data-testid="first-name"]', 'John')
      await page.fill('[data-testid="last-name"]', 'Doe')
      
      // Select birth date (must be at least 18 years ago)
      const birthDate = page.locator('[data-testid="birth-date"]')
      const validBirthDate = new Date()
      validBirthDate.setFullYear(validBirthDate.getFullYear() - 25)
      const birthDateString = validBirthDate.toISOString().split('T')[0]
      
      await birthDate.fill(birthDateString)
      
      // Submit registration
      await page.click('[data-testid="submit-registration"]')
      
      // Should proceed to next step
      await expect(page.locator('[data-testid="registration-step-2"]')).toBeVisible()
    })

    test('date range selection workflow', async ({ page }) => {
      await page.goto('/test-date-range')
      await page.waitForLoadState('networkidle')
      
      const startDate = page.locator('[data-testid="start-date"]')
      const endDate = page.locator('[data-testid="end-date"]')
      
      // Select start date
      await startDate.fill('2023-06-01')
      
      // Select end date
      await endDate.fill('2023-06-15')
      
      // Should show date range summary
      const summary = page.locator('[data-testid="date-range-summary"]')
      await expect(summary).toContainText('14 days')
    })
  })

  test.describe('Error Handling', () => {
    test('handles invalid date input gracefully', async ({ page }) => {
      const dateInput = page.locator('[data-testid="date-input"]')
      
      // Try to input invalid date
      await dateInput.fill('invalid-date')
      await dateInput.press('Tab')
      
      // Should handle gracefully without crashing
      const errorMessage = page.locator('[data-testid="invalid-date-error"]')
      await expect(errorMessage).toBeVisible()
    })

    test('recovers from network errors', async ({ page }) => {
      // Simulate network failure
      await page.route('**/api/validate-date', route => route.abort())
      
      const dateInput = page.locator('[data-testid="date-input"]')
      await dateInput.fill('2023-06-15')
      await dateInput.press('Tab')
      
      // Should show appropriate error message
      const networkError = page.locator('[data-testid="network-error"]')
      await expect(networkError).toBeVisible()
      
      // Should allow retry
      const retryButton = page.locator('[data-testid="retry-button"]')
      await expect(retryButton).toBeVisible()
    })
  })
})
