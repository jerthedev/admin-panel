import { chromium } from '@playwright/test';

/**
 * Global Teardown for Playwright Tests
 * 
 * Runs once after all tests to clean up the testing environment.
 * Cleans up test data and performs final cleanup operations.
 */
async function globalTeardown() {
  console.log('🧹 Starting global test teardown...');
  
  // Launch browser for teardown operations
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  
  try {
    // Clean up test data
    await cleanupTestData(page);
    
    console.log('✅ Global teardown completed successfully');
  } catch (error) {
    console.error('❌ Global teardown failed:', error);
    // Don't throw error in teardown to avoid masking test failures
  } finally {
    await browser.close();
  }
}

/**
 * Clean up test data using Laravel test APIs
 */
async function cleanupTestData(page) {
  console.log('🗑️ Cleaning up test data...');
  
  try {
    const response = await page.request.post('http://localhost:8000/admin/api/test/cleanup');
    
    if (response.ok()) {
      console.log('✅ Test data cleanup completed');
    } else {
      console.warn(`⚠️ Test data cleanup failed with status: ${response.status()}`);
    }
  } catch (error) {
    console.warn('⚠️ Test data cleanup failed:', error.message);
  }
}

export default globalTeardown;
