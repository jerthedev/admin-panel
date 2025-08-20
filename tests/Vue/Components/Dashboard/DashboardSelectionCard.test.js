/**
 * Dashboard Selection Card Component Tests
 * 
 * Tests for the DashboardSelectionCard Vue component including
 * metadata display, interactions, and accessibility features.
 */

import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import DashboardSelectionCard from '@/Components/Dashboard/DashboardSelectionCard.vue'

// Mock the composables
vi.mock('@/composables/useDashboardTransitions', () => ({
  useDashboardTransitions: () => ({
    isTransitioning: { value: false },
    currentTransition: { value: null },
    transitionProgress: { value: 0 }
  })
}))

describe('DashboardSelectionCard', () => {
  let wrapper
  let mockDashboard

  beforeEach(() => {
    mockDashboard = {
      uriKey: 'test-dashboard',
      name: 'Test Dashboard',
      description: 'A test dashboard for testing purposes',
      category: 'Analytics',
      icon: 'chart-bar',
      metadata: {
        icon: {
          type: 'heroicon',
          name: 'chart-bar'
        },
        category: 'Analytics',
        tags: ['test', 'analytics', 'example'],
        priority: 100,
        visible: true,
        enabled: true,
        color: '#3B82F6',
        background_color: '#EFF6FF',
        text_color: '#1E40AF',
        author: 'Test Author',
        version: '1.0.0',
        permissions: ['view-analytics'],
        dependencies: ['analytics-service'],
        last_accessed: new Date().toISOString()
      },
      isFavorite: false
    }
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  it('renders dashboard card with basic information', () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard
      }
    })

    expect(wrapper.find('[data-testid="dashboard-selection-card"]').exists()).toBe(true)
    expect(wrapper.find('.dashboard-title').text()).toBe('Test Dashboard')
    expect(wrapper.find('.dashboard-description').text()).toBe('A test dashboard for testing purposes')
  })

  it('displays dashboard metadata correctly', () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        showMetadata: true
      }
    })

    expect(wrapper.find('.dashboard-metadata').exists()).toBe(true)
    
    // Check if tags are displayed
    const tags = wrapper.findAll('.tag')
    expect(tags.length).toBeGreaterThan(0)
    
    // Check if category is displayed
    expect(wrapper.text()).toContain('Analytics')
  })

  it('handles click events when interactive', async () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        interactive: true
      }
    })

    await wrapper.find('[data-testid="dashboard-selection-card"]').trigger('click')
    
    expect(wrapper.emitted('click')).toBeTruthy()
    expect(wrapper.emitted('click')[0]).toEqual([mockDashboard])
  })

  it('does not handle click events when not interactive', async () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        interactive: false
      }
    })

    await wrapper.find('[data-testid="dashboard-selection-card"]').trigger('click')
    
    expect(wrapper.emitted('click')).toBeFalsy()
  })

  it('handles keyboard navigation', async () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        interactive: true
      }
    })

    const card = wrapper.find('[data-testid="dashboard-selection-card"]')
    
    // Test Enter key
    await card.trigger('keydown.enter')
    expect(wrapper.emitted('click')).toBeTruthy()
    
    // Test Space key
    await card.trigger('keydown.space')
    expect(wrapper.emitted('click')).toBeTruthy()
  })

  it('displays favorite button when enabled', () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        showFavorite: true
      }
    })

    expect(wrapper.find('.favorite-button').exists()).toBe(true)
  })

  it('handles favorite toggle', async () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        showFavorite: true
      }
    })

    await wrapper.find('.favorite-button').trigger('click')
    
    expect(wrapper.emitted('favorite')).toBeTruthy()
    expect(wrapper.emitted('favorite')[0][0]).toMatchObject({
      dashboard: mockDashboard,
      isFavorite: true
    })
  })

  it('displays badge when provided', () => {
    const badge = {
      value: 5,
      type: 'warning'
    }

    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        badge
      }
    })

    expect(wrapper.find('.badge-container').exists()).toBe(true)
  })

  it('shows loading overlay when loading', () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        isLoading: true
      }
    })

    expect(wrapper.find('.card-loading-overlay').exists()).toBe(true)
    expect(wrapper.classes()).toContain('is-loading')
  })

  it('applies disabled styles when dashboard is disabled', () => {
    const disabledDashboard = {
      ...mockDashboard,
      metadata: {
        ...mockDashboard.metadata,
        enabled: false
      }
    }

    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: disabledDashboard
      }
    })

    expect(wrapper.classes()).toContain('is-disabled')
  })

  it('truncates long descriptions', () => {
    const longDescription = 'A'.repeat(200)
    const dashboardWithLongDesc = {
      ...mockDashboard,
      description: longDescription
    }

    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: dashboardWithLongDesc,
        maxDescriptionLength: 50
      }
    })

    const description = wrapper.find('.dashboard-description').text()
    expect(description.length).toBeLessThanOrEqual(53) // 50 + '...'
    expect(description).toContain('...')
  })

  it('displays status indicators when enabled', () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        showStatus: true
      }
    })

    expect(wrapper.find('.status-indicators').exists()).toBe(true)
  })

  it('shows footer when enabled and has content', () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        showFooter: true
      }
    })

    expect(wrapper.find('.card-footer').exists()).toBe(true)
    expect(wrapper.text()).toContain('Test Author')
    expect(wrapper.text()).toContain('v1.0.0')
  })

  it('displays action buttons when enabled', () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        showActions: true,
        canEdit: true,
        canDelete: true,
        canConfigure: true
      }
    })

    expect(wrapper.find('.action-button.edit').exists()).toBe(true)
    expect(wrapper.find('.action-button.delete').exists()).toBe(true)
    expect(wrapper.find('.action-button.configure').exists()).toBe(true)
  })

  it('emits action events correctly', async () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        showActions: true,
        canEdit: true,
        canDelete: true,
        canConfigure: true
      }
    })

    // Test edit action
    await wrapper.find('.action-button.edit').trigger('click')
    expect(wrapper.emitted('edit')).toBeTruthy()
    expect(wrapper.emitted('edit')[0]).toEqual([mockDashboard])

    // Test delete action
    await wrapper.find('.action-button.delete').trigger('click')
    expect(wrapper.emitted('delete')).toBeTruthy()
    expect(wrapper.emitted('delete')[0]).toEqual([mockDashboard])

    // Test configure action
    await wrapper.find('.action-button.configure').trigger('click')
    expect(wrapper.emitted('configure')).toBeTruthy()
    expect(wrapper.emitted('configure')[0]).toEqual([mockDashboard])
  })

  it('applies correct size classes', () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        size: 'large'
      }
    })

    expect(wrapper.classes()).toContain('size-large')
  })

  it('applies correct variant classes', () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        variant: 'compact'
      }
    })

    expect(wrapper.classes()).toContain('variant-compact')
  })

  it('generates correct aria label', () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard
      }
    })

    const ariaLabel = wrapper.find('[data-testid="dashboard-selection-card"]').attributes('aria-label')
    expect(ariaLabel).toContain('Dashboard: Test Dashboard')
    expect(ariaLabel).toContain('A test dashboard for testing purposes')
    expect(ariaLabel).toContain('Category: Analytics')
  })

  it('handles custom colors from metadata', () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard
      }
    })

    expect(wrapper.classes()).toContain('has-custom-colors')
  })

  it('formats last accessed time correctly', () => {
    const now = new Date()
    const fiveMinutesAgo = new Date(now.getTime() - 5 * 60 * 1000)
    
    const dashboardWithLastAccessed = {
      ...mockDashboard,
      metadata: {
        ...mockDashboard.metadata,
        last_accessed: fiveMinutesAgo.toISOString()
      }
    }

    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: dashboardWithLastAccessed,
        showLastAccessed: true
      }
    })

    expect(wrapper.text()).toContain('5m ago')
  })

  it('limits visible tags based on maxTags prop', () => {
    wrapper = mount(DashboardSelectionCard, {
      props: {
        dashboard: mockDashboard,
        maxTags: 2
      }
    })

    // Should show 2 tags plus a "more" indicator
    const tags = wrapper.findAll('.tag')
    expect(tags.length).toBeLessThanOrEqual(3) // 2 visible + 1 more indicator
  })
})
