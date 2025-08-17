import { test, expect } from '@playwright/test'

/**
 * Hidden Field E2E Tests (Playwright)
 *
 * End-to-end tests for the Hidden field component using Playwright.
 * These tests validate the complete user workflow and browser behavior.
 *
 * Note: These tests are written but not executed in this environment.
 * They would be run in a real browser environment with the full application.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

test.describe('Hidden Field E2E', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to a test page with Hidden fields
    await page.goto('/admin/test-hidden-fields')
  })

  test('hidden field is not visible to users', async ({ page }) => {
    // Hidden fields should not be visible in the UI
    const hiddenInput = page.locator('input[type="hidden"][name="user_id"]')
    
    // Should exist in DOM but not be visible
    await expect(hiddenInput).toBeAttached()
    await expect(hiddenInput).not.toBeVisible()
    
    // Should have correct attributes
    await expect(hiddenInput).toHaveAttribute('type', 'hidden')
    await expect(hiddenInput).toHaveAttribute('name', 'user_id')
  })

  test('hidden field submits with form data', async ({ page }) => {
    // Fill out a form with hidden fields
    await page.fill('input[name="name"]', 'Test User')
    
    // Hidden field should have a value (set by server or JavaScript)
    const hiddenInput = page.locator('input[type="hidden"][name="user_id"]')
    await expect(hiddenInput).toHaveValue(/\d+/) // Should have numeric value
    
    // Submit form and verify hidden field is included
    await page.click('button[type="submit"]')
    
    // Check that form submission includes hidden field
    // (This would be verified through network monitoring or server response)
    await page.waitForURL('**/success')
  })

  test('csrf token hidden field works correctly', async ({ page }) => {
    // CSRF token should be present and have correct format
    const csrfInput = page.locator('input[type="hidden"][name="_token"]')
    
    await expect(csrfInput).toBeAttached()
    await expect(csrfInput).toHaveValue(/^[a-zA-Z0-9]{40,}$/) // Token format
    
    // Form submission should succeed with valid CSRF token
    await page.fill('input[name="title"]', 'Test Post')
    await page.click('button[type="submit"]')
    
    // Should not get CSRF error
    await expect(page.locator('.error')).not.toContainText('CSRF')
  })

  test('hidden field with dynamic default value', async ({ page }) => {
    // Test hidden field that gets value from JavaScript/server
    const timestampInput = page.locator('input[type="hidden"][name="timestamp"]')
    
    await expect(timestampInput).toBeAttached()
    
    // Value should be set (timestamp format)
    await expect(timestampInput).toHaveValue(/^\d{4}-\d{2}-\d{2}/)
    
    // Refresh page and verify new timestamp
    await page.reload()
    const newTimestamp = await timestampInput.inputValue()
    expect(newTimestamp).toMatch(/^\d{4}-\d{2}-\d{2}/)
  })

  test('multiple hidden fields in same form', async ({ page }) => {
    // Test form with multiple hidden fields
    const hiddenFields = [
      { name: 'user_id', pattern: /^\d+$/ },
      { name: '_token', pattern: /^[a-zA-Z0-9]{40,}$/ },
      { name: 'form_type', pattern: /^[a-z_]+$/ }
    ]
    
    for (const field of hiddenFields) {
      const input = page.locator(`input[type="hidden"][name="${field.name}"]`)
      await expect(input).toBeAttached()
      await expect(input).not.toBeVisible()
      await expect(input).toHaveValue(field.pattern)
    }
    
    // Submit form with all hidden fields
    await page.fill('input[name="content"]', 'Test content')
    await page.click('button[type="submit"]')
    
    await expect(page.locator('.success')).toBeVisible()
  })

  test('hidden field accessibility compliance', async ({ page }) => {
    // Hidden fields should not interfere with accessibility
    const hiddenInput = page.locator('input[type="hidden"][name="user_id"]')
    
    // Should not be focusable
    await expect(hiddenInput).not.toBeFocused()
    
    // Tab navigation should skip hidden fields
    await page.keyboard.press('Tab')
    const focusedElement = await page.locator(':focus')
    await expect(focusedElement).not.toHaveAttribute('type', 'hidden')
    
    // Screen readers should ignore hidden fields
    // (This would require specialized accessibility testing tools)
  })

  test('hidden field in create form scenario', async ({ page }) => {
    // Navigate to create form
    await page.goto('/admin/users/create')
    
    // Hidden field for user type should be present
    const userTypeInput = page.locator('input[type="hidden"][name="user_type"]')
    await expect(userTypeInput).toHaveValue('standard')
    
    // Fill visible form fields
    await page.fill('input[name="name"]', 'John Doe')
    await page.fill('input[name="email"]', 'john@example.com')
    
    // Submit and verify hidden field was included
    await page.click('button[type="submit"]')
    await expect(page.locator('.success')).toContainText('User created')
  })

  test('hidden field in edit form scenario', async ({ page }) => {
    // Navigate to edit form
    await page.goto('/admin/users/1/edit')
    
    // Hidden field for user ID should be present
    const userIdInput = page.locator('input[type="hidden"][name="id"]')
    await expect(userIdInput).toHaveValue('1')
    
    // Modify visible fields
    await page.fill('input[name="name"]', 'Jane Doe')
    
    // Submit and verify hidden field preserved the ID
    await page.click('button[type="submit"]')
    await expect(page.locator('.success')).toContainText('User updated')
    
    // Verify we're still on the same user (ID preserved)
    await expect(page).toHaveURL(/\/users\/1\/edit/)
  })

  test('hidden field with validation errors', async ({ page }) => {
    // Test form with required hidden field that might be missing
    await page.goto('/admin/test-validation')
    
    // Remove hidden field value via JavaScript (simulate tampering)
    await page.evaluate(() => {
      const hiddenInput = document.querySelector('input[name="required_hidden"]')
      if (hiddenInput) hiddenInput.value = ''
    })
    
    // Submit form
    await page.click('button[type="submit"]')
    
    // Should show validation error
    await expect(page.locator('.error')).toContainText('required')
  })

  test('hidden field performance with large forms', async ({ page }) => {
    // Test form with many hidden fields (performance test)
    await page.goto('/admin/test-large-form')
    
    // Count hidden fields
    const hiddenInputs = page.locator('input[type="hidden"]')
    const count = await hiddenInputs.count()
    expect(count).toBeGreaterThan(10) // Should have many hidden fields
    
    // Form should still be responsive
    const startTime = Date.now()
    await page.fill('input[name="title"]', 'Performance Test')
    await page.click('button[type="submit"]')
    const endTime = Date.now()
    
    // Should complete within reasonable time
    expect(endTime - startTime).toBeLessThan(5000) // 5 seconds max
  })

  test('hidden field with dynamic updates', async ({ page }) => {
    // Test hidden field that updates based on other form inputs
    await page.goto('/admin/test-dynamic-hidden')
    
    const categorySelect = page.locator('select[name="category"]')
    const hiddenInput = page.locator('input[type="hidden"][name="category_id"]')
    
    // Change category and verify hidden field updates
    await categorySelect.selectOption('technology')
    await expect(hiddenInput).toHaveValue('1')
    
    await categorySelect.selectOption('business')
    await expect(hiddenInput).toHaveValue('2')
    
    // Submit with final value
    await page.click('button[type="submit"]')
    await expect(page.locator('.success')).toBeVisible()
  })

  test('hidden field browser compatibility', async ({ page, browserName }) => {
    // Test hidden field works across different browsers
    const hiddenInput = page.locator('input[type="hidden"][name="browser_test"]')
    
    await expect(hiddenInput).toBeAttached()
    await expect(hiddenInput).toHaveAttribute('type', 'hidden')
    
    // Set value via JavaScript (simulating dynamic behavior)
    await page.evaluate((browser) => {
      const input = document.querySelector('input[name="browser_test"]')
      if (input) input.value = `tested-on-${browser}`
    }, browserName)
    
    await expect(hiddenInput).toHaveValue(`tested-on-${browserName}`)
    
    // Submit and verify
    await page.click('button[type="submit"]')
    await expect(page.locator('.success')).toContainText('Browser test passed')
  })

  test('hidden field security scenarios', async ({ page }) => {
    // Test that hidden fields maintain security
    const tokenInput = page.locator('input[type="hidden"][name="security_token"]')
    
    // Should have secure token
    await expect(tokenInput).toHaveValue(/^[a-f0-9]{64}$/)
    
    // Attempt to modify via dev tools (simulate attack)
    await page.evaluate(() => {
      const input = document.querySelector('input[name="security_token"]')
      if (input) input.value = 'malicious_value'
    })
    
    // Submit form
    await page.click('button[type="submit"]')
    
    // Should be rejected by server
    await expect(page.locator('.error')).toContainText('Invalid token')
  })

  test('hidden field with nova-style patterns', async ({ page }) => {
    // Test Nova-compatible usage patterns
    await page.goto('/admin/test-nova-patterns')
    
    // Test basic hidden field
    const slugInput = page.locator('input[type="hidden"][name="slug"]')
    await expect(slugInput).toBeAttached()
    
    // Test hidden field with default
    const typeInput = page.locator('input[type="hidden"][name="type"]')
    await expect(typeInput).toHaveValue('user')
    
    // Test hidden field with callable default (resolved server-side)
    const userIdInput = page.locator('input[type="hidden"][name="user_id"]')
    await expect(userIdInput).toHaveValue(/^\d+$/)
    
    // All should submit successfully
    await page.fill('input[name="title"]', 'Nova Pattern Test')
    await page.click('button[type="submit"]')
    await expect(page.locator('.success')).toContainText('Nova patterns work')
  })
})
