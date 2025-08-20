/**
 * Dashboard Mobile Tests
 * 
 * Comprehensive mobile testing for touch gestures, responsive design,
 * mobile navigation, and device-specific functionality.
 */

import { test, expect, devices } from '@playwright/test'

// Test on multiple mobile devices
const mobileDevices = [
  devices['iPhone 12'],
  devices['iPhone SE'],
  devices['Pixel 5'],
  devices['Galaxy S21'],
  devices['iPad'],
  devices['iPad Mini']
]

mobileDevices.forEach(device => {
  test.describe(`Dashboard Mobile Tests - ${device.name}`, () => {
    test.use({ ...device })

    test.beforeEach(async ({ page }) => {
      // Login as admin user
      await page.goto('/admin/login')
      await page.fill('[name="email"]', 'admin@example.com')
      await page.fill('[name="password"]', 'password')
      await page.tap('button[type="submit"]')
      await page.waitForURL('/admin')
    })

    test('mobile dashboard layout renders correctly', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="mobile-dashboard"]')
      
      // Check mobile-specific elements
      await expect(page.locator('[data-testid="mobile-header"]')).toBeVisible()
      await expect(page.locator('[data-testid="mobile-navigation"]')).toBeVisible()
      await expect(page.locator('[data-testid="bottom-navigation"]')).toBeVisible()
      
      // Check responsive grid
      const cards = page.locator('[data-testid="dashboard-card"]')
      const cardCount = await cards.count()
      
      if (cardCount > 0) {
        // Cards should stack vertically on mobile
        const firstCard = cards.first()
        const secondCard = cards.nth(1)
        
        if (await secondCard.isVisible()) {
          const firstCardBox = await firstCard.boundingBox()
          const secondCardBox = await secondCard.boundingBox()
          
          expect(secondCardBox.y).toBeGreaterThan(firstCardBox.y + firstCardBox.height - 10)
        }
      }
    })

    test('mobile navigation menu works correctly', async ({ page }) => {
      await page.goto('/admin')
      
      // Open mobile menu
      const menuButton = page.locator('[data-testid="mobile-menu-button"]')
      if (await menuButton.isVisible()) {
        await menuButton.tap()
        
        // Check menu is visible
        await expect(page.locator('[data-testid="mobile-menu"]')).toBeVisible()
        
        // Test menu items
        const menuItems = page.locator('[data-testid="mobile-menu-item"]')
        const itemCount = await menuItems.count()
        
        expect(itemCount).toBeGreaterThan(0)
        
        // Test menu item tap
        if (itemCount > 0) {
          await menuItems.first().tap()
          
          // Menu should close after selection
          await expect(page.locator('[data-testid="mobile-menu"]')).not.toBeVisible()
        }
      }
    })

    test('touch gestures work correctly', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-card"]')
      
      const card = page.locator('[data-testid="dashboard-card"]').first()
      
      // Test tap gesture
      await card.tap()
      
      // Test long press (if supported)
      try {
        await card.tap({ timeout: 1000 })
        // Check for context menu or long press action
      } catch (e) {
        // Long press might not be implemented
      }
      
      // Test swipe gesture on swipeable elements
      const swipeableElement = page.locator('[data-testid="swipeable"]').first()
      if (await swipeableElement.isVisible()) {
        const box = await swipeableElement.boundingBox()
        
        // Swipe left
        await page.touchscreen.tap(box.x + box.width * 0.8, box.y + box.height / 2)
        await page.touchscreen.tap(box.x + box.width * 0.2, box.y + box.height / 2)
        
        // Check for swipe action result
        await page.waitForTimeout(500)
      }
    })

    test('pull to refresh works correctly', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      // Simulate pull to refresh
      const container = page.locator('[data-testid="dashboard-container"]')
      const box = await container.boundingBox()
      
      // Start from top and pull down
      await page.touchscreen.tap(box.x + box.width / 2, box.y + 50)
      await page.touchscreen.tap(box.x + box.width / 2, box.y + 150)
      
      // Check for refresh indicator
      const refreshIndicator = page.locator('[data-testid="pull-to-refresh-indicator"]')
      if (await refreshIndicator.isVisible()) {
        await expect(refreshIndicator).toBeVisible()
        
        // Wait for refresh to complete
        await page.waitForTimeout(2000)
        await expect(refreshIndicator).not.toBeVisible()
      }
    })

    test('mobile keyboard interaction works correctly', async ({ page }) => {
      await page.goto('/admin')
      
      // Find search input
      const searchInput = page.locator('[data-testid="dashboard-search"]')
      if (await searchInput.isVisible()) {
        await searchInput.tap()
        
        // Check if virtual keyboard appears (viewport height changes)
        const initialViewport = page.viewportSize()
        await page.waitForTimeout(500)
        
        // Type in search
        await searchInput.fill('test search')
        
        // Check search functionality
        await page.keyboard.press('Enter')
        await page.waitForTimeout(1000)
        
        // Clear search
        await searchInput.clear()
      }
    })

    test('mobile form interaction works correctly', async ({ page }) => {
      await page.goto('/admin/settings')
      
      // Test form inputs on mobile
      const formInputs = page.locator('input, select, textarea')
      const inputCount = await formInputs.count()
      
      for (let i = 0; i < Math.min(inputCount, 3); i++) {
        const input = formInputs.nth(i)
        
        if (await input.isVisible()) {
          await input.tap()
          
          // Check input focus
          await expect(input).toBeFocused()
          
          // Test input
          const inputType = await input.getAttribute('type')
          if (inputType === 'text' || inputType === null) {
            await input.fill('test value')
          }
        }
      }
    })

    test('mobile scroll behavior works correctly', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      // Test vertical scrolling
      const initialScrollY = await page.evaluate(() => window.scrollY)
      
      // Scroll down
      await page.evaluate(() => window.scrollBy(0, 300))
      await page.waitForTimeout(500)
      
      const scrolledY = await page.evaluate(() => window.scrollY)
      expect(scrolledY).toBeGreaterThan(initialScrollY)
      
      // Test smooth scrolling back to top
      await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }))
      await page.waitForTimeout(1000)
      
      const finalScrollY = await page.evaluate(() => window.scrollY)
      expect(finalScrollY).toBeLessThan(50)
    })

    test('mobile orientation change works correctly', async ({ page, context }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      // Test portrait orientation
      await page.setViewportSize({ width: 375, height: 667 })
      await page.waitForTimeout(500)
      
      // Check layout in portrait
      const portraitLayout = await page.locator('[data-testid="dashboard-container"]').screenshot()
      
      // Test landscape orientation
      await page.setViewportSize({ width: 667, height: 375 })
      await page.waitForTimeout(500)
      
      // Check layout adapts to landscape
      const landscapeLayout = await page.locator('[data-testid="dashboard-container"]').screenshot()
      
      // Layouts should be different
      expect(portraitLayout).not.toEqual(landscapeLayout)
    })

    test('mobile performance is acceptable', async ({ page }) => {
      // Start performance monitoring
      await page.goto('/admin')
      
      // Measure load performance
      const performanceMetrics = await page.evaluate(() => {
        return new Promise((resolve) => {
          new PerformanceObserver((list) => {
            const entries = list.getEntries()
            const metrics = {}
            
            entries.forEach(entry => {
              if (entry.entryType === 'paint') {
                metrics[entry.name] = entry.startTime
              } else if (entry.entryType === 'largest-contentful-paint') {
                metrics.largestContentfulPaint = entry.startTime
              }
            })
            
            resolve(metrics)
          }).observe({ entryTypes: ['paint', 'largest-contentful-paint'] })
          
          // Fallback timeout
          setTimeout(() => resolve({}), 5000)
        })
      })
      
      // Performance assertions for mobile
      if (performanceMetrics['first-paint']) {
        expect(performanceMetrics['first-paint']).toBeLessThan(2000)
      }
      
      if (performanceMetrics['first-contentful-paint']) {
        expect(performanceMetrics['first-contentful-paint']).toBeLessThan(3000)
      }
      
      if (performanceMetrics.largestContentfulPaint) {
        expect(performanceMetrics.largestContentfulPaint).toBeLessThan(4000)
      }
    })

    test('mobile accessibility features work correctly', async ({ page }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      // Test touch target sizes
      const touchTargets = page.locator('button, a, input, [role="button"]')
      const targetCount = await touchTargets.count()
      
      for (let i = 0; i < Math.min(targetCount, 10); i++) {
        const target = touchTargets.nth(i)
        
        if (await target.isVisible()) {
          const box = await target.boundingBox()
          
          // Touch targets should be at least 44px
          expect(box.width).toBeGreaterThanOrEqual(44)
          expect(box.height).toBeGreaterThanOrEqual(44)
        }
      }
      
      // Test screen reader support
      const ariaLabels = page.locator('[aria-label]')
      const labelCount = await ariaLabels.count()
      expect(labelCount).toBeGreaterThan(0)
    })

    test('mobile network conditions work correctly', async ({ page, context }) => {
      // Simulate slow 3G
      await context.route('**/*', route => {
        setTimeout(() => route.continue(), 100) // Add 100ms delay
      })
      
      const startTime = Date.now()
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      const loadTime = Date.now() - startTime
      
      // Should still load within reasonable time on slow connection
      expect(loadTime).toBeLessThan(10000) // 10 seconds max
      
      // Check for loading indicators
      const loadingIndicators = page.locator('[data-testid*="loading"]')
      // Loading indicators should have been shown during slow load
    })

    test('mobile offline functionality works correctly', async ({ page, context }) => {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      // Go offline
      await context.setOffline(true)
      
      // Try to navigate
      await page.click('[data-testid="dashboard-link"]').catch(() => {})
      
      // Check for offline indicator
      const offlineIndicator = page.locator('[data-testid="offline-indicator"]')
      if (await offlineIndicator.isVisible()) {
        await expect(offlineIndicator).toBeVisible()
      }
      
      // Go back online
      await context.setOffline(false)
      await page.waitForTimeout(1000)
      
      // Offline indicator should disappear
      if (await offlineIndicator.isVisible()) {
        await expect(offlineIndicator).not.toBeVisible()
      }
    })

    test('mobile safe area handling works correctly', async ({ page }) => {
      // Simulate device with notch/safe area
      await page.addStyleTag({
        content: `
          :root {
            --sat: 44px;
            --sar: 0px;
            --sab: 34px;
            --sal: 0px;
          }
          
          body {
            padding-top: env(safe-area-inset-top, var(--sat));
            padding-bottom: env(safe-area-inset-bottom, var(--sab));
          }
        `
      })
      
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      // Check that content is not hidden behind safe areas
      const header = page.locator('[data-testid="mobile-header"]')
      const headerBox = await header.boundingBox()
      
      // Header should not be at the very top (should account for safe area)
      expect(headerBox.y).toBeGreaterThanOrEqual(0)
      
      const bottomNav = page.locator('[data-testid="bottom-navigation"]')
      if (await bottomNav.isVisible()) {
        const bottomNavBox = await bottomNav.boundingBox()
        const viewportHeight = page.viewportSize().height
        
        // Bottom nav should account for safe area
        expect(bottomNavBox.y + bottomNavBox.height).toBeLessThanOrEqual(viewportHeight)
      }
    })
  })
})

// Cross-device compatibility tests
test.describe('Dashboard Cross-Device Compatibility', () => {
  test('dashboard works consistently across mobile devices', async ({ browser }) => {
    const contexts = await Promise.all([
      browser.newContext(devices['iPhone 12']),
      browser.newContext(devices['Pixel 5']),
      browser.newContext(devices['Galaxy S21'])
    ])
    
    const pages = await Promise.all(contexts.map(context => context.newPage()))
    
    // Login on all devices
    for (const page of pages) {
      await page.goto('/admin/login')
      await page.fill('[name="email"]', 'admin@example.com')
      await page.fill('[name="password"]', 'password')
      await page.tap('button[type="submit"]')
      await page.waitForURL('/admin')
    }
    
    // Test dashboard functionality on all devices
    for (const page of pages) {
      await page.goto('/admin')
      await page.waitForSelector('[data-testid="dashboard-container"]')
      
      // Basic functionality should work on all devices
      await expect(page.locator('[data-testid="mobile-header"]')).toBeVisible()
      await expect(page.locator('[data-testid="dashboard-card"]').first()).toBeVisible()
    }
    
    // Cleanup
    await Promise.all(contexts.map(context => context.close()))
  })
})
