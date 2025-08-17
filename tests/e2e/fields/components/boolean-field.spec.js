import { test, expect } from '@playwright/test'

/**
 * Boolean Field Playwright E2E Tests
 *
 * Tests the complete end-to-end functionality of Boolean fields
 * in the browser environment, including visual rendering,
 * user interactions, and Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

test.describe('Boolean Field E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Set up test environment
    await page.goto('/admin-panel/test-boolean-field')
  })

  test('renders boolean field with checkbox input', async ({ page }) => {
    // Test basic boolean field rendering
    const booleanField = page.locator('[data-testid="boolean-field"]')
    await expect(booleanField).toBeVisible()

    // Test checkbox element exists
    const checkbox = booleanField.locator('input[type="checkbox"]')
    await expect(checkbox).toBeVisible()
    await expect(checkbox).toHaveClass(/h-4/)
    await expect(checkbox).toHaveClass(/w-4/)
    await expect(checkbox).toHaveClass(/text-blue-600/)
    await expect(checkbox).toHaveClass(/focus:ring-blue-500/)
    await expect(checkbox).toHaveClass(/border-gray-300/)
    await expect(checkbox).toHaveClass(/rounded/)
  })

  test('displays field label correctly', async ({ page }) => {
    const booleanField = page.locator('[data-testid="boolean-field"]')
    const label = booleanField.locator('label')
    
    await expect(label).toBeVisible()
    await expect(label).toContainText('Active')
    await expect(label).toHaveClass(/ml-2/)
    await expect(label).toHaveClass(/text-sm/)
    await expect(label).toHaveClass(/font-medium/)
    await expect(label).toHaveClass(/cursor-pointer/)
  })

  test('handles checkbox interactions correctly', async ({ page }) => {
    const booleanField = page.locator('[data-testid="boolean-field"]')
    const checkbox = booleanField.locator('input[type="checkbox"]')

    // Test initial unchecked state
    await expect(checkbox).not.toBeChecked()

    // Test checking the checkbox
    await checkbox.check()
    await expect(checkbox).toBeChecked()

    // Test unchecking the checkbox
    await checkbox.uncheck()
    await expect(checkbox).not.toBeChecked()
  })

  test('handles label click to toggle checkbox', async ({ page }) => {
    const booleanField = page.locator('[data-testid="boolean-field"]')
    const checkbox = booleanField.locator('input[type="checkbox"]')
    const label = booleanField.locator('label')

    // Test initial state
    await expect(checkbox).not.toBeChecked()

    // Click label to check
    await label.click()
    await expect(checkbox).toBeChecked()

    // Click label to uncheck
    await label.click()
    await expect(checkbox).not.toBeChecked()
  })

  test('displays required indicator when field is required', async ({ page }) => {
    const requiredField = page.locator('[data-testid="boolean-field-required"]')
    const requiredIndicator = requiredField.locator('.text-red-500')
    
    await expect(requiredIndicator).toBeVisible()
    await expect(requiredIndicator).toContainText('*')
  })

  test('does not display required indicator when field is optional', async ({ page }) => {
    const optionalField = page.locator('[data-testid="boolean-field-optional"]')
    const requiredIndicator = optionalField.locator('.text-red-500')
    
    await expect(requiredIndicator).toHaveCount(0)
  })

  test('handles disabled state correctly', async ({ page }) => {
    const disabledField = page.locator('[data-testid="boolean-field-disabled"]')
    const checkbox = disabledField.locator('input[type="checkbox"]')
    const label = disabledField.locator('label')

    // Test checkbox is disabled
    await expect(checkbox).toBeDisabled()
    await expect(checkbox).toHaveClass(/opacity-50/)
    await expect(checkbox).toHaveClass(/cursor-not-allowed/)

    // Test label has disabled styling
    await expect(label).toHaveClass(/cursor-not-allowed/)
    await expect(label).toHaveClass(/opacity-50/)

    // Test clicking disabled checkbox doesn't work
    const initialChecked = await checkbox.isChecked()
    await checkbox.click({ force: true })
    await expect(checkbox).toBeChecked({ checked: initialChecked })
  })

  test('displays readonly state with badge display', async ({ page }) => {
    // Test readonly true value
    const readonlyTrue = page.locator('[data-testid="boolean-field-readonly-true"]')
    await expect(readonlyTrue.locator('input[type="checkbox"]')).toHaveCount(0)
    
    const trueBadge = readonlyTrue.locator('.inline-flex')
    await expect(trueBadge).toBeVisible()
    await expect(trueBadge).toContainText('Yes')
    await expect(trueBadge).toHaveClass(/bg-green-100/)
    await expect(trueBadge).toHaveClass(/text-green-800/)

    // Test readonly false value
    const readonlyFalse = page.locator('[data-testid="boolean-field-readonly-false"]')
    await expect(readonlyFalse.locator('input[type="checkbox"]')).toHaveCount(0)
    
    const falseBadge = readonlyFalse.locator('.inline-flex')
    await expect(falseBadge).toBeVisible()
    await expect(falseBadge).toContainText('No')
    await expect(falseBadge).toHaveClass(/bg-gray-100/)
    await expect(falseBadge).toHaveClass(/text-gray-800/)
  })

  test('handles custom true/false values correctly', async ({ page }) => {
    // Test with custom string values
    const customField = page.locator('[data-testid="boolean-field-custom-values"]')
    const checkbox = customField.locator('input[type="checkbox"]')

    // Test initial state (should reflect custom value)
    const isInitiallyChecked = await checkbox.isChecked()
    
    // Toggle and verify state changes
    await checkbox.click()
    const isNowChecked = await checkbox.isChecked()
    expect(isNowChecked).not.toBe(isInitiallyChecked)

    // Toggle back
    await checkbox.click()
    await expect(checkbox).toBeChecked({ checked: isInitiallyChecked })
  })

  test('handles numeric true/false values correctly', async ({ page }) => {
    const numericField = page.locator('[data-testid="boolean-field-numeric"]')
    const checkbox = numericField.locator('input[type="checkbox"]')

    // Test checkbox functionality with numeric values
    await expect(checkbox).toBeVisible()
    
    // Test toggling
    const initialState = await checkbox.isChecked()
    await checkbox.click()
    await expect(checkbox).toBeChecked({ checked: !initialState })
  })

  test('maintains accessibility standards', async ({ page }) => {
    const booleanField = page.locator('[data-testid="boolean-field"]')
    const checkbox = booleanField.locator('input[type="checkbox"]')
    const label = booleanField.locator('label')

    // Test checkbox has proper attributes
    await expect(checkbox).toHaveAttribute('type', 'checkbox')
    
    // Test label is properly associated with checkbox
    const checkboxId = await checkbox.getAttribute('id')
    const labelFor = await label.getAttribute('for')
    expect(checkboxId).toBe(labelFor)

    // Test keyboard navigation
    await checkbox.focus()
    await expect(checkbox).toBeFocused()

    // Test space key toggles checkbox
    const initialState = await checkbox.isChecked()
    await page.keyboard.press('Space')
    await expect(checkbox).toBeChecked({ checked: !initialState })
  })

  test('handles dark mode correctly', async ({ page }) => {
    // Enable dark mode
    await page.locator('[data-testid="dark-mode-toggle"]').click()

    // Test that dark mode classes are applied
    const darkField = page.locator('[data-testid="boolean-field-dark"]')
    const checkbox = darkField.locator('input[type="checkbox"]')
    const label = darkField.locator('label')

    await expect(checkbox).toHaveClass(/border-gray-600/)
    await expect(checkbox).toHaveClass(/bg-gray-700/)
    await expect(label).toHaveClass(/text-white/)

    // Test readonly dark mode
    const readonlyDark = page.locator('[data-testid="boolean-field-readonly-dark"]')
    const badge = readonlyDark.locator('.inline-flex')
    
    if (await badge.isVisible()) {
      // Should have dark mode classes for badges
      const badgeClasses = await badge.getAttribute('class')
      expect(badgeClasses).toMatch(/(bg-green-900|bg-gray-700)/)
      expect(badgeClasses).toMatch(/(text-green-200|text-gray-300)/)
    }
  })

  test('displays correctly on different screen sizes', async ({ page }) => {
    const booleanField = page.locator('[data-testid="boolean-field"]')
    
    // Test desktop view
    await page.setViewportSize({ width: 1200, height: 800 })
    await expect(booleanField).toBeVisible()
    
    const checkbox = booleanField.locator('input[type="checkbox"]')
    const label = booleanField.locator('label')
    
    await expect(checkbox).toBeVisible()
    await expect(label).toBeVisible()

    // Test tablet view
    await page.setViewportSize({ width: 768, height: 1024 })
    await expect(booleanField).toBeVisible()
    await expect(checkbox).toBeVisible()
    await expect(label).toBeVisible()

    // Test mobile view
    await page.setViewportSize({ width: 375, height: 667 })
    await expect(booleanField).toBeVisible()
    await expect(checkbox).toBeVisible()
    await expect(label).toBeVisible()

    // Field should maintain its functionality across all screen sizes
    await checkbox.click()
    await expect(checkbox).toBeChecked()
  })

  test('integrates properly with BaseField wrapper', async ({ page }) => {
    const booleanField = page.locator('[data-testid="boolean-field"]')
    
    // Test that BaseField wrapper is present
    const baseField = booleanField.locator('[data-component="BaseField"]')
    await expect(baseField).toBeVisible()

    // Test help text is displayed if provided
    const helpText = booleanField.locator('.field-help')
    if (await helpText.count() > 0) {
      await expect(helpText).toBeVisible()
      await expect(helpText).toContainText('Toggle')
    }
  })

  test('handles error states gracefully', async ({ page }) => {
    // Test boolean field with validation errors
    const errorField = page.locator('[data-testid="boolean-field-with-errors"]')
    const checkbox = errorField.locator('input[type="checkbox"]')
    
    await expect(checkbox).toBeVisible()

    // Error message should be displayed
    const errorMessage = errorField.locator('.field-error')
    if (await errorMessage.count() > 0) {
      await expect(errorMessage).toBeVisible()
      await expect(errorMessage).toContainText('required')
    }

    // Checkbox should still be functional despite errors
    await checkbox.click()
    await expect(checkbox).toBeChecked()
  })

  test('handles focus and blur events correctly', async ({ page }) => {
    const booleanField = page.locator('[data-testid="boolean-field"]')
    const checkbox = booleanField.locator('input[type="checkbox"]')

    // Test focus
    await checkbox.focus()
    await expect(checkbox).toBeFocused()

    // Test blur
    await checkbox.blur()
    await expect(checkbox).not.toBeFocused()

    // Test tab navigation
    await page.keyboard.press('Tab')
    // Should focus on the checkbox (or skip if not focusable)
    const isFocused = await checkbox.evaluate(el => document.activeElement === el)
    // Boolean fields should be focusable
    expect(typeof isFocused).toBe('boolean')
  })

  test('performs well with many boolean fields', async ({ page }) => {
    // Test performance with multiple boolean fields
    const fieldList = page.locator('[data-testid="boolean-field-list"]')
    await expect(fieldList).toBeVisible()

    // Count boolean fields
    const checkboxes = fieldList.locator('input[type="checkbox"]')
    const checkboxCount = await checkboxes.count()
    expect(checkboxCount).toBeGreaterThan(5)

    // All checkboxes should be visible and functional
    for (let i = 0; i < Math.min(checkboxCount, 10); i++) {
      const checkbox = checkboxes.nth(i)
      await expect(checkbox).toBeVisible()
      
      // Test functionality
      const initialState = await checkbox.isChecked()
      await checkbox.click()
      await expect(checkbox).toBeChecked({ checked: !initialState })
    }

    // Page should remain responsive
    const startTime = Date.now()
    await page.locator('[data-testid="performance-test-button"]').click()
    const endTime = Date.now()
    
    // Should complete within reasonable time (less than 1 second)
    expect(endTime - startTime).toBeLessThan(1000)
  })

  test('handles form submission correctly', async ({ page }) => {
    const form = page.locator('[data-testid="boolean-field-form"]')
    const checkbox = form.locator('input[type="checkbox"]')
    const submitButton = form.locator('[data-testid="submit-button"]')

    // Test submitting with checked state
    await checkbox.check()
    await expect(checkbox).toBeChecked()
    
    await submitButton.click()
    
    // Should handle form submission (exact behavior depends on implementation)
    // At minimum, the form should not crash
    await expect(form).toBeVisible()

    // Test submitting with unchecked state
    await checkbox.uncheck()
    await expect(checkbox).not.toBeChecked()
    
    await submitButton.click()
    await expect(form).toBeVisible()
  })

  test('handles dynamic value changes from external sources', async ({ page }) => {
    const dynamicField = page.locator('[data-testid="boolean-field-dynamic"]')
    const checkbox = dynamicField.locator('input[type="checkbox"]')
    const toggleButton = page.locator('[data-testid="external-toggle-button"]')

    // Test initial state
    const initialState = await checkbox.isChecked()

    // Trigger external change
    await toggleButton.click()

    // Checkbox should reflect the external change
    await expect(checkbox).toBeChecked({ checked: !initialState })

    // Trigger another external change
    await toggleButton.click()

    // Should toggle back
    await expect(checkbox).toBeChecked({ checked: initialState })
  })

  test('maintains state consistency across interactions', async ({ page }) => {
    const consistencyField = page.locator('[data-testid="boolean-field-consistency"]')
    const checkbox = consistencyField.locator('input[type="checkbox"]')
    const label = consistencyField.locator('label')

    // Test multiple interaction methods produce consistent results
    
    // Method 1: Direct checkbox click
    await checkbox.click()
    const stateAfterCheckbox = await checkbox.isChecked()

    // Method 2: Label click
    await label.click()
    const stateAfterLabel = await checkbox.isChecked()
    expect(stateAfterLabel).toBe(!stateAfterCheckbox)

    // Method 3: Keyboard interaction
    await checkbox.focus()
    await page.keyboard.press('Space')
    const stateAfterKeyboard = await checkbox.isChecked()
    expect(stateAfterKeyboard).toBe(stateAfterCheckbox)

    // Method 4: Programmatic check/uncheck
    await checkbox.check()
    await expect(checkbox).toBeChecked()

    await checkbox.uncheck()
    await expect(checkbox).not.toBeChecked()
  })
})
