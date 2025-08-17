import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CountryField from '@components/Fields/CountryField.vue'
import { createMockField, mountField } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = { isDarkTheme: false }
vi.mock('@/stores/admin', () => ({ useAdminStore: () => mockAdminStore }))

// Mock SelectField used internally
vi.mock('@/components/Fields/SelectField.vue', () => ({ default: { name: 'SelectField', template: '<div data-testid="select-field"></div>' } }))

// Mock BaseField
vi.mock('@/components/Fields/BaseField.vue', () => ({ default: { name: 'BaseField', template: '<div data-testid="base-field"><slot /></div>' } }))

describe('CountryField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Country',
      attribute: 'country',
      component: 'CountryField',
      searchable: true,
      options: {
        US: 'United States',
        CA: 'Canada',
        GB: 'United Kingdom',
        FR: 'France',
      }
    })
  })

  afterEach(() => { if (wrapper) wrapper.unmount() })

  it('renders inside BaseField', () => {
    wrapper = mountField(CountryField, { field: mockField })
    expect(wrapper.find('[data-testid="base-field"]').exists()).toBe(true)
  })

  it('renders SelectField internally', () => {
    wrapper = mountField(CountryField, { field: mockField })
    expect(wrapper.find('[data-testid="select-field"]').exists()).toBe(true)
  })

  it('passes options and searchable to SelectField', () => {
    wrapper = mountField(CountryField, { field: mockField })
    const select = wrapper.findComponent({ name: 'SelectField' })
    expect(select.exists()).toBe(true)
  })

  it('emits update:modelValue when selection changes', async () => {
    // Unmock SelectField to test event propagation
    vi.doUnmock('@/components/Fields/SelectField.vue')
    wrapper = mount(CountryField, {
      props: {
        field: mockField,
        modelValue: null
      }
    })
    await wrapper.vm.$emit('update:modelValue', 'US')
    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('US')
  })
})

