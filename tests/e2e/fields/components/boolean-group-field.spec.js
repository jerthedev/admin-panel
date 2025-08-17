import { test, expect } from '@playwright/test'

/**
 * Boolean Group Field Playwright E2E Tests
 *
 * Tests the complete end-to-end functionality of BooleanGroup fields
 * in the browser environment, including visual rendering,
 * user interactions, and Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

test.describe('Boolean Group Field E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Set up test environment
    await page.goto('/admin-panel/test-boolean-group-field')
  })

  test('renders boolean group field with multiple checkboxes', async ({ page }) => {
    // Test basic boolean group field rendering
    const booleanGroupField = page.locator('[data-testid="boolean-group-field"]')
    await expect(booleanGroupField).toBeVisible()

    // Test multiple checkbox elements exist
    const checkboxes = booleanGroupField.locator('input[type="checkbox"]')
    await expect(checkboxes).toHaveCount(4) // Should have 4 permission checkboxes

    // Test each checkbox has proper styling
    for (let i = 0; i < 4; i++) {
      const checkbox = checkboxes.nth(i)
      await expect(checkbox).toBeVisible()
      await expect(checkbox).toHaveClass(/h-4/)
      await expect(checkbox).toHaveClass(/w-4/)
      await expect(checkbox).toHaveClass(/text-blue-600/)
      await expect(checkbox).toHaveClass(/focus:ring-blue-500/)
      await expect(checkbox).toHaveClass(/border-gray-300/)
      await expect(checkbox).toHaveClass(/rounded/)
    }
  })

  test('displays field labels correctly for each option', async ({ page }) => {
    const booleanGroupField = page.locator('[data-testid="boolean-group-field"]')
    const labels = booleanGroupField.locator('label')
    
    // Should have labels for each checkbox option
    await expect(labels).toHaveCount(4)
    
    // Test label content and styling
    await expect(labels.nth(0)).toContainText('Create')
    await expect(labels.nth(1)).toContainText('Read')
    await expect(labels.nth(2)).toContainText('Update')
    await expect(labels.nth(3)).toContainText('Delete')

    // Test label styling
    for (let i = 0; i < 4; i++) {
      const label = labels.nth(i)
      await expect(label).toHaveClass(/ml-2/)
      await expect(label).toHaveClass(/text-sm/)
      await expect(label).toHaveClass(/font-medium/)
      await expect(label).toHaveClass(/cursor-pointer/)
    }
  })

  test('handles individual checkbox interactions correctly', async ({ page }) => {
    const booleanGroupField = page.locator('[data-testid="boolean-group-field"]')
    const checkboxes = booleanGroupField.locator('input[type="checkbox"]')

    // Test initial states (should be mixed based on test data)
    const initialStates = []
    for (let i = 0; i < 4; i++) {
      initialStates[i] = await checkboxes.nth(i).isChecked()
    }

    // Test checking unchecked boxes
    for (let i = 0; i < 4; i++) {
      if (!initialStates[i]) {
        await checkboxes.nth(i).check()
        await expect(checkboxes.nth(i)).toBeChecked()
      }
    }

    // Test unchecking checked boxes
    for (let i = 0; i < 4; i++) {
      if (initialStates[i]) {
        await checkboxes.nth(i).uncheck()
        await expect(checkboxes.nth(i)).not.toBeChecked()
      }
    }
  })

  test('handles label clicks to toggle checkboxes', async ({ page }) => {
    const booleanGroupField = page.locator('[data-testid="boolean-group-field"]')
    const checkboxes = booleanGroupField.locator('input[type="checkbox"]')
    const labels = booleanGroupField.locator('label')

    // Test first checkbox/label pair
    const firstCheckbox = checkboxes.nth(0)
    const firstLabel = labels.nth(0)
    
    const initialState = await firstCheckbox.isChecked()
    
    // Click label to toggle
    await firstLabel.click()
    await expect(firstCheckbox).toBeChecked({ checked: !initialState })

    // Click label again to toggle back
    await firstLabel.click()
    await expect(firstCheckbox).toBeChecked({ checked: initialState })
  })

  test('displays required indicator when field is required', async ({ page }) => {
    const requiredField = page.locator('[data-testid="boolean-group-field-required"]')
    
    await expect(requiredField).toContainText('* Required')
    
    const requiredIndicator = requiredField.locator('.text-red-500')
    await expect(requiredIndicator).toBeVisible()
    await expect(requiredIndicator).toContainText('*')
  })

  test('does not display required indicator when field is optional', async ({ page }) => {
    const optionalField = page.locator('[data-testid="boolean-group-field-optional"]')
    
    // Should not contain required text
    await expect(optionalField).not.toContainText('* Required')
    
    const requiredIndicator = optionalField.locator('.text-red-500')
    await expect(requiredIndicator).toHaveCount(0)
  })

  test('handles disabled state correctly', async ({ page }) => {
    const disabledField = page.locator('[data-testid="boolean-group-field-disabled"]')
    const checkboxes = disabledField.locator('input[type="checkbox"]')
    const labels = disabledField.locator('label')

    // Test all checkboxes are disabled
    for (let i = 0; i < await checkboxes.count(); i++) {
      const checkbox = checkboxes.nth(i)
      await expect(checkbox).toBeDisabled()
      await expect(checkbox).toHaveClass(/opacity-50/)
      await expect(checkbox).toHaveClass(/cursor-not-allowed/)
    }

    // Test all labels have disabled styling
    for (let i = 0; i < await labels.count(); i++) {
      const label = labels.nth(i)
      await expect(label).toHaveClass(/cursor-not-allowed/)
      await expect(label).toHaveClass(/opacity-50/)
    }

    // Test clicking disabled checkboxes doesn't work
    const firstCheckbox = checkboxes.nth(0)
    const initialChecked = await firstCheckbox.isChecked()
    await firstCheckbox.click({ force: true })
    await expect(firstCheckbox).toBeChecked({ checked: initialChecked })
  })

  test('displays readonly state with badge display', async ({ page }) => {
    // Test readonly with mixed values
    const readonlyField = page.locator('[data-testid="boolean-group-field-readonly"]')
    await expect(readonlyField.locator('input[type="checkbox"]')).toHaveCount(0)
    
    // Should show badges for each option
    const badges = readonlyField.locator('.inline-flex')
    await expect(badges.count()).toBeGreaterThan(0)

    // Test badge styling for true values
    const trueBadges = readonlyField.locator('.bg-green-100')
    await expect(trueBadges.count()).toBeGreaterThan(0)
    
    // Test badge styling for false values
    const falseBadges = readonlyField.locator('.bg-gray-100')
    await expect(falseBadges.count()).toBeGreaterThan(0)

    // Test badges contain option labels
    await expect(readonlyField).toContainText('Create')
    await expect(readonlyField).toContainText('Read')
  })

  test('handles hide false values correctly in readonly mode', async ({ page }) => {
    const hideFalseField = page.locator('[data-testid="boolean-group-field-hide-false"]')
    
    // Should only show true values (green badges)
    const trueBadges = hideFalseField.locator('.bg-green-100')
    await expect(trueBadges.count()).toBeGreaterThan(0)
    
    // Should not show false values (gray badges)
    const falseBadges = hideFalseField.locator('.bg-gray-100')
    await expect(falseBadges).toHaveCount(0)
  })

  test('handles hide true values correctly in readonly mode', async ({ page }) => {
    const hideTrueField = page.locator('[data-testid="boolean-group-field-hide-true"]')
    
    // Should only show false values (gray badges)
    const falseBadges = hideTrueField.locator('.bg-gray-100')
    await expect(falseBadges.count()).toBeGreaterThan(0)
    
    // Should not show true values (green badges)
    const trueBadges = hideTrueField.locator('.bg-green-100')
    await expect(trueBadges).toHaveCount(0)
  })

  test('displays custom no value text when no values to show', async ({ page }) => {
    const noValueField = page.locator('[data-testid="boolean-group-field-no-values"]')
    
    // Should display custom no value text
    await expect(noValueField).toContainText('No permissions selected')
    
    // Should not have any badges
    const badges = noValueField.locator('.inline-flex')
    await expect(badges).toHaveCount(0)
  })

  test('maintains accessibility standards', async ({ page }) => {
    const booleanGroupField = page.locator('[data-testid="boolean-group-field"]')
    const checkboxes = booleanGroupField.locator('input[type="checkbox"]')
    const labels = booleanGroupField.locator('label')

    // Test each checkbox has proper attributes
    for (let i = 0; i < await checkboxes.count(); i++) {
      const checkbox = checkboxes.nth(i)
      const label = labels.nth(i)
      
      await expect(checkbox).toHaveAttribute('type', 'checkbox')
      
      // Test label is properly associated with checkbox
      const checkboxId = await checkbox.getAttribute('id')
      const labelFor = await label.getAttribute('for')
      expect(checkboxId).toBe(labelFor)
    }

    // Test keyboard navigation
    const firstCheckbox = checkboxes.nth(0)
    await firstCheckbox.focus()
    await expect(firstCheckbox).toBeFocused()

    // Test space key toggles checkbox
    const initialState = await firstCheckbox.isChecked()
    await page.keyboard.press('Space')
    await expect(firstCheckbox).toBeChecked({ checked: !initialState })

    // Test tab navigation between checkboxes
    await page.keyboard.press('Tab')
    const secondCheckbox = checkboxes.nth(1)
    await expect(secondCheckbox).toBeFocused()
  })

  test('handles dark mode correctly', async ({ page }) => {
    // Enable dark mode
    await page.locator('[data-testid="dark-mode-toggle"]').click()

    // Test that dark mode classes are applied
    const darkField = page.locator('[data-testid="boolean-group-field-dark"]')
    const checkboxes = darkField.locator('input[type="checkbox"]')
    const labels = darkField.locator('label')

    // Test checkbox dark mode styling
    for (let i = 0; i < await checkboxes.count(); i++) {
      const checkbox = checkboxes.nth(i)
      await expect(checkbox).toHaveClass(/border-gray-600/)
      await expect(checkbox).toHaveClass(/bg-gray-700/)
    }

    // Test label dark mode styling
    for (let i = 0; i < await labels.count(); i++) {
      const label = labels.nth(i)
      await expect(label).toHaveClass(/text-white/)
    }

    // Test readonly dark mode
    const readonlyDark = page.locator('[data-testid="boolean-group-field-readonly-dark"]')
    const badges = readonlyDark.locator('.inline-flex')
    
    if (await badges.count() > 0) {
      // Should have dark mode classes for badges
      const darkTrueBadges = readonlyDark.locator('.bg-green-900')
      const darkFalseBadges = readonlyDark.locator('.bg-gray-700')
      
      expect(await darkTrueBadges.count() + await darkFalseBadges.count()).toBeGreaterThan(0)
    }
  })

  test('displays correctly on different screen sizes', async ({ page }) => {
    const booleanGroupField = page.locator('[data-testid="boolean-group-field"]')
    
    // Test desktop view
    await page.setViewportSize({ width: 1200, height: 800 })
    await expect(booleanGroupField).toBeVisible()
    
    const checkboxes = booleanGroupField.locator('input[type="checkbox"]')
    const labels = booleanGroupField.locator('label')
    
    await expect(checkboxes.nth(0)).toBeVisible()
    await expect(labels.nth(0)).toBeVisible()

    // Test tablet view
    await page.setViewportSize({ width: 768, height: 1024 })
    await expect(booleanGroupField).toBeVisible()
    await expect(checkboxes.nth(0)).toBeVisible()
    await expect(labels.nth(0)).toBeVisible()

    // Test mobile view
    await page.setViewportSize({ width: 375, height: 667 })
    await expect(booleanGroupField).toBeVisible()
    await expect(checkboxes.nth(0)).toBeVisible()
    await expect(labels.nth(0)).toBeVisible()

    // Field should maintain its functionality across all screen sizes
    const firstCheckbox = checkboxes.nth(0)
    const initialState = await firstCheckbox.isChecked()
    await firstCheckbox.click()
    await expect(firstCheckbox).toBeChecked({ checked: !initialState })
  })

  test('integrates properly with BaseField wrapper', async ({ page }) => {
    const booleanGroupField = page.locator('[data-testid="boolean-group-field"]')
    
    // Test that BaseField wrapper is present
    const baseField = booleanGroupField.locator('[data-component="BaseField"]')
    await expect(baseField).toBeVisible()

    // Test help text is displayed if provided
    const helpText = booleanGroupField.locator('.field-help')
    if (await helpText.count() > 0) {
      await expect(helpText).toBeVisible()
      await expect(helpText).toContainText('Select')
    }
  })

  test('handles error states gracefully', async ({ page }) => {
    // Test boolean group field with validation errors
    const errorField = page.locator('[data-testid="boolean-group-field-with-errors"]')
    const checkboxes = errorField.locator('input[type="checkbox"]')
    
    await expect(checkboxes.nth(0)).toBeVisible()

    // Error message should be displayed
    const errorMessage = errorField.locator('.field-error')
    if (await errorMessage.count() > 0) {
      await expect(errorMessage).toBeVisible()
      await expect(errorMessage).toContainText('required')
    }

    // Checkboxes should still be functional despite errors
    const firstCheckbox = checkboxes.nth(0)
    const initialState = await firstCheckbox.isChecked()
    await firstCheckbox.click()
    await expect(firstCheckbox).toBeChecked({ checked: !initialState })
  })

  test('handles focus and blur events correctly', async ({ page }) => {
    const booleanGroupField = page.locator('[data-testid="boolean-group-field"]')
    const checkboxes = booleanGroupField.locator('input[type="checkbox"]')

    // Test focus on first checkbox
    const firstCheckbox = checkboxes.nth(0)
    await firstCheckbox.focus()
    await expect(firstCheckbox).toBeFocused()

    // Test blur
    await firstCheckbox.blur()
    await expect(firstCheckbox).not.toBeFocused()

    // Test tab navigation through all checkboxes
    await firstCheckbox.focus()
    for (let i = 1; i < await checkboxes.count(); i++) {
      await page.keyboard.press('Tab')
      await expect(checkboxes.nth(i)).toBeFocused()
    }
  })

  test('performs well with many boolean group options', async ({ page }) => {
    // Test performance with multiple boolean group options
    const largeField = page.locator('[data-testid="boolean-group-field-large"]')
    await expect(largeField).toBeVisible()

    // Count checkboxes
    const checkboxes = largeField.locator('input[type="checkbox"]')
    const checkboxCount = await checkboxes.count()
    expect(checkboxCount).toBeGreaterThan(8) // Should have many options

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
    const form = page.locator('[data-testid="boolean-group-field-form"]')
    const checkboxes = form.locator('input[type="checkbox"]')
    const submitButton = form.locator('[data-testid="submit-button"]')

    // Test submitting with mixed checkbox states
    await checkboxes.nth(0).check()
    await checkboxes.nth(1).uncheck()
    await checkboxes.nth(2).check()
    
    await submitButton.click()
    
    // Should handle form submission (exact behavior depends on implementation)
    // At minimum, the form should not crash
    await expect(form).toBeVisible()

    // Test submitting with all unchecked
    for (let i = 0; i < await checkboxes.count(); i++) {
      await checkboxes.nth(i).uncheck()
    }
    
    await submitButton.click()
    await expect(form).toBeVisible()
  })

  test('handles dynamic value changes from external sources', async ({ page }) => {
    const dynamicField = page.locator('[data-testid="boolean-group-field-dynamic"]')
    const checkboxes = dynamicField.locator('input[type="checkbox"]')
    const toggleButton = page.locator('[data-testid="external-toggle-button"]')

    // Test initial states
    const initialStates = []
    for (let i = 0; i < await checkboxes.count(); i++) {
      initialStates[i] = await checkboxes.nth(i).isChecked()
    }

    // Trigger external change
    await toggleButton.click()

    // At least some checkboxes should reflect the external change
    let changesDetected = false
    for (let i = 0; i < await checkboxes.count(); i++) {
      const currentState = await checkboxes.nth(i).isChecked()
      if (currentState !== initialStates[i]) {
        changesDetected = true
        break
      }
    }
    
    expect(changesDetected).toBe(true)
  })

  test('maintains state consistency across interactions', async ({ page }) => {
    const consistencyField = page.locator('[data-testid="boolean-group-field-consistency"]')
    const checkboxes = consistencyField.locator('input[type="checkbox"]')
    const labels = consistencyField.locator('label')

    // Test multiple interaction methods produce consistent results
    const firstCheckbox = checkboxes.nth(0)
    const firstLabel = labels.nth(0)
    
    // Method 1: Direct checkbox click
    const initialState = await firstCheckbox.isChecked()
    await firstCheckbox.click()
    const stateAfterCheckbox = await firstCheckbox.isChecked()
    expect(stateAfterCheckbox).toBe(!initialState)

    // Method 2: Label click
    await firstLabel.click()
    const stateAfterLabel = await firstCheckbox.isChecked()
    expect(stateAfterLabel).toBe(initialState) // Should toggle back

    // Method 3: Keyboard interaction
    await firstCheckbox.focus()
    await page.keyboard.press('Space')
    const stateAfterKeyboard = await firstCheckbox.isChecked()
    expect(stateAfterKeyboard).toBe(!initialState) // Should toggle again

    // Method 4: Programmatic check/uncheck
    await firstCheckbox.check()
    await expect(firstCheckbox).toBeChecked()

    await firstCheckbox.uncheck()
    await expect(firstCheckbox).not.toBeChecked()
  })

  test('handles complex option configurations', async ({ page }) => {
    const complexField = page.locator('[data-testid="boolean-group-field-complex"]')
    
    // Should handle various option key formats
    await expect(complexField).toContainText('Create Posts')
    await expect(complexField).toContainText('Edit Posts')
    await expect(complexField).toContainText('Delete Posts')
    await expect(complexField).toContainText('Manage Users')
    await expect(complexField).toContainText('System Admin')

    // All checkboxes should be functional regardless of key format
    const checkboxes = complexField.locator('input[type="checkbox"]')
    for (let i = 0; i < await checkboxes.count(); i++) {
      const checkbox = checkboxes.nth(i)
      await expect(checkbox).toBeVisible()
      
      const initialState = await checkbox.isChecked()
      await checkbox.click()
      await expect(checkbox).toBeChecked({ checked: !initialState })
    }
  })
})
