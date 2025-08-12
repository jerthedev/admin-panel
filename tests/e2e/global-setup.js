import { chromium } from '@playwright/test';

/**
 * Global Setup for Playwright Tests
 *
 * Runs once before all tests to prepare the testing environment.
 * Sets up test data using existing Laravel test APIs.
 */
async function globalSetup() {
  console.log('🚀 Starting global test setup...');

  // Launch browser for setup operations
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    // Wait for Laravel server to be ready
    await waitForServer(page);

    // Setup test data using existing test APIs
    await setupTestData(page);

    console.log('✅ Global setup completed successfully');
  } catch (error) {
    console.error('❌ Global setup failed:', error);
    throw error;
  } finally {
    await browser.close();
  }
}

/**
 * Wait for Laravel server to be ready
 */
async function waitForServer(page) {
  const maxAttempts = 30;
  const delay = 2000; // 2 seconds

  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      console.log(`⏳ Waiting for server... (attempt ${attempt}/${maxAttempts})`);

      const response = await page.goto('http://localhost:8000/admin/test', {
        waitUntil: 'networkidle',
        timeout: 10000
      });

      if (response && response.ok()) {
        console.log('✅ Server is ready');
        return;
      }
    } catch (error) {
      if (attempt === maxAttempts) {
        throw new Error(`Server not ready after ${maxAttempts} attempts: ${error.message}`);
      }
      await new Promise(resolve => setTimeout(resolve, delay));
    }
  }
}

/**
 * Setup test data using Laravel test APIs
 */
async function setupTestData(page) {
  console.log('📊 Setting up test data...');

  try {
    // Verify admin panel test route is accessible
    const testResponse = await page.request.get('http://localhost:8000/admin/test');
    if (!testResponse.ok()) {
      throw new Error(`Admin panel test route not accessible: ${testResponse.status()}`);
    }

    // Create a dedicated test user for E2E tests
    console.log('👤 Creating dedicated test user...');

    const createUserResponse = await page.request.post('http://localhost:8000/admin/api/test/create-user', {
      data: {
        name: 'E2E Test User',
        email: 'e2e-test@example.com',
        password: 'testpassword123',
        is_admin: true
      }
    });

    if (createUserResponse.ok()) {
      console.log('✅ Test user created successfully');
    } else {
      console.log('ℹ️ Test user creation failed, may already exist');
    }

    console.log('✅ Test data setup completed');
  } catch (error) {
    console.error('❌ Test data setup failed:', error);
    // Don't throw error - continue with tests even if user creation fails
  }
}

export default globalSetup;
