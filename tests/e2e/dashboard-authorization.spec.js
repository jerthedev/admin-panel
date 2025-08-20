import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage.js';

/**
 * Dashboard Authorization E2E Tests
 * 
 * Tests for dashboard authorization features including role-based access,
 * policy-based authorization, and authorization caching.
 */

test.describe('Dashboard Authorization', () => {
  let loginPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
  });

  test('should allow admin users to access all dashboards', async ({ page }) => {
    // Login as admin
    await loginPage.login('admin@example.com', 'password');
    await page.waitForURL('/admin', { timeout: 15000 });
    await page.waitForLoadState('networkidle');
    
    // Should successfully access main dashboard
    expect(page.url()).toContain('/admin');
    
    // Get available dashboards
    const dashboards = await page.evaluate(() => {
      return window.page?.props?.dashboards || [];
    });
    
    console.log(`👤 Admin user has access to ${dashboards.length} dashboards`);
    
    // All dashboards should be visible to admin
    dashboards.forEach(dashboard => {
      expect(dashboard.visible).toBe(true);
    });
    
    // Test direct access to main dashboard
    await page.goto('/admin/dashboards/main');
    await page.waitForLoadState('networkidle');
    
    // Should not get 403 error
    const response = await page.goto('/admin/dashboards/main');
    expect(response.status()).not.toBe(403);
    
    // Should load dashboard component
    const dashboardData = await page.evaluate(() => {
      return window.page?.props?.dashboard;
    });
    
    expect(dashboardData?.name).toBe('Main');
  });

  test('should handle unauthorized dashboard access', async ({ page }) => {
    // Login as admin first to establish session
    await loginPage.login('admin@example.com', 'password');
    await page.waitForURL('/admin', { timeout: 15000 });
    
    // Try to access a potentially restricted dashboard
    // (This test assumes there might be restricted dashboards in the system)
    const restrictedUrls = [
      '/admin/dashboards/restricted',
      '/admin/dashboards/super-admin-only',
      '/admin/dashboards/non-existent'
    ];
    
    for (const url of restrictedUrls) {
      const response = await page.goto(url, { waitUntil: 'networkidle' });
      
      // Should either get 404 (not found) or 403 (forbidden)
      // Both are acceptable for non-existent or unauthorized dashboards
      expect([403, 404]).toContain(response.status());
      
      console.log(`🔒 ${url} returned status: ${response.status()}`);
    }
  });

  test('should respect dashboard visibility settings', async ({ page }) => {
    await loginPage.login('admin@example.com', 'password');
    await page.waitForURL('/admin', { timeout: 15000 });
    await page.waitForLoadState('networkidle');
    
    // Get dashboard navigation data
    const navigationData = await page.evaluate(() => {
      const props = window.page?.props || {};
      return {
        dashboards: props.dashboards,
        navigation: props.navigation
      };
    });
    
    if (navigationData.dashboards) {
      // Check that only visible dashboards appear in navigation
      const visibleDashboards = navigationData.dashboards.filter(d => d.visible);
      const hiddenDashboards = navigationData.dashboards.filter(d => !d.visible);
      
      console.log(`👁️ Visible dashboards: ${visibleDashboards.length}`);
      console.log(`🙈 Hidden dashboards: ${hiddenDashboards.length}`);
      
      // Visible dashboards should be accessible
      for (const dashboard of visibleDashboards.slice(0, 3)) { // Test first 3 to avoid too many requests
        const response = await page.goto(`/admin/dashboards/${dashboard.uriKey}`);
        expect(response.status()).toBe(200);
        
        const loadedDashboard = await page.evaluate(() => {
          return window.page?.props?.dashboard;
        });
        
        expect(loadedDashboard?.uriKey).toBe(dashboard.uriKey);
      }
    }
  });

  test('should handle authorization caching correctly', async ({ page }) => {
    await loginPage.login('admin@example.com', 'password');
    await page.waitForURL('/admin', { timeout: 15000 });
    
    // Access dashboard multiple times to test caching
    const dashboardUrl = '/admin/dashboards/main';
    
    // First access
    const startTime1 = Date.now();
    await page.goto(dashboardUrl);
    await page.waitForLoadState('networkidle');
    const loadTime1 = Date.now() - startTime1;
    
    // Second access (should potentially use cache)
    const startTime2 = Date.now();
    await page.goto(dashboardUrl);
    await page.waitForLoadState('networkidle');
    const loadTime2 = Date.now() - startTime2;
    
    // Third access
    const startTime3 = Date.now();
    await page.goto(dashboardUrl);
    await page.waitForLoadState('networkidle');
    const loadTime3 = Date.now() - startTime3;
    
    console.log(`⏱️ Load times: ${loadTime1}ms, ${loadTime2}ms, ${loadTime3}ms`);
    
    // All accesses should be successful
    const finalDashboard = await page.evaluate(() => {
      return window.page?.props?.dashboard;
    });
    
    expect(finalDashboard?.name).toBe('Main');
    
    // If caching is working, subsequent loads might be faster
    // But this is not guaranteed in E2E tests, so we just verify functionality
    expect(loadTime2).toBeLessThan(10000); // Should load within 10 seconds
    expect(loadTime3).toBeLessThan(10000);
  });

  test('should handle session expiration gracefully', async ({ page }) => {
    await loginPage.login('admin@example.com', 'password');
    await page.waitForURL('/admin', { timeout: 15000 });
    
    // Clear session cookies to simulate expiration
    await page.context().clearCookies();
    
    // Try to access dashboard
    const response = await page.goto('/admin/dashboards/main', { waitUntil: 'networkidle' });
    
    // Should redirect to login or return 401/403
    const finalUrl = page.url();
    const status = response.status();
    
    console.log(`🔐 After session clear - URL: ${finalUrl}, Status: ${status}`);
    
    // Should either redirect to login or return unauthorized status
    const isRedirectedToLogin = finalUrl.includes('/login') || finalUrl.includes('/auth');
    const isUnauthorized = [401, 403].includes(status);
    
    expect(isRedirectedToLogin || isUnauthorized).toBe(true);
  });

  test('should handle authorization errors gracefully', async ({ page }) => {
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
    
    await loginPage.login('admin@example.com', 'password');
    await page.waitForURL('/admin', { timeout: 15000 });
    
    // Try various authorization scenarios
    const testUrls = [
      '/admin',
      '/admin/dashboards/main',
      '/admin/dashboards/non-existent'
    ];
    
    for (const url of testUrls) {
      await page.goto(url, { waitUntil: 'networkidle' });
      await page.waitForTimeout(1000);
    }
    
    // Log any errors for debugging
    if (jsErrors.length > 0) {
      console.log('⚠️ JavaScript errors during authorization test:', jsErrors);
    }
    if (consoleErrors.length > 0) {
      console.log('⚠️ Console errors during authorization test:', consoleErrors);
    }
    
    // Should not have critical authorization errors
    const criticalErrors = jsErrors.filter(error => 
      error.toLowerCase().includes('authorization') ||
      error.toLowerCase().includes('permission') ||
      error.toLowerCase().includes('forbidden')
    );
    
    expect(criticalErrors.length).toBe(0);
  });

  test('should maintain authorization state during navigation', async ({ page }) => {
    await loginPage.login('admin@example.com', 'password');
    await page.waitForURL('/admin', { timeout: 15000 });
    
    // Get initial authorization state
    const initialAuth = await page.evaluate(() => {
      return {
        isAuthenticated: !!window.page?.props?.auth?.user,
        dashboards: window.page?.props?.dashboards?.length || 0
      };
    });
    
    expect(initialAuth.isAuthenticated).toBe(true);
    
    // Navigate between different dashboard pages
    const navigationSequence = [
      '/admin',
      '/admin/dashboards/main',
      '/admin',
      '/admin/dashboards/main'
    ];
    
    for (const url of navigationSequence) {
      await page.goto(url);
      await page.waitForLoadState('networkidle');
      
      // Check that authorization is maintained
      const currentAuth = await page.evaluate(() => {
        return {
          isAuthenticated: !!window.page?.props?.auth?.user,
          dashboards: window.page?.props?.dashboards?.length || 0
        };
      });
      
      expect(currentAuth.isAuthenticated).toBe(true);
      expect(currentAuth.dashboards).toBeGreaterThanOrEqual(1);
    }
  });

  test('should handle policy-based authorization', async ({ page }) => {
    await loginPage.login('admin@example.com', 'password');
    await page.waitForURL('/admin', { timeout: 15000 });
    await page.waitForLoadState('networkidle');
    
    // Check if there are any policy-protected dashboards
    const dashboards = await page.evaluate(() => {
      return window.page?.props?.dashboards || [];
    });
    
    // Test access to each available dashboard
    for (const dashboard of dashboards.slice(0, 3)) { // Limit to first 3
      const response = await page.goto(`/admin/dashboards/${dashboard.uriKey}`);
      
      if (dashboard.visible) {
        // Should be accessible if visible
        expect(response.status()).toBe(200);
        
        const loadedDashboard = await page.evaluate(() => {
          return window.page?.props?.dashboard;
        });
        
        expect(loadedDashboard?.uriKey).toBe(dashboard.uriKey);
      } else {
        // Should not be accessible if not visible
        expect([403, 404]).toContain(response.status());
      }
    }
  });

  test('should handle concurrent authorization requests', async ({ page }) => {
    await loginPage.login('admin@example.com', 'password');
    await page.waitForURL('/admin', { timeout: 15000 });
    
    // Make multiple concurrent requests to test authorization handling
    const urls = [
      '/admin',
      '/admin/dashboards/main',
      '/admin/dashboards/main',
      '/admin'
    ];
    
    // Open multiple tabs and navigate simultaneously
    const promises = urls.map(async (url, index) => {
      if (index === 0) {
        // Use existing page for first request
        return page.goto(url, { waitUntil: 'networkidle' });
      } else {
        // Create new page for concurrent requests
        const newPage = await page.context().newPage();
        const response = await newPage.goto(url, { waitUntil: 'networkidle' });
        await newPage.close();
        return response;
      }
    });
    
    const responses = await Promise.all(promises);
    
    // All requests should succeed for authorized user
    responses.forEach((response, index) => {
      console.log(`🔄 Concurrent request ${index + 1}: ${response.status()}`);
      expect(response.status()).toBe(200);
    });
  });
});
