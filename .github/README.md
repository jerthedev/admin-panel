# GitHub Actions CI/CD for JTD Admin Panel

This directory contains GitHub Actions workflows for continuous integration and deployment of the JTD Admin Panel package.

## 🚀 Workflows

### 1. **Admin Panel CI** (`admin-panel-ci.yml`)
**Triggers:** Push/PR to main/develop branches
**Purpose:** Comprehensive testing and code quality checks

#### Jobs:
- **PHP Tests** - PHPUnit tests across PHP 8.2/8.3 and Laravel 11.x/12.x
- **JavaScript Tests** - Jest/Vitest tests for Vue components
- **Build Tests** - Asset compilation and build verification
- **Smoke Test** - Quick E2E test to verify basic functionality
- **Code Quality** - PHP CS Fixer, PHPStan, ESLint, TypeScript checks

#### Runtime: ~8-12 minutes

### 2. **Admin Panel E2E Tests** (`admin-panel-e2e.yml`)
**Triggers:** Push/PR to main/develop branches, manual dispatch
**Purpose:** Comprehensive end-to-end testing across browsers

#### Jobs:
- **E2E Tests** - Full Playwright test suite (Chrome, Firefox)
- **Test Summary** - Aggregated results and reporting
- **Deploy Preview** - PR comments with test results

#### Runtime: ~5-8 minutes

## 📊 Test Coverage

### E2E Test Coverage
- ✅ **Authentication workflow** - Login, logout, session management
- ✅ **Admin panel access** - Unauthenticated blocking, proper redirects
- ✅ **Form interactions** - Login form validation and submission
- ✅ **Responsive design** - Desktop, tablet, mobile viewports
- ✅ **Performance monitoring** - Page load time tracking
- ✅ **Error detection** - JavaScript errors, network failures
- ✅ **Security validation** - Invalid credentials handling

### Browser Support
- ✅ **Chrome** (Primary) - 100% test coverage
- ✅ **Firefox** (Secondary) - 100% test coverage
- ⚠️ **Safari** (Tertiary) - 89% test coverage (excluded from CI due to timeout issues)

## 🔧 Configuration

### Environment Variables
The workflows automatically configure the following environment variables:

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=admin_panel_test
DB_USERNAME=root
DB_PASSWORD=password
ADMIN_PANEL_ALLOW_ALL=true
ADMIN_PANEL_TEST_ENDPOINTS=true
```

### Services
- **MySQL 8.0** - Database for testing
- **Node.js 20** - JavaScript runtime
- **PHP 8.2/8.3** - PHP runtime with required extensions

## 📈 Performance Metrics

### CI Pipeline Performance
- **Total CI Runtime**: 8-12 minutes
- **E2E Test Runtime**: 5-8 minutes
- **Smoke Test Runtime**: 2-3 minutes
- **Build Time**: 1-2 minutes

### Test Performance
- **E2E Test Suite**: 7.1 seconds (9 tests)
- **PHP Unit Tests**: 2-5 seconds
- **JavaScript Tests**: 1-3 seconds
- **Build Process**: 30-60 seconds

## 🎯 Success Criteria

### Required Checks
All workflows must pass for PR approval:

1. **✅ PHP Tests** - All unit tests pass
2. **✅ JavaScript Tests** - All component tests pass
3. **✅ Build Tests** - Assets compile successfully
4. **✅ Smoke Test** - Basic E2E functionality works
5. **✅ Code Quality** - Linting and static analysis pass

### Optional Checks
These provide additional confidence but don't block PRs:

1. **📊 Full E2E Tests** - Comprehensive browser testing
2. **📈 Performance Tests** - Load time monitoring
3. **🔍 Security Scans** - Dependency vulnerability checks

## 🛠️ Local Development

### Running Tests Locally

```bash
# Run all tests (matches CI)
cd packages/jerthedev/admin-panel

# PHP tests
composer test

# JavaScript tests
npm run test

# E2E tests
npm run test:e2e

# Code quality
vendor/bin/pint
vendor/bin/phpstan analyse
npm run lint
npm run type-check
```

### Debugging CI Failures

1. **Check workflow logs** in GitHub Actions tab
2. **Download artifacts** for detailed test reports
3. **Run tests locally** with same environment
4. **Check screenshots/videos** for E2E test failures

## 📋 Artifacts

### Generated Artifacts
- **Test Reports** - HTML, JUnit, JSON formats
- **Screenshots** - Failure screenshots from E2E tests
- **Videos** - Full test execution recordings
- **Build Assets** - Compiled JavaScript and CSS
- **Coverage Reports** - Code coverage data

### Retention
- **Test Results**: 7 days
- **Build Artifacts**: 7 days
- **Coverage Reports**: 30 days (via Codecov)

## 🔄 Workflow Triggers

### Automatic Triggers
- **Push to main/develop** - Full CI pipeline
- **Pull Request** - Full CI pipeline + E2E tests
- **File changes** - Only when admin panel files change

### Manual Triggers
- **Workflow Dispatch** - Manual E2E test execution
- **Re-run Failed Jobs** - Individual job retry

## 🚨 Troubleshooting

### Common Issues

#### 1. **E2E Test Timeouts**
- **Cause**: Server startup delays in CI environment
- **Solution**: Increase timeout values or add retry logic

#### 2. **Database Connection Failures**
- **Cause**: MySQL service not ready
- **Solution**: Add health checks and wait conditions

#### 3. **Asset Build Failures**
- **Cause**: Node.js version mismatch or dependency issues
- **Solution**: Clear cache, update dependencies

#### 4. **Browser Installation Failures**
- **Cause**: Playwright browser download issues
- **Solution**: Use cached browsers or retry installation

### Debug Commands

```bash
# Check server status
curl -f http://localhost:8000/admin/test

# Verify database connection
php artisan tinker --execute="DB::connection()->getPdo()"

# Test browser installation
npx playwright install --dry-run

# Check asset compilation
npm run build -- --verbose
```

## 📚 Documentation

### Related Documentation
- [Playwright Configuration](../packages/jerthedev/admin-panel/playwright.config.js)
- [E2E Test Suite](../packages/jerthedev/admin-panel/tests/e2e/)
- [Package Documentation](../packages/jerthedev/admin-panel/README.md)

### External Resources
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Playwright CI Documentation](https://playwright.dev/docs/ci)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)

## 🎉 Success Metrics

### Quality Gates
- **✅ 100% test pass rate** on main branch
- **✅ Zero critical security vulnerabilities**
- **✅ Code coverage > 80%**
- **✅ Build time < 15 minutes**
- **✅ E2E test reliability > 95%**

### Performance Targets
- **⚡ E2E tests < 10 seconds**
- **⚡ Build time < 2 minutes**
- **⚡ Total CI time < 15 minutes**
- **⚡ Page load time < 3 seconds**
