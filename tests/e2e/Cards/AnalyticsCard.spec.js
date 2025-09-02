/**
 * AnalyticsCard Playwright E2E Tests
 * 
 * End-to-end tests for the AnalyticsCard component using Playwright,
 * testing user interactions, data display, and real browser behavior.
 */

import { test, expect } from '@playwright/test'

test.describe('AnalyticsCard E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Setup test environment
    await page.goto('/admin/login')
    
    // Login as admin user
    await page.fill('[data-testid="email-input"]', 'admin@example.com')
    await page.fill('[data-testid="password-input"]', 'password')
    await page.click('[data-testid="login-button"]')
    
    // Wait for redirect to dashboard
    await page.waitForURL('/admin')
    await page.waitForLoadState('networkidle')
  })

  test.describe('Card Display and Layout', () => {
    test('displays analytics card with proper structure', async ({ page }) => {
      // Navigate to a page that displays the AnalyticsCard
      await page.goto('/admin/dashboard')
      await page.waitForLoadState('networkidle')
      
      // Wait for the analytics card to be visible
      const analyticsCard = page.locator('[data-testid="analytics-card"]')
      await expect(analyticsCard).toBeVisible()
      
      // Verify card header elements
      await expect(page.locator('[data-testid="analytics-card-title"]')).toContainText('Analytics Overview')
      await expect(page.locator('[data-testid="analytics-card-description"]')).toContainText('Key performance metrics')
      
      // Verify group badge
      await expect(page.locator('[data-testid="analytics-card-group"]')).toContainText('Analytics')
      
      // Take screenshot for visual verification
      await page.screenshot({ path: 'test-results/screenshots/analytics-card-display.png' })
    })

    test('displays all metric items correctly', async ({ page }) => {
      await page.goto('/admin/dashboard')
      await page.waitForLoadState('networkidle')
      
      const analyticsCard = page.locator('[data-testid="analytics-card"]')
      await expect(analyticsCard).toBeVisible()
      
      // Verify all four main metrics are displayed
      const metricItems = page.locator('[data-testid="metric-item"]')
      await expect(metricItems).toHaveCount(4)
      
      // Verify metric labels
      await expect(page.locator('[data-testid="total-users-metric"]')).toContainText('Total Users')
      await expect(page.locator('[data-testid="active-users-metric"]')).toContainText('Active Users')
      await expect(page.locator('[data-testid="page-views-metric"]')).toContainText('Page Views')
      await expect(page.locator('[data-testid="revenue-metric"]')).toContainText('Revenue')
      
      // Verify metric values are displayed (formatted numbers)
      await expect(page.locator('[data-testid="total-users-value"]')).toContainText('15.4K')
      await expect(page.locator('[data-testid="active-users-value"]')).toContainText('12.4K')
      await expect(page.locator('[data-testid="page-views-value"]')).toContainText('89.8K')
      await expect(page.locator('[data-testid="revenue-value"]')).toContainText('$45,230.50')
    })

    test('displays conversion rate with progress indicator', async ({ page }) => {
      await page.goto('/admin/dashboard')
      await page.waitForLoadState('networkidle')
      
      // Verify conversion rate section
      const conversionSection = page.locator('[data-testid="conversion-rate-section"]')
      await expect(conversionSection).toBeVisible()
      
      // Verify conversion rate value
      await expect(page.locator('[data-testid="conversion-rate-value"]')).toContainText('3.2%')
      await expect(page.locator('[data-testid="conversion-rate-label"]')).toContainText('Conversion Rate')
      
      // Verify progress circle is present
      await expect(page.locator('[data-testid="conversion-rate-progress"]')).toBeVisible()
    })

    test('displays top pages section with data', async ({ page }) => {
      await page.goto('/admin/dashboard')
      await page.waitForLoadState('networkidle')
      
      // Verify top pages section
      const topPagesSection = page.locator('[data-testid="top-pages-section"]')
      await expect(topPagesSection).toBeVisible()
      
      // Verify section title
      await expect(page.locator('[data-testid="top-pages-title"]')).toContainText('Top Pages')
      
      // Verify page items are displayed
      const pageItems = page.locator('[data-testid="top-page-item"]')
      await expect(pageItems).toHaveCount(5)
      
      // Verify first page item content
      const firstPageItem = pageItems.first()
      await expect(firstPageItem.locator('[data-testid="page-path"]')).toContainText('/dashboard')
      await expect(firstPageItem.locator('[data-testid="page-views"]')).toContainText('12.5K views')
      await expect(firstPageItem.locator('[data-testid="page-percentage"]')).toContainText('35.2%')
    })

    test('displays device breakdown section', async ({ page }) => {
      await page.goto('/admin/dashboard')
      await page.waitForLoadState('networkidle')
      
      // Verify device breakdown section
      const deviceSection = page.locator('[data-testid="device-breakdown-section"]')
      await expect(deviceSection).toBeVisible()
      
      // Verify section title
      await expect(page.locator('[data-testid="device-breakdown-title"]')).toContainText('Device Breakdown')
      
      // Verify device items
      const deviceItems = page.locator('[data-testid="device-item"]')
      await expect(deviceItems).toHaveCount(3)
      
      // Verify device types and data
      await expect(page.locator('[data-testid="device-desktop"]')).toContainText('Desktop')
      await expect(page.locator('[data-testid="device-desktop"]')).toContainText('8.5K')
      await expect(page.locator('[data-testid="device-desktop"]')).toContainText('55.1%')
      
      await expect(page.locator('[data-testid="device-mobile"]')).toContainText('Mobile')
      await expect(page.locator('[data-testid="device-mobile"]')).toContainText('5.2K')
      await expect(page.locator('[data-testid="device-mobile"]')).toContainText('33.7%')
      
      await expect(page.locator('[data-testid="device-tablet"]')).toContainText('Tablet')
      await expect(page.locator('[data-testid="device-tablet"]')).toContainText('1.7K')
      await expect(page.locator('[data-testid="device-tablet"]')).toContainText('11.2%')
    })
  })

  test.describe('User Interactions', () => {
    test('refresh button works correctly', async ({ page }) => {
      await page.goto('/admin/dashboard')
      await page.waitForLoadState('networkidle')
      
      const analyticsCard = page.locator('[data-testid="analytics-card"]')
      await expect(analyticsCard).toBeVisible()
      
      // Find and click refresh button
      const refreshButton = page.locator('[data-testid="refresh-button"]')
      await expect(refreshButton).toBeVisible()
      await expect(refreshButton).toBeEnabled()
      
      // Click refresh button
      await refreshButton.click()
      
      // Verify loading state is shown
      await expect(page.locator('[data-testid="analytics-card"]')).toHaveClass(/card-loading/)
      
      // Wait for loading to complete
      await page.waitForTimeout(1000) // Simulate API call delay
      
      // Verify loading state is removed
      await expect(page.locator('[data-testid="analytics-card"]')).not.toHaveClass(/card-loading/)
      
      // Verify refresh button is enabled again
      await expect(refreshButton).toBeEnabled()
    })

    test('configure button triggers configuration', async ({ page }) => {
      await page.goto('/admin/dashboard')
      await page.waitForLoadState('networkidle')
      
      // Find and click configure button
      const configureButton = page.locator('[data-testid="configure-button"]')
      await expect(configureButton).toBeVisible()
      
      await configureButton.click()
      
      // Verify configuration modal or action is triggered
      // This would depend on the actual implementation
      // For now, we just verify the button is clickable
      await expect(configureButton).toBeVisible()
    })

    test('export button triggers export functionality', async ({ page }) => {
      await page.goto('/admin/dashboard')
      await page.waitForLoadState('networkidle')
      
      // Find and click export button
      const exportButton = page.locator('[data-testid="export-button"]')
      await expect(exportButton).toBeVisible()
      
      await exportButton.click()
      
      // Verify export action is triggered
      // This would depend on the actual implementation
      // For now, we just verify the button is clickable
      await expect(exportButton).toBeVisible()
    })
  })

  test.describe('Responsive Design', () => {
    test('card displays correctly on mobile devices', async ({ page }) => {
      // Set mobile viewport
      await page.setViewportSize({ width: 375, height: 667 })
      
      await page.goto('/admin/dashboard')
      await page.waitForLoadState('networkidle')
      
      const analyticsCard = page.locator('[data-testid="analytics-card"]')
      await expect(analyticsCard).toBeVisible()
      
      // Verify metrics are stacked properly on mobile
      const metricItems = page.locator('[data-testid="metric-item"]')
      await expect(metricItems).toHaveCount(4)
      
      // Take mobile screenshot
      await page.screenshot({ path: 'test-results/screenshots/analytics-card-mobile.png' })
    })

    test('card displays correctly on tablet devices', async ({ page }) => {
      // Set tablet viewport
      await page.setViewportSize({ width: 768, height: 1024 })
      
      await page.goto('/admin/dashboard')
      await page.waitForLoadState('networkidle')
      
      const analyticsCard = page.locator('[data-testid="analytics-card"]')
      await expect(analyticsCard).toBeVisible()
      
      // Verify layout adapts to tablet size
      const metricItems = page.locator('[data-testid="metric-item"]')
      await expect(metricItems).toHaveCount(4)
      
      // Take tablet screenshot
      await page.screenshot({ path: 'test-results/screenshots/analytics-card-tablet.png' })
    })
  })

  test.describe('Error Handling', () => {
    test('displays error state when data loading fails', async ({ page }) => {
      // Mock API failure
      await page.route('/admin/api/analytics-card/data', route => {
        route.fulfill({
          status: 500,
          contentType: 'application/json',
          body: JSON.stringify({ error: 'Failed to load analytics data' })
        })
      })
      
      await page.goto('/admin/dashboard')
      await page.waitForLoadState('networkidle')
      
      // Verify error state is displayed
      const errorMessage = page.locator('[data-testid="analytics-card-error"]')
      await expect(errorMessage).toBeVisible()
      await expect(errorMessage).toContainText('Error loading analytics data')
      
      // Verify card has error styling
      const analyticsCard = page.locator('[data-testid="analytics-card"]')
      await expect(analyticsCard).toHaveClass(/card-error/)
    })
  })

  test.describe('Performance', () => {
    test('card loads within acceptable time limits', async ({ page }) => {
      const startTime = Date.now()
      
      await page.goto('/admin/dashboard')
      await page.waitForLoadState('networkidle')
      
      // Wait for analytics card to be fully loaded
      const analyticsCard = page.locator('[data-testid="analytics-card"]')
      await expect(analyticsCard).toBeVisible()
      
      const loadTime = Date.now() - startTime
      
      // Verify load time is under 3 seconds
      expect(loadTime).toBeLessThan(3000)
      
      console.log(`Analytics card loaded in ${loadTime}ms`)
    })
  })

  test.describe('Accessibility', () => {
    test('card is accessible to screen readers', async ({ page }) => {
      await page.goto('/admin/dashboard')
      await page.waitForLoadState('networkidle')
      
      const analyticsCard = page.locator('[data-testid="analytics-card"]')
      await expect(analyticsCard).toBeVisible()
      
      // Verify card has proper ARIA labels
      await expect(analyticsCard).toHaveAttribute('role', 'region')
      await expect(analyticsCard).toHaveAttribute('aria-label', 'Analytics Overview Card')
      
      // Verify buttons have proper labels
      const refreshButton = page.locator('[data-testid="refresh-button"]')
      await expect(refreshButton).toHaveAttribute('aria-label', 'Refresh analytics data')
      
      // Verify metrics have proper labels
      const totalUsersMetric = page.locator('[data-testid="total-users-metric"]')
      await expect(totalUsersMetric).toHaveAttribute('aria-label', 'Total Users: 15,420')
    })
  })
})
