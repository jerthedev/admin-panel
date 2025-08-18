import { test, expect } from '@playwright/test'

/**
 * Line Field E2E Playwright Tests
 *
 * End-to-end tests for Line field Vue component using Playwright.
 * Tests real browser interactions, visual rendering, and user experience
 * in realistic application scenarios with 100% Nova v5 compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

test.describe('LineField E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to test page with Line fields
    await page.goto('/admin/test/line-fields')
    await page.waitForLoadState('networkidle')
  })

  test('displays line field with basic formatting', async ({ page }) => {
    // Test basic line field display
    const basicLine = page.locator('[data-testid="basic-line-field"]')
    await expect(basicLine).toBeVisible()
    await expect(basicLine).toContainText('Basic Line Content')
    
    // Check that it has proper CSS classes
    const lineContent = basicLine.locator('.line-content')
    await expect(lineContent).toHaveClass(/text-sm/)
    await expect(lineContent).toHaveClass(/text-gray-900/)
  })

  test('displays line field with small formatting', async ({ page }) => {
    const smallLine = page.locator('[data-testid="small-line-field"]')
    await expect(smallLine).toBeVisible()
    await expect(smallLine).toContainText('Small Text Content')
    
    // Check small formatting classes
    const lineContent = smallLine.locator('.line-content')
    await expect(lineContent).toHaveClass(/text-xs/)
    await expect(lineContent).toHaveClass(/text-gray-600/)
  })

  test('displays line field with heading formatting', async ({ page }) => {
    const headingLine = page.locator('[data-testid="heading-line-field"]')
    await expect(headingLine).toBeVisible()
    await expect(headingLine).toContainText('Heading Text Content')
    
    // Check heading formatting classes
    const lineContent = headingLine.locator('.line-content')
    await expect(lineContent).toHaveClass(/text-lg/)
    await expect(lineContent).toHaveClass(/font-semibold/)
  })

  test('displays line field with sub text formatting', async ({ page }) => {
    const subTextLine = page.locator('[data-testid="subtext-line-field"]')
    await expect(subTextLine).toBeVisible()
    await expect(subTextLine).toContainText('Sub Text Content')
    
    // Check sub text formatting classes
    const lineContent = subTextLine.locator('.line-content')
    await expect(lineContent).toHaveClass(/text-sm/)
    await expect(lineContent).toHaveClass(/text-gray-700/)
  })

  test('displays line field with HTML content', async ({ page }) => {
    const htmlLine = page.locator('[data-testid="html-line-field"]')
    await expect(htmlLine).toBeVisible()
    
    // Check that HTML is rendered
    const strongElement = htmlLine.locator('strong')
    await expect(strongElement).toBeVisible()
    await expect(strongElement).toContainText('Bold Text')
    
    const emElement = htmlLine.locator('em')
    await expect(emElement).toBeVisible()
    await expect(emElement).toContainText('Italic Text')
  })

  test('adapts to dark theme correctly', async ({ page }) => {
    // Switch to dark theme
    await page.click('[data-testid="theme-toggle"]')
    await page.waitForTimeout(500) // Wait for theme transition
    
    // Check dark theme classes are applied
    const basicLine = page.locator('[data-testid="basic-line-field"] .line-content')
    await expect(basicLine).toHaveClass(/text-gray-100/)
    
    const smallLine = page.locator('[data-testid="small-line-field"] .line-content')
    await expect(smallLine).toHaveClass(/text-gray-400/)
    
    const subTextLine = page.locator('[data-testid="subtext-line-field"] .line-content')
    await expect(subTextLine).toHaveClass(/text-gray-300/)
  })

  test('handles disabled state visually', async ({ page }) => {
    const disabledLine = page.locator('[data-testid="disabled-line-field"]')
    await expect(disabledLine).toBeVisible()
    
    // Check disabled styling
    const lineField = disabledLine.locator('.line-field')
    await expect(lineField).toHaveClass(/opacity-75/)
  })

  test('displays correctly in different viewport sizes', async ({ page }) => {
    // Test desktop view
    await page.setViewportSize({ width: 1200, height: 800 })
    const desktopLine = page.locator('[data-testid="responsive-line-field"]')
    await expect(desktopLine).toBeVisible()
    
    // Test tablet view
    await page.setViewportSize({ width: 768, height: 1024 })
    await expect(desktopLine).toBeVisible()
    
    // Test mobile view
    await page.setViewportSize({ width: 375, height: 667 })
    await expect(desktopLine).toBeVisible()
    
    // Content should remain readable at all sizes
    await expect(desktopLine).toContainText('Responsive Content')
  })

  test('maintains accessibility standards', async ({ page }) => {
    // Check that line fields are accessible
    const lineFields = page.locator('.line-field')
    const count = await lineFields.count()
    
    for (let i = 0; i < count; i++) {
      const field = lineFields.nth(i)
      
      // Check that content is readable
      const content = await field.textContent()
      expect(content).toBeTruthy()
      expect(content.trim().length).toBeGreaterThan(0)
    }
    
    // Run accessibility audit
    await expect(page).toHaveNoViolations()
  })

  test('renders correctly in resource index context', async ({ page }) => {
    await page.goto('/admin/users')
    await page.waitForLoadState('networkidle')
    
    // Check that line fields in user index are displayed
    const userStatusLines = page.locator('[data-field-type="line"][data-field-name="status"]')
    await expect(userStatusLines.first()).toBeVisible()
    
    // Check that multiple line fields can coexist
    const userRoleLines = page.locator('[data-field-type="line"][data-field-name="role"]')
    await expect(userRoleLines.first()).toBeVisible()
  })

  test('renders correctly in resource detail context', async ({ page }) => {
    await page.goto('/admin/users/1')
    await page.waitForLoadState('networkidle')
    
    // Check detailed line fields
    const nameHeading = page.locator('[data-testid="user-name-line"]')
    await expect(nameHeading).toBeVisible()
    await expect(nameHeading).toHaveClass(/text-lg/)
    await expect(nameHeading).toHaveClass(/font-semibold/)
    
    const emailSubtext = page.locator('[data-testid="user-email-line"]')
    await expect(emailSubtext).toBeVisible()
    await expect(emailSubtext).toHaveClass(/text-sm/)
    
    const statusSmall = page.locator('[data-testid="user-status-line"]')
    await expect(statusSmall).toBeVisible()
    await expect(statusSmall).toHaveClass(/text-xs/)
  })

  test('handles dynamic content updates', async ({ page }) => {
    // Navigate to dynamic content test page
    await page.goto('/admin/test/dynamic-line-fields')
    await page.waitForLoadState('networkidle')
    
    const dynamicLine = page.locator('[data-testid="dynamic-line-field"]')
    await expect(dynamicLine).toContainText('Initial Content')
    
    // Trigger content update
    await page.click('[data-testid="update-content-btn"]')
    await page.waitForTimeout(100)
    
    // Check that content updated
    await expect(dynamicLine).toContainText('Updated Content')
  })

  test('performs well with many line fields', async ({ page }) => {
    await page.goto('/admin/test/many-line-fields')
    await page.waitForLoadState('networkidle')
    
    // Measure performance
    const startTime = Date.now()
    
    // Check that all 100 line fields are rendered
    const lineFields = page.locator('.line-field')
    await expect(lineFields).toHaveCount(100)
    
    const endTime = Date.now()
    const renderTime = endTime - startTime
    
    // Should render quickly
    expect(renderTime).toBeLessThan(2000) // 2 seconds max
    
    // Check that content is correct for first and last fields
    await expect(lineFields.first()).toContainText('Line Field 1')
    await expect(lineFields.last()).toContainText('Line Field 100')
  })

  test('integrates properly with form validation', async ({ page }) => {
    await page.goto('/admin/test/line-field-validation')
    await page.waitForLoadState('networkidle')
    
    // Line fields should not interfere with form validation
    const form = page.locator('form')
    const lineField = page.locator('[data-testid="validation-line-field"]')
    const submitBtn = page.locator('[data-testid="submit-btn"]')
    
    await expect(lineField).toBeVisible()
    await expect(lineField).toContainText('Validation Context')
    
    // Submit form - line fields should not cause validation errors
    await submitBtn.click()
    
    // Check that form processes correctly (line fields don't interfere)
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible()
  })

  test('works correctly with conditional visibility', async ({ page }) => {
    await page.goto('/admin/test/conditional-line-fields')
    await page.waitForLoadState('networkidle')
    
    // Initially hidden line field
    const conditionalLine = page.locator('[data-testid="conditional-line-field"]')
    await expect(conditionalLine).not.toBeVisible()
    
    // Toggle visibility
    await page.click('[data-testid="toggle-visibility-btn"]')
    await page.waitForTimeout(100)
    
    // Now should be visible
    await expect(conditionalLine).toBeVisible()
    await expect(conditionalLine).toContainText('Conditional Content')
  })

  test('maintains visual consistency across different contexts', async ({ page }) => {
    // Test in different admin contexts
    const contexts = [
      '/admin/users',
      '/admin/users/1',
      '/admin/dashboard',
      '/admin/test/line-fields'
    ]
    
    for (const context of contexts) {
      await page.goto(context)
      await page.waitForLoadState('networkidle')
      
      const lineFields = page.locator('.line-field')
      const count = await lineFields.count()
      
      if (count > 0) {
        // Check consistent styling
        const firstField = lineFields.first()
        await expect(firstField).toHaveClass(/py-1/)
        
        const lineContent = firstField.locator('.line-content')
        await expect(lineContent).toHaveClass(/leading-relaxed/)
      }
    }
  })

  test('handles edge cases gracefully', async ({ page }) => {
    await page.goto('/admin/test/edge-case-line-fields')
    await page.waitForLoadState('networkidle')
    
    // Empty content line field
    const emptyLine = page.locator('[data-testid="empty-line-field"]')
    await expect(emptyLine).toBeVisible()
    // Should show field name as fallback
    await expect(emptyLine).toContainText('Empty Field')
    
    // Very long content line field
    const longLine = page.locator('[data-testid="long-content-line-field"]')
    await expect(longLine).toBeVisible()
    // Should handle long content without breaking layout
    const longContent = await longLine.textContent()
    expect(longContent.length).toBeGreaterThan(100)
    
    // Special characters line field
    const specialLine = page.locator('[data-testid="special-chars-line-field"]')
    await expect(specialLine).toBeVisible()
    await expect(specialLine).toContainText('Special: <>&"\'')
  })

  test('supports keyboard navigation', async ({ page }) => {
    await page.goto('/admin/test/line-fields')
    await page.waitForLoadState('networkidle')
    
    // Line fields should not interfere with keyboard navigation
    await page.keyboard.press('Tab')
    await page.keyboard.press('Tab')
    await page.keyboard.press('Tab')
    
    // Should be able to navigate past line fields without issues
    const focusedElement = await page.evaluate(() => document.activeElement.tagName)
    expect(focusedElement).toBeTruthy()
  })
})
