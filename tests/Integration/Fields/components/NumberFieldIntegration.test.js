import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mountField } from '../../../helpers.js'
import NumberField from '@/components/Fields/NumberField.vue'

// Mock the admin store
const mockAdminStore = { isDarkTheme: false }
vi.mock('@/stores/admin', () => ({ useAdminStore: () => mockAdminStore }))

describe('Integration: NumberField (PHP <-> Vue)', () => {
  let wrapper
  let field

  beforeEach(() => {
    field = {
      name: 'Price',
      attribute: 'price',
      component: 'NumberField',
      helpText: 'Enter price in USD',
      rules: ['required', 'numeric', 'min:0', 'max:9999.99'],
      min: 0,
      max: 9999.99,
      step: 0.01,
    }
  })

  afterEach(() => { if (wrapper) wrapper.unmount() })

  it('renders and binds initial value from PHP serialization', () => {
    wrapper = mountField(NumberField, { field, modelValue: 19.99 })

    const input = wrapper.find('input[type="number"]')
    expect(input.exists()).toBe(true)
    expect(input.element.value).toBe('19.99')
  })

  it('emits updated value for PHP fill handling', async () => {
    wrapper = mountField(NumberField, { field, modelValue: 10.50 })

    const input = wrapper.find('input')
    await input.setValue('25.99')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(25.99)
  })

  it('respects min/max/step meta from PHP field', () => {
    wrapper = mountField(NumberField, { field, modelValue: 50 })

    const input = wrapper.find('input')
    expect(input.attributes('min')).toBe('0')
    expect(input.attributes('max')).toBe('9999.99')
    expect(input.attributes('step')).toBe('0.01')
  })

  it('handles integer values from PHP backend', async () => {
    const integerField = {
      ...field,
      name: 'Quantity',
      attribute: 'quantity',
      min: 1,
      max: 100,
      step: 1,
    }

    wrapper = mountField(NumberField, { field: integerField, modelValue: 5 })

    const input = wrapper.find('input')
    await input.setValue('10')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(10)
  })

  it('handles float values from PHP backend', async () => {
    wrapper = mountField(NumberField, { field, modelValue: 19.99 })

    const input = wrapper.find('input')
    await input.setValue('25.50')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(25.50)
  })

  it('handles null values from PHP', () => {
    wrapper = mountField(NumberField, { field, modelValue: null })

    const input = wrapper.find('input')
    expect(input.element.value).toBe('')
  })

  it('emits null for empty input to match PHP behavior', async () => {
    wrapper = mountField(NumberField, { field, modelValue: 25 })

    const input = wrapper.find('input')
    await input.setValue('')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
  })

  it('handles zero values correctly', async () => {
    wrapper = mountField(NumberField, { field, modelValue: 10 })

    const input = wrapper.find('input')
    await input.setValue('0')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(0)
  })

  it('handles negative values when min allows', async () => {
    const negativeField = {
      ...field,
      name: 'Temperature',
      attribute: 'temperature',
      min: -100,
      max: 100,
      step: 1,
    }

    wrapper = mountField(NumberField, { field: negativeField, modelValue: 0 })

    const input = wrapper.find('input')
    await input.setValue('-15')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(-15)
  })

  it('handles large numbers correctly', async () => {
    const largeField = {
      ...field,
      name: 'Population',
      attribute: 'population',
      min: null,
      max: null,
      step: 1,
    }

    wrapper = mountField(NumberField, { field: largeField, modelValue: 0 })

    const input = wrapper.find('input')
    await input.setValue('1000000')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(1000000)
  })

  it('handles decimal precision correctly', async () => {
    const precisionField = {
      ...field,
      name: 'Precision',
      attribute: 'precision',
      min: null,
      max: null,
      step: 0.001,
    }

    wrapper = mountField(NumberField, { field: precisionField, modelValue: 0 })

    const input = wrapper.find('input')
    await input.setValue('1.234')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(1.234)
  })

  it('integrates with form validation states', () => {
    const fieldWithErrors = {
      ...field,
      hasError: true,
    }
    
    const errors = { price: ['The price field is required.'] }
    
    wrapper = mountField(NumberField, { 
      field: fieldWithErrors, 
      modelValue: null,
      errors 
    })

    // Component should handle error state appropriately
    expect(wrapper.props('errors')).toEqual(errors)
  })

  it('maintains focus behavior for form interactions', async () => {
    wrapper = mountField(NumberField, { field, modelValue: 0 })

    const input = wrapper.find('input')
    await input.trigger('focus')

    expect(wrapper.emitted('focus')).toBeTruthy()

    await input.trigger('blur')
    expect(wrapper.emitted('blur')).toBeTruthy()
  })

  it('handles readonly state for detail views', () => {
    wrapper = mountField(NumberField, {
      field,
      modelValue: 19.99,
      readonly: true
    })

    const input = wrapper.find('input')
    expect(input.element.readOnly).toBe(true)
  })

  it('handles disabled state', () => {
    wrapper = mountField(NumberField, {
      field,
      modelValue: 19.99,
      disabled: true
    })

    const input = wrapper.find('input')
    expect(input.element.disabled).toBe(true)
  })

  it('applies dark theme classes when enabled', () => {
    mockAdminStore.isDarkTheme = true
    
    wrapper = mountField(NumberField, { field, modelValue: 0 })

    const input = wrapper.find('input')
    expect(input.classes()).toContain('admin-input-dark')
    
    // Reset for other tests
    mockAdminStore.isDarkTheme = false
  })

  it('exposes focus method for external control', () => {
    wrapper = mountField(NumberField, { field, modelValue: 0 })

    expect(wrapper.vm.focus).toBeDefined()
    expect(typeof wrapper.vm.focus).toBe('function')
  })

  it('nova_api_compatibility_complete', () => {
    // Test complete Nova Number field API compatibility
    const novaField = {
      name: 'Age',
      attribute: 'age',
      component: 'NumberField',
      min: 18,
      max: 120,
      step: 1,
    }

    wrapper = mountField(NumberField, { field: novaField, modelValue: 25 })

    const input = wrapper.find('input')
    expect(input.attributes('type')).toBe('number')
    expect(input.attributes('min')).toBe('18')
    expect(input.attributes('max')).toBe('120')
    expect(input.attributes('step')).toBe('1')
    expect(input.element.value).toBe('25')
  })

  it('handles field without min/max/step (Nova defaults)', () => {
    const basicField = {
      name: 'Count',
      attribute: 'count',
      component: 'NumberField',
      min: null,
      max: null,
      step: null,
    }

    wrapper = mountField(NumberField, { field: basicField, modelValue: 42 })

    const input = wrapper.find('input')
    expect(input.attributes('type')).toBe('number')
    expect(input.attributes('step')).toBe('1') // Default step
    expect(input.element.value).toBe('42')
  })

  it('emits change events for form handling', async () => {
    wrapper = mountField(NumberField, { field, modelValue: 10 })

    const input = wrapper.find('input')
    await input.setValue('20')
    await input.trigger('input')

    expect(wrapper.emitted('change')).toBeTruthy()
    expect(wrapper.emitted('change')[0][0]).toBe(20)
  })

  it('handles complex decimal calculations', async () => {
    wrapper = mountField(NumberField, { field, modelValue: 0 })

    const input = wrapper.find('input')
    await input.setValue('19.999')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(19.999)
  })

  it('preserves numeric types for PHP backend', async () => {
    // Test that integers stay integers and floats stay floats
    const intField = {
      ...field,
      step: 1,
    }

    wrapper = mountField(NumberField, { field: intField, modelValue: 0 })

    const input = wrapper.find('input')
    await input.setValue('42')
    await input.trigger('input')

    // Should emit as number, not string
    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(42)
    expect(typeof wrapper.emitted('update:modelValue')[0][0]).toBe('number')
  })
})
