/**
 * DashboardGrid Integration Tests
 * 
 * Tests the integration of DashboardGrid.vue component with other dashboard
 * components, card components, and the overall dashboard system.
 */

import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { nextTick } from 'vue'
import DashboardGrid from '@/components/Dashboard/DashboardGrid.vue'
import BaseCard from '@/components/Dashboard/Cards/BaseCard.vue'
import MetricCard from '@/components/Dashboard/Cards/MetricCard.vue'

// Mock ResizeObserver
global.ResizeObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}))

// Mock window.matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
})

describe('DashboardGrid Integration', () => {
  let wrapper
  let mockCards

  beforeEach(() => {
    mockCards = [
      {
        id: 'metric-1',
        component: 'MetricCard',
        title: 'Total Users',
        gridArea: { row: 1, column: 1, rowSpan: 1, columnSpan: 1 },
        props: {
          value: 1250,
          format: 'number',
          trend: { direction: 'up', value: 12 }
        }
      },
      {
        id: 'metric-2',
        component: 'MetricCard',
        title: 'Revenue',
        gridArea: { row: 1, column: 2, rowSpan: 1, columnSpan: 2 },
        props: {
          value: 45230.50,
          format: 'currency',
          trend: { direction: 'up', value: 8.5 }
        }
      },
      {
        id: 'base-1',
        component: 'BaseCard',
        title: 'System Status',
        gridArea: { row: 2, column: 1, rowSpan: 1, columnSpan: 3 },
        props: {
          status: 'healthy',
          lastUpdated: new Date().toISOString()
        }
      }
    ]
  })

  afterEach(() => {
    if (wrapper) wrapper.unmount()
  })

  describe('Card Component Integration', () => {
    it('renders different card types correctly', () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards },
        global: {
          components: {
            MetricCard,
            BaseCard
          }
        }
      })

      const gridItems = wrapper.findAll('.grid-item')
      expect(gridItems).toHaveLength(3)

      // Check that components are rendered
      expect(wrapper.findComponent(MetricCard)).toBeTruthy()
      expect(wrapper.findComponent(BaseCard)).toBeTruthy()
    })

    it('passes props correctly to card components', () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards },
        global: {
          components: {
            MetricCard,
            BaseCard
          }
        }
      })

      const metricCards = wrapper.findAllComponents(MetricCard)
      expect(metricCards).toHaveLength(2)

      // Check first metric card props
      const firstMetricCard = metricCards[0]
      expect(firstMetricCard.props('value')).toBe(1250)
      expect(firstMetricCard.props('format')).toBe('number')

      // Check second metric card props
      const secondMetricCard = metricCards[1]
      expect(secondMetricCard.props('value')).toBe(45230.50)
      expect(secondMetricCard.props('format')).toBe('currency')
    })

    it('handles card errors gracefully', async () => {
      const errorCard = {
        id: 'error-card',
        component: 'NonExistentCard',
        title: 'Error Card',
        gridArea: { row: 1, column: 1, rowSpan: 1, columnSpan: 1 }
      }

      wrapper = mount(DashboardGrid, {
        props: { cards: [errorCard] }
      })

      // Should not crash when component doesn't exist
      expect(wrapper.exists()).toBe(true)
      expect(wrapper.findAll('.grid-item')).toHaveLength(1)
    })
  })

  describe('Responsive Layout Integration', () => {
    it('adapts grid layout for mobile viewport', async () => {
      // Mock mobile viewport
      window.matchMedia = vi.fn().mockImplementation(query => ({
        matches: query === '(max-width: 767px)',
        media: query,
        onchange: null,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
      }))

      wrapper = mount(DashboardGrid, {
        props: { 
          cards: mockCards,
          columns: { mobile: 1, tablet: 2, desktop: 3, wide: 4 }
        }
      })

      await nextTick()

      const gridElement = wrapper.find('.dashboard-grid')
      expect(gridElement.element.style.gridTemplateColumns).toBe('repeat(1, 1fr)')
      expect(wrapper.vm.currentBreakpoint).toBe('mobile')
    })

    it('adapts grid layout for tablet viewport', async () => {
      // Mock tablet viewport
      window.matchMedia = vi.fn().mockImplementation(query => ({
        matches: query === '(min-width: 768px) and (max-width: 1023px)',
        media: query,
        onchange: null,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
      }))

      wrapper = mount(DashboardGrid, {
        props: { 
          cards: mockCards,
          columns: { mobile: 1, tablet: 2, desktop: 3, wide: 4 }
        }
      })

      await nextTick()

      const gridElement = wrapper.find('.dashboard-grid')
      expect(gridElement.element.style.gridTemplateColumns).toBe('repeat(2, 1fr)')
      expect(wrapper.vm.currentBreakpoint).toBe('tablet')
    })

    it('handles viewport changes dynamically', async () => {
      let mediaQueryCallback = null
      
      // Mock matchMedia with callback capture
      window.matchMedia = vi.fn().mockImplementation(query => {
        const mq = {
          matches: query === '(min-width: 1024px) and (max-width: 1535px)',
          media: query,
          onchange: null,
          addListener: vi.fn(),
          removeListener: vi.fn(),
          addEventListener: vi.fn((event, callback) => {
            if (event === 'change') {
              mediaQueryCallback = callback
            }
          }),
          removeEventListener: vi.fn(),
          dispatchEvent: vi.fn(),
        }
        return mq
      })

      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      await nextTick()
      expect(wrapper.vm.currentBreakpoint).toBe('desktop')

      // Simulate viewport change to mobile
      if (mediaQueryCallback) {
        window.matchMedia = vi.fn().mockImplementation(query => ({
          matches: query === '(max-width: 767px)',
          media: query,
          onchange: null,
          addListener: vi.fn(),
          removeListener: vi.fn(),
          addEventListener: vi.fn(),
          removeEventListener: vi.fn(),
          dispatchEvent: vi.fn(),
        }))

        mediaQueryCallback()
        await nextTick()
        
        expect(wrapper.vm.currentBreakpoint).toBe('mobile')
      }
    })
  })

  describe('Dashboard System Integration', () => {
    it('integrates with dashboard refresh functionality', async () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      // Simulate dashboard refresh
      wrapper.vm.handleResize()
      await nextTick()

      expect(wrapper.emitted('grid-resize')).toBeTruthy()
      expect(wrapper.emitted('grid-resize')[0][0]).toMatchObject({
        breakpoint: expect.any(String),
        columns: expect.any(Number),
        gridElement: expect.any(Object)
      })
    })

    it('handles card interactions correctly', async () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      const firstGridItem = wrapper.find('.grid-item')
      await firstGridItem.trigger('click')

      expect(wrapper.emitted('card-click')).toBeTruthy()
      expect(wrapper.emitted('card-click')[0][0]).toEqual(mockCards[0])
    })

    it('supports dashboard-level card filtering', () => {
      const filteredCards = mockCards.filter(card => card.component === 'MetricCard')
      
      wrapper = mount(DashboardGrid, {
        props: { cards: filteredCards }
      })

      expect(wrapper.findAll('.grid-item')).toHaveLength(2)
      expect(wrapper.vm.cards).toEqual(filteredCards)
    })
  })

  describe('Performance Integration', () => {
    it('handles large datasets efficiently', () => {
      const largeCardSet = Array.from({ length: 50 }, (_, i) => ({
        id: `card-${i}`,
        component: 'MetricCard',
        title: `Metric ${i}`,
        gridArea: { 
          row: Math.floor(i / 4) + 1, 
          column: (i % 4) + 1, 
          rowSpan: 1, 
          columnSpan: 1 
        },
        props: { value: i * 100 }
      }))

      const startTime = performance.now()
      wrapper = mount(DashboardGrid, {
        props: { cards: largeCardSet }
      })
      const endTime = performance.now()

      expect(endTime - startTime).toBeLessThan(200) // Should render in less than 200ms
      expect(wrapper.findAll('.grid-item')).toHaveLength(50)
    })

    it('optimizes re-renders when cards change', async () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      const initialRenderCount = wrapper.findAll('.grid-item').length
      expect(initialRenderCount).toBe(3)

      // Add a new card
      const newCard = {
        id: 'new-card',
        component: 'MetricCard',
        title: 'New Metric',
        gridArea: { row: 3, column: 1, rowSpan: 1, columnSpan: 1 },
        props: { value: 999 }
      }

      await wrapper.setProps({ cards: [...mockCards, newCard] })

      const updatedRenderCount = wrapper.findAll('.grid-item').length
      expect(updatedRenderCount).toBe(4)
    })
  })

  describe('Accessibility Integration', () => {
    it('maintains accessibility standards with card components', () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      const gridElement = wrapper.find('.dashboard-grid')
      expect(gridElement.attributes('role')).toBe('grid')
      expect(gridElement.attributes('aria-label')).toBe('Dashboard grid')

      const gridItems = wrapper.findAll('.grid-item')
      gridItems.forEach(item => {
        expect(item.attributes('role')).toBe('gridcell')
        expect(item.attributes('aria-label')).toContain('Card:')
      })
    })

    it('supports keyboard navigation with card focus', async () => {
      wrapper = mount(DashboardGrid, {
        props: { 
          cards: mockCards,
          draggable: true
        }
      })

      const firstGridItem = wrapper.find('.grid-item')
      expect(firstGridItem.attributes('tabindex')).toBe('0')

      await firstGridItem.trigger('keydown', { key: 'Enter' })
      expect(wrapper.emitted('card-activate')).toBeTruthy()

      await firstGridItem.trigger('keydown', { key: ' ' })
      expect(wrapper.emitted('card-activate')).toHaveLength(2)
    })
  })

  describe('Error Handling Integration', () => {
    it('handles card component errors gracefully', async () => {
      const errorSpy = vi.fn()
      
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      wrapper.vm.handleCardError(new Error('Card error'), mockCards[0])
      
      expect(wrapper.emitted('card-error')).toBeTruthy()
      expect(wrapper.emitted('card-error')[0]).toEqual([
        new Error('Card error'),
        mockCards[0]
      ])
    })

    it('continues functioning when individual cards fail', () => {
      const cardsWithError = [
        ...mockCards,
        {
          id: 'error-card',
          component: 'ErrorCard', // Non-existent component
          title: 'Error Card',
          gridArea: { row: 3, column: 1, rowSpan: 1, columnSpan: 1 }
        }
      ]

      wrapper = mount(DashboardGrid, {
        props: { cards: cardsWithError }
      })

      // Grid should still render successfully
      expect(wrapper.exists()).toBe(true)
      expect(wrapper.findAll('.grid-item')).toHaveLength(4)
    })
  })
})
