/**
 * Dashboard Visual Regression Tests
 * 
 * Comprehensive visual testing to ensure UI consistency across
 * browsers, devices, and theme variations using Playwright.
 */

import { test, expect } from '@playwright/test'

test.describe('Dashboard Visual Regression', () => {
  test.beforeEach(async ({ page }) => {
    // Login as admin user
    await page.goto('/admin/login')
    await page.fill('[name="email"]', 'admin@example.com')
    await page.fill('[name="password"]', 'password')
    await page.click('button[type="submit"]')
    await page.waitForURL('/admin')
  })

  test.describe('Dashboard Layout', () => {
    test('dashboard main layout matches baseline', async ({ page }) => {
      await page.goto('/admin')
      
      // Wait for dashboard to fully load
      await page.waitForSelector('[data-testid="dashboard-container"]')
      await page.waitForLoadState('networkidle')
      
      // Take full page screenshot
      await expect(page).toHaveScreenshot('dashboard-main-layout.png', {
        fullPage: true,
        animations: 'disabled'
      })
    })

    test('dashboard header matches baseline', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-header"]')
      
      // Screenshot just the header
      const header = page.locator('[data-testid="dashboard-header"]')
      await expect(header).toHaveScreenshot('dashboard-header.png')
    })

    test('dashboard navigation matches baseline', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-navigation"]')
      
      // Screenshot the navigation
      const navigation = page.locator('[data-testid="dashboard-navigation"]')
      await expect(navigation).toHaveScreenshot('dashboard-navigation.png')
    })

    test('dashboard selector matches baseline', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-selector"]')
      
      // Screenshot the dashboard selector
      const selector = page.locator('[data-testid="dashboard-selector"]')
      await expect(selector).toHaveScreenshot('dashboard-selector.png')
    })
  })

  test.describe('Dashboard Cards', () => {
    test('dashboard cards grid matches baseline', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-cards"]')
      
      // Wait for all cards to load
      await page.waitForFunction(() => {
        const cards = document.querySelectorAll('[data-testid="dashboard-card"]')
        return cards.length > 0 && Array.from(cards).every(card => 
          !card.classList.contains('loading')
        )
      })
      
      const cardsGrid = page.locator('[data-testid="dashboard-cards"]')
      await expect(cardsGrid).toHaveScreenshot('dashboard-cards-grid.png')
    })

    test('individual dashboard card matches baseline', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-card"]')
      
      // Screenshot first card
      const firstCard = page.locator('[data-testid="dashboard-card"]').first()
      await expect(firstCard).toHaveScreenshot('dashboard-card-single.png')
    })

    test('dashboard card loading state matches baseline', async ({ page }) => {
      // Intercept API calls to simulate loading
      await page.route('/admin/api/dashboards/*/data', route => {
        // Delay response to capture loading state
        setTimeout(() => route.continue(), 2000)
      })
      
      await page.goto('/admin')
      
      // Capture loading state
      const loadingCard = page.locator('[data-testid="dashboard-card"].loading').first()
      await expect(loadingCard).toHaveScreenshot('dashboard-card-loading.png')
    })

    test('dashboard card error state matches baseline', async ({ page }) => {
      // Intercept API calls to simulate error
      await page.route('/admin/api/dashboards/*/data', route => {
        route.fulfill({
          status: 500,
          contentType: 'application/json',
          body: JSON.stringify({ error: 'Internal Server Error' })
        })
      })
      
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-card"].error')
      
      const errorCard = page.locator('[data-testid="dashboard-card"].error').first()
      await expect(errorCard).toHaveScreenshot('dashboard-card-error.png')
    })
  })

  test.describe('Responsive Design', () => {
    test('dashboard mobile layout matches baseline', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 }) // iPhone SE
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="mobile-dashboard"]')
      
      await expect(page).toHaveScreenshot('dashboard-mobile-layout.png', {
        fullPage: true,
        animations: 'disabled'
      })
    })

    test('dashboard tablet layout matches baseline', async ({ page }) => {
      await page.setViewportSize({ width: 768, height: 1024 }) // iPad
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      await expect(page).toHaveScreenshot('dashboard-tablet-layout.png', {
        fullPage: true,
        animations: 'disabled'
      })
    })

    test('dashboard desktop layout matches baseline', async ({ page }) => {
      await page.setViewportSize({ width: 1920, height: 1080 }) // Desktop
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      await expect(page).toHaveScreenshot('dashboard-desktop-layout.png', {
        fullPage: true,
        animations: 'disabled'
      })
    })
  })

  test.describe('Theme Variations', () => {
    test('dashboard light theme matches baseline', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      // Ensure light theme is active
      await page.evaluate(() => {
        document.documentElement.classList.remove('dark')
        document.documentElement.setAttribute('data-theme', 'light')
      })
      
      await expect(page).toHaveScreenshot('dashboard-light-theme.png', {
        fullPage: true,
        animations: 'disabled'
      })
    })

    test('dashboard dark theme matches baseline', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      // Enable dark theme
      await page.evaluate(() => {
        document.documentElement.classList.add('dark')
        document.documentElement.setAttribute('data-theme', 'dark')
      })
      
      await expect(page).toHaveScreenshot('dashboard-dark-theme.png', {
        fullPage: true,
        animations: 'disabled'
      })
    })

    test('dashboard high contrast theme matches baseline', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      // Enable high contrast theme
      await page.evaluate(() => {
        document.documentElement.classList.add('high-contrast')
        document.documentElement.setAttribute('data-theme', 'high-contrast')
      })
      
      await expect(page).toHaveScreenshot('dashboard-high-contrast-theme.png', {
        fullPage: true,
        animations: 'disabled'
      })
    })
  })

  test.describe('Interactive States', () => {
    test('dashboard selector dropdown matches baseline', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-selector"]')
      
      // Open dropdown
      await page.click('[data-testid="dashboard-selector-button"]')
      await page.waitForSelector('[data-testid="dashboard-selector-dropdown"]')
      
      const dropdown = page.locator('[data-testid="dashboard-selector-dropdown"]')
      await expect(dropdown).toHaveScreenshot('dashboard-selector-dropdown.png')
    })

    test('dashboard navigation menu matches baseline', async ({ page }) => {
      await page.goto('/admin')
      
      // Open navigation menu (if mobile)
      const menuButton = page.locator('[data-testid="mobile-menu-button"]')
      if (await menuButton.isVisible()) {
        await menuButton.click()
        await page.waitForSelector('[data-testid="mobile-navigation-menu"]')
        
        const menu = page.locator('[data-testid="mobile-navigation-menu"]')
        await expect(menu).toHaveScreenshot('dashboard-mobile-menu.png')
      }
    })

    test('dashboard card hover state matches baseline', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-card"]')
      
      // Hover over first card
      const firstCard = page.locator('[data-testid="dashboard-card"]').first()
      await firstCard.hover()
      
      await expect(firstCard).toHaveScreenshot('dashboard-card-hover.png')
    })

    test('dashboard button focus states match baseline', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-selector-button"]')
      
      // Focus on button using keyboard
      await page.keyboard.press('Tab')
      
      const focusedButton = page.locator('[data-testid="dashboard-selector-button"]:focus')
      await expect(focusedButton).toHaveScreenshot('dashboard-button-focus.png')
    })
  })

  test.describe('Loading States', () => {
    test('dashboard skeleton loading matches baseline', async ({ page }) => {
      // Intercept all API calls to show skeleton
      await page.route('/admin/api/**', route => {
        // Delay all API responses
        setTimeout(() => route.continue(), 5000)
      })
      
      await page.goto('/admin')
      
      // Wait for skeleton to appear
      await page.waitForSelector('[data-testid="dashboard-skeleton"]')
      
      const skeleton = page.locator('[data-testid="dashboard-skeleton"]')
      await expect(skeleton).toHaveScreenshot('dashboard-skeleton-loading.png')
    })

    test('dashboard empty state matches baseline', async ({ page }) => {
      // Mock empty dashboard response
      await page.route('/admin/api/dashboards', route => {
        route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ dashboards: [] })
        })
      })
      
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-empty-state"]')
      
      const emptyState = page.locator('[data-testid="dashboard-empty-state"]')
      await expect(emptyState).toHaveScreenshot('dashboard-empty-state.png')
    })
  })

  test.describe('Error States', () => {
    test('dashboard error page matches baseline', async ({ page }) => {
      // Mock error response
      await page.route('/admin/api/dashboards', route => {
        route.fulfill({
          status: 500,
          contentType: 'application/json',
          body: JSON.stringify({ error: 'Internal Server Error' })
        })
      })
      
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-error-state"]')
      
      const errorState = page.locator('[data-testid="dashboard-error-state"]')
      await expect(errorState).toHaveScreenshot('dashboard-error-state.png')
    })

    test('dashboard 404 page matches baseline', async ({ page }) => {
      await page.goto('/admin/dashboards/non-existent')
      await page.waitForSelector('[data-testid="dashboard-404"]')
      
      await expect(page).toHaveScreenshot('dashboard-404-page.png', {
        fullPage: true,
        animations: 'disabled'
      })
    })
  })

  test.describe('Accessibility Visual States', () => {
    test('dashboard with reduced motion matches baseline', async ({ page }) => {
      // Enable reduced motion preference
      await page.emulateMedia({ reducedMotion: 'reduce' })
      
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      await expect(page).toHaveScreenshot('dashboard-reduced-motion.png', {
        fullPage: true,
        animations: 'disabled'
      })
    })

    test('dashboard with large text matches baseline', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      // Simulate large text preference
      await page.addStyleTag({
        content: `
          * {
            font-size: 1.2em !important;
            line-height: 1.6 !important;
          }
        `
      })
      
      await expect(page).toHaveScreenshot('dashboard-large-text.png', {
        fullPage: true,
        animations: 'disabled'
      })
    })
  })

  test.describe('Print Styles', () => {
    test('dashboard print layout matches baseline', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      // Emulate print media
      await page.emulateMedia({ media: 'print' })
      
      await expect(page).toHaveScreenshot('dashboard-print-layout.png', {
        fullPage: true,
        animations: 'disabled'
      })
    })
  })

  test.describe('Cross-Browser Consistency', () => {
    test('dashboard renders consistently across browsers', async ({ page, browserName }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      await page.waitForLoadState('networkidle')
      
      // Take browser-specific screenshot
      await expect(page).toHaveScreenshot(`dashboard-${browserName}.png`, {
        fullPage: true,
        animations: 'disabled',
        threshold: 0.3 // Allow slight differences between browsers
      })
    })
  })
})

test.describe('Dashboard Visual Regression - Performance', () => {
  test('dashboard visual performance metrics', async ({ page }) => {
    await page.goto('/admin')
    
    // Measure visual performance
    const metrics = await page.evaluate(() => {
      return new Promise((resolve) => {
        new PerformanceObserver((list) => {
          const entries = list.getEntries()
          const paintEntries = entries.filter(entry => 
            entry.entryType === 'paint'
          )
          
          resolve({
            firstPaint: paintEntries.find(entry => entry.name === 'first-paint')?.startTime,
            firstContentfulPaint: paintEntries.find(entry => entry.name === 'first-contentful-paint')?.startTime,
            largestContentfulPaint: entries.find(entry => entry.entryType === 'largest-contentful-paint')?.startTime
          })
        }).observe({ entryTypes: ['paint', 'largest-contentful-paint'] })
      })
    })
    
    // Assert performance thresholds
    expect(metrics.firstPaint).toBeLessThan(1000) // < 1 second
    expect(metrics.firstContentfulPaint).toBeLessThan(1500) // < 1.5 seconds
    expect(metrics.largestContentfulPaint).toBeLessThan(2500) // < 2.5 seconds
  })
})
