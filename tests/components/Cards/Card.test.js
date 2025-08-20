import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import Card from '@/components/Cards/Card.vue'
import { createMockCard } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('Card', () => {
  let wrapper
  let mockCard

  beforeEach(() => {
    mockCard = createMockCard({
      name: 'Test Card',
      component: 'TestCard',
      uriKey: 'test-card',
      meta: {
        title: 'Test Card Title',
        description: 'Test card description',
        icon: 'test-icon'
      }
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders the card wrapper', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard
        }
      })

      expect(wrapper.find('.relative.overflow-hidden').exists()).toBe(true)
    })

    it('renders the card title from meta.title', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard
        }
      })

      const title = wrapper.find('h3')
      expect(title.exists()).toBe(true)
      expect(title.text()).toBe('Test Card Title')
    })

    it('falls back to card.name when meta.title is not provided', () => {
      const cardWithoutMetaTitle = {
        ...mockCard,
        meta: { ...mockCard.meta, title: undefined }
      }

      wrapper = mount(Card, {
        props: {
          card: cardWithoutMetaTitle
        }
      })

      const title = wrapper.find('h3')
      expect(title.exists()).toBe(true)
      expect(title.text()).toBe('Test Card')
    })

    it('renders subtitle when provided in meta', () => {
      const cardWithSubtitle = {
        ...mockCard,
        meta: { ...mockCard.meta, subtitle: 'Test subtitle' }
      }

      wrapper = mount(Card, {
        props: {
          card: cardWithSubtitle
        }
      })

      const subtitle = wrapper.find('p')
      expect(subtitle.exists()).toBe(true)
      expect(subtitle.text()).toBe('Test subtitle')
    })

    it('renders description when card meta contains description', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard
        }
      })

      // Verify the card meta is properly set
      expect(wrapper.vm.card.meta.description).toBe('Test card description')

      // The description should be accessible in the component
      expect(wrapper.html()).toContain('Test card description')
    })
  })

  describe('Props Validation', () => {
    it('accepts valid variant prop values', () => {
      const validVariants = ['default', 'bordered', 'elevated', 'flat']
      
      validVariants.forEach(variant => {
        wrapper = mount(Card, {
          props: {
            card: mockCard,
            variant
          }
        })
        expect(wrapper.props('variant')).toBe(variant)
        wrapper.unmount()
      })
    })

    it('accepts valid padding prop values', () => {
      const validPadding = ['none', 'sm', 'md', 'lg', 'xl']
      
      validPadding.forEach(padding => {
        wrapper = mount(Card, {
          props: {
            card: mockCard,
            padding
          }
        })
        expect(wrapper.props('padding')).toBe(padding)
        wrapper.unmount()
      })
    })

    it('accepts valid rounded prop values', () => {
      const validRounded = ['none', 'sm', 'md', 'lg', 'xl', 'full']
      
      validRounded.forEach(rounded => {
        wrapper = mount(Card, {
          props: {
            card: mockCard,
            rounded
          }
        })
        expect(wrapper.props('rounded')).toBe(rounded)
        wrapper.unmount()
      })
    })

    it('validates card prop structure', () => {
      const invalidCard = { name: 'Test' } // Missing required properties

      // This would normally throw a validation error in Vue
      // We test the validator function directly
      const cardProp = Card.props.card
      expect(cardProp.validator(invalidCard)).toBe(false)
      expect(cardProp.validator(mockCard)).toBe(true)
    })
  })

  describe('Styling and Variants', () => {
    it('applies default variant classes', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard,
          variant: 'default'
        }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('bg-white')
      expect(cardElement.classes()).toContain('shadow-sm')
      expect(cardElement.classes()).toContain('border')
    })

    it('applies bordered variant classes', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard,
          variant: 'bordered'
        }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('border-2')
    })

    it('applies hoverable classes when hoverable is true', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard,
          hoverable: true
        }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('hover:shadow-md')
    })

    it('applies clickable classes when clickable is true', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard,
          clickable: true
        }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('cursor-pointer')
      expect(cardElement.classes()).toContain('hover:shadow-md')
    })

    it('applies loading opacity when loading is true', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard,
          loading: true
        }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('opacity-75')
    })
  })

  describe('Interactive Features', () => {
    it('emits click event when clickable and clicked', async () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard,
          clickable: true
        }
      })

      await wrapper.trigger('click')

      expect(wrapper.emitted('click')).toBeTruthy()
      expect(wrapper.emitted('click')[0]).toEqual([expect.any(Object), mockCard])
    })

    it('does not emit click event when not clickable', async () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard,
          clickable: false
        }
      })

      await wrapper.trigger('click')

      expect(wrapper.emitted('click')).toBeFalsy()
    })

    it('does not emit click event when loading', async () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard,
          clickable: true,
          loading: true
        }
      })

      await wrapper.trigger('click')

      expect(wrapper.emitted('click')).toBeFalsy()
    })

    it('exposes refresh method', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard,
          refreshable: true
        }
      })

      expect(wrapper.vm.refresh).toBeDefined()
      expect(typeof wrapper.vm.refresh).toBe('function')
    })
  })

  describe('Loading State', () => {
    it('shows loading overlay when loading is true', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard,
          loading: true
        }
      })

      const loadingOverlay = wrapper.find('.absolute.inset-0')
      expect(loadingOverlay.exists()).toBe(true)
      
      const spinner = wrapper.find('.animate-spin')
      expect(spinner.exists()).toBe(true)
    })

    it('does not show loading overlay when loading is false', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard,
          loading: false
        }
      })

      const loadingOverlay = wrapper.find('.absolute.inset-0')
      expect(loadingOverlay.exists()).toBe(false)
    })
  })

  describe('Slot Content', () => {
    it('renders default slot content', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard
        },
        slots: {
          default: '<div class="test-content">Custom content</div>'
        }
      })

      const customContent = wrapper.find('.test-content')
      expect(customContent.exists()).toBe(true)
      expect(customContent.text()).toBe('Custom content')
    })

    it('renders header slot content', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard
        },
        slots: {
          header: '<div class="test-header">Custom header</div>'
        }
      })

      const customHeader = wrapper.find('.test-header')
      expect(customHeader.exists()).toBe(true)
      expect(customHeader.text()).toBe('Custom header')
    })

    it('renders footer slot content', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard
        },
        slots: {
          footer: '<div class="test-footer">Custom footer</div>'
        }
      })

      const customFooter = wrapper.find('.test-footer')
      expect(customFooter.exists()).toBe(true)
      expect(customFooter.text()).toBe('Custom footer')
    })

    it('renders actions slot content', () => {
      wrapper = mount(Card, {
        props: {
          card: mockCard
        },
        slots: {
          actions: '<button class="test-action">Action</button>'
        }
      })

      const actionButton = wrapper.find('.test-action')
      expect(actionButton.exists()).toBe(true)
      expect(actionButton.text()).toBe('Action')
    })
  })

  describe('Dark Theme Support', () => {
    it('applies dark theme classes when dark theme is active', async () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mount(Card, {
        props: {
          card: mockCard
        }
      })

      const cardElement = wrapper.find('.relative')
      expect(cardElement.classes()).toContain('dark:bg-gray-800')
    })
  })
})
