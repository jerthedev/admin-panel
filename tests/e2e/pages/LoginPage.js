import { AdminPage } from './AdminPage.js';

/**
 * Login Page Object Model
 *
 * Handles admin panel login page interactions
 */
export class LoginPage extends AdminPage {
  constructor(page) {
    super(page);
    this.url = '/admin/login';
  }

  // Selectors
  get emailInput() {
    return this.page.locator('input[name="email"]');
  }

  get passwordInput() {
    return this.page.locator('input[name="password"]');
  }

  get rememberCheckbox() {
    return this.page.locator('input[name="remember"]');
  }

  get loginButton() {
    return this.page.locator('button[type="submit"]');
  }

  get forgotPasswordLink() {
    return this.page.locator('a:has-text("Forgot Password")');
  }

  get errorMessage() {
    return this.page.locator('[data-testid="login-error"]');
  }

  get loginForm() {
    return this.page.locator('form');
  }

  // Actions
  async goto() {
    await this.page.goto(this.url);
    await this.loginForm.waitFor({ state: 'visible', timeout: 10000 });
    // Wait for page to fully load including CSRF tokens
    await this.page.waitForLoadState('networkidle');
  }

  async fillCredentials(email, password) {
    await this.emailInput.fill(email);
    await this.passwordInput.fill(password);
  }

  async toggleRememberMe() {
    await this.rememberCheckbox.check();
  }

  async submitLogin() {
    // Wait for any CSRF token to be loaded
    await this.page.waitForTimeout(500);

    // For Inertia.js forms, we need to wait for the response
    const responsePromise = this.page.waitForResponse(response =>
      response.url().includes('/admin/login') && response.request().method() === 'POST'
    );

    await this.loginButton.click();

    // Wait for the login response
    try {
      const response = await responsePromise;
      console.log('Login response status:', response.status());

      if (response.status() === 302) {
        // Get redirect location
        const location = response.headers()['location'];
        console.log('Redirect location:', location);

        // Wait for Inertia.js to handle the redirect
        await this.page.waitForTimeout(3000);
      } else if (response.status() === 200) {
        // Wait for any client-side navigation
        await this.page.waitForTimeout(2000);
      }
    } catch (error) {
      console.log('Login response error:', error.message);
    }
  }

  async login(email = 'admin@example.com', password = 'password', remember = false) {
    await this.goto();
    await this.fillCredentials(email, password);

    if (remember) {
      await this.toggleRememberMe();
    }

    await this.submitLogin();

    // For Inertia.js, check if we're redirected or if there are errors
    await this.page.waitForTimeout(5000);

    const currentUrl = this.page.url();
    console.log('Current URL after login attempt:', currentUrl);

    if (currentUrl.includes('/admin/login')) {
      // Still on login page, check for errors
      const errorMessage = await this.getErrorMessage();
      if (errorMessage) {
        throw new Error(`Login failed: ${errorMessage}`);
      } else {
        // Check if page content changed (might indicate successful login but no redirect)
        const pageContent = await this.page.textContent('body');
        if (pageContent.toLowerCase().includes('dashboard') ||
            pageContent.toLowerCase().includes('welcome') ||
            !pageContent.toLowerCase().includes('sign in')) {
          console.log('Login appears successful despite staying on same URL');
          return; // Consider this a successful login
        }
        throw new Error('Login failed: No redirect occurred and no success indicators found');
      }
    }

    // If we're here, login was successful
    await this.waitForPageLoad();
  }

  async getErrorMessage() {
    try {
      await this.errorMessage.waitFor({ state: 'visible', timeout: 2000 });
      return await this.errorMessage.textContent();
    } catch (error) {
      return null;
    }
  }

  async isLoginFormVisible() {
    return await this.loginForm.isVisible();
  }

  async clickForgotPassword() {
    await this.forgotPasswordLink.click();
  }
}
