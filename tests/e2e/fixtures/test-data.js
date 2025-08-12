/**
 * Test Data Fixtures
 * 
 * Common test data and configuration for Playwright tests
 */

export const TEST_USERS = {
  admin: {
    email: 'admin@example.com',
    password: 'password',
    name: 'Admin User',
    is_admin: true
  },
  
  user: {
    email: 'user@example.com',
    password: 'password',
    name: 'Regular User',
    is_admin: false
  }
};

export const TEST_RESOURCES = {
  posts: {
    title: 'Test Post',
    content: 'This is a test post content',
    status: 'published',
    is_published: true,
    is_featured: false
  },
  
  users: {
    name: 'Test User',
    email: 'test@example.com',
    is_admin: false,
    is_active: true
  }
};

export const ADMIN_ROUTES = {
  login: '/admin/login',
  dashboard: '/admin',
  resources: '/admin/resources',
  users: '/admin/resources/users',
  posts: '/admin/resources/posts'
};

export const TEST_TIMEOUTS = {
  short: 2000,
  medium: 5000,
  long: 10000,
  navigation: 15000,
  server: 30000
};

export const BROWSER_VIEWPORTS = {
  desktop: { width: 1280, height: 720 },
  tablet: { width: 768, height: 1024 },
  mobile: { width: 375, height: 667 }
};

export const TEST_SELECTORS = {
  adminLayout: '[data-testid="admin-layout"]',
  navigation: '[data-testid="admin-navigation"]',
  sidebar: '[data-testid="admin-sidebar"]',
  mainContent: '[data-testid="admin-main-content"]',
  loadingSpinner: '[data-testid="loading-spinner"]',
  alertMessage: '[data-testid="alert-message"]',
  
  // Form elements
  submitButton: 'button[type="submit"]',
  cancelButton: '[data-testid="cancel-button"]',
  saveButton: '[data-testid="save-button"]',
  deleteButton: '[data-testid="delete-button"]',
  
  // Table elements
  dataTable: '[data-testid="data-table"]',
  tableRow: '[data-testid="table-row"]',
  tableHeader: '[data-testid="table-header"]',
  
  // Modal elements
  modal: '[data-testid="modal"]',
  modalTitle: '[data-testid="modal-title"]',
  modalContent: '[data-testid="modal-content"]',
  modalClose: '[data-testid="modal-close"]'
};

export const API_ENDPOINTS = {
  testSetup: '/admin/api/test/setup-admin-demo',
  testCleanup: '/admin/api/test/cleanup',
  testSeed: '/admin/api/test/seed-field-examples',
  testStatus: '/admin/api/test/status',
  
  search: '/admin/api/search',
  metrics: '/admin/api/metrics',
  clearCache: '/admin/api/system/clear-cache'
};

export const FIELD_TYPES = [
  'text',
  'textarea',
  'email',
  'password',
  'number',
  'select',
  'checkbox',
  'radio',
  'date',
  'datetime',
  'time',
  'file',
  'image',
  'boolean',
  'json',
  'markdown',
  'rich-text',
  'currency',
  'phone',
  'url',
  'color',
  'range',
  'tags',
  'relationship',
  'belongs-to',
  'has-many',
  'many-to-many',
  'morph-to',
  'morph-many'
];

export const METRIC_TYPES = [
  'value',
  'trend',
  'partition',
  'progress'
];

export const ACTION_TYPES = [
  'standalone',
  'resource',
  'bulk'
];

/**
 * Generate test data for a specific field type
 */
export function generateFieldTestData(fieldType) {
  const testData = {
    text: 'Sample text value',
    textarea: 'Sample textarea content\nWith multiple lines',
    email: 'test@example.com',
    password: 'SecurePassword123!',
    number: 42,
    select: 'option1',
    checkbox: true,
    radio: 'option1',
    date: '2024-01-15',
    datetime: '2024-01-15T10:30:00',
    time: '10:30:00',
    boolean: true,
    json: '{"key": "value"}',
    markdown: '# Heading\n\nSample **markdown** content',
    currency: '1234.56',
    phone: '+1-555-123-4567',
    url: 'https://example.com',
    color: '#ff0000',
    range: 75,
    tags: ['tag1', 'tag2', 'tag3']
  };
  
  return testData[fieldType] || 'Default test value';
}

/**
 * Get test configuration for different environments
 */
export function getTestConfig(environment = 'local') {
  const configs = {
    local: {
      baseURL: 'http://localhost:8000',
      timeout: TEST_TIMEOUTS.medium,
      retries: 0,
      workers: 1
    },
    
    ci: {
      baseURL: 'http://localhost:8000',
      timeout: TEST_TIMEOUTS.long,
      retries: 2,
      workers: 1
    },
    
    staging: {
      baseURL: process.env.STAGING_URL || 'https://staging.example.com',
      timeout: TEST_TIMEOUTS.long,
      retries: 1,
      workers: 2
    }
  };
  
  return configs[environment] || configs.local;
}
