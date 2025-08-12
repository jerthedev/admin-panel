import { AdminPage } from './AdminPage.js';

/**
 * Dashboard Page Object Model
 * 
 * Handles admin panel dashboard interactions
 */
export class DashboardPage extends AdminPage {
  constructor(page) {
    super(page);
    this.url = '/admin';
  }

  // Selectors
  get pageTitle() {
    return this.page.locator('h1, [data-testid="page-title"]');
  }

  get metricsSection() {
    return this.page.locator('[data-testid="metrics-section"]');
  }

  get metricCards() {
    return this.page.locator('[data-testid="metric-card"]');
  }

  get widgetsSection() {
    return this.page.locator('[data-testid="widgets-section"]');
  }

  get recentActivitySection() {
    return this.page.locator('[data-testid="recent-activity"]');
  }

  get quickActionsSection() {
    return this.page.locator('[data-testid="quick-actions"]');
  }

  get quickActionButtons() {
    return this.page.locator('[data-testid="quick-action-button"]');
  }

  get systemInfoSection() {
    return this.page.locator('[data-testid="system-info"]');
  }

  // Actions
  async goto() {
    await this.page.goto(this.url);
    await this.waitForPageLoad();
  }

  async getPageTitle() {
    await this.pageTitle.waitFor({ state: 'visible', timeout: 5000 });
    return await this.pageTitle.textContent();
  }

  async getMetricCount() {
    await this.metricsSection.waitFor({ state: 'visible', timeout: 5000 });
    return await this.metricCards.count();
  }

  async getMetricValue(index = 0) {
    const metricCard = this.metricCards.nth(index);
    const valueElement = metricCard.locator('[data-testid="metric-value"]');
    return await valueElement.textContent();
  }

  async getMetricLabel(index = 0) {
    const metricCard = this.metricCards.nth(index);
    const labelElement = metricCard.locator('[data-testid="metric-label"]');
    return await labelElement.textContent();
  }

  async clickQuickAction(text) {
    const button = this.quickActionButtons.filter({ hasText: text });
    await button.click();
    await this.waitForLoadingToComplete();
  }

  async getQuickActionCount() {
    return await this.quickActionButtons.count();
  }

  async isMetricsSectionVisible() {
    return await this.metricsSection.isVisible();
  }

  async isWidgetsSectionVisible() {
    return await this.widgetsSection.isVisible();
  }

  async isRecentActivityVisible() {
    return await this.recentActivitySection.isVisible();
  }

  async isQuickActionsVisible() {
    return await this.quickActionsSection.isVisible();
  }

  async isSystemInfoVisible() {
    return await this.systemInfoSection.isVisible();
  }

  async waitForMetricsToLoad() {
    await this.metricsSection.waitFor({ state: 'visible', timeout: 10000 });
    
    // Wait for at least one metric card to be visible
    await this.metricCards.first().waitFor({ state: 'visible', timeout: 5000 });
  }

  async refreshDashboard() {
    await this.page.reload();
    await this.waitForPageLoad();
  }
}
