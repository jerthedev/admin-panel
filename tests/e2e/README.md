# End-to-End Testing with Playwright

This directory contains automated end-to-end tests for the JTD Admin Panel using Playwright.

## Overview

The E2E testing framework provides:
- **Automated test setup** using existing Laravel test APIs
- **Multi-browser testing** (Chrome, Firefox, Safari)
- **Page Object Models** for maintainable test code
- **Test isolation** with automatic cleanup
- **Screenshot and video capture** for debugging
- **CI/CD integration** ready

## Quick Start

### 1. Install Dependencies

```bash
npm install
npm run test:e2e:install
```

### 2. Start Laravel Server

```bash
# From the main Laravel project root
php artisan serve --port=8000
```

### 3. Run Tests

```bash
# Run all E2E tests
npm run test:e2e

# Run tests with browser UI visible
npm run test:e2e:headed

# Run tests in debug mode
npm run test:e2e:debug

# Run tests with Playwright UI
npm run test:e2e:ui

# View test report
npm run test:e2e:report
```

## Test Structure

```
tests/e2e/
├── README.md                 # This file
├── global-setup.js          # Global test setup
├── global-teardown.js       # Global test cleanup
├── admin-navigation.spec.js # Basic navigation tests
├── fixtures/
│   └── test-data.js         # Test data and configuration
├── pages/
│   ├── AdminPage.js         # Base page object model
│   ├── LoginPage.js         # Login page interactions
│   └── DashboardPage.js     # Dashboard page interactions
└── utils/
    ├── auth.js              # Authentication utilities
    └── test-data.js         # Test data management
```

## Page Object Models

### AdminPage (Base Class)
- Common admin panel functionality
- Navigation helpers
- Loading state management
- Screenshot utilities

### LoginPage
- Login form interactions
- Authentication flow
- Error handling

### DashboardPage
- Dashboard-specific interactions
- Metrics verification
- Widget management

## Test Utilities

### Authentication (`utils/auth.js`)
- `loginAsAdmin()` - Login with admin credentials
- `logout()` - Logout from admin panel
- `ensureAuthenticated()` - Ensure user is logged in
- `createAdminSession()` - Create reusable session
- `restoreAdminSession()` - Restore saved session

### Test Data (`utils/test-data.js`)
- `setupAdminDemo()` - Setup demo data
- `seedFieldExamples()` - Seed field examples
- `cleanupTestData()` - Clean up test data
- `ensureFreshTestData()` - Ensure clean state

## Configuration

### Playwright Config (`playwright.config.js`)
- **Base URL**: `http://localhost:8000`
- **Browsers**: Chrome, Firefox, Safari, Mobile
- **Timeouts**: 30s per test, 5s for assertions
- **Retries**: 2 on CI, 0 locally
- **Reports**: HTML, JSON, JUnit, Line

### Test Data (`fixtures/test-data.js`)
- User credentials and test data
- Admin panel routes and selectors
- Timeout configurations
- API endpoints

## Writing Tests

### Basic Test Structure

```javascript
import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage.js';
import { ensureAuthenticated } from './utils/auth.js';

test.describe('Feature Tests', () => {
  test.beforeEach(async ({ page }) => {
    await ensureAuthenticated(page);
  });

  test('should do something', async ({ page }) => {
    // Test implementation
  });
});
```

### Using Page Objects

```javascript
test('should login successfully', async ({ page }) => {
  const loginPage = new LoginPage(page);
  
  await loginPage.goto();
  await loginPage.login('admin@example.com', 'password');
  
  expect(page.url()).toContain('/admin');
});
```

### Test Data Management

```javascript
import { ensureFreshTestData } from './utils/test-data.js';

test.beforeEach(async ({ page }) => {
  await ensureFreshTestData(page);
});
```

## Best Practices

### 1. Test Isolation
- Each test should be independent
- Use `beforeEach` for setup
- Clean up test data between tests

### 2. Reliable Selectors
- Use `data-testid` attributes when possible
- Avoid CSS selectors that might change
- Use semantic selectors (role, text content)

### 3. Waiting Strategies
- Use `waitFor()` for dynamic content
- Wait for network requests to complete
- Use explicit waits over fixed delays

### 4. Error Handling
- Take screenshots on failure
- Use descriptive test names
- Add debug information in assertions

## Debugging

### Visual Debugging
```bash
# Run with browser visible
npm run test:e2e:headed

# Run in debug mode (step through)
npm run test:e2e:debug

# Use Playwright UI
npm run test:e2e:ui
```

### Screenshots and Videos
- Screenshots taken on failure automatically
- Videos recorded for failed tests
- Custom screenshots: `await page.screenshot({ path: 'debug.png' })`

### Console Logs
- Browser console logs captured automatically
- Add custom logging: `console.log()` in test files
- Check network requests in test reports

## CI/CD Integration

The tests are configured for CI/CD with:
- Automatic retries on failure
- Parallel execution disabled for stability
- Multiple output formats (HTML, JUnit, JSON)
- Screenshot and video artifacts

### GitHub Actions Example
```yaml
- name: Run E2E Tests
  run: |
    npm run test:e2e
  env:
    CI: true
```

## Troubleshooting

### Common Issues

1. **Server not ready**: Increase timeout in `global-setup.js`
2. **Authentication fails**: Check test user credentials
3. **Flaky tests**: Add explicit waits, check for race conditions
4. **Browser crashes**: Update browser versions with `npm run test:e2e:install`

### Debug Commands
```bash
# Check Playwright installation
npx playwright --version

# Install browsers
npx playwright install

# Run specific test file
npx playwright test admin-navigation.spec.js

# Run specific test
npx playwright test -g "should display dashboard"
```
