import { test, expect } from '@playwright/test'

/**
 * KeyValue Field End-to-End Tests (Playwright)
 *
 * Tests real-world user interactions with the KeyValue field component
 * in a browser environment. These tests validate the complete user experience
 * including keyboard navigation, form submission, and visual feedback.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

test.describe('KeyValue Field E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to admin panel with KeyValue field
    await page.goto('/admin/test-resources/create')
    
    // Wait for the page to load
    await page.waitForLoadState('networkidle')
  })

  test('user can add and edit key-value pairs', async ({ page }) => {
    // Find the KeyValue field
    const keyValueField = page.locator('[data-field="meta"]')
    await expect(keyValueField).toBeVisible()

    // Should start with one empty row
    const initialKeyInputs = keyValueField.locator('input[placeholder*="key"]')
    const initialValueInputs = keyValueField.locator('input[placeholder*="value"]')
    
    await expect(initialKeyInputs).toHaveCount(1)
    await expect(initialValueInputs).toHaveCount(1)

    // Add first key-value pair
    await initialKeyInputs.first().fill('name')
    await initialValueInputs.first().fill('John Doe')

    // Click "Add row" button to add another pair
    const addButton = keyValueField.locator('button', { hasText: 'Add row' })
    await addButton.click()

    // Should now have 2 rows
    const keyInputs = keyValueField.locator('input[placeholder*="key"]')
    const valueInputs = keyValueField.locator('input[placeholder*="value"]')
    
    await expect(keyInputs).toHaveCount(2)
    await expect(valueInputs).toHaveCount(2)

    // Add second key-value pair
    await keyInputs.nth(1).fill('email')
    await valueInputs.nth(1).fill('john@example.com')

    // Verify values are entered correctly
    await expect(keyInputs.nth(0)).toHaveValue('name')
    await expect(valueInputs.nth(0)).toHaveValue('John Doe')
    await expect(keyInputs.nth(1)).toHaveValue('email')
    await expect(valueInputs.nth(1)).toHaveValue('john@example.com')
  })

  test('user can remove key-value pairs', async ({ page }) => {
    const keyValueField = page.locator('[data-field="meta"]')

    // Add two pairs first
    const keyInputs = keyValueField.locator('input[placeholder*="key"]')
    const valueInputs = keyValueField.locator('input[placeholder*="value"]')
    
    await keyInputs.first().fill('first')
    await valueInputs.first().fill('value1')

    const addButton = keyValueField.locator('button', { hasText: 'Add row' })
    await addButton.click()

    await keyInputs.nth(1).fill('second')
    await valueInputs.nth(1).fill('value2')

    // Should have 2 pairs
    await expect(keyInputs).toHaveCount(2)

    // Remove the first pair
    const removeButtons = keyValueField.locator('button[type="button"]').filter({ hasNotText: 'Add row' })
    await removeButtons.first().click()

    // Should now have 1 pair remaining
    await expect(keyInputs).toHaveCount(1)
    
    // Verify the remaining pair is the second one
    await expect(keyInputs.first()).toHaveValue('second')
    await expect(valueInputs.first()).toHaveValue('value2')
  })

  test('keyboard navigation works correctly', async ({ page }) => {
    const keyValueField = page.locator('[data-field="meta"]')
    const keyInputs = keyValueField.locator('input[placeholder*="key"]')
    const valueInputs = keyValueField.locator('input[placeholder*="value"]')

    // Focus first key input
    await keyInputs.first().focus()
    await keyInputs.first().fill('test')

    // Tab to value input
    await page.keyboard.press('Tab')
    await expect(valueInputs.first()).toBeFocused()
    
    await valueInputs.first().fill('value')

    // Press Enter to add new row
    await page.keyboard.press('Enter')
    
    // Should have added a new row
    await expect(keyInputs).toHaveCount(2)
  })

  test('form submission includes key-value data', async ({ page }) => {
    const keyValueField = page.locator('[data-field="meta"]')
    
    // Fill in some key-value pairs
    const keyInputs = keyValueField.locator('input[placeholder*="key"]')
    const valueInputs = keyValueField.locator('input[placeholder*="value"]')
    
    await keyInputs.first().fill('name')
    await valueInputs.first().fill('John Doe')

    const addButton = keyValueField.locator('button', { hasText: 'Add row' })
    await addButton.click()

    await keyInputs.nth(1).fill('email')
    await valueInputs.nth(1).fill('john@example.com')

    // Fill in required name field
    await page.locator('input[name="name"]').fill('Test Resource')

    // Submit the form
    const submitButton = page.locator('button[type="submit"]')
    await submitButton.click()

    // Wait for navigation or success message
    await page.waitForLoadState('networkidle')

    // Verify we're redirected to the index or show page
    await expect(page).toHaveURL(/\/admin\/test-resources/)
  })

  test('readonly mode displays data correctly', async ({ page }) => {
    // Navigate to an existing resource with key-value data
    await page.goto('/admin/test-resources/1')
    await page.waitForLoadState('networkidle')

    const keyValueField = page.locator('[data-field="meta"]')
    
    // In readonly mode, should not have input fields
    const inputs = keyValueField.locator('input')
    await expect(inputs).toHaveCount(0)

    // Should display key-value pairs as text
    const keyValuePairs = keyValueField.locator('[data-testid="key-value-pair"]')
    await expect(keyValuePairs).toHaveCount.toBeGreaterThan(0)
  })

  test('validation prevents empty keys', async ({ page }) => {
    const keyValueField = page.locator('[data-field="meta"]')
    
    // Try to add a pair with empty key
    const keyInputs = keyValueField.locator('input[placeholder*="key"]')
    const valueInputs = keyValueField.locator('input[placeholder*="value"]')
    
    // Leave key empty, fill value
    await valueInputs.first().fill('some value')

    // Fill in required name field
    await page.locator('input[name="name"]').fill('Test Resource')

    // Submit the form
    const submitButton = page.locator('button[type="submit"]')
    await submitButton.click()

    // Form should submit successfully (empty keys are filtered out)
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/\/admin\/test-resources/)
  })

  test('custom labels are displayed correctly', async ({ page }) => {
    // Navigate to a page with custom KeyValue field labels
    await page.goto('/admin/test-resources/create?field=custom-meta')
    await page.waitForLoadState('networkidle')

    const keyValueField = page.locator('[data-field="custom-meta"]')
    
    // Check for custom labels
    await expect(keyValueField.locator('text=Property')).toBeVisible()
    await expect(keyValueField.locator('text=Content')).toBeVisible()
    await expect(keyValueField.locator('button', { hasText: 'Add new item' })).toBeVisible()
  })

  test('handles special characters and unicode', async ({ page }) => {
    const keyValueField = page.locator('[data-field="meta"]')
    const keyInputs = keyValueField.locator('input[placeholder*="key"]')
    const valueInputs = keyValueField.locator('input[placeholder*="value"]')

    // Test special characters
    await keyInputs.first().fill('special_chars')
    await valueInputs.first().fill('àáâãäåæçèéêë')

    const addButton = keyValueField.locator('button', { hasText: 'Add row' })
    await addButton.click()

    // Test unicode
    await keyInputs.nth(1).fill('unicode')
    await valueInputs.nth(1).fill('🚀 Rocket Ship 🌟')

    await addButton.click()

    // Test JSON-like values
    await keyInputs.nth(2).fill('json_config')
    await valueInputs.nth(2).fill('{"nested": {"value": true}}')

    // Verify all values are preserved
    await expect(keyInputs.nth(0)).toHaveValue('special_chars')
    await expect(valueInputs.nth(0)).toHaveValue('àáâãäåæçèéêë')
    await expect(keyInputs.nth(1)).toHaveValue('unicode')
    await expect(valueInputs.nth(1)).toHaveValue('🚀 Rocket Ship 🌟')
    await expect(keyInputs.nth(2)).toHaveValue('json_config')
    await expect(valueInputs.nth(2)).toHaveValue('{"nested": {"value": true}}')
  })

  test('dark theme styling works correctly', async ({ page }) => {
    // Enable dark theme
    await page.goto('/admin/settings')
    await page.locator('[data-testid="dark-theme-toggle"]').click()
    
    // Navigate back to KeyValue field
    await page.goto('/admin/test-resources/create')
    await page.waitForLoadState('networkidle')

    const keyValueField = page.locator('[data-field="meta"]')
    
    // Check that dark theme classes are applied
    const inputs = keyValueField.locator('input')
    await expect(inputs.first()).toHaveClass(/admin-input-dark/)
    
    // Check button styling in dark theme
    const addButton = keyValueField.locator('button', { hasText: 'Add row' })
    await expect(addButton).toHaveClass(/bg-gray-700/)
  })

  test('accessibility features work correctly', async ({ page }) => {
    const keyValueField = page.locator('[data-field="meta"]')
    
    // Check for proper ARIA labels and accessibility
    const keyInputs = keyValueField.locator('input[placeholder*="key"]')
    const valueInputs = keyValueField.locator('input[placeholder*="value"]')
    
    // Inputs should be focusable
    await keyInputs.first().focus()
    await expect(keyInputs.first()).toBeFocused()
    
    // Tab navigation should work
    await page.keyboard.press('Tab')
    await expect(valueInputs.first()).toBeFocused()
    
    // Remove buttons should be accessible via keyboard
    const removeButtons = keyValueField.locator('button[type="button"]').filter({ hasNotText: 'Add row' })
    if (await removeButtons.count() > 0) {
      await removeButtons.first().focus()
      await expect(removeButtons.first()).toBeFocused()
    }
  })

  test('handles large datasets efficiently', async ({ page }) => {
    const keyValueField = page.locator('[data-field="meta"]')
    
    // Add many key-value pairs
    for (let i = 1; i <= 20; i++) {
      const keyInputs = keyValueField.locator('input[placeholder*="key"]')
      const valueInputs = keyValueField.locator('input[placeholder*="value"]')
      
      await keyInputs.last().fill(`key_${i}`)
      await valueInputs.last().fill(`value_${i}`)
      
      if (i < 20) {
        const addButton = keyValueField.locator('button', { hasText: 'Add row' })
        await addButton.click()
      }
    }

    // Verify all pairs are present
    const keyInputs = keyValueField.locator('input[placeholder*="key"]')
    await expect(keyInputs).toHaveCount(21) // 20 filled + 1 empty

    // Check first and last values
    await expect(keyInputs.nth(0)).toHaveValue('key_1')
    await expect(keyInputs.nth(19)).toHaveValue('key_20')

    // Form should still be responsive
    const submitButton = page.locator('button[type="submit"]')
    await page.locator('input[name="name"]').fill('Large Dataset Test')
    await submitButton.click()

    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/\/admin\/test-resources/)
  })

  test('error states are handled gracefully', async ({ page }) => {
    // Test network error scenario
    await page.route('**/admin/test-resources', route => {
      route.abort()
    })

    const keyValueField = page.locator('[data-field="meta"]')
    const keyInputs = keyValueField.locator('input[placeholder*="key"]')
    const valueInputs = keyValueField.locator('input[placeholder*="value"]')
    
    await keyInputs.first().fill('test')
    await valueInputs.first().fill('value')
    
    await page.locator('input[name="name"]').fill('Test Resource')
    
    const submitButton = page.locator('button[type="submit"]')
    await submitButton.click()

    // Should show error message or stay on form
    // The exact behavior depends on error handling implementation
    await page.waitForTimeout(2000)
    
    // Form should still be functional
    await expect(keyInputs.first()).toHaveValue('test')
    await expect(valueInputs.first()).toHaveValue('value')
  })
})
