import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import CurrencyField from '@/components/Fields/CurrencyField.vue'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false,
  fullscreenMode: false,
  setTheme: vi.fn(),
  toggleFullscreen: vi.fn()
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock BaseField component
vi.mock('@/components/Fields/BaseField.vue', () => ({
  default: {
    name: 'BaseField',
    template: '<div><slot /></div>',
    props: ['field', 'modelValue', 'errors', 'disabled', 'readonly', 'size']
  }
}))

describe('CurrencyField Integration', () => {
  let wrapper

  beforeEach(() => {
    vi.clearAllMocks()
    mockAdminStore.isDarkTheme = false
  })

  const createWrapper = (fieldMeta = {}, modelValue = null, props = {}) => {
    const defaultField = {
      name: 'Price',
      attribute: 'price',
      component: 'CurrencyField',
      currency: 'USD',
      locale: 'en-US',
      symbol: '$',
      minValue: null,
      maxValue: null,
      step: 0.01,
      asMinorUnits: false,
      ...fieldMeta
    }

    return mount(CurrencyField, {
      props: {
        field: defaultField,
        modelValue,
        errors: {},
        disabled: false,
        readonly: false,
        size: 'default',
        ...props
      },
      global: {
        stubs: {
          BaseField: {
            template: '<div><slot /></div>'
          }
        }
      }
    })
  }

  describe('Nova API Compatibility', () => {
    it('renders with Nova-compatible field meta', () => {
      const field = {
        currency: 'EUR',
        locale: 'fr',
        symbol: '€',
        minValue: 0,
        maxValue: 1000,
        step: 0.05,
        asMinorUnits: false
      }

      wrapper = createWrapper(field)
      const input = wrapper.find('input[type="number"]')

      expect(input.exists()).toBe(true)
      expect(input.attributes('min')).toBe('0')
      expect(input.attributes('max')).toBe('1000')
      expect(input.attributes('step')).toBe('0.05')
    })

    it('handles asMinorUnits meta correctly', () => {
      const field = {
        currency: 'USD',
        step: 1, // Nova sets step to 1 for minor units
        asMinorUnits: true
      }

      wrapper = createWrapper(field)
      const input = wrapper.find('input[type="number"]')

      expect(input.attributes('step')).toBe('1')
    })

    it('displays currency symbol from meta', () => {
      const field = {
        currency: 'GBP',
        symbol: '£'
      }

      wrapper = createWrapper(field)
      const symbolElement = wrapper.find('.text-gray-500')

      expect(symbolElement.exists()).toBe(true)
      expect(symbolElement.text()).toBe('£')
    })
  })

  describe('Input Handling', () => {
    it('emits numeric values correctly', async () => {
      wrapper = createWrapper()
      const input = wrapper.find('input')

      await input.setValue('123.45')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(123.45)
    })

    it('handles empty input correctly', async () => {
      wrapper = createWrapper({}, '123.45')
      const input = wrapper.find('input')

      await input.setValue('')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
    })

    it('handles invalid input correctly', async () => {
      wrapper = createWrapper()
      const input = wrapper.find('input')

      await input.setValue('invalid')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      // HTML5 number input filters out invalid characters, so invalid text becomes null
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
    })
  })

  describe('Display Formatting', () => {
    it('formats display value using Intl.NumberFormat', () => {
      const field = {
        currency: 'EUR',
        locale: 'de-DE',
        symbol: '€'
      }

      wrapper = createWrapper(field, 1234.56)

      // The formattedDisplay computed should use Intl.NumberFormat
      const vm = wrapper.vm
      expect(vm.formattedDisplay).toBeTruthy()
      // Note: Exact format depends on browser Intl implementation
    })

    it('falls back to simple formatting when Intl unavailable', () => {
      // Mock Intl as undefined
      const originalIntl = global.Intl
      global.Intl = undefined

      const field = {
        currency: 'USD',
        symbol: '$'
      }

      wrapper = createWrapper(field, 123.45)
      const vm = wrapper.vm

      expect(vm.formattedDisplay).toBe('$123.45')

      // Restore Intl
      global.Intl = originalIntl
    })
  })

  describe('Symbol Positioning', () => {
    it('positions USD symbol on left', () => {
      const field = {
        currency: 'USD',
        symbol: '$'
      }

      wrapper = createWrapper(field)
      const vm = wrapper.vm

      expect(vm.symbolPosition).toBe('left')
      expect(wrapper.find('.pl-8').exists()).toBe(true)
    })

    it('positions EUR symbol on right', () => {
      const field = {
        currency: 'EUR',
        symbol: '€'
      }

      wrapper = createWrapper(field)
      const vm = wrapper.vm

      expect(vm.symbolPosition).toBe('right')
      expect(wrapper.find('.pr-12').exists()).toBe(true)
    })
  })

  describe('Placeholder Generation', () => {
    it('generates placeholder with symbol', () => {
      const field = {
        symbol: '$'
      }

      wrapper = createWrapper(field)
      const input = wrapper.find('input')

      expect(input.attributes('placeholder')).toBe('$0.00')
    })

    it('generates placeholder without symbol', () => {
      const field = {
        symbol: ''
      }

      wrapper = createWrapper(field)
      const input = wrapper.find('input')

      expect(input.attributes('placeholder')).toBe('0.00')
    })
  })

  describe('Event Handling', () => {
    it('emits focus event', async () => {
      wrapper = createWrapper()
      const input = wrapper.find('input')

      await input.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = createWrapper()
      const input = wrapper.find('input')

      await input.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('emits change event', async () => {
      wrapper = createWrapper()
      const input = wrapper.find('input')

      await input.trigger('change')

      expect(wrapper.emitted('change')).toBeTruthy()
    })
  })

  describe('Accessibility', () => {
    it('has proper input attributes', () => {
      wrapper = createWrapper()
      const input = wrapper.find('input')

      expect(input.attributes('type')).toBe('number')
      expect(input.attributes('id')).toMatch(/^currency-field-price-/)
    })

    it('respects disabled state', () => {
      wrapper = createWrapper({}, null, { disabled: true })
      const input = wrapper.find('input')

      expect(input.attributes('disabled')).toBeDefined()
    })

    it('respects readonly state', () => {
      wrapper = createWrapper({}, null, { readonly: true })
      const input = wrapper.find('input')

      expect(input.attributes('readonly')).toBeDefined()
    })
  })

  describe('Dark Theme Support', () => {
    it('applies dark theme classes when enabled', async () => {
      mockAdminStore.isDarkTheme = true
      wrapper = createWrapper()

      expect(wrapper.find('.admin-input-dark').exists()).toBe(true)
      expect(wrapper.find('.text-gray-400').exists()).toBe(true)
    })

    it('does not apply dark theme classes when disabled', async () => {
      mockAdminStore.isDarkTheme = false
      wrapper = createWrapper()

      expect(wrapper.find('.admin-input-dark').exists()).toBe(false)
      expect(wrapper.find('.text-gray-500').exists()).toBe(true)
    })
  })
})
