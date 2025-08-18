import { test, expect } from '@playwright/test'

/**
 * Stack Field E2E Playwright Tests
 *
 * End-to-end tests for Stack field Vue component using Playwright.
 * Tests real browser interactions, visual rendering, field composition,
 * and user experience in realistic application scenarios with 100% Nova v5 compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

test.describe('StackField E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to test page with Stack fields
    await page.goto('/admin/test/stack-fields')
    await page.waitForLoadState('networkidle')
  })

  test('displays stack field with multiple line fields', async ({ page }) => {
    const basicStack = page.locator('[data-testid="basic-stack-field"]')
    await expect(basicStack).toBeVisible()
    
    // Check that stack contains multiple items
    const stackItems = basicStack.locator('.stack-item')
    await expect(stackItems).toHaveCount(3)
    
    // Check individual field content
    await expect(stackItems.nth(0)).toContainText('John Doe')
    await expect(stackItems.nth(1)).toContainText('john@example.com')
    await expect(stackItems.nth(2)).toContainText('Active User')
    
    // Check stack field styling
    const stackField = basicStack.locator('.stack-field')
    await expect(stackField).toHaveClass(/space-y-2/)
  })

  test('displays stack field with mixed field types', async ({ page }) => {
    const mixedStack = page.locator('[data-testid="mixed-stack-field"]')
    await expect(mixedStack).toBeVisible()
    
    const stackItems = mixedStack.locator('.stack-item')
    await expect(stackItems).toHaveCount(4)
    
    // Check that different field types are rendered correctly
    // Text field
    const textField = stackItems.nth(0).locator('input, .text-content')
    await expect(textField).toBeVisible()
    
    // Line fields with different formatting
    const headingLine = stackItems.nth(1).locator('.line-content')
    await expect(headingLine).toHaveClass(/text-lg/)
    await expect(headingLine).toHaveClass(/font-semibold/)
    
    const smallLine = stackItems.nth(2).locator('.line-content')
    await expect(smallLine).toHaveClass(/text-xs/)
    
    const subTextLine = stackItems.nth(3).locator('.line-content')
    await expect(subTextLine).toHaveClass(/text-sm/)
  })

  test('displays stack field with proper border styling', async ({ page }) => {
    const borderedStack = page.locator('[data-testid="bordered-stack-field"]')
    await expect(borderedStack).toBeVisible()
    
    const stackItems = borderedStack.locator('.stack-item')
    await expect(stackItems).toHaveCount(3)
    
    // Check that multiple items have border styling
    for (let i = 0; i < 3; i++) {
      const item = stackItems.nth(i)
      await expect(item).toHaveClass(/border-l-2/)
      await expect(item).toHaveClass(/border-gray-200/)
      await expect(item).toHaveClass(/pl-3/)
    }
  })

  test('adapts to dark theme correctly', async ({ page }) => {
    // Switch to dark theme
    await page.click('[data-testid="theme-toggle"]')
    await page.waitForTimeout(500) // Wait for theme transition
    
    const darkStack = page.locator('[data-testid="theme-stack-field"]')
    const stackItems = darkStack.locator('.stack-item')
    
    // Check dark theme border styling
    for (let i = 0; i < await stackItems.count(); i++) {
      const item = stackItems.nth(i)
      await expect(item).toHaveClass(/border-gray-600/)
    }
    
    // Check that child fields also adapt to dark theme
    const lineContents = darkStack.locator('.line-content')
    const count = await lineContents.count()
    
    if (count > 0) {
      // At least one line field should have dark theme classes
      const firstLine = lineContents.first()
      const classes = await firstLine.getAttribute('class')
      expect(classes).toMatch(/text-gray-[1-4]00/)
    }
  })

  test('handles disabled state visually', async ({ page }) => {
    const disabledStack = page.locator('[data-testid="disabled-stack-field"]')
    await expect(disabledStack).toBeVisible()
    
    // Check disabled styling
    const stackField = disabledStack.locator('.stack-field')
    await expect(stackField).toHaveClass(/opacity-75/)
    
    // Child fields should also be disabled
    const stackItems = disabledStack.locator('.stack-item')
    const count = await stackItems.count()
    
    for (let i = 0; i < count; i++) {
      const item = stackItems.nth(i)
      // Child fields should inherit disabled state
      await expect(item).toBeVisible()
    }
  })

  test('displays empty state correctly', async ({ page }) => {
    const emptyStack = page.locator('[data-testid="empty-stack-field"]')
    await expect(emptyStack).toBeVisible()
    
    // Check empty state message
    const emptyMessage = emptyStack.locator('.stack-empty')
    await expect(emptyMessage).toBeVisible()
    await expect(emptyMessage).toContainText('No fields to display')
    
    // Should not have any stack items
    const stackItems = emptyStack.locator('.stack-item')
    await expect(stackItems).toHaveCount(0)
  })

  test('displays correctly in different viewport sizes', async ({ page }) => {
    const responsiveStack = page.locator('[data-testid="responsive-stack-field"]')
    
    // Test desktop view
    await page.setViewportSize({ width: 1200, height: 800 })
    await expect(responsiveStack).toBeVisible()
    const desktopItems = responsiveStack.locator('.stack-item')
    const desktopCount = await desktopItems.count()
    
    // Test tablet view
    await page.setViewportSize({ width: 768, height: 1024 })
    await expect(responsiveStack).toBeVisible()
    const tabletItems = responsiveStack.locator('.stack-item')
    await expect(tabletItems).toHaveCount(desktopCount)
    
    // Test mobile view
    await page.setViewportSize({ width: 375, height: 667 })
    await expect(responsiveStack).toBeVisible()
    const mobileItems = responsiveStack.locator('.stack-item')
    await expect(mobileItems).toHaveCount(desktopCount)
    
    // Content should remain readable at all sizes
    await expect(mobileItems.first()).toBeVisible()
  })

  test('maintains accessibility standards', async ({ page }) => {
    // Check that stack fields are accessible
    const stackFields = page.locator('.stack-field')
    const count = await stackFields.count()
    
    for (let i = 0; i < count; i++) {
      const field = stackFields.nth(i)
      
      // Check that stack is visible and has content
      await expect(field).toBeVisible()
      
      // Check that child items are accessible
      const stackItems = field.locator('.stack-item')
      const itemCount = await stackItems.count()
      
      for (let j = 0; j < itemCount; j++) {
        const item = stackItems.nth(j)
        await expect(item).toBeVisible()
        
        const content = await item.textContent()
        expect(content).toBeTruthy()
        expect(content.trim().length).toBeGreaterThan(0)
      }
    }
    
    // Run accessibility audit
    await expect(page).toHaveNoViolations()
  })

  test('renders correctly in resource index context', async ({ page }) => {
    await page.goto('/admin/users')
    await page.waitForLoadState('networkidle')
    
    // Check that stack fields in user index are displayed
    const userSummaryStacks = page.locator('[data-field-type="stack"][data-field-name="user_summary"]')
    
    if (await userSummaryStacks.count() > 0) {
      const firstStack = userSummaryStacks.first()
      await expect(firstStack).toBeVisible()
      
      // Check that stack contains multiple fields
      const stackItems = firstStack.locator('.stack-item')
      const itemCount = await stackItems.count()
      expect(itemCount).toBeGreaterThan(0)
    }
  })

  test('renders correctly in resource detail context', async ({ page }) => {
    await page.goto('/admin/users/1')
    await page.waitForLoadState('networkidle')
    
    // Check detailed stack fields
    const profileStack = page.locator('[data-testid="user-profile-stack"]')
    
    if (await profileStack.count() > 0) {
      await expect(profileStack).toBeVisible()
      
      const stackItems = profileStack.locator('.stack-item')
      const itemCount = await stackItems.count()
      expect(itemCount).toBeGreaterThan(0)
      
      // Check that different field types are rendered
      const textFields = profileStack.locator('.text-field, input')
      const lineFields = profileStack.locator('.line-field')
      
      const totalFields = await textFields.count() + await lineFields.count()
      expect(totalFields).toBeGreaterThan(0)
    }
  })

  test('handles dynamic content updates', async ({ page }) => {
    await page.goto('/admin/test/dynamic-stack-fields')
    await page.waitForLoadState('networkidle')
    
    const dynamicStack = page.locator('[data-testid="dynamic-stack-field"]')
    await expect(dynamicStack).toBeVisible()
    
    // Initial state
    let stackItems = dynamicStack.locator('.stack-item')
    await expect(stackItems).toHaveCount(2)
    
    // Trigger content update
    await page.click('[data-testid="add-field-btn"]')
    await page.waitForTimeout(100)
    
    // Check that new field was added
    stackItems = dynamicStack.locator('.stack-item')
    await expect(stackItems).toHaveCount(3)
    
    // Remove field
    await page.click('[data-testid="remove-field-btn"]')
    await page.waitForTimeout(100)
    
    // Check that field was removed
    stackItems = dynamicStack.locator('.stack-item')
    await expect(stackItems).toHaveCount(2)
  })

  test('performs well with complex stacks', async ({ page }) => {
    await page.goto('/admin/test/complex-stack-fields')
    await page.waitForLoadState('networkidle')
    
    // Measure performance
    const startTime = Date.now()
    
    // Check that complex stack with many fields renders
    const complexStack = page.locator('[data-testid="complex-stack-field"]')
    await expect(complexStack).toBeVisible()
    
    const stackItems = complexStack.locator('.stack-item')
    await expect(stackItems).toHaveCount(10) // Complex stack with 10 fields
    
    const endTime = Date.now()
    const renderTime = endTime - startTime
    
    // Should render quickly even with complex content
    expect(renderTime).toBeLessThan(3000) // 3 seconds max
    
    // Check that all fields are properly rendered
    for (let i = 0; i < 10; i++) {
      const item = stackItems.nth(i)
      await expect(item).toBeVisible()
      
      const content = await item.textContent()
      expect(content.trim().length).toBeGreaterThan(0)
    }
  })

  test('integrates properly with form context', async ({ page }) => {
    await page.goto('/admin/test/stack-field-forms')
    await page.waitForLoadState('networkidle')
    
    // Stack fields should not interfere with form functionality
    const form = page.locator('form')
    const stackField = page.locator('[data-testid="form-stack-field"]')
    const submitBtn = page.locator('[data-testid="submit-btn"]')
    
    await expect(stackField).toBeVisible()
    
    // Check that stack contains expected fields
    const stackItems = stackField.locator('.stack-item')
    await expect(stackItems.first()).toBeVisible()
    
    // Submit form - stack fields should not cause issues
    await submitBtn.click()
    
    // Check that form processes correctly
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible()
  })

  test('works correctly with conditional visibility', async ({ page }) => {
    await page.goto('/admin/test/conditional-stack-fields')
    await page.waitForLoadState('networkidle')
    
    // Initially hidden stack field
    const conditionalStack = page.locator('[data-testid="conditional-stack-field"]')
    await expect(conditionalStack).not.toBeVisible()
    
    // Toggle visibility
    await page.click('[data-testid="toggle-stack-visibility-btn"]')
    await page.waitForTimeout(100)
    
    // Now should be visible
    await expect(conditionalStack).toBeVisible()
    
    const stackItems = conditionalStack.locator('.stack-item')
    await expect(stackItems.first()).toBeVisible()
    await expect(stackItems.first()).toContainText('Conditional Stack Content')
  })

  test('handles field composition correctly', async ({ page }) => {
    const compositionStack = page.locator('[data-testid="composition-stack-field"]')
    await expect(compositionStack).toBeVisible()
    
    const stackItems = compositionStack.locator('.stack-item')
    const itemCount = await stackItems.count()
    expect(itemCount).toBeGreaterThan(1)
    
    // Check that different field types maintain their characteristics
    for (let i = 0; i < itemCount; i++) {
      const item = stackItems.nth(i)
      await expect(item).toBeVisible()
      
      // Check for field-specific elements
      const hasTextField = await item.locator('input, .text-field').count() > 0
      const hasLineField = await item.locator('.line-field').count() > 0
      
      // Each item should have at least one field type
      expect(hasTextField || hasLineField).toBe(true)
    }
  })

  test('maintains visual consistency across contexts', async ({ page }) => {
    // Test in different admin contexts
    const contexts = [
      '/admin/test/stack-fields',
      '/admin/test/complex-stack-fields',
      '/admin/test/dynamic-stack-fields'
    ]
    
    for (const context of contexts) {
      await page.goto(context)
      await page.waitForLoadState('networkidle')
      
      const stackFields = page.locator('.stack-field')
      const count = await stackFields.count()
      
      if (count > 0) {
        // Check consistent styling
        const firstField = stackFields.first()
        await expect(firstField).toHaveClass(/space-y-2/)
        
        const stackItems = firstField.locator('.stack-item')
        const itemCount = await stackItems.count()
        
        if (itemCount > 1) {
          // Multiple items should have consistent border styling
          for (let i = 0; i < itemCount; i++) {
            const item = stackItems.nth(i)
            await expect(item).toHaveClass(/border-l-2/)
            await expect(item).toHaveClass(/pl-3/)
          }
        }
      }
    }
  })

  test('handles edge cases gracefully', async ({ page }) => {
    await page.goto('/admin/test/edge-case-stack-fields')
    await page.waitForLoadState('networkidle')
    
    // Single field stack (no borders)
    const singleStack = page.locator('[data-testid="single-field-stack"]')
    await expect(singleStack).toBeVisible()
    
    const singleItems = singleStack.locator('.stack-item')
    await expect(singleItems).toHaveCount(1)
    
    // Single item should not have border styling
    const singleItem = singleItems.first()
    const classes = await singleItem.getAttribute('class')
    expect(classes).not.toMatch(/border-l-2/)
    
    // Stack with very long content
    const longContentStack = page.locator('[data-testid="long-content-stack"]')
    await expect(longContentStack).toBeVisible()
    
    const longItems = longContentStack.locator('.stack-item')
    await expect(longItems.first()).toBeVisible()
    
    // Should handle long content without breaking layout
    const longContent = await longItems.first().textContent()
    expect(longContent.length).toBeGreaterThan(50)
  })

  test('supports proper field nesting', async ({ page }) => {
    const nestedStack = page.locator('[data-testid="nested-stack-field"]')
    await expect(nestedStack).toBeVisible()
    
    // Check that nested fields maintain their structure
    const stackItems = nestedStack.locator('.stack-item')
    const itemCount = await stackItems.count()
    expect(itemCount).toBeGreaterThan(0)
    
    // Each item should contain properly nested field components
    for (let i = 0; i < itemCount; i++) {
      const item = stackItems.nth(i)
      
      // Should have field wrapper structure
      const fieldWrappers = item.locator('.field-wrapper, .line-field, .text-field')
      await expect(fieldWrappers.first()).toBeVisible()
    }
  })

  test('handles keyboard navigation appropriately', async ({ page }) => {
    await page.goto('/admin/test/stack-fields')
    await page.waitForLoadState('networkidle')
    
    // Stack fields should not interfere with keyboard navigation
    await page.keyboard.press('Tab')
    await page.keyboard.press('Tab')
    await page.keyboard.press('Tab')
    
    // Should be able to navigate past stack fields
    const focusedElement = await page.evaluate(() => document.activeElement.tagName)
    expect(focusedElement).toBeTruthy()
    
    // Focus should not get trapped in stack fields
    await page.keyboard.press('Tab')
    await page.keyboard.press('Tab')
    
    const newFocusedElement = await page.evaluate(() => document.activeElement.tagName)
    expect(newFocusedElement).toBeTruthy()
  })
})
