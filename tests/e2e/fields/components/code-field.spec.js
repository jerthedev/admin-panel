import { test, expect } from '@playwright/test'

/**
 * Code Field Playwright E2E Tests
 *
 * Tests the complete end-to-end functionality of Code fields
 * in the browser environment, including visual rendering,
 * user interactions, and Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

test.describe('Code Field E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Set up test environment
    await page.goto('/admin-panel/test-code-field')
  })

  test('renders code field with textarea editor', async ({ page }) => {
    // Test basic code field rendering
    const codeField = page.locator('[data-testid="code-field"]')
    await expect(codeField).toBeVisible()

    // Test textarea element exists
    const textarea = codeField.locator('textarea')
    await expect(textarea).toBeVisible()
    await expect(textarea).toHaveClass(/font-mono/)
    await expect(textarea).toHaveClass(/min-h-\[200px\]/)
    await expect(textarea).toHaveClass(/resize-y/)
  })

  test('displays field with correct language indicator', async ({ page }) => {
    const phpCodeField = page.locator('[data-testid="code-field-php"]')
    
    // Should show PHP language indicator
    await expect(phpCodeField).toContainText('PHP')
    
    const languageIndicator = phpCodeField.locator('.absolute.top-2.right-2')
    await expect(languageIndicator).toBeVisible()
    await expect(languageIndicator).toContainText('PHP')
  })

  test('displays JSON indicator for JSON fields', async ({ page }) => {
    const jsonCodeField = page.locator('[data-testid="code-field-json"]')
    
    // Should show JSON indicator
    await expect(jsonCodeField).toContainText('JSON')
    
    const jsonIndicator = jsonCodeField.locator('.absolute.top-2.left-2')
    await expect(jsonIndicator).toBeVisible()
    await expect(jsonIndicator).toContainText('JSON')
    
    // Should show JSON validation hint
    await expect(jsonCodeField).toContainText('Enter valid JSON format')
  })

  test('handles text input correctly', async ({ page }) => {
    const codeField = page.locator('[data-testid="code-field"]')
    const textarea = codeField.locator('textarea')

    // Test typing code
    const phpCode = '<?php echo "Hello World"; ?>'
    await textarea.fill(phpCode)
    
    await expect(textarea).toHaveValue(phpCode)
  })

  test('handles JSON input correctly', async ({ page }) => {
    const jsonCodeField = page.locator('[data-testid="code-field-json"]')
    const textarea = jsonCodeField.locator('textarea')

    // Test typing JSON
    const jsonCode = '{"name": "test", "value": 123}'
    await textarea.fill(jsonCode)
    
    await expect(textarea).toHaveValue(jsonCode)
  })

  test('displays appropriate placeholder text', async ({ page }) => {
    const phpField = page.locator('[data-testid="code-field-php"] textarea')
    await expect(phpField).toHaveAttribute('placeholder', /PHP code/)

    const jsonField = page.locator('[data-testid="code-field-json"] textarea')
    await expect(jsonField).toHaveAttribute('placeholder', 'Enter valid JSON...')

    const jsField = page.locator('[data-testid="code-field-javascript"] textarea')
    await expect(jsField).toHaveAttribute('placeholder', /JavaScript code/)
  })

  test('displays required indicator when field is required', async ({ page }) => {
    const requiredField = page.locator('[data-testid="code-field-required"]')
    
    await expect(requiredField).toContainText('* Required')
    
    const requiredIndicator = requiredField.locator('.text-red-500')
    await expect(requiredIndicator).toBeVisible()
    await expect(requiredIndicator).toContainText('*')
  })

  test('does not display required indicator when field is optional', async ({ page }) => {
    const optionalField = page.locator('[data-testid="code-field-optional"]')
    
    // Should not contain required text
    await expect(optionalField).not.toContainText('* Required')
    
    const requiredIndicator = optionalField.locator('.text-red-500')
    await expect(requiredIndicator).toHaveCount(0)
  })

  test('handles disabled state correctly', async ({ page }) => {
    const disabledField = page.locator('[data-testid="code-field-disabled"]')
    const textarea = disabledField.locator('textarea')

    // Test textarea is disabled
    await expect(textarea).toBeDisabled()
    await expect(textarea).toHaveClass(/bg-gray-50/)
    await expect(textarea).toHaveClass(/cursor-not-allowed/)

    // Test typing doesn't work when disabled
    await textarea.click({ force: true })
    await page.keyboard.type('test code')
    await expect(textarea).toHaveValue('') // Should remain empty
  })

  test('displays readonly state with formatted display', async ({ page }) => {
    // Test readonly with code content
    const readonlyField = page.locator('[data-testid="code-field-readonly"]')
    await expect(readonlyField.locator('textarea')).toHaveCount(0)
    
    // Should show pre-formatted code
    const preElement = readonlyField.locator('pre')
    await expect(preElement).toBeVisible()
    await expect(preElement).toHaveClass(/font-mono/)
    await expect(preElement).toHaveClass(/whitespace-pre-wrap/)
    
    // Should contain the code content
    await expect(preElement).toContainText('function')
  })

  test('displays readonly JSON with proper formatting', async ({ page }) => {
    const readonlyJsonField = page.locator('[data-testid="code-field-readonly-json"]')
    
    // Should show formatted JSON
    const preElement = readonlyJsonField.locator('pre')
    await expect(preElement).toBeVisible()
    await expect(preElement).toContainText('{')
    await expect(preElement).toContainText('}')
    await expect(preElement).toContainText('"')
  })

  test('shows "No content" for empty readonly field', async ({ page }) => {
    const emptyReadonlyField = page.locator('[data-testid="code-field-readonly-empty"]')
    
    const preElement = emptyReadonlyField.locator('pre')
    await expect(preElement).toContainText('No content')
  })

  test('handles error states gracefully', async ({ page }) => {
    // Test code field with validation errors
    const errorField = page.locator('[data-testid="code-field-with-errors"]')
    const textarea = errorField.locator('textarea')
    
    await expect(textarea).toBeVisible()
    await expect(textarea).toHaveClass(/border-red-300/)

    // Textarea should still be functional despite errors
    await textarea.fill('test code')
    await expect(textarea).toHaveValue('test code')
  })

  test('maintains accessibility standards', async ({ page }) => {
    const codeField = page.locator('[data-testid="code-field"]')
    const textarea = codeField.locator('textarea')

    // Test textarea has proper attributes
    await expect(textarea).toHaveAttribute('id')
    
    // Test keyboard navigation
    await textarea.focus()
    await expect(textarea).toBeFocused()

    // Test typing works
    await page.keyboard.type('console.log("test");')
    await expect(textarea).toHaveValue('console.log("test");')

    // Test tab navigation
    await page.keyboard.press('Tab')
    await expect(textarea).not.toBeFocused()
  })

  test('handles dark mode correctly', async ({ page }) => {
    // Enable dark mode
    await page.locator('[data-testid="dark-mode-toggle"]').click()

    // Test that dark mode classes are applied
    const darkField = page.locator('[data-testid="code-field-dark"]')
    const textarea = darkField.locator('textarea')

    // Test textarea dark mode styling
    await expect(textarea).toHaveClass(/border-gray-600/)
    await expect(textarea).toHaveClass(/bg-gray-700/)
    await expect(textarea).toHaveClass(/text-white/)

    // Test readonly dark mode
    const readonlyDark = page.locator('[data-testid="code-field-readonly-dark"]')
    const container = readonlyDark.locator('.bg-gray-50')
    
    await expect(container).toHaveClass(/bg-gray-800/)
    await expect(container).toHaveClass(/border-gray-600/)

    // Test indicators dark mode
    const languageIndicator = darkField.locator('.bg-gray-100')
    if (await languageIndicator.count() > 0) {
      await expect(languageIndicator).toHaveClass(/bg-gray-600/)
    }

    const jsonIndicator = darkField.locator('.bg-blue-100')
    if (await jsonIndicator.count() > 0) {
      await expect(jsonIndicator).toHaveClass(/bg-blue-900/)
    }
  })

  test('displays correctly on different screen sizes', async ({ page }) => {
    const codeField = page.locator('[data-testid="code-field"]')
    
    // Test desktop view
    await page.setViewportSize({ width: 1200, height: 800 })
    await expect(codeField).toBeVisible()
    
    const textarea = codeField.locator('textarea')
    await expect(textarea).toBeVisible()

    // Test tablet view
    await page.setViewportSize({ width: 768, height: 1024 })
    await expect(codeField).toBeVisible()
    await expect(textarea).toBeVisible()

    // Test mobile view
    await page.setViewportSize({ width: 375, height: 667 })
    await expect(codeField).toBeVisible()
    await expect(textarea).toBeVisible()

    // Field should maintain its functionality across all screen sizes
    await textarea.fill('mobile test code')
    await expect(textarea).toHaveValue('mobile test code')
  })

  test('integrates properly with BaseField wrapper', async ({ page }) => {
    const codeField = page.locator('[data-testid="code-field"]')
    
    // Test that BaseField wrapper is present
    const baseField = codeField.locator('[data-component="BaseField"]')
    await expect(baseField).toBeVisible()

    // Test help text is displayed if provided
    const helpText = codeField.locator('.field-help')
    if (await helpText.count() > 0) {
      await expect(helpText).toBeVisible()
      await expect(helpText).toContainText('Enter')
    }
  })

  test('handles focus and blur events correctly', async ({ page }) => {
    const codeField = page.locator('[data-testid="code-field"]')
    const textarea = codeField.locator('textarea')

    // Test focus
    await textarea.focus()
    await expect(textarea).toBeFocused()
    await expect(textarea).toHaveClass(/focus:ring-blue-500/)

    // Test blur
    await textarea.blur()
    await expect(textarea).not.toBeFocused()
  })

  test('handles large code content efficiently', async ({ page }) => {
    // Test performance with large code content
    const largeField = page.locator('[data-testid="code-field-large"]')
    const textarea = largeField.locator('textarea')
    
    await expect(textarea).toBeVisible()

    // Generate large code content
    const largeCode = Array(100).fill('console.log("line");').join('\n')
    
    const startTime = Date.now()
    await textarea.fill(largeCode)
    const endTime = Date.now()
    
    // Should handle large content efficiently (less than 1 second)
    expect(endTime - startTime).toBeLessThan(1000)
    
    await expect(textarea).toHaveValue(largeCode)
  })

  test('handles form submission correctly', async ({ page }) => {
    const form = page.locator('[data-testid="code-field-form"]')
    const textarea = form.locator('textarea')
    const submitButton = form.locator('[data-testid="submit-button"]')

    // Test submitting with code content
    const codeContent = 'function test() { return "hello"; }'
    await textarea.fill(codeContent)
    
    await submitButton.click()
    
    // Should handle form submission (exact behavior depends on implementation)
    // At minimum, the form should not crash
    await expect(form).toBeVisible()
  })

  test('handles JSON form submission correctly', async ({ page }) => {
    const jsonForm = page.locator('[data-testid="code-field-json-form"]')
    const textarea = jsonForm.locator('textarea')
    const submitButton = jsonForm.locator('[data-testid="submit-button"]')

    // Test submitting with JSON content
    const jsonContent = '{"name": "test", "active": true, "count": 42}'
    await textarea.fill(jsonContent)
    
    await submitButton.click()
    
    // Should handle JSON form submission
    await expect(jsonForm).toBeVisible()
  })

  test('handles dynamic value changes from external sources', async ({ page }) => {
    const dynamicField = page.locator('[data-testid="code-field-dynamic"]')
    const textarea = dynamicField.locator('textarea')
    const updateButton = page.locator('[data-testid="external-update-button"]')

    // Test initial value
    const initialValue = await textarea.inputValue()

    // Trigger external change
    await updateButton.click()

    // Value should change from external source
    const updatedValue = await textarea.inputValue()
    expect(updatedValue).not.toBe(initialValue)
  })

  test('maintains state consistency across interactions', async ({ page }) => {
    const consistencyField = page.locator('[data-testid="code-field-consistency"]')
    const textarea = consistencyField.locator('textarea')

    // Test multiple interaction methods produce consistent results
    const testCode = 'const x = 42;'
    
    // Method 1: Direct typing
    await textarea.focus()
    await page.keyboard.type(testCode)
    await expect(textarea).toHaveValue(testCode)

    // Method 2: Clear and fill
    await textarea.fill('')
    await expect(textarea).toHaveValue('')
    
    await textarea.fill(testCode)
    await expect(textarea).toHaveValue(testCode)

    // Method 3: Select all and replace
    await textarea.selectText()
    await page.keyboard.type('new code')
    await expect(textarea).toHaveValue('new code')
  })

  test('handles different programming languages correctly', async ({ page }) => {
    const languageTests = [
      { testId: 'code-field-php', language: 'PHP', code: '<?php echo "test"; ?>' },
      { testId: 'code-field-javascript', language: 'JavaScript', code: 'console.log("test");' },
      { testId: 'code-field-python', language: 'PYTHON', code: 'print("test")' },
      { testId: 'code-field-sql', language: 'SQL', code: 'SELECT * FROM users;' },
      { testId: 'code-field-yaml', language: 'YAML', code: 'name: test\nvalue: 123' },
    ]

    for (const { testId, language, code } of languageTests) {
      const field = page.locator(`[data-testid="${testId}"]`)
      const textarea = field.locator('textarea')
      
      // Should show correct language indicator
      if (language !== 'HTML') { // htmlmixed doesn't show indicator
        await expect(field).toContainText(language)
      }
      
      // Should accept the language-specific code
      await textarea.fill(code)
      await expect(textarea).toHaveValue(code)
    }
  })

  test('handles complex JSON structures correctly', async ({ page }) => {
    const complexJsonField = page.locator('[data-testid="code-field-complex-json"]')
    const textarea = complexJsonField.locator('textarea')

    // Test complex nested JSON
    const complexJson = JSON.stringify({
      users: [
        { id: 1, name: 'John', settings: { theme: 'dark' } },
        { id: 2, name: 'Jane', settings: { theme: 'light' } }
      ],
      config: {
        api: { version: 'v1', timeout: 5000 },
        features: ['auth', 'logging']
      }
    }, null, 2)

    await textarea.fill(complexJson)
    await expect(textarea).toHaveValue(complexJson)

    // Should show JSON indicator
    await expect(complexJsonField).toContainText('JSON')
  })

  test('handles code field resize functionality', async ({ page }) => {
    const resizableField = page.locator('[data-testid="code-field-resizable"]')
    const textarea = resizableField.locator('textarea')

    // Test that textarea is resizable
    await expect(textarea).toHaveClass(/resize-y/)

    // Test minimum height is maintained
    await expect(textarea).toHaveClass(/min-h-\[200px\]/)

    // Fill with content that would require scrolling
    const longCode = Array(50).fill('console.log("long line of code");').join('\n')
    await textarea.fill(longCode)
    
    await expect(textarea).toHaveValue(longCode)
  })

  test('handles copy and paste operations', async ({ page }) => {
    const copyPasteField = page.locator('[data-testid="code-field-copy-paste"]')
    const textarea = copyPasteField.locator('textarea')

    // Test paste operation
    const codeToTest = 'function example() {\n  return "pasted code";\n}'
    
    await textarea.focus()
    
    // Simulate paste (this might need to be adapted based on test environment)
    await page.evaluate((code) => {
      navigator.clipboard.writeText(code)
    }, codeToTest)
    
    await page.keyboard.press('Control+v')
    
    // Should contain the pasted content
    const value = await textarea.inputValue()
    expect(value).toContain('pasted code')
  })

  test('validates JSON input in real-time', async ({ page }) => {
    const jsonValidationField = page.locator('[data-testid="code-field-json-validation"]')
    const textarea = jsonValidationField.locator('textarea')

    // Test valid JSON
    await textarea.fill('{"valid": true}')
    // Should not show validation errors for valid JSON
    
    // Test invalid JSON
    await textarea.fill('{"invalid": json}')
    // Field should still accept the input (validation happens on submit)
    await expect(textarea).toHaveValue('{"invalid": json}')
  })
})
