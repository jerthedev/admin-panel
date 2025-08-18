import { test, expect } from '@playwright/test'

// NOTE: Playwright tests are not executed in CI. This spec documents scenarios.

test.describe('Select Field (Playwright)', () => {
  test('renders select field and allows selecting option (non-searchable)', async ({ page }) => {
    // Example route; adjust when wiring app for E2E
    // await page.goto('/admin/test-select-field')

    // const select = page.locator('[data-testid="select-field"] select')
    // await expect(select).toBeVisible()

    // await select.selectOption('published')
    // await expect(select).toHaveValue('published')

    // await page.getByRole('button', { name: 'Save' }).click()
    // await expect(page.locator('[data-testid="save-success"]')).toBeVisible()
  })

  test('renders searchable select and filters options', async ({ page }) => {
    // Example route; adjust when wiring app for E2E
    // await page.goto('/admin/test-select-field-searchable')

    // const trigger = page.locator('[data-testid="select-field-searchable"] .admin-input')
    // await trigger.click()
    // const search = page.locator('input[type="text"][placeholder="Search options..."]')
    // await expect(search).toBeVisible()
    // await search.fill('Draft')
    // await expect(page.locator('.cursor-pointer')).toContainText('Draft')
  })
})

