import { test, expect } from '@playwright/test'

/**
 * Heading Field Playwright E2E Tests
 *
 * Tests the complete end-to-end functionality of Heading fields
 * in the browser environment, including visual rendering,
 * user interactions, and Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

test.describe('Heading Field E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Set up test environment
    await page.goto('/admin-panel/test-heading-field')
  })

  test('renders basic text heading field correctly', async ({ page }) => {
    // Test basic heading field rendering
    const headingField = page.locator('[data-testid="heading-field-basic"]')
    await expect(headingField).toBeVisible()

    // Test heading content
    const headingContent = headingField.locator('.heading-content')
    await expect(headingContent).toBeVisible()
    await expect(headingContent).toContainText('User Information')

    // Test heading styling
    const headingFieldContainer = headingField.locator('.heading-field')
    await expect(headingFieldContainer).toHaveClass(/py-4/)
    await expect(headingFieldContainer).toHaveClass(/border-b/)
    await expect(headingFieldContainer).toHaveClass(/border-gray-200/)

    // Test text styling
    const textContent = headingContent.locator('.heading-text')
    await expect(textContent).toBeVisible()
    await expect(textContent).toHaveClass(/text-lg/)
    await expect(textContent).toHaveClass(/font-semibold/)
  })

  test('renders HTML heading field correctly', async ({ page }) => {
    // Test HTML heading field rendering
    const htmlHeadingField = page.locator('[data-testid="heading-field-html"]')
    await expect(htmlHeadingField).toBeVisible()

    // Test HTML content is rendered
    const htmlContent = htmlHeadingField.locator('.heading-content')
    await expect(htmlContent).toBeVisible()

    // Test specific HTML elements are rendered
    const heading = htmlContent.locator('h3')
    await expect(heading).toBeVisible()
    await expect(heading).toContainText('Important Section')
    await expect(heading).toHaveClass(/text-lg/)
    await expect(heading).toHaveClass(/font-medium/)

    const paragraph = htmlContent.locator('p')
    await expect(paragraph).toBeVisible()
    await expect(paragraph).toContainText('Please review carefully')

    // Test background styling is applied
    const container = htmlContent.locator('div').first()
    await expect(container).toHaveClass(/bg-blue-100/)
    await expect(container).toHaveClass(/p-4/)
  })

  test('displays complex HTML structures correctly', async ({ page }) => {
    // Test complex HTML heading with icons and styling
    const complexHeadingField = page.locator('[data-testid="heading-field-complex"]')
    await expect(complexHeadingField).toBeVisible()

    const htmlContent = complexHeadingField.locator('.heading-content')
    
    // Test alert-style container
    const alertContainer = htmlContent.locator('.rounded-md')
    await expect(alertContainer).toBeVisible()
    await expect(alertContainer).toHaveClass(/bg-blue-50/)
    await expect(alertContainer).toHaveClass(/p-4/)

    // Test icon is rendered
    const icon = htmlContent.locator('svg')
    await expect(icon).toBeVisible()
    await expect(icon).toHaveClass(/h-5/)
    await expect(icon).toHaveClass(/w-5/)
    await expect(icon).toHaveClass(/text-blue-400/)

    // Test text content
    await expect(htmlContent).toContainText('Information about this form section')
    
    // Test link is rendered and styled
    const link = htmlContent.locator('a')
    await expect(link).toBeVisible()
    await expect(link).toContainText('Learn more')
    await expect(link).toHaveClass(/font-medium/)
    await expect(link).toHaveClass(/text-blue-700/)
  })

  test('handles warning and danger styled headings', async ({ page }) => {
    // Test warning heading
    const warningHeading = page.locator('[data-testid="heading-field-warning"]')
    await expect(warningHeading).toBeVisible()

    const warningContent = warningHeading.locator('.heading-content')
    const warningContainer = warningContent.locator('.border-l-4')
    await expect(warningContainer).toBeVisible()
    await expect(warningContainer).toHaveClass(/border-yellow-400/)
    await expect(warningContainer).toHaveClass(/bg-yellow-50/)

    await expect(warningContent).toContainText('Warning')
    await expect(warningContent).toContainText('This action cannot be undone')

    // Test danger heading
    const dangerHeading = page.locator('[data-testid="heading-field-danger"]')
    await expect(dangerHeading).toBeVisible()

    const dangerContent = dangerHeading.locator('.heading-content')
    const dangerContainer = dangerContent.locator('.bg-red-50')
    await expect(dangerContainer).toBeVisible()
    await expect(dangerContainer).toHaveClass(/border-red-200/)

    await expect(dangerContent).toContainText('Danger Zone')
    await expect(dangerContent).toContainText('Actions in this section cannot be undone')
  })

  test('respects dark theme styling', async ({ page }) => {
    // Switch to dark theme
    await page.locator('[data-testid="theme-toggle"]').click()

    // Test heading field in dark theme
    const headingField = page.locator('[data-testid="heading-field-basic"]')
    await expect(headingField).toBeVisible()

    const headingFieldContainer = headingField.locator('.heading-field')
    await expect(headingFieldContainer).toHaveClass(/border-gray-700/)

    // Test text color in dark theme
    const headingContent = headingField.locator('.heading-content')
    await expect(headingContent).toHaveClass(/text-gray-100/)
  })

  test('does not interfere with form interactions', async ({ page }) => {
    // Test that heading fields don't interfere with form functionality
    const form = page.locator('[data-testid="test-form"]')
    await expect(form).toBeVisible()

    // Test that heading fields are present
    const headingField = form.locator('[data-testid="heading-field-basic"]')
    await expect(headingField).toBeVisible()

    // Test that form inputs still work
    const nameInput = form.locator('input[name="name"]')
    await expect(nameInput).toBeVisible()
    await nameInput.fill('Test User')
    await expect(nameInput).toHaveValue('Test User')

    const emailInput = form.locator('input[name="email"]')
    await expect(emailInput).toBeVisible()
    await emailInput.fill('test@example.com')
    await expect(emailInput).toHaveValue('test@example.com')

    // Test form submission works
    const submitButton = form.locator('button[type="submit"]')
    await expect(submitButton).toBeVisible()
    await submitButton.click()

    // Verify form was submitted (check for success message or redirect)
    await expect(page.locator('[data-testid="form-success"]')).toBeVisible()
  })

  test('displays correctly in different viewport sizes', async ({ page }) => {
    // Test mobile viewport
    await page.setViewportSize({ width: 375, height: 667 })
    
    const headingField = page.locator('[data-testid="heading-field-basic"]')
    await expect(headingField).toBeVisible()
    
    const headingContent = headingField.locator('.heading-content')
    await expect(headingContent).toBeVisible()
    await expect(headingContent).toContainText('User Information')

    // Test tablet viewport
    await page.setViewportSize({ width: 768, height: 1024 })
    await expect(headingField).toBeVisible()
    await expect(headingContent).toBeVisible()

    // Test desktop viewport
    await page.setViewportSize({ width: 1920, height: 1080 })
    await expect(headingField).toBeVisible()
    await expect(headingContent).toBeVisible()
  })

  test('handles accessibility requirements', async ({ page }) => {
    // Test heading field accessibility
    const headingField = page.locator('[data-testid="heading-field-basic"]')
    await expect(headingField).toBeVisible()

    // Test that heading content is accessible
    const headingContent = headingField.locator('.heading-content')
    
    // Test keyboard navigation doesn't get stuck on heading fields
    await page.keyboard.press('Tab')
    const focusedElement = page.locator(':focus')
    
    // Heading fields should not receive focus since they're display-only
    await expect(focusedElement).not.toBe(headingContent)

    // Test screen reader compatibility (heading structure)
    const htmlHeading = page.locator('[data-testid="heading-field-html"] h3')
    await expect(htmlHeading).toBeVisible()
    
    // Test that HTML headings maintain proper hierarchy
    await expect(htmlHeading).toHaveAttribute('class', /text-lg/)
  })

  test('renders correctly with various content types', async ({ page }) => {
    // Test heading with special characters
    const specialCharsHeading = page.locator('[data-testid="heading-field-special-chars"]')
    await expect(specialCharsHeading).toBeVisible()
    await expect(specialCharsHeading).toContainText('Données & Paramètres')

    // Test heading with numbers and symbols
    const numbersHeading = page.locator('[data-testid="heading-field-numbers"]')
    await expect(numbersHeading).toBeVisible()
    await expect(numbersHeading).toContainText('Section 1.2.3 - Configuration')

    // Test heading with HTML entities
    const entitiesHeading = page.locator('[data-testid="heading-field-entities"]')
    await expect(entitiesHeading).toBeVisible()
    await expect(entitiesHeading).toContainText('Price: €100 & up')
  })

  test('maintains visual consistency across different field types', async ({ page }) => {
    // Test that heading fields maintain consistent spacing with other fields
    const form = page.locator('[data-testid="mixed-fields-form"]')
    await expect(form).toBeVisible()

    // Test heading field spacing
    const headingField = form.locator('[data-testid="heading-field-basic"]')
    await expect(headingField).toBeVisible()

    // Test that heading field has proper margins/padding relative to other fields
    const textField = form.locator('[data-testid="text-field"]')
    await expect(textField).toBeVisible()

    const selectField = form.locator('[data-testid="select-field"]')
    await expect(selectField).toBeVisible()

    // Verify visual hierarchy is maintained
    const headingFieldContainer = headingField.locator('.heading-field')
    await expect(headingFieldContainer).toHaveClass(/mb-6/)
  })

  test('handles long content gracefully', async ({ page }) => {
    // Test heading with very long text content
    const longHeading = page.locator('[data-testid="heading-field-long"]')
    await expect(longHeading).toBeVisible()

    const longContent = longHeading.locator('.heading-content')
    await expect(longContent).toBeVisible()
    
    // Test that long content doesn't break layout
    await expect(longContent).toContainText('This is a very long heading')
    
    // Test that content wraps properly
    const boundingBox = await longContent.boundingBox()
    expect(boundingBox.width).toBeLessThan(page.viewportSize().width)
  })

  test('works correctly in nested form contexts', async ({ page }) => {
    // Test heading fields in complex nested forms
    const nestedForm = page.locator('[data-testid="nested-form"]')
    await expect(nestedForm).toBeVisible()

    // Test main section heading
    const mainHeading = nestedForm.locator('[data-testid="heading-main-section"]')
    await expect(mainHeading).toBeVisible()
    await expect(mainHeading).toContainText('Main Information')

    // Test subsection heading
    const subHeading = nestedForm.locator('[data-testid="heading-sub-section"]')
    await expect(subHeading).toBeVisible()
    await expect(subHeading).toContainText('Additional Details')

    // Test that both headings render correctly
    const mainContent = mainHeading.locator('.heading-content')
    const subContent = subHeading.locator('.heading-content')
    
    await expect(mainContent).toBeVisible()
    await expect(subContent).toBeVisible()
  })
})
