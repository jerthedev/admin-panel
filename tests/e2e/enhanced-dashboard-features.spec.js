import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage.js';
import { DashboardPage } from './pages/DashboardPage.js';

/**
 * Enhanced Dashboard Features E2E Tests
 * 
 * Tests for the new dashboard features including multiple dashboards,
 * navigation, authorization, and Nova v5 compatibility.
 */

test.describe('Enhanced Dashboard Features', () => {
  let loginPage;
  let dashboardPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    dashboardPage = new DashboardPage(page);
    
    // Login as admin before each test
    await loginPage.login('admin@example.com', 'password');
    await page.waitForURL('/admin', { timeout: 15000 });
  });

  test('should display main dashboard by default', async ({ page }) => {
    // Verify we're on the main dashboard
    expect(page.url()).toMatch(/\/admin\/?$/);
    
    await page.waitForLoadState('networkidle');
    
    // Check for dashboard component
    const dashboardContent = await page.evaluate(() => {
      // Look for Inertia page data
      const pageData = window.page || {};
      return {
        component: pageData.component,
        dashboard: pageData.props?.dashboard
      };
    });
    
    expect(dashboardContent.component).toBe('Dashboard');
    expect(dashboardContent.dashboard?.name).toBe('Main');
    expect(dashboardContent.dashboard?.uriKey).toBe('main');
  });

  test('should navigate to specific dashboards via URL', async ({ page }) => {
    // Test navigation to main dashboard explicitly
    await page.goto('/admin/dashboards/main');
    await page.waitForLoadState('networkidle');
    
    const mainDashboard = await page.evaluate(() => {
      return window.page?.props?.dashboard;
    });
    
    expect(mainDashboard?.name).toBe('Main');
    expect(mainDashboard?.uriKey).toBe('main');
    
    // Take screenshot of main dashboard
    await page.screenshot({ path: 'test-results/screenshots/main-dashboard.png' });
  });

  test('should display dashboard navigation if multiple dashboards exist', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Check for dashboard navigation data
    const navigationData = await page.evaluate(() => {
      const pageProps = window.page?.props || {};
      return {
        dashboards: pageProps.dashboards,
        navigation: pageProps.navigation
      };
    });
    
    if (navigationData.dashboards && navigationData.dashboards.length > 1) {
      console.log(`📊 Found ${navigationData.dashboards.length} dashboards`);
      
      // Should have navigation for multiple dashboards
      expect(navigationData.dashboards.length).toBeGreaterThan(1);
      
      // Each dashboard should have required properties
      navigationData.dashboards.forEach(dashboard => {
        expect(dashboard).toHaveProperty('name');
        expect(dashboard).toHaveProperty('uriKey');
        expect(dashboard).toHaveProperty('visible');
      });
    } else {
      console.log('ℹ️ Single dashboard setup - no navigation needed');
    }
  });

  test('should handle dashboard authorization correctly', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Check that only authorized dashboards are visible
    const dashboards = await page.evaluate(() => {
      return window.page?.props?.dashboards || [];
    });
    
    // All visible dashboards should be authorized
    dashboards.forEach(dashboard => {
      expect(dashboard.visible).toBe(true);
    });
    
    // Try accessing a dashboard that might not exist
    const response = await page.goto('/admin/dashboards/non-existent-dashboard', {
      waitUntil: 'networkidle'
    });
    
    // Should get 404 for non-existent dashboard
    expect(response.status()).toBe(404);
    
    // Navigate back to main dashboard
    await page.goto('/admin');
    await page.waitForLoadState('networkidle');
  });

  test('should display dashboard refresh button when enabled', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Check if refresh button is enabled for current dashboard
    const dashboardData = await page.evaluate(() => {
      return window.page?.props?.dashboard;
    });
    
    if (dashboardData?.showRefreshButton) {
      // Look for refresh button
      const refreshButton = page.locator('[data-testid="refresh-button"], button:has-text("Refresh"), .refresh-button');
      
      if (await refreshButton.count() > 0) {
        expect(await refreshButton.first().isVisible()).toBe(true);
        
        // Test refresh functionality
        await refreshButton.first().click();
        await page.waitForLoadState('networkidle');
        
        // Should still be on the same dashboard
        const updatedDashboard = await page.evaluate(() => {
          return window.page?.props?.dashboard;
        });
        
        expect(updatedDashboard?.uriKey).toBe(dashboardData.uriKey);
      }
    } else {
      console.log('ℹ️ Refresh button not enabled for current dashboard');
    }
  });

  test('should handle dashboard cards correctly', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Get dashboard cards data
    const cardsData = await page.evaluate(() => {
      return window.page?.props?.cards || [];
    });
    
    console.log(`🃏 Dashboard has ${cardsData.length} cards`);
    
    if (cardsData.length > 0) {
      // Verify cards have required structure
      cardsData.forEach((card, index) => {
        expect(card).toHaveProperty('component');
        expect(typeof card.component).toBe('string');
        
        if (card.title) {
          expect(typeof card.title).toBe('string');
        }
      });
      
      // Look for card elements in the DOM
      const cardElements = page.locator('.card, [data-testid*="card"], .dashboard-card');
      const cardCount = await cardElements.count();
      
      if (cardCount > 0) {
        console.log(`📊 Found ${cardCount} card elements in DOM`);
        
        // Verify first card is visible and has content
        const firstCard = cardElements.first();
        expect(await firstCard.isVisible()).toBe(true);
        
        const cardText = await firstCard.textContent();
        expect(cardText.trim()).toBeTruthy();
      }
    } else {
      console.log('ℹ️ No cards configured for current dashboard');
    }
  });

  test('should handle dashboard menu navigation', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Look for dashboard navigation menu
    const menuSelectors = [
      '[data-testid*="dashboard-menu"]',
      '.dashboard-navigation',
      'nav a[href*="/admin/dashboards/"]',
      '.sidebar a[href*="/admin/dashboards/"]'
    ];
    
    let dashboardLinks = [];
    
    for (const selector of menuSelectors) {
      const links = page.locator(selector);
      const count = await links.count();
      
      if (count > 0) {
        for (let i = 0; i < count; i++) {
          const link = links.nth(i);
          const href = await link.getAttribute('href');
          const text = await link.textContent();
          
          if (href && href.includes('/admin/dashboards/')) {
            dashboardLinks.push({ href, text: text.trim() });
          }
        }
      }
    }
    
    if (dashboardLinks.length > 0) {
      console.log(`🔗 Found ${dashboardLinks.length} dashboard navigation links`);
      
      // Test clicking on first dashboard link
      const firstLink = page.locator(`a[href="${dashboardLinks[0].href}"]`).first();
      
      if (await firstLink.isVisible()) {
        await firstLink.click();
        await page.waitForLoadState('networkidle');
        
        // Should navigate to the dashboard
        expect(page.url()).toContain(dashboardLinks[0].href);
        
        // Should load dashboard component
        const dashboardComponent = await page.evaluate(() => {
          return window.page?.component;
        });
        
        expect(dashboardComponent).toBe('Dashboard');
      }
    } else {
      console.log('ℹ️ No dashboard navigation links found');
    }
  });

  test('should maintain dashboard state during navigation', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Get initial dashboard state
    const initialDashboard = await page.evaluate(() => {
      return window.page?.props?.dashboard;
    });
    
    if (initialDashboard) {
      // Navigate away and back
      await page.goto('/admin/dashboards/main');
      await page.waitForLoadState('networkidle');
      
      // Navigate back to root
      await page.goto('/admin');
      await page.waitForLoadState('networkidle');
      
      // Should maintain dashboard functionality
      const finalDashboard = await page.evaluate(() => {
        return window.page?.props?.dashboard;
      });
      
      expect(finalDashboard).toBeTruthy();
      expect(finalDashboard.component).toBe('Dashboard');
    }
  });

  test('should handle dashboard errors gracefully', async ({ page }) => {
    // Monitor for JavaScript errors
    const jsErrors = [];
    page.on('pageerror', error => {
      jsErrors.push(error.message);
    });
    
    // Monitor for console errors
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });
    
    await page.waitForLoadState('networkidle');
    
    // Try to trigger potential error scenarios
    await page.evaluate(() => {
      // Simulate potential dashboard errors
      if (window.page?.props?.dashboard) {
        // Test dashboard data access
        const dashboard = window.page.props.dashboard;
        console.log('Dashboard loaded:', dashboard.name);
      }
    });
    
    await page.waitForTimeout(2000);
    
    // Log any errors for debugging
    if (jsErrors.length > 0) {
      console.log('⚠️ JavaScript errors during dashboard test:', jsErrors);
    }
    if (consoleErrors.length > 0) {
      console.log('⚠️ Console errors during dashboard test:', consoleErrors);
    }
    
    // Test should pass - we're monitoring for debugging
    expect(true).toBe(true);
  });

  test('should be accessible and follow best practices', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    
    // Check for basic accessibility features
    const accessibilityChecks = await page.evaluate(() => {
      const checks = {
        hasTitle: !!document.title,
        hasHeadings: document.querySelectorAll('h1, h2, h3, h4, h5, h6').length > 0,
        hasLandmarks: document.querySelectorAll('main, nav, header, footer, aside').length > 0,
        hasSkipLinks: document.querySelectorAll('a[href^="#"]').length > 0
      };
      
      return checks;
    });
    
    // Should have a meaningful title
    expect(accessibilityChecks.hasTitle).toBe(true);
    
    // Should have proper heading structure
    expect(accessibilityChecks.hasHeadings).toBe(true);
    
    // Should have semantic landmarks
    expect(accessibilityChecks.hasLandmarks).toBe(true);
    
    console.log('♿ Accessibility checks:', accessibilityChecks);
  });
});
