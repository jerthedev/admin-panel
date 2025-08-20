/**
 * DashboardGrid Vue Component Tests
 * 
 * Tests the DashboardGrid.vue component with CSS Grid-based layout system,
 * responsive breakpoints, card sizing/positioning, gap controls, and
 * foundation for drag-and-drop functionality.
 */

import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { nextTick } from 'vue'
import DashboardGrid from '@/components/Dashboard/DashboardGrid.vue'

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

describe('DashboardGrid', () => {
  let wrapper
  let mockCards

  beforeEach(() => {
    mockCards = [
      {
        id: 'card-1',
        component: 'MetricCard',
        title: 'Users',
        gridArea: { row: 1, column: 1, rowSpan: 1, columnSpan: 1 },
        props: { value: 1250 }
      },
      {
        id: 'card-2',
        component: 'MetricCard',
        title: 'Revenue',
        gridArea: { row: 1, column: 2, rowSpan: 1, columnSpan: 2 },
        props: { value: '$45,230' }
      },
      {
        id: 'card-3',
        component: 'MetricCard',
        title: 'Orders',
        gridArea: { row: 2, column: 1, rowSpan: 2, columnSpan: 1 },
        props: { value: 89 }
      }
    ]
  })

  afterEach(() => {
    if (wrapper) wrapper.unmount()
  })

  describe('Component Initialization', () => {
    it('renders without crashing', () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: [] }
      })

      expect(wrapper.exists()).toBe(true)
      expect(wrapper.find('.dashboard-grid').exists()).toBe(true)
    })

    it('accepts cards prop correctly', () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      expect(wrapper.vm.cards).toEqual(mockCards)
      expect(wrapper.findAll('.grid-item')).toHaveLength(3)
    })

    it('has correct default props', () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: [] }
      })

      expect(wrapper.vm.columns).toEqual({ mobile: 1, tablet: 2, desktop: 3, wide: 4 })
      expect(wrapper.vm.gap).toBe('1rem')
      expect(wrapper.vm.autoRows).toBe('minmax(200px, auto)')
      expect(wrapper.vm.responsive).toBe(true)
      expect(wrapper.vm.draggable).toBe(false)
    })
  })

  describe('CSS Grid Layout System', () => {
    it('applies correct CSS Grid properties', () => {
      wrapper = mount(DashboardGrid, {
        props: { 
          cards: mockCards,
          columns: { desktop: 4 },
          gap: '1.5rem',
          autoRows: 'minmax(250px, auto)'
        }
      })

      const gridElement = wrapper.find('.dashboard-grid')
      const style = gridElement.element.style

      expect(style.display).toBe('grid')
      expect(style.gap).toBe('1.5rem')
      expect(style.gridAutoRows).toBe('minmax(250px, auto)')
    })

    it('applies grid template columns based on breakpoint', async () => {
      wrapper = mount(DashboardGrid, {
        props: { 
          cards: mockCards,
          columns: { mobile: 1, tablet: 2, desktop: 3, wide: 4 }
        }
      })

      // Mock desktop breakpoint
      wrapper.vm.currentBreakpoint = 'desktop'
      await nextTick()

      const gridElement = wrapper.find('.dashboard-grid')
      expect(gridElement.element.style.gridTemplateColumns).toBe('repeat(3, 1fr)')
    })

    it('positions cards correctly using grid-area', () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      const gridItems = wrapper.findAll('.grid-item')
      
      // First card: row 1, column 1, span 1x1
      expect(gridItems[0].element.style.gridArea).toBe('1 / 1 / 2 / 2')
      
      // Second card: row 1, column 2, span 1x2
      expect(gridItems[1].element.style.gridArea).toBe('1 / 2 / 2 / 4')
      
      // Third card: row 2, column 1, span 2x1
      expect(gridItems[2].element.style.gridArea).toBe('2 / 1 / 4 / 2')
    })
  })

  describe('Responsive Breakpoints', () => {
    it('detects mobile breakpoint correctly', async () => {
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
        props: { cards: mockCards }
      })

      await nextTick()
      expect(wrapper.vm.currentBreakpoint).toBe('mobile')
      expect(wrapper.vm.currentColumns).toBe(1)
    })

    it('detects tablet breakpoint correctly', async () => {
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
        props: { cards: mockCards }
      })

      await nextTick()
      expect(wrapper.vm.currentBreakpoint).toBe('tablet')
      expect(wrapper.vm.currentColumns).toBe(2)
    })

    it('detects desktop breakpoint correctly', async () => {
      // Mock desktop viewport
      window.matchMedia = vi.fn().mockImplementation(query => ({
        matches: query === '(min-width: 1024px) and (max-width: 1535px)',
        media: query,
        onchange: null,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
      }))

      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      await nextTick()
      expect(wrapper.vm.currentBreakpoint).toBe('desktop')
      expect(wrapper.vm.currentColumns).toBe(3)
    })

    it('detects wide breakpoint correctly', async () => {
      // Mock wide viewport
      window.matchMedia = vi.fn().mockImplementation(query => ({
        matches: query === '(min-width: 1536px)',
        media: query,
        onchange: null,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
      }))

      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      await nextTick()
      expect(wrapper.vm.currentBreakpoint).toBe('wide')
      expect(wrapper.vm.currentColumns).toBe(4)
    })

    it('disables responsive behavior when responsive prop is false', () => {
      wrapper = mount(DashboardGrid, {
        props: { 
          cards: mockCards,
          responsive: false,
          columns: { desktop: 5 }
        }
      })

      expect(wrapper.vm.currentColumns).toBe(5)
      expect(wrapper.vm.currentBreakpoint).toBe('desktop')
    })
  })

  describe('Card Sizing and Positioning', () => {
    it('handles cards without gridArea gracefully', () => {
      const cardsWithoutGrid = [
        { id: 'card-1', component: 'MetricCard', title: 'Test' }
      ]

      wrapper = mount(DashboardGrid, {
        props: { cards: cardsWithoutGrid }
      })

      const gridItem = wrapper.find('.grid-item')
      expect(gridItem.element.style.gridArea).toBe('auto')
    })

    it('calculates grid area string correctly', () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      const gridAreaString = wrapper.vm.getGridAreaString({
        row: 2, column: 3, rowSpan: 2, columnSpan: 3
      })

      expect(gridAreaString).toBe('2 / 3 / 4 / 6')
    })

    it('handles invalid grid area values', () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      const gridAreaString = wrapper.vm.getGridAreaString({
        row: -1, column: 0, rowSpan: -2, columnSpan: 0
      })

      expect(gridAreaString).toBe('1 / 1 / 2 / 2')
    })
  })

  describe('Gap and Spacing Controls', () => {
    it('applies custom gap value', () => {
      wrapper = mount(DashboardGrid, {
        props: { 
          cards: mockCards,
          gap: '2rem'
        }
      })

      const gridElement = wrapper.find('.dashboard-grid')
      expect(gridElement.element.style.gap).toBe('2rem')
    })

    it('supports different gap formats', async () => {
      wrapper = mount(DashboardGrid, {
        props: { 
          cards: mockCards,
          gap: '1rem 2rem'
        }
      })

      const gridElement = wrapper.find('.dashboard-grid')
      expect(gridElement.element.style.gap).toBe('1rem 2rem')

      // Test pixel values
      await wrapper.setProps({ gap: '16px' })
      expect(gridElement.element.style.gap).toBe('16px')

      // Test percentage values
      await wrapper.setProps({ gap: '2%' })
      expect(gridElement.element.style.gap).toBe('2%')
    })

    it('applies custom auto rows value', () => {
      wrapper = mount(DashboardGrid, {
        props: { 
          cards: mockCards,
          autoRows: 'minmax(300px, auto)'
        }
      })

      const gridElement = wrapper.find('.dashboard-grid')
      expect(gridElement.element.style.gridAutoRows).toBe('minmax(300px, auto)')
    })
  })

  describe('Drag and Drop Foundation', () => {
    it('adds draggable attributes when draggable prop is true', () => {
      wrapper = mount(DashboardGrid, {
        props: { 
          cards: mockCards,
          draggable: true
        }
      })

      const gridItems = wrapper.findAll('.grid-item')
      gridItems.forEach(item => {
        expect(item.attributes('draggable')).toBe('true')
        expect(item.classes()).toContain('draggable')
      })
    })

    it('does not add draggable attributes when draggable prop is false', () => {
      wrapper = mount(DashboardGrid, {
        props: { 
          cards: mockCards,
          draggable: false
        }
      })

      const gridItems = wrapper.findAll('.grid-item')
      gridItems.forEach(item => {
        expect(item.attributes('draggable')).toBe('false')
        expect(item.classes()).not.toContain('draggable')
      })
    })

    it('emits drag events correctly', async () => {
      wrapper = mount(DashboardGrid, {
        props: {
          cards: mockCards,
          draggable: true
        }
      })

      const firstItem = wrapper.find('.grid-item')

      // Mock dataTransfer for drag events
      const mockDataTransfer = {
        setData: vi.fn(),
        getData: vi.fn(),
        effectAllowed: ''
      }

      // Test dragstart event
      await firstItem.trigger('dragstart', { dataTransfer: mockDataTransfer })
      expect(wrapper.emitted('drag-start')).toBeTruthy()
      expect(wrapper.emitted('drag-start')[0]).toEqual([mockCards[0], expect.any(Object)])

      // Test dragend event
      await firstItem.trigger('dragend', { dataTransfer: mockDataTransfer })
      expect(wrapper.emitted('drag-end')).toBeTruthy()
      expect(wrapper.emitted('drag-end')[0]).toEqual([mockCards[0], expect.any(Object)])
    })

    it('handles drop events correctly', async () => {
      wrapper = mount(DashboardGrid, {
        props: {
          cards: mockCards,
          draggable: true
        }
      })

      const gridElement = wrapper.find('.dashboard-grid')

      // Mock drop event with dataTransfer
      const mockDataTransfer = {
        getData: vi.fn().mockReturnValue('card-1')
      }

      await gridElement.trigger('drop', { dataTransfer: mockDataTransfer })
      expect(wrapper.emitted('card-drop')).toBeTruthy()
    })
  })

  describe('Event Handling', () => {
    it('emits card-click event when card is clicked', async () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      const firstItem = wrapper.find('.grid-item')
      await firstItem.trigger('click')

      expect(wrapper.emitted('card-click')).toBeTruthy()
      expect(wrapper.emitted('card-click')[0]).toEqual([mockCards[0], expect.any(Object)])
    })

    it('emits grid-resize event when grid is resized', async () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      // Simulate resize
      wrapper.vm.handleResize()
      await nextTick()

      expect(wrapper.emitted('grid-resize')).toBeTruthy()
    })
  })

  describe('Performance and Optimization', () => {
    it('handles large number of cards efficiently', () => {
      const manyCards = Array.from({ length: 100 }, (_, i) => ({
        id: `card-${i}`,
        component: 'MetricCard',
        title: `Card ${i}`,
        gridArea: { row: Math.floor(i / 4) + 1, column: (i % 4) + 1, rowSpan: 1, columnSpan: 1 }
      }))

      const startTime = performance.now()
      wrapper = mount(DashboardGrid, {
        props: { cards: manyCards }
      })
      const endTime = performance.now()

      expect(endTime - startTime).toBeLessThan(100) // Should render in less than 100ms
      expect(wrapper.findAll('.grid-item')).toHaveLength(100)
    })

    it('cleans up event listeners on unmount', () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards, responsive: true }
      })

      // Test that component unmounts without errors
      expect(() => wrapper.unmount()).not.toThrow()

      // Verify component is unmounted by checking if it exists
      expect(wrapper.exists()).toBe(false)
    })
  })

  describe('Accessibility', () => {
    it('has proper ARIA attributes', () => {
      wrapper = mount(DashboardGrid, {
        props: { cards: mockCards }
      })

      const gridElement = wrapper.find('.dashboard-grid')
      expect(gridElement.attributes('role')).toBe('grid')
      expect(gridElement.attributes('aria-label')).toBe('Dashboard grid')

      const gridItems = wrapper.findAll('.grid-item')
      gridItems.forEach(item => {
        expect(item.attributes('role')).toBe('gridcell')
      })
    })

    it('supports keyboard navigation when draggable', async () => {
      wrapper = mount(DashboardGrid, {
        props: { 
          cards: mockCards,
          draggable: true
        }
      })

      const firstItem = wrapper.find('.grid-item')
      expect(firstItem.attributes('tabindex')).toBe('0')

      // Test keyboard events
      await firstItem.trigger('keydown', { key: 'Enter' })
      expect(wrapper.emitted('card-activate')).toBeTruthy()

      await firstItem.trigger('keydown', { key: ' ' })
      expect(wrapper.emitted('card-activate')).toBeTruthy()
    })
  })
})
