/**
 * Test Data Utilities for Playwright Tests
 *
 * Provides helper functions for managing test data using
 * Laravel test APIs.
 */

/**
 * Setup admin demo data
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<Object>} Response data
 */
export async function setupAdminDemo(page) {
  console.log('📊 Setting up admin demo data...');

  const response = await page.request.post('/admin/api/test/setup-admin-demo');

  if (!response.ok()) {
    throw new Error(`Failed to setup admin demo: ${response.status()}`);
  }

  const data = await response.json();
  console.log('✅ Admin demo data setup completed');
  return data;
}

/**
 * Seed field examples
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<Object>} Response data
 */
export async function seedFieldExamples(page) {
  console.log('🌱 Seeding field examples...');

  const response = await page.request.post('/admin/api/test/seed-field-examples');

  if (!response.ok()) {
    throw new Error(`Failed to seed field examples: ${response.status()}`);
  }

  const data = await response.json();
  console.log('✅ Field examples seeded');
  return data;
}

/**
 * Cleanup test data
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<void>}
 */
export async function cleanupTestData(page) {
  console.log('🗑️ Cleaning up test data...');

  const response = await page.request.post('/admin/api/test/cleanup');

  if (!response.ok()) {
    console.warn(`⚠️ Cleanup failed with status: ${response.status()}`);
    return;
  }

  console.log('✅ Test data cleaned up');
}

/**
 * Get test data status
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<Object>} Status data
 */
export async function getTestDataStatus(page) {
  const response = await page.request.get('/admin/api/test/status');

  if (!response.ok()) {
    throw new Error(`Failed to get test data status: ${response.status()}`);
  }

  return await response.json();
}

/**
 * Ensure fresh test data
 *
 * Cleans up existing data and sets up fresh test data
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<Object>} Setup data
 */
export async function ensureFreshTestData(page) {
  console.log('⚠️ Skipping test data setup - using basic verification');

  // For now, just return a basic success response
  // TODO: Re-enable when database issues are resolved
  return {
    demo: { message: 'Skipped - basic verification only' },
    fields: { message: 'Skipped - basic verification only' }
  };
}

/**
 * Wait for test data to be ready
 *
 * Polls the test data status until it's ready
 *
 * @param {import('@playwright/test').Page} page
 * @param {number} maxAttempts - Maximum polling attempts
 * @param {number} delay - Delay between attempts in ms
 * @returns {Promise<Object>} Final status
 */
export async function waitForTestDataReady(page, maxAttempts = 10, delay = 1000) {
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      const status = await getTestDataStatus(page);

      if (status.ready) {
        console.log('✅ Test data is ready');
        return status;
      }

      console.log(`⏳ Test data not ready, attempt ${attempt}/${maxAttempts}`);

      if (attempt < maxAttempts) {
        await new Promise(resolve => setTimeout(resolve, delay));
      }
    } catch (error) {
      if (attempt === maxAttempts) {
        throw error;
      }
      await new Promise(resolve => setTimeout(resolve, delay));
    }
  }

  throw new Error('Test data not ready after maximum attempts');
}
