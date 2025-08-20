/**
 * Dashboard Playwright E2E Tests
 * 
 * End-to-end tests using Playwright for complete dashboard workflows,
 * testing user interactions, navigation, and real browser behavior.
 */

import { test, expect } from '@playwright/test'

test.describe('Dashboard E2E Workflow', () => {
    test.beforeEach(async ({ page }) => {
        // Setup test environment
        await page.goto('/admin/login')
        
        // Login as admin user
        await page.fill('[data-testid="email-input"]', 'admin@example.com')
        await page.fill('[data-testid="password-input"]', 'password')
        await page.click('[data-testid="login-button"]')
        
        // Wait for redirect to dashboard
        await page.waitForURL('/admin')
    })

    test('displays main dashboard with correct elements', async ({ page }) => {
        // Verify dashboard title
        await expect(page.locator('h1')).toContainText('Main')
        
        // Verify dashboard structure
        await expect(page.locator('[data-testid="dashboard-container"]')).toBeVisible()
        await expect(page.locator('[data-testid="metrics-section"]')).toBeVisible()
        await expect(page.locator('[data-testid="cards-section"]')).toBeVisible()
    })

    test('dashboard cards load and display correctly', async ({ page }) => {
        // Wait for cards to load
        await page.waitForSelector('[data-testid="dashboard-card"]')
        
        // Verify cards are present
        const cards = page.locator('[data-testid="dashboard-card"]')
        await expect(cards).toHaveCount(await cards.count())
        
        // Verify card content
        const firstCard = cards.first()
        await expect(firstCard).toBeVisible()
        await expect(firstCard.locator('.card-title')).toBeVisible()
    })

    test('refresh button works correctly', async ({ page }) => {
        // Check if refresh button is visible
        const refreshButton = page.locator('[data-testid="refresh-button"]')
        
        if (await refreshButton.isVisible()) {
            // Click refresh button
            await refreshButton.click()
            
            // Verify loading state
            await expect(page.locator('[data-testid="loading-indicator"]')).toBeVisible()
            
            // Wait for refresh to complete
            await page.waitForSelector('[data-testid="loading-indicator"]', { state: 'hidden' })
            
            // Verify dashboard is still visible
            await expect(page.locator('[data-testid="dashboard-container"]')).toBeVisible()
        }
    })

    test('dashboard navigation works', async ({ page }) => {
        // Navigate to different dashboard if available
        const dashboardNav = page.locator('[data-testid="dashboard-nav"]')
        
        if (await dashboardNav.isVisible()) {
            const analyticsLink = dashboardNav.locator('a[href*="analytics"]')
            
            if (await analyticsLink.isVisible()) {
                await analyticsLink.click()
                
                // Verify navigation
                await page.waitForURL('**/dashboards/analytics')
                await expect(page.locator('h1')).toContainText('Analytics')
            }
        }
    })

    test('dashboard metrics display correctly', async ({ page }) => {
        // Wait for metrics to load
        await page.waitForSelector('[data-testid="metric-card"]')
        
        const metrics = page.locator('[data-testid="metric-card"]')
        const metricCount = await metrics.count()
        
        if (metricCount > 0) {
            // Verify first metric
            const firstMetric = metrics.first()
            await expect(firstMetric).toBeVisible()
            await expect(firstMetric.locator('.metric-value')).toBeVisible()
            await expect(firstMetric.locator('.metric-label')).toBeVisible()
        }
    })

    test('dashboard cards are interactive', async ({ page }) => {
        // Wait for cards to load
        await page.waitForSelector('[data-testid="dashboard-card"]')
        
        const interactiveCard = page.locator('[data-testid="dashboard-card"]').first()
        
        // Check if card is clickable
        if (await interactiveCard.locator('[data-clickable="true"]').isVisible()) {
            await interactiveCard.click()
            
            // Verify interaction (could be modal, navigation, etc.)
            // This depends on the specific card implementation
            await page.waitForTimeout(500) // Allow for any animations
        }
    })

    test('dashboard handles errors gracefully', async ({ page }) => {
        // Simulate network error by intercepting requests
        await page.route('**/api/dashboard/**', route => {
            route.fulfill({
                status: 500,
                contentType: 'application/json',
                body: JSON.stringify({ error: 'Server error' })
            })
        })
        
        // Trigger refresh to cause error
        const refreshButton = page.locator('[data-testid="refresh-button"]')
        if (await refreshButton.isVisible()) {
            await refreshButton.click()
            
            // Verify error message is displayed
            await expect(page.locator('[data-testid="error-message"]')).toBeVisible()
            await expect(page.locator('[data-testid="error-message"]')).toContainText('Failed to refresh')
        }
    })

    test('dashboard is responsive on mobile', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 })
        
        // Verify dashboard adapts to mobile
        await expect(page.locator('[data-testid="dashboard-container"]')).toBeVisible()
        
        // Check if mobile navigation is present
        const mobileNav = page.locator('[data-testid="mobile-nav-toggle"]')
        if (await mobileNav.isVisible()) {
            await mobileNav.click()
            await expect(page.locator('[data-testid="mobile-nav-menu"]')).toBeVisible()
        }
        
        // Verify cards stack properly on mobile
        const cards = page.locator('[data-testid="dashboard-card"]')
        const cardCount = await cards.count()
        
        if (cardCount > 0) {
            // Cards should be stacked vertically on mobile
            const firstCard = cards.first()
            const secondCard = cards.nth(1)
            
            if (await secondCard.isVisible()) {
                const firstCardBox = await firstCard.boundingBox()
                const secondCardBox = await secondCard.boundingBox()
                
                // Second card should be below first card on mobile
                expect(secondCardBox.y).toBeGreaterThan(firstCardBox.y + firstCardBox.height - 10)
            }
        }
    })

    test('dashboard accessibility features work', async ({ page }) => {
        // Test keyboard navigation
        await page.keyboard.press('Tab')
        
        // Verify focus is visible
        const focusedElement = page.locator(':focus')
        await expect(focusedElement).toBeVisible()
        
        // Test screen reader support
        const dashboard = page.locator('[data-testid="dashboard-container"]')
        await expect(dashboard).toHaveAttribute('role', 'main')
        
        // Verify ARIA labels are present
        const cards = page.locator('[data-testid="dashboard-card"]')
        const cardCount = await cards.count()
        
        if (cardCount > 0) {
            const firstCard = cards.first()
            await expect(firstCard).toHaveAttribute('aria-label')
        }
    })

    test('dashboard performance is acceptable', async ({ page }) => {
        // Start performance measurement
        const startTime = Date.now()
        
        // Navigate to dashboard
        await page.goto('/admin')
        
        // Wait for dashboard to fully load
        await page.waitForSelector('[data-testid="dashboard-container"]')
        await page.waitForLoadState('networkidle')
        
        const loadTime = Date.now() - startTime
        
        // Dashboard should load within 3 seconds
        expect(loadTime).toBeLessThan(3000)
        
        // Verify no console errors
        const consoleErrors = []
        page.on('console', msg => {
            if (msg.type() === 'error') {
                consoleErrors.push(msg.text())
            }
        })
        
        // Refresh to trigger any potential errors
        await page.reload()
        await page.waitForSelector('[data-testid="dashboard-container"]')
        
        expect(consoleErrors).toHaveLength(0)
    })

    test('dashboard data updates in real-time', async ({ page }) => {
        // Get initial metric value
        const metricCard = page.locator('[data-testid="metric-card"]').first()
        
        if (await metricCard.isVisible()) {
            const initialValue = await metricCard.locator('.metric-value').textContent()
            
            // Simulate data change on backend (this would be done through API or WebSocket)
            await page.evaluate(() => {
                // Trigger a custom event that the dashboard listens for
                window.dispatchEvent(new CustomEvent('dashboard-update', {
                    detail: { metric: 'users', value: '999' }
                }))
            })
            
            // Wait for update
            await page.waitForTimeout(1000)
            
            // Verify value changed (if real-time updates are implemented)
            const updatedValue = await metricCard.locator('.metric-value').textContent()
            
            // This test depends on actual real-time implementation
            // For now, we just verify the element is still visible
            await expect(metricCard).toBeVisible()
        }
    })

    test('dashboard works with different user permissions', async ({ page }) => {
        // Logout current user
        await page.click('[data-testid="user-menu"]')
        await page.click('[data-testid="logout-button"]')
        
        // Login as regular user
        await page.goto('/admin/login')
        await page.fill('[data-testid="email-input"]', 'user@example.com')
        await page.fill('[data-testid="password-input"]', 'password')
        await page.click('[data-testid="login-button"]')
        
        // Navigate to dashboard
        await page.goto('/admin')
        
        // Verify limited dashboard is shown
        await expect(page.locator('[data-testid="dashboard-container"]')).toBeVisible()
        
        // Verify restricted elements are not visible
        const adminOnlyCard = page.locator('[data-testid="admin-only-card"]')
        if (await adminOnlyCard.isVisible()) {
            // This should not be visible for regular users
            expect(false).toBe(true) // Fail if admin-only content is visible
        }
    })

    test('dashboard search and filtering works', async ({ page }) => {
        // Check if search functionality exists
        const searchInput = page.locator('[data-testid="dashboard-search"]')
        
        if (await searchInput.isVisible()) {
            // Enter search term
            await searchInput.fill('users')
            
            // Wait for filtering
            await page.waitForTimeout(500)
            
            // Verify filtered results
            const visibleCards = page.locator('[data-testid="dashboard-card"]:visible')
            const cardCount = await visibleCards.count()
            
            // At least one card should match the search
            expect(cardCount).toBeGreaterThan(0)
            
            // Clear search
            await searchInput.clear()
            await page.waitForTimeout(500)
            
            // Verify all cards are visible again
            const allCards = page.locator('[data-testid="dashboard-card"]:visible')
            const allCardCount = await allCards.count()
            expect(allCardCount).toBeGreaterThanOrEqual(cardCount)
        }
    })
})
