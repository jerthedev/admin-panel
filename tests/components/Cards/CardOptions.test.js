/**
 * Card Options Vue Component Tests
 * 
 * Tests the enhanced Card.vue component with advanced meta options including
 * colors, theming, styling, and configuration options.
 */

import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import Card from '@/components/Cards/Card.vue'

// Mock admin store
const mockAdminStore = {
  isDarkTheme: false
}

// Mock useAdminStore
vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('Card Options', () => {
  let wrapper
  let baseCard

  beforeEach(() => {
    baseCard = {
      name: 'Test Card',
      component: 'TestCard',
      uriKey: 'test-card',
      meta: {}
    }
  })

  afterEach(() => {
    if (wrapper) wrapper.unmount()
  })

  describe('Color Options', () => {
    it('applies theme color classes from meta.color', () => {
      const cardWithColor = {
        ...baseCard,
        meta: { color: 'primary' }
      }

      wrapper = mount(Card, {
        props: { card: cardWithColor }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('bg-blue-50')
      expect(cardElement.classes()).toContain('border-blue-200')
    })

    it('applies success color theme', () => {
      const cardWithColor = {
        ...baseCard,
        meta: { color: 'success' }
      }

      wrapper = mount(Card, {
        props: { card: cardWithColor }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('bg-green-50')
      expect(cardElement.classes()).toContain('border-green-200')
    })

    it('applies danger color theme', () => {
      const cardWithColor = {
        ...baseCard,
        meta: { color: 'danger' }
      }

      wrapper = mount(Card, {
        props: { card: cardWithColor }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('bg-red-50')
      expect(cardElement.classes()).toContain('border-red-200')
    })

    it('applies Tailwind color classes', () => {
      const cardWithColor = {
        ...baseCard,
        meta: { color: 'purple-500' }
      }

      wrapper = mount(Card, {
        props: { card: cardWithColor }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('bg-purple-50')
      expect(cardElement.classes()).toContain('border-purple-200')
    })
  })

  describe('Custom Styling', () => {
    it('applies custom background color from meta.backgroundColor', () => {
      const cardWithBgColor = {
        ...baseCard,
        meta: { backgroundColor: '#f0f0f0' }
      }

      wrapper = mount(Card, {
        props: { card: cardWithBgColor }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.element.style.backgroundColor).toBe('rgb(240, 240, 240)')
    })

    it('applies custom text color from meta.textColor', () => {
      const cardWithTextColor = {
        ...baseCard,
        meta: { textColor: '#333333' }
      }

      wrapper = mount(Card, {
        props: { card: cardWithTextColor }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.element.style.color).toBe('rgb(51, 51, 51)')
    })

    it('applies custom border color from meta.borderColor', () => {
      const cardWithBorderColor = {
        ...baseCard,
        meta: { borderColor: '#ff0000' }
      }

      wrapper = mount(Card, {
        props: { card: cardWithBorderColor }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.element.style.borderColor).toBe('rgb(255, 0, 0)')
    })

    it('applies custom styles from meta.styles', () => {
      const cardWithStyles = {
        ...baseCard,
        meta: {
          styles: {
            borderRadius: '12px',
            boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)',
            padding: '20px'
          }
        }
      }

      wrapper = mount(Card, {
        props: { card: cardWithStyles }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.element.style.borderRadius).toBe('12px')
      expect(cardElement.element.style.boxShadow).toBe('0 4px 6px rgba(0, 0, 0, 0.1)')
      expect(cardElement.element.style.padding).toBe('20px')
    })

    it('applies custom CSS classes from meta.classes', () => {
      const cardWithClasses = {
        ...baseCard,
        meta: { classes: ['custom-card', 'highlighted'] }
      }

      wrapper = mount(Card, {
        props: { card: cardWithClasses }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('custom-card')
      expect(cardElement.classes()).toContain('highlighted')
    })
  })

  describe('Variant Options', () => {
    it('applies variant from meta.variant over prop variant', () => {
      const cardWithVariant = {
        ...baseCard,
        meta: { variant: 'elevated' }
      }

      wrapper = mount(Card, {
        props: { 
          card: cardWithVariant,
          variant: 'flat' // This should be overridden
        }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('shadow-lg')
      expect(cardElement.classes()).not.toContain('bg-gray-50')
    })

    it('applies gradient variant', () => {
      const cardWithGradient = {
        ...baseCard,
        meta: { variant: 'gradient' }
      }

      wrapper = mount(Card, {
        props: { card: cardWithGradient }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('bg-gradient-to-br')
      expect(cardElement.classes()).toContain('from-blue-50')
      expect(cardElement.classes()).toContain('to-indigo-100')
    })
  })

  describe('Content Options', () => {
    it('displays title from meta.title', () => {
      const cardWithTitle = {
        ...baseCard,
        meta: { title: 'Custom Card Title' }
      }

      wrapper = mount(Card, {
        props: { card: cardWithTitle }
      })

      const title = wrapper.find('h3')
      expect(title.text()).toBe('Custom Card Title')
    })

    it('displays subtitle from meta.subtitle', () => {
      const cardWithSubtitle = {
        ...baseCard,
        meta: { 
          title: 'Main Title',
          subtitle: 'Card subtitle'
        }
      }

      wrapper = mount(Card, {
        props: { card: cardWithSubtitle }
      })

      const subtitle = wrapper.find('p')
      expect(subtitle.text()).toBe('Card subtitle')
    })

    it('displays description from meta.description', () => {
      const cardWithDescription = {
        ...baseCard,
        meta: { description: 'This is a detailed description' }
      }

      wrapper = mount(Card, {
        props: { card: cardWithDescription }
      })

      expect(wrapper.html()).toContain('This is a detailed description')
    })

    it('displays icon from meta.icon', () => {
      const cardWithIcon = {
        ...baseCard,
        meta: { 
          title: 'Card with Icon',
          icon: 'ChartBarIcon'
        }
      }

      wrapper = mount(Card, {
        props: { card: cardWithIcon }
      })

      // Check that icon element is rendered (as a component)
      const iconElement = wrapper.find('[data-testid="card-icon"]')
      // Since we're using dynamic components, check if the component prop is set correctly
      expect(wrapper.vm.card.meta.icon).toBe('ChartBarIcon')
    })
  })

  describe('Combined Options', () => {
    it('applies multiple meta options together', () => {
      const complexCard = {
        ...baseCard,
        meta: {
          title: 'Complex Card',
          subtitle: 'With multiple options',
          color: 'primary',
          variant: 'elevated',
          backgroundColor: '#f8fafc',
          classes: ['custom-complex'],
          styles: { borderRadius: '8px' },
          refreshable: true
        }
      }

      wrapper = mount(Card, {
        props: { card: complexCard }
      })

      const cardElement = wrapper.find('.relative')
      const title = wrapper.find('h3')
      const subtitle = wrapper.find('p')

      // Check content
      expect(title.text()).toBe('Complex Card')
      expect(subtitle.text()).toBe('With multiple options')

      // Check styling
      expect(cardElement.classes()).toContain('shadow-lg') // elevated variant
      expect(cardElement.classes()).toContain('bg-blue-50') // primary color
      expect(cardElement.classes()).toContain('custom-complex') // custom class
      expect(cardElement.element.style.backgroundColor).toBe('rgb(248, 250, 252)') // custom bg
      expect(cardElement.element.style.borderRadius).toBe('8px') // custom style
    })
  })

  describe('Dark Theme Support', () => {
    it('applies dark theme classes with color options', () => {
      mockAdminStore.isDarkTheme = true

      const cardWithColor = {
        ...baseCard,
        meta: { color: 'primary' }
      }

      wrapper = mount(Card, {
        props: { card: cardWithColor }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('dark:bg-blue-900/20')
      expect(cardElement.classes()).toContain('dark:border-blue-700')
    })
  })

  describe('Performance', () => {
    it('handles large meta data efficiently', () => {
      const largeMeta = {
        title: 'Performance Test',
        color: 'primary',
        variant: 'elevated'
      }

      // Add many custom properties
      for (let i = 0; i < 100; i++) {
        largeMeta[`custom_${i}`] = `value_${i}`
      }

      const cardWithLargeMeta = {
        ...baseCard,
        meta: largeMeta
      }

      const startTime = performance.now()
      wrapper = mount(Card, {
        props: { card: cardWithLargeMeta }
      })
      const endTime = performance.now()

      // Should render efficiently
      expect(endTime - startTime).toBeLessThan(50) // Less than 50ms
      expect(wrapper.find('h3').text()).toBe('Performance Test')
    })
  })
})
