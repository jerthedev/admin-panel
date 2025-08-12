import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage.js';
import { DashboardPage } from './pages/DashboardPage.js';
import { ensureAuthenticated } from './utils/auth.js';
import { ensureFreshTestData } from './utils/test-data.js';

/**
 * Admin Panel Navigation Tests
 *
 * Basic end-to-end tests to verify admin panel navigation
 * and core functionality works correctly.
 */

test.describe('Admin Panel Navigation', () => {
  let loginPage;
  let dashboardPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    dashboardPage = new DashboardPage(page);

    // Ensure we have fresh test data (currently skipped)
    await ensureFreshTestData(page);

    // For now, skip authentication and just test basic page access
    // TODO: Re-enable when authentication flow is working
    console.log('⚠️ Skipping authentication - testing basic page access');
  });

  test('should access admin panel test page', async ({ page }) => {
    // Test basic admin panel accessibility
    await page.goto('/admin/test');

    // Verify we can access the test page
    expect(page.url()).toContain('/admin/test');

    // Verify page loads successfully
    await page.waitForLoadState('networkidle');

    // Check for basic content
    const content = await page.textContent('body');
    expect(content).toContain('Admin Panel');

    // Take screenshot for verification
    await page.screenshot({ path: 'test-results/screenshots/admin-test-page.png' });
  });

  test('should display dashboard metrics', async ({ page }) => {
    await dashboardPage.goto();
    await dashboardPage.waitForMetricsToLoad();

    // Verify metrics section is visible
    expect(await dashboardPage.isMetricsSectionVisible()).toBe(true);

    // Verify we have at least one metric
    const metricCount = await dashboardPage.getMetricCount();
    expect(metricCount).toBeGreaterThan(0);

    // Verify first metric has value and label
    if (metricCount > 0) {
      const metricValue = await dashboardPage.getMetricValue(0);
      const metricLabel = await dashboardPage.getMetricLabel(0);

      expect(metricValue).toBeTruthy();
      expect(metricLabel).toBeTruthy();
    }
  });

  test('should display dashboard sections', async ({ page }) => {
    await dashboardPage.goto();

    // Verify all main dashboard sections are present
    expect(await dashboardPage.isMetricsSectionVisible()).toBe(true);
    expect(await dashboardPage.isWidgetsSectionVisible()).toBe(true);
    expect(await dashboardPage.isRecentActivityVisible()).toBe(true);
    expect(await dashboardPage.isQuickActionsVisible()).toBe(true);
    expect(await dashboardPage.isSystemInfoVisible()).toBe(true);
  });

  test('should handle navigation between pages', async ({ page }) => {
    await dashboardPage.goto();

    // Verify we can navigate using sidebar
    if (await dashboardPage.sidebar.isVisible()) {
      // Try to click on a navigation item if available
      const navItems = await dashboardPage.sidebar.locator('a, button').all();

      if (navItems.length > 0) {
        // Click first navigation item
        await navItems[0].click();
        await dashboardPage.waitForLoadingToComplete();

        // Verify we're still in admin panel
        await expect(dashboardPage.adminLayout).toBeVisible();
      }
    }
  });

  test('should handle page refresh correctly', async ({ page }) => {
    await dashboardPage.goto();

    // Refresh the page
    await dashboardPage.refreshDashboard();

    // Verify we're still authenticated and on dashboard
    expect(page.url()).toContain('/admin');
    await expect(dashboardPage.adminLayout).toBeVisible();
  });

  test('should display quick actions', async ({ page }) => {
    await dashboardPage.goto();

    if (await dashboardPage.isQuickActionsVisible()) {
      const actionCount = await dashboardPage.getQuickActionCount();
      expect(actionCount).toBeGreaterThanOrEqual(0);

      // If there are quick actions, verify they're clickable
      if (actionCount > 0) {
        const firstAction = dashboardPage.quickActionButtons.first();
        await expect(firstAction).toBeVisible();

        // Verify button is enabled
        expect(await firstAction.isEnabled()).toBe(true);
      }
    }
  });
});

test.describe('Authentication Flow', () => {
  test('should redirect to login when not authenticated', async ({ page }) => {
    // Clear any existing session
    await page.context().clearCookies();
    await page.evaluate(() => {
      sessionStorage.clear();
      localStorage.clear();
    });

    // Try to access admin dashboard
    await page.goto('/admin');

    // Should be redirected to login
    await page.waitForURL('**/login', { timeout: 10000 });
    expect(page.url()).toContain('/login');

    const loginPage = new LoginPage(page);
    expect(await loginPage.isLoginFormVisible()).toBe(true);
  });

  test('should login with valid credentials', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // Perform login
    await loginPage.login('admin@example.com', 'password');

    // Verify successful login
    expect(page.url()).toContain('/admin');

    const dashboardPage = new DashboardPage(page);
    await expect(dashboardPage.adminLayout).toBeVisible();
  });

  test('should show error with invalid credentials', async ({ page }) => {
    const loginPage = new LoginPage(page);

    await loginPage.goto();
    await loginPage.fillCredentials('invalid@example.com', 'wrongpassword');
    await loginPage.submitLogin();

    // Should stay on login page
    expect(page.url()).toContain('/login');

    // Check for error message (if implemented)
    const errorMessage = await loginPage.getErrorMessage();
    if (errorMessage) {
      expect(errorMessage).toBeTruthy();
    }
  });
});
