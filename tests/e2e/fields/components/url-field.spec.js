import { test, expect } from '@playwright/test'

test.describe('URLField E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the admin panel URL field test page
    await page.goto('/admin/test/url-field')
  })

  test('renders URL field with proper input type and placeholder', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    
    await expect(urlInput).toBeVisible()
    await expect(urlInput).toHaveAttribute('placeholder', 'https://example.com')
  })

  test('accepts and displays valid URLs', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    const testUrl = 'https://laravel.com'
    
    await urlInput.fill(testUrl)
    await expect(urlInput).toHaveValue(testUrl)
  })

  test('handles complex URL formats', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    const complexUrls = [
      'https://sub.domain.co.uk/path?query=value&other=test#anchor',
      'http://localhost:3000/api/v1/users',
      'https://192.168.1.1:8080/admin',
      'ftp://files.example.com/documents'
    ]

    for (const url of complexUrls) {
      await urlInput.fill(url)
      await expect(urlInput).toHaveValue(url)
    }
  })

  test('handles international domain names', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    const internationalUrls = [
      'https://münchen.de',
      'https://例え.テスト'
    ]

    for (const url of internationalUrls) {
      await urlInput.fill(url)
      await expect(urlInput).toHaveValue(url)
    }
  })

  test('clears field when empty string is entered', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    
    // First enter a URL
    await urlInput.fill('https://example.com')
    await expect(urlInput).toHaveValue('https://example.com')
    
    // Then clear it
    await urlInput.fill('')
    await expect(urlInput).toHaveValue('')
  })

  test('maintains focus and blur behavior', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    
    await urlInput.focus()
    await expect(urlInput).toBeFocused()
    
    await urlInput.blur()
    await expect(urlInput).not.toBeFocused()
  })

  test('respects disabled state', async ({ page }) => {
    // Navigate to disabled field test
    await page.goto('/admin/test/url-field?disabled=true')
    
    const urlInput = page.locator('input[type="url"]')
    await expect(urlInput).toBeDisabled()
  })

  test('respects readonly state', async ({ page }) => {
    // Navigate to readonly field test
    await page.goto('/admin/test/url-field?readonly=true')
    
    const urlInput = page.locator('input[type="url"]')
    await expect(urlInput).toHaveAttribute('readonly')
  })

  test('shows link icon', async ({ page }) => {
    const linkIcon = page.locator('[data-testid="link-icon"]')
    await expect(linkIcon).toBeVisible()
  })

  test('applies dark theme classes when enabled', async ({ page }) => {
    // Enable dark theme
    await page.goto('/admin/test/url-field?theme=dark')
    
    const urlInput = page.locator('input[type="url"]')
    await expect(urlInput).toHaveClass(/admin-input-dark/)
  })

  test('handles form submission with URL data', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    const submitButton = page.locator('button[type="submit"]')
    const testUrl = 'https://nova.laravel.com'
    
    await urlInput.fill(testUrl)
    await submitButton.click()
    
    // Verify form submission (this would depend on your actual form implementation)
    await expect(page.locator('.success-message')).toContainText('URL saved successfully')
  })

  test('validates URL format on form submission', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    const submitButton = page.locator('button[type="submit"]')
    
    // Enter invalid URL
    await urlInput.fill('not-a-valid-url')
    await submitButton.click()
    
    // Check for validation error
    await expect(page.locator('.error-message')).toContainText('Please enter a valid URL')
  })

  test('handles very long URLs', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    const longUrl = 'https://example.com/' + 'a'.repeat(500)
    
    await urlInput.fill(longUrl)
    await expect(urlInput).toHaveValue(longUrl)
  })

  test('supports copy and paste operations', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    const testUrl = 'https://github.com/laravel/nova'
    
    // Simulate paste operation
    await urlInput.focus()
    await page.keyboard.type(testUrl)
    await expect(urlInput).toHaveValue(testUrl)
    
    // Select all and copy (this tests the field accepts selection)
    await page.keyboard.press('Control+a')
    await page.keyboard.press('Control+c')
    
    // Clear and paste
    await urlInput.fill('')
    await page.keyboard.press('Control+v')
    await expect(urlInput).toHaveValue(testUrl)
  })

  test('handles keyboard navigation', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    
    // Tab to the field
    await page.keyboard.press('Tab')
    await expect(urlInput).toBeFocused()
    
    // Type URL
    await page.keyboard.type('https://example.com')
    await expect(urlInput).toHaveValue('https://example.com')
    
    // Use arrow keys to navigate within the field
    await page.keyboard.press('Home')
    await page.keyboard.press('Delete')
    await expect(urlInput).toHaveValue('ttps://example.com')
  })

  test('maintains state during page interactions', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    const testUrl = 'https://laravel.com/docs'
    
    await urlInput.fill(testUrl)
    
    // Interact with other elements on the page
    await page.click('body')
    await page.keyboard.press('Tab')
    
    // Verify URL field still has the value
    await expect(urlInput).toHaveValue(testUrl)
  })

  test('works with browser autofill', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    
    // This test would depend on browser autofill being configured
    // For now, just verify the field accepts programmatic value setting
    await page.evaluate(() => {
      const input = document.querySelector('input[type="url"]')
      input.value = 'https://autofilled-url.com'
      input.dispatchEvent(new Event('input', { bubbles: true }))
    })
    
    await expect(urlInput).toHaveValue('https://autofilled-url.com')
  })

  test('handles rapid input changes', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    
    // Rapidly change the input value
    const urls = [
      'https://example.com',
      'https://test.com',
      'https://demo.com',
      'https://final.com'
    ]
    
    for (const url of urls) {
      await urlInput.fill(url)
      await page.waitForTimeout(50) // Small delay to simulate rapid typing
    }
    
    await expect(urlInput).toHaveValue('https://final.com')
  })

  test('integrates with form validation framework', async ({ page }) => {
    // Navigate to form with validation
    await page.goto('/admin/test/url-field?validation=true')
    
    const urlInput = page.locator('input[type="url"]')
    const submitButton = page.locator('button[type="submit"]')
    
    // Test required validation
    await submitButton.click()
    await expect(page.locator('.validation-error')).toContainText('URL is required')
    
    // Test URL format validation
    await urlInput.fill('invalid-url')
    await submitButton.click()
    await expect(page.locator('.validation-error')).toContainText('Please enter a valid URL')
    
    // Test successful validation
    await urlInput.fill('https://valid-url.com')
    await submitButton.click()
    await expect(page.locator('.validation-error')).not.toBeVisible()
  })

  test('works in different viewport sizes', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    
    // Test desktop size
    await page.setViewportSize({ width: 1200, height: 800 })
    await expect(urlInput).toBeVisible()
    await urlInput.fill('https://desktop-test.com')
    await expect(urlInput).toHaveValue('https://desktop-test.com')
    
    // Test tablet size
    await page.setViewportSize({ width: 768, height: 1024 })
    await expect(urlInput).toBeVisible()
    await expect(urlInput).toHaveValue('https://desktop-test.com')
    
    // Test mobile size
    await page.setViewportSize({ width: 375, height: 667 })
    await expect(urlInput).toBeVisible()
    await expect(urlInput).toHaveValue('https://desktop-test.com')
  })

  test('maintains accessibility standards', async ({ page }) => {
    const urlInput = page.locator('input[type="url"]')
    
    // Check for proper ARIA attributes
    await expect(urlInput).toHaveAttribute('type', 'url')
    
    // Check that the field is keyboard accessible
    await page.keyboard.press('Tab')
    await expect(urlInput).toBeFocused()
    
    // Check that screen readers can identify the field
    const fieldLabel = page.locator('label[for*="url"]')
    if (await fieldLabel.count() > 0) {
      await expect(fieldLabel).toBeVisible()
    }
  })
})
