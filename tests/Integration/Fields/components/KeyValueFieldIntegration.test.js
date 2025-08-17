import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import KeyValueField from '@/components/Fields/KeyValueField.vue'

// Helper function to mount field with realistic props
const mountField = (component, options = {}) => {
  const defaultOptions = {
    props: {
      field: {
        name: 'Meta',
        attribute: 'meta',
        component: 'KeyValueField',
        keyLabel: 'Key',
        valueLabel: 'Value',
        actionText: 'Add row',
        rules: [],
        helpText: null,
      },
      modelValue: [],
      errors: {},
      disabled: false,
      readonly: false,
      size: 'default',
    },
    global: {
      plugins: [createPinia()],
    },
  }

  return mount(component, {
    ...defaultOptions,
    ...options,
    props: { ...defaultOptions.props, ...options.props },
  })
}

describe('Integration: KeyValueField (PHP <-> Vue)', () => {
  let wrapper
  let field

  beforeEach(() => {
    setActivePinia(createPinia())
    field = {
      name: 'Meta',
      attribute: 'meta',
      component: 'KeyValueField',
      helpText: 'Enter key-value pairs for metadata',
      rules: ['json'],
      keyLabel: 'Property',
      valueLabel: 'Content',
      actionText: 'Add new property',
    }
  })

  afterEach(() => { if (wrapper) wrapper.unmount() })

  it('renders and binds initial value from PHP serialization', () => {
    wrapper = mountField(KeyValueField, { 
      props: { 
        field, 
        modelValue: [
          { key: 'name', value: 'John Doe' },
          { key: 'email', value: 'john@example.com' },
        ]
      } 
    })

    const inputs = wrapper.findAll('input[type="text"]')
    const inputValues = inputs.map(input => input.element.value)

    expect(inputValues).toContain('name')
    expect(inputValues).toContain('John Doe')
    expect(inputValues).toContain('email')
    expect(inputValues).toContain('john@example.com')
  })

  it('displays custom labels from PHP field configuration', () => {
    wrapper = mountField(KeyValueField, { props: { field } })

    expect(wrapper.text()).toContain('Property')
    expect(wrapper.text()).toContain('Content')
    expect(wrapper.text()).toContain('Add new property')
  })

  it('handles empty/null values from PHP gracefully', () => {
    wrapper = mountField(KeyValueField, { 
      props: { field, modelValue: null } 
    })

    expect(wrapper.exists()).toBe(true)
    // Should show at least one empty input row
    const inputs = wrapper.findAll('input[type="text"]')
    expect(inputs.length).toBeGreaterThanOrEqual(2)
  })

  it('emits data in format expected by PHP backend', async () => {
    wrapper = mountField(KeyValueField, { 
      props: { field, modelValue: [] } 
    })

    // Simulate user adding key-value pairs
    const inputs = wrapper.findAll('input[type="text"]')
    const keyInput = inputs[0]
    const valueInput = inputs[1]

    await keyInput.setValue('name')
    await valueInput.setValue('John Doe')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    const emittedValue = wrapper.emitted('update:modelValue')[0][0]
    
    // Should emit array of key-value objects as expected by PHP
    expect(emittedValue).toEqual([
      { key: 'name', value: 'John Doe' }
    ])
  })

  it('handles validation rules from PHP field configuration', () => {
    const fieldWithValidation = {
      ...field,
      rules: ['required', 'json'],
    }

    wrapper = mountField(KeyValueField, { 
      props: { field: fieldWithValidation } 
    })

    expect(wrapper.text()).toContain('* Required')
  })

  it('supports readonly mode for display-only contexts', () => {
    wrapper = mountField(KeyValueField, { 
      props: { 
        field, 
        readonly: true,
        modelValue: [
          { key: 'name', value: 'John Doe' },
          { key: 'email', value: 'john@example.com' },
        ]
      } 
    })

    // Should not have any input fields in readonly mode
    const inputs = wrapper.findAll('input[type="text"]')
    expect(inputs.length).toBe(0)

    // Should display the values
    expect(wrapper.text()).toContain('name')
    expect(wrapper.text()).toContain('John Doe')
    expect(wrapper.text()).toContain('email')
    expect(wrapper.text()).toContain('john@example.com')
  })

  it('handles disabled state from PHP field configuration', () => {
    wrapper = mountField(KeyValueField, { 
      props: { field, disabled: true } 
    })

    const inputs = wrapper.findAll('input[type="text"]')
    inputs.forEach(input => {
      expect(input.attributes('disabled')).toBeDefined()
    })

    const buttons = wrapper.findAll('button')
    buttons.forEach(button => {
      expect(button.attributes('disabled')).toBeDefined()
    })
  })

  it('filters out empty keys when sending data to PHP', async () => {
    wrapper = mountField(KeyValueField, { 
      props: { field, modelValue: [] } 
    })

    const inputs = wrapper.findAll('input[type="text"]')
    
    // Add a valid pair
    await inputs[0].setValue('validkey')
    await inputs[1].setValue('validvalue')

    // Try to add an invalid pair with empty key
    const addButton = wrapper.find('button')
    await addButton.trigger('click')

    const newInputs = wrapper.findAll('input[type="text"]')
    await newInputs[2].setValue('') // Empty key
    await newInputs[3].setValue('should be filtered')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    const lastEmittedValue = wrapper.emitted('update:modelValue').slice(-1)[0][0]
    
    // Should only contain the valid pair
    expect(lastEmittedValue).toEqual([
      { key: 'validkey', value: 'validvalue' }
    ])
  })

  it('maintains data integrity during add/remove operations', async () => {
    wrapper = mountField(KeyValueField, { 
      props: { 
        field, 
        modelValue: [
          { key: 'first', value: 'value1' },
          { key: 'second', value: 'value2' },
        ]
      } 
    })

    // Add a new pair
    const addButton = wrapper.find('button')
    await addButton.trigger('click')

    const inputs = wrapper.findAll('input[type="text"]')
    // Find the new empty inputs (should be the last ones)
    const newKeyInput = inputs[inputs.length - 2]
    const newValueInput = inputs[inputs.length - 1]

    await newKeyInput.setValue('third')
    await newValueInput.setValue('value3')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    const emittedValue = wrapper.emitted('update:modelValue').slice(-1)[0][0]
    
    expect(emittedValue).toEqual([
      { key: 'first', value: 'value1' },
      { key: 'second', value: 'value2' },
      { key: 'third', value: 'value3' },
    ])
  })

  it('handles complex values including special characters', async () => {
    wrapper = mountField(KeyValueField, { 
      props: { field, modelValue: [] } 
    })

    const inputs = wrapper.findAll('input[type="text"]')
    const keyInput = inputs[0]
    const valueInput = inputs[1]

    // Test with special characters and JSON-like values
    await keyInput.setValue('config.database.host')
    await valueInput.setValue('{"host": "localhost", "port": 5432}')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    const emittedValue = wrapper.emitted('update:modelValue')[0][0]
    
    expect(emittedValue).toEqual([
      { key: 'config.database.host', value: '{"host": "localhost", "port": 5432}' }
    ])
  })

  it('supports help text from PHP field configuration', () => {
    wrapper = mountField(KeyValueField, { props: { field } })

    // Help text should be rendered by BaseField component
    expect(wrapper.html()).toContain('Enter key-value pairs for metadata')
  })

  it('handles error states from PHP validation', () => {
    const errors = {
      meta: ['The meta field is required.']
    }

    wrapper = mountField(KeyValueField, { 
      props: { field, errors } 
    })

    // Error handling should be managed by BaseField component
    expect(wrapper.exists()).toBe(true)
  })

  it('maintains proper data flow for form submission', async () => {
    wrapper = mountField(KeyValueField, { 
      props: { field, modelValue: [] } 
    })

    // Simulate user creating multiple key-value pairs
    const inputs = wrapper.findAll('input[type="text"]')
    
    // First pair
    await inputs[0].setValue('name')
    await inputs[1].setValue('John Doe')

    // Add second pair
    const addButton = wrapper.find('button')
    await addButton.trigger('click')

    const newInputs = wrapper.findAll('input[type="text"]')
    await newInputs[2].setValue('email')
    await newInputs[3].setValue('john@example.com')

    // Final emitted value should be ready for PHP processing
    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    const finalValue = wrapper.emitted('update:modelValue').slice(-1)[0][0]
    
    expect(finalValue).toEqual([
      { key: 'name', value: 'John Doe' },
      { key: 'email', value: 'john@example.com' },
    ])

    // This format should be exactly what PHP KeyValue field expects
    expect(Array.isArray(finalValue)).toBe(true)
    finalValue.forEach(pair => {
      expect(pair).toHaveProperty('key')
      expect(pair).toHaveProperty('value')
      expect(typeof pair.key).toBe('string')
      expect(typeof pair.value).toBe('string')
    })
  })
})
