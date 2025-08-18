import { test, expect } from '@playwright/test'

/**
 * End-to-End tests for Timezone field using Playwright
 * 
 * Tests real-world user interactions and complete workflows
 * with the Timezone field in the admin panel.
 */

test.describe('Timezone Field E2E', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to a form with timezone field
    await page.goto('/admin/test-form-with-timezone')
  })

  test('should display timezone field with proper attributes', async ({ page }) => {
    // Locate the timezone field
    const timezoneField = page.locator('[data-testid="timezone-field-user-timezone"]')
    await expect(timezoneField).toBeVisible()

    // Check select element and attributes
    const select = timezoneField.locator('select')
    await expect(select).toBeVisible()
    await expect(select).toHaveAttribute('name', 'user_timezone')

    // Check placeholder option
    const placeholder = select.locator('option[value=""]')
    await expect(placeholder).toBeVisible()
    await expect(placeholder).toHaveText('Select timezone...')

    // Check that timezone options are present
    const options = select.locator('option:not([value=""])')
    const optionCount = await options.count()
    expect(optionCount).toBeGreaterThan(400) // Should have many timezone options
  })

  test('should allow timezone selection', async ({ page }) => {
    const select = page.locator('[data-testid="timezone-field-user-timezone"] select')
    
    // Select a timezone
    await select.selectOption('America/New_York')
    
    // Verify selection
    await expect(select).toHaveValue('America/New_York')
    
    // Change to different timezone
    await select.selectOption('Europe/London')
    await expect(select).toHaveValue('Europe/London')
    
    // Change to UTC
    await select.selectOption('UTC')
    await expect(select).toHaveValue('UTC')
  })

  test('should handle form submission with timezone', async ({ page }) => {
    const select = page.locator('[data-testid="timezone-field-user-timezone"] select')
    
    // Fill form with timezone
    await select.selectOption('America/Los_Angeles')
    await page.fill('[data-testid="text-field-name"] input', 'Test User')
    
    // Submit form
    await page.click('[data-testid="submit-button"]')
    
    // Should redirect to success page or show success message
    await expect(page).toHaveURL(/\/admin\/.*\/\d+/)
    
    // Verify timezone was saved
    const timezoneDisplay = page.locator('[data-testid="timezone-display-user-timezone"]')
    await expect(timezoneDisplay).toContainText('America/Los_Angeles')
  })

  test('should display validation errors', async ({ page }) => {
    // Try to submit form without selecting required timezone
    await page.click('[data-testid="submit-button"]')
    
    // Should show validation error
    const errorMessage = page.locator('[data-testid="field-error-user-timezone"]')
    await expect(errorMessage).toBeVisible()
    await expect(errorMessage).toContainText('required')
  })

  test('should handle nullable timezone field', async ({ page }) => {
    // Navigate to form with nullable timezone
    await page.goto('/admin/test-form-with-nullable-timezone')
    
    const select = page.locator('[data-testid="timezone-field-optional-timezone"] select')
    
    // Should start with no selection
    await expect(select).toHaveValue('')
    
    // Should allow submission without selection
    await page.fill('[data-testid="text-field-name"] input', 'Test User')
    await page.click('[data-testid="submit-button"]')
    
    // Should succeed
    await expect(page).toHaveURL(/\/admin\/.*\/\d+/)
  })

  test('should display timezone options in correct format', async ({ page }) => {
    const select = page.locator('[data-testid="timezone-field-user-timezone"] select')
    
    // Check specific timezone options format
    const utcOption = select.locator('option[value="UTC"]')
    await expect(utcOption).toHaveText('UTC')
    
    const nyOption = select.locator('option[value="America/New_York"]')
    await expect(nyOption).toHaveText('America/New_York')
    
    const londonOption = select.locator('option[value="Europe/London"]')
    await expect(londonOption).toHaveText('Europe/London')
    
    const tokyoOption = select.locator('option[value="Asia/Tokyo"]')
    await expect(tokyoOption).toHaveText('Asia/Tokyo')
  })

  test('should handle disabled state', async ({ page }) => {
    // Navigate to form with disabled timezone field
    await page.goto('/admin/test-form-with-disabled-timezone')
    
    const select = page.locator('[data-testid="timezone-field-disabled-timezone"] select')
    
    // Should be disabled
    await expect(select).toBeDisabled()
    
    // Should have disabled styling
    await expect(select).toHaveClass(/opacity-50/)
    await expect(select).toHaveClass(/cursor-not-allowed/)
  })

  test('should handle readonly state', async ({ page }) => {
    // Navigate to form with readonly timezone field
    await page.goto('/admin/test-form-with-readonly-timezone')
    
    const select = page.locator('[data-testid="timezone-field-readonly-timezone"] select')
    
    // Should be disabled (readonly is implemented as disabled for selects)
    await expect(select).toBeDisabled()
    
    // Should show existing value
    await expect(select).toHaveValue('America/New_York')
  })

  test('should work in dark theme', async ({ page }) => {
    // Enable dark theme
    await page.goto('/admin/settings/appearance')
    await page.click('[data-testid="dark-theme-toggle"]')
    
    // Navigate to timezone form
    await page.goto('/admin/test-form-with-timezone')
    
    const select = page.locator('[data-testid="timezone-field-user-timezone"] select')
    
    // Should have dark theme classes
    await expect(select).toHaveClass(/admin-input-dark/)
    
    // Should still be functional
    await select.selectOption('Europe/Paris')
    await expect(select).toHaveValue('Europe/Paris')
  })

  test('should handle error states', async ({ page }) => {
    const select = page.locator('[data-testid="timezone-field-user-timezone"] select')
    
    // Submit form to trigger validation error
    await page.click('[data-testid="submit-button"]')
    
    // Field should have error styling
    await expect(select).toHaveClass(/border-red-300/)
    
    // Fix the error
    await select.selectOption('UTC')
    
    // Error styling should be removed after fixing
    await expect(select).not.toHaveClass(/border-red-300/)
  })

  test('should handle all common timezones', async ({ page }) => {
    const select = page.locator('[data-testid="timezone-field-user-timezone"] select')
    
    // Test common timezones
    const commonTimezones = [
      'UTC',
      'America/New_York',
      'America/Chicago', 
      'America/Denver',
      'America/Los_Angeles',
      'Europe/London',
      'Europe/Paris',
      'Asia/Tokyo',
      'Australia/Sydney'
    ]
    
    for (const timezone of commonTimezones) {
      await select.selectOption(timezone)
      await expect(select).toHaveValue(timezone)
    }
  })

  test('should handle edge case timezones', async ({ page }) => {
    const select = page.locator('[data-testid="timezone-field-user-timezone"] select')
    
    // Test edge case timezones
    const edgeTimezones = [
      'Pacific/Kiritimati', // UTC+14
      'Pacific/Niue', // UTC-11
      'America/Argentina/Buenos_Aires', // Long name
      'Antarctica/McMurdo' // Antarctica
    ]
    
    for (const timezone of edgeTimezones) {
      // Check if option exists (some might not be available)
      const option = select.locator(`option[value="${timezone}"]`)
      const exists = await option.count() > 0
      
      if (exists) {
        await select.selectOption(timezone)
        await expect(select).toHaveValue(timezone)
      }
    }
  })

  test('should maintain selection during form interactions', async ({ page }) => {
    const select = page.locator('[data-testid="timezone-field-user-timezone"] select')
    
    // Select timezone
    await select.selectOption('Asia/Tokyo')
    await expect(select).toHaveValue('Asia/Tokyo')
    
    // Interact with other form fields
    await page.fill('[data-testid="text-field-name"] input', 'Test User')
    await page.fill('[data-testid="email-field-email"] input', 'test@example.com')
    
    // Timezone selection should be maintained
    await expect(select).toHaveValue('Asia/Tokyo')
    
    // Focus and blur the timezone field
    await select.focus()
    await select.blur()
    
    // Selection should still be maintained
    await expect(select).toHaveValue('Asia/Tokyo')
  })

  test('should work with form reset', async ({ page }) => {
    const select = page.locator('[data-testid="timezone-field-user-timezone"] select')
    
    // Select timezone
    await select.selectOption('Europe/Berlin')
    await expect(select).toHaveValue('Europe/Berlin')
    
    // Reset form
    await page.click('[data-testid="reset-button"]')
    
    // Should return to default/empty state
    await expect(select).toHaveValue('')
  })

  test('should handle keyboard navigation', async ({ page }) => {
    const select = page.locator('[data-testid="timezone-field-user-timezone"] select')
    
    // Focus the select
    await select.focus()
    
    // Use keyboard to navigate
    await page.keyboard.press('ArrowDown') // Open dropdown
    await page.keyboard.press('ArrowDown') // Move to first option
    await page.keyboard.press('Enter') // Select option
    
    // Should have selected a timezone
    const value = await select.inputValue()
    expect(value).not.toBe('')
  })

  test('should display help text', async ({ page }) => {
    // Navigate to form with help text
    await page.goto('/admin/test-form-with-timezone-help')
    
    const helpText = page.locator('[data-testid="field-help-user-timezone"]')
    await expect(helpText).toBeVisible()
    await expect(helpText).toContainText('Select your timezone')
  })

  test('should handle real-world user scenarios', async ({ page }) => {
    // Scenario 1: User registration
    await page.goto('/admin/users/create')
    
    await page.fill('[data-testid="text-field-name"] input', 'John Doe')
    await page.fill('[data-testid="email-field-email"] input', 'john@example.com')
    
    const timezoneSelect = page.locator('[data-testid="timezone-field-timezone"] select')
    await timezoneSelect.selectOption('America/New_York')
    
    await page.click('[data-testid="create-button"]')
    await expect(page).toHaveURL(/\/admin\/users\/\d+/)
    
    // Verify timezone is displayed
    const timezoneDisplay = page.locator('[data-testid="timezone-display-timezone"]')
    await expect(timezoneDisplay).toContainText('America/New_York')
  })

  test('should handle form validation with other fields', async ({ page }) => {
    const select = page.locator('[data-testid="timezone-field-user-timezone"] select')
    
    // Select timezone but leave other required fields empty
    await select.selectOption('UTC')
    await page.click('[data-testid="submit-button"]')
    
    // Should show validation errors for other fields
    const nameError = page.locator('[data-testid="field-error-name"]')
    await expect(nameError).toBeVisible()
    
    // Timezone field should not have error
    const timezoneError = page.locator('[data-testid="field-error-user-timezone"]')
    await expect(timezoneError).not.toBeVisible()
    
    // Timezone selection should be maintained
    await expect(select).toHaveValue('UTC')
  })
})
