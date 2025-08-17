import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ColorField from '@components/Fields/ColorField.vue'
import { createMockField, mountField } from '@tests/helpers.js'

// Mock the admin store
const mockAdminStore = { isDarkTheme: false }
vi.mock('@/stores/admin', () => ({ useAdminStore: () => mockAdminStore }))

describe('ColorField', () => {
  let wrapper
  let field

  beforeEach(() => {
    field = createMockField({
      name: 'Color',
      attribute: 'color',
      component: 'ColorField'
    })
  })

  afterEach(() => { if (wrapper) wrapper.unmount() })

  it('renders input type="color"', () => {
    wrapper = mountField(ColorField, { field })
    const input = wrapper.find('input[type="color"]')
    expect(input.exists()).toBe(true)
  })

  it('normalizes invalid values to #000000 for input display', () => {
    wrapper = mountField(ColorField, { field, modelValue: 'not-a-color' })
    const input = wrapper.find('input')
    expect(input.element.value.toLowerCase()).toBe('#000000')
  })

  it('emits update:modelValue and change on interactions', async () => {
    wrapper = mountField(ColorField, { field, modelValue: '#112233' })
    const input = wrapper.find('input')

    // simulate color change
    await input.setValue('#aabbcc')
    await input.trigger('input')
    await input.trigger('change')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('#aabbcc')
    expect(wrapper.emitted('change')[0][0]).toBe('#aabbcc')
  })

  it('respects disabled and readonly states', () => {
    wrapper = mount(ColorField, {
      props: { field, modelValue: '#112233', disabled: true, readonly: true }
    })

    const input = wrapper.find('input')
    expect(input.element.disabled).toBe(true)
    expect(input.element.readOnly).toBe(true)
  })

  it('applies dark theme classes when dark theme is active', () => {
    mockAdminStore.isDarkTheme = true
    wrapper = mountField(ColorField, { field, modelValue: '#112233' })

    const input = wrapper.find('input')
    expect(input.classes()).toContain('bg-gray-700')
  })
})

