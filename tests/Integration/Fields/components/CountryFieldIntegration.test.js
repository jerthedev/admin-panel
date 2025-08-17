import { describe, it, expect, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CountryField from '@/components/Fields/CountryField.vue'
import { createMockField } from '../../../helpers.js'

// Mock the admin store
const mockAdminStore = { isDarkTheme: false }
vi.mock('@/stores/admin', () => ({ useAdminStore: () => mockAdminStore }))

// Mock BaseField to assert render container
vi.mock('@/components/Fields/BaseField.vue', () => ({ default: { name: 'BaseField', template: '<div data-testid="base-field"><slot /></div>' } }))

// Mock SelectField used internally
vi.mock('@/components/Fields/SelectField.vue', () => ({ default: { name: 'SelectField', template: '<div data-testid="select-field"></div>' } }))

describe('CountryField Integration', () => {
  let wrapper

  afterEach(() => { if (wrapper) wrapper.unmount() })

  it('receives PHP meta (options/searchable) and renders correctly', () => {
    const phpField = createMockField({
      name: 'Country',
      attribute: 'country_code',
      component: 'CountryField',
      searchable: true,
      options: {
        US: 'United States',
        CA: 'Canada',
        GB: 'United Kingdom',
        FR: 'France',
      }
    })

    wrapper = mount(CountryField, {
      props: { field: phpField, modelValue: 'US' }
    })

    expect(wrapper.find('[data-testid="base-field"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="select-field"]').exists()).toBe(true)
  })

  it('works with minimal PHP config and null value', () => {
    const phpField = createMockField({ component: 'CountryField' })

    wrapper = mount(CountryField, { props: { field: phpField, modelValue: null } })
    expect(wrapper.find('[data-testid="select-field"]').exists()).toBe(true)
  })
})

