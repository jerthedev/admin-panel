/**
 * Authentication Utilities for Playwright Tests
 * 
 * Provides helper functions for admin panel authentication
 * and user session management.
 */

/**
 * Login as admin user
 * 
 * @param {import('@playwright/test').Page} page
 * @param {Object} options
 * @param {string} options.email - Admin email (default: admin@example.com)
 * @param {string} options.password - Admin password (default: password)
 * @returns {Promise<void>}
 */
export async function loginAsAdmin(page, options = {}) {
  const { email = 'admin@example.com', password = 'password' } = options;
  
  console.log(`🔐 Logging in as admin: ${email}`);
  
  // Navigate to admin login page
  await page.goto('/admin/login');
  
  // Wait for login form to be visible
  await page.waitForSelector('form', { timeout: 10000 });
  
  // Fill in credentials
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  
  // Submit login form
  await page.click('button[type="submit"]');
  
  // Wait for redirect to dashboard
  await page.waitForURL('/admin', { timeout: 15000 });
  
  // Verify we're logged in by checking for admin layout
  await page.waitForSelector('[data-testid="admin-layout"]', { timeout: 10000 });
  
  console.log('✅ Successfully logged in as admin');
}

/**
 * Logout from admin panel
 * 
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<void>}
 */
export async function logout(page) {
  console.log('🚪 Logging out...');
  
  try {
    // Look for logout button/link
    const logoutSelector = '[data-testid="logout-button"], a[href*="logout"], button:has-text("Logout")';
    await page.click(logoutSelector);
    
    // Wait for redirect to login page
    await page.waitForURL('/admin/login', { timeout: 10000 });
    
    console.log('✅ Successfully logged out');
  } catch (error) {
    console.warn('⚠️ Logout failed, clearing session manually');
    
    // Fallback: clear session storage and cookies
    await page.evaluate(() => {
      sessionStorage.clear();
      localStorage.clear();
    });
    
    await page.context().clearCookies();
    await page.goto('/admin/login');
  }
}

/**
 * Ensure user is authenticated
 * 
 * Checks if user is already logged in, if not performs login
 * 
 * @param {import('@playwright/test').Page} page
 * @param {Object} options - Login options
 * @returns {Promise<void>}
 */
export async function ensureAuthenticated(page, options = {}) {
  try {
    // Try to navigate to admin dashboard
    await page.goto('/admin');
    
    // Check if we're redirected to login
    const currentUrl = page.url();
    if (currentUrl.includes('/login')) {
      await loginAsAdmin(page, options);
    } else {
      // Verify we have admin layout
      await page.waitForSelector('[data-testid="admin-layout"]', { timeout: 5000 });
      console.log('✅ Already authenticated');
    }
  } catch (error) {
    console.log('🔄 Authentication check failed, performing login...');
    await loginAsAdmin(page, options);
  }
}

/**
 * Create admin user session storage
 * 
 * Sets up session storage for faster authentication in subsequent tests
 * 
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<Object>} Session data
 */
export async function createAdminSession(page) {
  await loginAsAdmin(page);
  
  // Extract session data
  const cookies = await page.context().cookies();
  const localStorage = await page.evaluate(() => ({ ...localStorage }));
  const sessionStorage = await page.evaluate(() => ({ ...sessionStorage }));
  
  return {
    cookies,
    localStorage,
    sessionStorage,
    timestamp: Date.now()
  };
}

/**
 * Restore admin session
 * 
 * Restores a previously saved session for faster test setup
 * 
 * @param {import('@playwright/test').Page} page
 * @param {Object} sessionData - Session data from createAdminSession
 * @returns {Promise<boolean>} Success status
 */
export async function restoreAdminSession(page, sessionData) {
  try {
    // Check if session is still valid (not older than 1 hour)
    const oneHour = 60 * 60 * 1000;
    if (Date.now() - sessionData.timestamp > oneHour) {
      return false;
    }
    
    // Restore cookies
    await page.context().addCookies(sessionData.cookies);
    
    // Navigate to admin panel
    await page.goto('/admin');
    
    // Restore storage
    await page.evaluate((data) => {
      Object.entries(data.localStorage).forEach(([key, value]) => {
        localStorage.setItem(key, value);
      });
      Object.entries(data.sessionStorage).forEach(([key, value]) => {
        sessionStorage.setItem(key, value);
      });
    }, sessionData);
    
    // Verify session is valid
    await page.waitForSelector('[data-testid="admin-layout"]', { timeout: 5000 });
    
    console.log('✅ Session restored successfully');
    return true;
  } catch (error) {
    console.log('⚠️ Session restore failed:', error.message);
    return false;
  }
}
