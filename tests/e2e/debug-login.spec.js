import { test, expect } from '@playwright/test';

/**
 * Debug Login Test
 * 
 * Simple test to inspect the login page structure and debug authentication issues.
 */

test.describe('Debug Login', () => {
  test('should inspect login page structure', async ({ page }) => {
    // Navigate to login page
    await page.goto('/admin/login');
    await page.waitForLoadState('networkidle');
    
    // Take screenshot of login page
    await page.screenshot({ path: 'test-results/screenshots/debug-login-page.png' });
    
    // Log page title
    const title = await page.title();
    console.log('Page title:', title);
    
    // Log page URL
    console.log('Page URL:', page.url());
    
    // Check for forms
    const forms = page.locator('form');
    const formCount = await forms.count();
    console.log('Number of forms found:', formCount);
    
    if (formCount > 0) {
      // Inspect first form
      const form = forms.first();
      const formAction = await form.getAttribute('action');
      const formMethod = await form.getAttribute('method');
      console.log('Form action:', formAction);
      console.log('Form method:', formMethod);
      
      // Check for inputs
      const inputs = form.locator('input');
      const inputCount = await inputs.count();
      console.log('Number of inputs in form:', inputCount);
      
      for (let i = 0; i < inputCount; i++) {
        const input = inputs.nth(i);
        const name = await input.getAttribute('name');
        const type = await input.getAttribute('type');
        const placeholder = await input.getAttribute('placeholder');
        console.log(`Input ${i}: name="${name}", type="${type}", placeholder="${placeholder}"`);
      }
      
      // Check for buttons
      const buttons = form.locator('button, input[type="submit"]');
      const buttonCount = await buttons.count();
      console.log('Number of buttons in form:', buttonCount);
      
      for (let i = 0; i < buttonCount; i++) {
        const button = buttons.nth(i);
        const type = await button.getAttribute('type');
        const text = await button.textContent();
        console.log(`Button ${i}: type="${type}", text="${text}"`);
      }
    }
    
    // Check for any Vue.js or Inertia.js indicators
    const hasVue = await page.evaluate(() => {
      return typeof window.Vue !== 'undefined' || 
             document.querySelector('[data-page]') !== null ||
             document.querySelector('#app') !== null;
    });
    console.log('Has Vue/Inertia indicators:', hasVue);
    
    // Check for CSRF token
    const csrfToken = await page.evaluate(() => {
      const metaTag = document.querySelector('meta[name="csrf-token"]');
      return metaTag ? metaTag.getAttribute('content') : null;
    });
    console.log('CSRF token found:', csrfToken ? 'Yes' : 'No');
    
    // Log page content (first 500 chars)
    const content = await page.textContent('body');
    console.log('Page content preview:', content.substring(0, 500));
    
    expect(true).toBe(true); // Always pass
  });

  test('should test manual login process', async ({ page }) => {
    await page.goto('/admin/login');
    await page.waitForLoadState('networkidle');
    
    // Try to find email input with various selectors
    const emailSelectors = [
      'input[name="email"]',
      'input[type="email"]',
      'input[placeholder*="email"]',
      'input[placeholder*="Email"]',
      '#email',
      '.email-input'
    ];
    
    let emailInput = null;
    for (const selector of emailSelectors) {
      const input = page.locator(selector).first();
      if (await input.isVisible()) {
        emailInput = input;
        console.log(`Found email input with selector: ${selector}`);
        break;
      }
    }
    
    // Try to find password input
    const passwordSelectors = [
      'input[name="password"]',
      'input[type="password"]',
      'input[placeholder*="password"]',
      'input[placeholder*="Password"]',
      '#password',
      '.password-input'
    ];
    
    let passwordInput = null;
    for (const selector of passwordSelectors) {
      const input = page.locator(selector).first();
      if (await input.isVisible()) {
        passwordInput = input;
        console.log(`Found password input with selector: ${selector}`);
        break;
      }
    }
    
    if (emailInput && passwordInput) {
      console.log('Both email and password inputs found, attempting login...');
      
      // Fill credentials
      await emailInput.fill('admin@example.com');
      await passwordInput.fill('password');
      
      // Wait a moment for any JavaScript to process
      await page.waitForTimeout(1000);
      
      // Take screenshot before submit
      await page.screenshot({ path: 'test-results/screenshots/debug-before-submit.png' });
      
      // Try to find submit button
      const submitSelectors = [
        'button[type="submit"]',
        'input[type="submit"]',
        'button:has-text("Login")',
        'button:has-text("Sign In")',
        'button:has-text("Submit")',
        '.login-button',
        '.submit-button'
      ];
      
      let submitButton = null;
      for (const selector of submitSelectors) {
        const button = page.locator(selector).first();
        if (await button.isVisible()) {
          submitButton = button;
          console.log(`Found submit button with selector: ${selector}`);
          break;
        }
      }
      
      if (submitButton) {
        // Click submit and wait for response
        await submitButton.click();
        
        // Wait for navigation or error
        await page.waitForTimeout(3000);
        
        // Take screenshot after submit
        await page.screenshot({ path: 'test-results/screenshots/debug-after-submit.png' });
        
        // Log final URL
        console.log('Final URL after submit:', page.url());
        
        // Check for any error messages
        const errorSelectors = [
          '.error', '.alert-danger', '.text-red', '.text-danger',
          '[class*="error"]', '[class*="invalid"]', '[role="alert"]'
        ];
        
        for (const selector of errorSelectors) {
          const errorElement = page.locator(selector).first();
          if (await errorElement.isVisible()) {
            const errorText = await errorElement.textContent();
            console.log(`Error found with selector ${selector}: ${errorText}`);
          }
        }
      } else {
        console.log('No submit button found');
      }
    } else {
      console.log('Could not find both email and password inputs');
      console.log('Email input found:', emailInput ? 'Yes' : 'No');
      console.log('Password input found:', passwordInput ? 'Yes' : 'No');
    }
    
    expect(true).toBe(true); // Always pass
  });
});
