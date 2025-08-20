/**
 * Dashboard Components Index
 * 
 * Exports all dashboard-related Vue components for easy importing
 * throughout the application.
 */

// Main Dashboard Components
export { default as Dashboard } from './Dashboard.vue'
export { default as DashboardCard } from './DashboardCard.vue'
export { default as DashboardSelector } from './DashboardSelector.vue'

// Dashboard Card Components
export { default as BaseCard } from './Cards/BaseCard.vue'
export { default as MetricCard } from './Cards/MetricCard.vue'
export { default as WelcomeCard } from './Cards/WelcomeCard.vue'

// Component Registration Helper
export const registerDashboardComponents = (app) => {
  // Register main components
  app.component('Dashboard', () => import('./Dashboard.vue'))
  app.component('DashboardCard', () => import('./DashboardCard.vue'))
  app.component('DashboardSelector', () => import('./DashboardSelector.vue'))
  
  // Register card components
  app.component('BaseCard', () => import('./Cards/BaseCard.vue'))
  app.component('MetricCard', () => import('./Cards/MetricCard.vue'))
  app.component('WelcomeCard', () => import('./Cards/WelcomeCard.vue'))
}

// Card Component Registry
export const cardComponents = {
  BaseCard: () => import('./Cards/BaseCard.vue'),
  MetricCard: () => import('./Cards/MetricCard.vue'),
  WelcomeCard: () => import('./Cards/WelcomeCard.vue')
}

// Helper function to get card component by name
export const getCardComponent = (componentName) => {
  return cardComponents[componentName] || cardComponents.BaseCard
}

// Dashboard configuration helpers
export const createDashboardConfig = (name, uriKey, options = {}) => {
  return {
    name,
    uriKey,
    description: options.description || '',
    showRefreshButton: options.showRefreshButton || false,
    ...options
  }
}

export const createCardConfig = (component, props = {}, options = {}) => {
  return {
    component,
    title: options.title || '',
    subtitle: options.subtitle || '',
    actions: options.actions || [],
    meta: options.meta || {},
    links: options.links || [],
    ...props
  }
}

// Type definitions for TypeScript support (if needed)
export const DashboardTypes = {
  Dashboard: 'Dashboard',
  Card: 'Card',
  Selector: 'Selector'
}

export const CardTypes = {
  Base: 'BaseCard',
  Metric: 'MetricCard',
  Welcome: 'WelcomeCard'
}
