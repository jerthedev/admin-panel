import { test, expect } from '@playwright/test'

/**
 * MultiSelect Field Playwright E2E Tests
 *
 * Tests the complete end-to-end functionality of MultiSelect fields
 * in the browser environment, including visual rendering,
 * user interactions, and Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

test.describe('MultiSelect Field E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Set up test environment
    await page.goto('/admin-panel/test-multiselect-field')
  })

  test('renders multiselect field with dropdown interface', async ({ page }) => {
    // Test basic multiselect field rendering
    const multiselectField = page.locator('[data-testid="multiselect-field"]')
    await expect(multiselectField).toBeVisible()

    // Test dropdown trigger exists and has proper styling
    const dropdownTrigger = multiselectField.locator('.admin-input')
    await expect(dropdownTrigger).toBeVisible()
    await expect(dropdownTrigger).toHaveClass(/border/)
    await expect(dropdownTrigger).toHaveClass(/rounded-md/)
    await expect(dropdownTrigger).toHaveClass(/cursor-pointer/)

    // Test chevron icon exists
    const chevronIcon = multiselectField.locator('[data-testid="chevron-icon"]')
    await expect(chevronIcon).toBeVisible()
  })

  test('opens dropdown when clicked and shows options', async ({ page }) => {
    const multiselectField = page.locator('[data-testid="multiselect-field"]')
    const dropdownTrigger = multiselectField.locator('.admin-input')
    
    // Click to open dropdown
    await dropdownTrigger.click()
    
    // Test dropdown menu appears
    const dropdownMenu = multiselectField.locator('[data-testid="dropdown-menu"]')
    await expect(dropdownMenu).toBeVisible()
    
    // Test options are visible
    const options = dropdownMenu.locator('.cursor-pointer')
    await expect(options).toHaveCount(6) // Should have 6 skill options
    
    // Test each option has proper content and styling
    await expect(options.nth(0)).toContainText('PHP')
    await expect(options.nth(1)).toContainText('JavaScript')
    await expect(options.nth(2)).toContainText('Python')
    await expect(options.nth(3)).toContainText('Java')
    await expect(options.nth(4)).toContainText('C#')
    await expect(options.nth(5)).toContainText('Ruby')
  })

  test('selects multiple options and displays them as tags', async ({ page }) => {
    const multiselectField = page.locator('[data-testid="multiselect-field"]')
    const dropdownTrigger = multiselectField.locator('.admin-input')
    
    // Open dropdown
    await dropdownTrigger.click()
    
    const dropdownMenu = multiselectField.locator('[data-testid="dropdown-menu"]')
    const options = dropdownMenu.locator('.cursor-pointer')
    
    // Select PHP
    await options.nth(0).click()
    
    // Test PHP tag appears
    const phpTag = multiselectField.locator('[data-testid="selected-tag"]').filter({ hasText: 'PHP' })
    await expect(phpTag).toBeVisible()
    
    // Select JavaScript
    await options.nth(1).click()
    
    // Test JavaScript tag appears
    const jsTag = multiselectField.locator('[data-testid="selected-tag"]').filter({ hasText: 'JavaScript' })
    await expect(jsTag).toBeVisible()
    
    // Test both tags are visible
    const allTags = multiselectField.locator('[data-testid="selected-tag"]')
    await expect(allTags).toHaveCount(2)
  })

  test('removes selected items when tag remove button is clicked', async ({ page }) => {
    const multiselectField = page.locator('[data-testid="multiselect-field"]')
    const dropdownTrigger = multiselectField.locator('.admin-input')
    
    // Open dropdown and select options
    await dropdownTrigger.click()
    const options = multiselectField.locator('[data-testid="dropdown-menu"] .cursor-pointer')
    await options.nth(0).click() // Select PHP
    await options.nth(1).click() // Select JavaScript
    
    // Test both tags are present
    const allTags = multiselectField.locator('[data-testid="selected-tag"]')
    await expect(allTags).toHaveCount(2)
    
    // Remove PHP tag
    const phpTag = multiselectField.locator('[data-testid="selected-tag"]').filter({ hasText: 'PHP' })
    const removeButton = phpTag.locator('[data-testid="remove-item"]')
    await removeButton.click()
    
    // Test PHP tag is removed
    await expect(phpTag).not.toBeVisible()
    
    // Test JavaScript tag is still present
    const jsTag = multiselectField.locator('[data-testid="selected-tag"]').filter({ hasText: 'JavaScript' })
    await expect(jsTag).toBeVisible()
    
    // Test only one tag remains
    await expect(allTags).toHaveCount(1)
  })

  test('shows search input when field is searchable', async ({ page }) => {
    const multiselectField = page.locator('[data-testid="multiselect-field-searchable"]')
    const dropdownTrigger = multiselectField.locator('.admin-input')
    
    // Open dropdown
    await dropdownTrigger.click()
    
    // Test search input appears
    const searchInput = multiselectField.locator('input[type="text"]')
    await expect(searchInput).toBeVisible()
    await expect(searchInput).toHaveAttribute('placeholder', /Search/)
  })

  test('filters options based on search query', async ({ page }) => {
    const multiselectField = page.locator('[data-testid="multiselect-field-searchable"]')
    const dropdownTrigger = multiselectField.locator('.admin-input')
    
    // Open dropdown
    await dropdownTrigger.click()
    
    const searchInput = multiselectField.locator('input[type="text"]')
    const dropdownMenu = multiselectField.locator('[data-testid="dropdown-menu"]')
    
    // Type search query
    await searchInput.fill('Java')
    
    // Test filtered options
    const visibleOptions = dropdownMenu.locator('.cursor-pointer:visible')
    await expect(visibleOptions).toHaveCount(2) // Should show Java and JavaScript
    
    await expect(visibleOptions.nth(0)).toContainText('Java')
    await expect(visibleOptions.nth(1)).toContainText('JavaScript')
  })

  test('closes dropdown when clicking outside', async ({ page }) => {
    const multiselectField = page.locator('[data-testid="multiselect-field"]')
    const dropdownTrigger = multiselectField.locator('.admin-input')
    
    // Open dropdown
    await dropdownTrigger.click()
    
    // Test dropdown is open
    const dropdownMenu = multiselectField.locator('[data-testid="dropdown-menu"]')
    await expect(dropdownMenu).toBeVisible()
    
    // Click outside the field
    await page.click('body')
    
    // Test dropdown is closed
    await expect(dropdownMenu).not.toBeVisible()
  })

  test('handles keyboard navigation with Escape key', async ({ page }) => {
    const multiselectField = page.locator('[data-testid="multiselect-field-searchable"]')
    const dropdownTrigger = multiselectField.locator('.admin-input')
    
    // Open dropdown
    await dropdownTrigger.click()
    
    const dropdownMenu = multiselectField.locator('[data-testid="dropdown-menu"]')
    await expect(dropdownMenu).toBeVisible()
    
    // Press Escape key
    const searchInput = multiselectField.locator('input[type="text"]')
    await searchInput.press('Escape')
    
    // Test dropdown is closed
    await expect(dropdownMenu).not.toBeVisible()
  })

  test('displays validation errors correctly', async ({ page }) => {
    const multiselectField = page.locator('[data-testid="multiselect-field-required"]')
    
    // Test error styling is applied
    const dropdownTrigger = multiselectField.locator('.admin-input')
    await expect(dropdownTrigger).toHaveClass(/border-red-500/)
    
    // Test error message is displayed
    const errorMessage = multiselectField.locator('[data-testid="error-message"]')
    await expect(errorMessage).toBeVisible()
    await expect(errorMessage).toContainText('required')
  })

  test('supports dark theme styling', async ({ page }) => {
    // Enable dark theme
    await page.evaluate(() => {
      document.documentElement.classList.add('dark')
    })
    
    const multiselectField = page.locator('[data-testid="multiselect-field"]')
    const dropdownTrigger = multiselectField.locator('.admin-input')
    
    // Test dark theme classes are applied
    await expect(dropdownTrigger).toHaveClass(/dark:bg-gray-700/)
    await expect(dropdownTrigger).toHaveClass(/dark:border-gray-600/)
    
    // Open dropdown and test dark theme for menu
    await dropdownTrigger.click()
    
    const dropdownMenu = multiselectField.locator('[data-testid="dropdown-menu"]')
    await expect(dropdownMenu).toHaveClass(/dark:bg-gray-700/)
  })

  test('maintains selection state across dropdown open/close cycles', async ({ page }) => {
    const multiselectField = page.locator('[data-testid="multiselect-field"]')
    const dropdownTrigger = multiselectField.locator('.admin-input')
    
    // Open dropdown and select options
    await dropdownTrigger.click()
    const options = multiselectField.locator('[data-testid="dropdown-menu"] .cursor-pointer')
    await options.nth(0).click() // Select PHP
    await options.nth(1).click() // Select JavaScript
    
    // Close dropdown by clicking outside
    await page.click('body')
    
    // Test tags are still visible
    const allTags = multiselectField.locator('[data-testid="selected-tag"]')
    await expect(allTags).toHaveCount(2)
    
    // Reopen dropdown
    await dropdownTrigger.click()
    
    // Test selected options show check marks
    const checkedOptions = multiselectField.locator('[data-testid="dropdown-menu"] .cursor-pointer [data-testid="check-icon"]')
    await expect(checkedOptions).toHaveCount(2)
  })

  test('handles form submission with selected values', async ({ page }) => {
    const multiselectField = page.locator('[data-testid="multiselect-field"]')
    const dropdownTrigger = multiselectField.locator('.admin-input')
    
    // Select multiple options
    await dropdownTrigger.click()
    const options = multiselectField.locator('[data-testid="dropdown-menu"] .cursor-pointer')
    await options.nth(0).click() // PHP
    await options.nth(2).click() // Python
    await options.nth(4).click() // C#
    
    // Submit form
    const submitButton = page.locator('[data-testid="submit-button"]')
    await submitButton.click()
    
    // Test form submission includes selected values
    await page.waitForSelector('[data-testid="success-message"]')
    const successMessage = page.locator('[data-testid="success-message"]')
    await expect(successMessage).toContainText('Skills saved: php, python, csharp')
  })

  test('preserves selection order as user selects options', async ({ page }) => {
    const multiselectField = page.locator('[data-testid="multiselect-field"]')
    const dropdownTrigger = multiselectField.locator('.admin-input')
    
    // Open dropdown
    await dropdownTrigger.click()
    const options = multiselectField.locator('[data-testid="dropdown-menu"] .cursor-pointer')
    
    // Select in specific order: Ruby, PHP, JavaScript
    await options.nth(5).click() // Ruby
    await options.nth(0).click() // PHP  
    await options.nth(1).click() // JavaScript
    
    // Test tags appear in selection order
    const tags = multiselectField.locator('[data-testid="selected-tag"]')
    await expect(tags.nth(0)).toContainText('Ruby')
    await expect(tags.nth(1)).toContainText('PHP')
    await expect(tags.nth(2)).toContainText('JavaScript')
  })
})
