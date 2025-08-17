import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import IDField from '@/components/Fields/IDField.vue'
import { createMockField, mountField } from '../../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('Integration: IDField (PHP <-> Vue)', () => {
  let wrapper
  let field

  beforeEach(() => {
    field = {
      name: 'ID',
      attribute: 'id',
      component: 'IDField',
      sortable: true,
      showOnCreation: false,
      showOnIndex: true,
      showOnDetail: true,
      showOnUpdate: true,
      copyable: true,
      asBigInt: false
    }
  })

  afterEach(() => { if (wrapper) wrapper.unmount() })

  describe('PHP Field Configuration Integration', () => {
    it('renders and binds initial value from PHP serialization', () => {
      wrapper = mountField(IDField, { field, modelValue: 123 })

      const input = wrapper.find('input[type="text"]')
      expect(input.exists()).toBe(true)
      expect(input.element.value).toBe('123')
      expect(input.element.readOnly).toBe(true)
    })

    it('handles PHP asBigInt configuration', () => {
      const bigIntField = createMockField({
        ...field,
        asBigInt: true
      })

      wrapper = mountField(IDField, {
        field: bigIntField,
        modelValue: '9007199254740991'
      })

      // Should handle big integers as strings
      const input = wrapper.find('input')
      expect(input.element.value).toBe('9007199254740991')
      
      // Should have asBigInt in field meta
      expect(wrapper.vm.field.asBigInt).toBe(true)
    })

    it('respects PHP copyable configuration', () => {
      const copyableField = createMockField({
        ...field,
        copyable: true
      })

      wrapper = mountField(IDField, {
        field: copyableField,
        modelValue: 456,
        props: {
          field: copyableField,
          mode: 'index'
        }
      })

      // Should show copy button when copyable and has value
      expect(wrapper.find('button[title="Copy to clipboard"]').exists()).toBe(true)
    })

    it('handles PHP visibility configuration', () => {
      const visibilityField = createMockField({
        ...field,
        showOnCreation: false,
        showOnIndex: true,
        showOnDetail: true,
        showOnUpdate: true
      })

      wrapper = mountField(IDField, { field: visibilityField })

      // Should respect Nova visibility settings
      expect(wrapper.vm.isReadonlyByDefault).toBe(true)
    })
  })

  describe('Display Mode Integration', () => {
    it('renders correctly in index mode from PHP', () => {
      wrapper = mountField(IDField, {
        field,
        modelValue: 789,
        props: {
          field,
          mode: 'index'
        }
      })

      // Should show display mode, not input
      expect(wrapper.find('div.flex.items-center.space-x-2').exists()).toBe(true)
      expect(wrapper.find('span.text-sm.font-mono').exists()).toBe(true)
      expect(wrapper.find('input').exists()).toBe(false)
    })

    it('renders correctly in detail mode from PHP', () => {
      wrapper = mountField(IDField, {
        field,
        modelValue: 101112,
        props: {
          field,
          mode: 'detail'
        }
      })

      // Should show display mode, not input
      expect(wrapper.find('div.flex.items-center.space-x-2').exists()).toBe(true)
      expect(wrapper.find('span.text-sm.font-mono').text()).toBe('101112')
      expect(wrapper.find('input').exists()).toBe(false)
    })

    it('renders correctly in form mode from PHP', () => {
      const formField = createMockField({
        ...field,
        showOnCreation: true
      })

      wrapper = mountField(IDField, {
        field: formField,
        modelValue: 131415,
        props: {
          field: formField,
          mode: 'form'
        }
      })

      // Should show input mode when explicitly shown on creation
      expect(wrapper.find('input').exists()).toBe(true)
      expect(wrapper.find('div.flex.items-center.space-x-2').exists()).toBe(false)
    })
  })

  describe('Value Handling Integration', () => {
    it('handles null values from PHP correctly', () => {
      wrapper = mountField(IDField, {
        field,
        props: {
          field,
          modelValue: null,
          mode: 'index'
        }
      })

      const displaySpan = wrapper.find('span.text-sm.font-mono')
      expect(displaySpan.text()).toBe('—')
    })

    it('handles string ID values from PHP', () => {
      wrapper = mountField(IDField, {
        field,
        modelValue: 'uuid-abc-123-def'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('uuid-abc-123-def')
    })

    it('handles numeric ID values from PHP', () => {
      wrapper = mountField(IDField, {
        field,
        modelValue: 999888777
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('999888777')
    })

    it('handles very large integer IDs with asBigInt', () => {
      const bigIntField = createMockField({
        ...field,
        asBigInt: true
      })

      wrapper = mountField(IDField, {
        field: bigIntField,
        modelValue: '18446744073709551615' // Max unsigned 64-bit integer
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('18446744073709551615')
    })
  })

  describe('Nova API Compatibility Integration', () => {
    it('integrates all Nova API methods correctly', () => {
      const phpFieldConfig = createMockField({
        name: 'User ID',
        attribute: 'user_id',
        component: 'IDField',
        sortable: true,
        showOnCreation: false,
        showOnIndex: true,
        showOnDetail: true,
        showOnUpdate: true,
        copyable: true,
        asBigInt: true
      })

      wrapper = mount(IDField, {
        props: {
          field: phpFieldConfig,
          modelValue: '9223372036854775807'
        }
      })

      // Test complete integration
      expect(wrapper.vm.field.name).toBe('User ID')
      expect(wrapper.vm.field.attribute).toBe('user_id')
      expect(wrapper.vm.field.component).toBe('IDField')
      expect(wrapper.vm.field.sortable).toBe(true)
      expect(wrapper.vm.field.showOnCreation).toBe(false)
      expect(wrapper.vm.field.asBigInt).toBe(true)
      expect(wrapper.vm.field.copyable).toBe(true)
    })

    it('maintains Nova field defaults', () => {
      const defaultField = createMockField({
        name: 'ID',
        attribute: 'id',
        component: 'IDField'
      })

      wrapper = mount(IDField, {
        props: {
          field: defaultField,
          modelValue: 42
        }
      })

      // Should have Nova defaults
      expect(wrapper.vm.field.name).toBe('ID')
      expect(wrapper.vm.field.attribute).toBe('id')
      expect(wrapper.vm.field.component).toBe('IDField')
    })
  })

  describe('CRUD Operations Integration', () => {
    it('handles create operation with ID field', () => {
      wrapper = mountField(IDField, {
        field,
        props: {
          field,
          modelValue: null, // New record
          mode: 'form'
        }
      })

      // Should show readonly input for new record (ID not yet assigned)
      const input = wrapper.find('input')
      expect(input.exists()).toBe(true)
      expect(input.element.readOnly).toBe(true)
      expect(input.element.value).toBe('')
    })

    it('handles read operation with formatted display', () => {
      wrapper = mountField(IDField, {
        field,
        modelValue: 123456,
        props: {
          field,
          mode: 'detail'
        }
      })

      // Should display value for reading
      const displaySpan = wrapper.find('span.text-sm.font-mono')
      expect(displaySpan.text()).toBe('123456')
    })

    it('handles update operation maintaining readonly state', () => {
      wrapper = mountField(IDField, {
        field,
        modelValue: 789012,
        props: {
          field,
          mode: 'form'
        }
      })

      // ID should remain readonly even in update mode
      const input = wrapper.find('input')
      expect(input.exists()).toBe(true)
      expect(input.element.readOnly).toBe(true)
      expect(input.element.value).toBe('789012')
    })
  })

  describe('Copy Functionality Integration', () => {
    beforeEach(() => {
      // Mock clipboard API
      Object.assign(navigator, {
        clipboard: {
          writeText: vi.fn().mockResolvedValue()
        }
      })
    })

    it('integrates copy functionality with PHP copyable setting', async () => {
      const copyableField = createMockField({
        ...field,
        copyable: true
      })

      wrapper = mountField(IDField, {
        field: copyableField,
        modelValue: 'copy-test-123',
        props: {
          field: copyableField,
          mode: 'index'
        }
      })

      const copyButton = wrapper.find('button[title="Copy to clipboard"]')
      expect(copyButton.exists()).toBe(true)

      await copyButton.trigger('click')
      expect(navigator.clipboard.writeText).toHaveBeenCalledWith('copy-test-123')
    })

    it('respects PHP non-copyable setting', () => {
      const nonCopyableField = createMockField({
        ...field,
        copyable: false
      })

      wrapper = mountField(IDField, {
        field: nonCopyableField,
        modelValue: 'no-copy-456',
        props: {
          field: nonCopyableField,
          mode: 'index'
        }
      })

      // Should not show copy button
      expect(wrapper.find('button').exists()).toBe(false)
    })
  })
})
