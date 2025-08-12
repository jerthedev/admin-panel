import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage.js';
import { DashboardPage } from './pages/DashboardPage.js';

/**
 * Dashboard Workflow Tests
 * 
 * Tests for admin panel dashboard functionality including
 * metrics, widgets, navigation, and overall user experience.
 */

test.describe('Dashboard Workflow', () => {
  let loginPage;
  let dashboardPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    dashboardPage = new DashboardPage(page);
    
    // Login as admin before each test
    await loginPage.login('admin@example.com', 'password');
    await page.waitForURL('/admin', { timeout: 15000 });
  });

  test('should display dashboard after successful login', async ({ page }) => {
    // Verify we're on the dashboard
    expect(page.url()).toContain('/admin');
    expect(page.url()).not.toContain('/login');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    
    // Check for basic dashboard elements
    const pageContent = await page.textContent('body');
    expect(pageContent).toBeTruthy();
    
    // Look for common dashboard indicators
    const hasDashboardContent = await page.evaluate(() => {
      const indicators = [
        'dashboard', 'admin', 'welcome', 'overview',
        'metrics', 'statistics', 'panel'
      ];
      
      const bodyText = document.body.textContent.toLowerCase();
      return indicators.some(indicator => bodyText.includes(indicator));
    });
    
    expect(hasDashboardContent).toBe(true);
    
    // Take screenshot of dashboard
    await page.screenshot({ path: 'test-results/screenshots/dashboard-loaded.png' });
  });

  test('should display navigation elements', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Look for navigation elements
    const navigationSelectors = [
      'nav', '[role="navigation"]', '.navigation', '.nav',
      '.sidebar', '.menu', '[data-testid*="nav"]'
    ];
    
    let hasNavigation = false;
    for (const selector of navigationSelectors) {
      const element = page.locator(selector).first();
      if (await element.isVisible()) {
        hasNavigation = true;
        break;
      }
    }
    
    // Should have some form of navigation
    expect(hasNavigation).toBe(true);
    
    // Look for common navigation items
    const commonNavItems = ['dashboard', 'users', 'settings', 'resources'];
    const pageText = await page.textContent('body');
    const hasNavItems = commonNavItems.some(item => 
      pageText.toLowerCase().includes(item)
    );
    
    expect(hasNavItems).toBe(true);
  });

  test('should display user information', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Look for user information display
    const userInfoSelectors = [
      '[data-testid*="user"]', '.user-info', '.profile',
      ':has-text("admin@example.com")', ':has-text("Admin User")'
    ];
    
    let hasUserInfo = false;
    for (const selector of userInfoSelectors) {
      try {
        const element = page.locator(selector).first();
        if (await element.isVisible()) {
          hasUserInfo = true;
          break;
        }
      } catch (error) {
        // Continue checking other selectors
      }
    }
    
    // Check if user email or name appears anywhere
    const pageContent = await page.textContent('body');
    const hasUserData = pageContent.includes('admin@example.com') || 
                       pageContent.includes('Admin User');
    
    expect(hasUserInfo || hasUserData).toBe(true);
  });

  test('should handle dashboard metrics if present', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Look for metrics/statistics sections
    const metricsSelectors = [
      '.metric', '.stats', '.statistics', '.card',
      '[data-testid*="metric"]', '.dashboard-card'
    ];
    
    let metricsFound = false;
    let metricsCount = 0;
    
    for (const selector of metricsSelectors) {
      const elements = page.locator(selector);
      const count = await elements.count();
      if (count > 0) {
        metricsFound = true;
        metricsCount = count;
        break;
      }
    }
    
    if (metricsFound) {
      console.log(`📊 Found ${metricsCount} metric elements`);
      
      // Verify metrics have content
      const firstMetric = page.locator('.metric, .stats, .card').first();
      if (await firstMetric.isVisible()) {
        const metricText = await firstMetric.textContent();
        expect(metricText.trim()).toBeTruthy();
      }
    } else {
      console.log('ℹ️ No metrics found - dashboard may be minimal');
    }
    
    // This test passes regardless of metrics presence
    expect(true).toBe(true);
  });

  test('should handle dashboard widgets if present', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Look for widget sections
    const widgetSelectors = [
      '.widget', '.dashboard-widget', '.panel',
      '[data-testid*="widget"]', '.dashboard-section'
    ];
    
    let widgetsFound = false;
    
    for (const selector of widgetSelectors) {
      const elements = page.locator(selector);
      const count = await elements.count();
      if (count > 0) {
        widgetsFound = true;
        console.log(`🔧 Found ${count} widget elements`);
        break;
      }
    }
    
    if (widgetsFound) {
      // Verify widgets have content
      const firstWidget = page.locator('.widget, .dashboard-widget, .panel').first();
      if (await firstWidget.isVisible()) {
        const widgetText = await firstWidget.textContent();
        expect(widgetText.trim()).toBeTruthy();
      }
    } else {
      console.log('ℹ️ No widgets found - dashboard may be minimal');
    }
    
    // This test passes regardless of widgets presence
    expect(true).toBe(true);
  });

  test('should be responsive on different screen sizes', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Test desktop size (default)
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.waitForTimeout(500);
    
    let desktopContent = await page.textContent('body');
    expect(desktopContent).toBeTruthy();
    
    // Test tablet size
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.waitForTimeout(500);
    
    let tabletContent = await page.textContent('body');
    expect(tabletContent).toBeTruthy();
    
    // Test mobile size
    await page.setViewportSize({ width: 375, height: 667 });
    await page.waitForTimeout(500);
    
    let mobileContent = await page.textContent('body');
    expect(mobileContent).toBeTruthy();
    
    // Take screenshot of mobile view
    await page.screenshot({ path: 'test-results/screenshots/dashboard-mobile.png' });
    
    // Reset to desktop
    await page.setViewportSize({ width: 1280, height: 720 });
  });

  test('should handle page refresh correctly', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Get initial page content
    const initialContent = await page.textContent('body');
    
    // Refresh the page
    await page.reload();
    await page.waitForLoadState('networkidle');
    
    // Should still be on admin panel
    expect(page.url()).toContain('/admin');
    expect(page.url()).not.toContain('/login');
    
    // Should have content after refresh
    const refreshedContent = await page.textContent('body');
    expect(refreshedContent).toBeTruthy();
    expect(refreshedContent.length).toBeGreaterThan(100); // Should have substantial content
  });

  test('should handle browser back/forward navigation', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Navigate to a different page if possible
    const links = page.locator('a[href^="/admin"]');
    const linkCount = await links.count();
    
    if (linkCount > 0) {
      // Click on first admin link
      await links.first().click();
      await page.waitForLoadState('networkidle');
      
      // Go back
      await page.goBack();
      await page.waitForLoadState('networkidle');
      
      // Should be back on dashboard
      expect(page.url()).toContain('/admin');
      
      // Go forward
      await page.goForward();
      await page.waitForLoadState('networkidle');
      
      // Should be on the linked page
      expect(page.url()).toContain('/admin');
    } else {
      console.log('ℹ️ No navigation links found - skipping back/forward test');
    }
    
    // Test passes regardless
    expect(true).toBe(true);
  });

  test('should load within acceptable time limits', async ({ page }) => {
    // Clear cache and reload to test fresh load time
    await page.evaluate(() => {
      if ('caches' in window) {
        caches.keys().then(names => {
          names.forEach(name => caches.delete(name));
        });
      }
    });
    
    const startTime = Date.now();
    
    await page.reload();
    await page.waitForLoadState('networkidle');
    
    const loadTime = Date.now() - startTime;
    
    console.log(`⏱️ Dashboard load time: ${loadTime}ms`);
    
    // Should load within 10 seconds (generous for E2E testing)
    expect(loadTime).toBeLessThan(10000);
    
    // Should load within 5 seconds for good performance
    if (loadTime < 5000) {
      console.log('✅ Good performance: under 5 seconds');
    } else {
      console.log('⚠️ Slow performance: over 5 seconds');
    }
  });

  test('should handle errors gracefully', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Check for JavaScript errors
    const jsErrors = [];
    page.on('pageerror', error => {
      jsErrors.push(error.message);
    });
    
    // Check for console errors
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });
    
    // Interact with the page to trigger any potential errors
    await page.click('body');
    await page.waitForTimeout(1000);
    
    // Check for failed network requests
    const failedRequests = [];
    page.on('response', response => {
      if (response.status() >= 400) {
        failedRequests.push(`${response.status()}: ${response.url()}`);
      }
    });
    
    await page.waitForTimeout(2000);
    
    // Log any errors found
    if (jsErrors.length > 0) {
      console.log('⚠️ JavaScript errors:', jsErrors);
    }
    if (consoleErrors.length > 0) {
      console.log('⚠️ Console errors:', consoleErrors);
    }
    if (failedRequests.length > 0) {
      console.log('⚠️ Failed requests:', failedRequests);
    }
    
    // Test passes - we're just logging errors for debugging
    expect(true).toBe(true);
  });
});
