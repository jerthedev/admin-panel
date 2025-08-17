import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mountField } from '../../../helpers.js'
import ColorField from '@/components/Fields/ColorField.vue'

// Mock the admin store
const mockAdminStore = { isDarkTheme: false }
vi.mock('@/stores/admin', () => ({ useAdminStore: () => mockAdminStore }))

describe('Integration: ColorField (PHP <-> Vue)', () => {
  let wrapper
  let field

  beforeEach(() => {
    field = {
      name: 'Color',
      attribute: 'color',
      component: 'ColorField',
      helpText: 'Pick a color',
      rules: ['nullable'],
    }
  })

  afterEach(() => { if (wrapper) wrapper.unmount() })

  it('renders and binds initial value from PHP serialization', () => {
    wrapper = mountField(ColorField, { field, modelValue: '#ff0000' })

    const input = wrapper.find('input[type="color"]')
    expect(input.exists()).toBe(true)
    expect(input.element.value.toLowerCase()).toBe('#ff0000')
  })

  it('emits updated value for PHP fill handling', async () => {
    wrapper = mountField(ColorField, { field, modelValue: '#112233' })

    const input = wrapper.find('input')
    await input.setValue('#aabbcc')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('#aabbcc')
  })
})

