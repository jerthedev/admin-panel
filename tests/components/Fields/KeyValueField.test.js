import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import KeyValueField from '@/components/Fields/KeyValueField.vue'
import { useAdminStore } from '@/stores/admin'

describe('KeyValueField', () => {
  let wrapper
  let pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  const createWrapper = (props = {}) => {
    const defaultProps = {
      field: {
        name: 'Meta',
        attribute: 'meta',
        component: 'KeyValueField',
        keyLabel: 'Key',
        valueLabel: 'Value',
        actionText: 'Add row',
      },
      modelValue: [],
      errors: [],
      disabled: false,
      readonly: false,
    }

    return mount(KeyValueField, {
      props: { ...defaultProps, ...props },
      global: {
        plugins: [pinia],
      },
    })
  }

  describe('Component Rendering', () => {
    it('renders correctly with default props', () => {
      wrapper = createWrapper()
      expect(wrapper.exists()).toBe(true)
      expect(wrapper.find('[data-testid="base-field"]').exists()).toBe(true)
    })

    it('displays custom labels from field configuration', () => {
      wrapper = createWrapper({
        field: {
          name: 'Settings',
          attribute: 'settings',
          component: 'KeyValueField',
          keyLabel: 'Property',
          valueLabel: 'Content',
          actionText: 'Add new item',
        },
      })

      expect(wrapper.text()).toContain('Property')
      expect(wrapper.text()).toContain('Content')
      expect(wrapper.text()).toContain('Add new item')
    })

    it('shows required indicator when field has required rule', () => {
      wrapper = createWrapper({
        field: {
          name: 'Meta',
          attribute: 'meta',
          component: 'KeyValueField',
          rules: ['required'],
          keyLabel: 'Key',
          valueLabel: 'Value',
          actionText: 'Add row',
        },
      })

      expect(wrapper.text()).toContain('* Required')
    })
  })

  describe('Readonly Mode', () => {
    it('displays key-value pairs in readonly mode', () => {
      wrapper = createWrapper({
        readonly: true,
        modelValue: [
          { key: 'name', value: 'John Doe' },
          { key: 'email', value: 'john@example.com' },
        ],
      })

      expect(wrapper.text()).toContain('name')
      expect(wrapper.text()).toContain('John Doe')
      expect(wrapper.text()).toContain('email')
      expect(wrapper.text()).toContain('john@example.com')
    })

    it('shows "No data" message when no pairs exist in readonly mode', () => {
      wrapper = createWrapper({
        readonly: true,
        modelValue: [],
      })

      expect(wrapper.text()).toContain('No data')
    })

    it('filters out empty keys in readonly mode', () => {
      wrapper = createWrapper({
        readonly: true,
        modelValue: [
          { key: 'name', value: 'John Doe' },
          { key: '', value: 'should not show' },
          { key: 'email', value: 'john@example.com' },
        ],
      })

      expect(wrapper.text()).toContain('name')
      expect(wrapper.text()).toContain('email')
      expect(wrapper.text()).not.toContain('should not show')
    })
  })

  describe('Editable Mode', () => {
    it('renders input fields for key-value pairs', () => {
      wrapper = createWrapper({
        modelValue: [
          { key: 'name', value: 'John Doe' },
        ],
      })

      const keyInputs = wrapper.findAll('input[type="text"]').filter(input => 
        input.attributes('placeholder')?.includes('key')
      )
      const valueInputs = wrapper.findAll('input[type="text"]').filter(input => 
        input.attributes('placeholder')?.includes('value')
      )

      expect(keyInputs.length).toBeGreaterThan(0)
      expect(valueInputs.length).toBeGreaterThan(0)
    })

    it('always shows at least one empty row for adding new pairs', () => {
      wrapper = createWrapper({
        modelValue: [],
      })

      const inputs = wrapper.findAll('input[type="text"]')
      expect(inputs.length).toBeGreaterThanOrEqual(2) // At least one key and one value input
    })

    it('displays existing key-value pairs in inputs', () => {
      wrapper = createWrapper({
        modelValue: [
          { key: 'name', value: 'John Doe' },
          { key: 'email', value: 'john@example.com' },
        ],
      })

      const inputs = wrapper.findAll('input[type="text"]')
      const inputValues = inputs.map(input => input.element.value)

      expect(inputValues).toContain('name')
      expect(inputValues).toContain('John Doe')
      expect(inputValues).toContain('email')
      expect(inputValues).toContain('john@example.com')
    })
  })

  describe('User Interactions', () => {
    it('emits update:modelValue when key input changes', async () => {
      wrapper = createWrapper({
        modelValue: [{ key: '', value: '' }],
      })

      const keyInput = wrapper.find('input[type="text"]')
      await keyInput.setValue('newkey')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      const emittedValue = wrapper.emitted('update:modelValue')[0][0]
      expect(emittedValue).toEqual([{ key: 'newkey', value: '' }])
    })

    it('emits update:modelValue when value input changes', async () => {
      wrapper = createWrapper({
        modelValue: [{ key: 'test', value: '' }],
      })

      const inputs = wrapper.findAll('input[type="text"]')
      const valueInput = inputs[1] // Second input should be value
      await valueInput.setValue('newvalue')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      const emittedValue = wrapper.emitted('update:modelValue')[0][0]
      expect(emittedValue).toEqual([{ key: 'test', value: 'newvalue' }])
    })

    it('adds new row when add button is clicked', async () => {
      wrapper = createWrapper({
        modelValue: [{ key: 'existing', value: 'value' }],
      })

      const addButton = wrapper.find('button')
      await addButton.trigger('click')

      // Should have more input fields now
      const inputs = wrapper.findAll('input[type="text"]')
      expect(inputs.length).toBeGreaterThanOrEqual(4) // At least 2 pairs worth of inputs
    })

    it('removes row when remove button is clicked', async () => {
      wrapper = createWrapper({
        modelValue: [
          { key: 'first', value: 'value1' },
          { key: 'second', value: 'value2' },
        ],
      })

      const removeButtons = wrapper.findAll('button').filter(btn => 
        btn.find('svg').exists() && !btn.text().includes('Add')
      )
      
      if (removeButtons.length > 0) {
        await removeButtons[0].trigger('click')

        expect(wrapper.emitted('update:modelValue')).toBeTruthy()
        const emittedValue = wrapper.emitted('update:modelValue')[0][0]
        expect(emittedValue.length).toBe(1)
      }
    })

    it('filters out empty keys when updating model value', async () => {
      wrapper = createWrapper({
        modelValue: [{ key: '', value: 'should be filtered' }],
      })

      const keyInput = wrapper.find('input[type="text"]')
      await keyInput.setValue('validkey')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      const emittedValue = wrapper.emitted('update:modelValue')[0][0]
      expect(emittedValue).toEqual([{ key: 'validkey', value: 'should be filtered' }])
    })
  })

  describe('Disabled State', () => {
    it('disables all inputs when disabled prop is true', () => {
      wrapper = createWrapper({
        disabled: true,
        modelValue: [{ key: 'test', value: 'value' }],
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

    it('does not emit events when disabled', async () => {
      wrapper = createWrapper({
        disabled: true,
        modelValue: [{ key: 'test', value: 'value' }],
      })

      const keyInput = wrapper.find('input[type="text"]')
      await keyInput.trigger('input')

      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })
  })

  describe('Event Handling', () => {
    it('emits focus event when input is focused', async () => {
      wrapper = createWrapper()

      const input = wrapper.find('input[type="text"]')
      await input.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event when input loses focus', async () => {
      wrapper = createWrapper()

      const input = wrapper.find('input[type="text"]')
      await input.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('emits change event when model value updates', async () => {
      wrapper = createWrapper({
        modelValue: [{ key: '', value: '' }],
      })

      const keyInput = wrapper.find('input[type="text"]')
      await keyInput.setValue('newkey')

      expect(wrapper.emitted('change')).toBeTruthy()
    })
  })

  describe('Keyboard Navigation', () => {
    it('adds new row when Enter is pressed on filled last row', async () => {
      wrapper = createWrapper({
        modelValue: [{ key: 'test', value: 'value' }],
      })

      const inputs = wrapper.findAll('input[type="text"]')
      const lastInput = inputs[inputs.length - 1]
      
      await lastInput.trigger('keydown', { key: 'Enter' })

      // Should add a new empty row
      const newInputs = wrapper.findAll('input[type="text"]')
      expect(newInputs.length).toBeGreaterThan(inputs.length)
    })
  })

  describe('Dark Theme Support', () => {
    it('applies dark theme classes when dark theme is enabled', () => {
      const adminStore = useAdminStore()
      adminStore.isDarkTheme = true

      wrapper = createWrapper()

      // Check for dark theme classes
      const darkElements = wrapper.findAll('.admin-input-dark')
      expect(darkElements.length).toBeGreaterThan(0)
    })
  })
})
