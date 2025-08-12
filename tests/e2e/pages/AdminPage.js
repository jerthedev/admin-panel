/**
 * Base Admin Page Object Model
 * 
 * Provides common functionality for all admin panel pages
 */
export class AdminPage {
  constructor(page) {
    this.page = page;
  }

  // Common selectors
  get adminLayout() {
    return this.page.locator('[data-testid="admin-layout"]');
  }

  get navigation() {
    return this.page.locator('[data-testid="admin-navigation"]');
  }

  get sidebar() {
    return this.page.locator('[data-testid="admin-sidebar"]');
  }

  get mainContent() {
    return this.page.locator('[data-testid="admin-main-content"]');
  }

  get loadingSpinner() {
    return this.page.locator('[data-testid="loading-spinner"]');
  }

  get alertMessage() {
    return this.page.locator('[data-testid="alert-message"]');
  }

  // Common actions
  async waitForPageLoad() {
    await this.adminLayout.waitFor({ state: 'visible', timeout: 10000 });
    await this.waitForLoadingToComplete();
  }

  async waitForLoadingToComplete() {
    // Wait for any loading spinners to disappear
    try {
      await this.loadingSpinner.waitFor({ state: 'hidden', timeout: 5000 });
    } catch (error) {
      // Loading spinner might not be present, which is fine
    }
  }

  async clickNavigationItem(text) {
    await this.navigation.getByText(text).click();
    await this.waitForLoadingToComplete();
  }

  async clickSidebarItem(text) {
    await this.sidebar.getByText(text).click();
    await this.waitForLoadingToComplete();
  }

  async getAlertMessage() {
    try {
      await this.alertMessage.waitFor({ state: 'visible', timeout: 2000 });
      return await this.alertMessage.textContent();
    } catch (error) {
      return null;
    }
  }

  async dismissAlert() {
    const dismissButton = this.alertMessage.locator('[data-testid="alert-dismiss"]');
    if (await dismissButton.isVisible()) {
      await dismissButton.click();
    }
  }

  async takeScreenshot(name) {
    await this.page.screenshot({ 
      path: `test-results/screenshots/${name}.png`,
      fullPage: true 
    });
  }
}
