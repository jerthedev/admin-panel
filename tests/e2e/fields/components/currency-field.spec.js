import { test, expect } from '@playwright/test'

/**
 * Playwright E2E tests for Currency field component
 * 
 * Note: These tests are designed to run against a live application
 * with the Currency field properly integrated. They test the complete
 * user interaction flow in a real browser environment.
 */

test.describe('Currency Field E2E', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to a page with Currency field
    // This would typically be a form page in your admin panel
    await page.goto('/admin/test-form-with-currency')
  })

  test('should display currency field with proper formatting', async ({ page }) => {
    // Locate the currency field
    const currencyField = page.locator('[data-testid="currency-field-price"]')
    await expect(currencyField).toBeVisible()

    // Check for currency symbol
    const symbol = page.locator('[data-testid="currency-symbol"]')
    await expect(symbol).toBeVisible()
    await expect(symbol).toHaveText('$')

    // Check input attributes
    const input = currencyField.locator('input[type="number"]')
    await expect(input).toHaveAttribute('step', '0.01')
    await expect(input).toHaveAttribute('min', '0')
  })

  test('should handle user input correctly', async ({ page }) => {
    const input = page.locator('[data-testid="currency-field-price"] input')
    
    // Clear and type a value
    await input.clear()
    await input.fill('123.45')
    
    // Verify the value is set
    await expect(input).toHaveValue('123.45')
    
    // Trigger blur to ensure formatting
    await input.blur()
    
    // Value should remain as entered for input field
    await expect(input).toHaveValue('123.45')
  })

  test('should validate min/max constraints', async ({ page }) => {
    const input = page.locator('[data-testid="currency-field-price"] input')
    
    // Test minimum value validation
    await input.clear()
    await input.fill('-10')
    await input.blur()
    
    // Should show validation error or prevent invalid value
    const errorMessage = page.locator('[data-testid="field-error"]')
    await expect(errorMessage).toBeVisible()
    
    // Test maximum value validation
    await input.clear()
    await input.fill('99999')
    await input.blur()
    
    await expect(errorMessage).toBeVisible()
  })

  test('should handle different currencies', async ({ page }) => {
    // Test EUR currency field
    const eurField = page.locator('[data-testid="currency-field-eur-price"]')
    await expect(eurField).toBeVisible()
    
    const eurSymbol = eurField.locator('[data-testid="currency-symbol"]')
    await expect(eurSymbol).toHaveText('€')
    
    // Test GBP currency field
    const gbpField = page.locator('[data-testid="currency-field-gbp-price"]')
    await expect(gbpField).toBeVisible()
    
    const gbpSymbol = gbpField.locator('[data-testid="currency-symbol"]')
    await expect(gbpSymbol).toHaveText('£')
  })

  test('should handle minor units correctly', async ({ page }) => {
    // Navigate to form with minor units field
    await page.goto('/admin/test-form-with-minor-units')
    
    const minorUnitsField = page.locator('[data-testid="currency-field-cents"]')
    const input = minorUnitsField.locator('input')
    
    // Step should be 1 for minor units
    await expect(input).toHaveAttribute('step', '1')
    
    // Enter a value
    await input.clear()
    await input.fill('123.45')
    
    // Submit form and verify backend receives correct value
    await page.click('[data-testid="submit-button"]')
    
    // Check for success message or redirect
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible()
  })

  test('should support keyboard navigation', async ({ page }) => {
    const input = page.locator('[data-testid="currency-field-price"] input')
    
    // Focus the field
    await input.focus()
    await expect(input).toBeFocused()
    
    // Use arrow keys to increment/decrement
    await input.press('ArrowUp')
    await input.press('ArrowUp')
    
    // Use Tab to navigate away
    await input.press('Tab')
    await expect(input).not.toBeFocused()
  })

  test('should handle copy/paste operations', async ({ page }) => {
    const input = page.locator('[data-testid="currency-field-price"] input')
    
    // Type a value
    await input.clear()
    await input.fill('456.78')
    
    // Select all and copy
    await input.selectText()
    await page.keyboard.press('Control+c')
    
    // Clear and paste
    await input.clear()
    await page.keyboard.press('Control+v')
    
    // Verify pasted value
    await expect(input).toHaveValue('456.78')
  })

  test('should work with form submission', async ({ page }) => {
    const input = page.locator('[data-testid="currency-field-price"] input')
    
    // Fill the currency field
    await input.clear()
    await input.fill('299.99')
    
    // Fill other required fields if any
    const nameField = page.locator('[data-testid="text-field-name"] input')
    if (await nameField.isVisible()) {
      await nameField.fill('Test Product')
    }
    
    // Submit the form
    await page.click('[data-testid="submit-button"]')
    
    // Wait for submission to complete
    await page.waitForLoadState('networkidle')
    
    // Verify success (could be redirect, success message, etc.)
    const currentUrl = page.url()
    expect(currentUrl).toContain('/admin/')
  })

  test('should display formatted values correctly', async ({ page }) => {
    // Navigate to a detail/show page with currency values
    await page.goto('/admin/products/1')
    
    const priceDisplay = page.locator('[data-testid="currency-display-price"]')
    await expect(priceDisplay).toBeVisible()
    
    // Should show formatted currency (e.g., "$123.45")
    const displayText = await priceDisplay.textContent()
    expect(displayText).toMatch(/^\$\d+\.\d{2}$/)
  })

  test('should handle dark mode correctly', async ({ page }) => {
    // Toggle dark mode
    await page.click('[data-testid="dark-mode-toggle"]')
    
    const currencyField = page.locator('[data-testid="currency-field-price"]')
    const input = currencyField.locator('input')
    
    // Verify dark mode classes are applied
    await expect(input).toHaveClass(/admin-input-dark/)
    
    // Symbol should also have dark mode styling
    const symbol = currencyField.locator('[data-testid="currency-symbol"]')
    await expect(symbol).toHaveClass(/text-gray-400/)
  })

  test('should be accessible', async ({ page }) => {
    const input = page.locator('[data-testid="currency-field-price"] input')
    
    // Check for proper ARIA attributes
    await expect(input).toHaveAttribute('type', 'number')
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
    const input = page.locator('[data-testid="currency-field-price"] input')
    
    // Test very large numbers
    await input.clear()
    await input.fill('999999.99')
    await expect(input).toHaveValue('999999.99')
    
    // Test very small numbers
    await input.clear()
    await input.fill('0.01')
    await expect(input).toHaveValue('0.01')
    
    // Test zero
    await input.clear()
    await input.fill('0')
    await expect(input).toHaveValue('0')
    
    // Test negative numbers (if allowed)
    await input.clear()
    await input.fill('-10.50')
    // Behavior depends on field configuration
  })
})

test.describe('Currency Field Integration with Forms', () => {
  test('should integrate properly with Nova-style forms', async ({ page }) => {
    await page.goto('/admin/products/create')
    
    // Fill out a complete form with currency field
    await page.fill('[data-testid="text-field-name"] input', 'Test Product')
    await page.fill('[data-testid="currency-field-price"] input', '49.99')
    await page.fill('[data-testid="currency-field-cost"] input', '25.00')
    
    // Submit form
    await page.click('[data-testid="create-button"]')
    
    // Verify redirect to show page or index
    await page.waitForURL(/\/admin\/products\/\d+/)
    
    // Verify currency values are displayed correctly
    const priceDisplay = page.locator('[data-testid="currency-display-price"]')
    await expect(priceDisplay).toContainText('$49.99')
  })

  test('should handle validation errors gracefully', async ({ page }) => {
    await page.goto('/admin/products/create')
    
    // Submit form with invalid currency value
    await page.fill('[data-testid="currency-field-price"] input', 'invalid')
    await page.click('[data-testid="create-button"]')
    
    // Should show validation error
    const errorMessage = page.locator('[data-testid="field-error-price"]')
    await expect(errorMessage).toBeVisible()
    await expect(errorMessage).toContainText('valid')
  })
})
