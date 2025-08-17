import { test, expect } from '@playwright/test'

/**
 * Country Field Playwright E2E Tests
 *
 * Focused UI tests for the Country field: rendering, searchable behavior,
 * selection updates, and persistence via test endpoints.
 */

test.describe('Country Field E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the Country field demo page (app-level route)
    await page.goto('/playwright/test-country-field')
  })

  test('renders country field and shows options', async ({ page }) => {
    const countryField = page.locator('[data-testid="country-field"]')
    await expect(countryField).toBeVisible()

    // Ensure basic options are present in the DOM (implementation-dependent)
    await expect(countryField).toContainText('United States')
    await expect(countryField).toContainText('Canada')
    await expect(countryField).toContainText('United Kingdom')
  })

  test('supports searchable behavior when enabled', async ({ page }) => {
    const countryField = page.locator('[data-testid="country-field-searchable"]')
    await expect(countryField).toBeVisible()

    const input = countryField.locator('input[type="search"], input[type="text"]')
    await input.fill('Uni')

    // Expect filtered options include United States and United Kingdom
    await expect(countryField).toContainText('United States')
    await expect(countryField).toContainText('United Kingdom')
  })

  test('allows selecting a country and persists value on save', async ({ page }) => {
    const countryField = page.locator('[data-testid="country-field-form"]')
    await expect(countryField).toBeVisible()

    // Open dropdown and select Canada (CA)
    await countryField.click()
    await page.getByText('Canada').click()

    // Submit the form
    await page.getByRole('button', { name: 'Save' }).click()

    // Expect a success message
    await expect(page.locator('[data-testid="save-success"]')).toBeVisible()

    // Confirm persisted display
    await expect(page.locator('[data-testid="country-display"]')).toContainText('Canada')
  })
})

